<?php
namespace classes\http;

use classes\Methods;
use classes\app\CronWorker;
use classes\payments\CardValidationService;
use classes\notifications\NotificationTriggers;
use features\Settings;


class CronRequestHandler {

    /**
     * Lock timeout in seconds - if a payment is locked longer than this, assume the process died
     */
    private const PAYMENT_LOCK_TIMEOUT = 120; // 2 minutes

    /**
     * Lock timeout for notification processing (separate from payment charging)
     */
    private const NOTIFICATION_LOCK_TIMEOUT = 120; // 2 minutes

    /**
     * Take scheduled payments (BNPL installments, deferred "pushed" payments)
     * Processes payments with status SCHEDULED that are due today or earlier
     *
     * Fetches and processes one payment at a time until:
     * - No more payments to process, OR
     * - Worker timeout is reached (finishes current payment first)
     *
     * Uses processing_at timestamp as a lock to prevent multiple crons processing same payment
     *
     * Runs every 5 mins with max 3 min runtime
     */
    public function takePayments(?CronWorker $worker = null): void {
        $worker?->log("Running takePayments...");

        $paymentsHandler = Methods::payments();
        $today = date('Y-m-d 23:59:59'); // Include all of today
        $lockTimeout = date('Y-m-d H:i:s', time() - self::PAYMENT_LOCK_TIMEOUT);

        $totalProcessed = 0;
        $totalSuccess = 0;
        $totalFailed = 0;

        $now = date('Y-m-d H:i:s');

        while (true) {
            // Get single SCHEDULED payment due today or earlier
            // Exclude payments currently being processed (locked) unless lock is stale
            // For retried payments, also check scheduled_at (next retry time)
            $payment = $paymentsHandler->excludeForeignKeys()->queryBuilder()
                ->where('status', 'SCHEDULED')
                ->where('due_date', '<=', $today)
                ->whereNotNull('initial_transaction_id')
                // Check that it's time to process (either no scheduled_at or scheduled_at has passed)
                ->startGroup('OR')
                    ->whereNull('scheduled_at')
                    ->where('scheduled_at', '<=', $now)
                ->endGroup()
                // Check not currently locked (or lock is stale)
                ->startGroup('OR')
                    ->whereNull('processing_at')
                    ->where('processing_at', '<', $lockTimeout)
                ->endGroup()
                ->order('due_date', 'ASC')
                ->first();

//            $worker?->log(json_encode($payment));
//            break;

            if (isEmpty($payment)) {
                $worker?->log("No more scheduled payments to process.");
                break;
            }

            // Try to acquire lock by setting processing_at
            // Use a conditional update to ensure atomicity
            $lockAcquired = $this->acquirePaymentLock($payment->uid, $lockTimeout);
            if (!$lockAcquired) {
                $worker?->log("Payment {$payment->uid} already locked by another process, skipping.");
                usleep(100000); // 100ms before trying next
                continue;
            }

            // Process this payment
            $result = $this->processScheduledPayment($payment->uid, $worker);
            $totalProcessed++;

            if ($result['success']) {
                $totalSuccess++;
            } else {
                $totalFailed++;
            }

            // Clear the lock (processing_at) - status change handles this implicitly
            // but we clear it explicitly for failed payments that stay SCHEDULED
            $this->releasePaymentLock($payment->uid);

            // Small delay between payments to avoid hammering Viva API
            usleep(200000); // 200ms

            // Check if we should stop AFTER completing current payment
            if ($worker !== null && !$worker->canRun()) {
                $worker?->log("Worker timeout reached, stopping after completing payment.");
                break;
            }
        }

        $worker?->log("takePayments completed. Processed: $totalProcessed (success: $totalSuccess, failed: $totalFailed)");
    }

    /**
     * Acquire lock on a payment for processing
     * Returns true if lock was acquired, false if already locked by another process
     */
    private function acquirePaymentLock(string $paymentUid, string $lockTimeout): bool {
        $now = date('Y-m-d H:i:s');

        // Update only if not locked or lock is stale
        $query = Methods::payments()->queryBuilder()
            ->where('uid', $paymentUid)
            ->startGroup('OR')
                ->whereNull('processing_at')
                ->where('processing_at', '<', $lockTimeout)
            ->endGroup();

        return Methods::payments()->queryUpdate($query, ['processing_at' => $now]);
    }

    /**
     * Release lock on a payment after processing
     */
    private function releasePaymentLock(string $paymentUid): void {
        Methods::payments()->update(['processing_at' => null], ['uid' => $paymentUid]);
    }

