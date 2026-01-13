<?php

namespace classes\notifications;

use classes\utility\Crud;
use Database\Collection;
use Database\model\NotificationBreakpoints;

class NotificationBreakpointHandler extends Crud {

    function __construct() {
        parent::__construct(NotificationBreakpoints::newStatic(), "notification_breakpoints");
    }

    public function getByKey(string $key): ?object {
        return $this->getFirst(['key' => $key]);
    }

    public function getActive(): Collection {
        return $this->getByX(['status' => 'active']);
    }

    public function getByCategory(string $category): Collection {
        return $this->getByX(['category' => $category, 'status' => 'active']);
    }

    public function getByTriggerType(string $triggerType): Collection {
        return $this->getByX(['trigger_type' => $triggerType, 'status' => 'active']);
    }

    public function getScheduledBreakpoints(): Collection {
        return $this->getByX(['trigger_type' => 'scheduled', 'status' => 'active']);
    }

    public function getInstantBreakpoints(): Collection {
        return $this->getByX(['trigger_type' => 'instant', 'status' => 'active']);
    }

    public function setActive(string $uid): bool {
        return $this->update(['status' => 'active'], ['uid' => $uid]);
    }

    public function setInactive(string $uid): bool {
        return $this->update(['status' => 'inactive'], ['uid' => $uid]);
    }
}
