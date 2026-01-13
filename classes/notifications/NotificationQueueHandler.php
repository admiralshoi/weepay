<?php

namespace classes\notifications;

use classes\utility\Crud;
use Database\Collection;
use Database\model\NotificationQueue;

class NotificationQueueHandler extends Crud {

    function __construct() {
        parent::__construct(NotificationQueue::newStatic(), "notification_queue");
    }

    public function getPending(int $limit = 100): Collection {
        $query = $this->queryBuilder()
            ->where('status', 'pending')
            ->where('scheduled_at', '<=', time())
            ->order('scheduled_at', 'ASC')
            ->limit($limit);

        return $this->queryGetAll($query);
    }

    public function getByRecipient(string $recipientUid): Collection {
        return $this->getByXOrderBy('created_at', 'DESC', ['recipient' => $recipientUid]);
    }

    public function getByStatus(string $status): Collection {
        return $this->getByXOrderBy('scheduled_at', 'ASC', ['status' => $status]);
    }

    public function getFailed(int $maxAttempts = 3): Collection {
        $query = $this->queryBuilder()
            ->where('status', 'failed')
            ->where('attempts', '<', $maxAttempts)
            ->order('scheduled_at', 'ASC');

        return $this->queryGetAll($query);
    }

    public function setProcessing(string $uid): bool {
        return $this->update(['status' => 'processing'], ['uid' => $uid]);
    }

    public function setSent(string $uid): bool {
        return $this->update([
            'status' => 'sent',
            'sent_at' => time()
        ], ['uid' => $uid]);
    }

    public function setFailed(string $uid, string $error): bool {
        $item = $this->excludeForeignKeys()->get($uid);
        $attempts = $item ? ($item->attempts + 1) : 1;

        return $this->update([
            'status' => 'failed',
            'attempts' => $attempts,
            'last_error' => $error
        ], ['uid' => $uid]);
    }

    public function setCancelled(string $uid): bool {
        return $this->update(['status' => 'cancelled'], ['uid' => $uid]);
    }

    public function insert(
        string $channel,
        string $content,
        int $scheduledAt,
        ?string $flowActionUid = null,
        ?string $recipientUid = null,
        ?string $recipientEmail = null,
        ?string $recipientPhone = null,
        ?string $subject = null,
        ?array $contextData = null
    ): bool {
        return $this->create([
            'flow_action' => $flowActionUid,
            'recipient' => $recipientUid,
            'recipient_email' => $recipientEmail,
            'recipient_phone' => $recipientPhone,
            'channel' => $channel,
            'subject' => $subject,
            'content' => $content,
            'context_data' => $contextData,
            'status' => 'pending',
            'scheduled_at' => $scheduledAt,
            'attempts' => 0,
        ]);
    }

    public function cleanupOld(int $daysOld = 30): int {
        $cutoff = time() - ($daysOld * 24 * 60 * 60);
        $query = $this->queryBuilder()
            ->where('status', 'IN', ['sent', 'cancelled'])
            ->where('created_at', '<', $cutoff);

        $count = $query->count();
        $query->delete();

        return $count;
    }
}
