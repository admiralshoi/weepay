<?php

namespace classes\notifications;

use classes\utility\Crud;
use Database\Collection;
use Database\model\NotificationFlowActions;

class NotificationFlowActionHandler extends Crud {

    function __construct() {
        parent::__construct(NotificationFlowActions::newStatic(), "notification_flow_actions");
    }

    public function getByFlow(string $flowUid): Collection {
        return $this->getByX(['flow' => $flowUid, 'status' => 'active']);
    }

    public function getByTemplate(string $templateUid): Collection {
        return $this->getByX(['template' => $templateUid]);
    }

    public function getByChannel(string $channel): Collection {
        return $this->getByX(['channel' => $channel, 'status' => 'active']);
    }

    public function setActive(string $uid): bool {
        return $this->update(['status' => 'active'], ['uid' => $uid]);
    }

    public function setInactive(string $uid): bool {
        return $this->update(['status' => 'inactive'], ['uid' => $uid]);
    }

    public function insert(
        string $flowUid,
        string $templateUid,
        string $channel,
        int $delayMinutes = 0,
        string $status = 'active'
    ): bool {
        return $this->create([
            'flow' => $flowUid,
            'template' => $templateUid,
            'channel' => $channel,
            'delay_minutes' => $delayMinutes,
            'status' => $status,
        ]);
    }
}
