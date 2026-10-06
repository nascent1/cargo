<?php

namespace App\Services;

use App\Models\SystemConfig;

/**
 * Barcode & QR Code Provenance Service per SRS §5.3
 *
 * Supports dual barcode encoding:
 *  - EAN-13: For retail POS scanning (incorporating configurable GS1 company prefix + Modulo 10 check digit).
 *  - Code 128: For internal warehouse inventory management, bins, GRNs, and batch serialization.
 *  - QR Provenance: For public consumer traceability.
 */
class BarcodeService
{
    /**
     * Generate Code 128 string for internal tracking (Batches, Bins, GRNs, SKUs)
     */
    public function generateCode128(string $prefix, string|int $referenceId): string
    {
        $cleanPrefix = strtoupper(preg_replace('/[^A-Z0-9]/', '', $prefix));
        $cleanRef = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $referenceId));
        $randomSuffix = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

        return sprintf('HC-%s-%s-%s', $cleanPrefix, $cleanRef, $randomSuffix);
    }

    /**
     * Generate EAN-13 retail barcode with GS1 Company Prefix + Check Digit
     */
    public function generateEan13(string|int $itemReference, ?string $customGs1Prefix = null): string
    {
        $gs1Prefix = $customGs1Prefix ?? SystemConfig::getValue('gs1_company_prefix', '6161234');
        $gs1Prefix = preg_replace('/[^0-9]/', '', (string) $gs1Prefix);

        if (strlen($gs1Prefix) >= 12) {
            $gs1Prefix = substr($gs1Prefix, 0, 7);
        }

        $remainingDigits = 12 - strlen($gs1Prefix);
        $cleanItemRef = preg_replace('/[^0-9]/', '', (string) $itemReference);
        $paddedItemRef = str_pad(substr($cleanItemRef, -$remainingDigits), $remainingDigits, '0', STR_PAD_LEFT);

        $base12 = $gs1Prefix . $paddedItemRef;
        $checkDigit = $this->calculateEan13CheckDigit($base12);

        return $base12 . $checkDigit;
    }

    /**
     * Calculate Modulo 10 check digit for EAN-13 (from first 12 digits)
     */
    public function calculateEan13CheckDigit(string $base12): int
    {
        $digits = str_split($base12);
        $sum = 0;

        foreach ($digits as $index => $digit) {
            // EAN-13 weighting: odd index (2nd, 4th, 6th...) * 3, even index (1st, 3rd...) * 1
            $weight = ($index % 2 === 1) ? 3 : 1;
            $sum += ((int) $digit) * $weight;
        }

        $remainder = $sum % 10;
        return $remainder === 0 ? 0 : 10 - $remainder;
    }

    /**
     * Verify if an EAN-13 string is valid according to check digit
     */
    public function verifyEan13(string $ean13): bool
    {
        if (!preg_match('/^[0-9]{13}$/', $ean13)) {
            return false;
        }

        $base12 = substr($ean13, 0, 12);
        $checkDigit = (int) substr($ean13, 12, 1);

        return $this->calculateEan13CheckDigit($base12) === $checkDigit;
    }

    /**
     * Generate QR Provenance URL for public verification
     */
    public function generateQrProvenanceUrl(string $batchCode, ?string $serialNumber = null): string
    {
        $baseUrl = url('/trace/' . urlencode($batchCode));

        if ($serialNumber) {
            $baseUrl .= '?serial=' . urlencode($serialNumber);
        }

        return $baseUrl;
    }
}
