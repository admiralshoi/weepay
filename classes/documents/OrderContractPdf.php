<?php

namespace classes\documents;

use classes\Methods;
use Database\Collection;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Generates BNPL order contract PDFs
 * Creates legally-binding contract documents for installment and pushed payment orders
 */
class OrderContractPdf {

    private object $order;
    private ?object $customer;
    private ?object $organisation;
    private ?object $location;
    private ?Collection $payments;
    private ?Collection $basketItems;
    private string $currency;

    public function __construct(object $order) {
        $this->order = $order;
        $this->currency = $order->currency ?? 'DKK';

        // Resolve organisation
        if (is_object($order->organisation)) {
            $this->organisation = $order->organisation;
        } elseif (!isEmpty($order->organisation)) {
            $this->organisation = Methods::organisations()->get($order->organisation);
        } else {
            $this->organisation = null;
        }

        // Resolve customer
        if (is_object($order->uuid)) {
            $this->customer = $order->uuid;
        } elseif (!isEmpty($order->uuid)) {
            $this->customer = Methods::users()->get($order->uuid);
        } else {
            $this->customer = null;
        }

        // Resolve location
        if (is_object($order->location)) {
            $this->location = $order->location;
        } elseif (!isEmpty($order->location)) {
            $this->location = Methods::locations()->excludeForeignKeys()->get($order->location);
        } else {
            $this->location = null;
        }

        // Fetch payments for this order
        $this->payments = Methods::payments()->excludeForeignKeys()->getByXOrderBy(
            'due_date', 'ASC', ['order' => $order->uid]
        );

        // Fetch basket items
        $this->basketItems = $this->fetchBasketItems();
    }

    /**
     * Fetch basket items for the order's terminal session
     */
    private function fetchBasketItems(): ?Collection {
        if (isEmpty($this->order)) {
            return null;
        }

        $terminalSessionUid = null;
        if (is_object($this->order->terminal_session)) {
            $terminalSessionUid = $this->order->terminal_session->uid;
        } elseif (is_string($this->order->terminal_session) && !isEmpty($this->order->terminal_session)) {
            $terminalSessionUid = $this->order->terminal_session;
        }

        if (isEmpty($terminalSessionUid)) {
            return null;
        }

        return Methods::checkoutBasket()->excludeForeignKeys()->getByX([
            'terminal_session' => $terminalSessionUid,
            'status' => 'FULFILLED'
        ]);
    }

    /**
     * Create from order UID
     */
    public static function fromOrderUid(string $orderUid): ?self {
        $order = Methods::orders()->get($orderUid);
        if (isEmpty($order)) {
            return null;
        }
        return new self($order);
    }

