<?php
/**
 * Generate Demo Images Script
 * Creates placeholder images for demo business cards
 */

// Set up paths
$webRoot = dirname(__DIR__) . '/web';
$mediaDir = $webRoot . '/storage/media';

// Ensure media directory exists
if (!is_dir($mediaDir)) {
    mkdir($mediaDir, 0755, true);
}

// Demo card data
$demoCards = [
    [
        'name' => 'Alex Chen',
        'initials' => 'AC',
        'company' => 'TechCorp Solutions',
        'theme' => 'blue',
        'colors' => ['#667eea', '#764ba2'], // Professional blue gradient
        'files' => [
            'profile' => 'demo-alex-profile.jpg',
            'logo' => 'demo-techcorp-logo.jpg',
            'cover' => 'demo-techcorp-cover.jpg'
        ]
    ],
    [
        'name' => 'Sarah Martinez',
        'initials' => 'SM',
        'company' => 'Design Studio Pro',
        'theme' => 'purple',
        'colors' => ['#9b59b6', '#8e44ad'], // Creative purple gradient
        'files' => [
            'profile' => 'demo-sarah-profile.jpg',
            'logo' => 'demo-designstudio-logo.jpg',
            'cover' => 'demo-designstudio-cover.jpg'
        ]
    ],
    [
        'name' => 'Michael Thompson',
        'initials' => 'MT',
        'company' => 'Innovation Ventures',
        'theme' => 'gold',
        'colors' => ['#f39c12', '#e67e22'], // Executive gold gradient
        'files' => [
            'profile' => 'demo-michael-profile.jpg',
            'logo' => 'demo-innovation-logo.jpg',
            'cover' => 'demo-innovation-cover.jpg'
        ]
    ]
];

/**
 * Convert hex color to RGB
 */
function hexToRgb($hex) {
    $hex = ltrim($hex, '#');
    return [
        'r' => hexdec(substr($hex, 0, 2)),
        'g' => hexdec(substr($hex, 2, 2)),
        'b' => hexdec(substr($hex, 4, 2))
    ];
}

/**
 * Generate profile photo (400x400px circle with initials)
 */
function generateProfilePhoto($name, $initials, $colors, $filename) {
    global $mediaDir;
    
    $size = 400;
    $image = imagecreatetruecolor($size, $size);
    
    // Create solid color background
    $rgb = hexToRgb($colors[0]);
    $bgColor = imagecolorallocate($image, $rgb['r'], $rgb['g'], $rgb['b']);
    imagefill($image, 0, 0, $bgColor);
    
    // Add initials
    $white = imagecolorallocate($image, 255, 255, 255);
    $font = 5; // Built-in font
    
    // Calculate text position (centered)
    $textWidth = imagefontwidth($font) * strlen($initials);
    $textHeight = imagefontheight($font);
    $x = ($size - $textWidth) / 2;
    $y = ($size - $textHeight) / 2;
    
    imagestring($image, $font, $x, $y, $initials, $white);
    
    // Save image
    $filepath = $mediaDir . '/' . $filename;
    imagejpeg($image, $filepath, 90);
    imagedestroy($image);
    
    echo "Generated profile photo: $filename\n";
}

/**
 * Generate company logo (400x400px geometric design)
 */
function generateCompanyLogo($company, $colors, $filename) {
    global $mediaDir;
    
    $size = 400;
    $image = imagecreatetruecolor($size, $size);
    
    // White background
    $white = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $white);
    
    // Draw geometric logo (simplified company logo)
    $rgb = hexToRgb($colors[0]);
    $logoColor = imagecolorallocate($image, $rgb['r'], $rgb['g'], $rgb['b']);
    
    // Draw overlapping circles/rectangles for logo
    $centerX = $size / 2;
    $centerY = $size / 2;
    $radius = 80;
    
    // Main logo shape (simplified)
    imagefilledellipse($image, $centerX - 30, $centerY, $radius, $radius, $logoColor);
    imagefilledellipse($image, $centerX + 30, $centerY, $radius, $radius, $logoColor);
    
    // Add company name initials
    $textColor = imagecolorallocate($image, 255, 255, 255);
    $font = 5;
    $initials = substr($company, 0, 2);
    $textWidth = imagefontwidth($font) * strlen($initials);
    $textHeight = imagefontheight($font);
    $x = ($size - $textWidth) / 2;
    $y = ($size - $textHeight) / 2;
    
    imagestring($image, $font, $x, $y, $initials, $textColor);
    
    // Save image
    $filepath = $mediaDir . '/' . $filename;
    imagejpeg($image, $filepath, 90);
    imagedestroy($image);
    
    echo "Generated company logo: $filename\n";
}

/**
 * Generate cover graphic (1200x400px banner with company name)
 */
function generateCoverGraphic($company, $colors, $filename) {
    global $mediaDir;
    
    $width = 1200;
    $height = 400;
    $image = imagecreatetruecolor($width, $height);
    
    // Create solid color background
    $rgb = hexToRgb($colors[0]);
    $bgColor = imagecolorallocate($image, $rgb['r'], $rgb['g'], $rgb['b']);
    imagefill($image, 0, 0, $bgColor);
    
    // Add company name
    $white = imagecolorallocate($image, 255, 255, 255);
    $font = 5;
    
    // Calculate text position (centered)
    $textWidth = imagefontwidth($font) * strlen($company);
    $textHeight = imagefontheight($font);
    $x = ($width - $textWidth) / 2;
    $y = ($height - $textHeight) / 2;
    
    imagestring($image, $font, $x, $y, $company, $white);
    
    // Save image
    $filepath = $mediaDir . '/' . $filename;
    imagejpeg($image, $filepath, 90);
    imagedestroy($image);
    
    echo "Generated cover graphic: $filename\n";
}

// Generate all demo images
echo "Generating demo images...\n\n";

foreach ($demoCards as $card) {
    echo "Creating images for {$card['name']} ({$card['company']}):\n";
    
    // Generate profile photo
    generateProfilePhoto($card['name'], $card['initials'], $card['colors'], $card['files']['profile']);
    
    // Generate company logo
    generateCompanyLogo($card['company'], $card['colors'], $card['files']['logo']);
    
    // Generate cover graphic
    generateCoverGraphic($card['company'], $card['colors'], $card['files']['cover']);
    
    echo "\n";
}

echo "Demo image generation complete!\n";
echo "Generated 9 images in: $mediaDir\n";
?>
