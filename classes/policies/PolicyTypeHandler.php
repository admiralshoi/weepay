<?php

namespace classes\policies;

use classes\Methods;
use classes\utility\Crud;
use Database\Collection;
use Database\model\PolicyTypes;

class PolicyTypeHandler extends Crud {

    private const TYPE_VALUES = ['consumer_privacy', 'consumer_terms', 'merchant_privacy', 'merchant_terms', 'cookies'];

    private const DISPLAY_NAMES = [
        'consumer_privacy' => 'Privatlivspolitik (Forbruger)',
        'consumer_terms' => 'Handelsbetingelser (Forbruger)',
        'merchant_privacy' => 'Privatlivspolitik (Erhverv)',
        'merchant_terms' => 'Handelsbetingelser (Erhverv)',
        'cookies' => 'Cookiepolitik',
    ];

    public function __construct() {
        parent::__construct(PolicyTypes::newStatic(), 'policy_types');
    }

    public function getTypeValues(): array {
        return self::TYPE_VALUES;
    }

    public function getDisplayName(string $type): string {
        return self::DISPLAY_NAMES[$type] ?? $type;
    }

    public function getUidForType(string $type): string {
        return 'pt_' . $type;
    }

    public function getByType(string $type): ?object {
        return $this->get($this->getUidForType($type));
    }

    public function getCurrentVersion(string $type): ?object {
        $policyType = $this->getByType($type);
        if (isEmpty($policyType) || isEmpty($policyType->current_version)) {
            return null;
        }

        $versionUid = is_object($policyType->current_version)
            ? $policyType->current_version->uid
            : $policyType->current_version;

        return Methods::policyVersions()->get($versionUid);
    }

    public function getPreviousVersion(string $type): ?object {
        $typeUid = $this->getUidForType($type);
        return Methods::policyVersions()->getFirstOrderBy('active_until', 'DESC', [
            'policy_type' => $typeUid,
            'status' => 'archived'
        ]);
    }

    public function setCurrentVersion(string $type, string $versionUid): bool {
        return $this->update(['current_version' => $versionUid], ['uid' => $this->getUidForType($type)]);
    }

    public function scheduleVersion(string $type, string $versionUid, string $scheduledAt): bool {
        return $this->update([
            'scheduled_version' => $versionUid,
            'scheduled_at' => $scheduledAt
        ], ['uid' => $this->getUidForType($type)]);
    }

    public function clearScheduledVersion(string $type): bool {
        return $this->update([
            'scheduled_version' => null,
            'scheduled_at' => null
        ], ['uid' => $this->getUidForType($type)]);
    }

    public function getAllWithStatus(): array {
        $result = [];

        foreach (self::TYPE_VALUES as $type) {
            $typeUid = $this->getUidForType($type);
            $policyType = $this->excludeForeignKeys()->get($typeUid);

            $currentVersion = null;
            $scheduledVersion = null;
            $draft = null;

            if (!isEmpty($policyType)) {
                if (!isEmpty($policyType->current_version)) {
                    $currentVersion = Methods::policyVersions()->get($policyType->current_version);
                }
                if (!isEmpty($policyType->scheduled_version)) {
                    $scheduledVersion = Methods::policyVersions()->get($policyType->scheduled_version);
                }
                $draft = Methods::policyVersions()->getDraftByType($typeUid);
            }

            $result[$type] = [
                'type' => $type,
                'type_uid' => $typeUid,
                'display_name' => $this->getDisplayName($type),
                'current_version' => $currentVersion ? [
                    'uid' => $currentVersion->uid,
                    'version' => $currentVersion->version,
                    'title' => $currentVersion->title,
                    'published_at' => $currentVersion->published_at,
                    'active_from' => $currentVersion->active_from,
                ] : null,
                'scheduled_version' => $scheduledVersion ? [
                    'uid' => $scheduledVersion->uid,
                    'version' => $scheduledVersion->version,
                    'title' => $scheduledVersion->title,
                ] : null,
                'scheduled_at' => $policyType->scheduled_at ?? null,
                'has_draft' => !isEmpty($draft),
                'draft_uid' => $draft ? $draft->uid : null,
            ];
        }

        return $result;
    }

    public function getReadyToPublish(): Collection {
        $now = date('Y-m-d H:i:s');
        $query = $this->queryBuilder()
            ->whereNotNull('scheduled_version')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $now);

        return $this->queryGetAll($query);
    }

    public function executeScheduledPublish(object $policyType): bool {
        $now = date('Y-m-d H:i:s');

        $currentVersionUid = is_object($policyType->current_version)
            ? $policyType->current_version->uid
            : $policyType->current_version;
        $scheduledVersionUid = is_object($policyType->scheduled_version)
            ? $policyType->scheduled_version->uid
            : $policyType->scheduled_version;
        $policyTypeUid = $policyType->uid;

        if (!isEmpty($currentVersionUid)) {
            Methods::policyVersions()->update([
                'status' => 'archived',
                'active_until' => $now
            ], ['uid' => $currentVersionUid]);

            Methods::policyChangeLogs()->logChange($currentVersionUid, $policyTypeUid, 'archived', null);
        }

        Methods::policyVersions()->update([
            'status' => 'published',
            'active_from' => $now
        ], ['uid' => $scheduledVersionUid]);

        Methods::policyChangeLogs()->logChange($scheduledVersionUid, $policyTypeUid, 'published', null);

        return $this->update([
            'current_version' => $scheduledVersionUid,
            'scheduled_version' => null,
            'scheduled_at' => null
        ], ['uid' => $policyTypeUid]);
    }
}
