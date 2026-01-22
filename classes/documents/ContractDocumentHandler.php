<?php

namespace classes\documents;

use classes\Methods;

/**
 * Handler for contract and rykker PDF documents
 * Manages storage, retrieval, and path generation for BNPL contracts and rykker notices
 */
class ContractDocumentHandler {

    private const BASE_PATH = ROOT . 'content/contracts/';

    /**
     * Ensure the directory exists for storing documents
     * Structure: content/contracts/{org_id}/{location_id}/{user_id}/
     */
    public function ensureDirectory(string $orgId, string $locationId, string $userId): string {
        $path = self::BASE_PATH . "{$orgId}/{$locationId}/{$userId}/";

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        return $path;
    }

    /**
     * Get the path for an order contract PDF
     */
    public function getContractPath(object $order): string {
        $orgId = is_object($order->organisation) ? $order->organisation->uid : $order->organisation;
        $locationId = is_object($order->location) ? $order->location->uid : $order->location;
        $userId = is_object($order->uuid) ? $order->uuid->uid : $order->uuid;

        $directory = $this->ensureDirectory($orgId, $locationId, $userId);
        return $directory . "{$order->uid}_contract.pdf";
    }

    /**
     * Get the path for a rykker PDF
     */
    public function getRykkerPath(object $payment, int $level): string {
        // Get IDs from payment, resolving foreign key objects if needed
        $orgId = is_object($payment->organisation) ? $payment->organisation->uid : $payment->organisation;
        $locationId = is_object($payment->location) ? $payment->location->uid : $payment->location;
        $userId = is_object($payment->uuid) ? $payment->uuid->uid : $payment->uuid;

        $directory = $this->ensureDirectory($orgId, $locationId, $userId);
        return $directory . "{$payment->uid}_rykker{$level}.pdf";
    }

    /**
     * Save a contract PDF to storage
     * @return string The file path where the PDF was saved
     */
    public function saveContract(object $order, string $pdfContent): string {
        $path = $this->getContractPath($order);
        file_put_contents($path, $pdfContent);
        return $path;
    }

    /**
     * Save a rykker PDF to storage
     * @return string The file path where the PDF was saved
     */
    public function saveRykker(object $payment, int $level, string $pdfContent): string {
        $path = $this->getRykkerPath($payment, $level);
        file_put_contents($path, $pdfContent);
        return $path;
    }

    /**
     * Check if a contract PDF exists for an order
     */
    public function contractExists(object $order): bool {
        $path = $this->getContractPath($order);
        return file_exists($path);
    }

    /**
     * Check if a rykker PDF exists for a payment at a given level
     */
    public function rykkerExists(object $payment, int $level): bool {
        $path = $this->getRykkerPath($payment, $level);
        return file_exists($path);
    }

    /**
     * Get the contract PDF content for an order
     * @return string|null PDF content or null if not found
     */
    public function getContract(object $order): ?string {
        $path = $this->getContractPath($order);
        if (!file_exists($path)) {
            return null;
        }
        return file_get_contents($path);
    }

    /**
     * Get the rykker PDF content for a payment
     * @return string|null PDF content or null if not found
     */
    public function getRykker(object $payment, int $level): ?string {
        $path = $this->getRykkerPath($payment, $level);
        if (!file_exists($path)) {
            return null;
        }
        return file_get_contents($path);
    }

    /**
     * Delete all documents for an order (contract and any rykkers for its payments)
     */
    public function deleteOrderDocuments(object $order): void {
        $contractPath = $this->getContractPath($order);
        if (file_exists($contractPath)) {
            unlink($contractPath);
        }
    }

    /**
     * Delete all rykker documents for a payment
     */
    public function deleteRykkerDocuments(object $payment): void {
        for ($level = 1; $level <= 3; $level++) {
            $path = $this->getRykkerPath($payment, $level);
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    /**
     * Stream a contract PDF to the browser for download
     */
    public function downloadContract(object $order, ?string $filename = null): void {
        $path = $this->getContractPath($order);
        if (!file_exists($path)) {
            throw new \Exception("Contract PDF not found");
        }

        $filename = $filename ?? "kontrakt_{$order->uid}.pdf";
        $this->streamPdf($path, $filename);
    }

    /**
     * Stream a rykker PDF to the browser for download
     */
    public function downloadRykker(object $payment, int $level, ?string $filename = null): void {
        $path = $this->getRykkerPath($payment, $level);
        if (!file_exists($path)) {
            throw new \Exception("Rykker PDF not found");
        }

        $filename = $filename ?? "rykker{$level}_{$payment->uid}.pdf";
        $this->streamPdf($path, $filename);
    }

    /**
     * Stream a PDF file to the browser
     */
    private function streamPdf(string $path, string $filename): void {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        readfile($path);
        exit;
    }

    /**
     * Get all rykker PDFs that exist for a payment
     * @return array Array of ['level' => int, 'path' => string]
     */
    public function getExistingRykkers(object $payment): array {
        $rykkers = [];
        for ($level = 1; $level <= 3; $level++) {
            $path = $this->getRykkerPath($payment, $level);
            if (file_exists($path)) {
                $rykkers[] = ['level' => $level, 'path' => $path];
            }
        }
        return $rykkers;
    }

    /**
     * Delete all rykker PDFs for a payment
     * Called when rykker is reset/cleared
     * @return int Number of files deleted
     */
    public function deleteRykkerPdfs(object $payment): int {
        $deleted = 0;
        for ($level = 1; $level <= 3; $level++) {
            $path = $this->getRykkerPath($payment, $level);
            if (file_exists($path)) {
                if (unlink($path)) {
                    $deleted++;
                }
            }
        }
        return $deleted;
    }

    /**
     * Delete a specific rykker PDF
     * @return bool True if deleted, false if not found
     */
    public function deleteRykkerPdf(object $payment, int $level): bool {
        $path = $this->getRykkerPath($payment, $level);
        if (file_exists($path)) {
            return unlink($path);
        }
        return false;
    }
}