    /**
     * Process a single scheduled payment
     *
     * @param string $paymentUid Payment UID
     * @param CronWorker|null $worker
     * @return array{success: bool, error?: string}
     */
    private function processScheduledPayment(string $paymentUid, ?CronWorker $worker = null): array {
        $paymentsHandler = Methods::payments();

        // Get payment with resolved foreign keys for merchant_prid access
        // Explicitly enable foreign keys to ensure organisation is resolved as an object
        $payment = $paymentsHandler->includeForeignKeys()->get($paymentUid);
        if (isEmpty($payment)) {
            $worker?->log("Payment $paymentUid not found, skipping.");
            return ['success' => false, 'error' => 'Payment not found'];
        }

        // Get organisation - may be string (UID) or object depending on FK resolution order
        // When order FK is resolved first, organisations table is already in $tables and skipped
        // So we need to handle both cases or fetch organisation directly
        $organisationUid = is_object($payment->organisation)
            ? $payment->organisation->uid
            : $payment->organisation;

        // Fetch organisation directly to ensure we have merchant_prid
        $organisation = Methods::organisations()->get($organisationUid);

        // Debug log payment data
        debugLog([
            'payment_uid' => $paymentUid,
            'status' => $payment->status,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'organisation_uid' => $organisationUid,
            'organisation_resolved' => $organisation ? 'YES' : 'NO',
            'merchant_prid' => $organisation->merchant_prid ?? 'NULL',
            'initial_transaction_id' => $payment->initial_transaction_id ?? 'NULL',
            'attempts' => $payment->attempts ?? 0,
        ], 'CRON_PROCESS_PAYMENT_START');

        // Double-check status (in case another process handled it)
        if ($payment->status !== 'SCHEDULED') {
            $worker?->log("Payment $paymentUid status is {$payment->status}, skipping.");
            return ['success' => false, 'error' => 'Payment status changed'];
        }

        // Get merchant_prid from organisation
        $merchantId = $organisation->merchant_prid ?? null;
        if (isEmpty($merchantId)) {
            $error = 'Missing merchant_prid';
            $worker?->log("Payment $paymentUid: $error");
            debugLog([
                'payment_uid' => $paymentUid,
                'organisation_uid' => $organisationUid,
                'organisation_found' => $organisation ? 'YES' : 'NO',
            ], 'CRON_PAYMENT_MISSING_MERCHANT_PRID');
            return $this->handlePaymentAttemptFailure($payment, $error, $worker);
        }

        // Get initial transaction ID for recurring charge
        $initialTransactionId = $payment->initial_transaction_id;
        if (isEmpty($initialTransactionId)) {
            $error = 'Missing initial_transaction_id';
            $worker?->log("Payment $paymentUid: $error");
            return $this->handlePaymentAttemptFailure($payment, $error, $worker);
        }

        $worker?->log("Charging payment $paymentUid: {$payment->amount} {$payment->currency} (attempt " . (($payment->attempts ?? 0) + 1) . ")");

        // Attempt to charge using stored card
        $isTestPayment = (bool)($payment->test ?? false);
        $isvAmount = !empty($payment->isv_amount) ? (float)$payment->isv_amount : null;
        $chargeResult = CardValidationService::chargeWithStoredCard(
            $merchantId,
            $initialTransactionId,
            (float)$payment->amount,
            $payment->currency,
            "Betaling rate {$payment->installment_number}",
            $isTestPayment,
            $isvAmount
        );

        debugLog([
            'payment_uid' => $paymentUid,
            'charge_result' => $chargeResult,
        ], 'CRON_PAYMENT_CHARGE_RESULT');

        if ($chargeResult['success']) {
            // Mark payment as completed
            $paymentsHandler->markAsCompleted($paymentUid, $chargeResult['transaction_id'] ?? null);
            $worker?->log("Payment $paymentUid charged successfully.");

            debugLog([
                'payment_uid' => $paymentUid,
                'about_to_call' => 'triggerPaymentNotification',
                'payment_uuid_type' => gettype($payment->uuid ?? null),
                'payment_order_type' => gettype($payment->order ?? null),
            ], 'DEEP_BEFORE_TRIGGER_NOTIFICATION');

            // Trigger success notification
            $this->triggerPaymentNotification($payment, 'success');

            debugLog(['payment_uid' => $paymentUid, 'done' => true], 'DEEP_AFTER_TRIGGER_NOTIFICATION');

            return ['success' => true];
        } else {
            $failureReason = $chargeResult['error'] ?? 'Charge failed';
            return $this->handlePaymentAttemptFailure($payment, $failureReason, $worker);
        }
    }

    /**
     * Handle a failed payment attempt - mark as PAST_DUE and schedule first rykker
     *
     * @param object $payment Payment object with resolved FKs
     * @param string $failureReason
     * @param CronWorker|null $worker
     * @return array{success: bool, error: string}
     */
    private function handlePaymentAttemptFailure(object $payment, string $failureReason, ?CronWorker $worker = null): array {
        $paymentsHandler = Methods::payments();
        $currentAttempts = ($payment->attempts ?? 0) + 1;

        debugLog([
            'payment_uid' => $payment->uid,
            'failure_reason' => $failureReason,
            'current_attempts' => $currentAttempts,
        ], 'CRON_PAYMENT_ATTEMPT_FAILURE');

        // Get days until first rykker from settings
        $rykker1Days = (int)(Settings::$app->rykker_1_days ?? 7);

        // Mark as PAST_DUE and schedule first rykker check
        $paymentsHandler->update([
            'status' => 'PAST_DUE',
            'failure_reason' => $failureReason,
            'attempts' => $currentAttempts,
            'scheduled_at' => date('Y-m-d H:i:s', strtotime("+{$rykker1Days} days")),
        ], ['uid' => $payment->uid]);

        $worker?->log("Payment {$payment->uid} marked as PAST_DUE, first rykker scheduled in {$rykker1Days} days");

        // Trigger payment past due notification
        $this->triggerPaymentNotification($payment, 'past_due', $failureReason);

        return ['success' => false, 'error' => $failureReason];
    }

