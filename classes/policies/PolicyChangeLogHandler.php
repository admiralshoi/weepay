<?php

namespace classes\policies;

use classes\Methods;
use classes\utility\Crud;
use Database\Collection;
use Database\model\PolicyChangeLogs;

class PolicyChangeLogHandler extends Crud {

    public function __construct() {
        parent::__construct(PolicyChangeLogs::newStatic(), 'policy_change_logs');
    }

    /**
     * Log a change to a policy version
     * Returns the changelog UID on success, null on failure
     */
    public function logChange(string $versionUid, string $typeUid, string $changeType, ?string $changedBy): ?string {
        // Get the version to create a snapshot
        $version = Methods::policyVersions()->excludeForeignKeys()->get($versionUid);
        if (isEmpty($version)) {
            return null;
        }

        $data = [
            'policy_version' => $versionUid,
            'policy_type' => $typeUid,
            'change_type' => $changeType,
            'changed_by' => $changedBy,
            'title_snapshot' => $version->title,
            'content_snapshot' => $version->content,
            'version_snapshot' => $version->version,
        ];

        if (!$this->create($data)) {
            return null;
        }

        return $this->recentUid;
    }

    /**
     * Get all change logs for a specific version
     */
    public function getByVersion(string $versionUid): Collection {
        return $this->getByXOrderBy('id', 'DESC', ['policy_version' => $versionUid]);
    }

    /**
     * Get all change logs for a specific policy type
     */
    public function getByType(string $typeUid): Collection {
        return $this->getByXOrderBy('id', 'DESC', ['policy_type' => $typeUid]);
    }

    /**
     * Delete all change logs for a version (used when deleting drafts)
     */
    public function deleteByVersion(string $versionUid): bool {
        return $this->delete(['policy_version' => $versionUid]);
    }

    /**
     * Get history summary for a version (without full content)
     */
    public function getHistorySummary(string $versionUid): array {
        $logs = $this->getByVersion($versionUid);
        $summary = [];

        foreach ($logs->list() as $log) {
            $summary[] = [
                'uid' => $log->uid,
                'change_type' => $log->change_type,
                'changed_by' => $log->changed_by,
                'title_snapshot' => $log->title_snapshot,
                'version_snapshot' => $log->version_snapshot,
                'created_at' => $log->created_at ?? null,
            ];
        }

        return $summary;
    }

    /**
     * Get full history for a policy type (for admin view)
     */
    public function getTypeHistory(string $typeUid, int $limit = 50): array {
        $query = $this->queryBuilder()
            ->where('policy_type', $typeUid)
            ->order('id', 'DESC')
            ->limit($limit);

        $logs = $this->queryGetAll($query);
        $history = [];

        foreach ($logs->list() as $log) {
            $changedByName = null;
            if (!isEmpty($log->changed_by)) {
                $changedByObj = is_object($log->changed_by) ? $log->changed_by : Methods::users()->get($log->changed_by);
                $changedByName = !isEmpty($changedByObj) ? ($changedByObj->full_name ?? null) : null;
            }

            $history[] = [
                'uid' => $log->uid,
                'policy_version' => is_object($log->policy_version) ? $log->policy_version->uid : $log->policy_version,
                'change_type' => $log->change_type,
                'changed_by_uid' => is_object($log->changed_by) ? $log->changed_by->uid : $log->changed_by,
                'changed_by_name' => $changedByName,
                'title_snapshot' => $log->title_snapshot,
                'version_snapshot' => $log->version_snapshot,
                'created_at' => $log->created_at ?? null,
            ];
        }

        return $history;
    }
}
