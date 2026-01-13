<?php

namespace routing\routes\api;

use classes\app\CronWorker;
use features\Settings;
use JetBrains\PhpStorm\ArrayShape;
use classes\Methods;
use JetBrains\PhpStorm\NoReturn;

class CronjobController {


    /**
     * Take scheduled payments (BNPL installments, deferred payments)
     */
    #[ArrayShape(["result" => "array|null|string", "response_code" => "int"])]
    public static function takePayments(): array {
        $worker = self::init("take_payments");
        if($worker === null) return self::returnJsonResponse("Cronjob may not be initiated.", 202);

        $requestHandler = Methods::cronRequestHandler();
        $requestHandler->takePayments($worker);

        return self::end($worker);
    }


    /**
     * Retry failed payments
     */
    #[ArrayShape(["result" => "array|null|string", "response_code" => "int"])]
    public static function retryPayments(): array {
        $worker = self::init("retry_payments");
        if($worker === null) return self::returnJsonResponse("Cronjob may not be initiated.", 202);

        $requestHandler = Methods::cronRequestHandler();
        $requestHandler->retryPayments($worker);

        return self::end($worker);
    }


    /**
     * Clean up old log files
     */
    #[ArrayShape(["result" => "array|null|string", "response_code" => "int"])]
    public static function cleanupLogs(): array {
        $worker = self::init("cleanup_logs");
        if($worker === null) return self::returnJsonResponse("Cronjob may not be initiated.", 202);

        $requestHandler = Methods::cronRequestHandler();
        $requestHandler->cleanupLogs($worker);

        return self::end($worker);
    }


    /**
     * Send payment notification reminders before due dates
     */
    #[ArrayShape(["result" => "array|null|string", "response_code" => "int"])]
    public static function paymentNotifications(): array {
        $worker = self::init("payment_notifications");
        if($worker === null) return self::returnJsonResponse("Cronjob may not be initiated.", 202);

        $requestHandler = Methods::cronRequestHandler();
        $requestHandler->paymentNotifications($worker);

        return self::end($worker);
    }


    /**
     * Process notification queue - send scheduled/delayed notifications
     */
    #[ArrayShape(["result" => "array|null|string", "response_code" => "int"])]
    public static function notificationQueue(): array {
        $worker = self::init("notification_queue");
        if($worker === null) return self::returnJsonResponse("Cronjob may not be initiated.", 202);

        $requestHandler = Methods::cronRequestHandler();
        $requestHandler->processNotificationQueue($worker);

        return self::end($worker);
    }


    #[ArrayShape(["result" => "array|null|string", "response_code" => "int"])]
    private static function end(CronWorker $worker): array {
        $worker->log("Finished running cronjob");
        $worker->end();
        return self::returnJsonResponse("Finished running cronjob");
    }

    private static function init(string $type, bool $force = false): ?CronWorker {
        Settings::$omnipotent = true;
        $worker = Methods::cronWorker($type);
        $timeOfInit = time();
        return !$worker->init($timeOfInit, $force) ? null : $worker;
    }


    #[ArrayShape(["result" => "array|null|string", "response_code" => "int"])]
    private static function returnJsonResponse(string|array|null $res, int $responseCode = 200): array {
        return [
            "result" => $res,
            "response_code" => $responseCode
        ];
    }

}
