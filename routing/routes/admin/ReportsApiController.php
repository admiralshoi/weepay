<?php

namespace routing\routes\admin;

use classes\Methods;
use classes\reports\AdminReportExporter;
use JetBrains\PhpStorm\NoReturn;

class ReportsApiController {

    /**
     * Generate CSV report
     */
    #[NoReturn] public static function generateCsv(array $args): void {
        $startDate = $args['start'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $args['end'] ?? date('Y-m-d');
        $organisationId = (!empty($args['organisation']) && $args['organisation'] !== 'all') ? $args['organisation'] : null;
        $locationId = (!empty($args['location']) && $args['location'] !== 'all') ? $args['location'] : null;
        $groupBy = $args['group_by'] ?? 'none';

        // Validate group_by
        if (!in_array($groupBy, ['none', 'organisation', 'location'])) {
            $groupBy = 'none';
        }

        try {
            $exporter = new AdminReportExporter($startDate, $endDate, $organisationId, $locationId, $groupBy);
            $filename = $exporter->generateCsv();

            Response()->jsonSuccess("CSV-rapport genereret", [
                'filename' => $filename,
                'download_url' => __url(\classes\enumerations\Links::$api->admin->reports->download($filename))
            ]);
        } catch (\Exception $e) {
            debugLog($e->getMessage(), 'admin_csv_export_error');
            Response()->jsonError("Kunne ikke generere CSV-rapport: " . $e->getMessage(), [], 500);
        }
    }

    /**
     * Generate PDF report
     */
    #[NoReturn] public static function generatePdf(array $args): void {
        $startDate = $args['start'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $args['end'] ?? date('Y-m-d');
        $organisationId = (!empty($args['organisation']) && $args['organisation'] !== 'all') ? $args['organisation'] : null;
        $locationId = (!empty($args['location']) && $args['location'] !== 'all') ? $args['location'] : null;
        $groupBy = $args['group_by'] ?? 'none';

        // Validate group_by
        if (!in_array($groupBy, ['none', 'organisation', 'location'])) {
            $groupBy = 'none';
        }

        try {
            $exporter = new AdminReportExporter($startDate, $endDate, $organisationId, $locationId, $groupBy);
            $filename = $exporter->generatePdf();

            Response()->jsonSuccess("PDF-rapport genereret", [
                'filename' => $filename,
                'download_url' => __url(\classes\enumerations\Links::$api->admin->reports->download($filename))
            ]);
        } catch (\Exception $e) {
            debugLog($e->getMessage(), 'admin_pdf_export_error');
            Response()->jsonError("Kunne ikke generere PDF-rapport: " . $e->getMessage(), [], 500);
        }
    }

    /**
     * Download a generated report file
     */
    #[NoReturn] public static function downloadReport(array $args): void {
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
        $filepath = AdminReportExporter::getFilePath($type, $filename);

        if (!$filepath) {
            Response()->jsonError("Filen findes ikke.", [], 404);
        }

