<?php

namespace routing\routes\merchants;

use classes\app\OrganisationPermissions;
use classes\Methods;
use classes\reports\ReportExporter;
use features\Settings;
use JetBrains\PhpStorm\NoReturn;

class ReportsApiController {

    /**
     * Generate CSV report
     */
    #[NoReturn] public static function generateCsv(array $args): void {
        // Check permission
        if (!OrganisationPermissions::__oRead('organisation', 'reports')) {
            Response()->jsonError("Du har ikke tilladelse til at generere rapporter.", [], 403);
        }

        $organisationId = __oUuid();
        if (isEmpty($organisationId)) {
            Response()->jsonError("Ingen organisation valgt.", [], 400);
        }

        $startDate = $args['start'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $args['end'] ?? date('Y-m-d');

        // Get location IDs if scoped
        $locationIds = Methods::locations()->userLocationPredicate();

        try {
            $exporter = new ReportExporter($organisationId, $startDate, $endDate, $locationIds ?: null);
            $filename = $exporter->generateCsv();

            Response()->jsonSuccess("CSV-rapport genereret", [
                'filename' => $filename,
                'download_url' => __url(\classes\enumerations\Links::$api->organisation->reports->download($filename))
            ]);
        } catch (\Exception $e) {
            debugLog($e->getMessage(), 'csv_export_error');
            Response()->jsonError("Kunne ikke generere CSV-rapport: " . $e->getMessage(), [], 500);
        }
    }

    /**
     * Generate PDF report
     */
    #[NoReturn] public static function generatePdf(array $args): void {
        // Check permission
        if (!OrganisationPermissions::__oRead('organisation', 'reports')) {
            Response()->jsonError("Du har ikke tilladelse til at generere rapporter.", [], 403);
        }

        $organisationId = __oUuid();
        if (isEmpty($organisationId)) {
            Response()->jsonError("Ingen organisation valgt.", [], 400);
        }

        $startDate = $args['start'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $args['end'] ?? date('Y-m-d');

        // Get location IDs if scoped
        $locationIds = Methods::locations()->userLocationPredicate();

        try {
            $exporter = new ReportExporter($organisationId, $startDate, $endDate, $locationIds ?: null);
            $filename = $exporter->generatePdf();

            Response()->jsonSuccess("PDF-rapport genereret", [
                'filename' => $filename,
                'download_url' => __url(\classes\enumerations\Links::$api->organisation->reports->download($filename))
            ]);
        } catch (\Exception $e) {
            debugLog($e->getMessage(), 'pdf_export_error');
            Response()->jsonError("Kunne ikke generere PDF-rapport: " . $e->getMessage(), [], 500);
        }
    }

    /**
     * Download a generated report file
     */
    #[NoReturn] public static function downloadReport(array $args): void {
        // Check permission
        if (!OrganisationPermissions::__oRead('organisation', 'reports')) {
            Response()->jsonError("Du har ikke tilladelse til at downloade rapporter.", [], 403);
        }

        $organisationId = __oUuid();
        if (isEmpty($organisationId)) {
            Response()->jsonError("Ingen organisation valgt.", [], 400);
        }

        $filename = $args['filename'] ?? '';
        if (isEmpty($filename)) {
            Response()->jsonError("Filnavn mangler.", [], 400);
        }

        // Sanitize filename to prevent directory traversal
        $filename = basename($filename);

        // Determine file type from extension
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $type = match ($ext) {
            'csv' => 'csv',
            'pdf' => 'pdf',
            default => null
        };

        if (!$type) {
            Response()->jsonError("Ugyldig filtype.", [], 400);
        }

        // Get file path
        $filepath = ReportExporter::getFilePath($organisationId, $type, $filename);

        if (!$filepath) {
            Response()->jsonError("Filen findes ikke.", [], 404);
        }

        // Get MIME type and serve file
        $mimeType = ReportExporter::getMimeType($filename);

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        readfile($filepath);
        exit;
    }
}
