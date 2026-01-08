<?php

namespace classes\payments;

use classes\Methods;
use Database\Collection;
use Dompdf\Dompdf;
use Dompdf\Options;

class PaymentReceipt {

    private object $payment;
    private ?object $order;
    private ?object $customer;
    private ?object $organisation;
    private ?object $location;
    private ?Collection $basketItems;
    private string $currency;

    public function __construct(object $payment) {
        $this->payment = $payment;
        $this->order = is_object($payment->order) ? $payment->order : null;
        $this->customer = is_object($payment->uuid) ? $payment->uuid : null;
        $this->organisation = is_object($payment->organisation) ? $payment->organisation : null;
        $this->location = is_object($payment->location) ? $payment->location : null;
        $this->currency = $payment->currency ?? 'DKK';
        $this->basketItems = $this->fetchBasketItems();
    }

    /**
     * Fetch basket items for the order's terminal session
     */
    private function fetchBasketItems(): ?Collection {
        if(isEmpty($this->order)) {
            return null;
        }

        // Get terminal_session from order - it might be a string or an object
        $terminalSessionUid = null;
        if(is_object($this->order->terminal_session)) {
            $terminalSessionUid = $this->order->terminal_session->uid;
        } elseif(is_string($this->order->terminal_session) && !isEmpty($this->order->terminal_session)) {
            $terminalSessionUid = $this->order->terminal_session;
        }

        if(isEmpty($terminalSessionUid)) {
            return null;
        }

        // Fetch basket items without resolving foreign keys (we don't need them)
        $basketHandler = Methods::checkoutBasket();
        return $basketHandler->excludeForeignKeys()->getByX([
            'terminal_session' => $terminalSessionUid,
            'status' => 'FULFILLED'
        ]);
    }

    /**
     * Create a PaymentReceipt instance from a payment UID
     */
    public static function fromPaymentUid(string $paymentUid): ?self {
        $paymentHandler = Methods::payments();
        $payment = $paymentHandler->get($paymentUid);

        if(isEmpty($payment)) {
            return null;
        }

        return new self($payment);
    }

