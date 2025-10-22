<?php
/**
 * Generate demo images for the demo cards
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/includes/Database.php';

echo "Generating demo images...\n";

try {
    $db = Database::getInstance();
    
    // Get demo data
    $demoData = $db->query("SELECT first_name, last_name, company_name, profile_photo_path, company_logo_path, cover_graphic_path, theme FROM demo_data WHERE website_type = 'primary'");
    
    foreach ($demoData as $row) {
        echo "\nGenerating images for " . $row['first_name'] . " " . $row['last_name'] . "...\n";
        
        // Generate profile photo
        if (!empty($row['profile_photo_path'])) {
            generateProfilePhoto($row['profile_photo_path'], $row['first_name'], $row['last_name'], $row['theme']);
            echo "✓ Generated profile photo: " . $row['profile_photo_path'] . "\n";
        }
        
        // Generate company logo
        if (!empty($row['company_logo_path'])) {
            generateCompanyLogo($row['company_logo_path'], $row['company_name'], $row['theme']);
            echo "✓ Generated company logo: " . $row['company_logo_path'] . "\n";
        }
        
        // Generate cover graphic
        if (!empty($row['cover_graphic_path'])) {
            generateCoverGraphic($row['cover_graphic_path'], $row['company_name'], $row['theme']);
            echo "✓ Generated cover graphic: " . $row['cover_graphic_path'] . "\n";
        }
    }
    
    echo "\n✅ All demo images generated successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

function generateProfilePhoto($filename, $firstName, $lastName, $theme) {
    $width = 400;
    $height = 400;
    
    // Create image
    $image = imagecreatetruecolor($width, $height);
    
    // Get theme colors
    $colors = getThemeColors($theme);
    
    // Create gradient background
    createGradient($image, $width, $height, $colors['primary'], $colors['secondary']);
    
    // Add initials
    $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
    $fontSize = 120;
    $font = 5; // Built-in font
    
    // Calculate text position
    $textWidth = imagefontwidth($font) * strlen($initials);
    $textHeight = imagefontheight($font);
    $x = ($width - $textWidth) / 2;
    $y = ($height - $textHeight) / 2;
    
    // Add text
    $textColor = imagecolorallocate($image, 255, 255, 255);
    imagestring($image, $font, $x, $y, $initials, $textColor);
    
    // Save image
    $path = __DIR__ . '/../storage/media/' . $filename;
    imagejpeg($image, $path, 90);
    imagedestroy($image);
}

function generateCompanyLogo($filename, $companyName, $theme) {
    $width = 300;
    $height = 300;
    
    // Create image
    $image = imagecreatetruecolor($width, $height);
    
    // Get theme colors
    $colors = getThemeColors($theme);
    
    // Create gradient background
    createGradient($image, $width, $height, $colors['primary'], $colors['secondary']);
    
    // Add company name (shortened)
    $shortName = substr($companyName, 0, 3);
    $font = 5;
    
    // Calculate text position
    $textWidth = imagefontwidth($font) * strlen($shortName);
    $textHeight = imagefontheight($font);
    $x = ($width - $textWidth) / 2;
    $y = ($height - $textHeight) / 2;
    
    // Add text
    $textColor = imagecolorallocate($image, 255, 255, 255);
    imagestring($image, $font, $x, $y, $shortName, $textColor);
    
    // Save image
    $path = __DIR__ . '/../storage/media/' . $filename;
    imagejpeg($image, $path, 90);
    imagedestroy($image);
}

function generateCoverGraphic($filename, $companyName, $theme) {
    $width = 800;
    $height = 400;
    
    // Create image
    $image = imagecreatetruecolor($width, $height);
    
    // Get theme colors
    $colors = getThemeColors($theme);
    
    // Create gradient background
    createGradient($image, $width, $height, $colors['primary'], $colors['secondary']);
    
    // Add company name
    $font = 5;
    $textWidth = imagefontwidth($font) * strlen($companyName);
    $textHeight = imagefontheight($font);
    $x = ($width - $textWidth) / 2;
    $y = ($height - $textHeight) / 2;
    
    // Add text
    $textColor = imagecolorallocate($image, 255, 255, 255);
    imagestring($image, $font, $x, $y, $companyName, $textColor);
    
    // Save image
    $path = __DIR__ . '/../storage/media/' . $filename;
    imagejpeg($image, $path, 90);
    imagedestroy($image);
}

function getThemeColors($theme) {
    $colorMap = [
        'professional-blue' => ['primary' => [59, 130, 246], 'secondary' => [37, 99, 235]],
        'creative-sunset' => ['primary' => [251, 146, 60], 'secondary' => [249, 115, 22]],
        'minimalist-gray' => ['primary' => [107, 114, 128], 'secondary' => [75, 85, 99]]
    ];
    
    return $colorMap[$theme] ?? ['primary' => [59, 130, 246], 'secondary' => [37, 99, 235]];
}

function createGradient($image, $width, $height, $color1, $color2) {
    for ($y = 0; $y < $height; $y++) {
        $ratio = $y / $height;
        $r = $color1[0] + ($color2[0] - $color1[0]) * $ratio;
        $g = $color1[1] + ($color2[1] - $color1[1]) * $ratio;
        $b = $color1[2] + ($color2[2] - $color1[2]) * $ratio;
        
        $color = imagecolorallocate($image, $r, $g, $b);
        imageline($image, 0, $y, $width, $y, $color);
    }
}
?>
