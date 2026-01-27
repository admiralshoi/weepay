<?php

namespace classes\notifications;

use classes\Methods;
use Database\Collection;
use Database\model\Users;

/**
 * NotificationService - Core engine for the notification system
 *
 * This service handles:
 * - Triggering notifications from breakpoints
 * - Placeholder replacement in templates
 * - Routing to appropriate channels (email, sms, bell)
 * - Queueing for delayed notifications
 * - Logging all sent notifications
 */
class NotificationService {

    private static array $contextData = [];
    private static string $debugTag = 'NotificationService';

    /**
     * Debug log helper
     */
    private static function debug(string $message, $data = null): void {
        $logData = ['message' => $message];
        if ($data !== null) {
            $logData['data'] = $data;
        }
        debugLog($logData, self::$debugTag);
    }

    /**
     * Trigger a notification breakpoint
     * This is the main entry point for sending notifications
     *
     * @param string $breakpointKey The breakpoint identifier (e.g., 'order.completed')
     * @param array $context Context data with placeholders (user, order, organisation, etc.)
     * @return bool Whether any notifications were triggered
     */
    public static function trigger(string $breakpointKey, array $context = []): bool {
        // DEEP DEBUG: Unique tag for easy log searching
        $orderUid = $context['order']['uid'] ?? 'no_order';
        $orderPlan = $context['order']['payment_plan'] ?? 'no_plan';
        $triggerTime = date('Y-m-d H:i:s.u');

        debugLog([
            'breakpoint_key' => $breakpointKey,
            'order_uid' => $orderUid,
            'order_payment_plan' => $orderPlan,
            'timestamp' => $triggerTime,
            'context_keys' => array_keys($context),
            'user_email' => $context['user']['email'] ?? 'no_email',
        ], 'DEEP_NOTIFICATION_TRIGGER_ENTRY');

        self::debug("=== TRIGGER START ===", [
            'breakpoint_key' => $breakpointKey,
            'context_keys' => array_keys($context)
        ]);

        self::$contextData = $context;

        // Check if breakpoint exists and is active
        $breakpoint = Methods::notificationBreakpoints()->getByKey($breakpointKey);
        if (isEmpty($breakpoint)) {
            self::debug("ABORT: Breakpoint not found", ['key' => $breakpointKey]);
            return false;
        }
        if ($breakpoint->status !== 'active') {
            self::debug("ABORT: Breakpoint not active", ['key' => $breakpointKey, 'status' => $breakpoint->status]);
            return false;
        }

        self::debug("Breakpoint found", [
            'uid' => $breakpoint->uid,
            'name' => $breakpoint->name,
            'trigger_type' => $breakpoint->trigger_type
        ]);

        // Get active flows for this breakpoint
        $flows = Methods::notificationFlows()->getActiveByBreakpoint($breakpointKey);
        if ($flows->empty()) {
            self::debug("ABORT: No active flows for breakpoint", ['key' => $breakpointKey]);
            debugLog(['breakpoint' => $breakpointKey, 'reason' => 'no_active_flows'], 'DEEP_NOTIFICATION_ABORT');
            return false;
        }

        // DEEP DEBUG: Log ALL matched flows with their conditions
        $flowsList = [];
        foreach ($flows->list() as $f) {
            $flowsList[] = [
                'uid' => $f->uid,
                'name' => $f->name,
                'conditions' => $f->conditions,
                'status' => $f->status,
            ];
        }
        debugLog([
            'breakpoint' => $breakpointKey,
            'order_uid' => $context['order']['uid'] ?? 'no_order',
            'order_payment_plan' => $context['order']['payment_plan'] ?? 'no_plan',
            'flows_count' => $flows->count(),
            'flows' => $flowsList,
        ], 'DEEP_NOTIFICATION_FLOWS_FOUND');

        self::debug("Found active flows", ['count' => $flows->count()]);

        $triggered = false;

        foreach ($flows->list() as $flow) {
            self::debug("Processing flow", [
                'flow_uid' => $flow->uid,
                'flow_name' => $flow->name,
                'recipient_type' => $flow->recipient_type ?? 'user',
                'status' => $flow->status
            ]);

            // Check schedule_offset_days matches days_until_due (for scheduled notifications)
            $flowOffset = $flow->schedule_offset_days ?? null;
            $daysUntilDue = $context['days_until_due'] ?? null;
            if ($flowOffset !== null && $daysUntilDue !== null) {
                $expectedDays = abs((int)$flowOffset);
                if ((int)$daysUntilDue !== $expectedDays) {
                    self::debug("SKIP: Flow offset doesn't match days_until_due", [
                        'flow_uid' => $flow->uid,
                        'flow_offset' => $flowOffset,
                        'expected_days' => $expectedDays,
                        'actual_days_until_due' => $daysUntilDue
                    ]);
                    continue;
                }
            }

            // Check flow conditions
            $conditionsMet = self::evaluateConditions($flow->conditions, $context);
            debugLog([
                'flow_uid' => $flow->uid,
                'flow_name' => $flow->name,
                'order_uid' => $context['order']['uid'] ?? 'no_order',
                'order_payment_plan' => $context['order']['payment_plan'] ?? 'no_plan',
                'conditions' => $flow->conditions,
                'conditions_met' => $conditionsMet,
            ], 'DEEP_NOTIFICATION_FLOW_CONDITION_CHECK');

            if (!$conditionsMet) {
                self::debug("SKIP: Flow conditions not met", ['flow_uid' => $flow->uid, 'conditions' => $flow->conditions]);
                continue;
            }

            // Get flow actions
            $actions = Methods::notificationFlowActions()->getByFlow($flow->uid);
            if ($actions->empty()) {
                self::debug("SKIP: No actions for flow", ['flow_uid' => $flow->uid]);
                continue;
            }

            self::debug("Found actions for flow", ['flow_uid' => $flow->uid, 'action_count' => $actions->count()]);

            // DEBUG: Log all actions for this flow
            $actionsDebug = [];
            foreach ($actions->list() as $a) {
                $actionsDebug[] = [
                    'uid' => $a->uid,
                    'channel' => $a->channel,
                    'status' => $a->status,
                    'template' => is_object($a->template) ? $a->template->uid : $a->template
                ];
            }
            debugLog([
                'flow_uid' => $flow->uid,
                'action_count' => $actions->count(),
                'actions' => $actionsDebug
            ], 'NOTIFICATION_FLOW_ACTIONS_DEBUG');

            foreach ($actions->list() as $action) {
                debugLog([
                    'step' => 'FOREACH_ACTION_START',
                    'action_uid' => $action->uid,
                    'channel' => $action->channel,
                    'status' => $action->status,
                ], 'NOTIF_DEBUG_STEP');

                self::debug("Processing action", [
                    'action_uid' => $action->uid,
                    'channel' => $action->channel,
                    'status' => $action->status,
                    'template' => is_object($action->template) ? $action->template->uid : $action->template
                ]);

                if ($action->status !== 'active') {
                    debugLog(['step' => 'SKIP_NOT_ACTIVE', 'status' => $action->status], 'NOTIF_DEBUG_STEP');
                    self::debug("SKIP: Action not active", ['action_uid' => $action->uid, 'status' => $action->status]);
                    continue;
                }

                // Get template
                $templateUid = is_object($action->template) ? $action->template->uid : $action->template;
                debugLog(['step' => 'GET_TEMPLATE', 'templateUid' => $templateUid], 'NOTIF_DEBUG_STEP');
                $template = Methods::notificationTemplates()->get($templateUid);
                if (isEmpty($template)) {
                    debugLog(['step' => 'SKIP_TEMPLATE_NOT_FOUND', 'templateUid' => $templateUid], 'NOTIF_DEBUG_STEP');
                    self::debug("SKIP: Template not found", ['template_uid' => $templateUid]);
                    continue;
                }
                if ($template->status !== 'active') {
                    debugLog(['step' => 'SKIP_TEMPLATE_NOT_ACTIVE', 'status' => $template->status], 'NOTIF_DEBUG_STEP');
                    self::debug("SKIP: Template not active", ['template_uid' => $template->uid, 'status' => $template->status]);
                    continue;
                }

                debugLog(['step' => 'TEMPLATE_LOADED', 'template_uid' => $template->uid, 'template_status' => $template->status], 'NOTIF_DEBUG_STEP');

                self::debug("Template loaded", [
                    'template_uid' => $template->uid,
                    'template_name' => $template->name,
                    'has_subject' => !empty($template->subject),
                    'has_html' => !empty($template->html_content)
                ]);

                // Determine recipient based on flow's recipient_type
                debugLog(['step' => 'RESOLVE_RECIPIENT_START', 'recipient_type' => $flow->recipient_type ?? 'null'], 'NOTIF_DEBUG_STEP');
                $recipientData = self::resolveRecipient($flow, $context);
                debugLog(['step' => 'RESOLVE_RECIPIENT_DONE', 'recipientData' => $recipientData], 'NOTIF_DEBUG_STEP');
                self::debug("Recipient resolved", $recipientData);

                if (empty($recipientData) || (empty($recipientData['email']) && empty($recipientData['phone']) && empty($recipientData['uid']))) {
                    self::debug("SKIP: No valid recipient", ['recipient_data' => $recipientData]);
                    continue;
                }

                // Process the action
                debugLog([
                    'flow_uid' => $flow->uid,
                    'flow_name' => $flow->name,
                    'action_uid' => $action->uid,
                    'channel' => $action->channel,
                    'template_uid' => $template->uid,
                    'template_name' => $template->name,
                    'order_uid' => $context['order']['uid'] ?? 'no_order',
                    'recipient_email' => $recipientData['email'] ?? 'no_email',
                    'recipient_phone' => $recipientData['phone'] ?? 'no_phone',
                    'delay_minutes' => $action->delay_minutes ?? 0,
                    'will_send_immediately' => !isset($action->delay_minutes) || $action->delay_minutes <= 0,
                ], 'DEEP_NOTIFICATION_ABOUT_TO_SEND');

                if (isset($action->delay_minutes) && $action->delay_minutes > 0) {
                    self::debug("Queueing notification for later", ['delay_minutes' => $action->delay_minutes]);
                    self::queueNotification($action, $template, $recipientData, $context, $flow);
                } else {
                    self::debug("Sending notification immediately");
                    self::sendNotification($action, $template, $recipientData, $context, $flow);
                }

                debugLog([
                    'flow_uid' => $flow->uid,
                    'action_uid' => $action->uid,
                    'channel' => $action->channel,
                    'order_uid' => $context['order']['uid'] ?? 'no_order',
                    'sent' => true,
                ], 'DEEP_NOTIFICATION_SENT');

                $triggered = true;
            }
        }

        self::debug("=== TRIGGER END ===", ['triggered' => $triggered]);

        debugLog([
            'breakpoint' => $breakpointKey,
            'order_uid' => $context['order']['uid'] ?? 'no_order',
            'triggered' => $triggered,
            'timestamp' => date('Y-m-d H:i:s.u'),
        ], 'DEEP_NOTIFICATION_TRIGGER_COMPLETE');

        return $triggered;
    }

