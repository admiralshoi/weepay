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
     * Get all active templates, optionally filtered by type and/or category
     */
    public function getActive(?string $type = null, ?string $category = null): Collection {
        $params = ['status' => 'ACTIVE'];
        if ($type) {
            $params['type'] = $type;
        }
        if ($category) {
            $params['category'] = $category;
        }
        return $this->getByXOrderBy('name', 'ASC', $params);
    }

    /**
     * Get active downloadable templates (category = template)
     */
    public function getActiveTemplates(?string $type = null): Collection {
        return $this->getActive($type, 'template');
    }

    /**
     * Get active A-sign base templates (category = a_sign_base)
     */
    public function getActiveASignBases(): Collection {
        return $this->getActive(null, 'a_sign_base');
    }

    /**
     * Get all templates ordered by name
     */
    public function getAll(): Collection {
        return $this->getByXOrderBy('name', 'ASC', []);
    }

    /**
     * Create a new marketing template (legacy - with direct file path)
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
     * Create a new template version from a base template
     */
    public function createFromBase(
        string $baseTemplateUid,
        string $name,
        ?string $versionName = null,
        ?string $description = null,
        string $status = 'DRAFT'
    ): ?string {
        $baseTemplate = \classes\Methods::marketingBaseTemplates()->excludeForeignKeys()->get($baseTemplateUid);
        if (isEmpty($baseTemplate)) {
            return null;
        }

        $created = $this->create([
            'name' => $name,
            'base_template' => $baseTemplateUid,
            'version_name' => $versionName,
            'file_path' => null,
            'type' => $baseTemplate->type,
            'category' => 'template',
            'description' => $description,
            'preview_image' => $baseTemplate->preview_image,
            'status' => $status,
            'created_by' => __uuid(),
        ]);
        return $created ? $this->recentUid : null;
    }

    /**
     * Get all versions of a base template
     */
    public function getVersionsOfBase(string $baseTemplateUid): Collection {
        return $this->getByXOrderBy('name', 'ASC', ['base_template' => $baseTemplateUid]);
    }

    /**
     * Get the file path for a template (falls back to base template if not set)
     */
    public function getFilePath(object $template): ?string {
        if (!isEmpty($template->file_path)) {
            return $template->file_path;
        }

        if (!isEmpty($template->base_template)) {
            $baseTemplateUid = is_object($template->base_template)
                ? $template->base_template->uid
                : $template->base_template;
            $baseTemplate = \classes\Methods::marketingBaseTemplates()->excludeForeignKeys()->get($baseTemplateUid);
            if (!isEmpty($baseTemplate)) {
                return $baseTemplate->file_path;
            }
        }

        return null;
    }

    /**
     * Update template details
     */
    public function updateTemplate(string $uid, array $data): bool {
        $allowedFields = ['name', 'type', 'category', 'description', 'status', 'preview_image', 'version_name'];
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
     * Note: If template is a version (has base_template), files belong to base and are not deleted
     */
    public function deleteTemplate(string $uid): bool {
        $template = $this->excludeForeignKeys()->get($uid);
        if (isEmpty($template)) {
            return false;
        }

        // Delete placeholders first
        \classes\Methods::marketingPlaceholders()->deleteByTemplate($uid);

        // Only delete files if this is a standalone template (not a version of a base)
        if (isEmpty($template->base_template)) {
            if (!isEmpty($template->file_path) && file_exists(ROOT . $template->file_path)) {
                unlink(ROOT . $template->file_path);
            }
            if (!isEmpty($template->preview_image) && file_exists(ROOT . $template->preview_image)) {
                unlink(ROOT . $template->preview_image);
            }
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

    /**
     * Get category options array
     */
    public function getCategoryOptions(): array {
        return [
            'template' => 'Template',
            'a_sign_base' => 'A-Skilt Base',
        ];
    }
}
