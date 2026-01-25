<?php

namespace classes\payments;

use classes\Methods;
use classes\utility\Crud;
use Database\Collection;
use Database\model\RequiresAttentionNotifications;

class RequiresAttentionNotificationHandler extends Crud {

    function __construct() {
        parent::__construct(RequiresAttentionNotifications::newStatic(), "requires_attention_notifications");
    }

    /**
     * Get unresolved notifications for a target audience
     *
     * @param string $targetAudience 'admin' or 'merchant'
     * @param string|null $organisationId Organisation UID (required for merchant)
     * @return Collection
     */
    public function getUnresolved(string $targetAudience, ?string $organisationId = null): Collection {
        $where = [
            'target_audience' => $targetAudience,
            'resolved' => 0,
        ];

        if ($targetAudience === 'merchant' && !isEmpty($organisationId)) {
            $where['organisation'] = $organisationId;
        }

        return $this->getByXOrderBy('created_at', 'DESC', $where);
    }

    /**
     * Get unresolved count for a target audience
     *
     * @param string $targetAudience 'admin' or 'merchant'
     * @param string|null $organisationId Organisation UID (required for merchant)
     * @return int
     */
    public function getUnresolvedCount(string $targetAudience, ?string $organisationId = null): int {
        $where = [
            'target_audience' => $targetAudience,
            'resolved' => 0,
        ];

        if ($targetAudience === 'merchant' && !isEmpty($organisationId)) {
            $where['organisation'] = $organisationId;
        }

        return $this->count($where);
    }

    /**
     * Mark a notification as resolved
     *
     * @param string $uid Notification UID
     * @param string $resolvedByUserId User who resolved it
     * @return bool
     */
    public function markResolved(string $uid, string $resolvedByUserId): bool {
        return $this->update([
            'resolved' => 1,
            'resolved_by' => $resolvedByUserId,
            'resolved_at' => date('Y-m-d H:i:s'),
        ], ['uid' => $uid]);
    }

