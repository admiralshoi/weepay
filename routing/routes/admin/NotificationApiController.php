<?php
namespace routing\routes\admin;

use classes\Methods;
use Database\model\NotificationTemplates;
use Database\model\NotificationFlows;
use Database\model\NotificationQueue;
use Database\model\NotificationLogs;
use JetBrains\PhpStorm\NoReturn;

/**
 * Admin Notification API Controller
 * Handles all notification system API endpoints
 */
class NotificationApiController {

    // =====================================================
    // TEMPLATES API
    // =====================================================

    #[NoReturn] public static function templatesList(array $args): void {
        $page = (int)($args['page'] ?? 1);
        $perPage = (int)($args['per_page'] ?? 25);
        $search = $args['search'] ?? '';
        $typeFilter = $args['type'] ?? '';
        $statusFilter = $args['status'] ?? '';
        $sortColumn = $args['sort_column'] ?? 'created_at';
        $sortDirection = strtoupper($args['sort_direction'] ?? 'DESC');

        $allowedSortColumns = ['created_at', 'name', 'type', 'status'];
        if (!in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'created_at';
        }

        $query = NotificationTemplates::queryBuilder();

        if (!empty($search)) {
            $query->startGroup('OR')
                ->whereLike('name', $search)
                ->whereLike('subject', $search)
                ->whereLike('uid', $search)
                ->endGroup();
        }

        if (!empty($typeFilter)) {
            $query->where('type', $typeFilter);
        }

        if (!empty($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        $totalCount = (clone $query)->count();
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $perPage;

        $templates = $query->order($sortColumn, $sortDirection)
            ->limit($perPage)
            ->offset($offset)
            ->all();

        $formattedTemplates = [];
        foreach ($templates->list() as $template) {
            $formattedTemplates[] = [
                'uid' => $template->uid,
                'name' => $template->name,
                'type' => $template->type,
                'subject' => $template->subject,
                'status' => $template->status,
                'created_at' => $template->created_at,
            ];
        }

        Response()->jsonSuccess('', [
            'templates' => $formattedTemplates,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $totalCount,
                'totalPages' => $totalPages,
            ],
        ]);
    }

    #[NoReturn] public static function templateCreate(array $args): void {
        $name = trim($args['name'] ?? '');
        $type = $args['type'] ?? 'email';
        $subject = trim($args['subject'] ?? '');
        $content = $args['content'] ?? '';
        $htmlContent = $args['html_content'] ?? null;
        $placeholders = $args['placeholders'] ?? [];
        $status = $args['status'] ?? 'draft';

        if (empty($name)) {
            Response()->jsonError('Navn er påkrævet');
        }

        if (empty($content)) {
            Response()->jsonError('Indhold er påkrævet');
        }

        $handler = Methods::notificationTemplates();
        $success = $handler->insert(
            $name,
            $type,
            $content,
            $subject ?: null,
            $htmlContent,
            $placeholders,
            $status,
            __uuid()
        );

