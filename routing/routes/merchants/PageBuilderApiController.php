<?php

namespace routing\routes\merchants;

use classes\app\LocationPermissions;
use classes\Methods;
use JetBrains\PhpStorm\NoReturn;

class PageBuilderApiController {

    /**
     * Generic method to upload location media (hero/logo)
     */
    #[NoReturn] private static function uploadLocationMedia(array $args, string $type): void {
        $locationId = $args["location_id"] ?? null;
        $pageId = $args["page_id"] ?? null;

        if(isEmpty($locationId)) Response()->jsonError("Manglende location ID", [], 400);

        $location = Methods::locations()->get($locationId);
        if(isEmpty($location)) Response()->jsonError("Location ikke fundet", [], 404);

        // Check permissions
        if(!LocationPermissions::__oModify($location, 'pages'))
            Response()->jsonPermissionError("modify", 'sideindhold');

        // Get the page we're editing
        $draft = null;
        if(!isEmpty($pageId)) {
            $draft = Methods::locationPages()->excludeForeignKeys()->get($pageId);
            if(isEmpty($draft) || $draft->location !== $locationId) {
                Response()->jsonError("Ugyldig page ID", [], 400);
            }
        } else {
            // Fallback: get or create draft
            $draft = Methods::locationPages()->getOrCreateDraft($locationId, __uuid());
            if(isEmpty($draft)) Response()->jsonError("Kunne ikke oprette eller hente draft", [], 500);
        }

        // If current page is PUBLISHED, create a new draft
        $originalDraftUid = $draft->uid;
        if($draft->state === 'PUBLISHED') {
            $newDraftData = [
                'logo' => $draft->logo,
                'hero_image' => $draft->hero_image,
                'title' => $draft->title,
                'caption' => $draft->caption,
                'about_us' => $draft->about_us,
                'credit_widget_enabled' => $draft->credit_widget_enabled,
                'sections' => $draft->sections,
                'offer_enabled' => $draft->offer_enabled,
                'offer_title' => $draft->offer_title,
                'offer_text' => $draft->offer_text,
                'offer_image' => $draft->offer_image,
            ];

            $newDraftUid = Methods::locationPages()->insertDraft($locationId, $newDraftData, __uuid());
            if(isEmpty($newDraftUid)) Response()->jsonError("Kunne ikke oprette ny draft", [], 500);

            $draft = Methods::locationPages()->excludeForeignKeys()->get($newDraftUid);
        }

        // Validate file upload
        if(empty($_FILES) || !isset($_FILES["file"]))
            Response()->jsonError("Ingen fil uploadet", [], 400);

        // Upload media based on type
        $mediaStream = Methods::mediaStream();
        $result = match($type) {
            'logo' => $mediaStream->uploadOrganisationLogo($_FILES, $location->uuid->uid),
            'offer' => $mediaStream->uploadOrganisationOfferImage($_FILES, $location->uuid->uid),
            default => $mediaStream->uploadOrganisationHeroImage($_FILES, $location->uuid->uid),
        };

        if(!$result["success"]) {
            Response()->jsonError($result["error"], [], 400);
        }

        // Update draft field
        $field = match($type) {
            'logo' => 'logo',
            'offer' => 'offer_image',
            default => 'hero_image',
        };
        $updated = Methods::locationPages()->updateDraft($draft->uid, [$field => $result["path"]]);

        if(!$updated) {
            Response()->jsonError("Kunne ikke opdatere draft med nyt billede", [], 500);
        }

