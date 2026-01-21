<?php

namespace classes\marketing;

use classes\utility\Crud;
use Database\Collection;
use Database\model\MarketingInspiration;

class MarketingInspirationHandler extends Crud {

    public function __construct() {
        parent::__construct(MarketingInspiration::newStatic(), 'marketing_inspiration');
    }

    /**
     * Get all active inspiration items, optionally filtered by category
     */
    public function getActive(?string $category = null): Collection {
        $params = ['status' => 'ACTIVE'];
        if ($category) {
            $params['category'] = $category;
        }
        return $this->getByXOrderBy('sort_order', 'ASC', $params);
    }

    /**
     * Get all inspiration items ordered by sort_order
     */
    public function getAll(): Collection {
        return $this->getByXOrderBy('sort_order', 'ASC', []);
    }

    /**
     * Create a new inspiration item
     */
    public function createInspiration(
        string $title,
        string $imagePath,
        string $category = 'other',
        ?string $description = null,
        string $status = 'DRAFT'
    ): ?string {
        // Get next sort order
        $maxSort = $this->queryBuilder()->max('sort_order') ?? 0;

        $created = $this->create([
            'title' => $title,
            'image_path' => $imagePath,
            'category' => $category,
            'description' => $description,
            'status' => $status,
            'sort_order' => $maxSort + 1,
            'created_by' => __uuid(),
        ]);
        return $created ? $this->recentUid : null;
    }

    /**
     * Update inspiration item
     */
    public function updateInspiration(string $uid, array $data): bool {
        $allowedFields = ['title', 'category', 'description', 'status', 'sort_order'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return false;
        }

        return $this->update($updateData, ['uid' => $uid]);
    }

    /**
     * Delete inspiration item and associated image
     */
    public function deleteInspiration(string $uid): bool {
        $item = $this->excludeForeignKeys()->get($uid);
        if (isEmpty($item)) {
            return false;
        }

        // Delete image file
        if (!isEmpty($item->image_path) && file_exists(ROOT . $item->image_path)) {
            unlink(ROOT . $item->image_path);
        }

        return $this->delete(['uid' => $uid]);
    }

    /**
     * Get category options array
     */
    public function getCategoryOptions(): array {
        return [
            'instagram' => 'Instagram',
            'a_sign' => 'A-Skilt',
            'a_sign_design' => 'A-Skilt (Design)',
            'a_sign_arbitrary' => 'A-Skilt (Vilkårligt)',
            'poster' => 'Plakat',
            'other' => 'Andet',
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
