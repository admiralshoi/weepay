<?php
namespace routing\routes\admin;

use classes\Methods;

/**
 * Admin Notification Controller
 * Handles all notification system pages for admin users
 */
class NotificationController {

    // =====================================================
    // NOTIFICATION TEMPLATES
    // =====================================================

    public static function templates(array $args): mixed {
        $templates = Methods::notificationTemplates()->getByXOrderBy('created_at', 'DESC', []);
        $args['templates'] = $templates;

        return Views("NOTIFICATION_TEMPLATES", $args, "AdminNotifications");
    }

    public static function templateDetail(array $args): mixed {
        $templateId = $args['id'] ?? null;
        $isNew = $templateId === 'new';

        if ($isNew) {
            $args['template'] = null;
            $args['isNew'] = true;
        } else {
            $template = Methods::notificationTemplates()->get($templateId);
            if (!$template) {
                return Response()->redirect(__url('panel/notifications/templates'));
            }
            $args['template'] = $template;
            $args['isNew'] = false;
        }

        // Get available breakpoints for placeholder reference
        $breakpoints = Methods::notificationBreakpoints()->getByX(['status' => 'active']);
        $args['breakpoints'] = $breakpoints;

        return Views("NOTIFICATION_TEMPLATE_DETAIL", $args, "AdminNotifications");
    }

    // =====================================================
    // NOTIFICATION BREAKPOINTS
    // =====================================================

    public static function breakpoints(array $args): mixed {
        $breakpoints = Methods::notificationBreakpoints()->getByXOrderBy('category', 'ASC', []);
        $args['breakpoints'] = $breakpoints;

        return Views("NOTIFICATION_BREAKPOINTS", $args, "AdminNotifications");
    }

    // =====================================================
    // NOTIFICATION FLOWS
    // =====================================================

    public static function flows(array $args): mixed {
        $flows = Methods::notificationFlows()->getByXOrderBy('created_at', 'DESC', []);
        $args['flows'] = $flows;

        // Get breakpoints for creating new flows
        $breakpoints = Methods::notificationBreakpoints()->getByX(['status' => 'active']);
        $args['breakpoints'] = $breakpoints;

        return Views("NOTIFICATION_FLOWS", $args, "AdminNotifications");
    }

    public static function flowDetail(array $args): mixed {
        $flowId = $args['id'] ?? null;
        $isNew = $flowId === 'new';

        if ($isNew) {
            $args['flow'] = null;
            $args['isNew'] = true;
            $args['flowActions'] = Methods::toCollection([]);
        } else {
            $flow = Methods::notificationFlows()->get($flowId);
            if (!$flow) {
                return Response()->redirect(__url('panel/notifications/flows'));
            }
            $args['flow'] = $flow;
            $args['isNew'] = false;

            // Get flow actions for this flow
            $flowActions = Methods::notificationFlowActions()->getByFlow($flowId);
            $args['flowActions'] = $flowActions;
        }

        // Get breakpoints and templates for selection
        $breakpoints = Methods::notificationBreakpoints()->getByX(['status' => 'active']);
        // Include both active and draft templates for selection, sorted by created_at desc
        $query = Methods::notificationTemplates()->queryBuilder()
            ->where('status', '!=', 'archived')
            ->order('created_at', 'DESC');
        $templates = Methods::notificationTemplates()->queryGetAll($query);
        $args['breakpoints'] = $breakpoints;
        $args['templates'] = $templates;

        return Views("NOTIFICATION_FLOW_DETAIL", $args, "AdminNotifications");
    }

    // =====================================================
    // NOTIFICATION QUEUE
    // =====================================================

    public static function queue(array $args): mixed {
        // Get pending and processing items
        $pending = Methods::notificationQueue()->getByStatus('pending');
        $processing = Methods::notificationQueue()->getByStatus('processing');
        $failed = Methods::notificationQueue()->getFailed();

        $args['pendingCount'] = $pending->count();
        $args['processingCount'] = $processing->count();
        $args['failedCount'] = $failed->count();

        return Views("NOTIFICATION_QUEUE", $args, "AdminNotifications");
    }

    // =====================================================
    // NOTIFICATION LOGS
    // =====================================================

    public static function logs(array $args): mixed {
        // Get stats for the logs page header
        $now = time();
        $last24h = $now - (24 * 60 * 60);
        $last7d = $now - (7 * 24 * 60 * 60);

        $args['sentLast24h'] = Methods::notificationLogs()->countByStatus('sent', $last24h);
        $args['sentLast7d'] = Methods::notificationLogs()->countByStatus('sent', $last7d);
        $args['failedLast24h'] = Methods::notificationLogs()->countByStatus('failed', $last24h);
        $args['failedLast7d'] = Methods::notificationLogs()->countByStatus('failed', $last7d);

        return Views("NOTIFICATION_LOGS", $args, "AdminNotifications");
    }

}
