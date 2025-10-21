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
    // Standard name tag: 2-1/3" height x 3-3/8" width (2.33" x 3.375")
    const TAG_WIDTH = 243;  // 3.375" x 72 = 243pt (width)
    const TAG_HEIGHT = 168; // 2.33" x 72 = 168pt (height)
    
    // Sheet dimensions
    const SHEET_WIDTH = 612;  // 8.5" x 72 = 612pt
    const SHEET_HEIGHT = 792; // 11" x 72 = 792pt
    
    // Margins and spacing - centered on page
    const LEFT_MARGIN = 39.5;  // (612 - (2*243 + 47)) / 2 = 39.5pt (adjusted for wider gap)
    const TOP_MARGIN = 42;     // (792 - (4*168 + 3*22)) / 2 = 42pt (adjusted for larger gap)
    const HORIZONTAL_GAP = 47; // 27pt + 20px (~15pt) = 47pt
    const VERTICAL_GAP = 22;   // 12pt + 10px (~7.5pt) = 22pt
    
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
                
                // Add extra spacing after the 2nd row (35 pixels = ~26 points total)
                $extraSpacing = 0;
                if ($row == 2) { // After second row (0-indexed, so row 2 is the 3rd row)
                    $extraSpacing = 26; // 20 pixels + 15 more pixels = 35 pixels total (~26 points)
                }
                
                $y = self::TOP_MARGIN + ($row * (self::TAG_HEIGHT + self::VERTICAL_GAP)) + $extraSpacing;
                
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
        
        // Two-column layout: Left side (text), Right side (QR code)
        $leftColumnWidth = $contentWidth * 0.5; // 50% of content width
        $rightColumnWidth = $contentWidth * 0.5; // 50% of content width
        $rightColumnX = $contentX + $leftColumnWidth;
        
        // Calculate total text height to center it vertically
        $textHeight = $this->calculateTextHeight($pdf, $cardData, $preferences, $leftColumnWidth);
        $textStartY = $contentY + (($contentHeight - $textHeight) / 2);
        
        // Add contact info on left side (vertically centered)
        $this->addContactInfo($pdf, $contentX, $textStartY, $leftColumnWidth, $cardData, $preferences);
        
        // Add larger QR code on right side (take up most of the right column)
        $qrSize = min($rightColumnWidth - 20, $contentHeight - 20); // Fill most of right column with padding
        $qrX = $rightColumnX + (($rightColumnWidth - $qrSize) / 2); // Center in right column
        $qrY = $contentY + (($contentHeight - $qrSize) / 2); // Center vertically
        $this->addQRCode($pdf, $qrX, $qrY, $cardData['id'], $qrSize);
    }
    
    /**
     * Add signature image (profile photo or company logo)
     */
    private function addSignatureImage($pdf, $tagX, $y, $cardData, $imageType, $columnWidth = null) {
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
                // Profile photo - circular crop, left-aligned in column
                $imgSize = self::PROFILE_SIZE;
                $imgX = $tagX; // Left-align instead of center
                
                // Use circular mask for profile photos
                $pdf->Image($imagePath, $imgX, $y, $imgSize, $imgSize, '', '', '', true, 300, '', false, false, 1, false, false, true);
                
                return $y + $imgSize;
            } else {
                // Company logo - rectangle, centered in column
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
                
                $imgX = $tagX; // Left-align instead of center
                
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
    private function addQRCode($pdf, $x, $y, $cardId, $size = null) {
        $qrUrl = "https://sharemycard.app/card.php?id=" . $cardId;
        
        // TCPDF has built-in QR code support
        $style = array(
            'border' => false,
            'padding' => 0,
            'fgcolor' => array(0,0,0),
            'bgcolor' => false
        );
        
        $qrSize = $size ?: self::QR_SIZE;
        $pdf->write2DBarcode($qrUrl, 'QRCODE,M', $x, $y, $qrSize, $qrSize, $style, 'N');
    }
    
    /**
     * Get line spacing multiplier based on preference
     */
    private function getLineSpacing($preferences) {
        $spacing = $preferences['line_spacing'] ?? '0';
        // Convert string to float, with 0 as default
        $multiplier = (float)$spacing;
        // Convert to actual spacing multiplier (0 = normal, positive = more space, negative = less space)
        return 1.0 + ($multiplier * 0.1);
    }
    
    /**
     * Get font size from preferences
     */
    private function getFontSize($preferences) {
        $size = $preferences['font_size'] ?? '12';
        // Convert string to float, with 12 as default
        return (float)$size;
    }

    /**
     * Calculate the height needed for main text content (without messages)
     */
    private function calculateMainTextHeight($pdf, $cardData, $preferences, $width) {
        $fontSize = $this->getFontSize($preferences);
        $spacingMultiplier = 1.0; // Fixed line spacing
        
        // Apply dynamic font scaling based on content length
        $maxCharsPerLine = 24; // Based on "john.doe@testcompany.com"
        $longestLine = 0;
        
        // Check each field to find the longest line
        if ($preferences['include_name']) {
            $name = $cardData['first_name'] . ' ' . $cardData['last_name'];
            $longestLine = max($longestLine, mb_strlen($name, 'UTF-8'));
        }
        if ($preferences['include_title']) {
            $longestLine = max($longestLine, mb_strlen($cardData['job_title'], 'UTF-8'));
        }
        if ($preferences['include_phone']) {
            $phone = $this->getPrimaryPhone($cardData['id']);
            if ($phone) {
                $longestLine = max($longestLine, mb_strlen($phone, 'UTF-8'));
            }
        }
        if ($preferences['include_email']) {
            $email = $this->getPrimaryEmail($cardData['id']);
            if ($email) {
                $longestLine = max($longestLine, mb_strlen($email, 'UTF-8'));
            }
        }
        if ($preferences['include_website']) {
            $website = $this->getPrimaryWebsite($cardData['id']);
            if ($website) {
                $longestLine = max($longestLine, mb_strlen($website, 'UTF-8'));
            }
        }
        
        // Scale down font if content is too long
        $effectiveFontSize = $fontSize;
        if ($longestLine > $maxCharsPerLine) {
            $scaleFactor = $maxCharsPerLine / $longestLine;
            $effectiveFontSize = max(4.0, round($fontSize * $scaleFactor, 2));
        } else {
            $effectiveFontSize = $fontSize;
        }
        
        // Use the effective font size for all calculations
        $lineHeight = $effectiveFontSize + 1;
        $totalHeight = 0;
        
        // Name (same size as everything else)
        if ($preferences['include_name']) {
            $totalHeight += $lineHeight * $spacingMultiplier;
        }
        
        // Title
        if ($preferences['include_title']) {
            $totalHeight += $lineHeight * $spacingMultiplier;
        }
        
        // Primary Phone
        if ($preferences['include_phone']) {
            $phone = $this->getPrimaryPhone($cardData['id']);
            if ($phone) {
                $totalHeight += $lineHeight * $spacingMultiplier;
            }
        }
        
        // Primary Email
        if ($preferences['include_email']) {
            $email = $this->getPrimaryEmail($cardData['id']);
            if ($email) {
                $totalHeight += $lineHeight * $spacingMultiplier;
            }
        }
        
        // Primary Website
        if ($preferences['include_website']) {
            $website = $this->getPrimaryWebsite($cardData['id']);
            if ($website) {
                $totalHeight += $lineHeight * $spacingMultiplier;
            }
        }
        
        // Address
        if ($preferences['include_address']) {
            $address = $this->getFormattedAddress($cardData['id']);
            if ($address) {
                $totalHeight += $lineHeight * 2 * $spacingMultiplier; // Address might wrap to 2 lines
            }
        }
        
        return $totalHeight;
    }

    /**
     * Calculate total height needed for text content
     */
    private function calculateTextHeight($pdf, $cardData, $preferences, $width) {
        $totalHeight = 0;
        $fontFamily = 'helvetica'; // Fixed font family
        $fontSize = $this->getFontSize($preferences);
        $spacingMultiplier = 1.0; // Fixed line spacing
        
        // Apply dynamic font scaling
        $contentStrings = $this->getContentStrings($cardData, $preferences);
        $longestLen = 0;
        foreach ($contentStrings as $s) {
            $len = mb_strlen($s ?? '', 'UTF-8');
            if ($len > $longestLen) $longestLen = $len;
        }
        
        $maxCharsPerLine = mb_strlen('john.doe@testcompany.com', 'UTF-8'); // 24
        if ($longestLen > 0 && $maxCharsPerLine > 0) {
            $scaleFactor = min(1.0, $maxCharsPerLine / $longestLen);
            $effectiveFontSize = max(4.0, round($fontSize * $scaleFactor, 2));
        } else {
            $effectiveFontSize = $fontSize;
        }
        
        // Use the effective font size for all calculations
        $lineHeight = $effectiveFontSize + 1;
        
        // Message above (if provided) - larger font with extra spacing
        if (!empty($preferences['message_above'])) {
            $messageFontSize = $effectiveFontSize + 6;
            $messageLineHeight = $messageFontSize + 1;
            $totalHeight += 8 + $messageLineHeight * $spacingMultiplier + 12; // Reduced space above + message + space below
        } else {
            // Add space when no upper message to maintain consistent layout
            $totalHeight += 8; // Reduced space for upper message area
        }
        
        // Name (same size as everything else)
        if ($preferences['include_name']) {
            $totalHeight += $lineHeight * $spacingMultiplier;
        }
        
        // Job Title
        if ($preferences['include_title'] && !empty($cardData['job_title'])) {
            $totalHeight += $lineHeight * $spacingMultiplier;
        }
        
        // Primary Phone
        if ($preferences['include_phone'] && !empty($cardData['phone_number'])) {
            $totalHeight += $lineHeight * $spacingMultiplier;
        }
        
        // Primary Email
        if ($preferences['include_email']) {
            $email = $this->getPrimaryEmail($cardData['id']);
            if ($email) {
                $totalHeight += $lineHeight * $spacingMultiplier;
            }
        }
        
        // Primary Website
        if ($preferences['include_website']) {
            $website = $this->getPrimaryWebsite($cardData['id']);
            if ($website) {
                $totalHeight += $lineHeight * $spacingMultiplier;
            }
        }
        
        // Address
        if ($preferences['include_address']) {
            $address = $this->getFormattedAddress($cardData['id']);
            if ($address) {
                $totalHeight += $lineHeight * 2 * $spacingMultiplier; // Address might wrap to 2 lines
            }
        }
        
        // Message below (if provided) - larger font with extra spacing
        if (!empty($preferences['message_below'])) {
            $messageFontSize = $effectiveFontSize + 6;
            $messageLineHeight = $messageFontSize + 1;
            $totalHeight += $messageLineHeight * $spacingMultiplier + 12; // More spacing
        } else {
            // Add space when no lower message to maintain consistent layout
            $totalHeight += 12; // Space for lower message area
        }
        
        return $totalHeight;
    }

    /**
     * Add contact information text
     */
    private function addContactInfo($pdf, $x, $y, $width, $cardData, $preferences) {
        $pdf->SetTextColor(0, 0, 0);
        
        $currentY = $y;
        $fontFamily = $preferences['font_family'] ?? 'helvetica';
        $fontSize = $this->getFontSize($preferences);
        $spacingMultiplier = $this->getLineSpacing($preferences);
        
        // Name (same size as everything else, but bold)
        if ($preferences['include_name']) {
            $name = $cardData['first_name'] . ' ' . $cardData['last_name'];
            $pdf->SetFont($fontFamily, 'B', $fontSize);
            $pdf->SetXY($x, $currentY);
            $pdf->Cell($width, 0, $this->truncateText($pdf, $name, $width, $fontSize, 'B'), 0, 1, 'L');
            $currentY += ($fontSize + 1) * $spacingMultiplier;
        }
        
        // Job Title
        if ($preferences['include_title'] && !empty($cardData['job_title'])) {
            $pdf->SetFont($fontFamily, '', $fontSize);
            $pdf->SetXY($x, $currentY);
            $pdf->Cell($width, 0, $this->truncateText($pdf, $cardData['job_title'], $width, $fontSize, ''), 0, 1, 'L');
            $currentY += ($fontSize + 1) * $spacingMultiplier;
        }
        
        // Primary Phone
        if ($preferences['include_phone'] && !empty($cardData['phone_number'])) {
            $pdf->SetFont($fontFamily, '', $fontSize);
            $pdf->SetXY($x, $currentY);
            $pdf->Cell($width, 0, $this->truncateText($pdf, $cardData['phone_number'], $width, $fontSize, ''), 0, 1, 'L');
            $currentY += ($fontSize + 1) * $spacingMultiplier;
        }
        
        // Primary Email
        if ($preferences['include_email']) {
            $email = $this->getPrimaryEmail($cardData['id']);
            if ($email) {
                $pdf->SetFont($fontFamily, '', $fontSize);
                $pdf->SetXY($x, $currentY);
                $pdf->Cell($width, 0, $this->truncateText($pdf, $email, $width, $fontSize, ''), 0, 1, 'L');
                $currentY += ($fontSize + 1) * $spacingMultiplier;
            }
        }
        
        // Primary Website
        if ($preferences['include_website']) {
            $website = $this->getPrimaryWebsite($cardData['id']);
            if ($website) {
                $pdf->SetFont($fontFamily, '', $fontSize);
                $pdf->SetXY($x, $currentY);
                $pdf->Cell($width, 0, $this->truncateText($pdf, $website, $width, $fontSize, ''), 0, 1, 'L');
                $currentY += ($fontSize + 1) * $spacingMultiplier;
            }
        }
        
        // Address
        if ($preferences['include_address']) {
            $address = $this->getFormattedAddress($cardData['id']);
            if ($address) {
                $pdf->SetFont($fontFamily, '', $fontSize);
                $pdf->SetXY($x, $currentY);
                // Address might need multiple lines
                $pdf->MultiCell($width, ($fontSize + 1) * $spacingMultiplier, $this->truncateText($pdf, $address, $width, $fontSize, '', 2), 0, 'L', 0, 1);
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
        // For testing purposes, return dummy data for 'test' card ID
        if ($cardId === 'test') {
            return [
                'id' => 'test',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'job_title' => 'Software Engineer',
                'company_name' => 'Test Company',
                'phone_number' => '(555) 123-4567',
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        
        return $this->db->querySingle(
            "SELECT * FROM business_cards WHERE id = ? AND is_active = 1",
            [$cardId]
        );
    }
    
    /**
     * Get primary phone for a business card
     */
    private function getPrimaryPhone($cardId) {
        // For testing purposes, return dummy phone for 'test' card ID
        if ($cardId === 'test') {
            return '+1 (555) 123-4567';
        }
        
        $phone = $this->db->querySingle(
            "SELECT * FROM phone_contacts WHERE business_card_id = ? ORDER BY created_at ASC LIMIT 1",
            [$cardId]
        );
        
        if (!$phone) {
            return null;
        }
        
        return $phone['phone_number'] ?? null;
    }

    /**
     * Get primary email for card
     */
    private function getPrimaryEmail($cardId) {
        // For testing purposes, return dummy email for 'test' card ID
        if ($cardId === 'test') {
            return 'john.doe@testcompany.com';
        }
        
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
        // For testing purposes, return dummy address for 'test' card ID
        if ($cardId === 'test') {
            return '123 Test Street, Test City, TC 12345';
        }
        
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
     * Get primary website URL
     */
    private function getPrimaryWebsite($cardId) {
        // For testing purposes, return dummy website for 'test' card ID
        if ($cardId === 'test') {
            return 'https://testcompany.com';
        }
        
        $website = $this->db->querySingle(
            "SELECT * FROM website_links WHERE business_card_id = ? AND is_primary = 1",
            [$cardId]
        );
        
        if (!$website) {
            return null;
        }
        
        return $website['url'] ?? null;
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
        
        // Add content with two-column layout
        $contentX = 20 * $scale;
        $contentY = 20 * $scale;
        $contentWidth = $width - (40 * $scale);
        $contentHeight = $height - (40 * $scale);
        
        // Two-column layout: Left side (text), Right side (QR code)
        $leftColumnWidth = $contentWidth * 0.5; // 50% of content width
        $rightColumnWidth = $contentWidth * 0.5; // 50% of content width
        $rightColumnX = $contentX + $leftColumnWidth;
        
        // Apply dynamic font scaling (same logic as HTML version)
        $contentStrings = $this->getContentStrings($cardData, $preferences);
        $longestLen = 0;
        foreach ($contentStrings as $s) {
            $len = mb_strlen($s ?? '', 'UTF-8');
            if ($len > $longestLen) $longestLen = $len;
        }
        
        $maxCharsPerLine = mb_strlen('john.doe@testcompany.com', 'UTF-8'); // 24
        $originalFontSize = $this->getFontSize($preferences);
        if ($longestLen > 0 && $maxCharsPerLine > 0) {
            $scaleFactor = min(1.0, $maxCharsPerLine / $longestLen);
            $effectiveFontSize = max(4.0, round($originalFontSize * $scaleFactor, 2));
        } else {
            $effectiveFontSize = $originalFontSize;
        }
        
        // Update preferences with effective font size for GD rendering
        $preferences['font_size'] = $effectiveFontSize;
        
        // Calculate total text height to center it vertically
        $textHeight = $this->calculateTextHeightGD($cardData, $preferences, $leftColumnWidth, $scale);
        $textStartY = $contentY + (($contentHeight - $textHeight) / 2);
        
        // Apply dynamic QR sizing (same logic as HTML version)
        $qrMaxPt = 70 - max(0, ($longestLen - 10)) * 1.2; // shrink ~1.2pt per extra char
        $qrMaxPt = max(40, min(70, $qrMaxPt));
        $qrSize = min($qrMaxPt * $scale, $rightColumnWidth - (20 * $scale), $contentHeight - (20 * $scale));
        
        $qrX = $rightColumnX + (($rightColumnWidth - $qrSize) / 2); // Center in right column
        $qrY = $contentY + (($contentHeight - $qrSize) / 2); // Center vertically
        $this->addQRCodeToGD($image, $qrX, $qrY, $qrSize, $cardData['id']);
        
        // Add text (left column, vertically centered)
        $this->addContactInfoToGD($image, $contentX, $textStartY, $leftColumnWidth, $cardData, $preferences, $scale, $black);
        
        return $image;
    }
    
    /**
     * Add signature image to GD image
     */
    private function addSignatureImageToGD($image, $width, $y, $cardData, $imageType, $scale, $offsetX = 0) {
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
                // Profile photo - circular, left-aligned
                $size = self::PROFILE_SIZE * $scale;
                $x = $offsetX; // Left-align instead of center
                
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
                
                $x = $offsetX; // Left-align instead of center
                
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
    /**
     * Calculate total height needed for text content in GD
     */
    private function calculateTextHeightGD($cardData, $preferences, $width, $scale) {
        $totalHeight = 0;
        $fontSize = $this->getFontSize($preferences);
        $spacingMultiplier = $this->getLineSpacing($preferences);
        
        // Name (same size as everything else)
        if ($preferences['include_name']) {
            $totalHeight += ($fontSize + 1) * $scale * $spacingMultiplier;
        }
        
        // Job Title (same size as everything else)
        if ($preferences['include_title'] && !empty($cardData['job_title'])) {
            $totalHeight += ($fontSize + 1) * $scale * $spacingMultiplier;
        }
        
        // Primary Phone (same size as everything else)
        if ($preferences['include_phone'] && !empty($cardData['phone_number'])) {
            $totalHeight += ($fontSize + 1) * $scale * $spacingMultiplier;
        }
        
        // Primary Email (same size as everything else)
        if ($preferences['include_email']) {
            $email = $this->getPrimaryEmail($cardData['id']);
            if ($email) {
                $totalHeight += ($fontSize + 1) * $scale * $spacingMultiplier;
            }
        }
        
        // Primary Website (same size as everything else)
        if ($preferences['include_website']) {
            $website = $this->getPrimaryWebsite($cardData['id']);
            if ($website) {
                $totalHeight += ($fontSize + 1) * $scale * $spacingMultiplier;
            }
        }
        
        // Address (same size as everything else)
        if ($preferences['include_address']) {
            $address = $this->getFormattedAddress($cardData['id']);
            if ($address) {
                $totalHeight += ($fontSize + 1) * 2 * $scale * $spacingMultiplier; // Address might wrap to 2 lines
            }
        }
        
        return $totalHeight;
    }

    private function addContactInfoToGD($image, $x, $y, $width, $cardData, $preferences, $scale, $color) {
        $currentY = $y;
        $fontFamily = $preferences['font_family'] ?? 'helvetica';
        $fontSize = $this->getFontSize($preferences);
        $spacingMultiplier = $this->getLineSpacing($preferences);
        $font = $this->getFontPath($fontFamily);
        
        // Use TTF fonts if available, otherwise fall back to built-in GD fonts
        $useTTF = $font !== null && file_exists($font);
        
        // Name (same size as everything else, but bold)
        if ($preferences['include_name']) {
            $name = $cardData['first_name'] . ' ' . $cardData['last_name'];
            if ($useTTF) {
                $scaledFontSize = $fontSize * $scale; // Same size as everything else
                $bbox = imagettfbbox($scaledFontSize, 0, $font, $name);
                if ($bbox !== false) {
                    imagettftext($image, $scaledFontSize, 0, $x, $currentY - $bbox[5], $color, $font, $name);
                }
            } else {
                // Fallback to built-in GD font
                imagestring($image, 5, $x, $currentY, $name, $color);
            }
            $currentY += ($fontSize + 1) * $scale * $spacingMultiplier;
        }
        
        // Job Title
        if ($preferences['include_title'] && !empty($cardData['job_title'])) {
            if ($useTTF) {
                $scaledFontSize = $fontSize * $scale; // Same size as everything else
                $bbox = imagettfbbox($scaledFontSize, 0, $font, $cardData['job_title']);
                if ($bbox !== false) {
                    imagettftext($image, $scaledFontSize, 0, $x, $currentY - $bbox[5], $color, $font, $cardData['job_title']);
                }
            } else {
                imagestring($image, 3, $x, $currentY, $cardData['job_title'], $color);
            }
            $currentY += ($fontSize + 1) * $scale * $spacingMultiplier;
        }
        
        // Primary Phone
        if ($preferences['include_phone'] && !empty($cardData['phone_number'])) {
            if ($useTTF) {
                $scaledFontSize = $fontSize * $scale; // Same size as everything else
                $bbox = imagettfbbox($scaledFontSize, 0, $font, $cardData['phone_number']);
                if ($bbox !== false) {
                    imagettftext($image, $scaledFontSize, 0, $x, $currentY - $bbox[5], $color, $font, $cardData['phone_number']);
                }
            } else {
                imagestring($image, 3, $x, $currentY, $cardData['phone_number'], $color);
            }
            $currentY += ($fontSize + 1) * $scale * $spacingMultiplier;
        }
        
        // Primary Email
        if ($preferences['include_email']) {
            $email = $this->getPrimaryEmail($cardData['id']);
            if ($email) {
                if ($useTTF) {
                    $scaledFontSize = $fontSize * $scale; // Same size as everything else
                    $bbox = imagettfbbox($scaledFontSize, 0, $font, $email);
                    if ($bbox !== false) {
                        imagettftext($image, $scaledFontSize, 0, $x, $currentY - $bbox[5], $color, $font, $email);
                    }
                } else {
                    imagestring($image, 2, $x, $currentY, $email, $color);
                }
                $currentY += ($fontSize + 1) * $scale * $spacingMultiplier;
            }
        }
        
        // Primary Website
        if ($preferences['include_website']) {
            $website = $this->getPrimaryWebsite($cardData['id']);
            if ($website) {
                if ($useTTF) {
                    $scaledFontSize = $fontSize * $scale; // Same size as everything else
                    $bbox = imagettfbbox($scaledFontSize, 0, $font, $website);
                    if ($bbox !== false) {
                        imagettftext($image, $scaledFontSize, 0, $x, $currentY - $bbox[5], $color, $font, $website);
                    }
                } else {
                    imagestring($image, 2, $x, $currentY, substr($website, 0, 40), $color);
                }
                $currentY += ($fontSize + 1) * $scale * $spacingMultiplier;
            }
        }
        
        // Address
        if ($preferences['include_address']) {
            $address = $this->getFormattedAddress($cardData['id']);
            if ($address) {
                if ($useTTF) {
                    $scaledFontSize = $fontSize * $scale; // Same size as everything else
                    $bbox = imagettfbbox($scaledFontSize, 0, $font, $address);
                    if ($bbox !== false) {
                        imagettftext($image, $scaledFontSize, 0, $x, $currentY - $bbox[5], $color, $font, $address);
                    }
                } else {
                    imagestring($image, 2, $x, $currentY, substr($address, 0, 40), $color);
                }
            }
        }
    }
    
    /**
     * Get font path for GD text rendering
     */
    private function getFontPath($fontFamily) {
        // Use the available DejaVu Sans Mono for all fonts since it's the only TTF available
        $availableFont = '/usr/share/fonts/dejavu/DejaVuSansMono.ttf';
        
        if (file_exists($availableFont)) {
            return $availableFont;
        }
        
        // Fallback to built-in GD fonts (return null to use imagestring)
        return null;
    }
    
    /**
     * Get content strings for length calculation
     */
    private function getContentStrings($cardData, $preferences) {
        $contentStrings = [];
        
        if ($preferences['include_name']) {
            $contentStrings[] = $cardData['first_name'] . ' ' . $cardData['last_name'];
        }
        
        if ($preferences['include_title'] && !empty($cardData['job_title'])) {
            $contentStrings[] = $cardData['job_title'];
        }
        
        if ($preferences['include_phone'] && !empty($cardData['phone_number'])) {
            $contentStrings[] = $cardData['phone_number'];
        }
        
        if ($preferences['include_email']) {
            $email = $this->getPrimaryEmail($cardData['id']);
            if ($email) {
                $contentStrings[] = $email;
            }
        }
        
        if ($preferences['include_website']) {
            $website = $this->getPrimaryWebsite($cardData['id']);
            if ($website) {
                $contentStrings[] = $website;
            }
        }
        
        if ($preferences['include_address']) {
            $address = $this->getFormattedAddress($cardData['id']);
            if ($address) {
                $contentStrings[] = $address;
            }
        }
        
        return $contentStrings;
    }
    
    // ============================================================================
    // NEW IMAGE-BASED APPROACH METHODS
    // ============================================================================
    
    /**
     * Generate complete PDF sheet with 8 name tags using image-based approach
     */
    public function generateNameTagSheetImageBased($cardId, $preferences) {
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
        
        // Generate high-resolution name tag image
        $nameTagImage = $this->generateHighResNameTagImage($cardData, $preferences);
        
        // Add 8 name tags to the sheet
        for ($row = 0; $row < 2; $row++) {
            for ($col = 0; $col < 4; $col++) {
                $pdf->AddPage();
                
                // Calculate position for this name tag
                $x = self::LEFT_MARGIN + ($col * (self::TAG_WIDTH + self::HORIZONTAL_GAP));
                $y = self::TOP_MARGIN + ($row * (self::TAG_HEIGHT + self::VERTICAL_GAP));
                
                // Embed the name tag image
                $pdf->Image($nameTagImage, $x, $y, self::TAG_WIDTH, self::TAG_HEIGHT, 'PNG');
            }
        }
        
        // Clean up temporary image file
        if (file_exists($nameTagImage)) {
            unlink($nameTagImage);
        }
        
        return $pdf;
    }
    
    /**
     * Generate high-resolution name tag image (300 DPI for print quality)
     */
    private function generateHighResNameTagImage($cardData, $preferences) {
        // High resolution: 300 DPI
        // Name tag: 3.375" x 2.33" = 1012.5 x 699 pixels
        $dpi = 300;
        $width = round(3.375 * $dpi);  // 1013 pixels
        $height = round(2.33 * $dpi);  // 699 pixels
        
        // Create high-resolution image
        $image = imagecreatetruecolor($width, $height);
        if (!$image) {
            throw new Exception('Failed to create high-resolution image');
        }
        
        // Set up colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        
        // Fill background
        imagefill($image, 0, 0, $white);
        
        // Calculate layout dimensions
        $padding = 20; // 20 pixels padding
        $contentWidth = $width - ($padding * 2);
        $contentHeight = $height - ($padding * 2);
        
        // Two-column layout: 50% each
        $leftColumnWidth = $contentWidth * 0.5;
        $rightColumnWidth = $contentWidth * 0.5;
        $rightColumnX = $padding + $leftColumnWidth;
        
        // Get font settings
        $fontFamily = $preferences['font_family'] ?? 'helvetica';
        // Start from a smaller base font size to reduce crowding (6–8pt clamp)
        $requestedSize = $this->getFontSize($preferences);
        $fontSize = max(6, min(8, (float)$requestedSize));
        $lineSpacing = $this->getLineSpacing($preferences);
        
        // Scale font size for high resolution (300 DPI)
        $scaledFontSize = $fontSize * ($dpi / 72); // Convert from 72 DPI to 300 DPI
        $font = $this->getFontPath($fontFamily);
        
        // Add contact information to left column
        $this->addContactInfoToHighResImage($image, $padding, $padding, $leftColumnWidth, $contentHeight, $cardData, $preferences, $scaledFontSize, $font, $black, $lineSpacing);
        
        // Add QR code to right column
        $this->addQRCodeToHighResImage($image, $rightColumnX, $padding, $rightColumnWidth, $contentHeight, $cardData['id'], $dpi);
        
        // Save to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'nametag_') . '.png';
        imagepng($image, $tempFile, 0); // No compression for best quality
        imagedestroy($image);
        
        return $tempFile;
    }
    
    /**
     * Add contact information to high-resolution image
     */
    private function addContactInfoToHighResImage($image, $x, $y, $width, $height, $cardData, $preferences, $fontSize, $font, $color, $lineSpacing) {
        $currentY = $y;
        $useTTF = $font !== null && file_exists($font);
        
        // Calculate total text height for vertical centering
        $totalTextHeight = $this->calculateHighResTextHeight($cardData, $preferences, $fontSize, $lineSpacing);
        $startY = $y + (($height - $totalTextHeight) / 2);
        $currentY = $startY;
        
        // Name (bold, same size as everything else)
        if ($preferences['include_name']) {
            $name = $cardData['first_name'] . ' ' . $cardData['last_name'];
            if ($useTTF) {
                $bbox = imagettfbbox($fontSize, 0, $font, $name);
                if ($bbox !== false) {
                    imagettftext($image, $fontSize, 0, $x, $currentY - $bbox[5], $color, $font, $name);
                }
            } else {
                imagestring($image, 5, $x, $currentY, $name, $color);
            }
            $currentY += ($fontSize + 1) * $lineSpacing;
        }
        
        // Job Title
        if ($preferences['include_title'] && !empty($cardData['job_title'])) {
            if ($useTTF) {
                $bbox = imagettfbbox($fontSize, 0, $font, $cardData['job_title']);
                if ($bbox !== false) {
                    imagettftext($image, $fontSize, 0, $x, $currentY - $bbox[5], $color, $font, $cardData['job_title']);
                }
            } else {
                imagestring($image, 5, $x, $currentY, $cardData['job_title'], $color);
            }
            $currentY += ($fontSize + 1) * $lineSpacing;
        }
        
        // Primary Phone
        if ($preferences['include_phone'] && !empty($cardData['phone_number'])) {
            if ($useTTF) {
                $bbox = imagettfbbox($fontSize, 0, $font, $cardData['phone_number']);
                if ($bbox !== false) {
                    imagettftext($image, $fontSize, 0, $x, $currentY - $bbox[5], $color, $font, $cardData['phone_number']);
                }
            } else {
                imagestring($image, 5, $x, $currentY, $cardData['phone_number'], $color);
            }
            $currentY += ($fontSize + 1) * $lineSpacing;
        }
        
        // Primary Email
        if ($preferences['include_email']) {
            $email = $this->getPrimaryEmail($cardData['id']);
            if ($email) {
                if ($useTTF) {
                    $bbox = imagettfbbox($fontSize, 0, $font, $email);
                    if ($bbox !== false) {
                        imagettftext($image, $fontSize, 0, $x, $currentY - $bbox[5], $color, $font, $email);
                    }
                } else {
                    imagestring($image, 5, $x, $currentY, $email, $color);
                }
                $currentY += ($fontSize + 1) * $lineSpacing;
            }
        }
        
        // Primary Website
        if ($preferences['include_website']) {
            $website = $this->getPrimaryWebsite($cardData['id']);
            if ($website) {
                if ($useTTF) {
                    $bbox = imagettfbbox($fontSize, 0, $font, $website);
                    if ($bbox !== false) {
                        imagettftext($image, $fontSize, 0, $x, $currentY - $bbox[5], $color, $font, $website);
                    }
                } else {
                    imagestring($image, 5, $x, $currentY, $website, $color);
                }
                $currentY += ($fontSize + 1) * $lineSpacing;
            }
        }
        
        // Address
        if ($preferences['include_address']) {
            $address = $this->getFormattedAddress($cardData['id']);
            if ($address) {
                if ($useTTF) {
                    $bbox = imagettfbbox($fontSize, 0, $font, $address);
                    if ($bbox !== false) {
                        imagettftext($image, $fontSize, 0, $x, $currentY - $bbox[5], $color, $font, $address);
                    }
                } else {
                    imagestring($image, 5, $x, $currentY, $address, $color);
                }
                $currentY += ($fontSize + 1) * $lineSpacing;
            }
        }
    }
    
    /**
     * Add QR code to high-resolution image
     */
    private function addQRCodeToHighResImage($image, $x, $y, $width, $height, $cardId, $dpi) {
        // Generate QR code URL
        $qrUrl = "https://sharemycard.app/card.php?id=" . urlencode($cardId);
        
        // Calculate QR code size (fill most of the right column)
        $qrSize = min($width - 40, $height - 40); // 40 pixels padding
        $qrX = $x + (($width - $qrSize) / 2);
        $qrY = $y + (($height - $qrSize) / 2);
        
        // Generate QR code image
        $qrImage = $this->generateQRCodeImage($qrUrl, $qrSize);
        
        if ($qrImage) {
            // Copy QR code to main image
            imagecopy($image, $qrImage, $qrX, $qrY, 0, 0, $qrSize, $qrSize);
            imagedestroy($qrImage);
        }
    }
    
    /**
     * Generate QR code as GD image
     */
    private function generateQRCodeImage($url, $size) {
        // Use external QR code service
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($url);
        
        // Get QR code image
        $qrData = file_get_contents($qrUrl);
        if ($qrData === false) {
            return false;
        }
        
        // Create image from data
        $qrImage = imagecreatefromstring($qrData);
        return $qrImage;
    }
    
    /**
     * Calculate total text height for high-resolution image
     */
    private function calculateHighResTextHeight($cardData, $preferences, $fontSize, $lineSpacing) {
        $totalHeight = 0;
        
        if ($preferences['include_name']) {
            $totalHeight += ($fontSize + 1) * $lineSpacing;
        }
        
        if ($preferences['include_title'] && !empty($cardData['job_title'])) {
            $totalHeight += ($fontSize + 1) * $lineSpacing;
        }
        
        if ($preferences['include_phone'] && !empty($cardData['phone_number'])) {
            $totalHeight += ($fontSize + 1) * $lineSpacing;
        }
        
        if ($preferences['include_email']) {
            $email = $this->getPrimaryEmail($cardData['id']);
            if ($email) {
                $totalHeight += ($fontSize + 1) * $lineSpacing;
            }
        }
        
        if ($preferences['include_website']) {
            $website = $this->getPrimaryWebsite($cardData['id']);
            if ($website) {
                $totalHeight += ($fontSize + 1) * $lineSpacing;
            }
        }
        
        if ($preferences['include_address']) {
            $address = $this->getFormattedAddress($cardData['id']);
            if ($address) {
                $totalHeight += ($fontSize + 1) * $lineSpacing;
            }
        }
        
        return $totalHeight;
    }
    
    /**
     * Generate preview image using high-resolution approach (scaled down for web)
     */
    public function generatePreviewImageImageBased($cardId, $preferences) {
        // Get card data with all contact information
        $cardData = $this->getCardData($cardId);
        if (!$cardData) {
            throw new Exception('Card not found');
        }
        
        // Generate high-resolution image
        $highResImage = $this->generateHighResNameTagImage($cardData, $preferences);
        
        // Scale down for web preview (2x scale for better quality)
        $scale = 2;
        $previewWidth = round(3.375 * 72 * $scale);  // 3.375" at 72 DPI, scaled
        $previewHeight = round(2.33 * 72 * $scale);  // 2.33" at 72 DPI, scaled
        
        // Create preview image
        $previewImage = imagecreatetruecolor($previewWidth, $previewHeight);
        if (!$previewImage) {
            throw new Exception('Failed to create preview image');
        }
        
        // Load high-resolution image
        $sourceImage = imagecreatefrompng($highResImage);
        if (!$sourceImage) {
            throw new Exception('Failed to load high-resolution image');
        }
        
        // Scale down
        imagecopyresampled($previewImage, $sourceImage, 0, 0, 0, 0, $previewWidth, $previewHeight, imagesx($sourceImage), imagesy($sourceImage));
        
        // Clean up
        imagedestroy($sourceImage);
        unlink($highResImage);
        
        return $previewImage;
    }
    
    // ============================================================================
    // HTML/CSS-BASED APPROACH METHODS
    // ============================================================================
    
    /**
     * Generate complete PDF sheet with 8 name tags using HTML/CSS approach
     */
    public function generateNameTagSheetHTML($cardId, $preferences) {
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
        
        // Generate HTML for name tag
        $nameTagHTML = $this->generateNameTagHTMLInternal($cardData, $preferences);
        
        // Add a single page
        $pdf->AddPage();
        
        // Add 8 name tags to the sheet (2 columns x 4 rows)
        for ($row = 0; $row < 4; $row++) {
            for ($col = 0; $col < 2; $col++) {
                // Calculate position for this name tag
                $x = self::LEFT_MARGIN + ($col * (self::TAG_WIDTH + self::HORIZONTAL_GAP));
                $y = self::TOP_MARGIN + ($row * (self::TAG_HEIGHT + self::VERTICAL_GAP));
                
                // No border lines - clean name tags
                
                // Add contact info using TCPDF native methods
                $this->addContactInfoToPDF($pdf, $x, $y, $cardData, $preferences);
                
                // Add QR code using TCPDF native method
                $this->addQRCodeToPDF($pdf, $x, $y, $cardData['id'], $preferences);
            }
        }
        
        return $pdf;
    }
    
    /**
     * Generate HTML for a single name tag (public method)
     */
    public function generateNameTagHTML($cardId, $preferences) {
        $cardData = $this->getCardData($cardId);
        if (!$cardData) {
            throw new Exception('Card not found');
        }
        
        return $this->generateNameTagHTMLInternal($cardData, $preferences);
    }
    
    /**
     * Generate HTML for a single name tag (internal method)
     */
    private function generateNameTagHTMLInternal($cardData, $preferences) {
        $fontFamily = $preferences['font_family'] ?? 'helvetica';
        $fontSize = $this->getFontSize($preferences);
        $lineSpacing = $this->getLineSpacing($preferences);
        
        // Convert font family to web-safe fonts
        $webFontFamily = $this->getWebFontFamily($fontFamily);
        
        // Build contact information and collect lengths for QR sizing heuristic
        $contactInfo = [];
        $contentStrings = [];
        
        // Add message above if provided (will be handled separately in table structure)
        $messageAbove = '';
        if (!empty($preferences['message_above'])) {
            $messageAbove = htmlspecialchars($preferences['message_above']);
            $contentStrings[] = $messageAbove;
        }
        
        if ($preferences['include_name']) {
            $name = htmlspecialchars($cardData['first_name'] . ' ' . $cardData['last_name']);
            $contactInfo[] = "<div class='name'>{$name}</div>";
            $contentStrings[] = $name;
        }
        
        if ($preferences['include_title'] && !empty($cardData['job_title'])) {
            $title = htmlspecialchars($cardData['job_title']);
            $contactInfo[] = "<div class='title'>{$title}</div>";
            $contentStrings[] = $title;
        }
        
        if ($preferences['include_phone'] && !empty($cardData['phone_number'])) {
            $phone = htmlspecialchars($cardData['phone_number']);
            $contactInfo[] = "<div class='contact'>{$phone}</div>";
            $contentStrings[] = $phone;
        }
        
        if ($preferences['include_email']) {
            $email = $this->getPrimaryEmail($cardData['id']);
            if ($email) {
                $email = htmlspecialchars($email);
                $contactInfo[] = "<div class='contact'>{$email}</div>";
                $contentStrings[] = $email;
            }
        }
        
        if ($preferences['include_website']) {
            $website = $this->getPrimaryWebsite($cardData['id']);
            if ($website) {
                $website = htmlspecialchars($website);
                $contactInfo[] = "<div class='contact'>{$website}</div>";
                $contentStrings[] = $website;
            }
        }
        
        if ($preferences['include_address']) {
            $address = $this->getFormattedAddress($cardData['id']);
            if ($address) {
                $address = htmlspecialchars($address);
                $contactInfo[] = "<div class='contact'>{$address}</div>";
                $contentStrings[] = $address;
            }
        }
        
        // Add message below if provided (will be handled separately in table structure)
        $messageBelow = '';
        if (!empty($preferences['message_below'])) {
            $messageBelow = htmlspecialchars($preferences['message_below']);
            $contentStrings[] = $messageBelow;
        }
        
        $contactHTML = implode('', $contactInfo);
        
        // Generate QR code URL
        $qrUrl = "https://sharemycard.app/card.php?id=" . urlencode($cardData['id']);

        // Heuristic: size QR based on longest line length so text never overlaps
        $longestLen = 0;
        foreach ($contentStrings as $s) {
            $len = mb_strlen($s ?? '', 'UTF-8');
            if ($len > $longestLen) $longestLen = $len;
        }
        
        // Dynamic font downscale if any line exceeds allowed characters (use email length as baseline)
        $maxCharsPerLine = mb_strlen('john.doe@testcompany.com', 'UTF-8'); // 24
        if ($longestLen > 0 && $maxCharsPerLine > 0) {
            $scale = min(1.0, $maxCharsPerLine / $longestLen);
            $effectiveFontSize = max(4.0, round($fontSize * $scale, 2));
        } else {
            $effectiveFontSize = $fontSize;
        }
        
        // Map longest length to a QR max width (in pt). Longer text => smaller QR.
        // Tuned: 40pt (min) to 70pt (max). At ~10 chars -> ~70pt, at 35+ -> ~40pt
        $qrMaxPt = 70 - max(0, ($longestLen - 10)) * 1.2; // shrink ~1.2pt per extra char
        $qrMaxPt = max(40, min(70, $qrMaxPt));
        
        // Calculate line height based on line spacing (using effective font size)
        $lineHeight = $effectiveFontSize * (1 + $lineSpacing * 0.1);
        
        // Generate actual QR code URL
        $qrUrl = "https://sharemycard.app/card.php?id=" . urlencode($cardData['id']);
        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qrUrl);
        
        $html = "
        <style>
            .nametag {
                width: 243pt;
                height: 168pt;
                font-family: {$webFontFamily};
                font-size: {$effectiveFontSize}pt;
                line-height: {$lineHeight}pt;
                color: #000;
                background: #fff;
                border: 1px solid #ddd;
            }
            .nametag table {
                width: 100%;
                height: 100%;
                border-collapse: collapse;
            }
            .nametag td {
                vertical-align: middle;
                padding: 8pt;
            }
            .message-row {
                text-align: center;
                font-weight: bold;
                font-size: " . ($effectiveFontSize + 6) . "pt;
                padding: 4pt 8pt;
            }
            .content-row td {
                vertical-align: middle;
            }
            .text-column {
                width: 60%;
                text-align: left;
                padding-left: 15pt;
                padding-right: 8pt;
            }
            .qr-column {
                width: 40%;
                text-align: center;
                padding-left: 8pt;
                padding-right: 8pt;
            }
            .name {
                font-weight: bold;
                margin-bottom: 2pt;
                font-size: {$effectiveFontSize}pt;
            }
            .title {
                margin-bottom: 2pt;
                font-size: {$effectiveFontSize}pt;
            }
            .contact {
                margin-bottom: 1pt;
                font-size: {$effectiveFontSize}pt;
            }
            .qr-code {
                width: {$qrMaxPt}pt;
                height: {$qrMaxPt}pt;
                max-width: 100%;
                max-height: 100%;
            }
        </style>
        <div class='nametag'>
            <table>
                " . (!empty($messageAbove) ? "<tr><td colspan='2' class='message-row'>{$messageAbove}</td></tr>" : "") . "
                <tr class='content-row'>
                    <td class='text-column'>
                        {$contactHTML}
                    </td>
                    <td class='qr-column'>
                        <img src='{$qrCodeUrl}' class='qr-code' alt='QR Code' />
                    </td>
                </tr>
                " . (!empty($messageBelow) ? "<tr><td colspan='2' class='message-row'>{$messageBelow}</td></tr>" : "") . "
            </table>
        </div>";
        
        return $html;
    }
    
    /**
     * Convert font family to web-safe font
     */
    private function getWebFontFamily($fontFamily) {
        switch ($fontFamily) {
            case 'helvetica':
                return 'Arial, Helvetica, sans-serif';
            case 'times':
                return 'Times New Roman, Times, serif';
            case 'courier':
                return 'Courier New, Courier, monospace';
            default:
                return 'Arial, Helvetica, sans-serif';
        }
    }
    
    /**
     * Add contact info to PDF using TCPDF native methods
     */
    private function addContactInfoToPDF($pdf, $x, $y, $cardData, $preferences) {
        $pdf->SetTextColor(0, 0, 0);
        
        // Content area (with padding) - centered within the name tag
        $padding = 12; // Increased padding for better centering
        $contentX = $x + $padding;
        $contentY = $y + $padding;
        $contentWidth = self::TAG_WIDTH - ($padding * 2);
        $contentHeight = self::TAG_HEIGHT - ($padding * 2);
        
        // Two-column layout: Left side (text), Right side (QR code)
        $leftColumnWidth = $contentWidth * 0.5; // 50% of content width
        $rightColumnWidth = $contentWidth * 0.5; // 50% of content width
        
        // Apply dynamic font scaling
        $contentStrings = $this->getContentStrings($cardData, $preferences);
        $longestLen = 0;
        foreach ($contentStrings as $s) {
            $len = mb_strlen($s ?? '', 'UTF-8');
            if ($len > $longestLen) $longestLen = $len;
        }
        
        $maxCharsPerLine = mb_strlen('john.doe@testcompany.com', 'UTF-8'); // 24
        $originalFontSize = $this->getFontSize($preferences);
        if ($longestLen > 0 && $maxCharsPerLine > 0) {
            $scaleFactor = min(1.0, $maxCharsPerLine / $longestLen);
            $effectiveFontSize = max(4.0, round($originalFontSize * $scaleFactor, 2));
        } else {
            $effectiveFontSize = $originalFontSize;
        }
        
        $fontFamily = 'helvetica'; // Fixed font family
        $spacingMultiplier = 1.0; // Fixed line spacing
        
        // Calculate the center of the card content area
        $cardCenterY = $contentY + ($contentHeight / 2);
        
        // Calculate main text height (without messages)
        $mainTextHeight = $this->calculateMainTextHeight($pdf, $cardData, $preferences, $leftColumnWidth);
        
        // Calculate message heights
        $upperMessageHeight = 0;
        $lowerMessageHeight = 0;
        if (!empty($preferences['message_above'])) {
            $upperMessageHeight = ($effectiveFontSize + 6) + 1 + 44; // Font + line + spacing (12 above + 32 below)
        }
        if (!empty($preferences['message_below'])) {
            $lowerMessageHeight = ($effectiveFontSize + 6) + 1 + 20; // Font + line + spacing (16 above + 4 below)
        }
        
        // Calculate center based only on main content (card data + QR code)
        // Messages will be positioned relative to this fixed center
        $contentStartY = $cardCenterY - ($mainTextHeight / 2);
        
        // Position upper message above the center content
        if (!empty($preferences['message_above'])) {
            $messageFontSize = $effectiveFontSize + 6; // Much larger font
            $upperMessageY = $contentStartY - $upperMessageHeight;
            $pdf->SetFont($fontFamily, 'B', $messageFontSize);
            $pdf->SetXY($contentX, $upperMessageY + 12); // 12pt space from top
            $pdf->Cell($contentWidth, 0, $this->truncateText($pdf, $preferences['message_above'], $contentWidth, $messageFontSize, 'B'), 0, 1, 'C'); // Centered across full width
        }
        
        $currentY = $contentStartY;
        
        // Name (same size as everything else, but bold)
        if ($preferences['include_name']) {
            $name = $cardData['first_name'] . ' ' . $cardData['last_name'];
            $pdf->SetFont($fontFamily, 'B', $effectiveFontSize);
            $pdf->SetXY($contentX, $currentY);
            $pdf->Cell($leftColumnWidth, 0, $this->truncateText($pdf, $name, $leftColumnWidth, $effectiveFontSize, 'B'), 0, 1, 'L');
            $currentY += ($effectiveFontSize + 1) * $spacingMultiplier;
        }
        
        // Job Title
        if ($preferences['include_title'] && !empty($cardData['job_title'])) {
            $pdf->SetFont($fontFamily, '', $effectiveFontSize);
            $pdf->SetXY($contentX, $currentY);
            $pdf->Cell($leftColumnWidth, 0, $this->truncateText($pdf, $cardData['job_title'], $leftColumnWidth, $effectiveFontSize, ''), 0, 1, 'L');
            $currentY += ($effectiveFontSize + 1) * $spacingMultiplier;
        }
        
        // Primary Phone
        if ($preferences['include_phone'] && !empty($cardData['phone_number'])) {
            $pdf->SetFont($fontFamily, '', $effectiveFontSize);
            $pdf->SetXY($contentX, $currentY);
            $pdf->Cell($leftColumnWidth, 0, $this->truncateText($pdf, $cardData['phone_number'], $leftColumnWidth, $effectiveFontSize, ''), 0, 1, 'L');
            $currentY += ($effectiveFontSize + 1) * $spacingMultiplier;
        }
        
        // Primary Email
        if ($preferences['include_email']) {
            $email = $this->getPrimaryEmail($cardData['id']);
            if ($email) {
                $pdf->SetFont($fontFamily, '', $effectiveFontSize);
                $pdf->SetXY($contentX, $currentY);
                $pdf->Cell($leftColumnWidth, 0, $this->truncateText($pdf, $email, $leftColumnWidth, $effectiveFontSize, ''), 0, 1, 'L');
                $currentY += ($effectiveFontSize + 1) * $spacingMultiplier;
            }
        }
        
        // Primary Website
        if ($preferences['include_website']) {
            $website = $this->getPrimaryWebsite($cardData['id']);
            if ($website) {
                $pdf->SetFont($fontFamily, '', $effectiveFontSize);
                $pdf->SetXY($contentX, $currentY);
                $pdf->Cell($leftColumnWidth, 0, $this->truncateText($pdf, $website, $leftColumnWidth, $effectiveFontSize, ''), 0, 1, 'L');
                $currentY += ($effectiveFontSize + 1) * $spacingMultiplier;
            }
        }
        
        // Address
        if ($preferences['include_address']) {
            $address = $this->getFormattedAddress($cardData['id']);
            if ($address) {
                $pdf->SetFont($fontFamily, '', $effectiveFontSize);
                $pdf->SetXY($contentX, $currentY);
                // Address might need multiple lines
                $pdf->MultiCell($leftColumnWidth, ($effectiveFontSize + 1) * $spacingMultiplier, $this->truncateText($pdf, $address, $leftColumnWidth, $effectiveFontSize, '', 2), 0, 'L', 0, 1);
            }
        }
        
        // Position lower message below the center content
        if (!empty($preferences['message_below'])) {
            $messageFontSize = $effectiveFontSize + 6;
            $lowerMessageY = $contentStartY + $mainTextHeight + 16; // 16pt space above the message
            $pdf->SetFont($fontFamily, 'B', $messageFontSize);
            $pdf->SetXY($contentX, $lowerMessageY);
            $pdf->Cell($contentWidth, 0, $this->truncateText($pdf, $preferences['message_below'], $contentWidth, $messageFontSize, 'B'), 0, 1, 'C');
        }
    }
    
    /**
     * Add QR code to PDF using TCPDF native methods
     */
    private function addQRCodeToPDF($pdf, $x, $y, $cardId, $preferences) {
        $qrUrl = "https://sharemycard.app/card.php?id=" . $cardId;
        
        // Content area (with padding) - centered within the name tag
        $padding = 12; // Match the padding used in addContactInfoToPDF
        $contentX = $x + $padding;
        $contentY = $y + $padding;
        $contentWidth = self::TAG_WIDTH - ($padding * 2);
        $contentHeight = self::TAG_HEIGHT - ($padding * 2);
        
        // Two-column layout: Right side (QR code)
        $leftColumnWidth = $contentWidth * 0.5; // 50% of content width
        $rightColumnWidth = $contentWidth * 0.5; // 50% of content width
        $rightColumnX = $contentX + $leftColumnWidth;
        
        // Apply dynamic QR sizing
        $contentStrings = $this->getContentStrings($this->getCardData($cardId), $preferences);
        $longestLen = 0;
        foreach ($contentStrings as $s) {
            $len = mb_strlen($s ?? '', 'UTF-8');
            if ($len > $longestLen) $longestLen = $len;
        }
        
        $qrMaxPt = 70 - max(0, ($longestLen - 10)) * 1.2; // shrink ~1.2pt per extra char
        $qrMaxPt = max(40, min(70, $qrMaxPt));
        
        // Calculate QR code position (center in right column, accounting for upper message)
        $qrSize = min($qrMaxPt, $rightColumnWidth - 8, $contentHeight - 8);
        $qrX = $rightColumnX + (($rightColumnWidth - $qrSize) / 2);
        
        // Use the same center-based positioning as the text
        $cardCenterY = $contentY + ($contentHeight / 2);
        $mainTextHeight = $this->calculateMainTextHeight($pdf, $this->getCardData($cardId), $preferences, $leftColumnWidth);
        
        // Calculate center based only on main content (card data + QR code)
        $contentStartY = $cardCenterY - ($mainTextHeight / 2);
        
        // Position QR code at the same center as the text
        $qrY = $contentStartY + ($mainTextHeight / 2) - ($qrSize / 2);
        
        // TCPDF has built-in QR code support
        $style = array(
            'border' => false,
            'padding' => 0,
            'fgcolor' => array(0,0,0),
            'bgcolor' => false
        );
        
        $pdf->write2DBarcode($qrUrl, 'QRCODE,M', $qrX, $qrY, $qrSize, $qrSize, $style, 'N');
    }
    
    /**
     * Generate preview image using HTML approach (convert HTML to image)
     */
    public function generatePreviewImageHTML($cardId, $preferences) {
        // Get card data with all contact information
        $cardData = $this->getCardData($cardId);
        if (!$cardData) {
            throw new Exception('Card not found');
        }
        
        // Generate HTML
        $html = $this->generateNameTagHTML($cardData, $preferences);
        
        // For preview, we'll create a simple HTML page and let the browser render it
        // This is a simplified approach - in production you might want to use a headless browser
        $previewHTML = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { 
                    margin: 0; 
                    padding: 0; 
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    background: #f5f5f5;
                }
                .preview-container {
                    width: 324px;
                    height: 224px;
                    border: 1px solid #ccc;
                    overflow: hidden;
                    background: white;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }
                /* Override sizes in preview to match requested pixel dimensions */
                .preview-container .nametag {
                    width: 324px !important;
                    height: 224px !important;
                }
            </style>
        </head>
        <body>
            <div class='preview-container'>
                {$html}
            </div>
        </body>
        </html>";
        
        // Save HTML to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'nametag_preview_') . '.html';
        file_put_contents($tempFile, $previewHTML);
        
        return $tempFile;
    }
}

