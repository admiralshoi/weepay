<?php

namespace classes\notifications;

use classes\utility\Crud;
use Database\Collection;
use Database\model\NotificationFlows;

class NotificationFlowHandler extends Crud {

    function __construct() {
        parent::__construct(NotificationFlows::newStatic(), "notification_flows");
    }

    public function getActive(): Collection {
        $now = time();
        $query = $this->queryBuilder()
            ->where('status', 'active')
            ->startGroup('OR')
                ->whereNull('starts_at')
                ->where('starts_at', '<=', $now)
            ->endGroup()
            ->startGroup('OR')
                ->whereNull('ends_at')
                ->where('ends_at', '>=', $now)
            ->endGroup()
            ->order('priority', 'ASC');

        return $this->queryGetAll($query);
    }

    public function getByBreakpoint(string $breakpointUid): Collection {
        return $this->getByXOrderBy('priority', 'ASC', ['breakpoint' => $breakpointUid, 'status' => 'active']);
    }

    /**
     * Get active flows for a breakpoint by key or UID
     * Supports both breakpoint key (e.g., 'order.completed') and breakpoint UID
     */
    public function getActiveByBreakpoint(string $breakpointKeyOrUid): Collection {
        $now = time();

        debugLog([
            'method' => 'getActiveByBreakpoint',
            'input' => $breakpointKeyOrUid,
            'now' => $now
        ], 'NotificationFlowHandler');

        // First, check if this is a breakpoint key or UID
        // If it doesn't start with 'nbp_', it's likely a key, so we need to find the breakpoint first
        $breakpointUid = $breakpointKeyOrUid;
        if (!str_starts_with($breakpointKeyOrUid, 'nbp_')) {
            // It's a key, look up the breakpoint
            $breakpoint = \classes\Methods::notificationBreakpoints()->getByKey($breakpointKeyOrUid);
            if ($breakpoint) {
                $breakpointUid = $breakpoint->uid;
                debugLog([
                    'resolved_key_to_uid' => true,
                    'key' => $breakpointKeyOrUid,
                    'uid' => $breakpointUid
                ], 'NotificationFlowHandler');
            } else {
                debugLog([
                    'error' => 'Breakpoint not found by key',
                    'key' => $breakpointKeyOrUid
                ], 'NotificationFlowHandler');
                return new Collection([]);
            }
        }

        $query = $this->queryBuilder()
            ->where('breakpoint', $breakpointUid)
            ->where('status', 'active')
            ->startGroup('OR')
                ->whereNull('starts_at')
                ->where('starts_at', '<=', $now)
            ->endGroup()
            ->startGroup('OR')
                ->whereNull('ends_at')
                ->where('ends_at', '>=', $now)
            ->endGroup()
            ->order('priority', 'ASC');

        $result = $this->queryGetAll($query);

        debugLog([
            'breakpoint_uid' => $breakpointUid,
            'flows_found' => $result->count()
        ], 'NotificationFlowHandler');

        return $result;
    }

    public function setActive(string $uid): bool {
        return $this->update(['status' => 'active'], ['uid' => $uid]);
    }

    public function setInactive(string $uid): bool {
        return $this->update(['status' => 'inactive'], ['uid' => $uid]);
    }

    public function insert(
        string $name,
        string $breakpointUid,
        ?string $description = null,
        string $status = 'draft',
        int $priority = 100,
        ?int $startsAt = null,
        ?int $endsAt = null,
        ?array $conditions = null,
        ?string $createdBy = null,
        int $scheduleOffsetDays = 0,
        string $recipientType = 'user',
        ?string $recipientEmail = null
    ): bool {
        return $this->create([
            'name' => $name,
            'breakpoint' => $breakpointUid,
            'description' => $description,
            'status' => $status,
            'priority' => $priority,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'conditions' => $conditions,
            'created_by' => $createdBy ?? __uuid(),
            'schedule_offset_days' => $scheduleOffsetDays,
            'recipient_type' => $recipientType,
            'recipient_email' => $recipientEmail,
        ]);
    }
}