    /**
     * Generate the PDF and return as string (for email attachments, storage, etc.)
     */
    public function generatePdfString(): string {
        $dompdf = $this->createDompdf();
        $dompdf->loadHtml($this->generateHtml());
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Generate the PDF and stream directly to browser for download
     */
    public function download(string $filename = null): void {
        $filename = $filename ?? $this->getDefaultFilename();

        $dompdf = $this->createDompdf();
        $dompdf->loadHtml($this->generateHtml());
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $dompdf->stream($filename, ['Attachment' => true]);
    }

    /**
     * Generate the PDF and stream for inline viewing in browser
     */
    public function view(): void {
        $dompdf = $this->createDompdf();
        $dompdf->loadHtml($this->generateHtml());
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $dompdf->stream($this->getDefaultFilename(), ['Attachment' => false]);
    }

    /**
     * Save the PDF to a file
     */
    public function saveToFile(string $filepath): bool {
        $pdfContent = $this->generatePdfString();
        return file_put_contents($filepath, $pdfContent) !== false;
    }

    /**
     * Get the default filename for the receipt
     */
    public function getDefaultFilename(): string {
        $date = date('Y-m-d', strtotime($this->payment->paid_at ?? $this->payment->due_date));
        return "kvittering_{$this->payment->uid}_{$date}.pdf";
    }

    /**
     * Create and configure Dompdf instance
     */
    private function createDompdf(): Dompdf {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        return new Dompdf($options);
    }

    /**
     * Generate the HTML for the receipt
     */
    private function generateHtml(): string {
        $payment = $this->payment;
        $order = $this->order;
        $customer = $this->customer;
        $organisation = $this->organisation;
        $location = $this->location;

        // Format values
        $amount = number_format($payment->amount, 2, ',', '.');
        $currencySymbol = $this->getCurrencySymbol();
        $paidDate = !isEmpty($payment->paid_at) ? date('d/m-Y H:i', strtotime($payment->paid_at)) : '-';
        $dueDate = date('d/m-Y', strtotime($payment->due_date));
        $receiptDate = date('d/m-Y H:i');

        // Organisation info
        $orgName = $organisation->name ?? BRAND_NAME;
        $orgAddress = '';
        if($organisation) {
            $parts = array_filter([
                $organisation->address_street ?? null,
                trim(($organisation->address_zip ?? '') . ' ' . ($organisation->address_city ?? '')),
                $organisation->address_country ?? null
            ]);
            $orgAddress = implode('<br>', $parts);
        }
        $orgCvr = $organisation->cvr ?? '';

        // Customer info
        $customerName = $customer->full_name ?? 'Ukendt';
        $customerEmail = $customer->email ?? '';

        // Location info
        $locationName = $location->name ?? '';

        // Order info
        $orderUid = $order->uid ?? '';
        $orderCaption = $order->caption ?? '';

        // Status
        $statusLabel = $this->getStatusLabel($payment->status);

        // Line items HTML
        $lineItemsHtml = $this->generateLineItemsHtml();

        return <<<HTML
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Kvittering - {$payment->uid}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            padding: 40px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            border-bottom: 2px solid #0066cc;
            padding-bottom: 20px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #0066cc;
        }
        .receipt-title {
            text-align: right;
        }
        .receipt-title h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 5px;
        }
        .receipt-title p {
            color: #666;
            font-size: 11px;
        }
        .info-section {
            margin-bottom: 30px;
        }
        .info-grid {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-grid td {
            padding: 5px 0;
            vertical-align: top;
        }
        .info-grid .label {
            color: #666;
            font-size: 11px;
            width: 50%;
        }
        .info-grid .value {
            font-weight: 500;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
        }
        .amount-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .amount-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }
        .amount-row.total {
            border-top: 2px solid #dee2e6;
            margin-top: 10px;
            padding-top: 15px;
        }
        .amount-row .label {
            color: #666;
        }
        .amount-row .value {
            font-weight: 500;
        }
        .amount-row.total .label,
        .amount-row.total .value {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }
        .two-column {
            width: 100%;
        }
        .two-column td {
            width: 50%;
            vertical-align: top;
            padding-right: 20px;
        }
        .line-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .line-items-table th {
            background: #f8f9fa;
            padding: 10px 8px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            color: #666;
            border-bottom: 2px solid #dee2e6;
        }
        .line-items-table th:last-child {
            text-align: right;
        }
        .line-items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #eee;
            font-size: 12px;
        }
        .line-items-table td:last-child {
            text-align: right;
            font-weight: 500;
        }
        .line-items-table .item-note {
            font-size: 10px;
            color: #666;
            font-style: italic;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            color: #999;
            font-size: 10px;
        }
        .footer p {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <table style="width: 100%; margin-bottom: 40px; border-bottom: 2px solid #0066cc; padding-bottom: 20px;">
        <tr>
            <td style="width: 50%;">
                <div class="logo">{$orgName}</div>
                {$orgAddress}
                {$orgCvr}
            </td>
            <td style="width: 50%; text-align: right;">
                <h1 style="font-size: 28px; color: #333; margin-bottom: 5px;">KVITTERING</h1>
                <p style="color: #666; font-size: 11px;">Betalings ID: {$payment->uid}</p>
                <p style="color: #666; font-size: 11px;">Dato: {$receiptDate}</p>
            </td>
        </tr>
    </table>

    <table class="two-column" style="margin-bottom: 30px;">
        <tr>
            <td>
                <div class="section-title">Kunde</div>
                <table class="info-grid">
                    <tr>
                        <td class="label">Navn</td>
                        <td class="value">{$customerName}</td>
                    </tr>
                    <tr>
                        <td class="label">Email</td>
                        <td class="value">{$customerEmail}</td>
                    </tr>
                </table>
            </td>
            <td>
                <div class="section-title">Betalingsdetaljer</div>
                <table class="info-grid">
                    <tr>
                        <td class="label">Status</td>
                        <td class="value"><span class="status-badge status-{$this->getStatusClass($payment->status)}">{$statusLabel}</span></td>
                    </tr>
                    <tr>
                        <td class="label">Betalt</td>
                        <td class="value">{$paidDate}</td>
                    </tr>
                    <tr>
                        <td class="label">Forfaldsdato</td>
                        <td class="value">{$dueDate}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="amount-box">
        {$lineItemsHtml}
        <table style="width: 100%;">
            <tr>
                <td colspan="2" style="border-top: 2px solid #dee2e6; padding-top: 15px;"></td>
            </tr>
            <tr>
                <td style="font-size: 16px; font-weight: bold;">Betaling (Rate {$payment->installment_number})</td>
                <td style="text-align: right; font-size: 16px; font-weight: bold;">{$amount} {$currencySymbol}</td>
            </tr>
        </table>
    </div>

    <table class="two-column" style="margin-bottom: 30px;">
        <tr>
            <td>
                <div class="section-title">Ordre Information</div>
                <table class="info-grid">
                    <tr>
                        <td class="label">Ordre ID</td>
                        <td class="value">{$orderUid}</td>
                    </tr>
                    <tr>
                        <td class="label">Lokation</td>
                        <td class="value">{$locationName}</td>
                    </tr>
                    <tr>
                        <td class="label">Beskrivelse</td>
                        <td class="value">{$orderCaption}</td>
                    </tr>
                </table>
            </td>
            <td>
                <div class="section-title">Betaling</div>
                <table class="info-grid">
                    <tr>
                        <td class="label">Rate</td>
                        <td class="value">{$payment->installment_number}</td>
                    </tr>
                    <tr>
                        <td class="label">Valuta</td>
                        <td class="value">{$this->currency}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="footer">
        <p>Denne kvittering er automatisk genereret af {$orgName}</p>
        <p>Ved spørgsmål kontakt venligst kundeservice</p>
        <p style="margin-top: 10px;">Genereret: {$receiptDate}</p>
    </div>
</body>
</html>
HTML;
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
     * Get status label in Danish
     */
    private function getStatusLabel(string $status): string {
        $labels = [
            'COMPLETED' => 'Gennemført',
            'PENDING' => 'Afventer',
            'SCHEDULED' => 'Planlagt',
            'PAST_DUE' => 'Forsinket',
            'FAILED' => 'Fejlet',
            'CANCELLED' => 'Annulleret',
            'REFUNDED' => 'Refunderet',
        ];
        return $labels[$status] ?? $status;
    }

    /**
     * Get status CSS class
     */
    private function getStatusClass(string $status): string {
        $classes = [
            'COMPLETED' => 'completed',
            'PENDING' => 'pending',
            'SCHEDULED' => 'pending',
            'PAST_DUE' => 'failed',
            'FAILED' => 'failed',
            'CANCELLED' => 'failed',
            'REFUNDED' => 'pending',
        ];
        return $classes[$status] ?? 'pending';
    }

    /**
     * Get the payment object
     */
    public function getPayment(): object {
        return $this->payment;
    }

    /**
     * Generate HTML for line items table
     */
    private function generateLineItemsHtml(): string {
        // If no basket items, show order total as single line
        if(isEmpty($this->basketItems) || $this->basketItems->count() === 0) {
            $orderAmount = $this->order ? number_format($this->order->amount, 2, ',', '.') : number_format($this->payment->amount, 2, ',', '.');
            $caption = $this->order->caption ?? 'Betaling';
            $currencySymbol = $this->getCurrencySymbol();

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
                    <td>{$orderAmount} {$currencySymbol}</td>
                </tr>
            </tbody>
        </table>
HTML;
        }

        // Build line items from basket
        $currencySymbol = $this->getCurrencySymbol();
        $rows = '';
        $total = 0;

        foreach($this->basketItems->list() as $item) {
            $name = htmlspecialchars($item->name ?? 'Vare');
            $price = (float)($item->price ?? 0);
            $total += $price;
            $priceFormatted = number_format($price, 2, ',', '.');
            $note = '';
            if(!isEmpty($item->note)) {
                $noteText = htmlspecialchars($item->note);
                $note = "<div class=\"item-note\">{$noteText}</div>";
            }

            $rows .= <<<HTML
                <tr>
                    <td>{$name}{$note}</td>
                    <td>{$priceFormatted} {$currencySymbol}</td>
                </tr>
HTML;
        }

        $totalFormatted = number_format($total, 2, ',', '.');

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
                <tr style="font-weight: bold; background: #f8f9fa;">
                    <td>Ordre Total</td>
                    <td>{$totalFormatted} {$currencySymbol}</td>
                </tr>
            </tbody>
        </table>
HTML;
    }
}
