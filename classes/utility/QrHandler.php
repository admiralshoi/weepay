<?php

namespace classes\utility;
use classes\Methods;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

class QrHandler {

    private ?object $built = null;

    public function build(string $url): static {
        $builder = new Builder(
            writer: new PngWriter(),
            data: $url,
            size: 300,
            margin: 10
        );

        $this->built = $builder->build();
        return $this;
    }
    public function saveToFile(string $destination): bool {
        if($this->built === null) return false;
        $path = explode("/", $destination);
        array_pop($path);
        $path = implode("/", $path);
        $root = "";

        if(!is_dir($path)) $root = ROOT;
        if(!is_dir($root . $path)) return false;
        $this->built->saveToFile(__DIR__ . '/merchant_12345.png');
        return true;
    }

    public function get(): ?object {
        return $this->built;
    }

    /**
     * Build a composite image with WeePay logo centered at top, margin, then QR code below
     * @param string $url The URL to encode in the QR code
     * @param int $logoMarginBottom Margin between logo and QR code in pixels
     * @return array ['image' => string (PNG binary), 'mimeType' => string]
     */
    public function buildWithLogo(string $url, int $logoMarginBottom = 50): array {
        // Build QR code
        $this->build($url);
        $qrString = $this->built->getString();

        // Load QR code image
        $qrImage = imagecreatefromstring($qrString);
        $qrWidth = imagesx($qrImage);
        $qrHeight = imagesy($qrImage);

        // Load logo (PNG version of LOGO_WIDE_HEADER)
        $logoPath = ROOT . 'public/' . str_replace('.svg', '.png', LOGO_WIDE_HEADER);
        if (!file_exists($logoPath)) {
            // Fallback to just the QR code if logo doesn't exist
            return ['image' => $qrString, 'mimeType' => 'image/png'];
        }

        $logoImage = imagecreatefrompng($logoPath);
        $logoWidth = imagesx($logoImage);
        $logoHeight = imagesy($logoImage);

        // Scale logo if it's wider than QR code (max 80% of QR width)
        $maxLogoWidth = (int)($qrWidth * 0.8);
        if ($logoWidth > $maxLogoWidth) {
            $scale = $maxLogoWidth / $logoWidth;
            $newLogoWidth = (int)($logoWidth * $scale);
            $newLogoHeight = (int)($logoHeight * $scale);
            $scaledLogo = imagecreatetruecolor($newLogoWidth, $newLogoHeight);

            // Preserve transparency
            imagealphablending($scaledLogo, false);
            imagesavealpha($scaledLogo, true);
            $transparent = imagecolorallocatealpha($scaledLogo, 255, 255, 255, 127);
            imagefilledrectangle($scaledLogo, 0, 0, $newLogoWidth, $newLogoHeight, $transparent);

            imagecopyresampled($scaledLogo, $logoImage, 0, 0, 0, 0, $newLogoWidth, $newLogoHeight, $logoWidth, $logoHeight);
            imagedestroy($logoImage);
            $logoImage = $scaledLogo;
            $logoWidth = $newLogoWidth;
            $logoHeight = $newLogoHeight;
        }

        // Calculate composite image dimensions
        $padding = 30;
        $compositeWidth = max($qrWidth, $logoWidth) + ($padding * 2);
        $compositeHeight = $logoHeight + $logoMarginBottom + $qrHeight + ($padding * 2);

        // Create composite image
        $composite = imagecreatetruecolor($compositeWidth, $compositeHeight);

        // Fill with white background
        $white = imagecolorallocate($composite, 255, 255, 255);
        imagefill($composite, 0, 0, $white);

        // Enable alpha blending for transparency
        imagealphablending($composite, true);

        // Calculate positions (center horizontally)
        $logoX = (int)(($compositeWidth - $logoWidth) / 2);
        $logoY = $padding;
        $qrX = (int)(($compositeWidth - $qrWidth) / 2);
        $qrY = $padding + $logoHeight + $logoMarginBottom;

        // Copy logo onto composite
        imagecopy($composite, $logoImage, $logoX, $logoY, 0, 0, $logoWidth, $logoHeight);

        // Copy QR code onto composite
        imagecopy($composite, $qrImage, $qrX, $qrY, 0, 0, $qrWidth, $qrHeight);

        // Output to string
        ob_start();
        imagepng($composite);
        $imageString = ob_get_clean();

        // Clean up
        imagedestroy($qrImage);
        imagedestroy($logoImage);
        imagedestroy($composite);

        return ['image' => $imageString, 'mimeType' => 'image/png'];
    }

}