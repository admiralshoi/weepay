<?php

namespace classes\marketing;

use classes\utility\Crud;
use Database\Collection;
use Database\model\ASignDesigns;

class ASignDesignHandler extends Crud {

    public function __construct() {
        parent::__construct(ASignDesigns::newStatic(), 'asign_designs');
    }

    /**
     * Get all designs for the current organisation
     */
    public function getMyDesigns(?string $locationUid = null): Collection {
        $params = ['organisation' => __oUuid()];
        if ($locationUid) {
            $params['location'] = $locationUid;
        }
        return $this->getByXOrderBy('created_at', 'DESC', $params);
    }

    /**
     * Get active designs (DRAFT or SAVED, not ARCHIVED)
     */
    public function getActiveDesigns(?string $locationUid = null): Collection {
        $query = $this->queryBuilder()
            ->where('organisation', '=', __oUuid())
            ->where('status', '!=', 'ARCHIVED')
            ->order('created_at', 'DESC');

        if ($locationUid) {
            $query->where('location', '=', $locationUid);
        }

        return $this->queryGetAll($query);
    }

    /**
     * Get designs by type
     */
    public function getByType(string $type): Collection {
        return $this->getByXOrderBy('created_at', 'DESC', [
            'organisation' => __oUuid(),
            'type' => $type,
            'status' => 'SAVED'
        ]);
    }

    /**
     * Create a new design
     */
    public function createDesign(
        string $name,
        string $type = 'design',
        string $size = 'A1',
        ?string $locationUid = null,
        ?array $elements = null,
        ?string $barColor = '#8B4513'
    ): ?string {
        $created = $this->create([
            'organisation' => __oUuid(),
            'location' => $locationUid,
            'name' => $name,
            'type' => $type,
            'size' => $size,
            'elements' => $elements,
            'bar_color' => $barColor,
            'status' => 'DRAFT',
            'created_by' => __uuid(),
        ]);
        return $created ? $this->recentUid : null;
    }

    /**
     * Update design data
     */
    public function updateDesign(string $uid, array $data): bool {
        // Verify ownership
        $design = $this->excludeForeignKeys()->get($uid);
        if (isEmpty($design) || $design->organisation !== __oUuid()) {
            return false;
        }

        $allowedFields = [
            'name', 'location', 'size', 'background_image', 'canvas_data',
            'elements', 'bar_color', 'status', 'preview_image', 'logo_image'
        ];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return false;
        }

        return ASignDesigns::whereList(['uid' => $uid])->update($updateData);
    }

    /**
     * Save design canvas state
     */
    public function saveCanvasState(string $uid, array $canvasData, ?array $elements = null): bool {
        $updateData = ['canvas_data' => $canvasData];
        if ($elements !== null) {
            $updateData['elements'] = $elements;
        }
        return $this->updateDesign($uid, $updateData);
    }

    /**
     * Update design background image
     */
    public function setBackgroundImage(string $uid, string $imagePath): bool {
        return $this->updateDesign($uid, ['background_image' => $imagePath]);
    }

    /**
     * Update design preview thumbnail
     */
    public function setPreviewImage(string $uid, string $previewPath): bool {
        return $this->updateDesign($uid, ['preview_image' => $previewPath]);
    }

    /**
     * Update design logo image
     */
    public function setLogoImage(string $uid, string $logoPath): bool {
        return $this->updateDesign($uid, ['logo_image' => $logoPath]);
    }

    /**
     * Archive a design
     */
    public function archiveDesign(string $uid): bool {
        return $this->updateDesign($uid, ['status' => 'ARCHIVED']);
    }

    /**
     * Delete design and associated files
     */
    public function deleteDesign(string $uid): bool {
        $design = $this->excludeForeignKeys()->get($uid);
        if (isEmpty($design) || $design->organisation !== __oUuid()) {
            return false;
        }

        // Delete associated files
        if (!isEmpty($design->background_image) && file_exists(ROOT . $design->background_image)) {
            unlink(ROOT . $design->background_image);
        }
        if (!isEmpty($design->preview_image) && file_exists(ROOT . $design->preview_image)) {
            unlink(ROOT . $design->preview_image);
        }
        if (!isEmpty($design->logo_image) && file_exists(ROOT . $design->logo_image)) {
            unlink(ROOT . $design->logo_image);
        }

        return $this->delete(['uid' => $uid]);
    }

    /**
     * Get design if user has access
     */
    public function getWithAccess(string $uid): ?object {
        $design = $this->excludeForeignKeys()->get($uid);
        if (isEmpty($design) || $design->organisation !== __oUuid()) {
            return null;
        }
        return $design;
    }

    /**
     * Get design with foreign keys resolved (for display)
     */
    public function getWithRelations(string $uid): ?object {
        $design = $this->excludeForeignKeys()->get($uid);
        if (isEmpty($design) || $design->organisation !== __oUuid()) {
            return null;
        }
        return $this->get($uid);
    }

    /**
     * Get type options
     */
    public function getTypeOptions(): array {
        return [
            'design' => 'Design',
            'arbitrary' => 'Vilkårligt',
        ];
    }

    /**
     * Get status options
     */
    public function getStatusOptions(): array {
        return [
            'DRAFT' => 'Kladde',
            'SAVED' => 'Gemt',
            'ARCHIVED' => 'Arkiveret',
        ];
    }

    /**
     * Get size options with real-world dimensions
     */
    public function getSizeOptions(): array {
        return [
            'A1' => [
                'label' => 'A1 (594 × 841 mm)',
                'widthMm' => 594,
                'heightMm' => 841,
            ],
            'B1' => [
                'label' => 'B1 (700 × 1000 mm)',
                'widthMm' => 700,
                'heightMm' => 1000,
            ],
            'A0' => [
                'label' => 'A0 (841 × 1189 mm)',
                'widthMm' => 841,
                'heightMm' => 1189,
            ],
            '50x70' => [
                'label' => '50 × 70 cm',
                'widthMm' => 500,
                'heightMm' => 700,
            ],
        ];
    }

    /**
     * Get storage path for organisation's A-Sign files
     */
    public function getStoragePath(): string {
        $orgUid = __oUuid();
        $path = "public/content/organisations/{$orgUid}/media/asign";

        // Create directory if it doesn't exist
        $fullPath = ROOT . $path;
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        return $path;
    }
}
