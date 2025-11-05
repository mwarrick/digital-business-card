<?php
/**
 * Name Tag Generator
 * Creates printable name tag PDFs with 8 tags per 8.5"x11" sheet
 * Also generates PNG previews for web interface
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/tcpdf/tcpdf.php';
require_once __DIR__ . '/log-image-creation.php';

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
    const TOP_MARGIN = 37;     // Increased to 37pt
    const HORIZONTAL_GAP = 37; // Decreased by 10pt (from 47pt to 37pt) to reduce gap between columns
    const VERTICAL_GAP = 15;   // Increased to 15pt
    
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
     * Uses spacing values from preferences if provided, otherwise uses constants
     */
    private function getNameTagPositions($preferences = []) {
        $positions = [];
        
        // Get spacing values from preferences or use defaults
        $topMargin = isset($preferences['top_margin']) ? (int)$preferences['top_margin'] : self::TOP_MARGIN;
        $verticalGap = isset($preferences['vertical_gap']) ? (int)$preferences['vertical_gap'] : self::VERTICAL_GAP;
        $horizontalGap = isset($preferences['horizontal_gap']) ? (int)$preferences['horizontal_gap'] : self::HORIZONTAL_GAP;
        
        // Get left margin from preferences, or calculate to center the columns
        if (isset($preferences['left_margin']) && $preferences['left_margin'] !== null && $preferences['left_margin'] !== '') {
            $leftMargin = (int)$preferences['left_margin'];
        } else {
            // Calculate left margin based on horizontal gap to center the columns
            $leftMargin = (self::SHEET_WIDTH - (2 * self::TAG_WIDTH) - $horizontalGap) / 2;
        }
        
        for ($row = 0; $row < 4; $row++) {
            for ($col = 0; $col < 2; $col++) {
                $x = $leftMargin + ($col * (self::TAG_WIDTH + $horizontalGap));
                $y = $topMargin + ($row * (self::TAG_HEIGHT + $verticalGap));
                
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
        
        // Company Name
        if ($preferences['include_company']) {
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
        
        // Company Name
        if ($preferences['include_company'] && !empty($cardData['company_name'])) {
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
        
        // Company Name
        if ($preferences['include_company'] && !empty($cardData['company_name'])) {
            $pdf->SetFont($fontFamily, '', $fontSize);
            $pdf->SetXY($x, $currentY);
            $pdf->Cell($width, 0, $this->truncateText($pdf, $cardData['company_name'], $width, $fontSize, ''), 0, 1, 'L');
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
        
        // Company Name (same size as everything else)
        if ($preferences['include_company'] && !empty($cardData['company_name'])) {
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
        
        // Company Name
        if ($preferences['include_company'] && !empty($cardData['company_name'])) {
            if ($useTTF) {
                $scaledFontSize = $fontSize * $scale; // Same size as everything else
                $bbox = imagettfbbox($scaledFontSize, 0, $font, $cardData['company_name']);
                if ($bbox !== false) {
                    imagettftext($image, $scaledFontSize, 0, $x, $currentY - $bbox[5], $color, $font, $cardData['company_name']);
                }
            } else {
                imagestring($image, 3, $x, $currentY, $cardData['company_name'], $color);
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
        
        if ($preferences['include_company'] && !empty($cardData['company_name'])) {
            $contentStrings[] = $cardData['company_name'];
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
        
        // Company Name
        if ($preferences['include_company'] && !empty($cardData['company_name'])) {
            if ($useTTF) {
                $bbox = imagettfbbox($fontSize, 0, $font, $cardData['company_name']);
                if ($bbox !== false) {
                    imagettftext($image, $fontSize, 0, $x, $currentY - $bbox[5], $color, $font, $cardData['company_name']);
                }
            } else {
                imagestring($image, 5, $x, $currentY, $cardData['company_name'], $color);
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
        
        if ($preferences['include_company'] && !empty($cardData['company_name'])) {
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
        
        if ($preferences['include_company'] && !empty($cardData['company_name'])) {
            $company = htmlspecialchars($cardData['company_name']);
            $contactInfo[] = "<div class='company'>{$company}</div>";
            $contentStrings[] = $company;
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
            .company {
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
     * Generate HTML for QR surround name tag (public method)
     * Creates HTML suitable for Word/Google Docs paste
     */
    public function generateQRSurroundNameTagHTML($cardId, $preferences) {
        $cardData = $this->getCardData($cardId);
        if (!$cardData) {
            throw new Exception('Card not found');
        }
        
        return $this->generateQRSurroundNameTagHTMLInternal($cardId, $preferences);
    }
    
    /**
     * Generate HTML for QR surround name tag (internal method)
     */
    private function generateQRSurroundNameTagHTMLInternal($cardId, $preferences) {
        // Get banner preferences
        $topBannerText = htmlspecialchars($preferences['top_banner_text'] ?? 'Hello My Name Is...');
        $topBannerColor = htmlspecialchars($preferences['top_banner_color'] ?? '#000000');
        $topBannerFontFamily = htmlspecialchars($preferences['top_banner_font_family'] ?? 'Caveat');
        $topBannerFontSize = (int)($preferences['top_banner_font_size'] ?? 16);
        
        $bottomBannerText = htmlspecialchars($preferences['bottom_banner_text'] ?? '');
        $bottomBannerColor = htmlspecialchars($preferences['bottom_banner_color'] ?? '#000000');
        $bottomBannerFontFamily = htmlspecialchars($preferences['bottom_banner_font_family'] ?? 'Caveat');
        $bottomBannerFontSize = (int)($preferences['bottom_banner_font_size'] ?? 8);
        
        // Generate QR code image URL (same as preview page uses)
        // Build the same parameters that preview-name-tag-qr-surround.php expects
        $previewParams = http_build_query([
            'card_id' => $cardId,
            'top_banner_text' => $preferences['top_banner_text'] ?? 'Hello My Name Is...',
            'top_banner_color' => $preferences['top_banner_color'] ?? '#000000',
            'top_banner_font_family' => $preferences['top_banner_font_family'] ?? 'Caveat',
            'top_banner_font_size' => $preferences['top_banner_font_size'] ?? 16,
            'bottom_banner_text' => $preferences['bottom_banner_text'] ?? '',
            'bottom_banner_color' => $preferences['bottom_banner_color'] ?? '#000000',
            'bottom_banner_font_family' => $preferences['bottom_banner_font_family'] ?? 'Caveat',
            'bottom_banner_font_size' => $preferences['bottom_banner_font_size'] ?? 8
        ]);
        $qrImageUrl = "https://sharemycard.app/user/cards/preview-name-tag-qr-surround.php?" . $previewParams;
        
        // Print dimensions: 3.375" = 243pt, 2.33" = 168pt
        // Actual image dimensions: 1013px × 700px
        // For web display on HTML generator page, scale to 25% of original (75% reduction)
        $printWidth = 1013;
        $printHeight = 700;
        $maxWebWidth = 860;
        $webScaleFactor = ($maxWebWidth / $printWidth) * 0.25; // Reduce by 75% (25% of original)
        
        $webWidth = $printWidth * $webScaleFactor;
        $webHeight = $printHeight * $webScaleFactor;
        
        // Calculate banner heights to match the preview logic in name-tags-qr-surround.php
        // Top banner is approximately 2x the size of bottom banner
        // Formula from preview: fontSize * 2.5 + padding
        $effectiveTopFontSize = $bottomBannerFontSize * 2;
        $actualTopFontSize = $topBannerFontSize;
        
        // Calculate heights in pixels (matching the preview calculation)
        $bottomBannerHeightPx = ($bottomBannerFontSize * 2.5 + 40) * $webScaleFactor;
        $topBannerHeightPx = max(($effectiveTopFontSize * 2.5 + 80) * $webScaleFactor, $bottomBannerHeightPx * 2);
        
        // Font sizes for web display (scaled)
        $webTopFontSize = $actualTopFontSize * $webScaleFactor;
        $webBottomFontSize = $bottomBannerFontSize * $webScaleFactor;
        
        // Convert font family to web-safe or Google Fonts
        $topWebFontFamily = $this->getQRSurroundWebFontFamily($topBannerFontFamily);
        $bottomWebFontFamily = $this->getQRSurroundWebFontFamily($bottomBannerFontFamily);
        
        // Build HTML structure matching the preview structure from name-tags-qr-surround.php
        // The preview uses CSS divs for banners and an img tag for the QR code
        // Use pixels for web display, not points
        $html = "
        <link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">
        <link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>
        <link href=\"https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;600;700&family=Caveat:wght@400;600;700&family=Kalam:wght@300;400;700&display=swap\" rel=\"stylesheet\">
        <style>
            .nametag-qr-surround-wrapper {
                width: {$webWidth}px;
                background: #fff;
                border: 1px solid #ddd;
                display: flex;
                flex-direction: column;
            }
            .banner-top {
                width: 100%;
                background-color: {$topBannerColor};
                color: white;
                text-align: center;
                font-family: {$topWebFontFamily}, cursive;
                font-size: {$webTopFontSize}px;
                font-weight: bold;
                padding: 0;
                box-sizing: border-box;
                display: flex;
                align-items: center;
                justify-content: center;
                height: {$topBannerHeightPx}px;
                line-height: {$topBannerHeightPx}px;
            }
            .qr-image-container {
                width: 100%;
                background: white;
                display: block;
            }
            .qr-code-img {
                width: {$webWidth}px;
                height: auto;
                display: block;
            }
            .banner-bottom {
                width: 100%;
                background-color: {$bottomBannerColor};
                color: white;
                text-align: center;
                font-family: {$bottomWebFontFamily}, cursive;
                font-size: {$webBottomFontSize}px;
                font-weight: bold;
                padding: 0;
                box-sizing: border-box;
                display: flex;
                align-items: center;
                justify-content: center;
                height: {$bottomBannerHeightPx}px;
                line-height: {$bottomBannerHeightPx}px;
            }
        </style>
        <div class='nametag-qr-surround-wrapper'>
            " . (!empty($topBannerText) ? "<div class='banner-top'>{$topBannerText}</div>" : "") . "
            <img src='{$qrImageUrl}' alt='QR Code Name Tag' class='qr-code-img' />
            " . (!empty($bottomBannerText) ? "<div class='banner-bottom'>{$bottomBannerText}</div>" : "") . "
        </div>";
        
        return $html;
    }
    
    /**
     * Convert QR surround font family to web-safe font or Google Fonts
     */
    private function getQRSurroundWebFontFamily($fontFamily) {
        // Map to Google Fonts or web-safe fonts
        switch (strtolower($fontFamily)) {
            case 'dancing script':
                return "'Dancing Script', cursive";
            case 'caveat':
                return "'Caveat', cursive";
            case 'kalam':
                return "'Kalam', sans-serif";
            default:
                return "'Caveat', cursive";
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
        
        // Company Name
        if ($preferences['include_company'] && !empty($cardData['company_name'])) {
            $pdf->SetFont($fontFamily, '', $effectiveFontSize);
            $pdf->SetXY($contentX, $currentY);
            $pdf->Cell($leftColumnWidth, 0, $this->truncateText($pdf, $cardData['company_name'], $leftColumnWidth, $effectiveFontSize, ''), 0, 1, 'L');
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
    
    /**
     * Generate QR surround name tag sheet (8 tags per sheet)
     */
    public function generateQRSurroundNameTagSheet($cardId, $preferences) {
        
        $cardData = $this->getCardData($cardId);
        if (!$cardData) {
            throw new Exception('Card not found');
        }
        
        // Create PDF instance
        $pdf = new TCPDF('P', 'pt', array(self::SHEET_WIDTH, self::SHEET_HEIGHT), true, 'UTF-8', false);
        
        // Set document properties
        $pdf->SetCreator('ShareMyCard');
        $pdf->SetAuthor($cardData['first_name'] . ' ' . $cardData['last_name']);
        $pdf->SetTitle('Hello My Name Is - Name Tags');
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set margins
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);
        
        // Add page
        $pdf->AddPage();
        
        // Get 8 positions for name tags (pass preferences for custom spacing)
        $positions = $this->getNameTagPositions($preferences);
        
        // Add each name tag
        foreach ($positions as $position) {
            $this->addQRSurroundNameTag($pdf, $position['x'], $position['y'], $cardData, $preferences);
        }
        
        // Removed dashed cut line between rows 3 and 4 - no longer needed
        
        return $pdf;
    }
    
    /**
     * Add single QR surround name tag to PDF at specified position
     */
    private function addQRSurroundNameTag($pdf, $x, $y, $cardData, $preferences) {
        // No border - clean name tags
        // Generate complete name tag image (includes banners and QR code)
        // The image will be scaled to fit the name tag dimensions
        $this->addQRCodeWithNameOverlay($pdf, $x, $y, $cardData['id'], self::TAG_WIDTH, self::TAG_HEIGHT, $preferences);
    }
    
    /**
     * Draw banner (top or bottom)
     */
    private function drawBanner($pdf, $x, $y, $width, $height, $text, $color, $preferences = []) {
        // Draw background
        $pdf->SetFillColor($color['r'], $color['g'], $color['b']);
        $pdf->Rect($x, $y, $width, $height, 'F');
        
        // Get font settings from preferences - use the specific banner's font settings
        // The preferences array passed to this function should contain only the relevant banner's font settings
        // (either top_banner_* or bottom_banner_* keys, not both)
        
        // Debug: log what we received
        error_log("drawBanner - Preferences received: " . json_encode($preferences));
        
        // Check for top banner font settings first
        if (isset($preferences['top_banner_font_family']) || isset($preferences['top_banner_font_size'])) {
            $fontFamily = $preferences['top_banner_font_family'] ?? 'Arial';
            $fontSize = isset($preferences['top_banner_font_size']) ? (int)$preferences['top_banner_font_size'] : 16;
            error_log("drawBanner - Using TOP banner: family='$fontFamily', size=$fontSize");
        }
        // Otherwise check for bottom banner font settings
        elseif (isset($preferences['bottom_banner_font_family']) || isset($preferences['bottom_banner_font_size'])) {
            $fontFamily = $preferences['bottom_banner_font_family'] ?? 'Arial';
            $fontSize = isset($preferences['bottom_banner_font_size']) ? (int)$preferences['bottom_banner_font_size'] : 8;
            error_log("drawBanner - Using BOTTOM banner: family='$fontFamily', size=$fontSize");
        }
        // Fallback defaults
        else {
            $fontFamily = 'Arial';
            $fontSize = 8;
            error_log("drawBanner - Using DEFAULTS: family='$fontFamily', size=$fontSize");
        }
        
        // Ensure font size is a valid integer
        $fontSize = max(6, min(72, (int)$fontSize));
        error_log("drawBanner - Final font size: $fontSize");
        
        // Map font families to TCPDF fonts
        // Note: TCPDF only supports helvetica, times, courier natively
        // For handwriting fonts, we'll use helvetica as fallback
        $tcpdfFont = 'helvetica'; // Default
        
        // Normalize font family name for comparison (case-insensitive)
        $fontFamilyLower = strtolower(trim($fontFamily));
        
        // Check for serif fonts
        if (stripos($fontFamilyLower, 'times') !== false || 
            stripos($fontFamilyLower, 'georgia') !== false ||
            stripos($fontFamilyLower, 'serif') !== false) {
            $tcpdfFont = 'times';
        } 
        // Check for monospace fonts
        elseif (stripos($fontFamilyLower, 'courier') !== false || 
                stripos($fontFamilyLower, 'mono') !== false) {
            $tcpdfFont = 'courier';
        }
        // Check for handwriting fonts (Dancing Script, Caveat, Kalam) - use helvetica as closest match
        elseif (stripos($fontFamilyLower, 'dancing') !== false ||
                stripos($fontFamilyLower, 'caveat') !== false ||
                stripos($fontFamilyLower, 'kalam') !== false ||
                stripos($fontFamilyLower, 'script') !== false) {
            $tcpdfFont = 'helvetica'; // Closest match for handwriting
        }
        // Default to helvetica for all other fonts (Arial, sans-serif, etc.)
        else {
            $tcpdfFont = 'helvetica';
        }
        
        // Draw text (white, centered)
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont($tcpdfFont, 'B', $fontSize);
        
        // Center text horizontally
        $textWidth = $pdf->GetStringWidth($text);
        $textX = $x + ($width - $textWidth) / 2;
        
        // Center text vertically - use proper calculation
        $textHeight = $fontSize * 0.35; // Approximate text height in points
        $textY = $y + ($height - $textHeight) / 2 + $textHeight * 0.7; // Adjust for baseline
        
        $pdf->SetXY($textX, $textY);
        $pdf->Cell(0, 0, $text, 0, 0, 'L');
        
        // Reset text color
        $pdf->SetTextColor(0, 0, 0);
    }
    
    /**
     * Add QR code with name overlay - uses the same image generation as preview
     * Now generates complete image with banners included
     */
    private function addQRCodeWithNameOverlay($pdf, $x, $y, $cardId, $width, $height, $preferences = []) {
        // Generate the complete PNG image with banners included
        $imageUrl = $this->generateCompleteQRSurroundImage($cardId, $preferences);
        
            if ($imageUrl && file_exists($imageUrl)) {
                // Get image dimensions to maintain aspect ratio
                $imageInfo = @getimagesize($imageUrl);
                if ($imageInfo !== false) {
                    $imageWidth = $imageInfo[0];
                    $imageHeight = $imageInfo[1];
                    
                    // Scale down for PDF to fit all 4 rows (reduce size by ~10% to ensure fit)
                    // This only affects PDF embedding, not the preview
                    $pdfScaleFactor = 0.90; // Scale down by 10% for PDF
                    $adjustedWidth = $width * $pdfScaleFactor;
                    $adjustedHeight = $height * $pdfScaleFactor;
                    
                    // Calculate scale to fit the adjusted width/height while maintaining aspect ratio
                    $scaleX = $adjustedWidth / $imageWidth;
                    $scaleY = $adjustedHeight / $imageHeight;
                    $scale = min($scaleX, $scaleY); // Use smaller scale to fit both dimensions
                    
                    $scaledWidth = $imageWidth * $scale;
                    $scaledHeight = $imageHeight * $scale;
                    
                    // Position image at the top-left of the tag area (don't center vertically)
                    // This ensures uniform spacing between rows
                    $imgX = $x + ($width - $scaledWidth) / 2; // Center horizontally
                    $imgY = $y; // Align to top of tag area
                    
                    // Embed the PNG image directly into the PDF
                    $pdf->Image($imageUrl, $imgX, $imgY, $scaledWidth, $scaledHeight, 'PNG', '', '', false, 300, '', false, false, 0);
            } else {
                // Fallback: use dimensions directly
                $pdf->Image($imageUrl, $x, $y, $width, $height, 'PNG', '', '', false, 300, '', false, false, 0);
            }
            
            // Clean up temporary file
            @unlink($imageUrl);
        } else {
            // Fallback: use TCPDF's built-in QR code if image generation fails
            $qrUrl = "https://sharemycard.app/card.php?id=" . $cardId . "&src=nametag-qr-surround";
            $style = array(
                'border' => false,
                'padding' => 0,
                'fgcolor' => array(0,0,0),
                'bgcolor' => false
            );
            $pdf->write2DBarcode($qrUrl, 'QRCODE,H', $x, $y, $width, $height, $style, 'N');
            
            // Draw name overlay
            $nameFontSize = $this->calculateNameFontSize($name, $width, $height);
            $pdf->SetFont('helvetica', 'B', $nameFontSize);
            $nameWidth = $pdf->GetStringWidth($name);
            $nameHeight = $nameFontSize * 0.35;
            $namePadding = 15;
            $cutoutWidth = $nameWidth + ($namePadding * 2);
            $cutoutHeight = $nameHeight + ($namePadding * 2);
            $cutoutX = $x + ($width - $cutoutWidth) / 2;
            $cutoutY = $y + ($height - $cutoutHeight) / 2;
            
            $pdf->SetFillColor(255, 255, 255);
            $pdf->Rect($cutoutX, $cutoutY, $cutoutWidth, $cutoutHeight, 'F');
            
            $textX = $x + ($width - $nameWidth) / 2;
            $textY = $cutoutY + ($cutoutHeight - $nameHeight) / 2 + $nameHeight * 0.7;
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY($textX, $textY);
            $pdf->Cell(0, 0, $name, 0, 0, 'L');
        }
    }
    
    /**
     * Generate complete name tag image with banners (returns file path)
     * This generates the complete image including top banner, QR code with name, and bottom banner
     */
    public function generateCompleteQRSurroundImage($cardId, $preferences) {
        if (!function_exists('imagecreatetruecolor')) {
            return false;
        }
        
        // Get banner settings
        $topBannerText = $preferences['top_banner_text'] ?? 'Hello My Name Is...';
        $topBannerColor = $this->hexToRgb($preferences['top_banner_color'] ?? '#000000');
        $topBannerFontFamily = $preferences['top_banner_font_family'] ?? 'Arial';
        $topBannerFontSize = (int)($preferences['top_banner_font_size'] ?? 16);
        
        $bottomBannerText = $preferences['bottom_banner_text'] ?? '';
        $bottomBannerColor = $this->hexToRgb($preferences['bottom_banner_color'] ?? '#000000');
        $bottomBannerFontFamily = $preferences['bottom_banner_font_family'] ?? 'Arial';
        $bottomBannerFontSize = (int)($preferences['bottom_banner_font_size'] ?? 8);
        
        // Debug: log bottom banner font settings
        error_log("generateCompleteQRSurroundImage - Bottom banner font settings: family='" . $bottomBannerFontFamily . "', size=" . $bottomBannerFontSize);
        error_log("generateCompleteQRSurroundImage - Full preferences keys: " . implode(', ', array_keys($preferences)));
        error_log("generateCompleteQRSurroundImage - bottom_banner_font_family in preferences: " . (isset($preferences['bottom_banner_font_family']) ? "'" . $preferences['bottom_banner_font_family'] . "'" : 'NOT SET'));
        error_log("generateCompleteQRSurroundImage - bottom_banner_font_size in preferences: " . (isset($preferences['bottom_banner_font_size']) ? $preferences['bottom_banner_font_size'] : 'NOT SET'));
        
        // Top banner is 2x bottom banner size
        $effectiveTopFontSize = $bottomBannerFontSize * 2;
        
        // Calculate banner heights based on font sizes and padding
        // Make banners taller to match visual design - increase padding significantly
        // Top banner should be noticeably taller (2x bottom)
        $topBannerHeight = (int)round($effectiveTopFontSize * 2.5 + 80); // More generous padding
        $bottomBannerHeight = (int)round($bottomBannerFontSize * 2.5 + 40); // More generous padding
        
        // Ensure top is exactly 2x bottom for consistency
        $topBannerHeight = max($topBannerHeight, $bottomBannerHeight * 2);
        
        // Exact print dimensions in pixels (provided): 1013 x 700
        $totalWidth = 1013;
        $totalHeight = 700;
        
        // QR code must be square - use the smaller dimension
        // Available space after banners
        $availableWidth = $totalWidth;
        $availableHeight = max(1, $totalHeight - ($topBannerHeight + $bottomBannerHeight));
        
        // Make QR code square - use the SMALLER of width or height to ensure it fits
        // This ensures the QR code is square and fits in the available space
        $qrDimension = min($availableWidth * 0.95, $availableHeight);
        // Ensure it's square - both dimensions must be equal
        $qrWidth = (int)round($qrDimension);
        $qrHeight = (int)round($qrDimension); // Make it square - same as width
        $qrX = (int)round(($totalWidth - $qrWidth) / 2); // Center horizontally
        
        error_log("QR Surround PDF - QR sizing: availableWidth=$availableWidth, availableHeight=$availableHeight, qrDimension=$qrDimension, qrWidth=$qrWidth, qrHeight=$qrHeight");
        
        $image = imagecreatetruecolor($totalWidth, $totalHeight);
        if ($image === false) {
            return false;
        }
        
        // White background
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);
        
        $currentY = 0;
        
        // Draw top banner
        if (!empty($topBannerText)) {
            $topBannerRGB = imagecolorallocate($image, $topBannerColor['r'], $topBannerColor['g'], $topBannerColor['b']);
            imagefilledrectangle($image, 0, $currentY, $totalWidth - 1, $currentY + $topBannerHeight - 1, $topBannerRGB);
            
            // Draw text using TrueType fonts if available
            $textColor = imagecolorallocate($image, 255, 255, 255);
            $fontPath = $this->getGdFontPath($topBannerFontFamily);
            
            if ($fontPath && function_exists('imagettftext')) {
                // Use TrueType font
                $bbox = imagettfbbox($effectiveTopFontSize, 0, $fontPath, $topBannerText);
                if ($bbox !== false) {
                    $textWidth = $bbox[2] - $bbox[0];
                    $textHeight = abs($bbox[7] - $bbox[1]);
                    $textX = (int)round(($totalWidth - $textWidth) / 2);
                    $textY = (int)round($currentY + ($topBannerHeight / 2) + ($textHeight / 2));
                    imagettftext($image, $effectiveTopFontSize, 0, $textX, $textY, $textColor, $fontPath, $topBannerText);
                } else {
                    // Fallback to built-in font if TTF fails
                    $this->drawTextWithBuiltInFont($image, $topBannerText, $textColor, $totalWidth, $currentY, $topBannerHeight);
                }
            } else {
                // Fallback to built-in font
                $this->drawTextWithBuiltInFont($image, $topBannerText, $textColor, $totalWidth, $currentY, $topBannerHeight);
            }
            
            $currentY += $topBannerHeight;
        }
        
        // Generate QR code (reuse existing logic)
        $qrImageUrl = $this->generateQRSurroundPreviewImage($cardId, $preferences);
        if ($qrImageUrl && file_exists($qrImageUrl)) {
            $qrImage = @imagecreatefrompng($qrImageUrl);
            if ($qrImage !== false) {
                // Get source dimensions
                $sourceWidth = imagesx($qrImage);
                $sourceHeight = imagesy($qrImage);
                
                // The source image from generateQRSurroundPreviewImage is 1013x700
                // The QR code inside is square and centered both horizontally and vertically
                // We need to extract the square QR code from the center
                // The QR code in source is square, so use the smaller dimension (height = 700)
                $sourceQrSize = min($sourceWidth, $sourceHeight); // This should be 700 (the height)
                $sourceQrX = (int)round(($sourceWidth - $sourceQrSize) / 2); // Center horizontally (1013-700)/2 = 156.5
                $sourceQrY = (int)round(($sourceHeight - $sourceQrSize) / 2); // Center vertically (700-700)/2 = 0, but let's be explicit
                
                // Center QR code vertically in available space
                $qrY = (int)round($currentY + (($availableHeight - $qrHeight) / 2));
                
                // Copy QR code - extract square from source (sourceQrSize x sourceQrSize) and resize to square destination (qrWidth x qrHeight)
                // Both source and destination are square, so this should preserve square aspect ratio
                imagecopyresampled($image, $qrImage, $qrX, $qrY, $sourceQrX, $sourceQrY, $qrWidth, $qrHeight, $sourceQrSize, $sourceQrSize);
                
                error_log("QR Surround PDF - QR copy: source=($sourceQrX,$sourceQrY) size=$sourceQrSize x $sourceQrSize, dest=($qrX,$qrY) size=$qrWidth x $qrHeight");
                imagedestroy($qrImage);
                @unlink($qrImageUrl); // Clean up
            }
        }
        $currentY += $availableHeight;
        
        // Draw bottom banner
        if (!empty($bottomBannerText)) {
            // Debug: log what we're about to use for bottom banner
            error_log("generateCompleteQRSurroundImage - Drawing bottom banner with font: family='" . $bottomBannerFontFamily . "', size=" . $bottomBannerFontSize);
            
            $bottomBannerRGB = imagecolorallocate($image, $bottomBannerColor['r'], $bottomBannerColor['g'], $bottomBannerColor['b']);
            imagefilledrectangle($image, 0, $currentY, $totalWidth - 1, $currentY + $bottomBannerHeight - 1, $bottomBannerRGB);
            
            // Draw text using TrueType fonts if available
            $textColor = imagecolorallocate($image, 255, 255, 255);
            $fontPath = $this->getGdFontPath($bottomBannerFontFamily);
            
            // If primary font not found, try fallback fonts (Caveat, then others)
            if (!$fontPath) {
                error_log("generateCompleteQRSurroundImage - Bottom banner font '$bottomBannerFontFamily' not found, trying fallbacks");
                $fallbackFonts = ['Caveat', 'Kalam', 'Dancing Script'];
                foreach ($fallbackFonts as $fallbackFont) {
                    $fontPath = $this->getGdFontPath($fallbackFont);
                    if ($fontPath) {
                        error_log("generateCompleteQRSurroundImage - Using fallback font: '$fallbackFont'");
                        break;
                    }
                }
            }
            
            error_log("generateCompleteQRSurroundImage - Bottom banner font path resolved: " . ($fontPath ?: 'NONE - using built-in fallback'));
            
            if ($fontPath && function_exists('imagettftext')) {
                // Use TrueType font with the specified font size
                $bbox = imagettfbbox($bottomBannerFontSize, 0, $fontPath, $bottomBannerText);
                if ($bbox !== false) {
                    $textWidth = $bbox[2] - $bbox[0];
                    $textHeight = abs($bbox[7] - $bbox[1]);
                    $textX = (int)round(($totalWidth - $textWidth) / 2);
                    $textY = (int)round($currentY + ($bottomBannerHeight / 2) + ($textHeight / 2));
                    imagettftext($image, $bottomBannerFontSize, 0, $textX, $textY, $textColor, $fontPath, $bottomBannerText);
                    error_log("generateCompleteQRSurroundImage - Bottom banner text drawn with TTF font, size=$bottomBannerFontSize");
                } else {
                    // Fallback to built-in font if TTF bbox calculation fails
                    error_log("generateCompleteQRSurroundImage - TTF bbox calculation failed, using built-in font");
                    $this->drawTextWithBuiltInFont($image, $bottomBannerText, $textColor, $totalWidth, $currentY, $bottomBannerHeight);
                }
            } else {
                // Fallback to built-in font only if no TTF font is available at all
                error_log("generateCompleteQRSurroundImage - No TTF font available, using built-in font");
                $this->drawTextWithBuiltInFont($image, $bottomBannerText, $textColor, $totalWidth, $currentY, $bottomBannerHeight);
            }
        }
        
        // Save to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'qr_surround_complete_') . '.png';
        imagepng($image, $tempFile);
        imagedestroy($image);
        
        return $tempFile;
    }
    
    /**
     * Generate the same PNG image used in preview (returns file path)
     * This includes the QR code with name overlay, matching the preview exactly
     * NOTE: This does NOT include banners - use generateCompleteQRSurroundImage() for full image
     */
    private function generateQRSurroundPreviewImage($cardId, $preferences) {
        // Reuse the same logic from preview-name-tag-qr-surround.php
        // This generates the exact same image as the preview
        
        if (!function_exists('imagecreatetruecolor')) {
            return false;
        }
        
        // Use exact print dimensions: 3-3/8" = 1013 pixels, 2-1/3" = 700 pixels
        // This matches the preview exactly
        $width = 1013; // Exact print width
        $height = 700; // Exact print height
        
        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            return false;
        }
        
        // White background
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);
        
        // Make QR code square (use the smaller dimension to ensure it fits)
        // QR codes must be square for proper scannability
        // Banners are added separately by TCPDF, so this is just the QR area
        $qrDimension = min($width * 0.95, $height); // Use 95% of width or full height, whichever is smaller
        $qrWidth = (int)round($qrDimension);
        $qrHeight = (int)round($qrDimension); // Make it square
        $qrX = (int)round(($width - $qrWidth) / 2); // Center horizontally
        $qrY = (int)round(($height - $qrHeight) / 2); // Center vertically
        
        // Generate QR code at ultra high resolution for maximum quality
        $qrUrl = "https://sharemycard.app/card.php?id=" . $cardId . "&src=nametag-qr-surround";
        // Request ultra high resolution QR code (4-5x the final size for maximum crispness)
        $qrApiSize = max(500, min(4000, round(max($qrWidth, $qrHeight) * 3))); // Ultra high resolution
        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=" . $qrApiSize . "x" . $qrApiSize . "&data=" . urlencode($qrUrl) . "&ecc=H";
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'ShareMyCard/1.0'
            ]
        ]);
        
        $qrCodeData = @file_get_contents($qrCodeUrl, false, $context);
        
        if ($qrCodeData !== false && strlen($qrCodeData) >= 100) {
            $qrImage = @imagecreatefromstring($qrCodeData);
            if ($qrImage !== false) {
                // Crop white quiet zone from QR code
                $qrSourceWidth = imagesx($qrImage);
                $qrSourceHeight = imagesy($qrImage);
                $whiteColor = imagecolorat($qrImage, 0, 0);
                
                // Find edges
                $top = 0;
                for ($y = 0; $y < $qrSourceHeight; $y++) {
                    for ($x = 0; $x < $qrSourceWidth; $x++) {
                        if (imagecolorat($qrImage, $x, $y) != $whiteColor) {
                            $top = $y;
                            break 2;
                        }
                    }
                }
                
                $bottom = $qrSourceHeight - 1;
                for ($y = $qrSourceHeight - 1; $y >= 0; $y--) {
                    for ($x = 0; $x < $qrSourceWidth; $x++) {
                        if (imagecolorat($qrImage, $x, $y) != $whiteColor) {
                            $bottom = $y;
                            break 2;
                        }
                    }
                }
                
                $left = 0;
                for ($x = 0; $x < $qrSourceWidth; $x++) {
                    for ($y = 0; $y < $qrSourceHeight; $y++) {
                        if (imagecolorat($qrImage, $x, $y) != $whiteColor) {
                            $left = $x;
                            break 2;
                        }
                    }
                }
                
                $right = $qrSourceWidth - 1;
                for ($x = $qrSourceWidth - 1; $x >= 0; $x--) {
                    for ($y = 0; $y < $qrSourceHeight; $y++) {
                        if (imagecolorat($qrImage, $x, $y) != $whiteColor) {
                            $right = $x;
                            break 2;
                        }
                    }
                }
                
                $croppedWidth = $right - $left + 1;
                $croppedHeight = $bottom - $top + 1;
                
                $croppedQrImage = imagecreatetruecolor($croppedWidth, $croppedHeight);
                $white = imagecolorallocate($croppedQrImage, 255, 255, 255);
                imagefill($croppedQrImage, 0, 0, $white);
                imagecopy($croppedQrImage, $qrImage, 0, 0, $left, $top, $croppedWidth, $croppedHeight);
                imagedestroy($qrImage);
                $qrImage = $croppedQrImage;
                $qrSourceWidth = $croppedWidth;
                $qrSourceHeight = $croppedHeight;
                
                // Copy QR code to main image
                imagecopyresampled($image, $qrImage, $qrX, $qrY, 0, 0, $qrWidth, $qrHeight, $qrSourceWidth, $qrSourceHeight);
                imagedestroy($qrImage);
            }
        }
        
        // Save to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'qr_surround_') . '.png';
        imagepng($image, $tempFile);
        imagedestroy($image);
        
        return $tempFile;
    }
    
    /**
     * Calculate adaptive QR code size based on name length
     */
    private function calculateQRSizeForName($name, $maxWidth, $maxHeight) {
        $nameLength = mb_strlen($name);
        
        // Estimate text width at 24pt font (starting point)
        $estimatedWidth = $this->estimateTextWidth($name, 24);
        
        // Padding around name
        $namePadding = 20; // 10pt on each side
        
        // Minimum QR size (for short names)
        $minSize = 60;
        
        // Calculate QR width (name width + padding, but at least minSize)
        $qrWidth = max($minSize, $estimatedWidth + $namePadding);
        
        // Calculate QR height based on name length
        if ($nameLength <= 5) {
            // Square for short names
            $qrHeight = $qrWidth;
        } else if ($nameLength <= 8) {
            // Slightly rectangular for medium names
            $qrHeight = $qrWidth * 0.75;
        } else {
            // More rectangular for long names
            $qrHeight = $qrWidth * 0.6;
        }
        
        // Ensure fits in available space
        $qrWidth = min($qrWidth, $maxWidth);
        $qrHeight = min($qrHeight, $maxHeight);
        
        // Ensure minimum size for scannability
        $qrWidth = max($qrWidth, $minSize);
        $qrHeight = max($qrHeight, $minSize);
        
        return ['width' => $qrWidth, 'height' => $qrHeight];
    }
    
    /**
     * Estimate text width at given font size
     */
    private function estimateTextWidth($text, $fontSize) {
        // Approximate: each character is roughly 0.6 * fontSize wide
        // This is a rough estimate - actual width depends on font and characters
        return mb_strlen($text) * $fontSize * 0.6;
    }
    
    /**
     * Calculate name font size to fit in QR code
     */
    private function calculateNameFontSize($name, $qrWidth, $qrHeight) {
        $nameLength = mb_strlen($name);
        $maxWidth = $qrWidth * 0.8; // 80% of QR width
        $maxHeight = $qrHeight * 0.5; // 50% of QR height
        
        // Start with large font
        $fontSize = 36;
        
        // Test text width and reduce until it fits
        while ($this->estimateTextWidth($name, $fontSize) > $maxWidth && $fontSize > 12) {
            $fontSize -= 2;
        }
        
        // Ensure doesn't exceed height
        if ($fontSize > $maxHeight) {
            $fontSize = $maxHeight;
        }
        
        return max(12, $fontSize); // Minimum 12pt
    }
    
    /**
     * Convert hex color to RGB array
     */
    private function hexToRgb($hex) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) == 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2))
        ];
    }
    
    /**
     * Get path to TTF font file for GD library
     * Maps font family names to system font paths and project font files
     */
    private function getGdFontPath($fontFamily) {
        // Normalize font family name
        $fontFamily = trim($fontFamily);
        $fontFamilyLower = strtolower($fontFamily);
        
        // Project fonts directory (web/fonts/)
        // Try multiple paths to find fonts directory
        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? dirname(dirname(dirname(__DIR__)));
        $docRoot = rtrim(str_replace('\\', '/', $docRoot), '/');
        
        $fontDirs = [
            $docRoot . '/fonts/',  // Standard location based on DOCUMENT_ROOT
            __DIR__ . '/../../../web/fonts/',  // Relative from NameTagGenerator (web/api/includes -> web/fonts)
            dirname(dirname(dirname(__DIR__))) . '/fonts/',  // Absolute from NameTagGenerator
            '/public_html/fonts/',  // Absolute server path
        ];
        
        $projectFontsDir = null;
        foreach ($fontDirs as $fontDir) {
            if (is_dir($fontDir)) {
                $projectFontsDir = $fontDir;
                break;
            }
        }
        
        if (!$projectFontsDir) {
            error_log("QR Surround PDF - WARNING: No fonts directory found, will use system fonts only");
        }
        error_log("QR Surround PDF - getGdFontPath called with: '$fontFamily' (lowercase: '$fontFamilyLower')");
        error_log("QR Surround PDF - Project fonts directory: '$projectFontsDir'");
        error_log("QR Surround PDF - Directory exists: " . (is_dir($projectFontsDir) ? 'YES' : 'NO'));
        if (is_dir($projectFontsDir)) {
            $files = scandir($projectFontsDir);
            error_log("QR Surround PDF - Files in fonts directory: " . implode(', ', array_filter($files, function($f) { return $f !== '.' && $f !== '..'; })));
        }
        
        // Build font map - only project fonts (no system fonts available on this server)
        $fontMap = [];
        
        if ($projectFontsDir) {
            // Standard fonts
            $fontMap['arial'] = [
                $projectFontsDir . 'arial.ttf',
                $projectFontsDir . 'Arial.ttf',
            ];
            $fontMap['helvetica'] = [
                $projectFontsDir . 'helvetica.ttf',
                $projectFontsDir . 'Helvetica.ttf',
            ];
            $fontMap['times new roman'] = [
                $projectFontsDir . 'times.ttf',
                $projectFontsDir . 'Times.ttf',
            ];
            $fontMap['times'] = [
                $projectFontsDir . 'times.ttf',
                $projectFontsDir . 'Times.ttf',
            ];
            $fontMap['courier new'] = [
                $projectFontsDir . 'courier.ttf',
                $projectFontsDir . 'Courier.ttf',
            ];
            $fontMap['courier'] = [
                $projectFontsDir . 'courier.ttf',
                $projectFontsDir . 'Courier.ttf',
            ];
            $fontMap['georgia'] = [
                $projectFontsDir . 'georgia.ttf',
                $projectFontsDir . 'Georgia.ttf',
            ];
            $fontMap['verdana'] = [
                $projectFontsDir . 'verdana.ttf',
                $projectFontsDir . 'Verdana.ttf',
            ];
            // Handwriting fonts
            $fontMap['dancing script'] = [
                $projectFontsDir . 'DancingScript-Regular.ttf',
                $projectFontsDir . 'dancing-script.ttf',
                $projectFontsDir . 'DancingScript.ttf',
            ];
            $fontMap['caveat'] = [
                $projectFontsDir . 'Caveat-Regular.ttf',
                $projectFontsDir . 'caveat.ttf',
                $projectFontsDir . 'Caveat.ttf',
            ];
            $fontMap['kalam'] = [
                $projectFontsDir . 'Kalam-Regular.ttf',
                $projectFontsDir . 'kalam.ttf',
                $projectFontsDir . 'Kalam.ttf',
            ];
        }
        
        // Find matching font family
        foreach ($fontMap as $key => $paths) {
            $keyLower = strtolower($key);
            $matches = ($keyLower === $fontFamilyLower || stripos($fontFamilyLower, $key) !== false);
            error_log("QR Surround PDF - Checking font key '$key' (lower: '$keyLower') against '$fontFamilyLower' - Match: " . ($matches ? 'YES' : 'NO'));
            
            if ($matches) {
                error_log("QR Surround PDF - Matched font key '$key', checking paths...");
                // Try each path in order until we find one that exists
                foreach ($paths as $path) {
                    $exists = file_exists($path);
                    $readable = $exists ? is_readable($path) : false;
                    error_log("QR Surround PDF -   Path: '$path' - Exists: " . ($exists ? 'YES' : 'NO') . ", Readable: " . ($readable ? 'YES' : 'NO'));
                    if ($exists && $readable) {
                        error_log("QR Surround PDF - SUCCESS: Returning font path: '$path'");
                        return $path;
                    }
                }
                error_log("QR Surround PDF - No valid font file found for key '$key'");
            }
        }
        
        // No default system fonts - will use built-in GD fonts
        
        // No TTF font found - will use built-in GD fonts
        error_log("QR Surround PDF - WARNING: No font found for '$fontFamily', returning null");
        return null;
    }
    
    /**
     * Draw text using GD's built-in fonts (fallback when TTF not available)
     */
    private function drawTextWithBuiltInFont($image, $text, $color, $width, $y, $height) {
        // Use largest built-in font (5)
        $font = 5;
        $fontWidth = imagefontwidth($font);
        $fontHeight = imagefontheight($font);
        $textPixelWidth = strlen($text) * $fontWidth;
        $textPixelHeight = $fontHeight;
        $textX = (int)round(($width - $textPixelWidth) / 2);
        $textY = (int)round($y + ($height - $textPixelHeight) / 2);
        imagestring($image, $font, $textX, $textY, $text, $color);
    }
}

