<?php

namespace classes\marketing;

use classes\utility\Crud;
use Database\Collection;
use Database\model\MarketingTemplatePlaceholders;

class MarketingPlaceholderHandler extends Crud {

    public function __construct() {
        parent::__construct(MarketingTemplatePlaceholders::newStatic(), 'marketing_placeholders');
    }

    /**
     * Get all placeholders for a template
     */
    public function getByTemplate(string $templateUid): Collection {
        return $this->getByXOrderBy('sort_order', 'ASC', ['template' => $templateUid]);
    }

    /**
     * Get placeholders for a specific page of a template
     */
    public function getByTemplatePage(string $templateUid, int $pageNumber): Collection {
        return $this->getByXOrderBy('sort_order', 'ASC', [
            'template' => $templateUid,
            'page_number' => $pageNumber
        ]);
    }

    /**
     * Save all placeholders for a template (replaces existing)
     */
    public function savePlaceholders(string $templateUid, array $placeholders): bool {
        // Delete existing placeholders for this template
        $this->deleteByTemplate($templateUid);

        // Insert new placeholders
        foreach ($placeholders as $index => $placeholder) {
            $created = $this->create([
                'template' => $templateUid,
                'type' => $placeholder['type'] ?? 'qr_code',
                'x' => floatval($placeholder['x'] ?? 0),
                'y' => floatval($placeholder['y'] ?? 0),
                'width' => floatval($placeholder['width'] ?? 10),
                'height' => floatval($placeholder['height'] ?? 10),
                'page_number' => intval($placeholder['page_number'] ?? 1),
                'font_size' => isset($placeholder['font_size']) ? intval($placeholder['font_size']) : 12,
                'font_color' => $placeholder['font_color'] ?? '#000000',
                'sort_order' => $index,
            ]);

            if (!$created) {
                return false;
            }
        }

        return true;
    }

    /**
     * Delete all placeholders for a template
     */
    public function deleteByTemplate(string $templateUid): bool {
        return $this->delete(['template' => $templateUid]);
    }

    /**
     * Get placeholder type options
     */
    public function getTypeOptions(): array {
        return [
            'qr_code' => 'QR-kode',
            'location_name' => 'Lokationsnavn',
            'location_logo' => 'Lokationslogo',
        ];
    }
}
