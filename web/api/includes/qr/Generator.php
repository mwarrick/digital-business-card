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
        
        // Get current time in EST timezone
        $est = new \DateTimeZone('America/New_York');
        $now = new \DateTime('now', $est);
        
        // Parse expires_at as EST (assuming it's stored in EST)
        // If expires_at is stored as DATETIME, MySQL returns it as a string
        $expiresAt = new \DateTime($qrCode['expires_at'], $est);
        
        // Compare: expired if current time >= expiration time
        return $now >= $expiresAt;
    }
}