        // Get MIME type and serve file
        $mimeType = AdminReportExporter::getMimeType($filename);

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        readfile($filepath);
        exit;
    }

    /**
     * Get report stats via API (for AJAX updates)
     */
    #[NoReturn] public static function getStats(array $args): void {
        $startDate = $args['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $args['end_date'] ?? date('Y-m-d');
        $organisationId = (!empty($args['organisation']) && $args['organisation'] !== 'all') ? $args['organisation'] : null;
        $locationId = (!empty($args['location']) && $args['location'] !== 'all') ? $args['location'] : null;

        $startTimestamp = strtotime($startDate . ' 00:00:00');
        $endTimestamp = strtotime($endDate . ' 23:59:59');

        // Build payment query
        $paymentQuery = Methods::payments()->queryBuilder()
            ->where('status', 'COMPLETED')
            ->whereTimeAfter('paid_at', $startTimestamp, '>=')
            ->whereTimeBefore('paid_at', $endTimestamp, '<=');

        if ($organisationId) {
            $paymentQuery->where('organisation', $organisationId);
        }
        if ($locationId) {
            $paymentQuery->where('location', $locationId);
        }

        // Build order query
        $orderQuery = Methods::orders()->queryBuilder()
            ->where('status', 'COMPLETED')
            ->whereTimeAfter('created_at', $startTimestamp, '>=')
            ->whereTimeBefore('created_at', $endTimestamp, '<=');

        if ($organisationId) {
            $orderQuery->where('organisation', $organisationId);
        }
        if ($locationId) {
            $orderQuery->where('location', $locationId);
        }

        // Calculate KPIs
        $grossRevenue = (clone $paymentQuery)->sum('amount') ?? 0;
        $isvAmount = (clone $paymentQuery)->sum('isv_amount') ?? 0;
        $paymentCount = (clone $paymentQuery)->count();
        $orderCount = (clone $orderQuery)->count();
        $orderAverage = $orderCount > 0 ? $grossRevenue / $orderCount : 0;

        // Get unique customers
        $payments = Methods::payments()->queryGetAll($paymentQuery->select(['uuid']));
        $customerIds = [];
        foreach ($payments->list() as $p) {
            $customerId = is_object($p->uuid) ? $p->uuid->uid : $p->uuid;
            if ($customerId) $customerIds[$customerId] = true;
        }
        $customerCount = count($customerIds);

        // Daily chart data
        $dailyData = [];
        $currentDate = strtotime($startDate);
        $endDateTs = strtotime($endDate);

        while ($currentDate <= $endDateTs) {
            $dayStart = strtotime(date('Y-m-d', $currentDate) . ' 00:00:00');
            $dayEnd = strtotime(date('Y-m-d', $currentDate) . ' 23:59:59');

            $dayPaymentQuery = Methods::payments()->queryBuilder()
                ->where('status', 'COMPLETED')
                ->whereTimeAfter('paid_at', $dayStart, '>=')
                ->whereTimeBefore('paid_at', $dayEnd, '<=');

            if ($organisationId) {
                $dayPaymentQuery->where('organisation', $organisationId);
            }
            if ($locationId) {
                $dayPaymentQuery->where('location', $locationId);
            }

            $dayRevenue = (clone $dayPaymentQuery)->sum('amount') ?? 0;
            $dayIsv = (clone $dayPaymentQuery)->sum('isv_amount') ?? 0;
            $dayPayments = (clone $dayPaymentQuery)->count();

            $dailyData[] = [
                'date' => date('d/m', $currentDate),
                'revenue' => (float)$dayRevenue,
                'isv' => (float)$dayIsv,
                'payments' => (int)$dayPayments
            ];

            $currentDate = strtotime('+1 day', $currentDate);
        }

        // Payment status breakdown
        $allPaymentsQuery = Methods::payments()->queryBuilder()
            ->whereTimeAfter('created_at', $startTimestamp, '>=')
            ->whereTimeBefore('created_at', $endTimestamp, '<=');

        if ($organisationId) {
            $allPaymentsQuery->where('organisation', $organisationId);
        }
        if ($locationId) {
            $allPaymentsQuery->where('location', $locationId);
        }

        $allPayments = Methods::payments()->queryGetAll($allPaymentsQuery->select(['uid', 'amount', 'status']));

        $paymentsByStatus = [
            'COMPLETED' => ['count' => 0, 'amount' => 0],
            'SCHEDULED' => ['count' => 0, 'amount' => 0],
            'PAST_DUE' => ['count' => 0, 'amount' => 0],
            'PENDING' => ['count' => 0, 'amount' => 0],
            'FAILED' => ['count' => 0, 'amount' => 0],
        ];

        foreach ($allPayments->list() as $payment) {
            $status = $payment->status;
            if (isset($paymentsByStatus[$status])) {
                $paymentsByStatus[$status]['count']++;
                $paymentsByStatus[$status]['amount'] += $payment->amount;
            }
        }

        // Revenue by organisation (top 10)
        $orgPayments = Methods::payments()->queryGetAll(
            Methods::payments()->queryBuilder()
                ->where('status', 'COMPLETED')
                ->whereTimeAfter('paid_at', $startTimestamp, '>=')
                ->whereTimeBefore('paid_at', $endTimestamp, '<=')
        );

        $revenueByOrg = [];
        $organisationCache = []; // Cache organisation names to avoid repeated lookups
        foreach ($orgPayments->list() as $payment) {
            $orgId = is_object($payment->organisation) ? $payment->organisation->uid : $payment->organisation;

            // Skip if no organisation
            if (empty($orgId)) continue;

            // Get organisation name
            if (is_object($payment->organisation) && !empty($payment->organisation->name)) {
                $orgName = $payment->organisation->name;
            } else {
                // Organisation wasn't resolved as object or name is empty, fetch from cache or DB
                if (!isset($organisationCache[$orgId])) {
                    $org = Methods::organisations()->get($orgId);
                    $organisationCache[$orgId] = $org ? $org->name : 'Ukendt';
                }
                $orgName = $organisationCache[$orgId];
            }

            if (!isset($revenueByOrg[$orgId])) {
                $revenueByOrg[$orgId] = ['uid' => $orgId, 'name' => $orgName, 'revenue' => 0, 'isv' => 0, 'payments' => 0];
            }
            $revenueByOrg[$orgId]['revenue'] += $payment->amount;
            $revenueByOrg[$orgId]['isv'] += $payment->isv_amount ?? 0;
            $revenueByOrg[$orgId]['payments']++;
        }
        usort($revenueByOrg, fn($a, $b) => $b['revenue'] <=> $a['revenue']);
        $revenueByOrg = array_slice(array_values($revenueByOrg), 0, 10);

        // Revenue by location (top 10)
        $revenueByLocation = [];
        $locationCache = []; // Cache location names to avoid repeated lookups
        foreach ($orgPayments->list() as $payment) {
            $locId = is_object($payment->location) ? $payment->location->uid : $payment->location;

            // Skip if no location
            if (empty($locId)) continue;

            // Get location name
            if (is_object($payment->location) && !empty($payment->location->name)) {
                $locName = $payment->location->name;
            } else {
                // Location wasn't resolved as object or name is empty, fetch from cache or DB
                if (!isset($locationCache[$locId])) {
                    $loc = Methods::locations()->get($locId);
                    $locationCache[$locId] = $loc ? $loc->name : 'Ukendt';
                }
                $locName = $locationCache[$locId];
            }

            if (!isset($revenueByLocation[$locId])) {
                $revenueByLocation[$locId] = ['uid' => $locId, 'name' => $locName, 'revenue' => 0, 'isv' => 0, 'payments' => 0];
            }
            $revenueByLocation[$locId]['revenue'] += $payment->amount;
            $revenueByLocation[$locId]['isv'] += $payment->isv_amount ?? 0;
            $revenueByLocation[$locId]['payments']++;
        }
        usort($revenueByLocation, fn($a, $b) => $b['revenue'] <=> $a['revenue']);
        $revenueByLocation = array_slice(array_values($revenueByLocation), 0, 10);

        Response()->jsonSuccess('', [
            'gross_revenue' => (float)$grossRevenue,
            'isv_amount' => (float)$isvAmount,
            'net_revenue' => (float)($grossRevenue - $isvAmount),
            'order_count' => (int)$orderCount,
            'payment_count' => (int)$paymentCount,
            'order_average' => (float)$orderAverage,
            'customer_count' => (int)$customerCount,
            'daily_data' => $dailyData,
            'payments_by_status' => $paymentsByStatus,
            'revenue_by_org' => $revenueByOrg,
            'revenue_by_location' => $revenueByLocation,
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ]);
    }
}
