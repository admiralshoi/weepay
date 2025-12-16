<?php

namespace classes\organisations;

use classes\Methods;
use classes\utility\Crud;
use Database\model\LocationPages;

class LocationPagesHandler extends Crud {

    public function __construct() {
        parent::__construct(LocationPages::newStatic(), 'location');
    }

    /**
     * Get the current draft for a location
     * Returns null if no draft exists
     */
    public function getCurrentDraft(string $locationId): ?object {
        return $this->getFirst(['location' => $locationId, 'state' => 'DRAFT']);
    }

    /**
     * Get the published page for a location
     * Returns null if no published page exists
     */
    public function getPublished(string $locationId): ?object {
        return $this->getFirst(['location' => $locationId, 'state' => 'PUBLISHED']);
    }

    /**
     * Create or get draft for a location
     * If draft exists, return it. Otherwise create from location data or empty
     */
    public function getOrCreateDraft(string $locationId, ?string $createdBy = null): ?object {
        // Check if draft already exists
        $existingDraft = $this->getCurrentDraft($locationId);
        if(!isEmpty($existingDraft)) {
            return $existingDraft;
        }

        // Get location to copy data from
        $location = Methods::locations()->get($locationId);
        if(isEmpty($location)) return null;

        // Create new draft from location data
        $draftData = [
            'location' => $locationId,
            'state' => 'DRAFT',
            'logo' => DEFAULT_LOCATION_LOGO,
            'hero_image' => DEFAULT_LOCATION_HERO,
            'title' => $location->name,
            'caption' => $location->caption,
            'about_us' => $location->description,
            'credit_widget_enabled' => 1,
            'sections' => [],
            'created_by' => $createdBy ?? __uuid()
        ];

        if(!$this->create($draftData)) return null;
        return $this->get($this->recentUid);
    }

    /**
     * Insert new draft for location
     * Archives any existing draft before creating new one
     */
    public function insertDraft(
        string $locationId,
        array $data,
        ?string $createdBy = null
    ): ?string {
        $this->update(['state' => 'ARCHIVED'], ['location' => $locationId, 'state' => 'DRAFT']);

        // Prepare draft data
        $draftData = array_merge([
            'location' => $locationId,
            'state' => 'DRAFT',
            'created_by' => $createdBy ?? __uuid()
        ], $data);

        // Insert new draft
        if(!$this->create($draftData)) {
            return null;
        }

        return $this->recentUid;
    }

    /**
     * Publish a draft
     * Sets draft to PUBLISHED and archives any existing published pages
     */
    public function publishDraft(string $draftUid): bool {
        $draft = $this->get($draftUid);
        if(isEmpty($draft) || $draft->state !== 'DRAFT') {
            return false;
        }
        $locationId = is_string($draft->location) ? $draft->location : $draft->location->uid;
        $this->update(['state' => 'ARCHIVED'], ['location' => $locationId, 'state' => 'PUBLISHED']);
        if(!$this->update(['state' => 'PUBLISHED'], ['uid' => $draftUid])) return false;
        $this->update(['state' => 'ARCHIVED'], ['location' => $locationId, 'state' => 'DRAFT']);
        return true;
    }

    /**
     * Update draft data
     */
    public function updateDraft(string $draftUid, array $data): bool {
        $draft = $this->get($draftUid);
        if(isEmpty($draft) || $draft->state === 'PUBLISHED') return false;
        return $this->update($data, ['uid' => $draftUid]);
    }
}
