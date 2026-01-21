<?php

namespace routing\routes\admin;

use classes\Methods;
use JetBrains\PhpStorm\NoReturn;

class MarketingApiController {

    /**
     * Upload a new marketing template PDF
     */
    #[NoReturn] public static function uploadTemplate(array $args): void {
        if (!Methods::isAdmin()) {
            Response()->jsonError('Adgang naegtet', 403);
        }

        if (empty($args['__FILES']) || !isset($args['__FILES']['file'])) {
            Response()->jsonError("Ingen fil uploadet", [], 400);
        }

        $file = $args['__FILES']['file'];
        $name = trim($args['name'] ?? '') ?: pathinfo($file['name'], PATHINFO_FILENAME);
        $type = $args['type'] ?? 'A4';
        $description = isset($args['description']) ? trim($args['description']) : null;

        // Validate PDF
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            Response()->jsonError("Kun PDF filer er tilladt", [], 400);
        }

        // Validate file size (max 20MB)
        if ($file['size'] > 20 * 1024 * 1024) {
            Response()->jsonError("Filen er for stor. Maks 20MB", [], 400);
        }

        // Ensure upload directory exists
        $uploadDir = "public/content/marketing/templates/";
        if (!is_dir(ROOT . $uploadDir)) {
            mkdir(ROOT . $uploadDir, 0755, true);
        }

        // Generate unique filename
        $filename = "template-" . time() . "-" . uniqid() . ".pdf";
        $filePath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], ROOT . $filePath)) {
            Response()->jsonError("Kunne ikke gemme filen", [], 500);
        }

        // Generate preview image (first page) if Imagick is available
        $previewPath = self::generatePdfPreview($filePath);

        // Create template record
        $templateUid = Methods::marketingTemplates()->createTemplate(
            $name,
            $filePath,
            $type,
            $description,
            $previewPath,
            'DRAFT'
        );

        if (!$templateUid) {
            // Cleanup file on failure
            if (file_exists(ROOT . $filePath)) {
                unlink(ROOT . $filePath);
            }
            Response()->jsonError("Kunne ikke oprette template", [], 500);
        }

        Response()->jsonSuccess("Template uploadet", [
            "uid" => $templateUid,
            "preview" => $previewPath ? __url($previewPath) : null,
            "file_path" => $filePath,
        ]);
    }

    /**
     * Generate a preview image from the first page of a PDF
     */
    private static function generatePdfPreview(string $pdfPath): ?string {
        // Check if Imagick extension is available
        if (!extension_loaded('imagick')) {
            return null;
        }

        try {
            $imagick = new \Imagick();
            $imagick->setResolution(150, 150);
            $imagick->readImage(ROOT . $pdfPath . '[0]'); // First page only
            $imagick->setImageFormat('png');
            $imagick->setImageCompressionQuality(85);

            // Ensure preview directory exists
            $previewDir = "public/content/marketing/previews/";
            if (!is_dir(ROOT . $previewDir)) {
                mkdir(ROOT . $previewDir, 0755, true);
            }

            $previewFilename = pathinfo($pdfPath, PATHINFO_FILENAME) . ".png";
            $previewPath = $previewDir . $previewFilename;
            $imagick->writeImage(ROOT . $previewPath);
            $imagick->clear();
            $imagick->destroy();

            return $previewPath;
        } catch (\Exception $e) {
            debugLog("PDF preview generation failed: " . $e->getMessage(), "MARKETING_PREVIEW");
            return null;
        }
    }

    /**
     * Update template details
     */
    #[NoReturn] public static function updateTemplate(array $args): void {
        if (!Methods::isAdmin()) {
            Response()->jsonError('Adgang naegtet', 403);
        }

        $uid = $args['uid'] ?? null;
        if (isEmpty($uid)) {
            Response()->jsonError("Manglende template ID", [], 400);
        }

        $template = Methods::marketingTemplates()->get($uid);
        if (isEmpty($template)) {
            Response()->jsonError("Template ikke fundet", [], 404);
        }

        $updateData = [];
        if (isset($args['name']) && !isEmpty(trim($args['name']))) {
            $updateData['name'] = trim($args['name']);
        }
        if (isset($args['type'])) {
            $updateData['type'] = $args['type'];
        }
        if (isset($args['description'])) {
            $updateData['description'] = trim($args['description']);
        }
        if (isset($args['status'])) {
            $updateData['status'] = $args['status'];
        }

        if (empty($updateData)) {
            Response()->jsonError("Ingen data at opdatere", [], 400);
        }

        $updated = Methods::marketingTemplates()->updateTemplate($uid, $updateData);

        if (!$updated) {
            Response()->jsonError("Kunne ikke opdatere template", [], 500);
        }

        Response()->jsonSuccess("Template opdateret");
    }

    /**
     * Delete a template
     */
    #[NoReturn] public static function deleteTemplate(array $args): void {
        if (!Methods::isAdmin()) {
            Response()->jsonError('Adgang naegtet', 403);
        }

        $uid = $args['uid'] ?? null;
        if (isEmpty($uid)) {
            Response()->jsonError("Manglende template ID", [], 400);
        }

        $deleted = Methods::marketingTemplates()->deleteTemplate($uid);

        if (!$deleted) {
            Response()->jsonError("Kunne ikke slette template", [], 500);
        }

        Response()->jsonSuccess("Template slettet");
    }

    /**
     * Save placeholder positions for a template
     */
    #[NoReturn] public static function savePlaceholders(array $args): void {
        if (!Methods::isAdmin()) {
            Response()->jsonError('Adgang naegtet', 403);
        }

        $templateUid = $args['template_uid'] ?? null;
        $placeholders = $args['placeholders'] ?? [];

        if (isEmpty($templateUid)) {
            Response()->jsonError("Manglende template ID", [], 400);
        }

        $template = Methods::marketingTemplates()->get($templateUid);
        if (isEmpty($template)) {
            Response()->jsonError("Template ikke fundet", [], 404);
        }

        // Validate placeholders array
        if (!is_array($placeholders)) {
            Response()->jsonError("Ugyldige placeholder data", [], 400);
        }

        $saved = Methods::marketingPlaceholders()->savePlaceholders($templateUid, $placeholders);

        if (!$saved) {
            Response()->jsonError("Kunne ikke gemme placeholders", [], 500);
        }

        Response()->jsonSuccess("Placeholders gemt", [
            "count" => count($placeholders)
        ]);
    }

    /**
     * Get the PDF file for a template (for PDF.js viewer)
     */
    #[NoReturn] public static function getPdfFile(array $args): void {
        if (!Methods::isAdmin()) {
            Response()->jsonError('Adgang naegtet', 403);
        }

        $templateId = $args['id'] ?? null;
        if (isEmpty($templateId)) {
            Response()->jsonError("Manglende template ID", [], 400);
        }

        $template = Methods::marketingTemplates()->excludeForeignKeys()->get($templateId);
        if (isEmpty($template)) {
            Response()->jsonError("Template ikke fundet", [], 404);
        }

        $filePath = ROOT . $template->file_path;
        if (!file_exists($filePath)) {
            Response()->jsonError("PDF fil ikke fundet", [], 404);
        }

        // Serve the PDF file
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($template->file_path) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=3600');

        readfile($filePath);
        exit;
    }
}
