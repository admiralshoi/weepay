<?php

namespace classes\errors;

use classes\notifications\MessageDispatcher;
use features\Settings;

class ErrorNotifier {

    private static array $sentHashes = [];
    private static int $rateLimitSeconds = 300; // 5 minutes

    public static function register(): void {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool {
        // Skip if migration
        if (self::isMigration()) return false;

        $context = self::buildContext($errstr, $errfile, $errline, debug_backtrace());
        self::notify($context);

        return false; // Let PHP handle it normally too
    }

    public static function handleException(\Throwable $e): void {
        // Skip if migration
        if (self::isMigration()) return;

        $context = self::buildContext(
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTrace()
        );
        self::notify($context);

        // Re-throw for normal handling
        throw $e;
    }

    public static function handleShutdown(): void {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            if (self::isMigration()) return;

            $context = self::buildContext($error['message'], $error['file'], $error['line'], []);
            self::notify($context);
        }
    }

    public static function notifyFromLog(string $message, string $keyword, string $trace): void {
        // Called from errorLog() for DB errors etc.
        if (self::isMigration()) return;

        $context = [
            'message' => $message,
            'keyword' => $keyword,
            'trace' => $trace,
            'url' => $_SERVER['REQUEST_URI'] ?? 'CLI',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'user' => self::getUserInfo(),
            'timestamp' => date('Y-m-d H:i:s'),
            'is_checkout' => self::isCheckout(),
            'server' => LIVE ? 'PRODUCTION' : 'LOCAL',
        ];

        self::notify($context);
    }

    private static function isMigration(): bool {
        // Check suppress flag (set during migrations)
        if (class_exists('\\features\\Settings') && Settings::$suppressErrorNotifications === true) {
            return true;
        }
        return false;
    }

    private static function isCheckout(): bool {
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        // URL pattern checks
        if (preg_match('#/merchant/[^/]+/checkout#', $uri)) return true;
        if (str_contains($uri, '/api/checkout/')) return true;

        // Request parameter checks
        $params = array_merge($_GET ?? [], $_POST ?? []);
        if (!empty($params['tsid']) || !empty($params['ts_id'])) return true;
        if (!empty($params['order_code'])) return true;

        return false;
    }

    private static function buildContext(string $message, string $file, int $line, array $trace): array {
        return [
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'trace' => self::formatTrace($trace),
            'url' => $_SERVER['REQUEST_URI'] ?? 'CLI',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'user' => self::getUserInfo(),
            'timestamp' => date('Y-m-d H:i:s'),
            'is_checkout' => self::isCheckout(),
            'server' => LIVE ? 'PRODUCTION' : 'LOCAL',
        ];
    }

    private static function getUserInfo(): array {
        $info = ['uid' => null, 'email' => null];
        if (class_exists('\\features\\Settings') && !isEmpty(Settings::$user ?? null)) {
            $info['uid'] = Settings::$user->uid ?? null;
            $info['email'] = Settings::$user->email ?? null;
        }
        return $info;
    }

    private static function formatTrace(array $trace): string {
        $lines = [];
        foreach ($trace as $i => $frame) {
            $file = $frame['file'] ?? '[internal]';
            $line = $frame['line'] ?? 0;
            $func = $frame['function'] ?? '';
            $class = $frame['class'] ?? '';
            $type = $frame['type'] ?? '';
            $lines[] = "#{$i} {$file}({$line}): {$class}{$type}{$func}()";
        }
        return implode("\n", $lines);
    }

    private static function notify(array $context): void {
        // Only notify on LIVE server
        if (!defined('LIVE') || !LIVE) {
            return;
        }

        if (!defined('ERROR_NOTIFICATION_ENABLED') || !ERROR_NOTIFICATION_ENABLED) {
            return;
        }

        // Rate limiting - same error hash within 5 minutes
        $hash = md5($context['message'] . ($context['file'] ?? '') . ($context['line'] ?? ''));
        if (isset(self::$sentHashes[$hash]) && (time() - self::$sentHashes[$hash]) < self::$rateLimitSeconds) {
            return;
        }
        self::$sentHashes[$hash] = time();

        // Log the notification
        self::logNotification($context);

        // Always send email
        self::sendEmail($context);

        // SMS only for checkout errors
        if ($context['is_checkout']) {
            self::sendSms($context);
        }
    }

