<?php

namespace classes\marketing;

use classes\Methods;
use classes\enumerations\Links;
use setasign\Fpdi\Fpdi;

class MarketingPdfGenerator {

    private ?object $template = null;
    private ?object $location = null;
    private ?object $locationPage = null;
    private string $outputSize = 'original';

    /**
     * Standard paper sizes in mm (width x height in portrait orientation)
     */
    private const PAPER_SIZES = [
        'A5' => ['width' => 148, 'height' => 210],
        'A4' => ['width' => 210, 'height' => 297],
        'A3' => ['width' => 297, 'height' => 420],
    ];

    /**
     * Set the template to use for generation
     */
    public function setTemplate(object $template): static {
        $this->template = $template;
        return $this;
    }

    /**
     * Set the location for customization
     */
    public function setLocation(object $location): static {
        $this->location = $location;

        // Also fetch location page for logo
        $this->locationPage = Methods::locationPages()->excludeForeignKeys()->getFirst([
            'location' => $location->uid,
            'state' => 'PUBLISHED'
        ]);

        return $this;
    }

    /**
     * Set the output size for the generated PDF
     * @param string $size A5, A4, A3, or 'original' to keep template size
     */
    public function setSize(string $size): static {
        $this->outputSize = $size;
        return $this;
    }

    /**
     * Generate the customized PDF
     * @return string|null PDF binary content or null on failure
     */
    public function generate(): ?string {
        if (!$this->template || !$this->location) {
            return null;
        }

        $templatePath = ROOT . $this->template->file_path;
        if (!file_exists($templatePath)) {
            debugLog("Template file not found: " . $templatePath, "MARKETING_PDF");
            return null;
        }

        try {
            $pdf = new Fpdi();

            // Get number of pages from source PDF
            $pageCount = $pdf->setSourceFile($templatePath);

            // Get placeholders for this template
            $placeholders = Methods::marketingPlaceholders()
                ->excludeForeignKeys()
                ->getByTemplate($this->template->uid);

            // Process each page
            for ($pageNum = 1; $pageNum <= $pageCount; $pageNum++) {
                $tplId = $pdf->importPage($pageNum);
                $originalSize = $pdf->getTemplateSize($tplId);

                // Calculate output dimensions based on requested size
                $outputDimensions = $this->calculateOutputDimensions($originalSize);

                // Add page with calculated dimensions
                $pdf->AddPage($outputDimensions['orientation'], [$outputDimensions['width'], $outputDimensions['height']]);

                // Use template scaled to fit the new page size
                $pdf->useTemplate($tplId, 0, 0, $outputDimensions['width'], $outputDimensions['height']);

                // Apply placeholders for this page (scaled to new dimensions)
                foreach ($placeholders->list() as $placeholder) {
                    if ($placeholder->page_number !== $pageNum) {
                        continue;
                    }
                    $this->applyPlaceholder($pdf, $placeholder, $outputDimensions);
                }
            }

            // Return PDF as string
            return $pdf->Output('S');

        } catch (\Exception $e) {
            debugLog("PDF generation error: " . $e->getMessage(), "MARKETING_PDF");
            return null;
        }
    }

    /**
     * Apply a single placeholder to the PDF
     */
    private function applyPlaceholder(Fpdi $pdf, object $placeholder, array $pageSize): void {
        // Convert percentage positions to absolute positions (in mm)
        $x = ($placeholder->x / 100) * $pageSize['width'];
        $y = ($placeholder->y / 100) * $pageSize['height'];
        $width = ($placeholder->width / 100) * $pageSize['width'];
        $height = ($placeholder->height / 100) * $pageSize['height'];

        switch ($placeholder->type) {
            case 'qr_code':
                $this->overlayQrCode($pdf, $x, $y, $width, $height);
                break;
            case 'location_name':
                $this->overlayLocationName($pdf, $x, $y, $width, $height, $placeholder);
                break;
            case 'location_logo':
                $this->overlayLocationLogo($pdf, $x, $y, $width, $height);
                break;
        }
    }

    /**
     * Overlay QR code at specified position
     */
    private function overlayQrCode(Fpdi $pdf, float $x, float $y, float $width, float $height): void {
        // Get location slug for QR code URL
        $slug = $this->location->slug;
        $qrUrl = HOST . Links::$merchant->public->getLocationPage($slug);

        // Generate QR code
        $qrResult = Methods::qr()->build($qrUrl)->get();

        if ($qrResult) {
            // Save QR to temp file
            $tempFile = ROOT . 'public/media/dynamic/tmp/qr_' . time() . '_' . uniqid() . '.png';

            // Ensure directory exists
            $tempDir = dirname($tempFile);
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $qrResult->saveToFile($tempFile);

            if (file_exists($tempFile)) {
                // Use the smaller dimension for QR code to maintain square aspect
                $size = min($width, $height);
                $pdf->Image($tempFile, $x, $y, $size, $size);

                // Cleanup temp file
                unlink($tempFile);
            }
        }
    }

