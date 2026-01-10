<?php

namespace classes\reports;

use classes\Methods;
use Database\Collection;
use Dompdf\Dompdf;
use Dompdf\Options;
use features\Settings;

class ReportExporter {

    private string $organisationId;
    private string $startDate;
    private string $endDate;
    private ?array $locationIds;
    private object $organisation;

    public function __construct(string $organisationId, string $startDate, string $endDate, ?array $locationIds = null) {
        $this->organisationId = $organisationId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->locationIds = $locationIds;
        $this->organisation = Methods::organisations()->get($organisationId);
    }

    /**
     * Get the reports directory path for this organisation
     */
    public function getReportsDir(): string {
        return ROOT . 'content/reports/' . $this->organisationId;
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

    /**
     * Generate CSV report and save to file
     * Returns the filename
     */
    public function generateCsv(): string {
        $this->ensureDirectoriesExist();

        $filename = 'rapport_' . $this->startDate . '_' . $this->endDate . '_' . time() . '.csv';
        $filepath = $this->getCsvDir() . '/' . $filename;

        $orders = $this->getOrders();
        $payments = $this->getCompletedPayments();
        $customerData = $this->getCustomerAggregates($orders, $payments);

        $handle = fopen($filepath, 'w');

        // UTF-8 BOM for Excel
        fwrite($handle, "\xEF\xBB\xBF");

        // Section 1: Orders
        fputcsv($handle, ["'=== ORDRER ==="], ';');
        fputcsv($handle, [
            'Ordre ID',
            'Dato',
            'Kunde',
            'Kunde Email',
            'Butik',
            'Beløb',
            'ISV Gebyr',
            'Netto',
            'Valuta',
            'Betalingsplan',
            'Status'
        ], ';');

        foreach ($orders->list() as $order) {
            $customerName = is_object($order->uuid) ? ($order->uuid->full_name ?? 'Ukendt') : 'Ukendt';
            $customerEmail = is_object($order->uuid) ? ($order->uuid->email ?? '') : '';
            $locationName = is_object($order->location) ? $order->location->name : 'Ukendt';
            $feeAmount = $order->fee_amount ?? 0;
            $netAmount = $order->amount - $feeAmount;

            fputcsv($handle, [
                $order->uid,
                date('d-m-Y H:i', strtotime($order->created_at)),
                $customerName,
                $customerEmail,
                $locationName,
                number_format($order->amount, 2, ',', '.'),
                number_format($feeAmount, 2, ',', '.'),
                number_format($netAmount, 2, ',', '.'),
                $order->currency ?? 'DKK',
                $this->translatePaymentPlan($order->payment_plan),
                $this->translateStatus($order->status)
            ], ';');
        }

        // Empty rows as separator
        fputcsv($handle, [], ';');
        fputcsv($handle, [], ';');

        // Section 2: Completed Payments
        fputcsv($handle, ["'=== GENNEMFØRTE BETALINGER ==="], ';');
        fputcsv($handle, [
            'Betaling ID',
            'Ordre ID',
            'Dato',
            'Kunde',
            'Beløb',
            'ISV Gebyr',
            'Valuta',
            'Rate Nr.',
            'Status'
        ], ';');

        foreach ($payments->list() as $payment) {
            $customerName = is_object($payment->uuid) ? ($payment->uuid->full_name ?? 'Ukendt') : 'Ukendt';
            $orderUid = is_object($payment->order) ? $payment->order->uid : ($payment->order ?? '');
            $isvAmount = $payment->isv_amount ?? 0;

            fputcsv($handle, [
                $payment->uid,
                $orderUid,
                date('d-m-Y H:i', strtotime($payment->paid_at ?? $payment->due_date)),
                $customerName,
                number_format($payment->amount, 2, ',', '.'),
                number_format($isvAmount, 2, ',', '.'),
                $payment->currency ?? 'DKK',
                $payment->installment_number ?? 1,
                $this->translatePaymentStatus($payment->status)
            ], ';');
        }

        // Empty rows as separator
        fputcsv($handle, [], ';');
        fputcsv($handle, [], ';');

        // Section 3: Customer Aggregates
        fputcsv($handle, ["'=== KUNDER ==="], ';');
        fputcsv($handle, [
            'Kunde ID',
            'Navn',
            'Email',
            'Total Ordrebeløb',
            'Total Betalt',
            'Total ISV Gebyr (Ordrer)',
            'Total ISV Gebyr (Betalinger)',
            'Antal Ordrer',
            'Antal Betalinger'
        ], ';');

        foreach ($customerData as $customer) {
            fputcsv($handle, [
                $customer['uid'],
                $customer['name'],
                $customer['email'],
                number_format($customer['total_order_amount'], 2, ',', '.'),
                number_format($customer['total_paid'], 2, ',', '.'),
                number_format($customer['total_order_isv'], 2, ',', '.'),
                number_format($customer['total_payment_isv'], 2, ',', '.'),
                $customer['order_count'],
                $customer['payment_count']
            ], ';');
        }

        // Summary section
        fputcsv($handle, [], ';');
        fputcsv($handle, [], ';');
        fputcsv($handle, ["'=== OPSUMMERING ==="], ';');

        $totalOrderAmount = $orders->reduce(fn($c, $i) => $c + $i['amount'], 0);
        $totalOrderIsv = $orders->reduce(fn($c, $i) => $c + ($i['fee_amount'] ?? 0), 0);
        $totalPaid = $payments->reduce(fn($c, $i) => $c + $i['amount'], 0);
        $totalPaymentIsv = $payments->reduce(fn($c, $i) => $c + ($i['isv_amount'] ?? 0), 0);

        fputcsv($handle, ['Periode', $this->startDate . ' til ' . $this->endDate], ';');
        fputcsv($handle, ['Antal ordrer', $orders->count()], ';');
        fputcsv($handle, ['Total ordrebeløb', number_format($totalOrderAmount, 2, ',', '.') . ' DKK'], ';');
        fputcsv($handle, ['Total ISV gebyr (ordrer)', number_format($totalOrderIsv, 2, ',', '.') . ' DKK'], ';');
        fputcsv($handle, ['Antal betalinger gennemført', $payments->count()], ';');
        fputcsv($handle, ['Total betalt', number_format($totalPaid, 2, ',', '.') . ' DKK'], ';');
        fputcsv($handle, ['Total ISV gebyr (betalinger)', number_format($totalPaymentIsv, 2, ',', '.') . ' DKK'], ';');
        fputcsv($handle, ['Antal unikke kunder', count($customerData)], ';');

        fclose($handle);

        return $filename;
    }

    /**
     * Generate PDF report and save to file
     * Returns the filename
     */
    public function generatePdf(): string {
        $this->ensureDirectoriesExist();

        $filename = 'rapport_' . $this->startDate . '_' . $this->endDate . '_' . time() . '.pdf';
        $filepath = $this->getPdfDir() . '/' . $filename;

        $orders = $this->getOrders();
        $payments = $this->getCompletedPayments();
        $allPayments = $this->getAllPayments();

        // Calculate KPIs
        $orderCount = $orders->count();
        $grossRevenue = $orders->reduce(fn($c, $i) => $c + $i['amount'], 0);
        $totalFees = $orders->reduce(fn($c, $i) => $c + ($i['fee_amount'] ?? 0), 0);
        $netRevenue = $grossRevenue - $totalFees;
        $orderAverage = $orderCount > 0 ? $grossRevenue / $orderCount : 0;

        // Unique customers
        $customerIds = [];
        foreach ($orders->list() as $order) {
            $customerId = is_object($order->uuid) ? $order->uuid->uid : $order->uuid;
            if ($customerId) $customerIds[$customerId] = true;
        }
        $customerCount = count($customerIds);

        // BNPL breakdown
        $bnplOrders = $orders->filter(fn($o) => !empty($o['payment_plan']) && in_array($o['payment_plan'], ['installments', 'pushed']));
        $fullPaymentOrders = $orders->filter(fn($o) => empty($o['payment_plan']) || $o['payment_plan'] === 'full');
        $bnplCount = $bnplOrders->count();
        $fullPaymentCount = $fullPaymentOrders->count();

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

        // Location breakdown
        $revenueByLocation = [];
        foreach ($orders->list() as $order) {
            $locUid = is_object($order->location) ? $order->location->uid : $order->location;
            $locName = is_object($order->location) ? $order->location->name : 'Ukendt';
            if (!isset($revenueByLocation[$locUid])) {
                $revenueByLocation[$locUid] = ['name' => $locName, 'revenue' => 0, 'orders' => 0, 'fees' => 0];
            }
            $revenueByLocation[$locUid]['revenue'] += $order->amount;
            $revenueByLocation[$locUid]['orders']++;
            $revenueByLocation[$locUid]['fees'] += $order->fee_amount ?? 0;
        }
        usort($revenueByLocation, fn($a, $b) => $b['revenue'] <=> $a['revenue']);

        // Generate HTML
        $html = $this->generatePdfHtml([
            'organisation' => $this->organisation,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'grossRevenue' => $grossRevenue,
            'netRevenue' => $netRevenue,
            'totalFees' => $totalFees,
            'orderCount' => $orderCount,
            'orderAverage' => $orderAverage,
            'customerCount' => $customerCount,
            'bnplCount' => $bnplCount,
            'fullPaymentCount' => $fullPaymentCount,
            'paymentsByStatus' => $paymentsByStatus,
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
     * Get orders for the period
     */
    private function getOrders(): Collection {
        $orderHandler = Methods::orders();
        $query = $orderHandler->queryBuilder()
            ->whereList(['organisation' => $this->organisationId, 'status' => 'COMPLETED'])
            ->whereTimeAfter('created_at', strtotime($this->startDate), '>=')
            ->whereTimeBefore('created_at', strtotime($this->endDate . ' +1 day'), '<=');

        if (!empty($this->locationIds)) {
            $query->where('location', $this->locationIds);
        }

        return $orderHandler->queryGetAll($query);
    }

    /**
     * Get completed payments for the period
     */
    private function getCompletedPayments(): Collection {
        $paymentsHandler = Methods::payments();
        $query = $paymentsHandler->queryBuilder()
            ->whereList(['organisation' => $this->organisationId, 'status' => 'COMPLETED'])
            ->whereTimeAfter('paid_at', strtotime($this->startDate), '>=')
            ->whereTimeBefore('paid_at', strtotime($this->endDate . ' +1 day'), '<=');

        if (!empty($this->locationIds)) {
            $query->where('location', $this->locationIds);
        }

        return $paymentsHandler->queryGetAll($query);
    }

    /**
     * Get all payments for the period (for status breakdown)
     */
    private function getAllPayments(): Collection {
        $paymentsHandler = Methods::payments();
        $filters = ['organisation' => $this->organisationId];

        if (!empty($this->locationIds)) {
            return $paymentsHandler->getByX($filters, ['amount', 'status'], ['location' => $this->locationIds]);
        }

        return $paymentsHandler->getByX($filters, ['amount', 'status']);
    }

    /**
     * Aggregate customer data from orders and payments
     */
    private function getCustomerAggregates(Collection $orders, Collection $payments): array {
        $customers = [];

        // Aggregate from orders
        foreach ($orders->list() as $order) {
            $customerId = is_object($order->uuid) ? $order->uuid->uid : $order->uuid;
            if (empty($customerId)) continue;

            if (!isset($customers[$customerId])) {
                $customers[$customerId] = [
                    'uid' => $customerId,
                    'name' => is_object($order->uuid) ? ($order->uuid->full_name ?? 'Ukendt') : 'Ukendt',
                    'email' => is_object($order->uuid) ? ($order->uuid->email ?? '') : '',
                    'total_order_amount' => 0,
                    'total_paid' => 0,
                    'total_order_isv' => 0,
                    'total_payment_isv' => 0,
                    'order_count' => 0,
                    'payment_count' => 0,
                ];
            }

            $customers[$customerId]['total_order_amount'] += $order->amount;
            $customers[$customerId]['total_order_isv'] += $order->fee_amount ?? 0;
            $customers[$customerId]['order_count']++;
        }

        // Aggregate from payments
        foreach ($payments->list() as $payment) {
            $customerId = is_object($payment->uuid) ? $payment->uuid->uid : $payment->uuid;
            if (empty($customerId)) continue;

            if (!isset($customers[$customerId])) {
                $customers[$customerId] = [
                    'uid' => $customerId,
                    'name' => is_object($payment->uuid) ? ($payment->uuid->full_name ?? 'Ukendt') : 'Ukendt',
                    'email' => is_object($payment->uuid) ? ($payment->uuid->email ?? '') : '',
                    'total_order_amount' => 0,
                    'total_paid' => 0,
                    'total_order_isv' => 0,
                    'total_payment_isv' => 0,
                    'order_count' => 0,
                    'payment_count' => 0,
                ];
            }

            $customers[$customerId]['total_paid'] += $payment->amount;
            $customers[$customerId]['total_payment_isv'] += $payment->isv_amount ?? 0;
            $customers[$customerId]['payment_count']++;
        }

        return array_values($customers);
    }

    /**
     * Translate payment plan to Danish
     */
    private function translatePaymentPlan(?string $plan): string {
        return match ($plan) {
            'installments' => 'Delbetaling',
            'pushed' => 'Udskudt',
            'full' => 'Fuld betaling',
            default => 'Fuld betaling'
        };
    }

    /**
     * Translate order status to Danish
     */
    private function translateStatus(string $status): string {
        return match ($status) {
            'COMPLETED' => 'Gennemført',
            'PENDING' => 'Afventer',
            'CANCELLED' => 'Annulleret',
            'DRAFT' => 'Kladde',
            default => $status
        };
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
        $orgName = $data['organisation']->name ?? BRAND_NAME;
        $startDate = date('d/m-Y', strtotime($data['startDate']));
        $endDate = date('d/m-Y', strtotime($data['endDate']));
        $generatedAt = date('d/m-Y H:i');

        $grossRevenue = number_format($data['grossRevenue'], 2, ',', '.');
        $netRevenue = number_format($data['netRevenue'], 2, ',', '.');
        $totalFees = number_format($data['totalFees'], 2, ',', '.');
        $orderAverage = number_format($data['orderAverage'], 2, ',', '.');

        // Location table rows
        $locationRows = '';
        foreach ($data['revenueByLocation'] as $loc) {
            $locRevenue = number_format($loc['revenue'], 2, ',', '.');
            $locFees = number_format($loc['fees'], 2, ',', '.');
            $locNet = number_format($loc['revenue'] - $loc['fees'], 2, ',', '.');
            $locationRows .= <<<HTML
            <tr>
                <td>{$loc['name']}</td>
                <td style="text-align: right;">{$loc['orders']}</td>
                <td style="text-align: right;">{$locRevenue} DKK</td>
                <td style="text-align: right;">{$locFees} DKK</td>
                <td style="text-align: right;">{$locNet} DKK</td>
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
    <title>Rapport - {$orgName}</title>
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
                <div class="logo">{$orgName}</div>
            </div>
            <div class="header-right">
                <div class="report-title">Salgsrapport</div>
                <div class="report-meta">
                    Periode: {$startDate} - {$endDate}<br>
                    Genereret: {$generatedAt}
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Nøgletal</div>
        <div class="kpi-grid">
            <div class="kpi-row">
                <div class="kpi-box">
                    <div class="kpi-label">Omsætning</div>
                    <div class="kpi-value">{$grossRevenue} DKK</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-label">Netto</div>
                    <div class="kpi-value">{$netRevenue} DKK</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-label">ISV Gebyr</div>
                    <div class="kpi-value">{$totalFees} DKK</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-label">Ordrer</div>
                    <div class="kpi-value">{$data['orderCount']}</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-label">Gns. ordre</div>
                    <div class="kpi-value">{$orderAverage} DKK</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-label">Kunder</div>
                    <div class="kpi-value">{$data['customerCount']}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Betalingsfordeling</div>
        <table>
            <tr>
                <td style="width: 50%;">
                    <strong>Betalingstype</strong><br>
                    Delbetaling (BNPL): {$data['bnplCount']} ordrer<br>
                    Fuld betaling: {$data['fullPaymentCount']} ordrer
                </td>
                <td style="width: 50%;">
                    <strong>Betalingsstatus</strong><br>
                    <span class="status-completed">Gennemført: {$completedCount} ({$completedAmount} DKK)</span><br>
                    <span class="status-scheduled">Planlagt: {$scheduledCount} ({$scheduledAmount} DKK)</span><br>
                    <span class="status-past-due">Forsinket: {$pastDueCount} ({$pastDueAmount} DKK)</span><br>
                    <span class="status-failed">Fejlet: {$failedCount} ({$failedAmount} DKK)</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Omsætning pr. butik</div>
        <table>
            <thead>
                <tr>
                    <th>Butik</th>
                    <th style="text-align: right;">Ordrer</th>
                    <th style="text-align: right;">Omsætning</th>
                    <th style="text-align: right;">ISV Gebyr</th>
                    <th style="text-align: right;">Netto</th>
                </tr>
            </thead>
            <tbody>
                {$locationRows}
            </tbody>
        </table>
    </div>

    <div class="footer">
        Genereret af WeePay &bull; {$generatedAt}
    </div>
</body>
</html>
HTML;
    }

    /**
     * Check if a file exists and return its path
     */
    public static function getFilePath(string $organisationId, string $type, string $filename): ?string {
        $basePath = ROOT . 'content/reports/' . $organisationId . '/' . $type . '/' . $filename;

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