    /**
     * Trigger payment notification (success or failed)
     *
     * @param object $payment Payment object with resolved FKs
     * @param string $type 'success' or 'failed'
     * @param string|null $failureReason
     */
    private function triggerPaymentNotification(object $payment, string $type, ?string $failureReason = null): void {
        debugLog([
            'payment_uid' => $payment->uid,
            'type' => $type,
            'timestamp' => date('Y-m-d H:i:s.u'),
        ], 'DEEP_TRIGGER_PAYMENT_NOTIFICATION_ENTRY');

        try {
            // Get order from payment (FK should be resolved)
            $order = $payment->order ?? null;

            // Get user - handle FK resolution order issue
            // payment.uuid may be a string while payment.order.uuid is resolved
            $user = $payment->uuid ?? null;
            if (!is_object($user) && is_object($order) && is_object($order->uuid ?? null)) {
                // User not resolved on payment, but resolved on order
                $user = $order->uuid;
            } elseif (!is_object($user) && !isEmpty($user)) {
                // Still a string, fetch directly
                $user = Methods::users()->get($user);
            }

            debugLog([
                'payment_uid' => $payment->uid,
                'user_resolved' => is_object($user),
                'user_type' => gettype($user),
                'user_uid' => is_object($user) ? ($user->uid ?? 'no_uid') : $user,
                'user_email' => is_object($user) ? ($user->email ?? 'no_email') : 'not_object',
                'order_resolved' => is_object($order),
                'order_type' => gettype($order),
                'order_uid' => is_object($order) ? ($order->uid ?? 'no_uid') : $order,
            ], 'DEEP_TRIGGER_PAYMENT_NOTIFICATION_DATA');

            if ($type === 'success') {
                debugLog(['calling' => 'paymentSuccessful'], 'DEEP_TRIGGER_ABOUT_TO_CALL');
                $result = NotificationTriggers::paymentSuccessful($payment, $user, $order);
                debugLog(['result' => $result], 'DEEP_TRIGGER_PAYMENT_SUCCESSFUL_RESULT');
            } elseif ($type === 'past_due') {
                debugLog(['calling' => 'paymentPastDue'], 'DEEP_TRIGGER_ABOUT_TO_CALL');
                $result = NotificationTriggers::paymentPastDue($payment, $user, $order, $failureReason);
                debugLog(['result' => $result], 'DEEP_TRIGGER_PAYMENT_PAST_DUE_RESULT');
            } else {
                debugLog(['calling' => 'paymentFailed'], 'DEEP_TRIGGER_ABOUT_TO_CALL');
                $result = NotificationTriggers::paymentFailed($payment, $user, $order, $failureReason);
                debugLog(['result' => $result], 'DEEP_TRIGGER_PAYMENT_FAILED_RESULT');
            }

            debugLog([
                'payment_uid' => $payment->uid,
                'type' => $type,
                'completed' => true,
            ], 'DEEP_TRIGGER_PAYMENT_NOTIFICATION_COMPLETE');

        } catch (\Throwable $e) {
            debugLog([
                'payment_uid' => $payment->uid,
                'type' => $type,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => array_slice($e->getTrace(), 0, 5),
            ], 'DEEP_TRIGGER_PAYMENT_NOTIFICATION_ERROR');

            errorLog([
                'payment_uid' => $payment->uid,
                'type' => $type,
                'error' => $e->getMessage(),
            ], 'payment-notification-trigger-error');
        }
    }


    /**
     * Clean up old log files
     * Removes logs older than configured retention period
     */
    public function cleanupLogs(?CronWorker $worker = null): void {
        $worker?->log("Running cleanupLogs...");

        $logDirs = [
            ROOT . 'logs/debug/',
            ROOT . 'logs/cron/',
        ];

        $retentionDays = 30; // Keep logs for 30 days
        $cutoffTime = time() - ($retentionDays * 24 * 60 * 60);
        $deletedCount = 0;

        foreach ($logDirs as $dir) {
            if (!is_dir($dir)) continue;

            $files = glob($dir . '*.log');
            foreach ($files as $file) {
                if (filemtime($file) < $cutoffTime) {
                    if (unlink($file)) {
                        $deletedCount++;
                        $worker?->log("Deleted old log file: " . basename($file));
                    }
                }
            }
        }

        $worker?->log("cleanupLogs completed. Deleted $deletedCount files.");
    }


    /**
     * Send payment due reminder notifications
     *
     * Finds payments where due_date matches any active flow offset (e.g., today+5, today+1)
     * Sends notifications directly (no queue) and logs to NotificationLog for deduplication
     *
     * Follows the same pattern as takePayments:
     * - Processes one payment at a time in a while loop
     * - Uses notification_lock_at to prevent race conditions
     * - Respects worker timeout
     *
     * Runs every 5 mins with max 3 min runtime
     */
    public function paymentNotifications(?CronWorker $worker = null): void {
        $worker?->log("Running paymentNotifications...");

        $flowsHandler = Methods::notificationFlows();
        $lockTimeout = date('Y-m-d H:i:s', time() - self::NOTIFICATION_LOCK_TIMEOUT);

        // Get all active flows for payment.due_reminder breakpoint with their offsets
        $reminderFlows = $flowsHandler->getActiveByBreakpoint('payment.due_reminder');
        if ($reminderFlows->empty()) {
            $worker?->log("No active reminder flows found.");
            return;
        }

        // Calculate target due dates from flow offsets
        // e.g., offset -5 means "5 days before due" → look for payments due in 5 days
        $targetDates = [];
        $flowsByOffset = [];
        foreach ($reminderFlows->list() as $flow) {
            $offset = abs((int)($flow->schedule_offset_days ?? 0));
            $targetDate = date('Y-m-d', strtotime("+{$offset} days"));
            $targetDates[$targetDate] = true;

            if (!isset($flowsByOffset[$offset])) {
                $flowsByOffset[$offset] = [];
            }
            $flowsByOffset[$offset][] = $flow;
        }
        $targetDates = array_keys($targetDates);

        $worker?->log("Target due dates: " . implode(', ', $targetDates));
        $worker?->log("Flow offsets: " . implode(', ', array_keys($flowsByOffset)));

        $totalProcessed = 0;
        $totalSent = 0;
        $totalSkipped = 0;

        while (true) {
            // Find next payment needing notification
            $payment = $this->getNextPaymentForNotification($targetDates, $lockTimeout);

            if (isEmpty($payment)) {
                $worker?->log("No more payments to notify.");
                break;
            }

            // Acquire lock atomically
            if (!$this->acquireNotificationLock($payment->uid, $lockTimeout)) {
                $worker?->log("Payment {$payment->uid} already locked, skipping.");
                usleep(100000); // 100ms
                continue;
            }

            try {
                // Calculate days until due for this payment
                $dueDate = strtotime(date('Y-m-d', strtotime($payment->due_date)));
                $today = strtotime('today');
                $daysUntilDue = (int)(($dueDate - $today) / 86400);

                $worker?->log("Processing payment {$payment->uid} (due in {$daysUntilDue} days)");

                // Send notification using existing NotificationTriggers
                // - Finds all active flows for payment.due_reminder breakpoint
                // - Checks flow conditions (e.g., payment_plan != 'direct')
                // - Handles deduplication via NotificationLog (reference_id + flow)
                // - Resolves placeholders and sends email/SMS
                $sent = $this->sendPaymentDueNotification($payment, $daysUntilDue, $worker);

                if ($sent) {
                    $totalSent++;
                    $worker?->log("Notification triggered for {$payment->uid}");
                } else {
                    $totalSkipped++;
                    $worker?->log("Notification skipped/failed for {$payment->uid}");
                }

                $totalProcessed++;

            } finally {
                // Always release lock
                $this->releaseNotificationLock($payment->uid);
            }

            usleep(200000); // 200ms between payments

            // Check worker timeout AFTER completing current payment
            if ($worker !== null && !$worker->canRun()) {
                $worker?->log("Worker timeout reached, stopping.");
                break;
            }
        }

        $worker?->log("paymentNotifications completed. Processed: $totalProcessed, Sent: $totalSent, Skipped: $totalSkipped");
    }

