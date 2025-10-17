<?php
/**
 * Virtual Background Generator
 * Creates downloadable background images with embedded QR codes
 */

require_once __DIR__ . '/Database.php';

class VirtualBackgroundGenerator {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Generate a virtual background image
     */
    public function generateBackground($cardId, $width, $height, $preferences = null) {
        // Get card data
        $card = $this->getCardData($cardId);
        if (!$card) {
            throw new Exception('Card not found');
        }
        
        // Get or create preferences
        if (!$preferences) {
            $preferences = $this->getPreferences($cardId);
        }
        
        // Ensure all required preference keys exist
        $defaults = [
            'qr_position' => 'bottom-right',
            'qr_size' => 300,
            'padding_x' => 50,
            'padding_y' => 50,
            'text_option' => 'qr-only'
        ];
        
        $preferences = array_merge($defaults, $preferences);
        
        // Create base image
        $image = imagecreatetruecolor($width, $height);
        if (!$image) {
            throw new Exception('Failed to create image');
        }
        
        // Generate gradient background
        $this->createGradientBackground($image, $width, $height, $card['theme'], $preferences);
        
        // Generate QR code
        $qrCodeData = $this->generateQRCode($cardId);
        
        // Use preferences directly without scaling for now to test
        $scaledQrSize = (int)$preferences['qr_size'];
        $scaledPaddingX = (int)$preferences['padding_x'];
        $scaledPaddingY = (int)$preferences['padding_y'];
        
        // Embed QR code
        $this->embedQRCode($image, $qrCodeData, $preferences['qr_position'], $scaledQrSize, $scaledPaddingX, $scaledPaddingY);
        
        // Add text overlay if requested
        if ($preferences['text_option'] !== 'qr-only') {
            $this->addTextOverlay($image, $card, $preferences['text_option'], $preferences['qr_position'], $scaledQrSize, $scaledPaddingX, $scaledPaddingY, $scaleFactor);
        }
        
        return $image;
    }
    
    /**
     * Get card data from database
     */
    private function getCardData($cardId) {
        return $this->db->querySingle(
            "SELECT id, first_name, last_name, company_name, job_title, theme FROM business_cards WHERE id = ? AND is_active = 1",
            [$cardId]
        );
    }
    
    /**
     * Get or create default preferences for a card
     */
    private function getPreferences($cardId) {
        $preferences = $this->db->querySingle(
            "SELECT * FROM virtual_background_preferences WHERE card_id = ?",
            [$cardId]
        );
        
        if (!$preferences) {
            // Return default preferences
            return [
                'qr_position' => 'bottom-right',
                'qr_size' => 300,
                'padding_x' => 50,
                'padding_y' => 50,
                'text_option' => 'qr-only',
                'color_top' => null,
                'color_bottom' => null
            ];
        }
        
        return $preferences;
    }
    
