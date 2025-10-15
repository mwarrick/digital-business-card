<?php
/**
 * QR Code Generation API
 * GET /api/cards/qrcode?id={card_id}&format={format}
 * 
 * Generates QR code for a business card with full vCard data
 * 
 * Query Parameters:
 * - id: Business card ID (required)
 * - format: Response format - 'json' (default) returns data URL, 'image' returns PNG image
 * - size: QR code size in pixels (default: 300, max: 1000)
 */

require_once __DIR__ . '/../includes/Api.php';
require_once __DIR__ . '/../includes/Database.php';

class QRCodeApi extends Api {
    private $db;
    private $userId;
    
    public function __construct() {
        parent::__construct();
        
        // Apply rate limiting: 100 QR generations per hour
        $this->applyRateLimit(100, 3600, 'qrcode');
        
        $this->db = Database::getInstance();
        $this->userId = $this->requireAuth();
        $this->handleRequest();
    }
    
    private function handleRequest() {
        if ($this->method !== 'GET') {
            $this->error('Method not allowed', 405);
        }
        
        $this->generateQRCode();
    }
    
    private function generateQRCode() {
        // Validate required parameters
        if (!isset($this->params['id'])) {
            $this->error('Card ID is required', 400);
        }
        
        $cardId = $this->params['id'];
        $format = $this->params['format'] ?? 'json';
        $size = min(1000, max(100, intval($this->params['size'] ?? 300)));
        
        try {
            // Get business card
            $card = $this->db->querySingle(
                "SELECT * FROM business_cards WHERE id = ? AND user_id = ? AND is_active = 1",
                [$cardId, $this->userId]
            );
            
            if (!$card) {
                $this->error('Business card not found', 404);
            }
            
            // Get additional contact information
            $emails = $this->db->query(
                "SELECT email, type, label FROM email_contacts WHERE business_card_id = ?",
                [$cardId]
            );
            
            $phones = $this->db->query(
                "SELECT phone_number, type, label FROM phone_contacts WHERE business_card_id = ?",
                [$cardId]
            );
            
            $websites = $this->db->query(
                "SELECT url, name, description FROM website_links WHERE business_card_id = ?",
                [$cardId]
            );
            
            $address = $this->db->querySingle(
                "SELECT * FROM addresses WHERE business_card_id = ?",
                [$cardId]
            );
            
            // Generate vCard data
            $vcard = $this->generateVCard($card, $emails, $phones, $websites, $address);
            
            // Generate QR code
            $qrCodeUrl = $this->generateQRCodeImage($vcard, $size);
            
            if ($format === 'image') {
                // Return PNG image directly
                header('Content-Type: image/png');
                header('Content-Disposition: inline; filename="qrcode-' . $cardId . '.png"');
                echo file_get_contents($qrCodeUrl);
                exit();
            } else {
                // Return JSON with data URL
                $this->success([
                    'business_card_id' => $cardId,
                    'qr_code_url' => $qrCodeUrl,
                    'vcard_data' => $vcard,
                    'size' => $size,
                    'format' => 'png'
                ], 'QR code generated successfully');
            }
            
        } catch (Exception $e) {
            error_log("QR code generation error: " . $e->getMessage());
            $this->error('Failed to generate QR code: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Generate vCard 3.0 format data
     */
    private function generateVCard($card, $emails, $phones, $websites, $address) {
        $vcard = "BEGIN:VCARD\r\n";
        $vcard .= "VERSION:3.0\r\n";
        
        // Name
        $vcard .= "N:" . $this->escapeVCard($card['last_name']) . ";" . 
                  $this->escapeVCard($card['first_name']) . ";;;\r\n";
        $vcard .= "FN:" . $this->escapeVCard($card['first_name'] . ' ' . $card['last_name']) . "\r\n";
        
        // Organization and Title
        if (!empty($card['company_name'])) {
            $vcard .= "ORG:" . $this->escapeVCard($card['company_name']) . "\r\n";
        }
        
        if (!empty($card['job_title'])) {
            $vcard .= "TITLE:" . $this->escapeVCard($card['job_title']) . "\r\n";
        }
        
        // Primary phone
        $vcard .= "TEL;TYPE=WORK,VOICE:" . $this->escapeVCard($card['phone_number']) . "\r\n";
        
        // Additional phones
        foreach ($phones as $phone) {
            $type = strtoupper($phone['type']);
            $vcard .= "TEL;TYPE={$type},VOICE:" . $this->escapeVCard($phone['phone_number']) . "\r\n";
        }
        
        // Email addresses
        foreach ($emails as $email) {
            $type = strtoupper($email['type']);
            $vcard .= "EMAIL;TYPE=INTERNET,{$type}:" . $this->escapeVCard($email['email']) . "\r\n";
        }
        
        // Website URLs
        foreach ($websites as $website) {
            $vcard .= "URL:" . $this->escapeVCard($website['url']) . "\r\n";
        }
        
        // Address
        if ($address) {
            $vcard .= "ADR;TYPE=WORK:;;" . 
                     $this->escapeVCard($address['street'] ?? '') . ";" .
                     $this->escapeVCard($address['city'] ?? '') . ";" .
                     $this->escapeVCard($address['state'] ?? '') . ";" .
                     $this->escapeVCard($address['postal_code'] ?? '') . ";" .
                     $this->escapeVCard($address['country'] ?? '') . "\r\n";
        }
        
        // Note/Bio
        if (!empty($card['bio'])) {
            $vcard .= "NOTE:" . $this->escapeVCard($card['bio']) . "\r\n";
        }
        
        $vcard .= "END:VCARD\r\n";
        
        return $vcard;
    }
    
    /**
     * Escape special characters for vCard format
     */
    private function escapeVCard($text) {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(',', '\\,', $text);
        $text = str_replace(';', '\\;', $text);
        $text = str_replace("\n", '\\n', $text);
        return $text;
    }
    
    /**
     * Generate QR code image using external API
     * In production, consider using a PHP QR code library like endroid/qr-code
     */
    private function generateQRCodeImage($data, $size) {
        // Using QR Server API as a simple solution
        // For production, consider using a PHP library or self-hosted solution
        $encodedData = urlencode($data);
        $url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encodedData}";
        
        return $url;
    }
}

// Initialize API
new QRCodeApi();

