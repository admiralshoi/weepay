<?php

namespace routing\routes\api;

use classes\app\CronWorker;
use features\Settings;
use JetBrains\PhpStorm\ArrayShape;
use classes\Methods;
use JetBrains\PhpStorm\NoReturn;

class CronjobController {

    /**
     * Validate the cronjob token from route parameter
     */
    private static function validateToken(array $args): bool {
        $token = $args['token'] ?? null;
        return $token === CRONJOB_TOKEN;
    }


    /**
     * Take scheduled payments (BNPL installments, deferred payments)
     */
    #[ArrayShape(["result" => "array|null|string", "response_code" => "int"])]
    public static function takePayments(array $args): array {
        if (!self::validateToken($args)) {
            return self::returnJsonResponse("Invalid token", 401);
        }

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
    public static function retryPayments(array $args): array {
        if (!self::validateToken($args)) {
            return self::returnJsonResponse("Invalid token", 401);
        }

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
    public static function cleanupLogs(array $args): array {
        if (!self::validateToken($args)) {
            return self::returnJsonResponse("Invalid token", 401);
        }

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
    public static function paymentNotifications(array $args): array {
        if (!self::validateToken($args)) {
            return self::returnJsonResponse("Invalid token", 401);
        }

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
    public static function notificationQueue(array $args): array {
        if (!self::validateToken($args)) {
            return self::returnJsonResponse("Invalid token", 401);
        }

        $worker = self::init("notification_queue");
        if($worker === null) return self::returnJsonResponse("Cronjob may not be initiated.", 202);

        $requestHandler = Methods::cronRequestHandler();
        $requestHandler->processNotificationQueue($worker);

        return self::end($worker);
    }


    /**
     * Check overdue payments and trigger rykker notifications
     */
    #[ArrayShape(["result" => "array|null|string", "response_code" => "int"])]
    public static function rykkerChecks(array $args): array {
        if (!self::validateToken($args)) {
            return self::returnJsonResponse("Invalid token", 401);
        }

        $worker = self::init("rykker_checks");
        if($worker === null) return self::returnJsonResponse("Cronjob may not be initiated.", 202);

        $requestHandler = Methods::cronRequestHandler();
        $requestHandler->rykkerChecks($worker);

        return self::end($worker);
    }


    /**
     * Generate and send weekly reports to organisations
     */
    #[ArrayShape(["result" => "array|null|string", "response_code" => "int"])]
    public static function weeklyReports(array $args): array {
        if (!self::validateToken($args)) {
            return self::returnJsonResponse("Invalid token", 401);
        }

        $worker = self::init("weekly_reports");
        if($worker === null) return self::returnJsonResponse("Cronjob may not be initiated.", 202);

        $requestHandler = Methods::cronRequestHandler();
        $requestHandler->weeklyReports($worker);

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


    /**
     * Force run a specific cronjob (admin only)
     * POST /api/admin/cronjobs/force-run
     */
    #[ArrayShape(["result" => "array|null|string", "response_code" => "int"])]
    public static function forceRun(): array {
        $type = Settings::$postData['type'] ?? null;

        if (empty($type)) {
            return self::returnJsonResponse(["status" => "error", "message" => "Cronjob type is required"], 400);
        }

        // Map type to controller method
        $methodMap = [
            'take_payments' => 'takePayments',
            'retry_payments' => 'retryPayments',
            'cleanup_logs' => 'cleanupLogs',
            'payment_notifications' => 'paymentNotifications',
            'notification_queue' => 'notificationQueue',
            'rykker_checks' => 'rykkerChecks',
            'weekly_reports' => 'weeklyReports',
        ];

        if (!isset($methodMap[$type])) {
            return self::returnJsonResponse(["status" => "error", "message" => "Unknown cronjob type: {$type}"], 400);
        }

        // Force init the worker
        $worker = self::init($type, true);
        if ($worker === null) {
            return self::returnJsonResponse(["status" => "error", "message" => "Could not initialize cronjob"], 500);
        }

        // Run the cronjob
        $requestHandler = Methods::cronRequestHandler();
        $method = lcfirst(str_replace('_', '', ucwords($type, '_')));

        // Map to actual method names
        $actualMethod = match($type) {
            'take_payments' => 'takePayments',
            'retry_payments' => 'retryPayments',
            'cleanup_logs' => 'cleanupLogs',
            'payment_notifications' => 'paymentNotifications',
            'notification_queue' => 'processNotificationQueue',
            'rykker_checks' => 'rykkerChecks',
            'weekly_reports' => 'weeklyReports',
            default => null
        };

        if ($actualMethod && method_exists($requestHandler, $actualMethod)) {
            $requestHandler->$actualMethod($worker);
        }

        $worker->log("Force run completed by admin");
        $worker->end();

        return self::returnJsonResponse(["status" => "success", "message" => "Cronjob '{$type}' completed"]);
    }


    /**
     * Get cronjob logs for a specific type
     * GET /api/admin/cronjobs/logs?type=xxx
     */
    #[ArrayShape(["result" => "array|null|string", "response_code" => "int"])]
    public static function getLogs(array $args): array {
        $type = $args['type'] ?? null;

        if (empty($type)) {
            return self::returnJsonResponse(["status" => "error", "message" => "Cronjob type is required"], 400);
        }

        $worker = Methods::cronWorker($type);
        $logFiles = $worker->getLogFiles($type);

        if ($logFiles === null) {
            return self::returnJsonResponse(["status" => "error", "message" => "Unknown cronjob type: {$type}"], 400);
        }

        $logs = [
            'log' => file_exists($logFiles['log']) ? file_get_contents($logFiles['log']) : '',
            'date' => file_exists($logFiles['date']) ? file_get_contents($logFiles['date']) : '',
            'memory' => file_exists($logFiles['memory']) ? file_get_contents($logFiles['memory']) : '',
        ];

        return self::returnJsonResponse(["status" => "success", "logs" => $logs]);
    }


    #[ArrayShape(["result" => "array|null|string", "response_code" => "int"])]
    private static function returnJsonResponse(string|array|null $res, int $responseCode = 200): array {
        return [
            "result" => $res,
            "response_code" => $responseCode
        ];
    }

}
