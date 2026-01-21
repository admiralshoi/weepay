<?php

namespace classes\policies;

use classes\Methods;
use classes\utility\Crud;
use Database\Collection;
use Database\model\PolicyVersions;

class PolicyVersionHandler extends Crud {

    public function __construct() {
        parent::__construct(PolicyVersions::newStatic(), 'policy_versions');
    }

    /**
     * Get all versions for a policy type
     */
    public function getAllByType(string $typeUid): Collection {
        return $this->getByXOrderBy('version', 'DESC', ['policy_type' => $typeUid]);
    }

    /**
     * Get draft version for a policy type (there should only be one)
     */
    public function getDraftByType(string $typeUid): ?object {
        return $this->getFirst(['policy_type' => $typeUid, 'status' => 'draft']);
    }

    /**
     * Get the next version number for a policy type
     */
    public function getNextVersionNumber(string $typeUid): int {
        $query = $this->queryBuilder()
            ->where('policy_type', $typeUid)
            ->order('version', 'DESC')
            ->limit(1);

        $latest = $this->queryGetFirst($query);
        return $latest ? $latest->version + 1 : 1;
    }

    /**
     * Create a new draft version
     */
    public function createDraft(string $typeUid, string $title, string $content, string $createdBy): ?string {
        // Check if draft already exists for this type
        $existingDraft = $this->getDraftByType($typeUid);
        if (!isEmpty($existingDraft)) {
            return null; // Draft already exists
        }

        $version = $this->getNextVersionNumber($typeUid);

        $data = [
            'policy_type' => $typeUid,
            'version' => $version,
            'title' => $title,
            'content' => $content,
            'status' => 'draft',
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
        ];

        if (!$this->create($data)) {
            return null;
        }

        // Log the creation
        Methods::policyChangeLogs()->logChange($this->recentUid, $typeUid, 'created', $createdBy);

        return $this->recentUid;
    }

    /**
     * Update an existing draft or scheduled version
     * If scheduled, reverts to draft and clears schedule
     */
    public function updateDraft(string $uid, array $data, string $updatedBy): bool {
        $version = $this->excludeForeignKeys()->get($uid);
        if (isEmpty($version)) {
            return false;
        }

        // Only allow editing drafts or scheduled versions (which are still drafts)
        if ($version->status !== 'draft') {
            return false;
        }

        $typeUid = $version->policy_type;
        $type = str_replace('pt_', '', $typeUid);

        // Check if this version is scheduled - if so, unschedule it
        $policyType = Methods::policyTypes()->excludeForeignKeys()->get($typeUid);
        if (!isEmpty($policyType) && $policyType->scheduled_version === $uid) {
            Methods::policyTypes()->clearScheduledVersion($type);
            Methods::policyChangeLogs()->logChange($uid, $typeUid, 'unscheduled', $updatedBy);
        }

        $data['updated_by'] = $updatedBy;

        $updated = $this->update($data, ['uid' => $uid]);

        if ($updated) {
            Methods::policyChangeLogs()->logChange($uid, $typeUid, 'updated', $updatedBy);
        }

        return $updated;
    }