    private static function logNotification(array $context): void {
        $dir = ROOT . "logs/errors/notifications/" . date("Y-m") . "/";
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $log = "[" . date("Y-m-d H:i:s") . "]\n";
        $log .= "URL: " . $context['url'] . "\n";
        $log .= "Checkout: " . ($context['is_checkout'] ? 'YES' : 'NO') . "\n";
        $log .= "Message: " . $context['message'] . "\n";
        if (!empty($context['file'])) {
            $log .= "File: " . $context['file'] . ":" . $context['line'] . "\n";
        }
        if (!empty($context['keyword'])) {
            $log .= "Keyword: " . $context['keyword'] . "\n";
        }
        $log .= "User: " . ($context['user']['uid'] ?? 'N/A') . "\n";
        $log .= "Notified: EMAIL" . ($context['is_checkout'] ? " + SMS" : "") . "\n";
        $log .= "---\n";

        $fn = date("d") . ".log";
        @file_put_contents($dir . $fn, $log, FILE_APPEND);
    }

    private static function sendEmail(array $context): void {
        if (!defined('ERROR_NOTIFICATION_EMAIL') || empty(ERROR_NOTIFICATION_EMAIL)) {
            return;
        }

        $subject = "[WeePay Error] " . substr($context['message'], 0, 50);
        if ($context['is_checkout']) {
            $subject = "[CHECKOUT ERROR] " . substr($context['message'], 0, 50);
        }

        $body = self::buildEmailBody($context);

        // Use PHP mail
        @mail(ERROR_NOTIFICATION_EMAIL, $subject, $body, [
            'From' => 'development@wee-pay.dk',
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    private static function buildEmailBody(array $context): string {
        $body = "=== WeePay Error Notification ===\n\n";
        $body .= "Server: " . ($context['server'] ?? 'UNKNOWN') . "\n";
        $body .= "Time: " . $context['timestamp'] . "\n";
        $body .= "URL: " . $context['url'] . "\n";
        $body .= "Method: " . $context['method'] . "\n";
        $body .= "Checkout Flow: " . ($context['is_checkout'] ? 'YES' : 'NO') . "\n\n";

        $body .= "=== Error Details ===\n";
        $body .= "Message: " . $context['message'] . "\n";
        if (!empty($context['file'])) {
            $body .= "File: " . $context['file'] . "\n";
            $body .= "Line: " . $context['line'] . "\n";
        }
        if (!empty($context['keyword'])) {
            $body .= "Keyword: " . $context['keyword'] . "\n";
        }

        $body .= "\n=== User Session ===\n";
        $body .= "User UID: " . ($context['user']['uid'] ?? 'Not logged in') . "\n";
        $body .= "User Email: " . ($context['user']['email'] ?? 'N/A') . "\n";

        $body .= "\n=== Stack Trace ===\n";
        $body .= $context['trace'] ?? 'No trace available';

        $body .= "\n\n=== Request Info ===\n";
        $body .= "GET: " . json_encode($_GET ?? []) . "\n";
        $body .= "POST keys: " . implode(', ', array_keys($_POST ?? [])) . "\n";

        return $body;
    }

    private static function sendSms(array $context): void {
        if (!defined('ERROR_NOTIFICATION_PHONE') || empty(ERROR_NOTIFICATION_PHONE)) {
            return;
        }

        // Short SMS message
        $message = "CHECKOUT ERROR on WeePay!\n";
        $message .= substr($context['message'], 0, 100);
        $message .= "\nURL: " . substr($context['url'], 0, 50);

        // Use MessageDispatcher
        MessageDispatcher::sms(
            ERROR_NOTIFICATION_PHONE,
            $message,
            'WeePay'
        );
    }
}
