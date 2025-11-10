<?php
/**
 * Simple QR Code Generator helper
 * For now, uses QRServer API for PNG/SVG; can be swapped to local library later.
 */

namespace QRCard\QR;

class Generator {
    /**
     * Build a URL to a QR image containing the given payload.
     * @param string $data Arbitrary text/url payload
     * @param int $size Size in px (100-1000)
     * @param string $format 'png' or 'svg'
     */
    public static function buildImageUrl(string $data, int $size = 300, string $format = 'png'): string {
        $size = max(100, min(1000, $size));
        $format = strtolower($format) === 'svg' ? 'svg' : 'png';
        $encoded = urlencode($data);
        $base = 'https://api.qrserver.com/v1/create-qr-code/';
        $params = "?size={$size}x{$size}&data={$encoded}";
        if ($format === 'svg') {
            // QRServer serves SVG via same endpoint with format param
            $params .= '&format=svg';
        }
        return $base . $params;
    }
    
    /**
     * Check if a QR code has expired.
     * @param array $qrCode QR code record from database (must include 'expires_at' field)
     * @return bool True if expired, false if not expired or no expiration set
     */
    public static function isQrCodeExpired(array $qrCode): bool {
        // If expires_at is NULL, QR code never expires
        if (empty($qrCode['expires_at']) || $qrCode['expires_at'] === null) {
            return false;
        }
        
        try {
            // Get current time in EST timezone
            $est = new \DateTimeZone('America/New_York');
            $now = new \DateTime('now', $est);
            
            // Parse expires_at as EST
            // MySQL DATETIME is returned as a string like "2025-11-05 10:00:00"
            // We need to explicitly parse it and set the timezone to EST
            $expiresAtString = trim($qrCode['expires_at']);
            
            // Try to parse the date string explicitly in EST timezone
            // Use createFromFormat to ensure proper parsing
            $expiresAt = \DateTime::createFromFormat('Y-m-d H:i:s', $expiresAtString, $est);
            
            // If that fails, try without seconds
            if (!$expiresAt) {
                $expiresAt = \DateTime::createFromFormat('Y-m-d H:i', $expiresAtString, $est);
            }
            
            // If still fails, fall back to constructor (less reliable)
            if (!$expiresAt) {
                $expiresAt = new \DateTime($expiresAtString, $est);
            }
            
            // Compare: expired if current time >= expiration time
            return $now >= $expiresAt;
        } catch (\Exception $e) {
            // If there's any error parsing the date, log it and assume not expired
            error_log('Error checking QR expiration: ' . $e->getMessage() . ' for expires_at: ' . ($qrCode['expires_at'] ?? 'NULL'));
            return false;
        }
    }
}