        if ($success) {
            Response()->jsonSuccess('Skabelon oprettet', ['uid' => $handler->recentUid]);
        } else {
            Response()->jsonError('Kunne ikke oprette skabelon');
        }
    }

    #[NoReturn] public static function templatePreview(array $args): void {
        $htmlContent = $args['html_content'] ?? '';

        if (empty($htmlContent)) {
            Response()->jsonError('Ingen HTML indhold at forhåndsvise');
        }

        // Replace only brand and template placeholders (no user/order data)
        $renderedHtml = \classes\notifications\NotificationService::replacePlaceholders($htmlContent, [], true);

        Response()->jsonSuccess('Forhåndsvisning genereret', ['html' => $renderedHtml]);
    }

    #[NoReturn] public static function templateUpdate(array $args): void {
        $uid = $args['uid'] ?? null;
        $name = trim($args['name'] ?? '');
        $type = $args['type'] ?? null;
        $subject = trim($args['subject'] ?? '');
        $content = $args['content'] ?? '';
        $htmlContent = $args['html_content'] ?? null;
        $placeholders = $args['placeholders'] ?? [];
        $status = $args['status'] ?? null;

        if (empty($uid)) {
            Response()->jsonError('Skabelon ID mangler');
        }

        $handler = Methods::notificationTemplates();
        $template = $handler->get($uid);

        if (isEmpty($template)) {
            Response()->jsonError('Skabelon ikke fundet');
        }

        $updateData = [];
        if (!empty($name)) $updateData['name'] = $name;
        if ($type !== null) $updateData['type'] = $type;
        if ($subject !== '') $updateData['subject'] = $subject ?: null;
        if ($content !== '') $updateData['content'] = $content;
        if ($htmlContent !== null) $updateData['html_content'] = $htmlContent;
        if (!empty($placeholders)) $updateData['placeholders'] = $placeholders;
        if ($status !== null) $updateData['status'] = $status;

        if (empty($updateData)) {
            Response()->jsonError('Ingen data at opdatere');
        }

        $success = $handler->update($updateData, ['uid' => $uid]);

        if ($success) {
            Response()->jsonSuccess('Skabelon opdateret');
        } else {
            Response()->jsonError('Kunne ikke opdatere skabelon');
        }
    }

    #[NoReturn] public static function templateDelete(array $args): void {
        $uid = $args['uid'] ?? null;

        if (empty($uid)) {
            Response()->jsonError('Skabelon ID mangler');
        }

        $handler = Methods::notificationTemplates();

        // Check if template is used in any flow actions
        $flowActions = Methods::notificationFlowActions()->getByTemplate($uid);
        if (!$flowActions->empty()) {
            Response()->jsonError('Skabelonen er i brug af ' . $flowActions->count() . ' flow handlinger');
        }

        $success = $handler->delete(['uid' => $uid]);

        if ($success) {
            Response()->jsonSuccess('Skabelon slettet');
        } else {
            Response()->jsonError('Kunne ikke slette skabelon');
        }
    }

    // =====================================================
    // FLOWS API
    // =====================================================

    #[NoReturn] public static function flowsList(array $args): void {
        $page = (int)($args['page'] ?? 1);
        $perPage = (int)($args['per_page'] ?? 25);
        $search = $args['search'] ?? '';
        $statusFilter = $args['status'] ?? '';
        $breakpointFilter = $args['breakpoint'] ?? '';
        $sortColumn = $args['sort_column'] ?? 'created_at';
        $sortDirection = strtoupper($args['sort_direction'] ?? 'DESC');

        $allowedSortColumns = ['created_at', 'name', 'status', 'priority'];
        if (!in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'created_at';
        }

        $query = NotificationFlows::queryBuilder();

        if (!empty($search)) {
            $query->startGroup('OR')
                ->whereLike('name', $search)
                ->whereLike('description', $search)
                ->whereLike('uid', $search)
                ->endGroup();
        }

        if (!empty($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        if (!empty($breakpointFilter)) {
            $query->where('breakpoint', $breakpointFilter);
        }

        $totalCount = (clone $query)->count();
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $perPage;

        $flows = $query->order($sortColumn, $sortDirection)
            ->limit($perPage)
            ->offset($offset)
            ->all();

        $formattedFlows = [];
        foreach ($flows->list() as $flow) {
            // Get breakpoint details
            $breakpoint = Methods::notificationBreakpoints()->excludeForeignKeys()->getFirst(['key' => $flow->breakpoint]);
            $breakpointName = $breakpoint ? $breakpoint->name : $flow->breakpoint;

            // Get action count
            $actionCount = Methods::notificationFlowActions()->count(['flow' => $flow->uid]);

            $formattedFlows[] = [
                'uid' => $flow->uid,
                'name' => $flow->name,
                'description' => $flow->description,
                'breakpoint' => $flow->breakpoint,
                'breakpoint_name' => $breakpointName,
                'status' => $flow->status,
                'priority' => $flow->priority,
                'starts_at' => $flow->starts_at,
                'ends_at' => $flow->ends_at,
                'action_count' => $actionCount,
                'created_at' => $flow->created_at,
            ];
        }

        Response()->jsonSuccess('', [
            'flows' => $formattedFlows,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $totalCount,
                'totalPages' => $totalPages,
            ],
        ]);
    }

    #[NoReturn] public static function flowCreate(array $args): void {
        debugLog($args, 'flowCreate_args');

        $name = trim($args['name'] ?? '');
        $description = trim($args['description'] ?? '');
        $breakpoint = $args['breakpoint'] ?? '';
        $status = $args['status'] ?? 'draft';
        $priority = (int)($args['priority'] ?? 100);
        $startsAt = $args['starts_at'] ?? null;
        $endsAt = $args['ends_at'] ?? null;
        $conditions = $args['conditions'] ?? null;
        // Decode conditions if it's a JSON string
        if (is_string($conditions) && !empty($conditions)) {
            $conditions = json_decode($conditions, true);
            if (!is_array($conditions)) $conditions = null;
        }
        $scheduleOffsetDays = (int)($args['schedule_offset_days'] ?? 0);
        $recipientType = $args['recipient_type'] ?? 'user';
        $recipientEmail = trim($args['recipient_email'] ?? '') ?: null;

        debugLog([
            'name' => $name,
            'breakpoint' => $breakpoint,
            'status' => $status,
            'priority' => $priority,
            'scheduleOffsetDays' => $scheduleOffsetDays,
            'recipientType' => $recipientType,
        ], 'flowCreate_parsed');

        if (empty($name)) {
            Response()->jsonError('Navn er påkrævet');
        }

        if (empty($breakpoint)) {
            Response()->jsonError('Breakpoint er påkrævet');
        }

        // Look up breakpoint by key to get uid (form sends key, db needs uid)
        $breakpointRecord = Methods::notificationBreakpoints()->getFirst(['key' => $breakpoint]);
        debugLog(['byKey' => $breakpointRecord ? $breakpointRecord->uid : null], 'flowCreate_breakpoint_lookup');

        if (!$breakpointRecord) {
            // Try by uid as fallback
            $breakpointRecord = Methods::notificationBreakpoints()->get($breakpoint);
            debugLog(['byUid' => $breakpointRecord ? $breakpointRecord->uid : null], 'flowCreate_breakpoint_fallback');
        }
        if (!$breakpointRecord) {
            Response()->jsonError('Ugyldig breakpoint');
        }
        $breakpointUid = $breakpointRecord->uid;
        debugLog(['breakpointUid' => $breakpointUid], 'flowCreate_breakpoint_resolved');

        // Validate recipient_type
        $validRecipientTypes = ['user', 'organisation', 'location', 'organisation_owner', 'custom'];
        if (!in_array($recipientType, $validRecipientTypes)) {
            Response()->jsonError('Ugyldig modtagertype');
        }

        // Require email for custom recipient type
        if ($recipientType === 'custom' && empty($recipientEmail)) {
            Response()->jsonError('E-mail er påkrævet for brugerdefineret modtager');
        }

        // Convert dates to Unix timestamps (default starts_at to now if not provided)
        $startsAtTs = $startsAt ? strtotime($startsAt) : time();
        $endsAtTs = $endsAt ? strtotime($endsAt) : null;

        debugLog([
            'name' => $name,
            'breakpointUid' => $breakpointUid,
            'description' => $description,
            'status' => $status,
            'priority' => $priority,
            'startsAtTs' => $startsAtTs,
            'endsAtTs' => $endsAtTs,
            'conditions' => $conditions,
            'createdBy' => __uuid(),
            'scheduleOffsetDays' => $scheduleOffsetDays,
            'recipientType' => $recipientType,
            'recipientEmail' => $recipientEmail,
        ], 'flowCreate_insert_data');

        $handler = Methods::notificationFlows();
        $success = $handler->insert(
            $name,
            $breakpointUid,
            $description ?: null,
            $status,
            $priority,
            $startsAtTs,
            $endsAtTs,
            $conditions,
            __uuid(),
            $scheduleOffsetDays,
            $recipientType,
            $recipientEmail
        );

        debugLog(['success' => $success, 'recentUid' => $handler->recentUid ?? null], 'flowCreate_result');

        if ($success) {
            $flowUid = $handler->recentUid;

            // Create any pending actions
            $actions = $args['actions'] ?? [];
            if (!empty($actions) && is_array($actions)) {
                $actionHandler = Methods::notificationFlowActions();
                foreach ($actions as $action) {
                    if (!empty($action['template']) && !empty($action['channel'])) {
                        $actionHandler->insert(
                            $flowUid,
                            $action['template'],
                            $action['channel']
                        );
                    }
                }
            }

            Response()->jsonSuccess('Flow oprettet', ['uid' => $flowUid]);
        } else {
            Response()->jsonError('Kunne ikke oprette flow');
        }
    }

    #[NoReturn] public static function flowUpdate(array $args): void {
        $uid = $args['uid'] ?? null;
        $name = trim($args['name'] ?? '');
        $description = $args['description'] ?? null;
        $breakpoint = $args['breakpoint'] ?? null;
        $status = $args['status'] ?? null;
        $priority = isset($args['priority']) ? (int)$args['priority'] : null;
        $startsAt = $args['starts_at'] ?? null;
        $endsAt = $args['ends_at'] ?? null;
        $conditions = $args['conditions'] ?? null;
        // Decode conditions if it's a JSON string
        if (is_string($conditions) && !empty($conditions)) {
            $conditions = json_decode($conditions, true);
            if (!is_array($conditions)) $conditions = null;
        }
        $scheduleOffsetDays = isset($args['schedule_offset_days']) ? (int)$args['schedule_offset_days'] : null;
        $recipientType = $args['recipient_type'] ?? null;
        $recipientEmail = isset($args['recipient_email']) ? (trim($args['recipient_email']) ?: null) : null;

        if (empty($uid)) {
            Response()->jsonError('Flow ID mangler');
        }

        $handler = Methods::notificationFlows();
        $flow = $handler->get($uid);

        if (isEmpty($flow)) {
            Response()->jsonError('Flow ikke fundet');
        }

        // Validate recipient_type if provided
        if ($recipientType !== null) {
            $validRecipientTypes = ['user', 'organisation', 'location', 'organisation_owner', 'custom'];
            if (!in_array($recipientType, $validRecipientTypes)) {
                Response()->jsonError('Ugyldig modtagertype');
            }
        }

        // Look up breakpoint by key to get uid if breakpoint is being updated
        $breakpointUid = null;
        if ($breakpoint !== null) {
            $breakpointRecord = Methods::notificationBreakpoints()->getFirst(['key' => $breakpoint]);
            if (!$breakpointRecord) {
                $breakpointRecord = Methods::notificationBreakpoints()->get($breakpoint);
            }
            if (!$breakpointRecord) {
                Response()->jsonError('Ugyldig breakpoint');
            }
            $breakpointUid = $breakpointRecord->uid;
        }

        $updateData = [];
        if (!empty($name)) $updateData['name'] = $name;
        if ($description !== null) $updateData['description'] = $description ?: null;
        if ($breakpointUid !== null) $updateData['breakpoint'] = $breakpointUid;
        if ($status !== null) $updateData['status'] = $status;
        if ($priority !== null) $updateData['priority'] = $priority;
        if ($startsAt !== null) $updateData['starts_at'] = $startsAt ? strtotime($startsAt) : null;
        if ($endsAt !== null) $updateData['ends_at'] = $endsAt ? strtotime($endsAt) : null;
        if ($conditions !== null) $updateData['conditions'] = $conditions;
        if ($scheduleOffsetDays !== null) $updateData['schedule_offset_days'] = $scheduleOffsetDays;
        if ($recipientType !== null) $updateData['recipient_type'] = $recipientType;
        if (array_key_exists('recipient_email', $args)) $updateData['recipient_email'] = $recipientEmail;

        // Validate custom recipient requires email
        $effectiveRecipientType = $recipientType ?? $flow->recipient_type;
        $effectiveRecipientEmail = array_key_exists('recipient_email', $updateData) ? $updateData['recipient_email'] : $flow->recipient_email;
        if ($effectiveRecipientType === 'custom' && empty($effectiveRecipientEmail)) {
            Response()->jsonError('E-mail er påkrævet for brugerdefineret modtager');
        }

        if (empty($updateData)) {
            Response()->jsonError('Ingen data at opdatere');
        }

        $success = $handler->update($updateData, ['uid' => $uid]);

        if ($success) {
            Response()->jsonSuccess('Flow opdateret');
        } else {
            Response()->jsonError('Kunne ikke opdatere flow');
        }
    }

    #[NoReturn] public static function flowDelete(array $args): void {
        $uid = $args['uid'] ?? null;

        if (empty($uid)) {
            Response()->jsonError('Flow ID mangler');
        }

        // Delete flow actions first
        Methods::notificationFlowActions()->delete(['flow' => $uid]);

        // Delete flow
        $success = Methods::notificationFlows()->delete(['uid' => $uid]);

        if ($success) {
            Response()->jsonSuccess('Flow slettet');
        } else {
            Response()->jsonError('Kunne ikke slette flow');
        }
    }

    #[NoReturn] public static function flowClone(array $args): void {
        $uid = $args['uid'] ?? null;

        if (empty($uid)) {
            Response()->jsonError('Flow ID mangler');
        }

        $flowHandler = Methods::notificationFlows();
        $flow = $flowHandler->excludeForeignKeys()->get($uid);

        if (isEmpty($flow)) {
            Response()->jsonError('Flow ikke fundet');
        }

        // Create new flow with copied data
        $success = $flowHandler->insert(
            $flow->name . ' (kopi)',
            $flow->breakpoint,
            $flow->description,
            'draft', // Always start as draft
            $flow->priority,
            $flow->starts_at,
            $flow->ends_at,
            $flow->conditions ? array_values(toArray($flow->conditions)) : null,
            __uuid(),
            $flow->schedule_offset_days,
            $flow->recipient_type,
            $flow->recipient_email
        );

        if (!$success) {
            Response()->jsonError('Kunne ikke klone flow');
        }

        $newFlowUid = $flowHandler->recentUid;

        // Clone all actions
        $actionHandler = Methods::notificationFlowActions();
        $actions = $actionHandler->excludeForeignKeys()->getByX(['flow' => $uid]);

        $clonedActions = 0;
        if ($actions && !$actions->empty()) {
            foreach ($actions->list() as $action) {
                $actionSuccess = $actionHandler->insert(
                    $newFlowUid,
                    $action->template,
                    $action->channel,
                    $action->delay_minutes,
                    $action->status
                );
                if ($actionSuccess) $clonedActions++;
            }
        }

        $redirectUrl = __url(\classes\enumerations\Links::$admin->panelNotificationFlows) . '/' . $newFlowUid;
        Response()->setRedirect($redirectUrl)->jsonSuccess('Flow klonet', [
            'uid' => $newFlowUid,
            'cloned_actions' => $clonedActions,
        ]);
    }

    // =====================================================
    // FLOW ACTIONS API
    // =====================================================

    #[NoReturn] public static function flowActionCreate(array $args): void {
        $flowUid = $args['flow'] ?? null;
        $templateUid = $args['template'] ?? null;
        $channel = $args['channel'] ?? 'email';
        $delayMinutes = (int)($args['delay_minutes'] ?? 0);
        $status = $args['status'] ?? 'active';

        if (empty($flowUid)) {
            Response()->jsonError('Flow ID mangler');
        }

        if (empty($templateUid)) {
            Response()->jsonError('Skabelon ID mangler');
        }

        $handler = Methods::notificationFlowActions();
        $success = $handler->insert($flowUid, $templateUid, $channel, $delayMinutes, $status);

        if ($success) {
            Response()->jsonSuccess('Handling oprettet', ['uid' => $handler->recentUid]);
        } else {
            Response()->jsonError('Kunne ikke oprette handling');
        }
    }

    #[NoReturn] public static function flowActionUpdate(array $args): void {
        $uid = $args['uid'] ?? null;
        $templateUid = $args['template'] ?? null;
        $channel = $args['channel'] ?? null;
        $delayMinutes = isset($args['delay_minutes']) ? (int)$args['delay_minutes'] : null;
        $status = $args['status'] ?? null;

        if (empty($uid)) {
            Response()->jsonError('Handling ID mangler');
        }

        $handler = Methods::notificationFlowActions();
        $action = $handler->get($uid);

        if (isEmpty($action)) {
            Response()->jsonError('Handling ikke fundet');
        }

        $updateData = [];
        if ($templateUid !== null) $updateData['template'] = $templateUid;
        if ($channel !== null) $updateData['channel'] = $channel;
        if ($delayMinutes !== null) $updateData['delay_minutes'] = $delayMinutes;
        if ($status !== null) $updateData['status'] = $status;

        if (empty($updateData)) {
            Response()->jsonError('Ingen data at opdatere');
        }

        $success = $handler->update($updateData, ['uid' => $uid]);

        if ($success) {
            Response()->jsonSuccess('Handling opdateret');
        } else {
            Response()->jsonError('Kunne ikke opdatere handling');
        }
    }

    #[NoReturn] public static function flowActionDelete(array $args): void {
        $uid = $args['uid'] ?? null;

        if (empty($uid)) {
            Response()->jsonError('Handling ID mangler');
        }

        $success = Methods::notificationFlowActions()->delete(['uid' => $uid]);

        if ($success) {
            Response()->jsonSuccess('Handling slettet');
        } else {
            Response()->jsonError('Kunne ikke slette handling');
        }
    }

    // =====================================================
    // QUEUE API
    // =====================================================

    #[NoReturn] public static function queueList(array $args): void {
        $page = (int)($args['page'] ?? 1);
        $perPage = (int)($args['per_page'] ?? 25);
        $statusFilter = $args['status'] ?? '';
        $channelFilter = $args['channel'] ?? '';
        $sortColumn = $args['sort_column'] ?? 'scheduled_at';
        $sortDirection = strtoupper($args['sort_direction'] ?? 'ASC');

        $allowedSortColumns = ['scheduled_at', 'created_at', 'channel', 'status'];
        if (!in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'scheduled_at';
        }

        $query = NotificationQueue::queryBuilder();

        if (!empty($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        if (!empty($channelFilter)) {
            $query->where('channel', $channelFilter);
        }

        $totalCount = (clone $query)->count();
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $perPage;

        $items = $query->order($sortColumn, $sortDirection)
            ->limit($perPage)
            ->offset($offset)
            ->all();

        $formattedItems = [];
        foreach ($items->list() as $item) {
            $formattedItems[] = [
                'uid' => $item->uid,
                'channel' => $item->channel,
                'recipient' => $item->recipient,
                'recipient_email' => $item->recipient_email,
                'recipient_phone' => $item->recipient_phone,
                'subject' => $item->subject,
                'status' => $item->status,
                'attempts' => $item->attempts,
                'last_error' => $item->last_error,
                'scheduled_at' => $item->scheduled_at,
                'sent_at' => $item->sent_at,
                'created_at' => $item->created_at,
            ];
        }

        Response()->jsonSuccess('', [
            'items' => $formattedItems,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $totalCount,
                'totalPages' => $totalPages,
            ],
        ]);
    }

    #[NoReturn] public static function queueCancel(array $args): void {
        $uid = $args['uid'] ?? null;

        if (empty($uid)) {
            Response()->jsonError('Queue ID mangler');
        }

        $handler = Methods::notificationQueue();
        $item = $handler->get($uid);

        if (isEmpty($item)) {
            Response()->jsonError('Kø element ikke fundet');
        }

        if ($item->status === 'sent') {
            Response()->jsonError('Kan ikke annullere sendt notifikation');
        }

        $success = $handler->setCancelled($uid);

        if ($success) {
            Response()->jsonSuccess('Notifikation annulleret');
        } else {
            Response()->jsonError('Kunne ikke annullere notifikation');
        }
    }

    // =====================================================
    // LOGS API
    // =====================================================

    // =====================================================
    // TEST / DEBUG API
    // =====================================================

    /**
     * Test trigger a notification breakpoint with sample data
     * Useful for debugging the notification flow
     */
    #[NoReturn] public static function testTrigger(array $args): void {
        $breakpointKey = $args['breakpoint'] ?? null;
        $recipientEmail = $args['recipient_email'] ?? null;
        $recipientUid = $args['recipient_uid'] ?? __uuid();

        if (empty($breakpointKey)) {
            Response()->jsonError('Breakpoint key er påkrævet');
        }

        // Build test context with sample data
        $context = [
            'user' => [
                'uid' => $recipientUid,
                'full_name' => 'Test Bruger',
                'email' => $recipientEmail ?? 'test@example.com',
                'phone' => '+4512345678',
            ],
            'order' => [
                'uid' => 'ord_test123',
                'amount' => 29900,
                'formatted_amount' => '299,00 DKK',
                'currency' => 'DKK',
                'caption' => 'Test ordre',
                'status' => 'COMPLETED',
            ],
            'organisation' => [
                'uid' => 'org_test456',
                'name' => 'Test Organisation',
                'email' => 'org@example.com',
            ],
            'payment' => [
                'uid' => 'pay_test789',
                'amount' => 10000,
                'formatted_amount' => '100,00 DKK',
                'due_date' => date('Y-m-d', strtotime('+7 days')),
                'due_date_formatted' => date('d/m/Y', strtotime('+7 days')),
                'installment_number' => 1,
                'status' => 'SCHEDULED',
            ],
            'payment_plan' => [
                'total_installments' => 4,
                'remaining_installments' => 3,
                'first_amount_formatted' => '100,00 DKK',
                'installment_amount_formatted' => '66,33 DKK',
                'next_due_date' => date('d/m/Y', strtotime('+7 days')),
            ],
            'days_until_due' => 7,
            'days_overdue' => 0,
            'payment_link' => HOST . 'pay/ord_test123',
            'receipt_link' => HOST . 'receipt/ord_test123',
            'app' => [
                'name' => BRAND_NAME,
                'url' => HOST,
            ],
        ];

        // Override recipient email if provided
        if ($recipientEmail) {
            $context['recipient_email'] = $recipientEmail;
        }

        debugLog([
            'action' => 'testTrigger',
            'breakpoint' => $breakpointKey,
            'recipient_email' => $recipientEmail,
            'context_keys' => array_keys($context),
        ], 'NotificationApiController');

        // Trigger the notification
        $result = \classes\notifications\NotificationService::trigger($breakpointKey, $context);

        Response()->jsonSuccess('Test trigger udført', [
            'breakpoint' => $breakpointKey,
            'triggered' => $result,
            'check_logs' => 'Se logs/debug/ for detaljer',
        ]);
    }

    /**
     * Process the notification queue manually (for testing)
     */
    #[NoReturn] public static function processQueue(array $args): void {
        $limit = (int)($args['limit'] ?? 10);

        debugLog(['action' => 'processQueue', 'limit' => $limit], 'NotificationApiController');

        $results = \classes\notifications\NotificationService::processQueue($limit);

        Response()->jsonSuccess('Kø behandlet', $results);
    }

    /**
     * Process scheduled breakpoints manually (for testing)
     */
    #[NoReturn] public static function processScheduled(array $args): void {
        debugLog(['action' => 'processScheduled'], 'NotificationApiController');

        $results = \classes\notifications\NotificationService::processScheduledBreakpoints();

        Response()->jsonSuccess('Planlagte breakpoints behandlet', $results);
    }

    // =====================================================
    // LOGS API
    // =====================================================

    #[NoReturn] public static function logsList(array $args): void {
        $page = (int)($args['page'] ?? 1);
        $perPage = (int)($args['per_page'] ?? 25);
        $statusFilter = $args['status'] ?? '';
        $channelFilter = $args['channel'] ?? '';
        $recipientFilter = $args['recipient'] ?? '';
        $sortColumn = $args['sort_column'] ?? 'created_at';
        $sortDirection = strtoupper($args['sort_direction'] ?? 'DESC');

        $allowedSortColumns = ['created_at', 'channel', 'status'];
        if (!in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'created_at';
        }

        // Use handler for proper foreign key resolution
        $handler = Methods::notificationLogs();
        $query = $handler->queryBuilder();

        if (!empty($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        if (!empty($channelFilter)) {
            $query->where('channel', $channelFilter);
        }

        if (!empty($recipientFilter)) {
            $query->startGroup('OR')
                ->whereLike('recipient', $recipientFilter)
                ->whereLike('recipient_identifier', $recipientFilter)
                ->endGroup();
        }

        $totalCount = (clone $query)->count();
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $perPage;

        $query->order($sortColumn, $sortDirection)
            ->limit($perPage)
            ->offset($offset);

        // Use queryGetAll for proper foreign key resolution
        $logs = $handler->queryGetAll($query);

        $formattedLogs = [];
        foreach ($logs->list() as $log) {
            // Handle recipient - could be a User object (foreign key) or a string
            $recipientName = null;
            $recipientIdentifier = $log->recipient_identifier;

            if (is_object($log->recipient)) {
                // Foreign key resolved to User object
                $recipientName = $log->recipient->full_name ?? null;
                if (!$recipientIdentifier) {
                    $recipientIdentifier = $log->recipient->email ?? $log->recipient->phone ?? null;
                }
            }

            $formattedLogs[] = [
                'uid' => $log->uid,
                'channel' => $log->channel,
                'recipient_name' => $recipientName,
                'recipient_identifier' => $recipientIdentifier,
                'subject' => $log->subject,
                'status' => $log->status,
                'breakpoint_key' => $log->breakpoint_key,
                'created_at' => $log->created_at,
            ];
        }

        Response()->jsonSuccess('', [
            'logs' => $formattedLogs,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $totalCount,
                'totalPages' => $totalPages,
            ],
        ]);
    }

    /**
     * Resend a notification from logs
     */
    #[NoReturn] public static function logsResend(array $args): void {
        $uid = $args['uid'] ?? null;

        if (empty($uid)) {
            Response()->jsonError('Manglende log UID');
        }

        // Get the original log entry
        $handler = Methods::notificationLogs();
        $log = $handler->get($uid);

        if (!$log) {
            Response()->jsonError('Log ikke fundet');
        }

        // Get recipient info
        $recipientUid = null;
        $recipientEmail = null;
        $recipientPhone = null;

        if (is_object($log->recipient)) {
            $recipientUid = $log->recipient->uid;
            $recipientEmail = $log->recipient->email;
            $recipientPhone = $log->recipient->phone;
        } elseif (!empty($log->recipient)) {
            $recipientUid = $log->recipient;
            // Try to get user
            $user = Methods::users()->get($log->recipient);
            if ($user) {
                $recipientEmail = $user->email;
                $recipientPhone = $user->phone;
            }
        }

        // Use recipient_identifier as fallback
        if (empty($recipientEmail) && empty($recipientPhone) && !empty($log->recipient_identifier)) {
            if ($log->channel === 'email') {
                $recipientEmail = $log->recipient_identifier;
            } elseif ($log->channel === 'sms') {
                $recipientPhone = $log->recipient_identifier;
            }
        }

        $success = false;
        $errorMessage = null;

        try {
            switch ($log->channel) {
                case 'email':
                    if (empty($recipientEmail)) {
                        $errorMessage = 'Ingen e-mail adresse fundet';
                        break;
                    }
                    $success = \classes\notifications\MessageDispatcher::email(
                        $recipientEmail,
                        $log->subject ?? 'Ingen emne',
                        $log->content ?? '',
                        null // No HTML content stored separately
                    );
                    break;

                case 'sms':
                    if ($recipientUid) {
                        $success = \classes\notifications\MessageDispatcher::smsToUser(
                            $recipientUid,
                            $log->content ?? ''
                        );
                    } elseif (!empty($recipientPhone)) {
                        $success = \classes\notifications\MessageDispatcher::sms(
                            $recipientPhone,
                            $log->content ?? ''
                        );
                    } else {
                        $errorMessage = 'Ingen telefonnummer fundet';
                    }
                    break;

                case 'bell':
                    if (empty($recipientUid)) {
                        $errorMessage = 'Ingen bruger fundet for push-notifikation';
                        break;
                    }
                    $success = \classes\notifications\MessageDispatcher::bell(
                        $recipientUid,
                        $log->subject ?? 'Notifikation',
                        $log->content ?? ''
                    );
                    break;

                default:
                    $errorMessage = 'Ukendt kanal: ' . $log->channel;
            }
        } catch (\Exception $e) {
            $errorMessage = 'Fejl ved afsendelse: ' . $e->getMessage();
            debugLog(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 'NotificationApiController_logsResend');
        }

        if (!$success) {
            Response()->jsonError($errorMessage ?? 'Kunne ikke gensende notifikationen');
        }

        // Create a new log entry for the resent notification
        // Use correct identifier based on channel
        $newRecipientIdentifier = match($log->channel) {
            'sms' => $recipientPhone,
            'email' => $recipientEmail,
            'bell' => $recipientUid,
            default => $log->recipient_identifier,
        };

        $newLogData = [
            'flow' => is_object($log->flow) ? $log->flow->uid : $log->flow,
            'template' => is_object($log->template) ? $log->template->uid : $log->template,
            'breakpoint_key' => $log->breakpoint_key,
            'recipient' => $recipientUid,
            'recipient_identifier' => $newRecipientIdentifier,
            'channel' => $log->channel,
            'subject' => $log->subject,
            'content' => $log->content,
            'status' => 'sent',
            'reference_id' => $log->reference_id,
            'reference_type' => $log->reference_type,
        ];

        debugLog(['newLogData' => $newLogData], 'RESEND_NOTIF_CREATE_DATA');
        $created = $handler->create($newLogData);
        debugLog(['created' => $created, 'recentUid' => $handler->recentUid], 'RESEND_NOTIF_CREATE_RESULT');

        // Format the new log for response
        $formattedLog = null;
        if ($created) {
            $newLog = $handler->get($handler->recentUid);
            debugLog(['newLog' => $newLog, 'newLogType' => gettype($newLog)], 'RESEND_NOTIF_FETCHED');

            $recipientName = null;
            if (is_object($log->recipient)) {
                $recipientName = $log->recipient->full_name ?? null;
            }

            $formattedLog = [
                'uid' => $newLog->uid ?? null,
                'channel' => $newLog->channel ?? null,
                'recipient_name' => $recipientName,
                'recipient_identifier' => $newLog->recipient_identifier ?? null,
                'subject' => $newLog->subject ?? null,
                'status' => $newLog->status ?? null,
                'breakpoint_key' => $newLog->breakpoint_key ?? null,
                'created_at' => $newLog->created_at ?? null,
            ];
        }

        Response()->jsonSuccess('Notifikationen blev gensendt', [
            'log' => $formattedLog,
        ]);
    }

}
