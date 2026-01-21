<?php

namespace routing\routes\merchants;

use classes\Methods;
use JetBrains\PhpStorm\NoReturn;

class MaterialsApiController {

    /**
     * Download a marketing template PDF customized for a location
     *
     * GET params:
     * - template_uid: The template to download
     * - location_uid: The location to customize for
     * - size: Output size (A5, A4, A3, original) - defaults to original
     */
    #[NoReturn] public static function download(array $args): void {
        $templateUid = $args['template_uid'] ?? null;
        $locationUid = $args['location_uid'] ?? null;
        $size = $args['size'] ?? 'original';

        if (isEmpty($templateUid)) {
            Response()->jsonError("Manglende template ID", [], 400);
        }

        if (isEmpty($locationUid)) {
            Response()->jsonError("Manglende lokations ID", [], 400);
        }

        // Get template
        $template = Methods::marketingTemplates()->get($templateUid);
        if (isEmpty($template)) {
            Response()->jsonError("Template ikke fundet", [], 404);
        }

        // Only allow ACTIVE templates for merchants
        if ($template->status !== 'ACTIVE') {
            Response()->jsonError("Template er ikke tilgængelig", [], 403);
        }

        // Only allow downloadable templates (not a_sign_base)
        if ($template->category === 'a_sign_base') {
            Response()->jsonError("Denne template kan ikke downloades", [], 403);
        }

        // Get location and verify access
        $location = Methods::locations()->excludeForeignKeys()->get($locationUid);
        if (isEmpty($location)) {
            Response()->jsonError("Lokation ikke fundet", [], 404);
        }

        // Verify the location belongs to the current organisation
        if ($location->uuid !== __oUuid()) {
            Response()->jsonError("Du har ikke adgang til denne lokation", [], 403);
        }

        // Re-fetch with foreign keys for PDF generation (needs org data for logo)
        $location = Methods::locations()->get($locationUid);

        // Validate size parameter
        $validSizes = ['A5', 'A4', 'A3', 'original'];
        if (!in_array($size, $validSizes)) {
            $size = 'original';
        }

        // Generate PDF
        $pdfGenerator = Methods::marketingPdfGenerator();
        $pdfContent = $pdfGenerator
            ->setTemplate($template)
            ->setLocation($location)
            ->setSize($size)
            ->generate();

        if (!$pdfContent) {
            Response()->jsonError("Kunne ikke generere PDF", [], 500);
        }

        // Generate filename
        $locationSlug = $location->slug ?? 'location';
        $templateName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $template->name);
        $filename = "{$locationSlug}_{$templateName}_{$size}.pdf";

        // Output PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdfContent));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $pdfContent;
        exit;
    }

    /**
     * Get available templates for download
     */
    #[NoReturn] public static function getTemplates(array $args): void {
        // Get only active, downloadable templates
        $templates = Methods::marketingTemplates()
            ->excludeForeignKeys()
            ->getActiveTemplates();

        $result = [];
        foreach ($templates->list() as $template) {
            $result[] = [
                'uid' => $template->uid,
                'name' => $template->name,
                'type' => $template->type,
                'description' => $template->description,
                'preview_image' => $template->preview_image ? __url($template->preview_image) : null,
            ];
        }

        Response()->jsonSuccess("Templates hentet", ['templates' => $result]);
    }

    /**
     * Get inspiration gallery items
     */
    #[NoReturn] public static function getInspiration(array $args): void {
        $category = $args['category'] ?? null;

        $inspirationHandler = Methods::marketingInspiration();

        if ($category && $category !== 'all') {
            $items = $inspirationHandler->getByX(['status' => 'ACTIVE', 'category' => $category]);
        } else {
            $items = $inspirationHandler->getActive();
        }

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