    /**
     * Get next payment needing notification
     * Returns payment with due_date matching any target date, not locked
     */
    private function getNextPaymentForNotification(array $targetDates, string $lockTimeout): ?object {
        if (empty($targetDates)) return null;

        // Build query for payments with due_date matching any target
        $query = Methods::payments()->excludeForeignKeys()->queryBuilder()
            ->where('status', 'SCHEDULED');

        // due_date IN (target_dates) - need to match just the date part
        // Each date becomes an AND group within an OR group
        $query->startGroup('OR');
        foreach ($targetDates as $date) {
            $query->startGroup('AND')
                ->where('due_date', '>=', $date . ' 00:00:00')
                ->where('due_date', '<=', $date . ' 23:59:59')
            ->endGroup();
        }
        $query->endGroup();

        // Not locked (or lock is stale)
        $query->startGroup('OR')
            ->whereNull('notification_lock_at')
            ->where('notification_lock_at', '<', $lockTimeout)
        ->endGroup();

        $query->order('due_date', 'ASC');

        return Methods::payments()->queryGetFirst($query);
    }

    /**
     * Acquire notification lock on a payment
     */
    private function acquireNotificationLock(string $paymentUid, string $lockTimeout): bool {
        $now = date('Y-m-d H:i:s');

        // Update only if not locked or lock is stale
        $query = Methods::payments()->queryBuilder()
            ->where('uid', $paymentUid)
            ->startGroup('OR')
                ->whereNull('notification_lock_at')
                ->where('notification_lock_at', '<', $lockTimeout)
            ->endGroup();

        return Methods::payments()->queryUpdate($query, ['notification_lock_at' => $now]);
    }

    /**
     * Release notification lock on a payment
     */
    private function releaseNotificationLock(string $paymentUid): void {
        Methods::payments()->update(['notification_lock_at' => null], ['uid' => $paymentUid]);
    }

    /**
     * Send payment due notification using existing NotificationTriggers
     *
     * Uses the existing paymentDueReminder() method which:
     * 1. Builds context via buildPaymentReminderContext() (includes all placeholders)
     * 2. Calls NotificationService::trigger('payment.due_reminder', $context)
     * 3. Trigger handles conditions, deduplication, placeholder resolution, and sending
     */
    private function sendPaymentDueNotification(object $payment, int $daysUntilDue, ?CronWorker $worker): bool {
        // Get payment with resolved FKs for context building
        $paymentFull = Methods::payments()->get($payment->uid);
        if (isEmpty($paymentFull)) return false;

        // Get user from payment (FK resolved as object)
        $user = is_object($paymentFull->uuid) ? $paymentFull->uuid : Methods::users()->get($paymentFull->uuid);
        if (isEmpty($user)) {
            $worker?->log("User not found for payment {$payment->uid}");
            return false;
        }

        // Use existing NotificationTriggers method - handles everything:
        // - Builds context with user, payment, order, organisation, location data
        // - Resolves all placeholders ({{user.full_name}}, {{payment.formatted_amount}}, etc.)
        // - Checks flow conditions
        // - Handles deduplication via NotificationLog
        // - Sends email/SMS directly
        return NotificationTriggers::paymentDueReminder($paymentFull, $user, $daysUntilDue);
    }


    /**
     * Process notification queue
     * Sends scheduled/delayed notifications from the queue
     */
    public function processNotificationQueue(?CronWorker $worker = null): void {
        $worker?->log("Running processNotificationQueue...");

        $batchSize = 100;
        $totalProcessed = 0;
        $totalSent = 0;
        $totalFailed = 0;

        // Process in batches while worker can run
        while ($worker === null || $worker->canRun()) {
            $results = \classes\notifications\NotificationService::processQueue($batchSize);

            if ($results['processed'] === 0) {
                $worker?->log("No more notifications to process.");
                break;
            }

            $totalProcessed += $results['processed'];
            $totalSent += $results['sent'];
            $totalFailed += $results['failed'];

            $worker?->log("Batch processed: {$results['processed']} (sent: {$results['sent']}, failed: {$results['failed']})");
            $worker?->memoryLog("after_batch");

            // Small delay between batches
            usleep(100000); // 100ms
        }

        // Cleanup old queue items (sent/cancelled older than 30 days)
        $cleaned = Methods::notificationQueue()->cleanupOld(30);
        if ($cleaned > 0) {
            $worker?->log("Cleaned up $cleaned old queue items.");
        }

        // Cleanup old notification logs (older than 90 days, only read ones)
        $cleanedLogs = Methods::userNotifications()->deleteOld(90, true);
        if ($cleanedLogs > 0) {
            $worker?->log("Cleaned up $cleanedLogs old user notifications.");
        }

        $worker?->log("processNotificationQueue completed. Total: $totalProcessed (sent: $totalSent, failed: $totalFailed)");
    }


