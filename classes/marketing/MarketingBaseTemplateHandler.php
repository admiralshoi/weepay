<?php

namespace classes\marketing;

use classes\utility\Crud;
use Database\Collection;
use Database\model\MarketingBaseTemplates;

class MarketingBaseTemplateHandler extends Crud {

    public function __construct() {
        parent::__construct(MarketingBaseTemplates::newStatic(), 'marketing_base_templates');
    }

    /**
     * Get all base templates ordered by name
     */
    public function getAll(): Collection {
        return $this->getByXOrderBy('name', 'ASC', []);
    }

    /**
     * Get base templates filtered by type
     */
    public function getByType(string $type): Collection {
        return $this->getByXOrderBy('name', 'ASC', ['type' => $type]);
    }

    /**
     * Create a new base template
     */
    public function createBaseTemplate(
        string $name,
        string $filePath,
        string $type = 'A4',
        ?string $description = null,
        ?string $previewImage = null
    ): ?string {
        $created = $this->create([
            'name' => $name,
            'file_path' => $filePath,
            'type' => $type,
            'description' => $description,
            'preview_image' => $previewImage,
            'created_by' => __uuid(),
        ]);
        return $created ? $this->recentUid : null;
    }

    /**
     * Update base template details
     */
    public function updateBaseTemplate(string $uid, array $data): bool {
        $allowedFields = ['name', 'type', 'description'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return false;
        }

        return $this->update($updateData, ['uid' => $uid]);
    }

    /**
     * Delete base template and associated files
     * Only allowed if no versions reference this base
     */
    public function deleteBaseTemplate(string $uid): bool|string {
        $baseTemplate = $this->excludeForeignKeys()->get($uid);
        if (isEmpty($baseTemplate)) {
            return "Base template not found";
        }

        // Check if any versions reference this base
        $versionCount = \classes\Methods::marketingTemplates()->count(['base_template' => $uid]);
        if ($versionCount > 0) {
            return "Cannot delete: {$versionCount} version(s) use this base template";
        }

        // Delete files
        if (!isEmpty($baseTemplate->file_path) && file_exists(ROOT . $baseTemplate->file_path)) {
            unlink(ROOT . $baseTemplate->file_path);
        }
        if (!isEmpty($baseTemplate->preview_image) && file_exists(ROOT . $baseTemplate->preview_image)) {
            unlink(ROOT . $baseTemplate->preview_image);
        }

        return $this->delete(['uid' => $uid]) ? true : "Failed to delete base template";
    }

    /**
     * Get count of versions created from a base template
     */
    public function getVersionCount(string $uid): int {
        return \classes\Methods::marketingTemplates()->count(['base_template' => $uid]);
    }

    /**
     * Get template types as options array
     */
    public function getTypeOptions(): array {
        return [
            'A4' => 'A4',
            'A3' => 'A3',
            'A5' => 'A5',
            'roll-up' => 'Roll-up',
            'poster' => 'Plakat',
            'flyer' => 'Flyer',
            'sticker' => 'Sticker',
        ];
    }
}
