<?php

namespace classes\marketing;

use classes\utility\Crud;
use Database\Collection;
use Database\model\MarketingTemplates;

class MarketingTemplateHandler extends Crud {

    public function __construct() {
        parent::__construct(MarketingTemplates::newStatic(), 'marketing_templates');
    }

    /**
     * Get all active templates, optionally filtered by type
     */
    public function getActive(?string $type = null): Collection {
        $params = ['status' => 'ACTIVE'];
        if ($type) {
            $params['type'] = $type;
        }
        return $this->getByXOrderBy('name', 'ASC', $params);
    }

    /**
     * Get all templates ordered by name
     */
    public function getAll(): Collection {
        return $this->getByXOrderBy('name', 'ASC', []);
    }

    /**
     * Create a new marketing template
     */
    public function createTemplate(
        string $name,
        string $filePath,
        string $type = 'A4',
        ?string $description = null,
        ?string $previewImage = null,
        string $status = 'DRAFT'
    ): ?string {
        $created = $this->create([
            'name' => $name,
            'file_path' => $filePath,
            'type' => $type,
            'description' => $description,
            'preview_image' => $previewImage,
            'status' => $status,
            'created_by' => __uuid(),
        ]);
        return $created ? $this->recentUid : null;
    }

    /**
     * Update template details
     */
    public function updateTemplate(string $uid, array $data): bool {
        $allowedFields = ['name', 'type', 'description', 'status', 'preview_image'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return false;
        }

        return $this->update($updateData, ['uid' => $uid]);
    }

    /**
     * Set template status to ACTIVE
     */
    public function setActive(string $uid): bool {
        return $this->update(['status' => 'ACTIVE'], ['uid' => $uid]);
    }

    /**
     * Set template status to INACTIVE
     */
    public function setInactive(string $uid): bool {
        return $this->update(['status' => 'INACTIVE'], ['uid' => $uid]);
    }

    /**
     * Delete template and associated files
     */
    public function deleteTemplate(string $uid): bool {
        $template = $this->excludeForeignKeys()->get($uid);
        if (isEmpty($template)) {
            return false;
        }

        // Delete placeholders first
        \classes\Methods::marketingPlaceholders()->deleteByTemplate($uid);

        // Delete files
        if (!isEmpty($template->file_path) && file_exists(ROOT . $template->file_path)) {
            unlink(ROOT . $template->file_path);
        }
        if (!isEmpty($template->preview_image) && file_exists(ROOT . $template->preview_image)) {
            unlink(ROOT . $template->preview_image);
        }

        return $this->delete(['uid' => $uid]);
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

    /**
     * Get status options array
     */
    public function getStatusOptions(): array {
        return [
            'DRAFT' => 'Kladde',
            'ACTIVE' => 'Aktiv',
            'INACTIVE' => 'Inaktiv',
        ];
    }
}