    /**
     * Check overdue payments and trigger rykker (collection reminder) notifications
     * Uses scheduled_at to control timing - payments are only processed when scheduled_at <= now
     * Escalates through rykker levels: 1, 2, 3 (final/collection)
     */
    public function rykkerChecks(?CronWorker $worker = null): void {
        $worker?->log("Running rykkerChecks...");

        // Get rykker settings from AppMeta
        $rykker1Days = (int)(Settings::$app->rykker_1_days ?? 7);
        $rykker2Days = (int)(Settings::$app->rykker_2_days ?? 14);
        $rykker3Days = (int)(Settings::$app->rykker_3_days ?? 21);
        $rykker1Fee = (float)(Settings::$app->rykker_1_fee ?? 0);
        $rykker2Fee = (float)(Settings::$app->rykker_2_fee ?? 100);
        $rykker3Fee = (float)(Settings::$app->rykker_3_fee ?? 100);

        $worker?->log("Rykker settings loaded:");
        $worker?->log("  - Days: R1={$rykker1Days}d, R2={$rykker2Days}d, R3={$rykker3Days}d");
        $worker?->log("  - Fees: R1={$rykker1Fee}kr, R2={$rykker2Fee}kr, R3={$rykker3Fee}kr");

        // Debug log settings
        debugLog([
            'rykker_1_days' => $rykker1Days,
            'rykker_2_days' => $rykker2Days,
            'rykker_3_days' => $rykker3Days,
            'rykker_1_fee' => $rykker1Fee,
            'rykker_2_fee' => $rykker2Fee,
            'rykker_3_fee' => $rykker3Fee,
            'current_time' => date('Y-m-d H:i:s'),
        ], 'RYKKER_CRON_SETTINGS');

        $paymentHandler = Methods::payments();

        // getPastDueForRykker only returns payments where scheduled_at <= now
        $scheduledPayments = $paymentHandler->getPastDueForRykker();

        $worker?->log("Found {$scheduledPayments->count()} payments scheduled for rykker (scheduled_at <= now)");

        // Debug log the query results
        debugLog([
            'payments_found' => $scheduledPayments->count(),
            'current_time' => date('Y-m-d H:i:s'),
        ], 'RYKKER_CRON_QUERY');

        $stats = [
            'checked' => 0,
            'rykker_1_sent' => 0,
            'rykker_2_sent' => 0,
            'rykker_3_sent' => 0,
            'sent_to_collection' => 0,
            'notifications_triggered' => 0,
            'errors' => 0,
        ];

        foreach ($scheduledPayments->list() as $payment) {
            $stats['checked']++;

            // Get user info for logging
            $userUid = is_object($payment->uuid) ? $payment->uuid->uid : $payment->uuid;
            $userName = is_object($payment->uuid) ? ($payment->uuid->full_name ?? $payment->uuid->email ?? 'Unknown') : 'Unknown';

            try {
                $currentLevel = (int)($payment->rykker_level ?? 0);
                $newLevel = $currentLevel + 1;
                $currentFee = (float)($payment->rykker_fee ?? 0);

                $worker?->log("Processing payment {$payment->uid}:");
                $worker?->log("  - User: {$userName} ({$userUid})");
                $worker?->log("  - Amount: {$payment->amount} {$payment->currency}");
                $worker?->log("  - Due date: {$payment->due_date}");
                $worker?->log("  - Scheduled at: {$payment->scheduled_at}");
                $worker?->log("  - Current rykker level: {$currentLevel}");
                $worker?->log("  - Current rykker fee: {$currentFee}kr");

                // Debug log payment details
                debugLog([
                    'payment_uid' => $payment->uid,
                    'user_uid' => $userUid,
                    'user_name' => $userName,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'due_date' => $payment->due_date,
                    'scheduled_at' => $payment->scheduled_at,
                    'current_rykker_level' => $currentLevel,
                    'current_rykker_fee' => $currentFee,
                    'status' => $payment->status,
                ], 'RYKKER_CRON_PAYMENT_PROCESSING');

                // Max level is 3
                if ($newLevel > 3) {
                    $worker?->log("  - SKIP: Already at max rykker level (3)");
                    debugLog([
                        'payment_uid' => $payment->uid,
                        'reason' => 'max_level_reached',
                        'current_level' => $currentLevel,
                    ], 'RYKKER_CRON_PAYMENT_SKIPPED');
                    continue;
                }

                // Check minimum days since due_date for this rykker level
                $dueTimestamp = strtotime($payment->due_date);
                $todayTimestamp = strtotime('today');
                $daysSinceDue = (int)(($todayTimestamp - $dueTimestamp) / 86400);

                $requiredDays = match($newLevel) {
                    1 => $rykker1Days,
                    2 => $rykker2Days,
                    3 => $rykker3Days,
                    default => 0,
                };

                if ($daysSinceDue < $requiredDays) {
                    $worker?->log("  - SKIP: Only {$daysSinceDue} days since due_date, need {$requiredDays} days for rykker {$newLevel}");
                    debugLog([
                        'payment_uid' => $payment->uid,
                        'reason' => 'not_enough_days_since_due',
                        'days_since_due' => $daysSinceDue,
                        'required_days' => $requiredDays,
                        'new_level' => $newLevel,
                    ], 'RYKKER_CRON_PAYMENT_SKIPPED');
                    continue;
                }

                $worker?->log("  - Days since due: {$daysSinceDue} (required: {$requiredDays})");

                // Get fee for this rykker level
                $fee = match($newLevel) {
                    1 => $rykker1Fee,
                    2 => $rykker2Fee,
                    3 => $rykker3Fee,
                    default => 0,
                };

                $worker?->log("  - New rykker level: {$newLevel}");
                $worker?->log("  - Fee to add: {$fee}kr");
                $worker?->log("  - Total fee after: " . ($currentFee + $fee) . "kr");

                // Update payment with new rykker level, fee, and next scheduled_at
                $worker?->log("  - Calling sendRykker()...");
                $updateSuccess = $paymentHandler->sendRykker($payment->uid, $newLevel, $fee);

                debugLog([
                    'payment_uid' => $payment->uid,
                    'new_level' => $newLevel,
                    'fee_added' => $fee,
                    'update_success' => $updateSuccess,
                ], 'RYKKER_CRON_SEND_RYKKER');

                if ($updateSuccess) {
                    $worker?->log("  - sendRykker() SUCCESS");

                    // Trigger notification
                    $worker?->log("  - Triggering notification (payment.rykker_{$newLevel})...");

                    try {
                        $notificationResult = NotificationTriggers::paymentRykker($payment, $newLevel, $fee);
                        $stats['notifications_triggered']++;

                        debugLog([
                            'payment_uid' => $payment->uid,
                            'rykker_level' => $newLevel,
                            'notification_result' => $notificationResult,
                        ], 'RYKKER_CRON_NOTIFICATION_TRIGGERED');

                        $worker?->log("  - Notification triggered successfully");
                    } catch (\Throwable $e) {
                        $worker?->log("  - WARNING: Notification trigger failed: " . $e->getMessage());
                        debugLog([
                            'payment_uid' => $payment->uid,
                            'rykker_level' => $newLevel,
                            'error' => $e->getMessage(),
                        ], 'RYKKER_CRON_NOTIFICATION_ERROR');
                    }

                    $stats["rykker_{$newLevel}_sent"]++;
                    $worker?->log("  - Rykker {$newLevel} completed for {$payment->uid}");
                } else {
                    $stats['errors']++;
                    $worker?->log("  - ERROR: sendRykker() failed for {$payment->uid}");
                    debugLog([
                        'payment_uid' => $payment->uid,
                        'new_level' => $newLevel,
                        'error' => 'sendRykker returned false',
                    ], 'RYKKER_CRON_SEND_RYKKER_FAILED');
                }

            } catch (\Exception $e) {
                $stats['errors']++;
                $worker?->log("  - EXCEPTION: " . $e->getMessage());
                debugLog([
                    'payment_uid' => $payment->uid,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ], 'RYKKER_CRON_EXCEPTION');
            }

            $worker?->log(""); // Empty line between payments
        }

        // =====================================================
        // PHASE 2: Mark rykker level 3 payments as sent_to_collection
        // after 7-day grace period (scheduled_at has passed)
        // =====================================================
        $worker?->log("------------------------------------------");
        $worker?->log("Checking for rykker 3 payments ready for collection...");

        $collectionPayments = $paymentHandler->queryGetAll(
            $paymentHandler->queryBuilder()
                ->where('status', 'PAST_DUE')
                ->where('rykker_level', 3)
                ->where('sent_to_collection', 0)
                ->where('scheduled_at', '<=', date('Y-m-d H:i:s'))
        );

        $collectionCount = 0;
        foreach ($collectionPayments->list() as $payment) {
            $worker?->log("Marking payment {$payment->uid} as sent_to_collection");

            $paymentHandler->update([
                'sent_to_collection' => 1,
                'scheduled_at' => null,
            ], ['uid' => $payment->uid]);

            $collectionCount++;

            debugLog([
                'payment_uid' => $payment->uid,
                'action' => 'marked_sent_to_collection',
            ], 'RYKKER_CRON_SENT_TO_COLLECTION');
        }

        $worker?->log("Marked {$collectionCount} payments as sent_to_collection");
        $stats['sent_to_collection'] = $collectionCount;

        $worker?->log("==========================================");
        $worker?->log("rykkerChecks COMPLETED");
        $worker?->log("  - Payments checked: {$stats['checked']}");
        $worker?->log("  - Rykker 1 sent: {$stats['rykker_1_sent']}");
        $worker?->log("  - Rykker 2 sent: {$stats['rykker_2_sent']}");
        $worker?->log("  - Rykker 3 sent: {$stats['rykker_3_sent']}");
        $worker?->log("  - Sent to collection: {$stats['sent_to_collection']}");
        $worker?->log("  - Notifications triggered: {$stats['notifications_triggered']}");
        $worker?->log("  - Errors: {$stats['errors']}");
        $worker?->log("==========================================");

        debugLog($stats, 'RYKKER_CRON_COMPLETED');
    }