    /**
     * Overlay location name text at specified position
     */
    private function overlayLocationName(Fpdi $pdf, float $x, float $y, float $width, float $height, object $placeholder): void {
        $fontSize = $placeholder->font_size ?? 12;
        $fontColor = $placeholder->font_color ?? '#000000';

        // Parse hex color to RGB
        $color = $this->hexToRgb($fontColor);

        $pdf->SetFont('Helvetica', '', $fontSize);
        $pdf->SetTextColor($color['r'], $color['g'], $color['b']);

        // Position and render text
        $pdf->SetXY($x, $y);

        // Use MultiCell for text that might wrap, or Cell for single line
        $text = $this->location->name ?? '';

        // Center text vertically in the height
        $textHeight = $fontSize * 0.352778; // Convert pt to mm (approximate)
        $yOffset = ($height - $textHeight) / 2;

        $pdf->SetXY($x, $y + $yOffset);
        $pdf->Cell($width, $textHeight, $text, 0, 0, 'C');
    }

    /**
     * Overlay location logo at specified position
     */
    private function overlayLocationLogo(Fpdi $pdf, float $x, float $y, float $width, float $height): void {
        $logoPath = null;

        // Try to get logo from location page
        if ($this->locationPage && !isEmpty($this->locationPage->logo)) {
            $logoPath = ROOT . $this->locationPage->logo;
        }

        // Fallback to organisation logo if available
        if ((!$logoPath || !file_exists($logoPath)) && isset($this->location->uuid)) {
            $org = is_object($this->location->uuid) ? $this->location->uuid : Methods::organisations()->get($this->location->uuid);
            if ($org && !isEmpty($org->pictures)) {
                $pictures = is_string($org->pictures) ? json_decode($org->pictures, true) : (array)$org->pictures;
                if (!empty($pictures['logo'])) {
                    $logoPath = ROOT . $pictures['logo'];
                }
            }
        }

        if ($logoPath && file_exists($logoPath)) {
            // Get image dimensions to maintain aspect ratio
            $imageInfo = @getimagesize($logoPath);
            if ($imageInfo) {
                list($imgWidth, $imgHeight) = $imageInfo;
                $imgRatio = $imgWidth / $imgHeight;
                $boxRatio = $width / $height;

                if ($imgRatio > $boxRatio) {
                    // Image is wider, fit to width
                    $finalWidth = $width;
                    $finalHeight = $width / $imgRatio;
                } else {
                    // Image is taller, fit to height
                    $finalHeight = $height;
                    $finalWidth = $height * $imgRatio;
                }

                // Center within the placeholder box
                $xOffset = ($width - $finalWidth) / 2;
                $yOffset = ($height - $finalHeight) / 2;

                $pdf->Image($logoPath, $x + $xOffset, $y + $yOffset, $finalWidth, $finalHeight);
            }
        }
    }

    /**
     * Convert hex color to RGB array
     */
    private function hexToRgb(string $hex): array {
        $hex = ltrim($hex, '#');

        // Handle 3-character hex
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Calculate output dimensions based on requested size
     * Maintains aspect ratio and determines orientation
     */
    private function calculateOutputDimensions(array $originalSize): array {
        // If original size requested, return as-is
        if ($this->outputSize === 'original' || !isset(self::PAPER_SIZES[$this->outputSize])) {
            return [
                'width' => $originalSize['width'],
                'height' => $originalSize['height'],
                'orientation' => $originalSize['orientation'],
                'scale' => 1.0,
            ];
        }

        $targetSize = self::PAPER_SIZES[$this->outputSize];

        // Determine orientation from original
        $isLandscape = $originalSize['width'] > $originalSize['height'];

        if ($isLandscape) {
            // Swap target dimensions for landscape
            $targetWidth = $targetSize['height'];
            $targetHeight = $targetSize['width'];
            $orientation = 'L';
        } else {
            $targetWidth = $targetSize['width'];
            $targetHeight = $targetSize['height'];
            $orientation = 'P';
        }

        // Calculate scale factor
        $scaleX = $targetWidth / $originalSize['width'];
        $scaleY = $targetHeight / $originalSize['height'];
        $scale = min($scaleX, $scaleY); // Use smaller scale to fit within bounds

        return [
            'width' => $targetWidth,
            'height' => $targetHeight,
            'orientation' => $orientation,
            'scale' => $scale,
        ];
    }
}