    /**
     * Generate PDF as string
     */
    public function generatePdfString(): string {
        $dompdf = $this->createDompdf();
        $dompdf->loadHtml($this->generateHtml());
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Download the PDF
     */
    public function download(?string $filename = null): void {
        $filename = $filename ?? $this->getDefaultFilename();

        $dompdf = $this->createDompdf();
        $dompdf->loadHtml($this->generateHtml());
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $dompdf->stream($filename, ['Attachment' => true]);
    }

    /**
     * View PDF inline
     */
    public function view(): void {
        $dompdf = $this->createDompdf();
        $dompdf->loadHtml($this->generateHtml());
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $dompdf->stream($this->getDefaultFilename(), ['Attachment' => false]);
    }

    /**
     * Save to file
     */
    public function saveToFile(string $filepath): bool {
        return file_put_contents($filepath, $this->generatePdfString()) !== false;
    }

    /**
     * Get default filename
     */
    public function getDefaultFilename(): string {
        $date = date('Y-m-d', strtotime($this->order->created_at));
        return "kontrakt_{$this->order->uid}_{$date}.pdf";
    }

    /**
     * Create Dompdf instance
     */
    private function createDompdf(): Dompdf {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        return new Dompdf($options);
    }

    /**
     * Get currency symbol
     */
    private function getCurrencySymbol(): string {
        $symbols = [
            'DKK' => 'kr.',
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'SEK' => 'kr.',
            'NOK' => 'kr.'
        ];
        return $symbols[$this->currency] ?? $this->currency;
    }

    /**
     * Format amount with Danish number format
     */
    private function formatAmount(float $amount): string {
        return number_format($amount, 2, ',', '.');
    }

    /**
     * Generate the HTML for the contract
     */
    private function generateHtml(): string {
        $order = $this->order;
        $customer = $this->customer;
        $organisation = $this->organisation;
        $location = $this->location;

        // Format values
        $orderAmount = $this->formatAmount(orderAmount($order));
        $currencySymbol = $this->getCurrencySymbol();
        $orderDate = date('d/m-Y H:i', strtotime($order->created_at));
        $contractDate = date('d/m-Y H:i');

        // Location info (primary party in contract)
        $locationName = $this->getProperty($location, 'name')
            ?? $this->getProperty($organisation, 'name')
            ?? BRAND_NAME;
        $locationAddress = $this->formatLocationAddress();
        $locationContact = $this->formatLocationContact();

        // Organisation CVR
        $orgCvr = $this->getProperty($organisation, 'cvr') ?? '';

        // Customer info
        $customerName = $this->getProperty($customer, 'full_name') ?? 'Ukendt';
        $customerEmail = $this->getProperty($customer, 'email') ?? '';
        $customerPhone = $this->getProperty($customer, 'phone') ?? '';

        // Get CPR/NIN from AuthOidc record
        $customerCpr = '';
        if ($customer) {
            $customerUid = $this->getProperty($customer, 'uid');
            if ($customerUid) {
                $authOidc = Methods::oidcAuthentication()->excludeForeignKeys()->getFirst(['user' => $customerUid]);
                if ($authOidc) {
                    $customerCpr = $this->getProperty($authOidc, 'nin') ?? '';
                }
            }
        }

        // Order info
        $orderCaption = $this->getProperty($order, 'caption') ?? '';

        // Payment plan info
        $paymentPlanLabel = $this->getPaymentPlanLabel($order->payment_plan);
        $paymentCount = $this->payments ? $this->payments->count() : 0;

        // Line items HTML
        $lineItemsHtml = $this->generateLineItemsHtml();

        // Payment schedule HTML
        $paymentScheduleHtml = $this->generatePaymentScheduleHtml();

        return <<<HTML
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Kontrakt - {$order->uid}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #333;
            padding: 30px 40px;
        }
        .header {
            margin-bottom: 25px;
            border-bottom: 2px solid #0066cc;
            padding-bottom: 15px;
        }
        .header-table {
            width: 100%;
        }
        .logo {
            font-size: 20px;
            font-weight: bold;
            color: #0066cc;
        }
        .document-title {
            font-size: 22px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .document-subtitle {
            color: #666;
            font-size: 10px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
        }
        .info-table {
            width: 100%;
            margin-bottom: 15px;
        }
        .info-table td {
            padding: 4px 0;
            vertical-align: top;
        }
        .info-table .label {
            color: #666;
            font-size: 10px;
            width: 120px;
        }
        .info-table .value {
            font-weight: 500;
        }
        .parties-table {
            width: 100%;
            margin-bottom: 15px;
        }
        .parties-table td {
            width: 50%;
            vertical-align: top;
            padding-right: 15px;
        }
        .party-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 12px;
        }
        .party-title {
            font-weight: bold;
            font-size: 11px;
            color: #333;
            margin-bottom: 8px;
        }
        .amount-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .schedule-table th {
            background: #f8f9fa;
            padding: 8px 6px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            color: #666;
            border-bottom: 2px solid #dee2e6;
        }
        .schedule-table th:last-child {
            text-align: right;
        }
        .schedule-table td {
            padding: 8px 6px;
            border-bottom: 1px solid #eee;
            font-size: 10px;
        }
        .schedule-table td:last-child {
            text-align: right;
        }
        .schedule-table .status-pending {
            color: #856404;
        }
        .schedule-table .status-completed {
            color: #155724;
        }
        .terms-box {
            background: #fff8e6;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 12px;
            margin-bottom: 15px;
        }
        .terms-box h4 {
            font-size: 11px;
            font-weight: bold;
            color: #856404;
            margin-bottom: 8px;
        }
        .terms-box ul {
            margin-left: 15px;
            font-size: 10px;
        }
        .terms-box li {
            margin-bottom: 5px;
        }
        .legal-box {
            background: #e8f4fd;
            border: 1px solid #0066cc;
            border-radius: 5px;
            padding: 12px;
            margin-bottom: 15px;
        }
        .legal-box h4 {
            font-size: 11px;
            font-weight: bold;
            color: #0066cc;
            margin-bottom: 8px;
        }
        .legal-box p {
            font-size: 9px;
            color: #333;
            margin-bottom: 5px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #999;
            font-size: 9px;
        }
        .acceptance-box {
            background: #f0f8ff;
            border: 1px solid #0066cc;
            border-radius: 5px;
            padding: 10px 15px;
            margin-top: 20px;
            font-size: 10px;
        }
        .acceptance-box strong {
            color: #0066cc;
        }
        .line-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .line-items-table th {
            background: #f8f9fa;
            padding: 6px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            color: #666;
            border-bottom: 1px solid #dee2e6;
        }
        .line-items-table th:last-child {
            text-align: right;
        }
        .line-items-table td {
            padding: 6px;
            border-bottom: 1px solid #eee;
            font-size: 10px;
        }
        .line-items-table td:last-child {
            text-align: right;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 50%;">
                    <div class="logo">{$locationName}</div>
                    <div style="font-size: 9px; color: #666; margin-top: 5px;">
                        {$locationAddress}
                        {$orgCvr}
                    </div>
                </td>
                <td style="width: 50%; text-align: right;">
                    <div class="document-title">BNPL AFTALE</div>
                    <div class="document-subtitle">Ordre: {$order->uid}</div>
                    <div class="document-subtitle">Dato: {$orderDate}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Parties Section -->
    <div class="section">
        <div class="section-title">PARTER</div>
        <table class="parties-table">
            <tr>
                <td>
                    <div class="party-box">
                        <div class="party-title">Køber</div>
                        <table class="info-table">
                            <tr>
                                <td class="label">Navn</td>
                                <td class="value">{$customerName}</td>
                            </tr>
                            <tr>
                                <td class="label">CPR-nr.</td>
                                <td class="value">{$customerCpr}</td>
                            </tr>
                            <tr>
                                <td class="label">Email</td>
                                <td class="value">{$customerEmail}</td>
                            </tr>
                            <tr>
                                <td class="label">Telefon</td>
                                <td class="value">{$customerPhone}</td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td>
                    <div class="party-box">
                        <div class="party-title">Sælger</div>
                        <table class="info-table">
                            <tr>
                                <td class="label">Virksomhed</td>
                                <td class="value">{$locationName}</td>
                            </tr>
                            <tr>
                                <td class="label">CVR</td>
                                <td class="value">{$orgCvr}</td>
                            </tr>
                            <tr>
                                <td class="label">Kontakt</td>
                                <td class="value">{$locationContact}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Order Details -->
    <div class="section">
        <div class="section-title">ORDREDETALJER</div>
        <div class="amount-box">
            {$lineItemsHtml}
            <table style="width: 100%; margin-top: 10px; border-top: 2px solid #dee2e6; padding-top: 10px;">
                <tr>
                    <td style="font-weight: bold; font-size: 14px;">Total</td>
                    <td style="text-align: right; font-weight: bold; font-size: 14px;">{$orderAmount} {$currencySymbol}</td>
                </tr>
            </table>
        </div>
        <table class="info-table">
            <tr>
                <td class="label">Betalingsplan</td>
                <td class="value">{$paymentPlanLabel} ({$paymentCount} rater)</td>
            </tr>
            <tr>
                <td class="label">Beskrivelse</td>
                <td class="value">{$orderCaption}</td>
            </tr>
        </table>
    </div>

    <!-- Payment Schedule -->
    <div class="section">
        <div class="section-title">BETALINGSPLAN</div>
        {$paymentScheduleHtml}
    </div>

    <!-- Important Notice -->
    <div class="notice-box" style="background: #e8f4fd; border: 1px solid #0066cc; border-radius: 5px; padding: 12px; margin-bottom: 15px;">
        <p style="font-size: 10px; color: #0066cc; margin: 0;"><strong>Bemærk:</strong> Dette dokument afspejler det oprindelige ordrebeløb og inkluderer ikke eventuelle gebyrer, der måtte opstå ved forsinket betaling. Eventuelle rykkergebyrer eller andre omkostninger vil fremgå af separate dokumenter.</p>
    </div>

    <!-- Terms & Conditions -->
    <div class="terms-box">
        <h4>VILKÅR OG BETINGELSER</h4>
        <ul>
            <li>Denne aftale indgås direkte mellem dig og <strong>{$locationName}</strong>.</li>
            <li><strong>{$locationName}</strong> yder kreditten - WeePay er ikke kreditgiver og foretager ikke kreditvurdering.</li>
            <li>Ved forsinket betaling kan der pålægges rykkergebyrer af <strong>{$locationName}</strong> iht. deres betalingsbetingelser.</li>
            <li>Eventuelle renter, gebyrer eller konsekvenser ved manglende betaling fastsættes af <strong>{$locationName}</strong>.</li>
            <li>Maksimal betalingshenstand er 90 dage fra købsdato.</li>
            <li>Ved spørgsmål eller tvister kontakt venligst <strong>{$locationName}</strong> direkte.</li>
        </ul>
    </div>

    <!-- Legal Disclaimer -->
    <div class="legal-box">
        <h4>JURIDISK INFORMATION</h4>
        <p><strong>WeePay fungerer udelukkende som teknisk betalingsformidler.</strong></p>
        <p>WeePay er ikke kreditgiver, foretager ikke kreditvurdering og er ikke part i denne aftale.</p>
        <p>Aftalen er bindende mellem køber ({$customerName}) og sælger ({$locationName}).</p>
        <p>For yderligere information om WeePays rolle, se wee-pay.dk/policies/consumer/bnpl</p>
    </div>

    <!-- Acceptance -->
    <div class="acceptance-box">
        <strong>Aftale accepteret:</strong> {$orderDate}<br>
        Denne kontrakt blev accepteret elektronisk ved gennemførelse af køb hos {$locationName}.
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Dokument genereret: {$contractDate}</p>
        <p>Kontrakt ID: {$order->uid}</p>
        <p style="margin-top: 8px;">Teknisk platform: WeePay | wee-pay.dk</p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Format location address
     */
    private function formatLocationAddress(): string {
        if (!$this->organisation) {
            return '';
        }

        // Try to get address from organisation's company_address (JSON decoded) or individual fields
        $companyAddress = $this->getProperty($this->organisation, 'company_address');

        if ($companyAddress && is_object($companyAddress)) {
            $parts = array_filter([
                $this->getProperty($companyAddress, 'line_1'),
                trim(($this->getProperty($companyAddress, 'postal_code') ?? '') . ' ' . ($this->getProperty($companyAddress, 'city') ?? '')),
            ]);
            if (!empty($parts)) {
                return implode(', ', $parts);
            }
        }

        // Fallback to individual fields
        $parts = array_filter([
            $this->getProperty($this->organisation, 'address_street'),
            trim(($this->getProperty($this->organisation, 'address_zip') ?? '') . ' ' . ($this->getProperty($this->organisation, 'address_city') ?? '')),
        ]);

        return implode(', ', $parts);
    }

    /**
     * Format location contact info
     */
    private function formatLocationContact(): string {
        $parts = [];

        // Check for email - location first, then organisation
        $email = null;
        if ($this->location) {
            $email = $this->getProperty($this->location, 'email')
                ?? $this->getProperty($this->location, 'contact_email');
        }
        if (isEmpty($email) && $this->organisation) {
            $email = $this->getProperty($this->organisation, 'contact_email')
                ?? $this->getProperty($this->organisation, 'primary_email');
        }
        if (!isEmpty($email)) {
            $parts[] = $email;
        }

        // Check for phone - location first, then organisation
        $phone = null;
        if ($this->location) {
            $phone = $this->getProperty($this->location, 'phone')
                ?? $this->getProperty($this->location, 'contact_phone');
        }
        if (isEmpty($phone) && $this->organisation) {
            $phone = $this->getProperty($this->organisation, 'contact_phone');
        }
        if (!isEmpty($phone)) {
            $parts[] = $phone;
        }

        return implode(' / ', $parts);
    }

    /**
     * Safely get a property from an object without triggering warnings
     */
    private function getProperty(?object $obj, string $property): mixed {
        if (!$obj) {
            return null;
        }
        return isset($obj->$property) ? $obj->$property : null;
    }

    /**
     * Get payment plan label in Danish
     */
    private function getPaymentPlanLabel(?string $plan): string {
        return match ($plan) {
            'installments' => 'Afdragsordning',
            'pushed' => 'Udskudt betaling',
            'direct' => 'Direkte betaling',
            default => 'Betalingsaftale'
        };
    }

    /**
     * Generate HTML for line items
     */
    private function generateLineItemsHtml(): string {
        $currencySymbol = $this->getCurrencySymbol();

        if (isEmpty($this->basketItems) || $this->basketItems->count() === 0) {
            $caption = $this->order->caption ?? 'Køb';
            $amount = $this->formatAmount(orderAmount($this->order));

            return <<<HTML
            <table class="line-items-table">
                <thead>
                    <tr>
                        <th>Beskrivelse</th>
                        <th>Beløb</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{$caption}</td>
                        <td>{$amount} {$currencySymbol}</td>
                    </tr>
                </tbody>
            </table>
HTML;
        }

        $rows = '';
        foreach ($this->basketItems->list() as $item) {
            $name = htmlspecialchars($item->name ?? 'Vare');
            $price = $this->formatAmount((float)($item->price ?? 0));
            $rows .= "<tr><td>{$name}</td><td>{$price} {$currencySymbol}</td></tr>";
        }

        return <<<HTML
        <table class="line-items-table">
            <thead>
                <tr>
                    <th>Vare</th>
                    <th>Beløb</th>
                </tr>
            </thead>
            <tbody>
                {$rows}
            </tbody>
        </table>
HTML;
    }

    /**
     * Generate payment schedule HTML
     */
    private function generatePaymentScheduleHtml(): string {
        if (!$this->payments || $this->payments->count() === 0) {
            return '<p style="color: #666; font-size: 10px;">Ingen betalinger registreret endnu.</p>';
        }

        $currencySymbol = $this->getCurrencySymbol();
        $rows = '';

        foreach ($this->payments->list() as $payment) {
            $number = $payment->installment_number ?? '-';
            $dueDate = date('d/m-Y', strtotime($payment->due_date));
            $amount = $this->formatAmount((float)$payment->amount);

            $status = $payment->status;
            $statusLabel = match ($status) {
                'COMPLETED' => 'Betalt',
                'PENDING', 'SCHEDULED' => 'Afventer',
                'PAST_DUE' => 'Forsinket',
                'REFUNDED' => 'Refunderet',
                'VOIDED' => 'Annulleret',
                default => $status
            };

            $statusClass = in_array($status, ['COMPLETED']) ? 'status-completed' : 'status-pending';

            $rows .= <<<HTML
                <tr>
                    <td>{$number}</td>
                    <td>{$dueDate}</td>
                    <td>{$amount} {$currencySymbol}</td>
                    <td class="{$statusClass}">{$statusLabel}</td>
                </tr>
HTML;
        }

        return <<<HTML
        <table class="schedule-table">
            <thead>
                <tr>
                    <th>Rate</th>
                    <th>Forfaldsdato</th>
                    <th>Beløb</th>
                    <th style="text-align: left;">Status</th>
                </tr>
            </thead>
            <tbody>
                {$rows}
            </tbody>
        </table>
HTML;
    }

    /**
     * Get the order object
     */
    public function getOrder(): object {
        return $this->order;
    }
}
