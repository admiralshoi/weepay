<?php

namespace classes\documents;

use classes\enumerations\Links;
use classes\Methods;
use Dompdf\Dompdf;
use Dompdf\Options;
use features\Settings;

/**
 * Generates Rykker (dunning notice) PDFs
 * Creates payment reminder documents for overdue BNPL payments
 */
class RykkerPdf {

    private object $payment;
    private int $level;
    private ?object $order;
    private ?object $customer;
    private ?object $organisation;
    private ?object $location;
    private string $currency;

    public function __construct(object $payment, int $level) {
        $this->payment = $payment;
        $this->level = max(1, min(3, $level)); // Ensure level is 1-3
        $this->currency = $payment->currency ?? 'DKK';

        // Resolve order
        if (is_object($payment->order)) {
            $this->order = $payment->order;
        } elseif (!isEmpty($payment->order)) {
            $this->order = Methods::orders()->excludeForeignKeys()->get($payment->order);
        } else {
            $this->order = null;
        }

        // Resolve organisation
        if (is_object($payment->organisation)) {
            $this->organisation = $payment->organisation;
        } elseif (!isEmpty($payment->organisation)) {
            $this->organisation = Methods::organisations()->get($payment->organisation);
        } else {
            $this->organisation = null;
        }

        // Resolve customer
        if (is_object($payment->uuid)) {
            $this->customer = $payment->uuid;
        } elseif (!isEmpty($payment->uuid)) {
            $this->customer = Methods::users()->get($payment->uuid);
        } else {
            $this->customer = null;
        }

        // Resolve location
        if (is_object($payment->location)) {
            $this->location = $payment->location;
        } elseif (!isEmpty($payment->location)) {
            $this->location = Methods::locations()->excludeForeignKeys()->get($payment->location);
        } else {
            $this->location = null;
        }
    }

