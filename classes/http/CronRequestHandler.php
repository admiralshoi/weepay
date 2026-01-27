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
            $vivaEventId = $chargeResult['event_id'] ?? null;
            return $this->handlePaymentAttemptFailure($payment, $failureReason, $worker, $vivaEventId, $chargeResult);
        }
    }

    /**
     * Handle a failed payment attempt - mark as PAST_DUE and schedule retry + rykker
     *
     * scheduled_at = next payment retry attempt (uses payment_retry_day_interval)
     * rykker_scheduled_at = next rykker escalation (uses rykker_1_days)
     *
     * If failure is merchant's fault, we DON'T schedule rykkers (not customer's fault)
     *
     * @param object $payment Payment object with resolved FKs
     * @param string $failureReason
     * @param CronWorker|null $worker
     * @param int|null $vivaEventId Viva EventId code for error categorization
     * @param array $chargeResult Full charge result from Viva (for error context)
     * @return array{success: bool, error: string}
     */
    private function handlePaymentAttemptFailure(object $payment, string $failureReason, ?CronWorker $worker = null, ?int $vivaEventId = null, array $chargeResult = []): array {
        $paymentsHandler = Methods::payments();
        $currentAttempts = ($payment->attempts ?? 0) + 1;

        // Get HTTP error code from charge result (e.g., 403 = API disabled)
        $httpErrorCode = $chargeResult['error_code'] ?? null;

        // Check if this is a merchant fault (e.g., recurring not enabled)
        $categorizer = new \classes\payments\PaymentErrorCategorizer();
        $isMerchantFault = $categorizer->requiresMerchantAttention($vivaEventId ?? 0, $httpErrorCode);

        debugLog([
            'payment_uid' => $payment->uid,
            'failure_reason' => $failureReason,
            'current_attempts' => $currentAttempts,
            'viva_event_id' => $vivaEventId,
            'http_error_code' => $httpErrorCode,
            'is_merchant_fault' => $isMerchantFault,
            'charge_result_keys' => !empty($chargeResult) ? array_keys($chargeResult) : 'EMPTY',
        ], 'CRON_PAYMENT_ATTEMPT_FAILURE');

        // Get settings for scheduling
        $retryIntervalDays = (int)(Settings::$app->payment_retry_day_interval ?? 3);
        $rykker1Days = (int)(Settings::$app->rykker_1_days ?? 7);

        // Build update data
        $updateData = [
            'status' => 'PAST_DUE',
            'failure_reason' => $failureReason,
            'attempts' => $currentAttempts,
            // scheduled_at = next payment retry attempt
            'scheduled_at' => date('Y-m-d H:i:s', strtotime("+{$retryIntervalDays} days")),
        ];

        // Only schedule rykker if it's NOT the merchant's fault
        // If merchant config issue (e.g., recurring not enabled), don't punish customer with rykker fees
        if (!$isMerchantFault) {
            $updateData['rykker_scheduled_at'] = date('Y-m-d H:i:s', strtotime("+{$rykker1Days} days"));
            $worker?->log("Payment {$payment->uid} marked as PAST_DUE, retry in {$retryIntervalDays} days, rykker in {$rykker1Days} days");
        } else {
            // Clear any existing rykker schedule - merchant needs to fix config
            $updateData['rykker_scheduled_at'] = null;
            $worker?->log("Payment {$payment->uid} marked as PAST_DUE (MERCHANT FAULT - no rykker), retry in {$retryIntervalDays} days");
        }

        // Mark as PAST_DUE and schedule
        $paymentsHandler->update($updateData, ['uid' => $payment->uid]);

        // Trigger payment past due notification (customer) - but only if not merchant fault
        if (!$isMerchantFault) {
            $this->triggerPaymentNotification($payment, 'past_due', $failureReason);
        } else {
            $worker?->log("Skipping customer notification - merchant fault");
        }

        // Create merchant attention notification if error requires merchant action
        // Note: EventId can be 0 which is a valid error code, so we check for null specifically
        $hasVivaEventId = $vivaEventId !== null;
        debugLog([
            'payment_uid' => $payment->uid,
            'viva_event_id' => $vivaEventId,
            'viva_event_id_is_null' => $vivaEventId === null,
            'has_viva_event_id' => $hasVivaEventId,
            'is_merchant_fault' => $isMerchantFault,
        ], 'CRON_BEFORE_ATTENTION_NOTIFICATION');

        if ($hasVivaEventId) {
            try {
                $notificationHandler = Methods::requiresAttentionNotifications();

                debugLog([
                    'payment_uid' => $payment->uid,
                    'viva_event_id' => $vivaEventId,
                    'calling' => 'createFromPaymentFailure',
                ], 'CRON_CALLING_CREATE_NOTIFICATION');

                $notificationUid = $notificationHandler->createFromPaymentFailure($payment, $vivaEventId, $chargeResult);

                debugLog([
                    'payment_uid' => $payment->uid,
                    'notification_uid' => $notificationUid,
                    'notification_created' => $notificationUid ? 'YES' : 'NO',
                ], 'CRON_NOTIFICATION_RESULT');

                if ($notificationUid) {
                    $worker?->log("Created requires-attention notification {$notificationUid} for payment {$payment->uid}");
                }
            } catch (\Exception $e) {
                debugLog([
                    'payment_uid' => $payment->uid,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ], 'CRON_ATTENTION_NOTIFICATION_ERROR');
            }
        } else {
            debugLog([
                'payment_uid' => $payment->uid,
                'reason' => 'vivaEventId is empty, skipping notification creation',
            ], 'CRON_SKIP_ATTENTION_NOTIFICATION');
        }

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
        $iterations = 0;
        $maxIterations = 300; // Safety limit
        $processedUids = []; // Track already processed to avoid infinite loop

        while (true) {
            $iterations++;

            // Safety checks at START of each iteration
            if ($iterations > $maxIterations) {
                $worker?->log("Safety limit reached ($maxIterations iterations), stopping.");
                break;
            }

            if ($worker !== null && !$worker->canRun()) {
                $worker?->log("Worker timeout reached, stopping.");
                break;
            }

            // Find next payment needing notification (exclude already processed)
            $payment = $this->getNextPaymentForNotification($targetDates, $lockTimeout, $processedUids);

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
                // Mark as processed immediately to avoid re-querying
                $processedUids[] = $payment->uid;

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
        }

        $worker?->log("paymentNotifications completed. Iterations: $iterations, Processed: $totalProcessed, Sent: $totalSent, Skipped: $totalSkipped");
    }

    /**
     * Get next payment needing notification
     * Returns payment with due_date matching any target date, not locked, not already processed
     */
    private function getNextPaymentForNotification(array $targetDates, string $lockTimeout, array $processedUids = []): ?object {
        if (empty($targetDates)) return null;

        // Build query for payments with due_date matching any target
        $query = Methods::payments()->excludeForeignKeys()->queryBuilder()
            ->where('status', 'SCHEDULED');

        // Exclude already processed UIDs to prevent infinite loop
        if (!empty($processedUids)) {
            $query->where('uid', 'NOT IN', $processedUids);
        }

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
     * Uses rykker_scheduled_at to control timing - payments are only processed when rykker_scheduled_at <= now
     * Escalates through rykker levels: 1, 2, 3 (final/collection)
     *
     * Note: rykker_scheduled_at is separate from scheduled_at (payment retry).
     * If payment failed due to merchant fault, rykker_scheduled_at will be null (no rykker).
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

        // getPastDueForRykker only returns payments where rykker_scheduled_at <= now
        $scheduledPayments = $paymentHandler->getPastDueForRykker();

        $worker?->log("Found {$scheduledPayments->count()} payments scheduled for rykker (rykker_scheduled_at <= now)");

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
        // after 7-day grace period (rykker_scheduled_at has passed)
        // =====================================================
        $worker?->log("------------------------------------------");
        $worker?->log("Checking for rykker 3 payments ready for collection...");

        $collectionPayments = $paymentHandler->queryGetAll(
            $paymentHandler->queryBuilder()
                ->where('status', 'PAST_DUE')
                ->where('rykker_level', 3)
                ->where('sent_to_collection', 0)
                ->whereNotNull('rykker_scheduled_at')
                ->where('rykker_scheduled_at', '<=', date('Y-m-d H:i:s'))
        );

        $collectionCount = 0;
        foreach ($collectionPayments->list() as $payment) {
            $worker?->log("Marking payment {$payment->uid} as sent_to_collection");

            $paymentHandler->update([
                'sent_to_collection' => 1,
                'rykker_scheduled_at' => null,
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
        $vivaEventId = $chargeResult['event_id'] ?? null;
        return $this->handleRetryFailure($payment, $failureReason, $retryIntervalDays, $worker, $vivaEventId, $chargeResult);
    }

    /**
     * Handle retry failure - increment attempts, schedule next retry
     * scheduled_at = next payment retry
     * If merchant fault, clear rykker_scheduled_at (don't punish customer)
     *
     * @param object $payment Payment object
     * @param string $failureReason Error message
     * @param int $retryIntervalDays Days until next retry
     * @param CronWorker|null $worker
     * @param int|null $vivaEventId Viva EventId code for error categorization
     * @param array $chargeResult Full charge result from Viva (for error context)
     * @return array{success: bool, error: string}
     */
    private function handleRetryFailure(object $payment, string $failureReason, int $retryIntervalDays, ?CronWorker $worker = null, ?int $vivaEventId = null, array $chargeResult = []): array {
        $paymentsHandler = Methods::payments();
        $currentAttempts = ($payment->attempts ?? 0) + 1;

        // Get HTTP error code from charge result (e.g., 403 = API disabled)
        $httpErrorCode = $chargeResult['error_code'] ?? null;

        // Check if this is a merchant fault
        $categorizer = new \classes\payments\PaymentErrorCategorizer();
        $isMerchantFault = $categorizer->requiresMerchantAttention($vivaEventId ?? 0, $httpErrorCode);

        // Schedule next retry attempt
        $nextScheduledAt = date('Y-m-d H:i:s', strtotime("+{$retryIntervalDays} days"));

        // Build update data
        $updateData = [
            'attempts' => $currentAttempts,
            'failure_reason' => $failureReason,
            'scheduled_at' => $nextScheduledAt,
        ];

        // If merchant fault, clear rykker schedule (not customer's fault)
        if ($isMerchantFault) {
            $updateData['rykker_scheduled_at'] = null;
            $worker?->log("Payment {$payment->uid} retry failed (MERCHANT FAULT - clearing rykker): $failureReason (attempt $currentAttempts, next retry: $nextScheduledAt)");
        } else {
            $worker?->log("Payment {$payment->uid} retry failed: $failureReason (attempt $currentAttempts, next retry: $nextScheduledAt)");
        }

        $paymentsHandler->update($updateData, ['uid' => $payment->uid]);

        debugLog([
            'payment_uid' => $payment->uid,
            'failure_reason' => $failureReason,
            'attempts' => $currentAttempts,
            'next_scheduled' => $nextScheduledAt,
            'viva_event_id' => $vivaEventId,
            'http_error_code' => $httpErrorCode,
            'is_merchant_fault' => $isMerchantFault,
        ], 'CRON_RETRY_PAYMENT_FAILURE');

        // Create merchant attention notification if error requires merchant action
        if ($vivaEventId !== null) {
            try {
                $notificationHandler = Methods::requiresAttentionNotifications();
                $notificationUid = $notificationHandler->createFromPaymentFailure($payment, $vivaEventId, $chargeResult);
                if ($notificationUid) {
                    $worker?->log("Created requires-attention notification {$notificationUid} for payment {$payment->uid}");
                }
            } catch (\Exception $e) {
                debugLog([
                    'payment_uid' => $payment->uid,
                    'error' => $e->getMessage(),
                ], 'CRON_ATTENTION_NOTIFICATION_ERROR');
            }
        }

        return ['success' => false, 'error' => $failureReason];
    }


    /**
     * System cleanup cronjob
     * Removes stale/expired data from database and cleans up old log files
     *
     * Runs hourly (time_gab: 3600 seconds)
     */
    public function systemCleanup(?CronWorker $worker = null): void {
        $worker?->log("Starting system cleanup...", true);
        $worker?->memoryLog("start", true);

        $stats = [
            'oidc_sessions' => 0,
            'draft_orders' => 0,
            'draft_payments' => 0,
            'draft_baskets' => 0,
            '2fa_codes' => 0,
            'terminal_sessions' => 0,
            'log_files' => 0,
            'empty_dirs' => 0,
            'cron_logs' => 0,
        ];

        // 1. Cleanup OIDC Sessions (expired > 5 days)
        $stats['oidc_sessions'] = $this->cleanupOidcSessions($worker);
        if ($worker !== null && !$worker->canRun()) { $this->logCleanupStats($worker, $stats); return; }

        // 2. Cleanup Draft Orders (> 5 days old)
        $stats['draft_orders'] = $this->cleanupDraftOrders($worker);
        if ($worker !== null && !$worker->canRun()) { $this->logCleanupStats($worker, $stats); return; }

        // 3. Cleanup Draft Payments (> 5 days old)
        $stats['draft_payments'] = $this->cleanupDraftPayments($worker);
        if ($worker !== null && !$worker->canRun()) { $this->logCleanupStats($worker, $stats); return; }

        // 4. Cleanup Draft Checkout Baskets (> 1 day old)
        $stats['draft_baskets'] = $this->cleanupDraftBaskets($worker);
        if ($worker !== null && !$worker->canRun()) { $this->logCleanupStats($worker, $stats); return; }

        // 5. Cleanup expired 2FA codes (expired > 5 days)
        $stats['2fa_codes'] = $this->cleanupExpired2FACodes($worker);
        if ($worker !== null && !$worker->canRun()) { $this->logCleanupStats($worker, $stats); return; }

        // 6. Cleanup Terminal Sessions (VOID/PENDING > 1 day, NOT COMPLETED)
        $stats['terminal_sessions'] = $this->cleanupTerminalSessions($worker);
        if ($worker !== null && !$worker->canRun()) { $this->logCleanupStats($worker, $stats); return; }

        // 7. Cleanup Log Files (2 months = 60 days)
        $stats['log_files'] = $this->cleanupSystemLogFiles($worker, 60);
        if ($worker !== null && !$worker->canRun()) { $this->logCleanupStats($worker, $stats); return; }

        // 8. Cleanup Empty Log Directories
        $stats['empty_dirs'] = $this->cleanupEmptyLogDirs($worker);
        if ($worker !== null && !$worker->canRun()) { $this->logCleanupStats($worker, $stats); return; }

        // 9. Cleanup Old Cron Logs (5 days)
        $cronWorker = Methods::cronWorker();
        $stats['cron_logs'] = $cronWorker->cleanupOldLogs(5);
        $worker?->log("Cleaned up {$stats['cron_logs']} old cron log files");

        $this->logCleanupStats($worker, $stats);
        $worker?->memoryLog("end");
    }

    /**
     * Log cleanup statistics
     */
    private function logCleanupStats(?CronWorker $worker, array $stats): void {
        $worker?->log("==========================================");
        $worker?->log("System Cleanup COMPLETED");
        $worker?->log("  - OIDC Sessions deleted: {$stats['oidc_sessions']}");
        $worker?->log("  - Draft Orders deleted: {$stats['draft_orders']}");
        $worker?->log("  - Draft Payments deleted: {$stats['draft_payments']}");
        $worker?->log("  - Draft Baskets deleted: {$stats['draft_baskets']}");
        $worker?->log("  - 2FA Codes deleted: {$stats['2fa_codes']}");
        $worker?->log("  - Terminal Sessions deleted: {$stats['terminal_sessions']}");
        $worker?->log("  - Log Files deleted: {$stats['log_files']}");
        $worker?->log("  - Empty Dirs removed: {$stats['empty_dirs']}");
        $worker?->log("  - Cron Log Files deleted: {$stats['cron_logs']}");
        $worker?->log("==========================================");

        debugLog($stats, 'SYSTEM_CLEANUP_COMPLETED');
    }

    /**
     * Cleanup expired OIDC sessions (expired > 5 days)
     * No FK references to this table - safe to delete directly
     */
    private function cleanupOidcSessions(?CronWorker $worker): int {
        $count = 0;
        $fiveDaysAgo = time() - (5 * 24 * 60 * 60);

        $worker?->log("--- OIDC Sessions (expired > 5 days) ---");

        while (true) {
            $session = Methods::oidcSession()->excludeForeignKeys()->queryGetFirst(
                Methods::oidcSession()->queryBuilder()
                    ->where('expires_at', '<', $fiveDaysAgo)
            );

            if (isEmpty($session)) break;

            $expiresAt = date('Y-m-d H:i:s', $session->expires_at);
            Methods::oidcSession()->queryBuilder()->where('uid', $session->uid)->delete();
            $worker?->log("[DELETED] OIDC Session: {$session->uid} (expires_at: {$expiresAt})");
            $count++;

            if ($worker !== null && !$worker->canRun()) break;
        }

        $worker?->log("Deleted $count OIDC sessions");
        return $count;
    }

    /**
     * Cleanup draft orders (> 5 days old)
     * Deletes order and its payments
     * Also cleans up orphaned terminal session + baskets if applicable
     * SKIP if: has COMPLETED payment OR has PendingValidationRefund
     */
    private function cleanupDraftOrders(?CronWorker $worker): int {
        $count = 0;
        $skipped = 0;
        $sessionCount = 0;
        $fiveDaysAgo = date('Y-m-d H:i:s', strtotime('-5 days'));

        $worker?->log("--- Draft Orders (> 5 days old) ---");

        $processed = [];
        while (true) {
            $order = Methods::orders()->excludeForeignKeys()->queryGetFirst(
                Methods::orders()->queryBuilder()
                    ->where('status', 'DRAFT')
                    ->where('created_at', '<', $fiveDaysAgo)
                    ->where('uid', 'NOT IN', $processed ?: [''])
            );

            if (isEmpty($order)) break;
            $processed[] = $order->uid;

            // Check if order has any COMPLETED payments - skip
            $hasCompletedPayment = Methods::payments()->exists(['order' => $order->uid, 'status' => 'COMPLETED']);
            if ($hasCompletedPayment) {
                $worker?->log("[SKIPPED] Order: {$order->uid} - has COMPLETED payment");
                $skipped++;
                if ($worker !== null && !$worker->canRun()) break;
                continue;
            }

            // Check if order has pending validation refunds - skip (user said leave these)
            $hasPendingRefund = Methods::pendingValidationRefunds()->exists(['order' => $order->uid]);
            if ($hasPendingRefund) {
                $worker?->log("[SKIPPED] Order: {$order->uid} - has pending refund");
                $skipped++;
                if ($worker !== null && !$worker->canRun()) break;
                continue;
            }

            // Store terminal_session before deleting order
            $terminalSessionUid = $order->terminal_session;

            // Delete all payments linked to this order first
            Methods::payments()->queryBuilder()->where('order', $order->uid)->delete();

            // Delete the order
            Methods::orders()->queryBuilder()->where('uid', $order->uid)->delete();
            $worker?->log("[DELETED] Order: {$order->uid} (created: {$order->created_at})");
            $count++;

            // Check if terminal session is now orphaned and can be cleaned up
            if (!isEmpty($terminalSessionUid)) {
                $canDeleteSession = $this->tryCleanupOrphanedTerminalSession($terminalSessionUid, $worker);
                if ($canDeleteSession) $sessionCount++;
            }

            if ($worker !== null && !$worker->canRun()) break;
        }

        $worker?->log("Deleted $count draft orders + $sessionCount terminal sessions, skipped $skipped");
        return $count;
    }

    /**
     * Try to cleanup a terminal session if it's now orphaned (no orders, VOID/PENDING state)
     */
    private function tryCleanupOrphanedTerminalSession(string $sessionUid, ?CronWorker $worker): bool {
        $session = Methods::terminalSessions()->excludeForeignKeys()->getFirst(['uid' => $sessionUid]);
        if (isEmpty($session)) return false;

        // Only cleanup VOID or PENDING sessions
        if (!in_array($session->state, ['VOID', 'PENDING'])) return false;

        // Check if session still has other orders
        $hasOtherOrders = Methods::orders()->exists(['terminal_session' => $sessionUid]);
        if ($hasOtherOrders) return false;

        // Check if session has FULFILLED baskets (DRAFT and VOID are safe to delete)
        $fulfilledBasket = Methods::checkoutBasket()->exists(['terminal_session' => $sessionUid, 'status' => 'FULFILLED']);
        if ($fulfilledBasket) return false;

        // Delete baskets first
        $basketCount = Methods::checkoutBasket()->count(['terminal_session' => $sessionUid]);
        if ($basketCount > 0) {
            Methods::checkoutBasket()->queryBuilder()->where('terminal_session', $sessionUid)->delete();
            $worker?->log("[DELETED] {$basketCount} basket(s) for orphaned session {$sessionUid}");
        }

        // Delete the session
        Methods::terminalSessions()->queryBuilder()->where('uid', $sessionUid)->delete();
        $worker?->log("[DELETED] Orphaned Terminal Session: {$sessionUid}");
        return true;
    }

    /**
     * Cleanup draft payments (> 5 days old, status PENDING, no linked order)
     * Only deletes orphan payments - those linked to orders are handled by orders cleanup
     */
    private function cleanupDraftPayments(?CronWorker $worker): int {
        $count = 0;
        $fiveDaysAgo = date('Y-m-d H:i:s', strtotime('-5 days'));

        $worker?->log("--- Draft Payments (PENDING > 5 days old, orphans) ---");

        while (true) {
            $payment = Methods::payments()->excludeForeignKeys()->queryGetFirst(
                Methods::payments()->queryBuilder()
                    ->where('status', 'PENDING')
                    ->where('created_at', '<', $fiveDaysAgo)
                    ->whereNull('order')
            );

            if (isEmpty($payment)) break;

            Methods::payments()->queryBuilder()->where('uid', $payment->uid)->delete();
            $worker?->log("[DELETED] Payment: {$payment->uid} (created: {$payment->created_at}, amount: {$payment->amount})");
            $count++;

            if ($worker !== null && !$worker->canRun()) break;
        }

        $worker?->log("Deleted $count orphan payments");
        return $count;
    }

    /**
     * Baskets are only deleted via terminal session cleanup
     * This ensures baskets are never deleted while still needed
     */
    private function cleanupDraftBaskets(?CronWorker $worker): int {
        $worker?->log("--- Draft Checkout Baskets ---");
        $worker?->log("Baskets are cleaned up via terminal session cleanup");
        return 0;
    }

    /**
     * Cleanup expired 2FA codes (expired > 5 days, NOT verified)
     * Verified codes are kept for verification checks
     */
    private function cleanupExpired2FACodes(?CronWorker $worker): int {
        $count = 0;
        $fiveDaysAgo = time() - (5 * 24 * 60 * 60);

        $worker?->log("--- Expired 2FA Codes (expired > 5 days, unverified) ---");

        while (true) {
            $code = Methods::twoFactorAuth()->excludeForeignKeys()->queryGetFirst(
                Methods::twoFactorAuth()->queryBuilder()
                    ->where('expires_at', '<', $fiveDaysAgo)
                    ->where('verified', 0)
            );

            if (isEmpty($code)) break;

            $expiresAt = date('Y-m-d H:i:s', $code->expires_at);
            Methods::twoFactorAuth()->queryBuilder()->where('uid', $code->uid)->delete();
            $worker?->log("[DELETED] 2FA Code: {$code->uid} (expires_at: {$expiresAt}, purpose: " . ($code->purpose ?? 'N/A') . ")");
            $count++;

            if ($worker !== null && !$worker->canRun()) break;
        }

        $worker?->log("Deleted $count expired unverified 2FA codes");
        return $count;
    }

    /**
     * Cleanup terminal sessions (VOID/PENDING > 1 day, NOT COMPLETED)
     * Also deletes linked baskets when session is deleted
     * SKIP if: has linked orders OR has non-DRAFT baskets
     */
    private function cleanupTerminalSessions(?CronWorker $worker): int {
        $count = 0;
        $basketCount = 0;
        $skipped = 0;
        $oneDayAgo = date('Y-m-d H:i:s', strtotime('-1 day'));

        $worker?->log("--- Terminal Sessions (VOID/PENDING > 1 day) ---");

        $processed = [];
        while (true) {
            $session = Methods::terminalSessions()->excludeForeignKeys()->queryGetFirst(
                Methods::terminalSessions()->queryBuilder()
                    ->where('state', ['VOID', 'PENDING'])
                    ->where('created_at', '<', $oneDayAgo)
                    ->where('uid', 'NOT IN', $processed ?: [''])
            );

            if (isEmpty($session)) break;
            $processed[] = $session->uid;

            // Check if session has linked orders - skip if any exist
            $hasOrder = Methods::orders()->exists(['terminal_session' => $session->uid]);
            if ($hasOrder) {
                $worker?->log("[SKIPPED] Terminal Session: {$session->uid} - has linked order");
                $skipped++;
                if ($worker !== null && !$worker->canRun()) break;
                continue;
            }

            // Check if session has FULFILLED baskets - skip if so (DRAFT and VOID are safe to delete)
            $fulfilledBasket = Methods::checkoutBasket()->exists(['terminal_session' => $session->uid, 'status' => 'FULFILLED']);
            if ($fulfilledBasket) {
                $worker?->log("[SKIPPED] Terminal Session: {$session->uid} - has FULFILLED basket");
                $skipped++;
                if ($worker !== null && !$worker->canRun()) break;
                continue;
            }

            // Count and delete any DRAFT/VOID baskets linked to this session
            $basketsDeleted = Methods::checkoutBasket()->count(['terminal_session' => $session->uid]);
            if ($basketsDeleted > 0) {
                Methods::checkoutBasket()->queryBuilder()->where('terminal_session', $session->uid)->delete();
                $basketCount += $basketsDeleted;
                $worker?->log("[DELETED] {$basketsDeleted} basket(s) for session {$session->uid}");
            }

            // Delete the session
            Methods::terminalSessions()->queryBuilder()->where('uid', $session->uid)->delete();
            $worker?->log("[DELETED] Terminal Session: {$session->uid} (created: {$session->created_at}, state: {$session->state})");
            $count++;

            if ($worker !== null && !$worker->canRun()) break;
        }

        $worker?->log("Deleted $count terminal sessions + $basketCount baskets, skipped $skipped");
        return $count;
    }

    /**
     * Cleanup system log files (> retentionDays old)
     * Handles dated subdirectories (YYYY-MM/DD.log pattern)
     */
    private function cleanupSystemLogFiles(?CronWorker $worker, int $retentionDays = 60): int {
        $count = 0;
        $cutoffTime = time() - ($retentionDays * 24 * 60 * 60);
        $cutoffDate = date('Y-m-d H:i:s', $cutoffTime);

        $worker?->log("--- Log Files (> {$retentionDays} days old, before {$cutoffDate}) ---");

        $logDirs = [
            ROOT . 'logs/debug/',
            ROOT . 'logs/errors/',
            ROOT . 'logs/test/',
            ROOT . 'logs/cron/',
            ROOT . 'logs/scraper/',
            ROOT . 'logs/webhook/',
            ROOT . 'logs/http/',
        ];

        foreach ($logDirs as $baseDir) {
            if (!is_dir($baseDir)) continue;

            $this->deleteOldLogFiles($baseDir, $cutoffTime, $count, $worker);

            if ($worker !== null && !$worker->canRun()) break;
        }

        $worker?->log("Deleted $count log files");
        return $count;
    }

    /**
     * Recursively delete old log files
     */
    private function deleteOldLogFiles(string $dir, int $cutoffTime, int &$count, ?CronWorker $worker): void {
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'log') continue;
                if ($file->getMTime() < $cutoffTime) {
                    $modTime = date('Y-m-d H:i:s', $file->getMTime());
                    $size = round($file->getSize() / 1024, 2);
                    $relativePath = str_replace(ROOT, '', $file->getPathname());

                    if (@unlink($file->getPathname())) {
                        $worker?->log("[DELETED] {$relativePath} (modified: {$modTime}, size: {$size}KB)");
                        $count++;
                    } else {
                        $worker?->log("[FAILED] Could not delete {$relativePath}");
                    }
                }

                if ($worker !== null && !$worker->canRun()) return;
            }
        } catch (\Exception $e) {
            $worker?->log("Error scanning directory $dir: " . $e->getMessage());
        }
    }

    /**
     * Cleanup empty log directories (YYYY-MM subdirs)
     */
    private function cleanupEmptyLogDirs(?CronWorker $worker): int {
        $count = 0;
        $logDirs = [
            ROOT . 'logs/debug/',
            ROOT . 'logs/errors/',
            ROOT . 'logs/test/',
        ];

        $worker?->log("--- Empty Log Directories ---");

        foreach ($logDirs as $baseDir) {
            if (!is_dir($baseDir)) continue;

            $subdirs = glob($baseDir . '*', GLOB_ONLYDIR);
            if ($subdirs === false) continue;

            foreach ($subdirs as $subdir) {
                $files = glob($subdir . '/*');
                if ($files !== false && empty($files)) {
                    $relativePath = str_replace(ROOT, '', $subdir);

                    if (@rmdir($subdir)) {
                        $worker?->log("[DELETED] Empty dir: {$relativePath}");
                        $count++;
                    } else {
                        $worker?->log("[FAILED] Could not remove dir: {$relativePath}");
                    }
                }
            }
        }

        $worker?->log("Removed $count empty directories");
        return $count;
    }
}