        $label = match($type) {
            'logo' => 'Logo',
            'offer' => 'Tilbudsbillede',
            default => 'Hero-billede',
        };
        Response()->jsonSuccess("{$label} uploadet", [
            "path" => $result["path"],
            "url" => __url($result["path"]),
            "width" => $result["width"],
            "height" => $result["height"],
            "default" => $result["default"] ?? false,
            "draft_uid" => $draft->uid,
            "created_new_draft" => $draft->uid !== $originalDraftUid
        ]);
    }

    /**
     * Generic method to remove location media (hero/logo)
     */
    #[NoReturn] private static function removeLocationMedia(array $args, string $type): void {
        $locationId = $args["location_id"] ?? null;
        $pageId = $args["page_id"] ?? null;

        if(isEmpty($locationId)) Response()->jsonError("Manglende location ID", [], 400);

        $location = Methods::locations()->get($locationId);
        if(isEmpty($location)) Response()->jsonError("Location ikke fundet", [], 404);

        // Check permissions
        if(!LocationPermissions::__oModify($location, 'pages'))
            Response()->jsonPermissionError("modify", 'sideindhold');

        // Get the page we're editing
        $draft = null;
        if(!isEmpty($pageId)) {
            $draft = Methods::locationPages()->excludeForeignKeys()->get($pageId);
            if(isEmpty($draft) || $draft->location !== $locationId) {
                Response()->jsonError("Ugyldig page ID", [], 400);
            }
        } else {
            // Fallback: get or create draft
            $draft = Methods::locationPages()->getOrCreateDraft($locationId, __uuid());
            if(isEmpty($draft)) Response()->jsonError("Kunne ikke oprette eller hente draft", [], 500);
        }

        // If current page is PUBLISHED, create a new draft
        $originalDraftUid = $draft->uid;
        if($draft->state === 'PUBLISHED') {
            $newDraftData = [
                'logo' => $draft->logo,
                'hero_image' => $draft->hero_image,
                'title' => $draft->title,
                'caption' => $draft->caption,
                'about_us' => $draft->about_us,
                'credit_widget_enabled' => $draft->credit_widget_enabled,
                'sections' => $draft->sections,
                'offer_enabled' => $draft->offer_enabled,
                'offer_title' => $draft->offer_title,
                'offer_text' => $draft->offer_text,
                'offer_image' => $draft->offer_image,
            ];

            $newDraftUid = Methods::locationPages()->insertDraft($locationId, $newDraftData, __uuid());
            if(isEmpty($newDraftUid)) Response()->jsonError("Kunne ikke oprette ny draft", [], 500);

            $draft = Methods::locationPages()->excludeForeignKeys()->get($newDraftUid);
        }

        $field = match($type) {
            'logo' => 'logo',
            'offer' => 'offer_image',
            default => 'hero_image',
        };
        $defaultConstant = match($type) {
            'logo' => DEFAULT_LOCATION_LOGO,
            'offer' => null, // No default for offer image
            default => DEFAULT_LOCATION_HERO,
        };
        $currentMedia = $draft->$field;

        // Check if we should delete the file
        $shouldDeleteFile = false;
        if(!isEmpty($currentMedia) && $currentMedia !== $defaultConstant) {
            // Get all locations for this organisation
            $orgLocations = Methods::locations()->excludeForeignKeys()->getByX(['uuid' => $location->uuid->uid], ['uid']);
            $locationIds = array_column($orgLocations->toArray(), 'uid');

            // Check if any other location page uses this image
            $shouldDeleteFile = Methods::locationPages()->excludeForeignKeys()
                ->queryBuilder()
                ->where('location', $locationIds)
                ->where('uid', '!=', $draft->uid)
                ->where($field, $currentMedia)
                ->count() === 0;
        }

        // Delete file if no other location page uses it
        if($shouldDeleteFile) {
            $filePath = ROOT . $currentMedia;
            if(file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // Set back to default in draft (null for offer image)
        $updated = Methods::locationPages()->updateDraft($draft->uid, [$field => $defaultConstant]);

        if(!$updated) {
            Response()->jsonError("Kunne ikke opdatere draft", [], 500);
        }

        $label = match($type) {
            'logo' => 'Logo',
            'offer' => 'Tilbudsbillede',
            default => 'Hero-billede',
        };
        Response()->jsonSuccess("{$label} fjernet", [
            "default_url" => $defaultConstant ? __url($defaultConstant) : null,
            "draft_uid" => $draft->uid,
            "created_new_draft" => $draft->uid !== $originalDraftUid
        ]);
    }

    #[NoReturn] public static function uploadLocationHeroImage(array $args): void {
        self::uploadLocationMedia($args, 'hero');
    }

    #[NoReturn] public static function removeLocationHeroImage(array $args): void {
        self::removeLocationMedia($args, 'hero');
    }

    #[NoReturn] public static function uploadLocationLogo(array $args): void {
        self::uploadLocationMedia($args, 'logo');
    }

    #[NoReturn] public static function removeLocationLogo(array $args): void {
        self::removeLocationMedia($args, 'logo');
    }

    #[NoReturn] public static function uploadLocationOfferImage(array $args): void {
        self::uploadLocationMedia($args, 'offer');
    }

    #[NoReturn] public static function removeLocationOfferImage(array $args): void {
        self::removeLocationMedia($args, 'offer');
    }

    /**
     * Save location page draft
     */
    #[NoReturn] public static function saveLocationPageDraft(array $args): void {
        $locationId = $args["location_id"] ?? null;
        $pageId = $args["page_id"] ?? null;

        if(isEmpty($locationId)) Response()->jsonError("Manglende location ID", [], 400);

        $location = Methods::locations()->get($locationId);
        if(isEmpty($location)) Response()->jsonError("Location ikke fundet", [], 404);

        // Check permissions
        if(!LocationPermissions::__oModify($location, 'pages'))
            Response()->jsonPermissionError("modify", 'sideindhold');

        // Get the page we're editing
        $draft = null;
        if(!isEmpty($pageId)) {
            $draft = Methods::locationPages()->excludeForeignKeys()->get($pageId);
            if(isEmpty($draft) || $draft->location !== $locationId) {
                Response()->jsonError("Ugyldig page ID", [], 400);
            }
        } else {
            // Fallback: get or create draft
            $draft = Methods::locationPages()->getOrCreateDraft($locationId, __uuid());
            if(isEmpty($draft)) Response()->jsonError("Kunne ikke oprette eller hente draft", [], 500);
        }

        // If current page is PUBLISHED, create a new draft with the data
        $originalDraftUid = $draft->uid;
        if($draft->state === 'PUBLISHED') {
            // Create new draft from published
            $newDraftData = [
                'logo' => $draft->logo,
                'hero_image' => $draft->hero_image,
                'title' => $draft->title,
                'caption' => $draft->caption,
                'about_us' => $draft->about_us,
                'credit_widget_enabled' => $draft->credit_widget_enabled,
                'sections' => $draft->sections,
                'offer_enabled' => $draft->offer_enabled,
                'offer_title' => $draft->offer_title,
                'offer_text' => $draft->offer_text,
                'offer_image' => $draft->offer_image,
            ];

            $newDraftUid = Methods::locationPages()->insertDraft($locationId, $newDraftData, __uuid());
            if(isEmpty($newDraftUid)) Response()->jsonError("Kunne ikke oprette ny draft", [], 500);

            // Update draft reference
            $draft = Methods::locationPages()->excludeForeignKeys()->get($newDraftUid);
        }

        // Prepare update data
        $updateData = [];

        // Update standard fields
        if(array_key_exists('title', $args)) $updateData['title'] = trim($args['title']);
        if(array_key_exists('caption', $args)) $updateData['caption'] = trim($args['caption']);
        if(array_key_exists('about_us', $args)) $updateData['about_us'] = trim($args['about_us']);
        if(array_key_exists('credit_widget_enabled', $args))
            $updateData['credit_widget_enabled'] = !empty($args['credit_widget_enabled']) ? 1 : 0;

        // Update offer fields
        if(array_key_exists('offer_enabled', $args))
            $updateData['offer_enabled'] = !empty($args['offer_enabled']) ? 1 : 0;
        if(array_key_exists('offer_title', $args)) $updateData['offer_title'] = trim($args['offer_title']);
        if(array_key_exists('offer_text', $args)) $updateData['offer_text'] = trim($args['offer_text']);

        // Process sections - find all section fields regardless of index
        $sections = [];
        foreach($args as $key => $value) {
            if(preg_match('/^section_title_(\d+)$/', $key, $matches)) {
                $index = $matches[1];
                $title = trim($args["section_title_{$index}"] ?? '');
                $content = trim($args["section_content_{$index}"] ?? '');

                // Only add section if it has content
                if(!isEmpty($title) || !isEmpty($content)) {
                    $sections[] = [
                        'title' => $title,
                        'content' => $content
                    ];
                }
            }
        }
        $updateData['sections'] = $sections;

        // Update draft
        $updated = Methods::locationPages()->updateDraft($draft->uid, $updateData);
        if(!$updated) Response()->jsonError("Kunne ikke gemme ændringer", [], 500);

        Response()->jsonSuccess("Ændringer gemt", [
            "draft_uid" => $draft->uid,
            "created_new_draft" => $draft->uid !== $originalDraftUid
        ]);
    }

    /**
     * Publish a draft page
     */
    #[NoReturn] public static function publishPageDraft(array $args): void {
        $locationId = $args["location_id"] ?? null;
        $pageId = $args["page_id"] ?? null;

        if(isEmpty($locationId)) Response()->jsonError("Manglende location ID", [], 400);
        if(isEmpty($pageId)) Response()->jsonError("Manglende page ID", [], 400);

        $location = Methods::locations()->get($locationId);
        if(isEmpty($location)) Response()->jsonError("Location ikke fundet", [], 404);

        // Check permissions
        if(!LocationPermissions::__oModify($location, 'pages'))
            Response()->jsonPermissionError("modify", 'sideindhold');

        $page = Methods::locationPages()->excludeForeignKeys()->get($pageId);
        if(isEmpty($page)) Response()->jsonError("Side ikke fundet", [], 404);
        if($page->location !== $locationId) Response()->jsonError("Ugyldig side for denne location", [], 403);

        // Can only publish drafts or archived pages
        if($page->state === 'PUBLISHED') Response()->jsonError("Siden er allerede udgivet", [], 400);

        // Publish the draft
        $published = Methods::locationPages()->publishDraft($pageId);
        if(!$published) Response()->jsonError("Kunne ikke udgive siden", [], 500);

        Response()->jsonSuccess("Siden er blevet udgivet", [
            "page_id" => $pageId
        ]);
    }

}