    /**
     * Send a notification immediately
     */
    private static function sendNotification(
        object $action,
        object $template,
        array $recipientData,
        array $context,
        object $flow
    ): bool {
        self::debug("--- sendNotification START ---", [
            'channel' => $action->channel,
            'template' => $template->name,
            'recipient' => $recipientData['email'] ?? $recipientData['phone'] ?? $recipientData['uid'] ?? 'unknown'
        ]);

        // Check for duplicate notification
        $referenceId = $context['reference_id'] ?? null;
        $referenceType = $context['reference_type'] ?? null;
        $recipientUid = $recipientData['uid'] ?? null;

        // Generate hash for logging
        $dedupHash = \classes\notifications\NotificationLogHandler::generateDedupHash(
            $flow->uid, $recipientUid, $action->channel, $referenceId, $referenceType
        );

        debugLog([
            'action' => 'dedup_check',
            'flow_uid' => $flow->uid,
            'recipient_uid' => $recipientUid,
            'channel' => $action->channel,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
            'dedup_hash' => $dedupHash,
        ], 'notification-dedup');

        if (Methods::notificationLogs()->alreadySent(
            $flow->uid,
            $recipientUid,
            $action->channel,
            $referenceId,
            $referenceType
        )) {
            debugLog([
                'action' => 'dedup_blocked',
                'flow_uid' => $flow->uid,
                'recipient_uid' => $recipientUid,
                'channel' => $action->channel,
                'reference_id' => $referenceId,
                'reference_type' => $referenceType,
                'dedup_hash' => $dedupHash,
            ], 'notification-dedup');

            self::debug("SKIP: Duplicate notification already sent", [
                'flow_uid' => $flow->uid,
                'recipient' => $recipientUid,
                'channel' => $action->channel,
                'reference_id' => $referenceId,
                'reference_type' => $referenceType
            ]);
            return false;
        }

        $content = self::replacePlaceholders($template->content, $context, false);
        $subject = $template->subject ? self::replacePlaceholders($template->subject, $context, false) : null;
        $htmlContent = $template->html_content ? self::replacePlaceholders($template->html_content, $context, true) : null;

        self::debug("Content prepared", [
            'subject' => $subject,
            'content_length' => strlen($content),
            'has_html' => !empty($htmlContent),
            'html_length' => $htmlContent ? strlen($htmlContent) : 0
        ]);

        $success = false;
        $attachments = [];

        switch ($action->channel) {
            case 'email':
                self::debug("Sending via EMAIL channel");
                // Process attachment placeholders for email
                $processed = self::processAttachmentPlaceholders($content, $htmlContent, $context);
                $content = $processed['content'];
                $htmlContent = $processed['htmlContent'];
                $attachments = $processed['attachments'];
                self::debug("Attachment placeholders processed", ['attachments_count' => count($attachments)]);
                $success = self::sendEmail($recipientData, $subject, $content, $htmlContent, $context, $attachments);
                break;
            case 'sms':
                // Check if SMS should be skipped (e.g., bulk payment from order page)
                if (!empty($context['skip_sms'])) {
                    self::debug("SMS skipped due to skip_sms flag in context");
                    $success = true; // Mark as success but don't actually send
                    break;
                }
                self::debug("Sending via SMS channel");
                $success = self::sendSms($recipientData, $content);
                break;
            case 'bell':
                self::debug("Sending via BELL channel");
                $success = self::sendBellNotification($recipientData, $subject ?? $template->name, $content, $context);
                break;
            default:
                self::debug("ERROR: Unknown channel", ['channel' => $action->channel]);
        }

        self::debug("Send result", ['success' => $success, 'channel' => $action->channel]);

        // Log the notification with reference data for deduplication
        $breakpointKey = is_object($flow->breakpoint) ? $flow->breakpoint->key : $flow->breakpoint;

        // Determine recipient identifier based on channel
        $recipientIdentifier = match($action->channel) {
            'sms' => $recipientData['phone'] ?? null,
            'email' => $recipientData['email'] ?? null,
            'bell' => $recipientData['uid'] ?? null,
            default => $recipientData['email'] ?? $recipientData['phone'] ?? null,
        };

        $logResult = self::logNotification(
            $action->channel,
            $content,
            $success ? 'sent' : 'failed',
            $flow->uid,
            $template->uid,
            $breakpointKey,
            $recipientData['uid'] ?? null,
            $recipientIdentifier,
            $subject,
            $context['reference_id'] ?? null,
            $context['reference_type'] ?? null,
            $flow->schedule_offset_days ?? null
        );

        self::debug("--- sendNotification END ---", ['success' => $success, 'logged' => $logResult]);

        return $success;
    }

    /**
     * Queue a notification for later delivery
     */
    private static function queueNotification(
        object $action,
        object $template,
        array $recipientData,
        array $context,
        object $flow
    ): bool {
        $scheduledAt = time() + (($action->delay_minutes ?? 0) * 60);
        $content = self::replacePlaceholders($template->content, $context, false);
        $subject = $template->subject ? self::replacePlaceholders($template->subject, $context, false) : null;

        self::debug("--- queueNotification ---", [
            'channel' => $action->channel,
            'delay_minutes' => $action->delay_minutes ?? 0,
            'scheduled_at' => date('Y-m-d H:i:s', $scheduledAt),
            'recipient' => $recipientData['email'] ?? $recipientData['phone'] ?? $recipientData['uid'] ?? 'unknown',
            'subject' => $subject,
            'content_length' => strlen($content)
        ]);

        $result = Methods::notificationQueue()->insert(
            $action->channel,
            $content,
            $scheduledAt,
            $action->uid,
            $recipientData['uid'] ?? null,
            $recipientData['email'] ?? null,
            $recipientData['phone'] ?? null,
            $subject,
            $context
        );

        self::debug("Queue insert result", ['success' => $result]);

        return $result;
    }

