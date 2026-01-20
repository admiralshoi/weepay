<?php

namespace classes\app;

use classes\utility\Crud;
use Database\Collection;
use Database\model\Faqs;

class FaqHandler extends Crud {

    function __construct() {
        parent::__construct(Faqs::newStatic(), "faqs");
    }

    /**
     * Get all FAQs for a specific type (consumer or merchant)
     */
    public function getByType(string $type, bool $activeOnly = true): Collection {
        $params = ['type' => $type];
        if ($activeOnly) {
            $params['is_active'] = 1;
        }
        return $this->getByXOrderBy('sort_order', 'ASC', $params);
    }

    /**
     * Get FAQs grouped by category for a specific type
     */
    public function getGroupedByCategory(string $type, bool $activeOnly = true): array {
        $faqs = $this->getByType($type, $activeOnly);
        $grouped = [];

        foreach ($faqs->list() as $faq) {
            $category = $faq->category;
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $faq;
        }

        return $grouped;
    }

    /**
     * Get all unique categories for a type
     */
    public function getCategories(string $type): array {
        $faqs = $this->getByType($type, false);
        $categories = [];

        foreach ($faqs->list() as $faq) {
            if (!in_array($faq->category, $categories)) {
                $categories[] = $faq->category;
            }
        }

        return $categories;
    }

    /**
     * Get maximum sort order for a category
     */
    public function getMaxSortOrder(string $type, string $category): int {
        $query = $this->queryBuilder()
            ->where('type', $type)
            ->where('category', $category)
            ->order('sort_order', 'DESC');

        $faq = $this->queryGetFirst($query);
        return $faq ? (int)$faq->sort_order : 0;
    }

    /**
     * Create a new FAQ
     */
    public function createFaq(array $data): ?string {
        // Set default sort order if not provided
        if (!isset($data['sort_order'])) {
            $data['sort_order'] = $this->getMaxSortOrder($data['type'], $data['category']) + 1;
        }

        if ($this->create($data)) {
            return $this->recentUid;
        }
        return null;
    }

    /**
     * Update FAQ
     */
    public function updateFaq(string $uid, array $data): bool {
        return $this->update($data, ['uid' => $uid]);
    }

    /**
     * Delete FAQ
     */
    public function deleteFaq(string $uid): bool {
        return $this->delete(['uid' => $uid]);
    }

    /**
     * Toggle FAQ active status
     */
    public function toggleActive(string $uid): bool {
        $faq = $this->get($uid);
        if (!$faq) return false;

        $newStatus = $faq->is_active ? 0 : 1;
        return $this->update(['is_active' => $newStatus], ['uid' => $uid]);
    }

    /**
     * Reorder FAQs within a category
     */
    public function reorder(string $type, string $category, array $uidOrder): bool {
        foreach ($uidOrder as $index => $uid) {
            $this->update(['sort_order' => $index], ['uid' => $uid]);
        }
        return true;
    }

}
