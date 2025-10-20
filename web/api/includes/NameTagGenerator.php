<?php
/**
 * Name Tag Generator
 * Creates printable name tag PDFs with 8 tags per 8.5"x11" sheet
 * Also generates PNG previews for web interface
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/tcpdf/tcpdf.php';

class NameTagGenerator {
    private $db;
    
    // Name tag dimensions in points (1 inch = 72 points)
    const TAG_WIDTH = 243;  // 3.375" x 72 = 243pt
    const TAG_HEIGHT = 168; // 2.33" x 72 = 168pt
    
    // Sheet dimensions
    const SHEET_WIDTH = 612;  // 8.5" x 72 = 612pt
    const SHEET_HEIGHT = 792; // 11" x 72 = 792pt
    
    // Margins and spacing
    const LEFT_MARGIN = 13;
    const TOP_MARGIN = 36;
    const HORIZONTAL_GAP = 18;
    const VERTICAL_GAP = 0;
    
    // Element dimensions
    const QR_SIZE = 72;  // 1 inch
    const PROFILE_SIZE = 54;  // 0.75 inch diameter
    const LOGO_MAX_WIDTH = 108;  // 1.5 inches
    const LOGO_MAX_HEIGHT = 43;  // 0.6 inches
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Generate complete PDF sheet with 8 name tags
     */
    public function generateNameTagSheet($cardId, $preferences) {
        // Get card data with all contact information
        $cardData = $this->getCardData($cardId);
        if (!$cardData) {
            throw new Exception('Card not found');
        }
        
        // Create PDF instance
        $pdf = new TCPDF('P', 'pt', array(self::SHEET_WIDTH, self::SHEET_HEIGHT), true, 'UTF-8', false);
        
        // Set document properties
        $pdf->SetCreator('ShareMyCard');
        $pdf->SetAuthor($cardData['first_name'] . ' ' . $cardData['last_name']);
        $pdf->SetTitle('Name Tags');
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set margins
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);
        
        // Add page
        $pdf->AddPage();
        
        // Get 8 positions for name tags
        $positions = $this->getNameTagPositions();
        
        // Add each name tag
        foreach ($positions as $position) {
            $this->addNameTag($pdf, $position['x'], $position['y'], $cardData, $preferences);
        }
        
        return $pdf;
    }
    
    /**
     * Calculate 8 positions for name tags on sheet (2 columns x 4 rows)
     */
    private function getNameTagPositions() {
        $positions = [];
        
        for ($row = 0; $row < 4; $row++) {
            for ($col = 0; $col < 2; $col++) {
                $x = self::LEFT_MARGIN + ($col * (self::TAG_WIDTH + self::HORIZONTAL_GAP));
                $y = self::TOP_MARGIN + ($row * (self::TAG_HEIGHT + self::VERTICAL_GAP));
                
                $positions[] = ['x' => $x, 'y' => $y];
            }
        }
        
        return $positions;
    }
    
    /**
     * Add single name tag to PDF at specified position
     */
    private function addNameTag($pdf, $x, $y, $cardData, $preferences) {
        // Draw border (light gray, dashed for cutting guide)
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->SetLineStyle(array('width' => 0.5, 'dash' => '2,2'));
        $pdf->Rect($x, $y, self::TAG_WIDTH, self::TAG_HEIGHT, 'D');
        
        // Reset line style
        $pdf->SetLineStyle(array('width' => 0.5, 'dash' => 0));
        
        // Content area (with padding)
        $contentX = $x + 10;
        $contentY = $y + 10;
        $contentWidth = self::TAG_WIDTH - 20;
        $contentHeight = self::TAG_HEIGHT - 20;
        
        $currentY = $contentY;
        
        // Add signature image (profile photo or company logo) - top center
        if ($preferences['include_signature'] !== 'none') {
            $currentY = $this->addSignatureImage($pdf, $x, $currentY, $cardData, $preferences['include_signature']);
            $currentY += 5; // Small gap after image
        }
        
        // Add QR code on right side
        $qrX = $x + self::TAG_WIDTH - self::QR_SIZE - 10;
        $qrY = $y + (self::TAG_HEIGHT - self::QR_SIZE) / 2;
        $this->addQRCode($pdf, $qrX, $qrY, $cardData['id']);
        
        // Add contact info on left side (avoiding QR code area)
        $textWidth = self::TAG_WIDTH - self::QR_SIZE - 30; // Leave space for QR
        $this->addContactInfo($pdf, $contentX, $currentY, $textWidth, $cardData, $preferences);
    }
    
    /**
     * Add signature image (profile photo or company logo)
     */
    private function addSignatureImage($pdf, $tagX, $y, $cardData, $imageType) {
        $imagePath = null;
        $isProfile = false;
        
        if ($imageType === 'profile' && !empty($cardData['profile_photo_path'])) {
            $imagePath = __DIR__ . '/../../storage/media/' . $cardData['profile_photo_path'];
            $isProfile = true;
        } elseif ($imageType === 'logo' && !empty($cardData['company_logo_path'])) {
            $imagePath = __DIR__ . '/../../storage/media/' . $cardData['company_logo_path'];
            $isProfile = false;
        }
        
        if (!$imagePath || !file_exists($imagePath)) {
            return $y; // No image to add
        }
        
        try {
            if ($isProfile) {
                // Profile photo - circular crop, centered, 0.75" diameter
                $imgSize = self::PROFILE_SIZE;
                $imgX = $tagX + (self::TAG_WIDTH - $imgSize) / 2;
                
                // Use circular mask for profile photos
                $pdf->Image($imagePath, $imgX, $y, $imgSize, $imgSize, '', '', '', true, 300, '', false, false, 1, false, false, true);
                
                return $y + $imgSize;
            } else {
                // Company logo - rectangle, centered, max 1.5" x 0.6"
                list($origWidth, $origHeight) = getimagesize($imagePath);
                
                // Calculate dimensions maintaining aspect ratio
                $ratio = $origWidth / $origHeight;
                if ($ratio > (self::LOGO_MAX_WIDTH / self::LOGO_MAX_HEIGHT)) {
                    // Width is limiting factor
                    $imgWidth = self::LOGO_MAX_WIDTH;
                    $imgHeight = $imgWidth / $ratio;
                } else {
                    // Height is limiting factor
                    $imgHeight = self::LOGO_MAX_HEIGHT;
                    $imgWidth = $imgHeight * $ratio;
                }
                
                $imgX = $tagX + (self::TAG_WIDTH - $imgWidth) / 2;
                
                $pdf->Image($imagePath, $imgX, $y, $imgWidth, $imgHeight, '', '', '', true, 300);
                
                return $y + $imgHeight;
            }
        } catch (Exception $e) {
            // If image fails, just skip it
            error_log("Failed to add signature image: " . $e->getMessage());
            return $y;
        }
    }
    
    /**
     * Add QR code linking to public card view
     */
    private function addQRCode($pdf, $x, $y, $cardId) {
        $qrUrl = "https://sharemycard.app/card.php?id=" . $cardId;
        
        // TCPDF has built-in QR code support
        $style = array(
            'border' => false,
            'padding' => 0,
            'fgcolor' => array(0,0,0),
            'bgcolor' => false
        );
        
        $pdf->write2DBarcode($qrUrl, 'QRCODE,M', $x, $y, self::QR_SIZE, self::QR_SIZE, $style, 'N');
    }
    
    /**
     * Add contact information text
     */
    private function addContactInfo($pdf, $x, $y, $width, $cardData, $preferences) {
        $pdf->SetTextColor(0, 0, 0);
        
        $currentY = $y;
        
        // Name (large, bold)
        if ($preferences['include_name']) {
            $name = $cardData['first_name'] . ' ' . $cardData['last_name'];
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->SetXY($x, $currentY);
            $pdf->Cell($width, 0, $this->truncateText($pdf, $name, $width, 14, 'B'), 0, 1, 'L');
            $currentY += 16;
        }
        
        // Job Title
        if ($preferences['include_title'] && !empty($cardData['job_title'])) {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetXY($x, $currentY);
            $pdf->Cell($width, 0, $this->truncateText($pdf, $cardData['job_title'], $width, 10, ''), 0, 1, 'L');
            $currentY += 12;
        }
        
        // Primary Phone
        if ($preferences['include_phone'] && !empty($cardData['phone_number'])) {
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetXY($x, $currentY);
            $pdf->Cell($width, 0, $this->truncateText($pdf, $cardData['phone_number'], $width, 9, ''), 0, 1, 'L');
            $currentY += 11;
        }
        
        // Primary Email
        if ($preferences['include_email']) {
            $email = $this->getPrimaryEmail($cardData['id']);
            if ($email) {
                $pdf->SetFont('helvetica', '', 9);
                $pdf->SetXY($x, $currentY);
                $pdf->Cell($width, 0, $this->truncateText($pdf, $email, $width, 9, ''), 0, 1, 'L');
                $currentY += 11;
            }
        }
        
        // Address
        if ($preferences['include_address']) {
            $address = $this->getFormattedAddress($cardData['id']);
            if ($address) {
                $pdf->SetFont('helvetica', '', 9);
                $pdf->SetXY($x, $currentY);
                // Address might need multiple lines
                $pdf->MultiCell($width, 11, $this->truncateText($pdf, $address, $width, 9, '', 2), 0, 'L', 0, 1);
            }
        }
    }
    
    /**
     * Truncate text with ellipsis if it exceeds width
     */
    private function truncateText($pdf, $text, $maxWidth, $fontSize, $fontStyle, $maxLines = 1) {
        $pdf->SetFont('helvetica', $fontStyle, $fontSize);
        
        if ($pdf->GetStringWidth($text) <= $maxWidth) {
            return $text;
        }
        
        // Truncate with ellipsis
        $ellipsis = '...';
        $truncated = '';
        $words = explode(' ', $text);
        
        foreach ($words as $word) {
            $test = $truncated . ($truncated ? ' ' : '') . $word;
            if ($pdf->GetStringWidth($test . $ellipsis) > $maxWidth) {
                break;
            }
            $truncated = $test;
        }
        
        return $truncated . $ellipsis;
    }
    
    /**
     * Get card data with all information
     */
    private function getCardData($cardId) {
        return $this->db->querySingle(
            "SELECT * FROM business_cards WHERE id = ? AND is_active = 1",
            [$cardId]
        );
    }
    
    /**
     * Get primary email for card
     */
    private function getPrimaryEmail($cardId) {
        $email = $this->db->querySingle(
            "SELECT email FROM email_contacts WHERE business_card_id = ? ORDER BY is_primary DESC, created_at ASC LIMIT 1",
            [$cardId]
        );
        return $email ? $email['email'] : null;
    }
    
    /**
     * Get formatted address
     */
    private function getFormattedAddress($cardId) {
        $address = $this->db->querySingle(
            "SELECT * FROM addresses WHERE business_card_id = ?",
            [$cardId]
        );
        
        if (!$address) {
            return null;
        }
        
        $parts = array_filter([
            $address['street'] ?? null,
            $address['city'] ?? null,
            ($address['state'] ?? '') . ' ' . ($address['zip_code'] ?? '')
        ]);
        
        return implode(', ', array_map('trim', $parts));
    }
    
    /**
     * Generate PNG preview image of single name tag
     * Used for live preview in web interface
     */
    public function generatePreviewImage($cardId, $preferences) {
        $cardData = $this->getCardData($cardId);
        if (!$cardData) {
            throw new Exception('Card not found');
        }
        
        // Create image (scale 2x for better quality preview)
        $scale = 2;
        $width = self::TAG_WIDTH * $scale;
        $height = self::TAG_HEIGHT * $scale;
        
        $image = imagecreatetruecolor($width, $height);
        if (!$image) {
            throw new Exception('Failed to create image');
        }
        
        // Background color (white)
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $gray = imagecolorallocate($image, 200, 200, 200);
        
        imagefill($image, 0, 0, $white);
        
        // Draw border
        imagerectangle($image, 1, 1, $width-2, $height-2, $gray);
        
        // Add content
        $contentX = 20 * $scale;
        $contentY = 20 * $scale;
        $currentY = $contentY;
        
        // Add signature image if requested
        if ($preferences['include_signature'] !== 'none') {
            $currentY = $this->addSignatureImageToGD($image, $width, $currentY, $cardData, $preferences['include_signature'], $scale);
            $currentY += 10 * $scale;
        }
        
        // Add QR code
        $qrSize = self::QR_SIZE * $scale;
        $qrX = $width - $qrSize - (10 * $scale);
        $qrY = ($height - $qrSize) / 2;
        $this->addQRCodeToGD($image, $qrX, $qrY, $qrSize, $cardData['id']);
        
        // Add text
        $textWidth = $width - $qrSize - (60 * $scale);
        $this->addContactInfoToGD($image, $contentX, $currentY, $textWidth, $cardData, $preferences, $scale, $black);
        
        return $image;
    }
    
    /**
     * Add signature image to GD image
     */
    private function addSignatureImageToGD($image, $width, $y, $cardData, $imageType, $scale) {
        $imagePath = null;
        $isProfile = false;
        
        if ($imageType === 'profile' && !empty($cardData['profile_photo_path'])) {
            $imagePath = __DIR__ . '/../../storage/media/' . $cardData['profile_photo_path'];
            $isProfile = true;
        } elseif ($imageType === 'logo' && !empty($cardData['company_logo_path'])) {
            $imagePath = __DIR__ . '/../../storage/media/' . $cardData['company_logo_path'];
            $isProfile = false;
        }
        
        if (!$imagePath || !file_exists($imagePath)) {
            return $y;
        }
        
        try {
            $srcImage = $this->loadImage($imagePath);
            if (!$srcImage) {
                return $y;
            }
            
            if ($isProfile) {
                // Profile photo - circular
                $size = self::PROFILE_SIZE * $scale;
                $x = ($width - $size) / 2;
                
                // Create circular mask
                $dst = imagecreatetruecolor($size, $size);
                $transparent = imagecolorallocate($dst, 255, 255, 255);
                imagefill($dst, 0, 0, $transparent);
                
                // Resize and copy
                imagecopyresampled($dst, $srcImage, 0, 0, 0, 0, $size, $size, imagesx($srcImage), imagesy($srcImage));
                
                // Apply circular mask
                $mask = imagecreatetruecolor($size, $size);
                $maskColor = imagecolorallocate($mask, 0, 0, 0);
                imagefill($mask, 0, 0, $maskColor);
                $white = imagecolorallocate($mask, 255, 255, 255);
                imagefilledellipse($mask, $size/2, $size/2, $size, $size, $white);
                
                for ($i = 0; $i < $size; $i++) {
                    for ($j = 0; $j < $size; $j++) {
                        $maskPixel = imagecolorat($mask, $i, $j);
                        if ($maskPixel == $maskColor) {
                            imagesetpixel($dst, $i, $j, imagecolorallocate($dst, 255, 255, 255));
                        }
                    }
                }
                
                imagecopy($image, $dst, $x, $y, 0, 0, $size, $size);
                imagedestroy($dst);
                imagedestroy($mask);
                
                return $y + $size;
            } else {
                // Company logo - rectangle
                $maxWidth = self::LOGO_MAX_WIDTH * $scale;
                $maxHeight = self::LOGO_MAX_HEIGHT * $scale;
                
                $origWidth = imagesx($srcImage);
                $origHeight = imagesy($srcImage);
                $ratio = $origWidth / $origHeight;
                
                if ($ratio > ($maxWidth / $maxHeight)) {
                    $dstWidth = $maxWidth;
                    $dstHeight = $dstWidth / $ratio;
                } else {
                    $dstHeight = $maxHeight;
                    $dstWidth = $dstHeight * $ratio;
                }
                
                $x = ($width - $dstWidth) / 2;
                
                $dst = imagecreatetruecolor($dstWidth, $dstHeight);
                imagecopyresampled($dst, $srcImage, 0, 0, 0, 0, $dstWidth, $dstHeight, $origWidth, $origHeight);
                imagecopy($image, $dst, $x, $y, 0, 0, $dstWidth, $dstHeight);
                imagedestroy($dst);
                
                return $y + $dstHeight;
            }
            
            imagedestroy($srcImage);
        } catch (Exception $e) {
            error_log("Failed to add signature image to GD: " . $e->getMessage());
            return $y;
        }
    }
    
    /**
     * Load image from file (supports JPEG, PNG, GIF)
     */
    private function loadImage($path) {
        $imageInfo = getimagesize($path);
        if (!$imageInfo) {
            return false;
        }
        
        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($path);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($path);
            default:
                return false;
        }
    }
    
    /**
     * Add QR code to GD image
     */
    private function addQRCodeToGD($image, $x, $y, $size, $cardId) {
        $qrUrl = "https://sharemycard.app/card.php?id=" . $cardId;
        $encodedData = urlencode($qrUrl);
        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encodedData}&format=png";
        
        $qrCodeImage = file_get_contents($qrCodeUrl);
        if ($qrCodeImage === false) {
            return; // Skip QR if generation fails
        }
        
        $qrResource = imagecreatefromstring($qrCodeImage);
        if ($qrResource === false) {
            return;
        }
        
        imagecopy($image, $qrResource, $x, $y, 0, 0, imagesx($qrResource), imagesy($qrResource));
        imagedestroy($qrResource);
    }
    
    /**
     * Add contact info text to GD image
     */
    private function addContactInfoToGD($image, $x, $y, $width, $cardData, $preferences, $scale, $color) {
        $currentY = $y;
        $fontPath = __DIR__ . '/tcpdf/fonts/';
        
        // Name (large, bold) - using built-in GD fonts for simplicity
        if ($preferences['include_name']) {
            $name = $cardData['first_name'] . ' ' . $cardData['last_name'];
            imagestring($image, 5, $x, $currentY, $name, $color);
            $currentY += 18 * $scale;
        }
        
        // Job Title
        if ($preferences['include_title'] && !empty($cardData['job_title'])) {
            imagestring($image, 3, $x, $currentY, $cardData['job_title'], $color);
            $currentY += 14 * $scale;
        }
        
        // Primary Phone
        if ($preferences['include_phone'] && !empty($cardData['phone_number'])) {
            imagestring($image, 3, $x, $currentY, $cardData['phone_number'], $color);
            $currentY += 13 * $scale;
        }
        
        // Primary Email
        if ($preferences['include_email']) {
            $email = $this->getPrimaryEmail($cardData['id']);
            if ($email) {
                imagestring($image, 2, $x, $currentY, $email, $color);
                $currentY += 12 * $scale;
            }
        }
        
        // Address
        if ($preferences['include_address']) {
            $address = $this->getFormattedAddress($cardData['id']);
            if ($address) {
                imagestring($image, 2, $x, $currentY, substr($address, 0, 40), $color);
            }
        }
    }
}

