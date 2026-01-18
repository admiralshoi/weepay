<?php

namespace classes\notifications;

use classes\utility\Crud;
use Database\Collection;
use Database\model\NotificationLogs;

class NotificationLogHandler extends Crud {

    function __construct() {
        parent::__construct(NotificationLogs::newStatic(), "notification_logs");
    }

    public function getByRecipient(string $recipientUid): Collection {
        return $this->getByXOrderBy('created_at', 'DESC', ['recipient' => $recipientUid]);
    }

    public function getByFlow(string $flowUid): Collection {
        return $this->getByXOrderBy('created_at', 'DESC', ['flow' => $flowUid]);
    }

    public function getByBreakpoint(string $breakpointKey): Collection {
        return $this->getByXOrderBy('created_at', 'DESC', ['breakpoint_key' => $breakpointKey]);
    }

    public function getByChannel(string $channel): Collection {
        return $this->getByXOrderBy('created_at', 'DESC', ['channel' => $channel]);
    }

    public function getByStatus(string $status): Collection {
        return $this->getByXOrderBy('created_at', 'DESC', ['status' => $status]);
    }

    public function countByChannel(string $channel, ?int $sinceTimestamp = null): int {
        $params = ['channel' => $channel];
        if ($sinceTimestamp) {
            $query = $this->queryBuilder()
                ->where('channel', $channel)
                ->where('created_at', '>=', $sinceTimestamp);
            return $query->count();
        }
        return $this->count($params);
    }

    public function countByStatus(string $status, ?int $sinceTimestamp = null): int {
        $params = ['status' => $status];
        if ($sinceTimestamp) {
            $query = $this->queryBuilder()
                ->where('status', $status)
                ->where('created_at', '>=', $sinceTimestamp);
            return $query->count();
        }
        return $this->count($params);
    }

    /**
     * Generate dedup hash from notification parameters
     */
    public static function generateDedupHash(
        ?string $flowUid,
        ?string $recipientUid,
        string $channel,
        ?string $referenceId,
        ?string $referenceType
    ): string {
        return md5("{$flowUid}:{$recipientUid}:{$channel}:{$referenceId}:{$referenceType}");
    }

    /**
     * Check if a notification has already been sent successfully
     */
    public function alreadySent(
        ?string $flowUid,
        ?string $recipientUid,
        string $channel,
        ?string $referenceId,
        ?string $referenceType
    ): bool {
        $hash = self::generateDedupHash($flowUid, $recipientUid, $channel, $referenceId, $referenceType);
        return $this->exists(['dedup_hash' => $hash, 'status' => 'sent']);
    }

    /**
     * Get existing log entry by dedup hash
     */
    public function getByDedupHash(string $hash): ?object {
        return $this->getFirst(['dedup_hash' => $hash]);
    }

    public function insert(
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
        ?int $scheduleOffset = null,
        ?array $metadata = null
    ): bool {
        $dedupHash = self::generateDedupHash($flowUid, $recipientUid, $channel, $referenceId, $referenceType);

        return $this->create([
            'flow' => $flowUid,
            'template' => $templateUid,
            'breakpoint_key' => $breakpointKey,
            'recipient' => $recipientUid,
            'recipient_identifier' => $recipientIdentifier,
            'channel' => $channel,
            'subject' => $subject,
            'content' => $content,
            'status' => $status,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
            'schedule_offset' => $scheduleOffset,
            'metadata' => $metadata,
            'dedup_hash' => $dedupHash,
        ]);
    }
}