    /**
     * Generate and send weekly reports to organisations
     * Calculates stats and sends report notifications
     */
    public function weeklyReports(?CronWorker $worker = null): void {
        $worker?->log("Running weeklyReports...");

        // TODO: Implement weekly reports logic
        // 1. For each active organisation:
        //    a. Calculate weekly stats (orders, revenue, payments)
        //    b. Generate context data for template
        //    c. Trigger report.weekly_organisation breakpoint
        // 2. For each active location:
        //    a. Calculate location-specific stats
        //    b. Trigger report.weekly_location breakpoint
        // 3. Optionally generate PDF reports as attachments

        $worker?->log("weeklyReports completed.");
    }


    /**
     * Publish scheduled policies
     * Checks PolicyTypes where scheduled_at <= NOW and swaps pointers
     */
    public function policyPublish(?CronWorker $worker = null): void {
        $worker?->log("Running policyPublish...");

        $stats = [
            'checked' => 0,
            'published' => 0,
            'archived' => 0,
            'errors' => 0,
        ];

        // Find policy types with scheduled versions ready to publish
        $readyToPublish = Methods::policyTypes()->getReadyToPublish();

        if ($readyToPublish && $readyToPublish->count() > 0) {
            $worker?->log("Found {$readyToPublish->count()} scheduled policy types ready to publish.");

            foreach ($readyToPublish->list() as $policyType) {
                $stats['checked']++;

                try {
                    $worker?->log("Processing policy type {$policyType->uid} (scheduled_at: {$policyType->scheduled_at})");

                    // Execute the atomic pointer swap
                    $success = Methods::policyTypes()->executeScheduledPublish($policyType);

                    if ($success) {
                        $stats['published']++;
                        if (!isEmpty($policyType->current_version)) {
                            $stats['archived']++;
                            $worker?->log("  - Archived previous version, swapped pointer");
                        }
                        $worker?->log("  - Published scheduled version for {$policyType->uid}");
                    } else {
                        $stats['errors']++;
                        $worker?->log("  - ERROR: Failed to execute pointer swap");
                    }

                } catch (\Exception $e) {
                    $stats['errors']++;
                    $worker?->log("  - ERROR: " . $e->getMessage());
                    debugLog([
                        'policy_type_uid' => $policyType->uid,
                        'error' => $e->getMessage(),
                    ], 'POLICY_PUBLISH_CRON_ERROR');
                }
            }
        } else {
            $worker?->log("No scheduled policies ready to publish.");
        }

        $worker?->log("policyPublish completed. Checked: {$stats['checked']}, Published: {$stats['published']}, Archived: {$stats['archived']}, Errors: {$stats['errors']}");
    }