    /**
     * Create gradient background based on theme or custom colors
     */
    private function createGradientBackground($image, $width, $height, $theme, $preferences = []) {
        // Check if custom colors are provided
        if (isset($preferences['color_top']) && isset($preferences['color_bottom'])) {
            $colors = [$preferences['color_top'], $preferences['color_bottom']];
        } else {
            $colors = $this->getThemeColors($theme);
        }
        
        // Convert hex colors to RGB
        $color1 = $this->hexToRgb($colors[0]);
        $color2 = $this->hexToRgb($colors[1]);
        
        // Create linear gradient
        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / $height;
            $r = $color1['r'] + ($color2['r'] - $color1['r']) * $ratio;
            $g = $color1['g'] + ($color2['g'] - $color1['g']) * $ratio;
            $b = $color1['b'] + ($color2['b'] - $color1['b']) * $ratio;
            
            $color = imagecolorallocate($image, (int)$r, (int)$g, (int)$b);
            imageline($image, 0, $y, $width, $y, $color);
        }
    }
    
    /**
     * Get theme colors for gradient
     */
    private function getThemeColors($theme) {
        $themeColors = [
            'professional-blue' => ['#667eea', '#764ba2'],
            'minimalist-gray' => ['#bdc3c7', '#2c3e50'],
            'creative-sunset' => ['#f093fb', '#f5576c'],
            'corporate-green' => ['#11998e', '#38ef7d'],
            'tech-purple' => ['#4776e6', '#8e54e9']
        ];
        
        return $themeColors[$theme] ?? $themeColors['professional-blue'];
    }
    
    /**
     * Convert hex color to RGB
     */
    private function hexToRgb($hex) {
        $hex = ltrim($hex, '#');
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2))
        ];
    }
    
    /**
     * Generate QR code data
     */
    private function generateQRCode($cardId) {
        // Use the same URL format as other QR codes in the system
        $qrUrl = "https://sharemycard.app/card.php?id=" . $cardId;
        
        // Generate QR code using QR Server API (same as used elsewhere in the system)
        $encodedData = urlencode($qrUrl);
        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={$encodedData}&format=png";
        
        // Download the QR code image
        $qrCodeImage = file_get_contents($qrCodeUrl);
        
        if ($qrCodeImage === false) {
            throw new Exception('Failed to generate QR code');
        }
        
        // Create image resource from the downloaded data
        $qrCodeResource = imagecreatefromstring($qrCodeImage);
        
        if ($qrCodeResource === false) {
            throw new Exception('Failed to create QR code image resource');
        }
        
        return $qrCodeResource;
    }
    
    /**
     * Embed QR code into image
     */
    private function embedQRCode($image, $qrCodeResource, $position, $size, $paddingX, $paddingY) {
        // Calculate position based on corner
        $width = imagesx($image);
        $height = imagesy($image);
        
        switch ($position) {
            case 'top-left':
                $x = $paddingX;
                $y = $paddingY;
                break;
            case 'top-right':
                $x = $width - $size - $paddingX;
                $y = $paddingY;
                break;
            case 'bottom-left':
                $x = $paddingX;
                $y = $height - $size - $paddingY;
                break;
            case 'bottom-right':
            default:
                $x = $width - $size - $paddingX;
                $y = $height - $size - $paddingY;
                break;
        }
        
        // Resize the QR code to the desired size
        $resizedQr = imagecreatetruecolor($size, $size);
        if (!$resizedQr) {
            throw new Exception('Failed to create resized QR code image');
        }
        
        // Get original QR code dimensions
        $originalWidth = imagesx($qrCodeResource);
        $originalHeight = imagesy($qrCodeResource);
        
        // Resize the QR code
        imagecopyresampled($resizedQr, $qrCodeResource, 0, 0, 0, 0, $size, $size, $originalWidth, $originalHeight);
        
        // Copy the resized QR code onto the main image
        imagecopy($image, $resizedQr, $x, $y, 0, 0, $size, $size);
        
        // Clean up the resized QR code resource
        imagedestroy($resizedQr);
        imagedestroy($qrCodeResource);
    }
    
    /**
     * Add text overlay to image
     */
    private function addTextOverlay($image, $card, $textOption, $qrPosition, $qrSize, $paddingX, $paddingY, $scaleFactor) {
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Prepare text based on option
        $text = '';
        switch ($textOption) {
            case 'qr-name':
                $text = $card['first_name'] . ' ' . $card['last_name'];
                break;
            case 'qr-name-company':
                $text = $card['first_name'] . ' ' . $card['last_name'];
                if (!empty($card['company_name'])) {
                    $text .= "\n" . $card['company_name'];
                }
                break;
        }
        
        if (empty($text)) return;
        
        // Calculate text position relative to QR code
        $fontSize = max(24, (int)(48 * $scaleFactor));
        $textColor = imagecolorallocate($image, 255, 255, 255);
        $shadowColor = imagecolorallocate($image, 0, 0, 0);
        
        // Calculate QR code position
        switch ($qrPosition) {
            case 'top-left':
                $qrX = $paddingX;
                $qrY = $paddingY;
                break;
            case 'top-right':
                $qrX = $width - $qrSize - $paddingX;
                $qrY = $paddingY;
                break;
            case 'bottom-left':
                $qrX = $paddingX;
                $qrY = $height - $qrSize - $paddingY;
                break;
            case 'bottom-right':
            default:
                $qrX = $width - $qrSize - $paddingX;
                $qrY = $height - $qrSize - $paddingY;
                break;
        }
        
        // Position text relative to QR code
        $textLines = explode("\n", $text);
        $lineHeight = $fontSize + 10;
        
        foreach ($textLines as $index => $line) {
            $textWidth = imagefontwidth($fontSize) * strlen($line);
            
            // Center text horizontally relative to QR code
            $textX = $qrX + ($qrSize - $textWidth) / 2;
            
            // Position text above or below QR code
            if (in_array($qrPosition, ['top-left', 'top-right'])) {
                // Text below QR code
                $textY = $qrY + $qrSize + 20 + ($index * $lineHeight);
            } else {
                // Text above QR code
                $textY = $qrY - 20 - (count($textLines) - $index) * $lineHeight;
            }
            
            // Draw shadow
            imagestring($image, $fontSize, $textX + 2, $textY + 2, $line, $shadowColor);
            // Draw text
            imagestring($image, $fontSize, $textX, $textY, $line, $textColor);
        }
    }
    
    /**
     * Save preferences for a card
     */
    public function savePreferences($cardId, $preferences) {
        $existing = $this->db->querySingle(
            "SELECT id FROM virtual_background_preferences WHERE card_id = ?",
            [$cardId]
        );
        
        if ($existing) {
            // Update existing preferences
            $this->db->execute(
                "UPDATE virtual_background_preferences SET 
                 qr_position = ?, qr_size = ?, padding_x = ?, padding_y = ?, text_option = ?, 
                 color_top = ?, color_bottom = ?, updated_at = NOW()
                 WHERE card_id = ?",
                [
                    $preferences['qr_position'],
                    $preferences['qr_size'],
                    $preferences['padding_x'],
                    $preferences['padding_y'],
                    $preferences['text_option'],
                    $preferences['color_top'] ?? null,
                    $preferences['color_bottom'] ?? null,
                    $cardId
                ]
            );
        } else {
            // Create new preferences
            $id = $this->generateUUID();
            $this->db->execute(
                "INSERT INTO virtual_background_preferences 
                 (id, card_id, qr_position, qr_size, padding_x, padding_y, text_option, color_top, color_bottom) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $id,
                    $cardId,
                    $preferences['qr_position'],
                    $preferences['qr_size'],
                    $preferences['padding_x'],
                    $preferences['padding_y'],
                    $preferences['text_option'],
                    $preferences['color_top'] ?? null,
                    $preferences['color_bottom'] ?? null
                ]
            );
        }
    }
    
    /**
     * Generate UUID
     */
    private function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
?>