    /**
     * Create from payment UID and level
     */
    public static function fromPaymentUid(string $paymentUid, int $level): ?self {
        $payment = Methods::payments()->get($paymentUid);
        if (isEmpty($payment)) {
            return null;
        }
        return new self($payment, $level);
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
        $date = date('Y-m-d');
        return "rykker{$this->level}_{$this->payment->uid}_{$date}.pdf";
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
     * Get title based on rykker level
     */
    private function getTitle(): string {
        return match ($this->level) {
            1 => '1. BETALINGSPÅMINDELSE',
            2 => '2. BETALINGSPÅMINDELSE',
            3 => 'INKASSOVARSEL',
            default => 'BETALINGSPÅMINDELSE'
        };
    }

    /**
     * Get legal statement text based on level
     */
    private function getLegalStatementText(): string {
        $locationName = $this->getProperty($this->location, 'name')
            ?? $this->getProperty($this->organisation, 'name')
            ?? BRAND_NAME;

        return match ($this->level) {
            1 => "Dette dokument bekræfter, at nedenstående betaling til {$locationName} ikke blev betalt rettidigt, og at et rykkergebyr er pålagt i henhold til gældende betalingsbetingelser.",
            2 => "Dette dokument bekræfter, at nedenstående betaling til {$locationName} fortsat er udestående efter første rykker, og at yderligere rykkergebyr er pålagt.",
            3 => "Dette dokument bekræfter, at nedenstående fordring til {$locationName} er i væsentlig misligholdelse. Sagen kan uden yderligere varsel overdrages til inkasso.",
            default => "Dette dokument bekræfter udestående betaling til {$locationName}."
        };
    }

    /**
     * Calculate days overdue
     */
    private function getDaysOverdue(): int {
        $dueDate = strtotime($this->payment->due_date);
        $today = strtotime(date('Y-m-d'));
        $diff = $today - $dueDate;
        return max(0, (int)floor($diff / 86400));
    }

    /**
     * Generate the HTML for the rykker notice
     */
    private function generateHtml(): string {
        $payment = $this->payment;
        $order = $this->order;
        $customer = $this->customer;
        $organisation = $this->organisation;
        $location = $this->location;

        // Format values - payment->amount already includes rykker_fee
        // So we subtract to get the original amount
        $rykkerFee = (float)($payment->rykker_fee ?? 0);
        $totalAmount = (float)$payment->amount; // This is the total (original + fee)
        $originalAmount = $totalAmount - $rykkerFee; // Original amount before fees

        $originalFormatted = $this->formatAmount($originalAmount);
        $totalFormatted = $this->formatAmount($totalAmount);
        $rykkerFeeFormatted = $this->formatAmount($rykkerFee);
        $currencySymbol = $this->getCurrencySymbol();

        $documentDate = date('d/m-Y');
        $dueDate = date('d/m-Y', strtotime($payment->due_date));

        // Location info
        $locationName = $this->getProperty($location, 'name')
            ?? $this->getProperty($organisation, 'name')
            ?? BRAND_NAME;
        $locationAddress = $this->formatLocationAddress();
        $locationContact = $this->formatLocationContact();
        $orgCvr = $this->getProperty($organisation, 'cvr') ?? '';

        // Customer info
        $customerName = $this->getProperty($customer, 'full_name') ?? 'Ukendt';
        $customerUid = $this->getProperty($customer, 'uid') ?? '';

        // Get CPR/NIN from AuthOidc record
        $customerNin = '';
        if ($customer) {
            $uid = $this->getProperty($customer, 'uid');
            if ($uid) {
                $authOidc = Methods::oidcAuthentication()->excludeForeignKeys()->getFirst(['user' => $uid]);
                if ($authOidc) {
                    $customerNin = $this->getProperty($authOidc, 'nin') ?? '';
                }
            }
        }

        // Order info
        $orderUid = $this->getProperty($order, 'uid') ?? 'Ukendt';

        // Title and legal statement based on level
        $title = $this->getTitle();
        $legalStatement = $this->getLegalStatementText();

        // Level 3 specific warning
        $collectionWarningHtml = '';
        if ($this->level >= 3) {
            $collectionWarningHtml = <<<HTML
            <div class="collection-warning">
                <strong>INKASSOVARSEL</strong>
                <p>Denne fordring kan uden yderligere varsel overdrages til inkasso, hvilket vil medføre betydelige ekstraomkostninger.</p>
            </div>
HTML;
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Rykker {$this->level} - {$payment->uid}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.6;
            color: #333;
            padding: 40px;
        }
        .header {
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #dc3545;
        }
        .header-table {
            width: 100%;
        }
        .logo {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .document-title {
            font-size: 20px;
            font-weight: bold;
            color: #dc3545;
            margin-bottom: 5px;
        }
        .document-date {
            color: #666;
            font-size: 10px;
        }
        .recipient-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 25px;
        }
        .recipient-label {
            font-size: 10px;
            color: #666;
            margin-bottom: 5px;
        }
        .recipient-name {
            font-weight: bold;
            font-size: 13px;
        }
        .intro-text {
            margin-bottom: 25px;
            font-size: 11px;
            line-height: 1.8;
        }
        .amount-box {
            background: #fff8f8;
            border: 2px solid #dc3545;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .amount-table {
            width: 100%;
        }
        .amount-table td {
            padding: 8px 0;
            border-bottom: 1px solid #f0d0d0;
        }
        .amount-table tr:last-child td {
            border-bottom: none;
            padding-top: 15px;
        }
        .amount-table .label {
            color: #666;
        }
        .amount-table .value {
            text-align: right;
            font-weight: 500;
        }
        .amount-table .total-label {
            font-size: 14px;
            font-weight: bold;
            color: #dc3545;
        }
        .amount-table .total-value {
            font-size: 16px;
            font-weight: bold;
            color: #dc3545;
            text-align: right;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-table {
            width: 100%;
        }
        .info-table td {
            padding: 5px 0;
            vertical-align: top;
        }
        .info-table .label {
            color: #666;
            font-size: 10px;
            width: 130px;
        }
        .info-table .value {
            font-weight: 500;
        }
        .fee-warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 10px;
            color: #856404;
        }
        .collection-warning {
            background: #f8d7da;
            border: 2px solid #dc3545;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .collection-warning strong {
            color: #721c24;
            font-size: 12px;
            display: block;
            margin-bottom: 8px;
        }
        .collection-warning p {
            font-size: 10px;
            color: #721c24;
            margin-bottom: 5px;
        }
        .payment-link-box {
            background: #d4edda;
            border: 1px solid #28a745;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        .payment-link-box strong {
            color: #155724;
            font-size: 12px;
        }
        .payment-link-box a {
            color: #155724;
            word-break: break-all;
            font-size: 10px;
        }
        .contact-box {
            background: #e8f4fd;
            border: 1px solid #0066cc;
            border-radius: 5px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 10px;
        }
        .contact-box strong {
            color: #0066cc;
        }
        .legal-disclaimer {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 9px;
            color: #666;
        }
        .legal-disclaimer p {
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
                        {$locationAddress}<br>
                        CVR: {$orgCvr}
                    </div>
                </td>
                <td style="width: 50%; text-align: right;">
                    <div class="document-title">{$title}</div>
                    <div class="document-date">Dokumentdato: {$documentDate}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Debtor Information -->
    <div class="recipient-box">
        <div class="party-title" style="font-weight: bold; margin-bottom: 8px;">DEBITOR</div>
        <table class="info-table">
            <tr>
                <td class="label">Navn:</td>
                <td class="value">{$customerName}</td>
            </tr>
            <tr>
                <td class="label">CPR-nr.:</td>
                <td class="value">{$customerNin}</td>
            </tr>
            <tr>
                <td class="label">Bruger ID:</td>
                <td class="value">{$customerUid}</td>
            </tr>
        </table>
    </div>

    <!-- Legal Statement -->
    <div class="intro-text">
        <p>{$legalStatement}</p>
    </div>

    <!-- Amount Box -->
    <div class="amount-box">
        <table class="amount-table">
            <tr>
                <td class="label">Oprindeligt beløb:</td>
                <td class="value">{$originalFormatted} {$currencySymbol}</td>
            </tr>
            <tr>
                <td class="label">Rykkergebyr ({$this->level}. rykker):</td>
                <td class="value">{$rykkerFeeFormatted} {$currencySymbol}</td>
            </tr>
            <tr>
                <td class="total-label">SKYLDIGT BELØB:</td>
                <td class="total-value">{$totalFormatted} {$currencySymbol}</td>
            </tr>
        </table>
    </div>

    <!-- Payment Info -->
    <div class="info-section">
        <table class="info-table">
            <tr>
                <td class="label">Oprindelig forfaldsdato:</td>
                <td class="value">{$dueDate}</td>
            </tr>
            <tr>
                <td class="label">Ordre reference:</td>
                <td class="value">{$orderUid}</td>
            </tr>
            <tr>
                <td class="label">Betalings ID:</td>
                <td class="value">{$payment->uid}</td>
            </tr>
            <tr>
                <td class="label">Rykker niveau:</td>
                <td class="value">{$this->level} af 3</td>
            </tr>
        </table>
    </div>

    <!-- Collection Warning (level 3 only) -->
    {$collectionWarningHtml}

    <!-- Legal Disclaimer -->
    <div class="legal-disclaimer">
        <p><strong>Juridisk grundlag:</strong></p>
        <p>Rykkergebyret er pålagt af {$locationName} i henhold til deres betalingsbetingelser og gældende lovgivning.</p>
        <p>Kreditten er ydet af {$locationName}. WeePay er ikke kreditgiver og fungerer udelukkende som teknisk betalingsformidler.</p>
        <p>Ved indsigelser mod kravet skal henvendelse rettes direkte til {$locationName}.</p>
    </div>

    <!-- Contact Info -->
    <div class="contact-box">
        <strong>Kreditor:</strong><br>
        {$locationName}<br>
        {$locationContact}
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Udstedt via WeePay på vegne af {$locationName}</p>
        <p>Dokument ID: RYK-{$this->level}-{$payment->uid}</p>
        <p>Dokumentdato: {$documentDate}</p>
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

        return implode(' | ', $parts);
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
     * Get the payment object
     */
    public function getPayment(): object {
        return $this->payment;
    }

    /**
     * Get the rykker level
     */
    public function getLevel(): int {
        return $this->level;
    }
}