    /**
     * Retry failed PAST_DUE payments
     * Attempts to charge payments that have failed previously, including rykker fees
     * Similar to consumer payNow but automated via cronjob
     *
     * Uses configurable settings:
     * - payment_max_attempts: Maximum charge attempts before stopping retries
     * - payment_retry_day_interval: Days between retry attempts
     */
    public function retryPayments(?CronWorker $worker = null): void {
        $worker?->log("Running retryPayments...");

        // Get configurable settings
        $maxAttempts = (int)(Settings::$app->payment_max_attempts ?? 5);
        $retryIntervalDays = (int)(Settings::$app->payment_retry_day_interval ?? 3);

        $paymentsHandler = Methods::payments();
        $ordersHandler = Methods::orders();
        $now = date('Y-m-d H:i:s');
        $lockTimeout = date('Y-m-d H:i:s', time() - self::PAYMENT_LOCK_TIMEOUT);

        $stats = [
            'checked' => 0,
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        while (true) {
            // Find PAST_DUE payments eligible for retry:
            // - attempts < maxAttempts (under the limit)
            // - sent_to_collection = 0 (not yet in collection)
            // - has initial_transaction_id (can charge stored card)
            // - scheduled_at <= now (time to retry)
            // - not locked or lock is stale
            $payment = $paymentsHandler->excludeForeignKeys()->queryBuilder()
                ->where('status', 'PAST_DUE')
                ->where('attempts', '<', $maxAttempts)
                ->where('sent_to_collection', 0)
                ->whereNotNull('initial_transaction_id')
                ->startGroup('OR')
                    ->whereNull('scheduled_at')
                    ->where('scheduled_at', '<=', $now)
                ->endGroup()
                ->startGroup('OR')
                    ->whereNull('processing_at')
                    ->where('processing_at', '<', $lockTimeout)
                ->endGroup()
                ->order('scheduled_at', 'ASC')
                ->first();

            if (isEmpty($payment)) {
                $worker?->log("No more payments to retry.");
                break;
            }

            // Acquire lock
            $lockAcquired = $this->acquirePaymentLock($payment->uid, $lockTimeout);
            if (!$lockAcquired) {
                $worker?->log("Payment {$payment->uid} already locked, skipping.");
                $stats['skipped']++;
                usleep(100000);
                continue;
            }

            $stats['checked']++;

            // Process retry
            $result = $this->processPaymentRetry($payment->uid, $retryIntervalDays, $worker);

            if ($result['success']) {
                $stats['success']++;
            } else {
                $stats['failed']++;
            }

            // Release lock
            $this->releasePaymentLock($payment->uid);

            usleep(200000); // 200ms delay

            // Check worker timeout
            if ($worker !== null && !$worker->canRun()) {
                $worker?->log("Worker timeout reached, stopping.");
                break;
            }
        }

        $worker?->log("retryPayments completed. Checked: {$stats['checked']}, Success: {$stats['success']}, Failed: {$stats['failed']}, Skipped: {$stats['skipped']}");
    }

    /**
     * Process a single payment retry - includes rykker fees like payNow
     *
     * @param string $paymentUid
     * @param int $retryIntervalDays Days until next retry on failure
     * @param CronWorker|null $worker
     * @return array{success: bool, error?: string}
     */
    private function processPaymentRetry(string $paymentUid, int $retryIntervalDays, ?CronWorker $worker = null): array {
        $paymentsHandler = Methods::payments();
        $ordersHandler = Methods::orders();

        // Get payment with resolved FKs
        $payment = $paymentsHandler->includeForeignKeys()->get($paymentUid);
        if (isEmpty($payment)) {
            $worker?->log("Payment $paymentUid not found.");
            return ['success' => false, 'error' => 'Payment not found'];
        }

        // Double-check status
        if ($payment->status !== 'PAST_DUE') {
            $worker?->log("Payment $paymentUid status is {$payment->status}, skipping.");
            return ['success' => false, 'error' => 'Payment status changed'];
        }

        // Get organisation for merchant_prid
        $organisationUid = is_object($payment->organisation) ? $payment->organisation->uid : $payment->organisation;
        $organisation = Methods::organisations()->get($organisationUid);

        if (isEmpty($organisation) || isEmpty($organisation->merchant_prid)) {
            $worker?->log("Payment $paymentUid: Missing merchant_prid");
            return $this->handleRetryFailure($payment, 'Missing merchant_prid', $retryIntervalDays, $worker);
        }

        if (isEmpty($payment->initial_transaction_id)) {
            $worker?->log("Payment $paymentUid: Missing initial_transaction_id");
            return $this->handleRetryFailure($payment, 'Missing initial_transaction_id', $retryIntervalDays, $worker);
        }

        // Get order for fee percentage
        $order = is_object($payment->order) ? $payment->order : $ordersHandler->get($payment->order);
        $orderUid = is_object($payment->order) ? $payment->order->uid : $payment->order;

        // Calculate total charge including rykker fees (same as payNow)
        $originalAmount = (float)$payment->amount;
        $rykkerFee = (float)($payment->rykker_fee ?? 0);
        $totalChargeAmount = $originalAmount + $rykkerFee;

        // Recalculate ISV amount based on total charge
        $feePercent = (float)($order->fee ?? 0);
        $originalIsvAmount = (float)($payment->isv_amount ?? 0);
        $newIsvAmount = round($totalChargeAmount * $feePercent / 100, 2);

        $currentAttempts = ($payment->attempts ?? 0) + 1;

        debugLog([
            'payment_uid' => $paymentUid,
            'original_amount' => $originalAmount,
            'rykker_fee' => $rykkerFee,
            'total_charge' => $totalChargeAmount,
            'attempt' => $currentAttempts,
        ], 'CRON_RETRY_PAYMENT_START');

        $worker?->log("Retrying payment $paymentUid: {$totalChargeAmount} {$payment->currency} (attempt $currentAttempts)");

        // Attempt charge
        $isTestPayment = (bool)($payment->test ?? false);
        $chargeResult = CardValidationService::chargeWithStoredCard(
            $organisation->merchant_prid,
            $payment->initial_transaction_id,
            $totalChargeAmount,
            $payment->currency,
            "Betaling af forsinket rate" . ($rykkerFee > 0 ? " inkl. rykkergebyr" : ""),
            $isTestPayment,
            $newIsvAmount > 0 ? $newIsvAmount : null
        );

        debugLog([
            'payment_uid' => $paymentUid,
            'result' => $chargeResult,
        ], 'CRON_RETRY_PAYMENT_RESULT');

        if ($chargeResult['success']) {
            // SUCCESS - Update payment amounts BEFORE notification (so notification has correct values)
            $paymentsHandler->update([
                'amount' => $totalChargeAmount,
                'isv_amount' => $newIsvAmount,
                'sent_to_collection' => 0,
                'scheduled_at' => null,
            ], ['uid' => $paymentUid]);

            // Mark as completed
            $paymentsHandler->markAsCompleted($paymentUid, $chargeResult['transaction_id'] ?? null);

            // Update order totals if there was a rykker fee
            if ($rykkerFee > 0) {
                $newOrderAmount = (float)$order->amount + $rykkerFee;
                $isvDifference = $newIsvAmount - $originalIsvAmount;
                $newOrderFeeAmount = (float)($order->fee_amount ?? 0) + $isvDifference;

                $ordersHandler->update([
                    'amount' => $newOrderAmount,
                    'fee_amount' => $newOrderFeeAmount,
                ], ['uid' => $orderUid]);
            }

            $worker?->log("Payment $paymentUid charged successfully.");

            // Trigger notification with updated payment data
            try {
                $updatedPayment = $paymentsHandler->get($paymentUid);
                $user = is_object($payment->uuid) ? $payment->uuid : Methods::users()->get($payment->uuid);
                NotificationTriggers::paymentSuccessful($updatedPayment, $user, $order);
            } catch (\Throwable $e) {
                errorLog(['error' => $e->getMessage()], 'retry-payment-notification-error');
            }

            return ['success' => true];
        }

        // FAILURE
        $failureReason = $chargeResult['error'] ?? 'Charge failed';
        return $this->handleRetryFailure($payment, $failureReason, $retryIntervalDays, $worker);
    }

    /**
     * Handle retry failure - increment attempts, schedule next retry
     * Always sets scheduled_at (even after max attempts) for rykker system
     */
    private function handleRetryFailure(object $payment, string $failureReason, int $retryIntervalDays, ?CronWorker $worker = null): array {
        $paymentsHandler = Methods::payments();
        $currentAttempts = ($payment->attempts ?? 0) + 1;

        // Always set scheduled_at for next check (used by both retry and rykker systems)
        $nextScheduledAt = date('Y-m-d H:i:s', strtotime("+{$retryIntervalDays} days"));

        $paymentsHandler->update([
            'attempts' => $currentAttempts,
            'failure_reason' => $failureReason,
            'scheduled_at' => $nextScheduledAt,
        ], ['uid' => $payment->uid]);

        $worker?->log("Payment {$payment->uid} retry failed: $failureReason (attempt $currentAttempts, next scheduled: $nextScheduledAt)");

        debugLog([
            'payment_uid' => $payment->uid,
            'failure_reason' => $failureReason,
            'attempts' => $currentAttempts,
            'next_scheduled' => $nextScheduledAt,
        ], 'CRON_RETRY_PAYMENT_FAILURE');

        return ['success' => false, 'error' => $failureReason];
    }
}
