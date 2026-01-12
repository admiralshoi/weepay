<?php

namespace classes\reports;

use classes\Methods;
use Database\Collection;
use Dompdf\Dompdf;
use Dompdf\Options;

class AdminReportExporter {

    private string $startDate;
    private string $endDate;
    private ?string $organisationId;
    private ?string $locationId;
    private string $groupBy; // 'none', 'organisation', 'location'

    public function __construct(
        string $startDate,
        string $endDate,
        ?string $organisationId = null,
        ?string $locationId = null,
        string $groupBy = 'none'
    ) {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->organisationId = $organisationId;
        $this->locationId = $locationId;
        $this->groupBy = $groupBy;
    }

    /**
     * Get the admin reports directory path
     */
    public function getReportsDir(): string {
        return ROOT . 'content/reports/admin';
    }

    /**
     * Get the CSV directory path
     */
    public function getCsvDir(): string {
        return $this->getReportsDir() . '/csv';
    }

    /**
     * Get the PDF directory path
     */
    public function getPdfDir(): string {
        return $this->getReportsDir() . '/pdf';
    }

    /**
     * Ensure directories exist
     */
    private function ensureDirectoriesExist(): void {
        $dirs = [$this->getReportsDir(), $this->getCsvDir(), $this->getPdfDir()];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    // Cache for organisation and location lookups
    private array $organisationCache = [];
    private array $locationCache = [];

    /**
     * Get organisation name by ID (with caching)
     */
    private function getOrganisationName(?string $orgId): string {
        if (isEmpty($orgId)) return 'Ukendt';

        if (!isset($this->organisationCache[$orgId])) {
            $org = Methods::organisations()->get($orgId);
            $this->organisationCache[$orgId] = $org ? ($org->name ?? 'Ukendt') : 'Ukendt';
        }
        return $this->organisationCache[$orgId];
    }

    /**
     * Get organisation CVR by ID (with caching)
     */
    private function getOrganisationCvr(?string $orgId): string {
        if (isEmpty($orgId)) return '';

        if (!isset($this->organisationCache[$orgId . '_cvr'])) {
            $org = Methods::organisations()->get($orgId);
            $this->organisationCache[$orgId . '_cvr'] = $org ? ($org->cvr ?? '') : '';
        }
        return $this->organisationCache[$orgId . '_cvr'];
    }

    /**
     * Get location name by ID (with caching)
     */
    private function getLocationName(?string $locId): string {
        if (isEmpty($locId)) return 'Ukendt';

        if (!isset($this->locationCache[$locId])) {
            $loc = Methods::locations()->get($locId);
            $this->locationCache[$locId] = $loc ? ($loc->name ?? 'Ukendt') : 'Ukendt';
        }
        return $this->locationCache[$locId];
    }

    /**
     * Generate CSV report and save to file
     * Returns the filename
     */
    public function generateCsv(): string {
        $this->ensureDirectoriesExist();

        $filename = 'admin_rapport_' . $this->startDate . '_' . $this->endDate . '_' . time() . '.csv';
        $filepath = $this->getCsvDir() . '/' . $filename;

        $payments = $this->getCompletedPayments();
        $orders = $this->getCompletedOrders();

        $handle = fopen($filepath, 'w');

        // UTF-8 BOM for Excel
        fwrite($handle, "\xEF\xBB\xBF");

        // Section 1: Summary
        fputcsv($handle, ["'=== PLATFORM OPSUMMERING ==="], ';');

        // Payment stats (actually received)
        $totalPaymentRevenue = $payments->reduce(fn($c, $i) => $c + $i['amount'], 0);
        $totalPaymentIsv = $payments->reduce(fn($c, $i) => $c + ($i['isv_amount'] ?? 0), 0);
        $totalPayments = $payments->count();

        // Order stats (expected/generated)
        $totalOrderRevenue = $orders->reduce(fn($c, $i) => $c + $i['amount'], 0);
        $totalOrderIsv = $orders->reduce(fn($c, $i) => $c + ($i['fee_amount'] ?? 0), 0);
        $totalOrders = $orders->count();

        fputcsv($handle, ['Periode', $this->startDate . ' til ' . $this->endDate], ';');
        fputcsv($handle, [], ';');
        fputcsv($handle, ["'--- Betalinger (faktisk modtaget) ---"], ';');
        fputcsv($handle, ['Betalings omsætning', number_format($totalPaymentRevenue, 2, ',', '.') . ' DKK'], ';');
        fputcsv($handle, ['Betalings ISV (netto salg)', number_format($totalPaymentIsv, 2, ',', '.') . ' DKK'], ';');
        fputcsv($handle, ['Antal betalinger', $totalPayments], ';');
        fputcsv($handle, [], ';');
        fputcsv($handle, ["'--- Ordrer (forventet) ---"], ';');
        fputcsv($handle, ['Ordre omsætning', number_format($totalOrderRevenue, 2, ',', '.') . ' DKK'], ';');
        fputcsv($handle, ['Ordre ISV (forventet netto)', number_format($totalOrderIsv, 2, ',', '.') . ' DKK'], ';');
        fputcsv($handle, ['Antal ordrer', $totalOrders], ';');
        fputcsv($handle, [], ';');
        fputcsv($handle, [], ';');

        // Section 2: By Organisation
        if ($this->groupBy === 'organisation' || $this->groupBy === 'none') {
            fputcsv($handle, ["'=== PR. ORGANISATION ==="], ';');
            fputcsv($handle, [
                'Organisation',
                'CVR',
                'Antal ordrer',
                'Antal betalinger',
                'Omsætning',
                'ISV (Netto salg)',
                'Valuta'
            ], ';');

            $byOrg = $this->aggregateByOrganisation($payments, $orders);
            foreach ($byOrg as $org) {
                fputcsv($handle, [
                    $org['name'],
                    $org['cvr'],
                    $org['order_count'],
                    $org['payment_count'],
                    number_format($org['revenue'], 2, ',', '.'),
                    number_format($org['isv'], 2, ',', '.'),
                    'DKK'
                ], ';');
            }

            fputcsv($handle, [], ';');
            fputcsv($handle, [], ';');
        }

        // Section 3: By Location
        if ($this->groupBy === 'location' || $this->groupBy === 'none') {
            fputcsv($handle, ["'=== PR. LOKATION ==="], ';');
            fputcsv($handle, [
                'Lokation',
                'Organisation',
                'Antal ordrer',
                'Antal betalinger',
                'Omsætning',
                'ISV (Netto salg)',
                'Valuta'
            ], ';');

            $byLoc = $this->aggregateByLocation($payments, $orders);
            foreach ($byLoc as $loc) {
                fputcsv($handle, [
                    $loc['name'],
                    $loc['org_name'],
                    $loc['order_count'],
                    $loc['payment_count'],
                    number_format($loc['revenue'], 2, ',', '.'),
                    number_format($loc['isv'], 2, ',', '.'),
                    'DKK'
                ], ';');
            }

            fputcsv($handle, [], ';');
            fputcsv($handle, [], ';');
        }

        // Section 4: All Payments
        fputcsv($handle, ["'=== ALLE BETALINGER ==="], ';');
        fputcsv($handle, [
            'Betaling ID',
            'Ordre ID',
            'Dato',
            'Organisation',
            'Lokation',
            'Kunde',
            'Beløb',
            'ISV Gebyr',
            'Valuta',
            'Status'
        ], ';');

        foreach ($payments->list() as $payment) {
            $customerName = is_object($payment->uuid) ? ($payment->uuid->full_name ?? 'Ukendt') : 'Ukendt';
            $orderUid = is_object($payment->order) ? $payment->order->uid : ($payment->order ?? '');

            // Resolve organisation name
            $orgId = is_object($payment->organisation) ? $payment->organisation->uid : $payment->organisation;
            $orgName = is_object($payment->organisation) && !isEmpty($payment->organisation->name)
                ? $payment->organisation->name
                : $this->getOrganisationName($orgId);

            // Resolve location name
            $locId = is_object($payment->location) ? $payment->location->uid : $payment->location;
            $locName = is_object($payment->location) && !isEmpty($payment->location->name)
                ? $payment->location->name
                : $this->getLocationName($locId);

            fputcsv($handle, [
                $payment->uid,
                $orderUid,
                date('d-m-Y H:i', strtotime($payment->paid_at ?? $payment->due_date)),
                $orgName,
                $locName,
                $customerName,
                number_format($payment->amount, 2, ',', '.'),
                number_format($payment->isv_amount ?? 0, 2, ',', '.'),
                $payment->currency ?? 'DKK',
                $this->translatePaymentStatus($payment->status)
            ], ';');
        }

        fclose($handle);

        return $filename;
    }

    /**
     * Generate PDF report and save to file
     * Returns the filename
     */
    public function generatePdf(): string {
        $this->ensureDirectoriesExist();

        $filename = 'admin_rapport_' . $this->startDate . '_' . $this->endDate . '_' . time() . '.pdf';
        $filepath = $this->getPdfDir() . '/' . $filename;

        $payments = $this->getCompletedPayments();
        $orders = $this->getCompletedOrders();
        $allPayments = $this->getAllPayments();

        // Payment KPIs (actually received)
        $paymentRevenue = $payments->reduce(fn($c, $i) => $c + $i['amount'], 0);
        $paymentIsv = $payments->reduce(fn($c, $i) => $c + ($i['isv_amount'] ?? 0), 0);
        $paymentCount = $payments->count();

        // Order KPIs (expected/generated)
        $orderRevenue = $orders->reduce(fn($c, $i) => $c + $i['amount'], 0);
        $orderIsv = $orders->reduce(fn($c, $i) => $c + ($i['fee_amount'] ?? 0), 0);
        $orderCount = $orders->count();
        $orderAverage = $orderCount > 0 ? $orderRevenue / $orderCount : 0;

        // Unique customers and organisations
        $customerIds = [];
        $orgIds = [];
        foreach ($payments->list() as $payment) {
            $customerId = is_object($payment->uuid) ? $payment->uuid->uid : $payment->uuid;
            $orgId = is_object($payment->organisation) ? $payment->organisation->uid : $payment->organisation;
            if ($customerId) $customerIds[$customerId] = true;
            if ($orgId) $orgIds[$orgId] = true;
        }
        $customerCount = count($customerIds);
        $organisationCount = count($orgIds);

        // Payment status breakdown
        $paymentsByStatus = [
            'COMPLETED' => ['count' => 0, 'amount' => 0],
            'SCHEDULED' => ['count' => 0, 'amount' => 0],
            'PAST_DUE' => ['count' => 0, 'amount' => 0],
            'FAILED' => ['count' => 0, 'amount' => 0],
        ];
        foreach ($allPayments->list() as $payment) {
            $status = $payment->status;
            if (isset($paymentsByStatus[$status])) {
                $paymentsByStatus[$status]['count']++;
                $paymentsByStatus[$status]['amount'] += $payment->amount;
            }
        }

        // By organisation
        $revenueByOrg = $this->aggregateByOrganisation($payments, $orders);

        // By location
        $revenueByLocation = $this->aggregateByLocation($payments, $orders);

        // Generate HTML
        $html = $this->generatePdfHtml([
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'paymentRevenue' => $paymentRevenue,
            'paymentIsv' => $paymentIsv,
            'paymentCount' => $paymentCount,
            'orderRevenue' => $orderRevenue,
            'orderIsv' => $orderIsv,
            'orderCount' => $orderCount,
            'orderAverage' => $orderAverage,
            'customerCount' => $customerCount,
            'organisationCount' => $organisationCount,
            'paymentsByStatus' => $paymentsByStatus,
            'revenueByOrg' => $revenueByOrg,
            'revenueByLocation' => $revenueByLocation,
        ]);

        // Generate PDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        file_put_contents($filepath, $dompdf->output());

        return $filename;
    }

    /**
     * Get completed orders for the period
     */
    private function getCompletedOrders(): Collection {
        $query = Methods::orders()->queryBuilder()
            ->where('status', 'COMPLETED')
            ->whereTimeAfter('created_at', strtotime($this->startDate), '>=')
            ->whereTimeBefore('created_at', strtotime($this->endDate . ' 23:59:59'), '<=');

        if ($this->organisationId) {
            $query->where('organisation', $this->organisationId);
        }
        if ($this->locationId) {
            $query->where('location', $this->locationId);
        }

        return Methods::orders()->queryGetAll($query);
    }

    /**
     * Get completed payments for the period
     */
    private function getCompletedPayments(): Collection {
        $query = Methods::payments()->queryBuilder()
            ->where('status', 'COMPLETED')
            ->whereTimeAfter('paid_at', strtotime($this->startDate), '>=')
            ->whereTimeBefore('paid_at', strtotime($this->endDate . ' 23:59:59'), '<=');

        if ($this->organisationId) {
            $query->where('organisation', $this->organisationId);
        }
        if ($this->locationId) {
            $query->where('location', $this->locationId);
        }

        return Methods::payments()->queryGetAll($query);
    }

    /**
     * Get all payments for the period (for status breakdown)
     */
    private function getAllPayments(): Collection {
        $query = Methods::payments()->queryBuilder()
            ->whereTimeAfter('created_at', strtotime($this->startDate), '>=')
            ->whereTimeBefore('created_at', strtotime($this->endDate . ' 23:59:59'), '<=');

        if ($this->organisationId) {
            $query->where('organisation', $this->organisationId);
        }
        if ($this->locationId) {
            $query->where('location', $this->locationId);
        }

        return Methods::payments()->queryGetAll($query->select(['uid', 'amount', 'isv_amount', 'status']));
    }

    /**
     * Aggregate data by organisation
     */
    private function aggregateByOrganisation(Collection $payments, Collection $orders): array {
        $byOrg = [];

        // From payments
        foreach ($payments->list() as $payment) {
            $orgId = is_object($payment->organisation) ? $payment->organisation->uid : $payment->organisation;

            // Skip if no organisation
            if (isEmpty($orgId)) continue;

            // Resolve organisation name and CVR
            $orgName = is_object($payment->organisation) && !isEmpty($payment->organisation->name)
                ? $payment->organisation->name
                : $this->getOrganisationName($orgId);
            $cvr = is_object($payment->organisation) && !isEmpty($payment->organisation->cvr)
                ? $payment->organisation->cvr
                : $this->getOrganisationCvr($orgId);

            if (!isset($byOrg[$orgId])) {
                $byOrg[$orgId] = [
                    'name' => $orgName,
                    'cvr' => $cvr,
                    'revenue' => 0,
                    'isv' => 0,
                    'order_count' => 0,
                    'payment_count' => 0,
                ];
            }

            $byOrg[$orgId]['revenue'] += $payment->amount;
            $byOrg[$orgId]['isv'] += $payment->isv_amount ?? 0;
            $byOrg[$orgId]['payment_count']++;
        }

        // Count orders
        foreach ($orders->list() as $order) {
            $orgId = is_object($order->organisation) ? $order->organisation->uid : $order->organisation;
            if (isset($byOrg[$orgId])) {
                $byOrg[$orgId]['order_count']++;
            }
        }

        usort($byOrg, fn($a, $b) => $b['revenue'] <=> $a['revenue']);
        return array_values($byOrg);
    }

    /**
     * Aggregate data by location
     */
    private function aggregateByLocation(Collection $payments, Collection $orders): array {
        $byLoc = [];

        // From payments
        foreach ($payments->list() as $payment) {
            $locId = is_object($payment->location) ? $payment->location->uid : $payment->location;

            // Skip if no location
            if (isEmpty($locId)) continue;

            // Resolve location name
            $locName = is_object($payment->location) && !isEmpty($payment->location->name)
                ? $payment->location->name
                : $this->getLocationName($locId);

            // Resolve organisation name
            $orgId = is_object($payment->organisation) ? $payment->organisation->uid : $payment->organisation;
            $orgName = is_object($payment->organisation) && !isEmpty($payment->organisation->name)
                ? $payment->organisation->name
                : $this->getOrganisationName($orgId);

            if (!isset($byLoc[$locId])) {
                $byLoc[$locId] = [
                    'name' => $locName,
                    'org_name' => $orgName,
                    'revenue' => 0,
                    'isv' => 0,
                    'order_count' => 0,
                    'payment_count' => 0,
                ];
            }

            $byLoc[$locId]['revenue'] += $payment->amount;
            $byLoc[$locId]['isv'] += $payment->isv_amount ?? 0;
            $byLoc[$locId]['payment_count']++;
        }

        // Count orders
        foreach ($orders->list() as $order) {
            $locId = is_object($order->location) ? $order->location->uid : $order->location;
            if (isset($byLoc[$locId])) {
                $byLoc[$locId]['order_count']++;
            }
        }

        usort($byLoc, fn($a, $b) => $b['revenue'] <=> $a['revenue']);
        return array_values($byLoc);
    }

    /**
     * Translate payment status to Danish
     */
    private function translatePaymentStatus(string $status): string {
        return match ($status) {
            'COMPLETED' => 'Gennemført',
            'SCHEDULED' => 'Planlagt',
            'PAST_DUE' => 'Forsinket',
            'FAILED' => 'Fejlet',
            default => $status
        };
    }

    /**
     * Generate PDF HTML content
     */
    private function generatePdfHtml(array $data): string {
        $startDate = date('d/m-Y', strtotime($data['startDate']));
        $endDate = date('d/m-Y', strtotime($data['endDate']));
        $generatedAt = date('d/m-Y H:i');

        // Payment stats (actually received)
        $paymentRevenue = number_format($data['paymentRevenue'], 2, ',', '.');
        $paymentIsv = number_format($data['paymentIsv'], 2, ',', '.');

        // Order stats (expected)
        $orderRevenue = number_format($data['orderRevenue'], 2, ',', '.');
        $orderIsv = number_format($data['orderIsv'], 2, ',', '.');
        $orderAverage = number_format($data['orderAverage'], 2, ',', '.');

        // Organisation table rows
        $orgRows = '';
        foreach (array_slice($data['revenueByOrg'], 0, 15) as $org) {
            $orgRevenue = number_format($org['revenue'], 2, ',', '.');
            $orgIsv = number_format($org['isv'], 2, ',', '.');
            $orgRows .= <<<HTML
            <tr>
                <td>{$org['name']}</td>
                <td style="text-align: right;">{$org['order_count']}</td>
                <td style="text-align: right;">{$org['payment_count']}</td>
                <td style="text-align: right;">{$orgRevenue} DKK</td>
                <td style="text-align: right;">{$orgIsv} DKK</td>
            </tr>
HTML;
        }

        // Location table rows
        $locRows = '';
        foreach (array_slice($data['revenueByLocation'], 0, 15) as $loc) {
            $locRevenue = number_format($loc['revenue'], 2, ',', '.');
            $locIsv = number_format($loc['isv'], 2, ',', '.');
            $locRows .= <<<HTML
            <tr>
                <td>{$loc['name']}</td>
                <td>{$loc['org_name']}</td>
                <td style="text-align: right;">{$loc['payment_count']}</td>
                <td style="text-align: right;">{$locRevenue} DKK</td>
                <td style="text-align: right;">{$locIsv} DKK</td>
            </tr>
HTML;
        }

        // Payment status rows
        $completedCount = $data['paymentsByStatus']['COMPLETED']['count'];
        $completedAmount = number_format($data['paymentsByStatus']['COMPLETED']['amount'], 2, ',', '.');
        $scheduledCount = $data['paymentsByStatus']['SCHEDULED']['count'];
        $scheduledAmount = number_format($data['paymentsByStatus']['SCHEDULED']['amount'], 2, ',', '.');
        $pastDueCount = $data['paymentsByStatus']['PAST_DUE']['count'];
        $pastDueAmount = number_format($data['paymentsByStatus']['PAST_DUE']['amount'], 2, ',', '.');
        $failedCount = $data['paymentsByStatus']['FAILED']['count'];
        $failedAmount = number_format($data['paymentsByStatus']['FAILED']['amount'], 2, ',', '.');

        return <<<HTML
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Admin Platform Rapport</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #333;
            padding: 30px;
        }
        .header {
            border-bottom: 2px solid #0066cc;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header-flex {
            display: table;
            width: 100%;
        }
        .header-left, .header-right {
            display: table-cell;
            vertical-align: top;
        }
        .header-right {
            text-align: right;
        }
        .logo {
            font-size: 22px;
            font-weight: bold;
            color: #0066cc;
        }
        .report-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 5px;
        }
        .report-meta {
            color: #666;
            font-size: 10px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #0066cc;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e0e0e0;
        }
        .kpi-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .kpi-row {
            display: table-row;
        }
        .kpi-box {
            display: table-cell;
            width: 16.66%;
            padding: 10px;
            text-align: center;
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
        }
        .kpi-label {
            font-size: 9px;
            color: #666;
            margin-bottom: 3px;
        }
        .kpi-value {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: #f8f9fa;
            font-weight: bold;
            font-size: 10px;
            color: #666;
        }
        .status-completed { color: #28a745; }
        .status-scheduled { color: #ffc107; }
        .status-past-due { color: #dc3545; }
        .status-failed { color: #6c757d; }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
            color: #666;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-flex">
            <div class="header-left">
                <div class="logo">WeePay Admin</div>
            </div>
            <div class="header-right">
                <div class="report-title">Platform Rapport</div>
                <div class="report-meta">
                    Periode: {$startDate} - {$endDate}<br>
                    Genereret: {$generatedAt}
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Betalinger (Faktisk Modtaget)</div>
        <div class="kpi-grid">
            <div class="kpi-row">
                <div class="kpi-box">
                    <div class="kpi-label">Betalings Omsætning</div>
                    <div class="kpi-value">{$paymentRevenue} DKK</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-label">Betalings ISV</div>
                    <div class="kpi-value">{$paymentIsv} DKK</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-label">Antal Betalinger</div>
                    <div class="kpi-value">{$data['paymentCount']}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Ordrer (Forventet)</div>
        <div class="kpi-grid">
            <div class="kpi-row">
                <div class="kpi-box">
                    <div class="kpi-label">Ordre Omsætning</div>
                    <div class="kpi-value">{$orderRevenue} DKK</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-label">Ordre ISV</div>
                    <div class="kpi-value">{$orderIsv} DKK</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-label">Antal Ordrer</div>
                    <div class="kpi-value">{$data['orderCount']}</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-label">Gns. Ordre</div>
                    <div class="kpi-value">{$orderAverage} DKK</div>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Oversigt</div>
        <div class="kpi-grid">
            <div class="kpi-row">
                <div class="kpi-box">
                    <div class="kpi-label">Organisationer</div>
                    <div class="kpi-value">{$data['organisationCount']}</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-label">Kunder</div>
                    <div class="kpi-value">{$data['customerCount']}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Betalingsstatus</div>
        <table>
            <tr>
                <td style="width: 100%;">
                    <span class="status-completed">Gennemført: {$completedCount} ({$completedAmount} DKK)</span><br>
                    <span class="status-scheduled">Planlagt: {$scheduledCount} ({$scheduledAmount} DKK)</span><br>
                    <span class="status-past-due">Forsinket: {$pastDueCount} ({$pastDueAmount} DKK)</span><br>
                    <span class="status-failed">Fejlet: {$failedCount} ({$failedAmount} DKK)</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Top Organisationer</div>
        <table>
            <thead>
                <tr>
                    <th>Organisation</th>
                    <th style="text-align: right;">Ordrer</th>
                    <th style="text-align: right;">Betalinger</th>
                    <th style="text-align: right;">Omsætning</th>
                    <th style="text-align: right;">ISV</th>
                </tr>
            </thead>
            <tbody>
                {$orgRows}
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Top Lokationer</div>
        <table>
            <thead>
                <tr>
                    <th>Lokation</th>
                    <th>Organisation</th>
                    <th style="text-align: right;">Betalinger</th>
                    <th style="text-align: right;">Omsætning</th>
                    <th style="text-align: right;">ISV</th>
                </tr>
            </thead>
            <tbody>
                {$locRows}
            </tbody>
        </table>
    </div>

    <div class="footer">
        Genereret af WeePay Admin &bull; {$generatedAt}
    </div>
</body>
</html>
HTML;
    }

    /**
     * Check if a file exists and return its path
     */
    public static function getFilePath(string $type, string $filename): ?string {
        $basePath = ROOT . 'content/reports/admin/' . $type . '/' . $filename;

        if (file_exists($basePath)) {
            return $basePath;
        }

        return null;
    }

    /**
     * Get MIME type for file
     */
    public static function getMimeType(string $filename): string {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return match ($ext) {
            'csv' => 'text/csv',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream'
        };
    }
}
