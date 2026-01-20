<?php

namespace classes\notifications;

use classes\utility\Crud;
use Database\Collection;
use Database\model\UserNotifications;

class UserNotificationHandler extends Crud {

    function __construct() {
        parent::__construct(UserNotifications::newStatic(), "user_notifications");
    }

    public function getByUser(string $userUid, int $limit = 50): Collection {
        $query = $this->queryBuilder()
            ->where('user', $userUid)
            ->order('created_at', 'DESC')
            ->limit($limit);

        return $this->queryGetAll($query);
    }

    public function getUnreadByUser(string $userUid): Collection {
        return $this->getByXOrderBy('created_at', 'DESC', ['user' => $userUid, 'is_read' => 0]);
    }

    public function countUnread(string $userUid): int {
        return $this->count(['user' => $userUid, 'is_read' => 0]);
    }

    public function markAsRead(string $uid): bool {
        return $this->update([
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s')
        ], ['uid' => $uid]);
    }

    public function markAllAsRead(string $userUid): bool {
        return $this->update([
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s')
        ], ['user' => $userUid, 'is_read' => 0]);
    }

    public function getByReference(string $referenceType, string $referenceId): Collection {
        return $this->getByX(['reference_type' => $referenceType, 'reference_id' => $referenceId]);
    }

    public function insert(
        string $userUid,
        string $title,
        string $content,
        string $type = 'info',
        ?string $icon = null,
        ?string $link = null,
        ?string $referenceType = null,
        ?string $referenceId = null
    ): bool {
        return $this->create([
            'user' => $userUid,
            'title' => $title,
            'content' => $content,
            'type' => $type,
            'icon' => $icon,
            'link' => $link,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'is_read' => 0,
        ]);
    }

    public function deleteOld(int $daysOld = 90, bool $onlyRead = true): int {
        $cutoff = time() - ($daysOld * 24 * 60 * 60);
        $query = $this->queryBuilder()
            ->where('created_at', '<', $cutoff);

        if ($onlyRead) {
            $query->where('is_read', 1);
        }

        $count = $query->count();
        $query->delete();

        return $count;
    }

    /**
     * Get paginated notifications for a user
     * Unread notifications appear first, then ordered by created_at DESC
     */
    public function getPaginated(string $userUid, int $perPage = 15, ?string $cursor = null): array {
        $query = $this->queryBuilder()
            ->where('user', $userUid)
            ->order('is_read', 'ASC')  // Unread (0) first
            ->order('created_at', 'DESC');

        // Get total count and unread count for first page
        $meta = [];
        if (empty($cursor)) {
            $countQuery = $this->queryBuilder()->where('user', $userUid);
            $meta['total_count'] = $countQuery->count();
            $meta['unread_count'] = $this->countUnread($userUid);
        }

        // Apply cursor for pagination
        if (!empty($cursor)) {
            $query->setPaginationCursor($cursor);
        }

        $items = $query->paginate($perPage);

        return [
            'items' => $items,
            'meta' => $meta,
        ];
    }
}
