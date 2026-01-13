<?php
namespace classes\http;

use classes\Methods;
use classes\app\CronWorker;


class CronRequestHandler {


    /**
     * Take scheduled payments (BNPL installments, deferred "pushed" payments)
     * Processes payments that are due today
     */
    public function takePayments(?CronWorker $worker = null): void {
        $worker?->log("Running takePayments...");

        // TODO: Implement payment processing logic
        // 1. Find all BNPL installments due today
        // 2. Find all "pushed" payments (deferred to 1st of month) due today
        // 3. Attempt to charge each payment
        // 4. Update payment status accordingly
        // 5. Send confirmation/failure notifications

        $worker?->log("takePayments completed.");
    }


    /**
     * Retry failed payments
     * Attempts to reprocess payments that previously failed
     */
    public function retryPayments(?CronWorker $worker = null): void {
        $worker?->log("Running retryPayments...");

        // TODO: Implement retry logic
        // 1. Find all failed payments eligible for retry (based on retry policy)
        // 2. Attempt to charge each payment again
        // 3. Update retry count and status
        // 4. Mark as permanently failed after max retries
        // 5. Send notifications for successful retries or final failures

        $worker?->log("retryPayments completed.");
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
     * Process scheduled notification breakpoints
     * Triggers notifications based on scheduled events (payment reminders, overdue, etc.)
     */
    public function paymentNotifications(?CronWorker $worker = null): void {
        $worker?->log("Running scheduled notification breakpoints...");

        $results = \classes\notifications\NotificationService::processScheduledBreakpoints();

        $worker?->log("Scheduled breakpoints processed: " . json_encode($results));
        $worker?->log("paymentNotifications completed.");
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


}
