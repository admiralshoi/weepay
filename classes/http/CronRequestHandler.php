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
     * Send payment notification reminders
     * Notifies users about upcoming payments (1 day, 3 days, 7 days before)
     */
    public function paymentNotifications(?CronWorker $worker = null): void {
        $worker?->log("Running paymentNotifications...");

        // TODO: Implement notification logic
        // 1. Find BNPL installments due in 1, 3, 7 days
        // 2. Find "pushed" payments due in 1, 3, 7 days
        // 3. Check if notification already sent for this period
        // 4. Send email/SMS reminders
        // 5. Log notification sent

        $worker?->log("paymentNotifications completed.");
    }


}