    /**
     * Publish a version immediately
     * Archives the current version and swaps the pointer
     * Clears any scheduled version for this type
     */
    public function publishImmediate(
        string $uid,
        string $publishedBy,
        bool $notify = false,
        array $recipientTypes = []
    ): bool {
        $version = $this->excludeForeignKeys()->get($uid);
        if (isEmpty($version) || $version->status !== 'draft') {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $typeUid = $version->policy_type;

        // Get the type string from uid (e.g., "pt_consumer_privacy" -> "consumer_privacy")
        $type = str_replace('pt_', '', $typeUid);

        // Get current published version via pointer
        $policyType = Methods::policyTypes()->excludeForeignKeys()->get($typeUid);
        $currentVersionUid = $policyType->current_version ?? null;

        // Clear scheduled version if exists (we're publishing now instead)
        if (!isEmpty($policyType->scheduled_version)) {
            Methods::policyTypes()->clearScheduledVersion($type);
        }

        // Archive current version if exists
        if (!isEmpty($currentVersionUid)) {
            $this->update([
                'status' => 'archived',
                'active_until' => $now
            ], ['uid' => $currentVersionUid]);

            Methods::policyChangeLogs()->logChange($currentVersionUid, $typeUid, 'archived', $publishedBy);
        }

        // Update the new version to published
        $updated = $this->update([
            'status' => 'published',
            'published_at' => $now,
            'published_by' => $publishedBy,
            'active_from' => $now
        ], ['uid' => $uid]);

        if ($updated) {
            // Swap the pointer
            Methods::policyTypes()->setCurrentVersion($type, $uid);

            $changelogUid = Methods::policyChangeLogs()->logChange($uid, $typeUid, 'published', $publishedBy);

            // Send notifications if requested
            if ($notify && !empty($recipientTypes) && $changelogUid) {
                $freshVersion = $this->get($uid);
                \classes\notifications\NotificationTriggers::policyUpdatedBatch(
                    $freshVersion,
                    $recipientTypes,
                    $changelogUid,
                    $now // effective date is now for immediate publish
                );
            }
        }

        return $updated;
    }

    /**
     * Schedule a version for future publication
     * Sends notifications immediately with the scheduled date
     */
    public function schedulePublish(
        string $uid,
        string $scheduledAt,
        string $scheduledBy,
        bool $notify = false,
        array $recipientTypes = []
    ): bool {
        $version = $this->excludeForeignKeys()->get($uid);
        if (isEmpty($version) || $version->status !== 'draft') {
            return false;
        }

        $typeUid = $version->policy_type;
        $type = str_replace('pt_', '', $typeUid);

        // Update version with scheduled info
        $updated = $this->update([
            'published_at' => date('Y-m-d H:i:s'),
            'published_by' => $scheduledBy,
        ], ['uid' => $uid]);

        if ($updated) {
            // Set the scheduled pointer
            Methods::policyTypes()->scheduleVersion($type, $uid, $scheduledAt);

            $changelogUid = Methods::policyChangeLogs()->logChange($uid, $typeUid, 'scheduled', $scheduledBy);

            // Send notifications if requested (with scheduled date as effective date)
            if ($notify && !empty($recipientTypes) && $changelogUid) {
                $freshVersion = $this->get($uid);
                \classes\notifications\NotificationTriggers::policyUpdatedBatch(
                    $freshVersion,
                    $recipientTypes,
                    $changelogUid,
                    $scheduledAt // effective date is the scheduled date
                );
            }
        }

        return $updated;
    }

    /**
     * Delete a draft version (only drafts can be deleted)
     */
    public function deleteDraft(string $uid): bool {
        $version = $this->excludeForeignKeys()->get($uid);
        if (isEmpty($version) || $version->status !== 'draft') {
            return false;
        }

        // Delete audit trail for this version
        Methods::policyChangeLogs()->deleteByVersion($uid);

        // Clear scheduled pointer if this was scheduled
        $typeUid = $version->policy_type;
        $type = str_replace('pt_', '', $typeUid);
        $policyType = Methods::policyTypes()->excludeForeignKeys()->get($typeUid);
        if ($policyType->scheduled_version === $uid) {
            Methods::policyTypes()->clearScheduledVersion($type);
        }

        return $this->delete(['uid' => $uid]);
    }

    /**
     * Get or create a draft for editing
     * Returns scheduled version if exists (editable), otherwise draft or creates new
     */
    public function getOrCreateDraft(string $type, string $createdBy): ?object {
        $typeUid = Methods::policyTypes()->getUidForType($type);
        $policyType = Methods::policyTypes()->excludeForeignKeys()->get($typeUid);

        // Check if there's a scheduled version - return it for editing
        if (!isEmpty($policyType) && !isEmpty($policyType->scheduled_version)) {
            return $this->get($policyType->scheduled_version);
        }

        // Check if draft already exists
        $existingDraft = $this->getDraftByType($typeUid);
        if (!isEmpty($existingDraft)) {
            return $existingDraft;
        }

        // Get current published to copy from (via pointer)
        $current = Methods::policyTypes()->getCurrentVersion($type);

        // If no pointer exists, check for any published version in the database (e.g., from seed data)
        if (isEmpty($current)) {
            $current = $this->getFirstOrderBy('version', 'DESC', [
                'policy_type' => $typeUid,
                'status' => 'published'
            ]);
        }

        if ($current) {
            // Create draft from current published
            $draftUid = $this->createDraft(
                $typeUid,
                $current->title,
                $current->content,
                $createdBy
            );
        } else {
            // Create empty draft
            $draftUid = $this->createDraft(
                $typeUid,
                Methods::policyTypes()->getDisplayName($type),
                '',
                $createdBy
            );
        }

        return $draftUid ? $this->get($draftUid) : null;
    }

    /**
     * Get archived versions for a type
     */
    public function getArchivedByType(string $typeUid): Collection {
        return $this->getByXOrderBy('active_until', 'DESC', [
            'policy_type' => $typeUid,
            'status' => 'archived'
        ]);
    }

    /**
     * Get version by UID
     */
    public function getVersion(string $uid): ?object {
        return $this->get($uid);
    }

    /**
     * Get a specific version by type and version number
     * Used for viewing a specific version via URL (e.g., /policies/consumer/privacy-policy/2)
     */
    public function getByTypeAndVersion(string $type, int $versionNumber): ?object {
        $typeUid = Methods::policyTypes()->getUidForType($type);
        if (isEmpty($typeUid)) {
            return null;
        }

        return $this->getFirst([
            'policy_type' => $typeUid,
            'version' => $versionNumber
        ]);
    }

    /**
     * Replace content placeholders with actual values
     */
    public function renderContent(string $content): string {
        $replacements = [
            '{{BRAND_NAME}}' => BRAND_NAME,
            '{{COMPANY_NAME}}' => COMPANY_NAME,
            '{{COMPANY_CVR}}' => COMPANY_CVR,
            '{{COMPANY_ADDRESS_STRING}}' => COMPANY_ADDRESS_STRING,
            '{{CONTACT_EMAIL}}' => CONTACT_EMAIL,
            '{{CONTACT_PHONE}}' => CONTACT_PHONE,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
}