    /**
     * Process queued notifications (called by cron)
     */
    public static function processQueue(int $limit = 100): array {
        self::debug("=== PROCESS QUEUE START ===", ['limit' => $limit]);

        $pending = Methods::notificationQueue()->getPending($limit);
        $results = ['processed' => 0, 'sent' => 0, 'failed' => 0];

        self::debug("Pending items found", ['count' => $pending->count()]);

        foreach ($pending->list() as $item) {
            $results['processed']++;

            self::debug("Processing queue item", [
                'queue_uid' => $item->uid,
                'channel' => $item->channel,
                'scheduled_at' => $item->scheduled_at,
                'recipient_email' => $item->recipient_email,
                'recipient_phone' => $item->recipient_phone,
                'subject' => $item->subject
            ]);

            // Mark as processing
            Methods::notificationQueue()->setProcessing($item->uid);

            // Get flow action for template info
            $action = null;
            $template = null;
            $flow = null;

            if ($item->flow_action) {
                $flowActionUid = is_object($item->flow_action) ? $item->flow_action->uid : $item->flow_action;
                $action = Methods::notificationFlowActions()->get($flowActionUid);
                if ($action) {
                    $templateUid = is_object($action->template) ? $action->template->uid : $action->template;
                    $template = Methods::notificationTemplates()->get($templateUid);
                    $flowUid = is_object($action->flow) ? $action->flow->uid : $action->flow;
                    $flow = Methods::notificationFlows()->get($flowUid);
                }
                self::debug("Flow action resolved", [
                    'action_uid' => $action ? $action->uid : null,
                    'template_uid' => $template ? $template->uid : null,
                    'flow_uid' => $flow ? $flow->uid : null
                ]);
            }

            $recipientData = [
                'uid' => is_object($item->recipient) ? $item->recipient->uid : $item->recipient,
                'email' => $item->recipient_email,
                'phone' => $item->recipient_phone,
            ];

            $context = $item->context_data ?? [];
            $success = false;

            switch ($item->channel) {
                case 'email':
                    $htmlContent = $template && $template->html_content
                        ? self::replacePlaceholders($template->html_content, $context, true)
                        : null;
                    self::debug("Queue: Sending email", [
                        'to' => $recipientData['email'],
                        'subject' => $item->subject,
                        'has_html' => !empty($htmlContent)
                    ]);
                    $success = self::sendEmail($recipientData, $item->subject, $item->content, $htmlContent);
                    break;
                case 'sms':
                    self::debug("Queue: Sending SMS", ['to' => $recipientData['phone']]);
                    $success = self::sendSms($recipientData, $item->content);
                    break;
                case 'bell':
                    self::debug("Queue: Sending bell notification", ['to' => $recipientData['uid']]);
                    $success = self::sendBellNotification($recipientData, $item->subject ?? 'Notifikation', $item->content, $context);
                    break;
            }

            self::debug("Queue item send result", ['queue_uid' => $item->uid, 'success' => $success]);

            if ($success) {
                Methods::notificationQueue()->setSent($item->uid);
                $results['sent']++;
            } else {
                Methods::notificationQueue()->setFailed($item->uid, 'Delivery failed');
                $results['failed']++;
            }

            // Log the notification
            $breakpointKey = $flow && is_object($flow->breakpoint) ? $flow->breakpoint->key : ($flow->breakpoint ?? null);
            $context = $item->context_data ?? [];

            // Determine recipient identifier based on channel
            $recipientIdentifier = match($item->channel) {
                'sms' => $recipientData['phone'] ?? null,
                'email' => $recipientData['email'] ?? null,
                'bell' => $recipientData['uid'] ?? null,
                default => $recipientData['email'] ?? $recipientData['phone'] ?? null,
            };

            self::logNotification(
                $item->channel,
                $item->content,
                $success ? 'sent' : 'failed',
                $flow->uid ?? null,
                $template->uid ?? null,
                $breakpointKey,
                $recipientData['uid'],
                $recipientIdentifier,
                $item->subject,
                $context['reference_id'] ?? null,
                $context['reference_type'] ?? null,
                $flow->schedule_offset_days ?? null
            );
        }

        self::debug("=== PROCESS QUEUE END ===", $results);

        return $results;
    }

    /**
     * Process attachment placeholders in content
     * Returns array with 'content', 'htmlContent', and 'attachments'
     */
    private static function processAttachmentPlaceholders(string $content, ?string $htmlContent, array $context): array {
        $attachments = [];

        // Check for {{attach:order_contract}} placeholder
        if (str_contains($content, '{{attach:order_contract}}') || ($htmlContent && str_contains($htmlContent, '{{attach:order_contract}}'))) {
            try {
                $attachment = self::generateOrderContractAttachment($context);
                if ($attachment) {
                    $attachments[] = $attachment;
                }
            } catch (\Exception $e) {
                debugLog(['error' => $e->getMessage()], 'ATTACH_ORDER_CONTRACT_ERROR');
            }
        }

        // Check for {{attach:rykker_pdf}} placeholder
        if (str_contains($content, '{{attach:rykker_pdf}}') || ($htmlContent && str_contains($htmlContent, '{{attach:rykker_pdf}}'))) {
            try {
                $attachment = self::generateRykkerAttachment($context);
                if ($attachment) {
                    $attachments[] = $attachment;
                }
            } catch (\Exception $e) {
                debugLog(['error' => $e->getMessage()], 'ATTACH_RYKKER_PDF_ERROR');
            }
        }

        // Remove attachment placeholders from content
        $content = preg_replace('/\{\{attach:[a-z_]+\}\}/', '', $content);
        if ($htmlContent) {
            $htmlContent = preg_replace('/\{\{attach:[a-z_]+\}\}/', '', $htmlContent);
        }

        return [
            'content' => trim($content),
            'htmlContent' => $htmlContent ? trim($htmlContent) : null,
            'attachments' => $attachments,
        ];
    }

    /**
     * Generate order contract PDF attachment
     */
    private static function generateOrderContractAttachment(array $context): ?array {
        // Need order in context
        $order = $context['order_object'] ?? null;

        // If no object, try to get from UID
        if (!$order && isset($context['order']['uid'])) {
            $order = Methods::orders()->get($context['order']['uid']);
        }

        if (!$order) {
            self::debug("Cannot generate order contract: no order in context");
            return null;
        }

        // Only generate for BNPL orders
        if (!in_array($order->payment_plan, ['installments', 'pushed'])) {
            self::debug("Skipping contract attachment: not a BNPL order", ['payment_plan' => $order->payment_plan]);
            return null;
        }

        // Check if contract already exists
        $documentHandler = Methods::contractDocuments();
        $contractContent = $documentHandler->getContract($order);

        if (!$contractContent) {
            // Generate the contract PDF
            $pdf = new \classes\documents\OrderContractPdf($order);
            $contractContent = $pdf->generatePdfString();

            // Save it for future use
            $documentHandler->saveContract($order, $contractContent);
        }

        return [
            'filename' => "kontrakt_{$order->uid}.pdf",
            'content' => $contractContent,
            'mime' => 'application/pdf',
        ];
    }

    /**
     * Generate rykker PDF attachment
     */
    private static function generateRykkerAttachment(array $context): ?array {
        // Need payment and rykker level in context
        $payment = $context['payment_object'] ?? null;
        $rykkerLevel = $context['rykker']['level'] ?? null;

        // If no object, try to get from UID
        if (!$payment && isset($context['payment']['uid'])) {
            $payment = Methods::payments()->get($context['payment']['uid']);
        }

        if (!$payment) {
            self::debug("Cannot generate rykker PDF: no payment in context");
            return null;
        }

        if (!$rykkerLevel) {
            // Try to get from payment object
            $rykkerLevel = (int)($payment->rykker_level ?? 0);
        }

        if ($rykkerLevel < 1) {
            self::debug("Cannot generate rykker PDF: no rykker level");
            return null;
        }

        // Check if rykker already exists
        $documentHandler = Methods::contractDocuments();
        $rykkerContent = $documentHandler->getRykker($payment, $rykkerLevel);

        if (!$rykkerContent) {
            // Generate the rykker PDF
            $pdf = new \classes\documents\RykkerPdf($payment, $rykkerLevel);
            $rykkerContent = $pdf->generatePdfString();

            // Save it for future use
            $documentHandler->saveRykker($payment, $rykkerLevel, $rykkerContent);
        }

        return [
            'filename' => "rykker{$rykkerLevel}_{$payment->uid}.pdf",
            'content' => $rykkerContent,
            'mime' => 'application/pdf',
        ];
    }