    /**
     * Create notification from a payment failure
     *
     * @param object $payment Payment object
     * @param int $vivaEventId Viva event ID
     * @param array $chargeResult Full charge result from Viva (contains ErrorCode, ErrorText, response, etc.)
     * @return string|false Created notification UID or false
     */
    public function createFromPaymentFailure(object $payment, int $vivaEventId, array $chargeResult = []): string|false {
        // Get HTTP error code from charge result (e.g., 403 = API disabled)
        $httpErrorCode = $chargeResult['error_code'] ?? null;

        debugLog([
            'payment_uid' => $payment->uid,
            'viva_event_id' => $vivaEventId,
            'http_error_code' => $httpErrorCode,
            'charge_result_keys' => array_keys($chargeResult),
        ], 'ATTENTION_NOTIFICATION_CREATE_START');

        $categorizer = new PaymentErrorCategorizer();

        $requiresAttention = $categorizer->requiresMerchantAttention($vivaEventId, $httpErrorCode);
        debugLog([
            'viva_event_id' => $vivaEventId,
            'http_error_code' => $httpErrorCode,
            'requires_merchant_attention' => $requiresAttention,
            'fault_type' => $categorizer->categorize($vivaEventId, $httpErrorCode),
        ], 'ATTENTION_NOTIFICATION_CATEGORIZATION');

        // Only create notification if it requires merchant attention
        if (!$requiresAttention) {
            debugLog([
                'viva_event_id' => $vivaEventId,
                'reason' => 'Does not require merchant attention',
            ], 'ATTENTION_NOTIFICATION_SKIP');
            return false;
        }

        // Check for existing unresolved notification for same payment
        $existing = $this->getFirst([
            'related_entity_type' => 'payment',
            'related_entity_uid' => $payment->uid,
            'resolved' => 0,
        ]);

        if (!isEmpty($existing)) {
            // Already have an unresolved notification for this payment
            return $existing->uid;
        }

        // Get organisation UID (handle foreign key resolution)
        $organisationUid = is_object($payment->organisation) ? $payment->organisation->uid : $payment->organisation;

        // Build error context with full Viva response details
        $errorContext = [
            'viva_error_code' => $chargeResult['error_code'] ?? null,
            'viva_error_text' => $chargeResult['error'] ?? null,
            'viva_event_id' => $vivaEventId,
            'payment_amount' => $payment->amount ?? null,
            'payment_currency' => $payment->currency ?? null,
            'payment_attempts' => $payment->attempts ?? 0,
            'initial_transaction_id' => $payment->initial_transaction_id ?? null,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        // Include full response if available (excluding sensitive data)
        if (!empty($chargeResult['response'])) {
            $errorContext['viva_response'] = $chargeResult['response'];
        }

        $notificationData = [
            'target_audience' => 'merchant',
            'organisation' => $organisationUid,
            'source' => 'payment',
            'type' => $categorizer->getNotificationType($vivaEventId, $httpErrorCode),
            'severity' => $categorizer->getSeverity($vivaEventId, $httpErrorCode),
            'title' => $categorizer->getTitle($vivaEventId, $httpErrorCode),
            'message' => $categorizer->getMessage($vivaEventId, $payment, $httpErrorCode),
            'related_entity_type' => 'payment',
            'related_entity_uid' => $payment->uid,
            'error_context' => $errorContext,
            'viva_event_id' => $vivaEventId,
            'fault_type' => $categorizer->categorize($vivaEventId, $httpErrorCode),
        ];

        debugLog([
            'notification_data' => $notificationData,
            'organisation_uid' => $organisationUid,
        ], 'ATTENTION_NOTIFICATION_ABOUT_TO_CREATE');

        $result = $this->create($notificationData);

        debugLog([
            'create_result' => $result,
            'recent_uid' => $this->recentUid ?? 'NOT_SET',
        ], 'ATTENTION_NOTIFICATION_CREATE_RESULT');

        return $result;
    }

    /**
     * Create a PHP error notification for admins
     *
     * @param string $title Error title
     * @param string $message Error message
     * @param array $context Error context (file, line, trace, etc.)
     * @param string $severity 'warning', 'critical', or 'info'
     * @param string $type 'php_error' or 'php_fatal'
     * @return string|false Created notification UID or false
     */
    public function createPhpError(
        string $title,
        string $message,
        array $context = [],
        string $severity = 'critical',
        string $type = 'php_error'
    ): string|false {
        return $this->create([
            'target_audience' => 'admin',
            'organisation' => null,
            'source' => 'php_error',
            'type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'error_context' => $context,
            'fault_type' => 'platform',
        ]);
    }

    /**
     * Create a cronjob failure notification for admins
     *
     * @param string $cronjobType Cronjob type/name
     * @param string $message Error message
     * @param array $context Error context
     * @return string|false Created notification UID or false
     */
    public function createCronjobFailure(
        string $cronjobType,
        string $message,
        array $context = []
    ): string|false {
        return $this->create([
            'target_audience' => 'admin',
            'organisation' => null,
            'source' => 'cronjob',
            'type' => 'cronjob_failure',
            'severity' => 'critical',
            'title' => "Cronjob fejlet: {$cronjobType}",
            'message' => $message,
            'error_context' => $context,
            'fault_type' => 'platform',
        ]);
    }

    /**
     * Create a webhook failure notification for admins
     *
     * @param string $webhookType Webhook type
     * @param string $message Error message
     * @param array $context Error context
     * @return string|false Created notification UID or false
     */
    public function createWebhookFailure(
        string $webhookType,
        string $message,
        array $context = []
    ): string|false {
        return $this->create([
            'target_audience' => 'admin',
            'organisation' => null,
            'source' => 'webhook',
            'type' => 'webhook_failure',
            'severity' => 'warning',
            'title' => "Webhook fejlet: {$webhookType}",
            'message' => $message,
            'error_context' => $context,
            'fault_type' => 'platform',
        ]);
    }

    /**
     * Create an API error notification for admins
     *
     * @param string $apiName API name
     * @param string $message Error message
     * @param array $context Error context
     * @return string|false Created notification UID or false
     */
    public function createApiError(
        string $apiName,
        string $message,
        array $context = []
    ): string|false {
        return $this->create([
            'target_audience' => 'admin',
            'organisation' => null,
            'source' => 'api',
            'type' => 'api_error',
            'severity' => 'warning',
            'title' => "API fejl: {$apiName}",
            'message' => $message,
            'error_context' => $context,
            'fault_type' => 'platform',
        ]);
    }

    /**
     * Create a gateway error notification (can be for admin or merchant)
     *
     * @param string $message Error message
     * @param string|null $organisationId Organisation UID (null for platform-wide)
     * @param array $context Error context
     * @return string|false Created notification UID or false
     */
    public function createGatewayError(
        string $message,
        ?string $organisationId = null,
        array $context = []
    ): string|false {
        $isAdmin = isEmpty($organisationId);

        return $this->create([
            'target_audience' => $isAdmin ? 'admin' : 'merchant',
            'organisation' => $organisationId,
            'source' => 'payment',
            'type' => 'gateway_error',
            'severity' => 'critical',
            'title' => 'Betalingsgateway fejl',
            'message' => $message,
            'error_context' => $context,
            'fault_type' => $isAdmin ? 'platform' : 'system',
        ]);
    }

    /**
     * Create notification from any Viva API error (refund, createPayment, etc.)
     * This is a generic handler for all Viva API calls that may fail due to merchant config issues.
     *
     * @param string $action The action that failed ('refund', 'create_payment', 'charge', 'card_validation')
     * @param array $vivaResult The result from the Viva API call
     * @param string|null $organisationUid Organisation UID
     * @param array $context Additional context (payment_uid, order_uid, amount, etc.)
     * @return string|false Created notification UID or false if not merchant attention required
     */
    public function createFromVivaError(
        string $action,
        array $vivaResult,
        ?string $organisationUid = null,
        array $context = []
    ): string|false {
        $vivaEventId = $vivaResult['EventId'] ?? $vivaResult['event_id'] ?? null;
        $httpErrorCode = $vivaResult['ErrorCode'] ?? $vivaResult['error_code'] ?? null;
        $errorText = $vivaResult['ErrorText'] ?? $vivaResult['error'] ?? 'Ukendt fejl';

        debugLog([
            'action' => $action,
            'viva_event_id' => $vivaEventId,
            'http_error_code' => $httpErrorCode,
            'error_text' => $errorText,
            'organisation_uid' => $organisationUid,
            'context_keys' => array_keys($context),
        ], 'ATTENTION_NOTIFICATION_VIVA_ERROR_START');

        $categorizer = new PaymentErrorCategorizer();

        // Check if this requires merchant attention
        $requiresAttention = $categorizer->requiresMerchantAttention($vivaEventId ?? 0, $httpErrorCode);

        debugLog([
            'requires_merchant_attention' => $requiresAttention,
            'fault_type' => $categorizer->categorize($vivaEventId ?? 0, $httpErrorCode),
        ], 'ATTENTION_NOTIFICATION_VIVA_ERROR_CATEGORIZATION');

        if (!$requiresAttention) {
            debugLog([
                'action' => $action,
                'reason' => 'Does not require merchant attention',
            ], 'ATTENTION_NOTIFICATION_VIVA_ERROR_SKIP');
            return false;
        }

        // Build action-specific title and type
        $actionTitles = [
            'refund' => 'Refundering fejlet',
            'create_payment' => 'Oprettelse af betaling fejlet',
            'charge' => 'Opkrævning fejlet',
            'card_validation' => 'Kortvalidering fejlet',
        ];

        $actionTypes = [
            'refund' => 'refund_failed',
            'create_payment' => 'payment_creation_failed',
            'charge' => 'charge_failed',
            'card_validation' => 'card_validation_failed',
        ];

        // Use categorizer title if available, otherwise use action-specific title
        $title = $categorizer->getTitle($vivaEventId ?? 0, $httpErrorCode, $action);
        if ($title === 'Betalingsfejl' || $title === 'Systemfejl') {
            $title = $actionTitles[$action] ?? 'Viva API fejl';
        }

        $type = $categorizer->getNotificationType($vivaEventId ?? 0, $httpErrorCode, $action);
        if ($type === 'other') {
            $type = $actionTypes[$action] ?? 'viva_api_error';
        }

        // Build error context
        $errorContext = array_merge([
            'action' => $action,
            'viva_event_id' => $vivaEventId,
            'viva_error_code' => $httpErrorCode,
            'viva_error_text' => $errorText,
            'timestamp' => date('Y-m-d H:i:s'),
        ], $context);

        // Include full response if available
        if (!empty($vivaResult)) {
            $errorContext['viva_response'] = $vivaResult;
        }

        $notificationData = [
            'target_audience' => 'merchant',
            'organisation' => $organisationUid,
            'source' => 'payment',
            'type' => $type,
            'severity' => $categorizer->getSeverity($vivaEventId ?? 0, $httpErrorCode),
            'title' => $title,
            'message' => $categorizer->getMessage($vivaEventId ?? 0, null, $httpErrorCode, $action) . "\n\nFejlbesked: {$errorText}",
            'error_context' => $errorContext,
            'viva_event_id' => $vivaEventId,
            'fault_type' => $categorizer->categorize($vivaEventId ?? 0, $httpErrorCode),
        ];

        // Add related entity if provided
        if (!isEmpty($context['payment_uid'] ?? null)) {
            $notificationData['related_entity_type'] = 'payment';
            $notificationData['related_entity_uid'] = $context['payment_uid'];
        } elseif (!isEmpty($context['order_uid'] ?? null)) {
            $notificationData['related_entity_type'] = 'order';
            $notificationData['related_entity_uid'] = $context['order_uid'];
        }

        debugLog([
            'notification_data' => $notificationData,
        ], 'ATTENTION_NOTIFICATION_VIVA_ERROR_ABOUT_TO_CREATE');

        $result = $this->create($notificationData);

        debugLog([
            'create_result' => $result,
        ], 'ATTENTION_NOTIFICATION_VIVA_ERROR_RESULT');

        return $result;
    }

    /**
     * Get all notifications with filters
     *
     * @param array $filters Filter options
     * @return Collection
     */
    public function getFiltered(array $filters = []): Collection {
        $query = $this->queryBuilder();

        if (isset($filters['target_audience'])) {
            $query->where('target_audience', $filters['target_audience']);
        }

        if (isset($filters['organisation'])) {
            $query->where('organisation', $filters['organisation']);
        }

        if (isset($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (isset($filters['resolved'])) {
            $query->where('resolved', (int)$filters['resolved']);
        }

        if (isset($filters['fault_type'])) {
            $query->where('fault_type', $filters['fault_type']);
        }

        $query->order('created_at', 'DESC');

        return $this->queryGetAll($query);
    }

    /**
     * Get statistics for admin dashboard
     *
     * @return object Statistics object
     */
    public function getAdminStats(): object {
        $unresolvedAdmin = $this->count(['target_audience' => 'admin', 'resolved' => 0]);
        $unresolvedMerchant = $this->count(['target_audience' => 'merchant', 'resolved' => 0]);

        $criticalCount = $this->count(['resolved' => 0, 'severity' => 'critical']);
        $warningCount = $this->count(['resolved' => 0, 'severity' => 'warning']);

        return (object)[
            'unresolved_admin' => $unresolvedAdmin,
            'unresolved_merchant' => $unresolvedMerchant,
            'critical_count' => $criticalCount,
            'warning_count' => $warningCount,
            'total_unresolved' => $unresolvedAdmin + $unresolvedMerchant,
        ];
    }

    /**
     * Resolve all notifications for a related entity
     *
     * @param string $entityType Entity type (payment, order, etc.)
     * @param string $entityUid Entity UID
     * @param string $resolvedByUserId User who resolved them
     * @return int Number of notifications resolved
     */
    public function resolveByEntity(string $entityType, string $entityUid, string $resolvedByUserId): int {
        $notifications = $this->getByX([
            'related_entity_type' => $entityType,
            'related_entity_uid' => $entityUid,
            'resolved' => 0,
        ]);

        $count = 0;
        foreach ($notifications->list() as $notification) {
            if ($this->markResolved($notification->uid, $resolvedByUserId)) {
                $count++;
            }
        }

        return $count;
    }

}
