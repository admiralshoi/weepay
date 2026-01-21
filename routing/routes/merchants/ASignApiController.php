<?php

namespace routing\routes\merchants;

use classes\Methods;
use JetBrains\PhpStorm\NoReturn;

class ASignApiController {

    /**
     * List all designs for current organisation
     */
    #[NoReturn] public static function listDesigns(array $args): void {
        $locationUid = $args['location_uid'] ?? null;
        $includeArchived = ($args['include_archived'] ?? 'false') === 'true';

        $handler = Methods::asignDesigns();

        if ($includeArchived) {
            $designs = $handler->getMyDesigns($locationUid);
        } else {
            $designs = $handler->getActiveDesigns($locationUid);
        }

        $result = [];
        foreach ($designs->list() as $design) {
            $result[] = [
                'uid' => $design->uid,
                'name' => $design->name,
                'type' => $design->type,
                'status' => $design->status,
                'preview_image' => $design->preview_image ? __url($design->preview_image) : null,
                'location' => $design->location?->uid ?? null,
                'location_name' => $design->location?->name ?? null,
                'created_at' => $design->created_at,
            ];
        }

        Response()->jsonSuccess("Designs hentet", ['designs' => $result]);
    }

    /**
     * Get a single design with full data
     */
    #[NoReturn] public static function getDesign(array $args): void {
        $uid = $args['id'] ?? null;

        if (isEmpty($uid)) {
            Response()->jsonError("Manglende design ID", [], 400);
        }

        $handler = Methods::asignDesigns();
        $design = $handler->getWithAccess($uid);

        if (isEmpty($design)) {
            Response()->jsonError("Design ikke fundet", [], 404);
        }

        // Get full design with relations for location data
        $fullDesign = $handler->get($uid);

        $result = [
            'uid' => $design->uid,
            'name' => $design->name,
            'type' => $design->type,
            'status' => $design->status,
            'background_image' => $design->background_image ? __url($design->background_image) : null,
            'logo_image' => $design->logo_image ? __url($design->logo_image) : null,
            'canvas_data' => $design->canvas_data,
            'elements' => $design->elements,
            'bar_color' => $design->bar_color,
            'preview_image' => $design->preview_image ? __url($design->preview_image) : null,
            'location' => $fullDesign->location ? [
                'uid' => $fullDesign->location->uid,
                'name' => $fullDesign->location->name,
                'slug' => $fullDesign->location->slug,
            ] : null,
            'created_at' => $design->created_at,
        ];

        Response()->jsonSuccess("Design hentet", ['design' => $result]);
    }

    /**
     * Create a new design
     */
    #[NoReturn] public static function createDesign(array $args): void {
        $name = $args['name'] ?? null;
        $type = $args['type'] ?? 'design';
        $size = $args['size'] ?? 'A1';
        $locationUid = $args['location_uid'] ?? null;
        $barColor = $args['bar_color'] ?? '#8B4513';

        if (isEmpty($name)) {
            Response()->jsonError("Manglende navn", [], 400);
        }

        // Validate type
        if (!in_array($type, ['design', 'arbitrary'])) {
            $type = 'design';
        }

        // Validate size
        if (!in_array($size, ['A1', 'B1', 'A0', '50x70'])) {
            $size = 'A1';
        }

        // Validate location if provided
        if (!isEmpty($locationUid)) {
            $location = Methods::locations()->excludeForeignKeys()->get($locationUid);
            if (isEmpty($location) || $location->uuid !== __oUuid()) {
                Response()->jsonError("Lokation ikke fundet", [], 404);
            }
        }

        $handler = Methods::asignDesigns();
        $uid = $handler->createDesign($name, $type, $size, $locationUid, null, $barColor);

        if (isEmpty($uid)) {
            Response()->jsonError("Kunne ikke oprette design", [], 500);
        }

        Response()->jsonSuccess("Design oprettet", ['uid' => $uid]);
    }

    /**
     * Update an existing design
     */
    #[NoReturn] public static function updateDesign(array $args): void {
        $uid = $args['uid'] ?? null;

        if (isEmpty($uid)) {
            Response()->jsonError("Manglende design ID", [], 400);
        }

        $handler = Methods::asignDesigns();

        // Verify access
        $design = $handler->getWithAccess($uid);
        if (isEmpty($design)) {
            Response()->jsonError("Design ikke fundet", [], 404);
        }

        // Prepare update data
        $updateData = [];

        if (isset($args['name'])) {
            $updateData['name'] = $args['name'];
        }
        if (isset($args['canvas_data'])) {
            $updateData['canvas_data'] = $args['canvas_data'];
        }
        if (isset($args['elements'])) {
            $updateData['elements'] = $args['elements'];
        }
        if (isset($args['bar_color'])) {
            $updateData['bar_color'] = $args['bar_color'];
        }
        if (isset($args['status'])) {
            if (in_array($args['status'], ['DRAFT', 'SAVED', 'ARCHIVED'])) {
                $updateData['status'] = $args['status'];
            }
        }
        if (isset($args['size'])) {
            if (in_array($args['size'], ['A1', 'B1', 'A0', '50x70'])) {
                $updateData['size'] = $args['size'];
            }
        }
        if (isset($args['location_uid'])) {
            if (isEmpty($args['location_uid'])) {
                $updateData['location'] = null;
            } else {
                $location = Methods::locations()->excludeForeignKeys()->get($args['location_uid']);
                if (!isEmpty($location) && $location->uuid === __oUuid()) {
                    $updateData['location'] = $args['location_uid'];
                }
            }
        }

        // Handle clearing background image
        if (isset($args['clear_background']) && $args['clear_background'] === true) {
            if (!isEmpty($design->background_image) && file_exists(ROOT . $design->background_image)) {
                unlink(ROOT . $design->background_image);
            }
            $updateData['background_image'] = null;
        }

        // Handle clearing logo image
        if (isset($args['clear_logo']) && $args['clear_logo'] === true) {
            if (isset($design->logo_image) && !isEmpty($design->logo_image) && file_exists(ROOT . $design->logo_image)) {
                unlink(ROOT . $design->logo_image);
            }
            $updateData['logo_image'] = null;
        }

        if (empty($updateData)) {
            Response()->jsonError("Ingen data at opdatere", [], 400);
        }

        $updated = $handler->updateDesign($uid, $updateData);

        if (!$updated) {
            Response()->jsonError("Kunne ikke opdatere design", [], 500);
        }

        Response()->jsonSuccess("Design opdateret");
    }

    /**
     * Delete a design
     */
    #[NoReturn] public static function deleteDesign(array $args): void {
        $uid = $args['uid'] ?? null;

        if (isEmpty($uid)) {
            Response()->jsonError("Manglende design ID", [], 400);
        }

        $handler = Methods::asignDesigns();
        $deleted = $handler->deleteDesign($uid);

        if (!$deleted) {
            Response()->jsonError("Kunne ikke slette design", [], 500);
        }

        Response()->jsonSuccess("Design slettet");
    }

    /**
     * Upload background image for a design
     */
    #[NoReturn] public static function uploadBackground(array $args): void {
        $uid = $args['uid'] ?? null;
        $file = $args['__FILES']['image'] ?? null;

        if (isEmpty($uid)) {
            Response()->jsonError("Manglende design ID", [], 400);
        }

        if (isEmpty($file) || !isset($file['tmp_name'])) {
            Response()->jsonError("Ingen fil uploadet", [], 400);
        }

        // Check for upload errors
        if (isset($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'Filen overskrider upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE => 'Filen overskrider MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'Filen blev kun delvist uploadet',
                UPLOAD_ERR_NO_FILE => 'Ingen fil blev uploadet',
                UPLOAD_ERR_NO_TMP_DIR => 'Manglende midlertidig mappe',
                UPLOAD_ERR_CANT_WRITE => 'Kunne ikke skrive fil til disk',
                UPLOAD_ERR_EXTENSION => 'Fil upload stoppet af extension',
            ];
            $errorMsg = $errorMessages[$file['error']] ?? 'Ukendt upload fejl';
            Response()->jsonError($errorMsg, [], 400);
        }

        $handler = Methods::asignDesigns();

        // Verify access
        $design = $handler->getWithAccess($uid);
        if (isEmpty($design)) {
            Response()->jsonError("Design ikke fundet", [], 404);
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            Response()->jsonError("Ugyldig filtype. Kun JPG, PNG, WebP og SVG er tilladt.", [], 400);
        }

        // Validate file size (10MB max)
        if ($file['size'] > 10 * 1024 * 1024) {
            Response()->jsonError("Filen er for stor. Maksimum 10MB.", [], 400);
        }

        // Get storage path
        $storagePath = $handler->getStoragePath();
        $extension = match($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            default => 'jpg'
        };
        $filename = "bg-" . time() . "-" . $uid . "." . $extension;
        $relativePath = $storagePath . "/" . $filename;
        $fullPath = ROOT . $relativePath;

        // Delete old background if exists
        if (!isEmpty($design->background_image) && file_exists(ROOT . $design->background_image)) {
            unlink(ROOT . $design->background_image);
        }

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            Response()->jsonError("Kunne ikke gemme filen", [], 500);
        }

        // Update design
        $handler->setBackgroundImage($uid, $relativePath);

        Response()->jsonSuccess("Baggrundsbillede uploadet", [
            'path' => $relativePath,
            'url' => __url($relativePath)
        ]);
    }

    /**
     * Upload logo image for a design
     */
    #[NoReturn] public static function uploadLogo(array $args): void {
        $uid = $args['uid'] ?? null;
        $file = $args['__FILES']['image'] ?? null;

        if (isEmpty($uid)) {
            Response()->jsonError("Manglende design ID", [], 400);
        }

        if (isEmpty($file) || !isset($file['tmp_name'])) {
            Response()->jsonError("Ingen fil uploadet", [], 400);
        }

        // Check for upload errors
        if (isset($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
            Response()->jsonError("Upload fejl", [], 400);
        }

        $handler = Methods::asignDesigns();

        // Verify access
        $design = $handler->getWithAccess($uid);
        if (isEmpty($design)) {
            Response()->jsonError("Design ikke fundet", [], 404);
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            Response()->jsonError("Ugyldig filtype. Kun JPG, PNG, WebP og SVG er tilladt.", [], 400);
        }

        // Validate file size (5MB max for logos)
        if ($file['size'] > 5 * 1024 * 1024) {
            Response()->jsonError("Filen er for stor. Maksimum 5MB.", [], 400);
        }

        // Get storage path
        $storagePath = $handler->getStoragePath();
        $extension = match($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            default => 'png'
        };
        $filename = "logo-" . $uid . "." . $extension;
        $relativePath = $storagePath . "/" . $filename;
        $fullPath = ROOT . $relativePath;

        // Delete old logo if exists
        if (isset($design->logo_image) && !isEmpty($design->logo_image) && file_exists(ROOT . $design->logo_image)) {
            unlink(ROOT . $design->logo_image);
        }

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            Response()->jsonError("Kunne ikke gemme filen", [], 500);
        }

        // Update design
        $handler->setLogoImage($uid, $relativePath);

        Response()->jsonSuccess("Logo uploadet", [
            'path' => $relativePath,
            'url' => __url($relativePath)
        ]);
    }

    /**
     * Upload preview image for a design
     */
    #[NoReturn] public static function uploadPreview(array $args): void {
        $uid = $args['uid'] ?? null;
        $imageData = $args['image_data'] ?? null;

        if (isEmpty($uid)) {
            Response()->jsonError("Manglende design ID", [], 400);
        }

        if (isEmpty($imageData)) {
            Response()->jsonError("Manglende billeddata", [], 400);
        }

        $handler = Methods::asignDesigns();

        // Verify access
        $design = $handler->getWithAccess($uid);
        if (isEmpty($design)) {
            Response()->jsonError("Design ikke fundet", [], 404);
        }

        // Decode base64 image data
        // Format: data:image/png;base64,XXXXX
        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
            $extension = $matches[1];
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
            $imageData = base64_decode($imageData);

            if ($imageData === false) {
                Response()->jsonError("Ugyldig billeddata", [], 400);
            }
        } else {
            Response()->jsonError("Ugyldig billedformat", [], 400);
        }

        // Get storage path
        $storagePath = $handler->getStoragePath();
        $filename = "preview-" . $uid . "." . $extension;
        $relativePath = $storagePath . "/" . $filename;
        $fullPath = ROOT . $relativePath;

        // Delete old preview if exists
        if (!isEmpty($design->preview_image) && file_exists(ROOT . $design->preview_image)) {
            unlink(ROOT . $design->preview_image);
        }

        // Save image
        if (file_put_contents($fullPath, $imageData) === false) {
            Response()->jsonError("Kunne ikke gemme preview", [], 500);
        }

        // Update design
        $handler->setPreviewImage($uid, $relativePath);

        Response()->jsonSuccess("Preview opdateret", [
            'path' => $relativePath,
            'url' => __url($relativePath)
        ]);
    }

    /**
     * Generate QR code for a location
     */
    #[NoReturn] public static function generateQr(array $args): void {
        $locationUid = $args['location_uid'] ?? null;

        if (isEmpty($locationUid)) {
            Response()->jsonError("Manglende lokations ID", [], 400);
        }

        // Verify access
        $location = Methods::locations()->excludeForeignKeys()->get($locationUid);
        if (isEmpty($location) || $location->uuid !== __oUuid()) {
            Response()->jsonError("Lokation ikke fundet", [], 404);
        }

        // Get full location for slug
        $location = Methods::locations()->get($locationUid);

        // Generate QR code URL
        $qrUrl = __url("merchant/" . $location->slug);

        // Generate QR code as base64
        $qrHandler = Methods::qr()->build($qrUrl);
        $qrCode = base64_encode($qrHandler->get()->getString());

        Response()->jsonSuccess("QR kode genereret", [
            'url' => $qrUrl,
            'qr_base64' => $qrCode
        ]);
    }

    /**
     * Get inspiration images for A-Sign designs
     */
    #[NoReturn] public static function getInspiration(array $args): void {
        $type = $args['type'] ?? null;

        $handler = Methods::marketingInspiration();

        // Map type to category
        $category = null;
        if ($type === 'design') {
            $category = 'a_sign_design';
        } elseif ($type === 'arbitrary') {
            $category = 'a_sign_arbitrary';
        } elseif ($type === 'all' || isEmpty($type)) {
            // Get both a_sign categories
            $designItems = $handler->getActive('a_sign_design');
            $arbitraryItems = $handler->getActive('a_sign_arbitrary');
            // Also get legacy a_sign category for backwards compatibility
            $legacyItems = $handler->getActive('a_sign');

            $allItems = array_merge(
                $designItems->list(),
                $arbitraryItems->list(),
                $legacyItems->list()
            );

            $result = [];
            foreach ($allItems as $item) {
                $result[] = [
                    'uid' => $item->uid,
                    'title' => $item->title,
                    'category' => $item->category,
                    'description' => $item->description,
                    'image' => __url($item->image_path),
                ];
            }

            Response()->jsonSuccess("Inspiration hentet", ['items' => $result]);
            return;
        }

        $items = $handler->getActive($category);

        $result = [];
        foreach ($items->list() as $item) {
            $result[] = [
                'uid' => $item->uid,
                'title' => $item->title,
                'category' => $item->category,
                'description' => $item->description,
                'image' => __url($item->image_path),
            ];
        }

        Response()->jsonSuccess("Inspiration hentet", ['items' => $result]);
    }
}