    /**
     * Send email notification via MessageDispatcher
     */
    private static function sendEmail(array $recipientData, ?string $subject, string $content, ?string $htmlContent = null, array $context = [], array $attachments = []): bool {
        // Determine from name - use location name if available for consumer emails
        $fromName = null;
        if (!empty($context['location']['name'])) {
            $fromName = $context['location']['name'];
        }

        self::debug("sendEmail called", [
            'has_email' => !empty($recipientData['email']),
            'has_uid' => !empty($recipientData['uid']),
            'email' => $recipientData['email'] ?? null,
            'uid' => $recipientData['uid'] ?? null,
            'subject' => $subject,
            'fromName' => $fromName
        ]);

        // Resolve email if not provided
        if (empty($recipientData['email']) && !empty($recipientData['uid'])) {
            self::debug("Email not provided, sending to user by UID");
            try {
                $result = MessageDispatcher::emailToUser(
                    $recipientData['uid'],
                    $subject ?? BRAND_NAME,
                    $content,
                    $htmlContent
                );
                self::debug("emailToUser result", ['success' => $result]);
                return $result;
            } catch (\Exception $e) {
                self::debug("emailToUser EXCEPTION", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return false;
            }
        }

        if (empty($recipientData['email'])) {
            self::debug("ERROR: No email address available");
            return false;
        }

        try {
            self::debug("Calling MessageDispatcher::email", [
                'to' => $recipientData['email'],
                'fromName' => $fromName,
                'attachments_count' => count($attachments)
            ]);
            $result = MessageDispatcher::email(
                $recipientData['email'],
                $subject ?? BRAND_NAME,
                $content,
                $htmlContent,
                null,        // fromEmail - use default
                $fromName,   // fromName - location name or default
                $attachments // PDF attachments
            );
            self::debug("MessageDispatcher::email result", ['success' => $result]);
            return $result;
        } catch (\Exception $e) {
            self::debug("MessageDispatcher::email EXCEPTION", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return false;
        }
    }

    /**
     * Send SMS notification via MessageDispatcher
     */
    private static function sendSms(array $recipientData, string $content): bool {
        self::debug("sendSms called", [
            'has_phone' => !empty($recipientData['phone']),
            'has_uid' => !empty($recipientData['uid']),
            'phone' => $recipientData['phone'] ?? null,
            'phone_country_code' => $recipientData['phone_country_code'] ?? null,
            'content_length' => strlen($content)
        ]);

        // If we have a UID, use smsToUser to get proper country code from user record
        if (!empty($recipientData['uid'])) {
            self::debug("Sending SMS via smsToUser (has UID)");
            try {
                $result = MessageDispatcher::smsToUser($recipientData['uid'], $content);
                self::debug("smsToUser result", ['success' => $result]);
                return $result;
            } catch (\Exception $e) {
                self::debug("smsToUser EXCEPTION", ['error' => $e->getMessage()]);
                return false;
            }
        }

        if (empty($recipientData['phone'])) {
            self::debug("ERROR: No phone number available");
            return false;
        }

        // Fallback: use phone directly with country code if available
        try {
            $dialerCode = null;
            if (!empty($recipientData['phone_country_code'])) {
                $dialerCode = \classes\utility\Misc::callerCode($recipientData['phone_country_code']);
            }
            self::debug("Sending SMS directly", ['phone' => $recipientData['phone'], 'dialer_code' => $dialerCode]);
            $result = MessageDispatcher::sms($recipientData['phone'], $content, null, $dialerCode);
            self::debug("MessageDispatcher::sms result", ['success' => $result]);
            return $result;
        } catch (\Exception $e) {
            self::debug("MessageDispatcher::sms EXCEPTION", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send bell (in-app) notification via MessageDispatcher
     */
    private static function sendBellNotification(array $recipientData, string $title, string $content, array $context = []): bool {
        self::debug("sendBellNotification called", [
            'uid' => $recipientData['uid'] ?? null,
            'title' => $title,
            'content_length' => strlen($content)
        ]);

        if (empty($recipientData['uid'])) {
            self::debug("ERROR: No user UID for bell notification");
            return false;
        }

        // Determine link and reference
        $link = $context['link'] ?? null;
        $referenceType = $context['reference_type'] ?? null;
        $referenceId = $context['reference_id'] ?? null;
        $icon = $context['icon'] ?? 'mdi-bell-outline';
        $type = $context['notification_type'] ?? 'info';

        try {
            $result = MessageDispatcher::bell(
                $recipientData['uid'],
                $title,
                $content,
                $type,
                $icon,
                $link,
                $referenceType,
                $referenceId
            );
            self::debug("MessageDispatcher::bell result", ['success' => $result]);
            return $result;
        } catch (\Exception $e) {
            self::debug("MessageDispatcher::bell EXCEPTION", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Log a notification to the permanent log
     */
    private static function logNotification(
        string $channel,
        string $content,
        string $status,
        ?string $flowUid = null,
        ?string $templateUid = null,
        ?string $breakpointKey = null,
        ?string $recipientUid = null,
        ?string $recipientIdentifier = null,
        ?string $subject = null,
        ?string $referenceId = null,
        ?string $referenceType = null,
        ?int $scheduleOffset = null
    ): bool {
        return Methods::notificationLogs()->insert(
            $channel,
            $content,
            $status,
            $flowUid,
            $templateUid,
            $breakpointKey,
            $recipientUid,
            $recipientIdentifier,
            $subject,
            $referenceId,
            $referenceType,
            $scheduleOffset
        );
    }

    /**
     * Replace placeholders in content with actual values
     * Supports nested placeholders like {{user.full_name}}, {{order.amount}}
     *
     * @param string $content The content with placeholders
     * @param array $context Context data for replacement
     * @param bool $isHtml Whether the content is HTML (enables image placeholders)
     */
    public static function replacePlaceholders(string $content, array $context, bool $isHtml = false): string {
        // First pass: replace template placeholders (components)
        $content = self::replaceTemplatePlaceholders($content, $context, $isHtml);

        // Second pass: replace regular placeholders
        return preg_replace_callback('/\{\{([a-zA-Z0-9_.]+)\}\}/', function($matches) use ($context, $isHtml) {
            $placeholder = $matches[1];
            $parts = explode('.', $placeholder);

            // Handle brand placeholders
            if ($parts[0] === 'brand') {
                return self::getBrandPlaceholder($parts[1] ?? '', $isHtml);
            }

            $value = $context;
            foreach ($parts as $part) {
                if (is_array($value) && isset($value[$part])) {
                    $value = $value[$part];
                } elseif (is_object($value) && isset($value->$part)) {
                    $value = $value->$part;
                } else {
                    return $matches[0]; // Return original placeholder if not found
                }
            }

            // Format special types
            if (is_array($value) || is_object($value)) {
                return json_encode($value);
            }

            return (string) $value;
        }, $content);
    }

    /**
     * Replace template component placeholders like {{template.email_header}}
     */
    private static function replaceTemplatePlaceholders(string $content, array $context, bool $isHtml): string {
        return preg_replace_callback('/\{\{template\.([a-zA-Z0-9_]+)\}\}/', function($matches) use ($context, $isHtml) {
            $slug = $matches[1];

            // Get the component template by slug (status 'template' for base components)
            $template = Methods::notificationTemplates()->getFirst(['slug' => $slug, 'category' => 'component', 'status' => 'template']);
            if (isEmpty($template)) {
                // Fallback to 'active' for backwards compatibility
                $template = Methods::notificationTemplates()->getFirst(['slug' => $slug, 'category' => 'component', 'status' => 'active']);
            }
            if (isEmpty($template)) {
                return $matches[0]; // Return original if not found
            }

            // Get the appropriate content based on HTML mode
            $componentContent = $isHtml && !empty($template->html_content)
                ? $template->html_content
                : $template->content;

            // Recursively replace placeholders in the component (but not template placeholders to avoid infinite loops)
            return preg_replace_callback('/\{\{([a-zA-Z0-9_.]+)\}\}/', function($innerMatches) use ($context, $isHtml) {
                $placeholder = $innerMatches[1];
                $parts = explode('.', $placeholder);

                // Skip template placeholders in nested components
                if ($parts[0] === 'template') {
                    return $innerMatches[0];
                }

                // Handle brand placeholders
                if ($parts[0] === 'brand') {
                    return self::getBrandPlaceholder($parts[1] ?? '', $isHtml);
                }

                $value = $context;
                foreach ($parts as $part) {
                    if (is_array($value) && isset($value[$part])) {
                        $value = $value[$part];
                    } elseif (is_object($value) && isset($value->$part)) {
                        $value = $value->$part;
                    } else {
                        return $innerMatches[0];
                    }
                }

                if (is_array($value) || is_object($value)) {
                    return json_encode($value);
                }

                return (string) $value;
            }, $componentContent);
        }, $content);
    }

    /**
     * Get brand placeholder value
     *
     * @param string $key The brand placeholder key
     * @param bool $isHtml Whether to render HTML (for logos)
     */
    private static function getBrandPlaceholder(string $key, bool $isHtml = false): string {
        return match ($key) {
            'logo' => $isHtml
                ? '<img src="' . __asset(str_replace('.svg', '.png', LOGO_WIDE_HEADER)) . '" alt="' . BRAND_NAME . '" style="max-width: 150px; height: auto;">'
                : BRAND_NAME,
            'logo_icon' => $isHtml
                ? '<img src="' . __asset(str_replace('.svg', '.png', LOGO_ICON)) . '" alt="' . BRAND_NAME . '" style="width: 40px; height: 40px;">'
                : BRAND_NAME,
            'name' => BRAND_NAME,
            'site' => SITE_NAME,
            'url' => HOST,
            'company_name' => COMPANY_NAME,
            'company_address' => COMPANY_ADDRESS_STRING,
            'cvr' => COMPANY_CVR,
            'email' => CONTACT_EMAIL,
            'phone' => CONTACT_PHONE,
            default => '{{brand.' . $key . '}}',
        };
    }

    /**
     * Evaluate flow conditions against context
     */
    private static function evaluateConditions(?array $conditions, array $context): bool {
        self::debug("evaluateConditions START", [
            'conditions_count' => $conditions ? count($conditions) : 0,
            'conditions' => $conditions,
        ]);

        if (empty($conditions)) {
            self::debug("evaluateConditions: No conditions, returning TRUE");
            return true;
        }

        foreach ($conditions as $index => $condition) {
            // Handle both array and object conditions
            if (is_object($condition)) {
                $condition = (array) $condition;
            }
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;

            self::debug("evaluateConditions: Checking condition #$index", [
                'field' => $field,
                'operator' => $operator,
                'expected_value' => $value,
            ]);

            if (!$field) {
                self::debug("evaluateConditions: Skipping condition #$index - no field");
                continue;
            }

            // Get the actual value from context
            $parts = explode('.', $field);
            $actualValue = $context;
            foreach ($parts as $part) {
                if (is_array($actualValue) && isset($actualValue[$part])) {
                    $actualValue = $actualValue[$part];
                } elseif (is_object($actualValue) && isset($actualValue->$part)) {
                    $actualValue = $actualValue->$part;
                } else {
                    $actualValue = null;
                    break;
                }
            }

            self::debug("evaluateConditions: Resolved actual value", [
                'field' => $field,
                'actual_value' => $actualValue,
                'expected_value' => $value,
                'operator' => $operator,
            ]);

            // Evaluate the condition
            $result = match ($operator) {
                '=' => $actualValue == $value,
                '!=' => $actualValue != $value,
                '>' => $actualValue > $value,
                '>=' => $actualValue >= $value,
                '<' => $actualValue < $value,
                '<=' => $actualValue <= $value,
                'in' => is_array($value) && in_array($actualValue, $value),
                'not_in' => is_array($value) && !in_array($actualValue, $value),
                'contains' => is_string($actualValue) && str_contains($actualValue, $value),
                'starts_with' => is_string($actualValue) && str_starts_with($actualValue, $value),
                'ends_with' => is_string($actualValue) && str_ends_with($actualValue, $value),
                default => true,
            };

            self::debug("evaluateConditions: Condition #$index result", [
                'result' => $result,
                'actual_value' => $actualValue,
                'operator' => $operator,
                'expected_value' => $value,
            ]);

            if (!$result) {
                self::debug("evaluateConditions: Condition #$index FAILED, returning FALSE");
                return false;
            }
        }

        self::debug("evaluateConditions: All conditions passed, returning TRUE");
        return true;
    }

    /**
     * Resolve recipient data based on flow's recipient_type and context
     *
     * @param object $flow The notification flow with recipient_type
     * @param array $context Context data with user, organisation, location, etc.
     * @return array Recipient data with uid, email, phone, full_name
     */
    private static function resolveRecipient(object $flow, array $context): array {
        $recipient = [];
        $recipientType = $flow->recipient_type ?? 'user';

        self::debug("resolveRecipient", [
            'recipient_type' => $recipientType,
            'has_user_in_context' => isset($context['user']),
            'has_organisation_in_context' => isset($context['organisation']),
            'has_location_in_context' => isset($context['location'])
        ]);

        switch ($recipientType) {
            case 'user':
                $recipient = self::resolveUserRecipient($context);
                break;

            case 'organisation':
                $recipient = self::resolveOrganisationRecipient($context);
                break;

            case 'location':
                $recipient = self::resolveLocationRecipient($context);
                break;

            case 'organisation_owner':
                $recipient = self::resolveOrganisationOwnerRecipient($context);
                break;

            case 'custom':
                if (!empty($flow->recipient_email)) {
                    $recipient = [
                        'email' => $flow->recipient_email,
                        'full_name' => null,
                    ];
                    self::debug("Custom recipient from flow", ['email' => $flow->recipient_email]);
                }
                break;

            case 'admin':
                $recipient = self::resolveAdminRecipient($context);
                break;
        }

        // Direct recipient override from context (legacy support)
        if (isset($context['recipient_email'])) {
            $recipient['email'] = $context['recipient_email'];
            self::debug("Recipient email overridden from context", ['email' => $context['recipient_email']]);
        }
        if (isset($context['recipient_phone'])) {
            $recipient['phone'] = $context['recipient_phone'];
            self::debug("Recipient phone overridden from context", ['phone' => $context['recipient_phone']]);
        }
        if (isset($context['recipient_uid'])) {
            $recipient['uid'] = $context['recipient_uid'];
            self::debug("Recipient UID overridden from context", ['uid' => $context['recipient_uid']]);
        }

        self::debug("Final recipient resolved", $recipient);

        return $recipient;
    }

    /**
     * Resolve user recipient from context
     */
    private static function resolveUserRecipient(array $context): array {
        $recipient = [];

        if (!isset($context['user'])) {
            return $recipient;
        }

        $user = $context['user'];
        if (is_object($user)) {
            $recipient['uid'] = $user->uid ?? null;
            $recipient['email'] = $user->email ?? null;
            $recipient['phone'] = $user->phone ?? null;
            $recipient['full_name'] = $user->full_name ?? null;
        } elseif (is_array($user)) {
            $recipient['uid'] = $user['uid'] ?? null;
            $recipient['email'] = $user['email'] ?? null;
            $recipient['phone'] = $user['phone'] ?? null;
            $recipient['full_name'] = $user['full_name'] ?? null;
        } elseif (is_string($user)) {
            $recipient['uid'] = $user;
            $userObj = Users::where('uid', $user)->first();
            if ($userObj) {
                $recipient['email'] = $userObj->email;
                $recipient['phone'] = $userObj->phone;
                $recipient['full_name'] = $userObj->full_name;
            }
        }

        return $recipient;
    }

    /**
     * Resolve organisation email recipient from context
     */
    private static function resolveOrganisationRecipient(array $context): array {
        $recipient = [];

        if (!isset($context['organisation'])) {
            return $recipient;
        }

        $org = $context['organisation'];
        if (is_object($org)) {
            $recipient['email'] = $org->email ?? null;
            $recipient['full_name'] = $org->name ?? null;
        } elseif (is_array($org)) {
            $recipient['email'] = $org['email'] ?? null;
            $recipient['full_name'] = $org['name'] ?? null;
        } elseif (is_string($org)) {
            // Organisation UID provided
            $orgObj = \Database\model\Organisations::where('uid', $org)->first();
            if ($orgObj) {
                $recipient['email'] = $orgObj->email;
                $recipient['full_name'] = $orgObj->name;
            }
        }

        return $recipient;
    }

    /**
     * Resolve location email recipient from context
     */
    private static function resolveLocationRecipient(array $context): array {
        $recipient = [];

        if (!isset($context['location'])) {
            return $recipient;
        }

        $location = $context['location'];
        if (is_object($location)) {
            $recipient['email'] = $location->email ?? null;
            $recipient['full_name'] = $location->name ?? null;
        } elseif (is_array($location)) {
            $recipient['email'] = $location['email'] ?? null;
            $recipient['full_name'] = $location['name'] ?? null;
        } elseif (is_string($location)) {
            // Location UID provided
            $locationObj = \Database\model\Locations::where('uid', $location)->first();
            if ($locationObj) {
                $recipient['email'] = $locationObj->email;
                $recipient['full_name'] = $locationObj->name;
            }
        }

        return $recipient;
    }

    /**
     * Resolve organisation owner recipient from context
     */
    private static function resolveOrganisationOwnerRecipient(array $context): array {
        $recipient = [];

        if (!isset($context['organisation'])) {
            return $recipient;
        }

        $org = $context['organisation'];
        $orgUid = null;

        if (is_object($org)) {
            $orgUid = $org->uid ?? null;
        } elseif (is_array($org)) {
            $orgUid = $org['uid'] ?? null;
        } elseif (is_string($org)) {
            $orgUid = $org;
        }

        if ($orgUid) {
            // Find organisation owner
            $ownerMember = Methods::organisationMembers()->getFirst([
                'organisation' => $orgUid,
                'role' => 'owner',
                'status' => 'active'
            ]);

            if ($ownerMember && $ownerMember->uuid) {
                $user = is_object($ownerMember->uuid) ? $ownerMember->uuid : Methods::users()->get($ownerMember->uuid);
                if ($user) {
                    $recipient['uid'] = $user->uid;
                    $recipient['email'] = $user->email;
                    $recipient['phone'] = $user->phone;
                    $recipient['full_name'] = $user->full_name;
                }
            }
        }

        return $recipient;
    }

    /**
     * Resolve admin recipient from context
     * Returns the first active admin user (access_level 8 or 9)
     */
    private static function resolveAdminRecipient(array $context): array {
        $recipient = [];

        // Find first active admin user
        $admin = Users::where('access_level', '>=', 8)
            ->order('access_level', 'DESC')
            ->first();

        if ($admin) {
            $recipient['uid'] = $admin->uid;
            $recipient['email'] = $admin->email;
            $recipient['phone'] = $admin->phone ?? null;
            $recipient['full_name'] = $admin->full_name ?? 'Admin';
            self::debug("Admin recipient resolved", ['uid' => $admin->uid, 'email' => $admin->email]);
        } else {
            self::debug("No admin user found for recipient");
        }

        return $recipient;
    }

    /**
     * Get available placeholders for a breakpoint
     */
    public static function getAvailablePlaceholders(string $breakpointKey): array {
        $breakpoint = Methods::notificationBreakpoints()->getByKey($breakpointKey);
        if (isEmpty($breakpoint)) {
            return [];
        }

        return $breakpoint->available_placeholders ?? [];
    }

    /**
     * Build payment plan context from an order
     * This creates a rich context object with all payment plan details
     *
     * @param object $order The order object
     * @return array Payment plan context data
     */
    public static function buildPaymentPlanContext(object $order): array {
        $currency = $order->currency ?? 'DKK';
        $orderAmount = $order->amount ?? 0;

        $context = [
            'total_installments' => 1,
            'remaining_installments' => 0,
            'completed_installments' => 0,
            'first_amount' => $orderAmount,
            'first_amount_formatted' => self::formatAmount($orderAmount, $currency),
            'installment_amount' => $orderAmount,
            'installment_amount_formatted' => self::formatAmount($orderAmount, $currency),
            'remaining_amount' => 0,
            'remaining_amount_formatted' => '0,00 ' . $currency,
            'total_amount' => $orderAmount,
            'total_amount_formatted' => self::formatAmount($orderAmount, $currency),
            'next_due_date' => null,
            'first_due_date' => null,
            'last_due_date' => null,
            'schedule_summary' => '',
            'total_paid' => 0,
            'total_paid_formatted' => '0,00 ' . $currency,
        ];

        // Get payment units for this order
        $paymentUnits = \Database\model\PartialPaymentUnits::where('order', $order->uid)
            ->order('installment_number', 'ASC')
            ->all()->list();

        if (empty($paymentUnits)) {
            // Single payment order
            return $context;
        }

        $context['total_installments'] = count($paymentUnits);
        $paidCount = 0;
        $totalPaid = 0;
        $remaining = 0;
        $nextDueDate = null;
        $firstDueDate = null;
        $lastDueDate = null;
        $scheduleLines = [];

        foreach ($paymentUnits as $i => $unit) {
            $isPaid = in_array($unit->status, ['COMPLETED', 'PAID']);

            if ($i === 0) {
                $context['first_amount'] = $unit->amount;
                $context['first_amount_formatted'] = self::formatAmount($unit->amount, $currency);
                $firstDueDate = $unit->due_date;
            }

            if ($isPaid) {
                $paidCount++;
                $totalPaid += $unit->amount;
            } else {
                $remaining += $unit->amount;
                if ($nextDueDate === null) {
                    $nextDueDate = $unit->due_date;
                }
            }

            $lastDueDate = $unit->due_date;

            // Build schedule summary line
            $statusText = $isPaid ? '✓' : '';
            $dueDateFormatted = $unit->due_date ? date('d.m.Y', strtotime($unit->due_date)) : '-';
            $scheduleLines[] = "Rate " . ($i + 1) . ": " . self::formatAmount($unit->amount, $currency) . " - " . $dueDateFormatted . " " . $statusText;
        }

        // Calculate typical installment amount (excluding first which may differ)
        if (count($paymentUnits) > 1) {
            $context['installment_amount'] = $paymentUnits[1]->amount;
            $context['installment_amount_formatted'] = self::formatAmount($paymentUnits[1]->amount, $currency);
        }

        $context['remaining_installments'] = $context['total_installments'] - $paidCount;
        $context['completed_installments'] = $paidCount;
        $context['remaining_amount'] = $remaining;
        $context['remaining_amount_formatted'] = self::formatAmount($remaining, $currency);
        $context['total_paid'] = $totalPaid;
        $context['total_paid_formatted'] = self::formatAmount($totalPaid, $currency);
        $context['next_due_date'] = $nextDueDate ? date('d.m.Y', strtotime($nextDueDate)) : null;
        $context['first_due_date'] = $firstDueDate ? date('d.m.Y', strtotime($firstDueDate)) : null;
        $context['last_due_date'] = $lastDueDate ? date('d.m.Y', strtotime($lastDueDate)) : null;
        $context['schedule_summary'] = implode("\n", $scheduleLines);

        return $context;
    }

    /**
     * Format amount from øre to readable string
     */
    private static function formatAmount(int $amountInOre, string $currency = 'DKK'): string {
        return number_format($amountInOre / 100, 2, ',', '.') . ' ' . $currency;
    }

    /**
     * Build common links context for an order
     *
     * @param object $order The order object
     * @return array Links context data
     */
    public static function buildLinksContext(object $order): array {
        $orderUid = $order->uid;

        return [
            'order_link' => HOST . 'order/' . $orderUid,
            'payment_link' => HOST . 'pay/' . $orderUid,
            'receipt_link' => HOST . 'receipt/' . $orderUid,
            'agreement_link' => HOST . 'agreement/' . $orderUid,
            'dashboard_link' => HOST . 'dashboard',
            'history_link' => HOST . 'payments',
            'retry_link' => HOST . 'pay/' . $orderUid . '?retry=1',
        ];
    }

    /**
     * Preview a template with sample data
     */
    public static function previewTemplate(string $templateUid, array $sampleData = []): array {
        $template = Methods::notificationTemplates()->get($templateUid);
        if (isEmpty($template)) {
            return ['error' => 'Template not found'];
        }

        // Default sample data
        $defaultData = [
            'user' => [
                'uid' => 'usr_sample123',
                'full_name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+4512345678',
            ],
            'order' => [
                'uid' => 'ord_sample456',
                'amount' => '299,00',
                'currency' => 'DKK',
                'status' => 'COMPLETED',
            ],
            'organisation' => [
                'name' => 'Sample Organisation',
                'email' => 'org@example.com',
            ],
            'app' => [
                'name' => BRAND_NAME,
                'url' => HOST,
            ],
        ];

        $data = array_merge($defaultData, $sampleData);

        return [
            'subject' => $template->subject ? self::replacePlaceholders($template->subject, $data, false) : null,
            'content' => self::replacePlaceholders($template->content, $data, false),
            'html_content' => $template->html_content ? self::replacePlaceholders($template->html_content, $data, true) : null,
        ];
    }

    /**
     * Process scheduled notification breakpoints (called by cron)
     * Finds records matching scheduled breakpoints and triggers notifications
     *
     * @return array Results with counts per breakpoint
     */
    public static function processScheduledBreakpoints(): array {
        self::debug("=== PROCESS SCHEDULED BREAKPOINTS START ===");

        $results = [
            'breakpoints_processed' => 0,
            'notifications_triggered' => 0,
            'details' => []
        ];

        // Get all active scheduled breakpoints
        $breakpoints = Methods::notificationBreakpoints()->getByX([
            'trigger_type' => 'scheduled',
            'status' => 'active'
        ]);

        self::debug("Found scheduled breakpoints", ['count' => $breakpoints->count()]);

        if ($breakpoints->empty()) {
            self::debug("No scheduled breakpoints found, exiting");
            return $results;
        }

        foreach ($breakpoints->list() as $breakpoint) {
            self::debug("Processing scheduled breakpoint", [
                'key' => $breakpoint->key,
                'name' => $breakpoint->name
            ]);
            $bpResults = self::processScheduledBreakpoint($breakpoint);
            $results['breakpoints_processed']++;
            $results['notifications_triggered'] += $bpResults['triggered'];
            $results['details'][$breakpoint->key] = $bpResults;
        }

        self::debug("=== PROCESS SCHEDULED BREAKPOINTS END ===", $results);

        return $results;
    }

    /**
     * Process a single scheduled breakpoint
     */
    private static function processScheduledBreakpoint(object $breakpoint): array {
        $results = ['triggered' => 0, 'records_found' => 0];

        // Get active flows for this breakpoint
        $flows = Methods::notificationFlows()->getActiveByBreakpoint($breakpoint->key);
        if ($flows->empty()) {
            self::debug("No active flows for scheduled breakpoint", ['key' => $breakpoint->key]);
            return $results;
        }

        self::debug("Found flows for scheduled breakpoint", [
            'breakpoint' => $breakpoint->key,
            'flow_count' => $flows->count()
        ]);

        // Group flows by schedule_offset_days for efficient querying
        $flowsByOffset = [];
        foreach ($flows->list() as $flow) {
            $offset = (int)($flow->schedule_offset_days ?? 0);
            if (!isset($flowsByOffset[$offset])) {
                $flowsByOffset[$offset] = [];
            }
            $flowsByOffset[$offset][] = $flow;
        }

        self::debug("Flows grouped by offset", ['offsets' => array_keys($flowsByOffset)]);

        // Process each unique offset
        foreach ($flowsByOffset as $offsetDays => $flowsForOffset) {
            self::debug("Processing offset", [
                'offset_days' => $offsetDays,
                'flows_at_offset' => count($flowsForOffset)
            ]);

            $records = self::getRecordsForScheduledBreakpoint($breakpoint->key, $offsetDays);
            $results['records_found'] += count($records);

            self::debug("Found records for offset", [
                'offset_days' => $offsetDays,
                'record_count' => count($records)
            ]);

            foreach ($records as $record) {
                self::debug("Processing record", [
                    'reference_id' => $record['reference_id'],
                    'reference_type' => $record['reference_type']
                ]);

                // Build context from record
                $context = self::buildContextFromRecord($breakpoint->key, $record);

                // Trigger notification for each flow at this offset
                foreach ($flowsForOffset as $flow) {
                    // Check if already sent (deduplication)
                    if (self::hasScheduledNotificationBeenSent($flow->uid, $record['reference_id'], $offsetDays)) {
                        self::debug("SKIP: Already sent", [
                            'flow_uid' => $flow->uid,
                            'reference_id' => $record['reference_id'],
                            'offset' => $offsetDays
                        ]);
                        continue;
                    }

                    // Trigger the notification
                    self::debug("Triggering notification for flow", ['flow_uid' => $flow->uid]);
                    if (self::triggerForFlow($flow, $context)) {
                        $results['triggered']++;
                        self::debug("Notification triggered successfully", ['flow_uid' => $flow->uid]);

                        // Mark as sent to prevent duplicates
                        self::markScheduledNotificationSent($flow->uid, $record['reference_id'], $offsetDays);
                    } else {
                        self::debug("Notification NOT triggered", ['flow_uid' => $flow->uid]);
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Get records matching a scheduled breakpoint for a specific offset
     */
    private static function getRecordsForScheduledBreakpoint(string $breakpointKey, int $offsetDays): array {
        $records = [];
        $targetDate = strtotime(($offsetDays >= 0 ? '+' : '') . $offsetDays . ' days', strtotime('today'));
        $targetDateStr = date('Y-m-d', $targetDate);

        switch ($breakpointKey) {
            case 'payment.due_reminder':
                // Find payments due on target date (offsetDays before today means target is in future)
                $payments = \Database\model\PartialPaymentUnits::where('status', 'SCHEDULED')
                    ->where('due_date', $targetDateStr)
                    ->all()->list();

                foreach ($payments as $payment) {
                    $records[] = [
                        'reference_id' => $payment->uid,
                        'reference_type' => 'payment',
                        'payment' => $payment,
                        'order_uid' => $payment->order ?? null,
                        'user_uid' => null, // Will be resolved from order
                    ];
                }
                break;

            case 'payment.overdue_reminder':
                // Find payments that are overdue (past due_date, still scheduled)
                $overdueDate = date('Y-m-d', strtotime('-' . abs($offsetDays) . ' days'));
                $payments = \Database\model\PartialPaymentUnits::where('status', 'SCHEDULED')
                    ->where('due_date', $overdueDate)
                    ->all()->list();

                foreach ($payments as $payment) {
                    $records[] = [
                        'reference_id' => $payment->uid,
                        'reference_type' => 'payment',
                        'payment' => $payment,
                        'order_uid' => $payment->order ?? null,
                        'user_uid' => null,
                    ];
                }
                break;

            // Add more scheduled breakpoint types as needed
        }

        return $records;
    }

    /**
     * Build context array from a record for placeholder replacement
     */
    private static function buildContextFromRecord(string $breakpointKey, array $record): array {
        $context = [];

        // Add payment data
        if (isset($record['payment'])) {
            $payment = $record['payment'];
            $currency = 'DKK';

            $context['payment'] = self::buildPaymentContext($payment, $currency);

            // Calculate days until/overdue
            $dueTimestamp = strtotime($payment->due_date);
            $today = strtotime('today');
            $daysDiff = floor(($dueTimestamp - $today) / 86400);
            $context['days_until_due'] = max(0, $daysDiff);
            $context['days_overdue'] = max(0, -$daysDiff);
        }

        // Resolve order and user
        if (isset($record['order_uid']) && $record['order_uid']) {
            $order = Methods::orders()->get($record['order_uid']);
            if ($order) {
                $currency = $order->currency ?? 'DKK';

                $context['order'] = self::buildOrderContext($order, $currency);

                // Add payment plan context
                $context['payment_plan'] = self::buildPaymentPlanContext($order);

                // Add links context
                $links = self::buildLinksContext($order);
                $context['payment_link'] = $links['payment_link'];
                $context['receipt_link'] = $links['receipt_link'];
                $context['order_link'] = $links['order_link'];
                $context['agreement_link'] = $links['agreement_link'];
                $context['dashboard_link'] = $links['dashboard_link'];
                $context['history_link'] = $links['history_link'];

                // Get user from order
                if ($order->user) {
                    $user = is_object($order->user) ? $order->user : Methods::users()->get($order->user);
                    if ($user) {
                        $context['user'] = self::buildUserContext($user);
                    }
                }

                // Get organisation from order
                if ($order->organisation) {
                    $org = is_object($order->organisation) ? $order->organisation : Methods::organisations()->get($order->organisation);
                    if ($org) {
                        $context['organisation'] = self::buildOrganisationContext($org);
                    }
                }

                // Get location from order
                if ($order->location) {
                    $location = is_object($order->location) ? $order->location : Methods::locations()->get($order->location);
                    if ($location) {
                        $context['location'] = self::buildLocationContext($location);
                    }
                }

                // Get card info if available
                $context['card'] = self::buildCardContext($order);

                // Build fees context
                $context['fees'] = self::buildFeesContext($order);
            }
        }

        // Add date/time context
        $context['today'] = date('d.m.Y');
        $context['today_full'] = self::formatDateFull(time());
        $context['current_time'] = date('H:i');
        $context['current_year'] = date('Y');

        // Add app context
        $context['app'] = [
            'name' => BRAND_NAME,
            'url' => HOST,
            'support_email' => CONTACT_EMAIL ?? (BRAND_NAME . ' Support'),
            'login_url' => HOST . 'login',
        ];

        // Add VIVA note (standard text)
        $context['viva_note'] = 'Opkrævning og afvikling af betalinger håndteres af VIVA på vegne af forretningen.';

        // Store reference for deduplication
        $context['reference_id'] = $record['reference_id'];
        $context['reference_type'] = $record['reference_type'];

        return $context;
    }

    /**
     * Build user context with all available placeholders
     */
    public static function buildUserContext($user): array {
        if (is_string($user)) {
            $user = Methods::users()->get($user);
        }
        if (!$user) return [];

        return [
            'uid' => $user->uid ?? null,
            'full_name' => $user->full_name ?? null,
            'first_name' => $user->first_name ?? null,
            'last_name' => $user->last_name ?? null,
            'email' => $user->email ?? null,
            'phone' => $user->phone ?? null,
        ];
    }

    /**
     * Build organisation context with all available placeholders
     */
    public static function buildOrganisationContext($org): array {
        if (is_string($org)) {
            $org = Methods::organisations()->get($org);
        }
        if (!$org) return [];

        return [
            'uid' => $org->uid ?? null,
            'name' => $org->name ?? null,
            'email' => $org->email ?? null,
            'phone' => $org->phone ?? null,
            'address' => $org->address ?? null,
            'city' => $org->city ?? null,
            'zip' => $org->zip ?? null,
            'cvr' => $org->cvr ?? null,
        ];
    }

    /**
     * Build location context with all available placeholders
     */
    public static function buildLocationContext($location): array {
        if (is_string($location)) {
            $location = Methods::locations()->get($location);
        }
        if (!$location) return [];

        return [
            'uid' => $location->uid ?? null,
            'name' => $location->name ?? null,
            'address' => $location->address ?? null,
            'city' => $location->city ?? null,
            'zip' => $location->zip ?? null,
            'phone' => $location->phone ?? null,
            'email' => $location->email ?? null,
        ];
    }

    /**
     * Build order context with all available placeholders
     */
    public static function buildOrderContext($order, string $currency = 'DKK'): array {
        if (is_string($order)) {
            $order = Methods::orders()->get($order);
        }
        if (!$order) return [];

        $createdAt = $order->created_at ?? null;
        $createdTimestamp = is_numeric($createdAt) ? (int)$createdAt : ($createdAt ? strtotime($createdAt) : null);

        return [
            'uid' => $order->uid ?? null,
            'amount' => $order->amount ?? 0,
            'formatted_amount' => self::formatAmount($order->amount ?? 0, $currency),
            'currency' => $currency,
            'status' => $order->status ?? null,
            'payment_plan' => $order->payment_plan ?? null, // direct, pushed, installments
            'caption' => $order->caption ?? null,
            'created_at' => $createdAt,
            'created_date' => $createdTimestamp ? date('d.m.Y', $createdTimestamp) : null,
            'created_time' => $createdTimestamp ? date('H:i', $createdTimestamp) : null,
            'created_datetime' => $createdTimestamp ? date('d.m.Y H:i', $createdTimestamp) : null,
        ];
    }

    /**
     * Build payment context with all available placeholders
     */
    public static function buildPaymentContext($payment, string $currency = 'DKK'): array {
        if (is_string($payment)) {
            $payment = Methods::partialPaymentUnits()->get($payment);
        }
        if (!$payment) return [];

        $dueDate = $payment->due_date ?? null;
        $paidAt = $payment->paid_at ?? null;
        $paidTimestamp = is_numeric($paidAt) ? (int)$paidAt : ($paidAt ? strtotime($paidAt) : null);

        $statusLabels = [
            'PENDING' => 'Afventer',
            'SCHEDULED' => 'Planlagt',
            'COMPLETED' => 'Betalt',
            'PAID' => 'Betalt',
            'PAST_DUE' => 'Forsinket',
            'FAILED' => 'Fejlet',
            'CANCELLED' => 'Annulleret',
            'REFUNDED' => 'Refunderet',
        ];

        return [
            'uid' => $payment->uid ?? null,
            'amount' => $payment->amount ?? 0,
            'formatted_amount' => self::formatAmount($payment->amount ?? 0, $currency),
            'due_date' => $dueDate,
            'due_date_formatted' => $dueDate ? date('d.m.Y', strtotime($dueDate)) : null,
            'paid_at' => $paidAt,
            'paid_date' => $paidTimestamp ? date('d.m.Y', $paidTimestamp) : null,
            'paid_time' => $paidTimestamp ? date('H:i', $paidTimestamp) : null,
            'installment_number' => $payment->installment_number ?? null,
            'status' => $payment->status ?? null,
            'status_label' => $statusLabels[$payment->status ?? ''] ?? ($payment->status ?? 'Ukendt'),
        ];
    }

    /**
     * Build card context (masked card info)
     */
    public static function buildCardContext($order): array {
        // Try to get card info from order's payment method or viva transaction
        // This is a placeholder - actual implementation depends on where card data is stored
        return [
            'last4' => '****', // Would come from stored card data
            'brand' => null,
            'expiry' => null,
            'holder_name' => null,
        ];
    }

    /**
     * Build fees context
     */
    public static function buildFeesContext($order): array {
        $reminderFee = 10000; // 100 DKK in øre - this should come from config
        $currency = $order->currency ?? 'DKK';

        // Count reminders sent for this order
        $reminderCount = 0;
        // This would query notification logs for reminder notifications for this order
        // For now, just returning structure

        $totalFees = $reminderCount * $reminderFee;
        $orderAmount = $order->amount ?? 0;

        return [
            'reminder_fee' => self::formatAmount($reminderFee, $currency),
            'reminder_fee_amount' => $reminderFee,
            'total_fees' => self::formatAmount($totalFees, $currency),
            'total_fees_amount' => $totalFees,
            'reminder_count' => $reminderCount,
            'total_outstanding' => $orderAmount + $totalFees,
            'total_outstanding_formatted' => self::formatAmount($orderAmount + $totalFees, $currency),
        ];
    }

    /**
     * Format date in full Danish format
     */
    private static function formatDateFull(int $timestamp): string {
        $months = [
            1 => 'januar', 2 => 'februar', 3 => 'marts', 4 => 'april',
            5 => 'maj', 6 => 'juni', 7 => 'juli', 8 => 'august',
            9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'december'
        ];
        $day = date('j', $timestamp);
        $month = $months[(int)date('n', $timestamp)];
        $year = date('Y', $timestamp);
        return "d. {$day}. {$month} {$year}";
    }

    /**
     * Trigger notification for a specific flow (used by scheduled breakpoints)
     */
    private static function triggerForFlow(object $flow, array $context): bool {
        // Check flow conditions
        if (!self::evaluateConditions($flow->conditions, $context)) {
            return false;
        }

        // Check flow date range
        $now = time();
        if ($flow->starts_at && strtotime($flow->starts_at) > $now) {
            return false;
        }
        if ($flow->ends_at && strtotime($flow->ends_at) < $now) {
            return false;
        }

        // Get flow actions
        $actions = Methods::notificationFlowActions()->getByFlow($flow->uid);
        if ($actions->empty()) {
            return false;
        }

        $triggered = false;

        foreach ($actions->list() as $action) {
            if ($action->status !== 'active') {
                continue;
            }

            // Get template
            $template = Methods::notificationTemplates()->get($action->template);
            if (isEmpty($template) || $template->status !== 'active') {
                continue;
            }

            // Determine recipient based on flow's recipient_type
            $recipientData = self::resolveRecipient($flow, $context);
            if (empty($recipientData) || (empty($recipientData['email']) && empty($recipientData['phone']) && empty($recipientData['uid']))) {
                continue;
            }

            // Process the action
            if ($action->delay_minutes > 0) {
                self::queueNotification($action, $template, $recipientData, $context, $flow);
            } else {
                self::sendNotification($action, $template, $recipientData, $context, $flow);
            }

            $triggered = true;
        }

        return $triggered;
    }

    /**
     * Check if a scheduled notification has already been sent
     */
    private static function hasScheduledNotificationBeenSent(string $flowUid, string $referenceId, int $offsetDays): bool {
        // Check notification logs for this combination
        $existing = Methods::notificationLogs()->getFirst([
            'flow' => $flowUid,
            'reference_id' => $referenceId,
            'schedule_offset' => $offsetDays,
        ]);

        return !isEmpty($existing);
    }

    /**
     * Mark a scheduled notification as sent (for deduplication)
     */
    private static function markScheduledNotificationSent(string $flowUid, string $referenceId, int $offsetDays): void {
        // This is handled automatically when logging the notification
        // The log entry includes flow, reference_id, and schedule_offset
    }
}
