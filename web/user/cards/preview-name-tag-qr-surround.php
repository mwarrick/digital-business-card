<?php
/**
 * Name Tag Preview Generator - QR Surround Variant
 * Generates PNG preview of single name tag with QR code surrounding name
 */

// Start output buffering to prevent any output before headers
ob_start();

// Turn off error display for production (errors will be logged)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Check if GD library is available
if (!function_exists('imagecreatetruecolor')) {
    ob_clean();
    http_response_code(500);
    header('Content-Type: image/png');
    // Create error image
    $errorImage = imagecreatetruecolor(324, 224) ?: imagecreate(324, 224);
    $white = imagecolorallocate($errorImage, 255, 255, 255);
    $red = imagecolorallocate($errorImage, 255, 0, 0);
    imagefill($errorImage, 0, 0, $white);
    imagestring($errorImage, 5, 10, 100, 'GD Library not available', $red);
    imagepng($errorImage);
    imagedestroy($errorImage);
    exit;
}

require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../../api/includes/NameTagGenerator.php';

// Helper function to get font path (duplicate from NameTagGenerator for preview)
function getGdFontPathForPreview($fontFamily) {
    $fontFamily = trim($fontFamily);
    $fontFamilyLower = strtolower($fontFamily);
    
    // Get absolute path to fonts directory using DOCUMENT_ROOT
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? dirname(dirname(dirname(__DIR__)));
    // Remove trailing slash if present
    $docRoot = rtrim(str_replace('\\', '/', $docRoot), '/');
    // Project fonts directory - try multiple possible locations
    $fontDirs = [
        $docRoot . '/fonts/',  // Standard location based on DOCUMENT_ROOT
        __DIR__ . '/../../fonts/',  // Relative from preview file (web/user/cards -> web/fonts)
        dirname(dirname(dirname(__DIR__))) . '/fonts/',  // Absolute from preview file
        '/public_html/fonts/',  // Absolute server path
    ];
    
    error_log("=== NEW FONT RESOLUTION CODE V2 ===");
    error_log("QR Surround Preview - getGdFontPathForPreview called with: '$fontFamily' (lowercase: '$fontFamilyLower')");
    error_log("QR Surround Preview - DOCUMENT_ROOT: '$docRoot'");
    
    $projectFontsDir = null;
    foreach ($fontDirs as $idx => $fontDir) {
        $exists = is_dir($fontDir);
        error_log("QR Surround Preview - Font dir option $idx: '$fontDir' - Exists: " . ($exists ? 'YES' : 'NO'));
        if ($exists) {
            $projectFontsDir = $fontDir;
            $files = scandir($fontDir);
            $fontFiles = array_filter($files, function($f) { return $f !== '.' && $f !== '..' && preg_match('/\.ttf$/i', $f); });
            error_log("QR Surround Preview - Using font directory: '$fontDir'");
            error_log("QR Surround Preview - Font files found: " . implode(', ', $fontFiles));
            break;
        }
    }
    
    if (!$projectFontsDir) {
        error_log("QR Surround Preview - WARNING: No fonts directory found, will use built-in GD fonts only");
        return null; // No fonts available
    }
    
    // Build font map - only fonts that actually exist in the directory
    $fontMap = [];
    
    // Handwriting fonts that are installed
    $caveatPaths = [
        $projectFontsDir . 'Caveat-Regular.ttf',
        $projectFontsDir . 'caveat.ttf',
        $projectFontsDir . 'Caveat.ttf',
    ];
    
    $fontMap['dancing script'] = [
        $projectFontsDir . 'DancingScript-Regular.ttf',
        $projectFontsDir . 'dancing-script.ttf',
        $projectFontsDir . 'DancingScript.ttf',
    ];
    $fontMap['caveat'] = $caveatPaths;
    $fontMap['kalam'] = [
        $projectFontsDir . 'Kalam-Regular.ttf',
        $projectFontsDir . 'kalam.ttf',
        $projectFontsDir . 'Kalam.ttf',
    ];
    
    // Map standard font names to Caveat as fallback (create new array, not reference)
    // This way if someone somehow selects Arial, it will use Caveat instead
    // IMPORTANT: Use array_merge to create a copy, not a reference
    $fontMap['arial'] = array_merge([], $caveatPaths);
    $fontMap['helvetica'] = array_merge([], $caveatPaths);
    $fontMap['times new roman'] = array_merge([], $caveatPaths);
    $fontMap['times'] = array_merge([], $caveatPaths);
    $fontMap['courier new'] = array_merge([], $caveatPaths);
    $fontMap['courier'] = array_merge([], $caveatPaths);
    $fontMap['georgia'] = array_merge([], $caveatPaths);
    $fontMap['verdana'] = array_merge([], $caveatPaths);
    
    error_log("QR Surround Preview - Font map built. Keys: " . implode(', ', array_keys($fontMap)));
    error_log("QR Surround Preview - Arial paths: " . implode(', ', $fontMap['arial']));
    error_log("QR Surround Preview - Caveat paths: " . implode(', ', $fontMap['caveat']));
    
    foreach ($fontMap as $key => $paths) {
        $keyLower = strtolower($key);
        $matches = ($keyLower === $fontFamilyLower || stripos($fontFamilyLower, $key) !== false);
        error_log("QR Surround Preview - Checking font key '$key' (lower: '$keyLower') against '$fontFamilyLower' - Match: " . ($matches ? 'YES' : 'NO'));
        
        if ($matches) {
            error_log("QR Surround Preview - Matched font key '$key', checking paths...");
            foreach ($paths as $path) {
                $exists = file_exists($path);
                $readable = $exists ? is_readable($path) : false;
                error_log("QR Surround Preview -   Path: '$path' - Exists: " . ($exists ? 'YES' : 'NO') . ", Readable: " . ($readable ? 'YES' : 'NO'));
                if ($exists && $readable) {
                    error_log("QR Surround Preview - SUCCESS: Returning font path: '$path'");
                    return $path;
                }
            }
            error_log("QR Surround Preview - No valid font file found for key '$key'");
        }
    }
    
    // No default system fonts - will use built-in GD fonts
    
    error_log("QR Surround Preview - WARNING: No font found for '$fontFamily', returning null");
    return null;
}

// Helper function (define before use)
function hexToRgb($hex) {
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

// Get parameters
// If parameters aren't parsed correctly (due to &amp; issue), try parsing from REQUEST_URI
if (empty($_GET['card_id']) && !empty($_SERVER['REQUEST_URI'])) {
    $queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
    if ($queryString) {
        // Replace &amp; with & if present
        $queryString = str_replace('&amp;', '&', $queryString);
        parse_str($queryString, $parsedParams);
        if (!empty($parsedParams)) {
            $_GET = array_merge($_GET, $parsedParams);
        }
    }
}

error_log("QR Surround Preview - All GET params: " . print_r($_GET, true));
error_log("QR Surround Preview - REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A'));

$cardId = $_GET['card_id'] ?? '';
$topBannerText = $_GET['top_banner_text'] ?? 'Hello My Name Is...';
$topBannerColor = $_GET['top_banner_color'] ?? '#000000';
$topBannerFontFamily = $_GET['top_banner_font_family'] ?? 'Arial';
$topBannerFontSize = (int)($_GET['top_banner_font_size'] ?? 16);
$bottomBannerText = $_GET['bottom_banner_text'] ?? '';
$bottomBannerColor = $_GET['bottom_banner_color'] ?? '#000000';
$bottomBannerFontFamily = $_GET['bottom_banner_font_family'] ?? 'Arial';
$bottomBannerFontSize = (int)($_GET['bottom_banner_font_size'] ?? 8);

error_log("QR Surround Preview - Parsed params - Card ID: '$cardId'");

// Validate parameters
if (empty($cardId)) {
    ob_clean();
    http_response_code(400);
    header('Content-Type: image/png');
    $errorImage = imagecreatetruecolor(324, 224);
    $white = imagecolorallocate($errorImage, 255, 255, 255);
    $red = imagecolorallocate($errorImage, 255, 0, 0);
    imagefill($errorImage, 0, 0, $white);
    imagestring($errorImage, 5, 10, 100, 'Missing card ID', $red);
    imagepng($errorImage);
    imagedestroy($errorImage);
    exit;
}

// Debug: Log the name value
$db = Database::getInstance();

// Verify card exists and is active
$card = $db->querySingle(
    "SELECT id FROM business_cards WHERE id = ? AND is_active = 1",
    [$cardId]
);

if (!$card) {
    ob_clean();
    http_response_code(404);
    header('Content-Type: image/png');
    $errorImage = imagecreatetruecolor(324, 224);
    $white = imagecolorallocate($errorImage, 255, 255, 255);
    $red = imagecolorallocate($errorImage, 255, 0, 0);
    imagefill($errorImage, 0, 0, $white);
    imagestring($errorImage, 5, 10, 100, 'Card not found', $red);
    imagepng($errorImage);
    imagedestroy($errorImage);
    exit;
}

try {
    // Log debug info
    error_log("QR Surround Preview Debug - Card ID: $cardId");
    error_log("QR Surround Preview Debug - Banner text: $topBannerText, Color: $topBannerColor");
    
    // Build preferences array
    $preferences = [
        'top_banner_text' => $topBannerText,
        'top_banner_color' => $topBannerColor,
        'bottom_banner_text' => $bottomBannerText,
        'bottom_banner_color' => $bottomBannerColor
    ];
    
    // Generate preview image directly using GD
    error_log("QR Surround Preview Debug - Starting image creation");
    
    // Generate image using GD
    // Use exact print dimensions: 3-3/8" = 1013 pixels, 2-1/3" = 700 pixels
    $width = 1013; // Exact print width
    $height = 700; // Exact print height
    
    $image = imagecreatetruecolor($width, $height);
    if ($image === false) {
        throw new Exception('Failed to create image resource');
    }
    error_log("QR Surround Preview Debug - Image created: $width x $height");
    
    // White background
    $white = imagecolorallocate($image, 255, 255, 255);
    if ($white === false) {
        throw new Exception('Failed to allocate white color');
    }
    imagefill($image, 0, 0, $white);
    error_log("QR Surround Preview Debug - Background filled");
    
    // Draw QR code - fill entire image
    $availableWidth = $width;
    $availableHeight = $height;
    
    error_log("QR Surround Preview Debug - Image dimensions: width=$width, height=$height");
    error_log("QR Surround Preview Debug - Available space: $availableWidth x $availableHeight");
    
    // Make QR code square (use the smaller dimension to ensure it fits)
    // QR codes must be square for proper scannability
    $qrSize = min($width * 0.95, $height); // Use 95% of width or full height, whichever is smaller
    $qrWidth = (int)round($qrSize);
    $qrHeight = (int)round($qrSize); // Make it square
    $qrX = (int)round(($width - $qrWidth) / 2); // Center horizontally
    $qrY = (int)round(($height - $qrHeight) / 2); // Center vertically
    
    // Validate coordinates are within image bounds
    if ($qrX < 0 || $qrY < 0 || $qrX + $qrWidth > $width || $qrY + $qrHeight > $height) {
        error_log("QR Surround Preview Debug - ERROR: QR coordinates out of bounds!");
        error_log("QR Surround Preview Debug - QR: x=$qrX, y=$qrY, w=$qrWidth, h=$qrHeight");
        error_log("QR Surround Preview Debug - Image: w=$width, h=$height");
        // Fix coordinates to be within bounds
        $qrX = max(0, min($qrX, $width - $qrWidth));
        $qrY = max(0, min($qrY, $height - $qrHeight));
        error_log("QR Surround Preview Debug - Fixed QR: x=$qrX, y=$qrY");
    }
    
    // Generate QR code image
    $qrUrl = "https://sharemycard.app/card.php?id=" . $cardId . "&src=nametag-qr-surround";
    // Request high resolution QR code (3x the final size for crisp rendering)
    $qrSize = max(500, min(4000, round(max($qrWidth, $qrHeight) * 3))); // High resolution for quality
    $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=" . $qrSize . "x" . $qrSize . "&data=" . urlencode($qrUrl) . "&ecc=H";
    
    error_log("QR Surround Preview Debug - QR URL: $qrUrl");
    error_log("QR Surround Preview Debug - QR Code API URL: $qrCodeUrl");
    error_log("QR Surround Preview Debug - QR Position: x=$qrX, y=$qrY, size=$qrWidth x $qrHeight");
    
    // Draw a test rectangle first to show where QR should be (for debugging)
    // $testRect = imagecolorallocate($image, 200, 200, 200); // Light gray
    // imagerectangle($image, $qrX, $qrY, $qrX + $qrWidth, $qrY + $qrHeight, $testRect);
    
    $qrCodeData = false;
    $qrImage = false;
    
    // Try to fetch QR code
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'ShareMyCard/1.0'
        ]
    ]);
    
    $qrCodeData = @file_get_contents($qrCodeUrl, false, $context);
    
    if ($qrCodeData === false || strlen($qrCodeData) < 100) {
        $error = error_get_last();
        error_log("QR Surround Preview Debug - Failed to fetch QR code. Error: " . ($error ? $error['message'] : 'Unknown'));
        error_log("QR Surround Preview Debug - allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'enabled' : 'disabled'));
        
        // Draw a simple QR-like pattern as fallback
        $black = imagecolorallocate($image, 0, 0, 0);
        $gray = imagecolorallocate($image, 128, 128, 128);
        
        // Draw a simple checkerboard pattern to simulate QR code
        $moduleSize = (int)max(2, round($qrWidth / 25));
        for ($y = 0; $y < $qrHeight; $y += $moduleSize) {
            for ($x = 0; $x < $qrWidth; $x += $moduleSize) {
                $color = (($x + $y) % ($moduleSize * 2) < $moduleSize) ? $black : $gray;
                $rectX = $qrX + $x;
                $rectY = $qrY + $y;
                $rectX2 = $qrX + $x + $moduleSize - 1;
                $rectY2 = $qrY + $y + $moduleSize - 1;
                imagefilledrectangle($image, $rectX, $rectY, $rectX2, $rectY2, $color);
            }
        }
        
        error_log("QR Surround Preview Debug - Using fallback QR pattern");
    } else {
        error_log("QR Surround Preview Debug - QR code fetched, size: " . strlen($qrCodeData) . " bytes");
        $qrImage = @imagecreatefromstring($qrCodeData);
        if ($qrImage === false) {
            $error = error_get_last();
            error_log("QR Surround Preview Debug - Failed to create image from QR code data. Error: " . ($error ? $error['message'] : 'Unknown'));
            error_log("QR Surround Preview Debug - First 100 bytes: " . substr($qrCodeData, 0, 100));
            
            // Draw fallback pattern
            $black = imagecolorallocate($image, 0, 0, 0);
            $gray = imagecolorallocate($image, 128, 128, 128);
            $moduleSize = (int)max(2, round($qrWidth / 25));
            for ($y = 0; $y < $qrHeight; $y += $moduleSize) {
                for ($x = 0; $x < $qrWidth; $x += $moduleSize) {
                    $color = (($x + $y) % ($moduleSize * 2) < $moduleSize) ? $black : $gray;
                    $rectX = $qrX + $x;
                    $rectY = $qrY + $y;
                    $rectX2 = $qrX + $x + $moduleSize - 1;
                    $rectY2 = $qrY + $y + $moduleSize - 1;
                    imagefilledrectangle($image, $rectX, $rectY, $rectX2, $rectY2, $color);
                }
            }
        } else {
            error_log("QR Surround Preview Debug - QR image created successfully, dimensions: " . imagesx($qrImage) . "x" . imagesy($qrImage));
            
            // Crop the white quiet zone (border) from the QR code
            // Find the actual QR code bounds by detecting where black pixels start
            $qrSourceWidth = imagesx($qrImage);
            $qrSourceHeight = imagesy($qrImage);
            
            // Get white color index for comparison
            $whiteColor = imagecolorat($qrImage, 0, 0);
            
            // Find top edge (first row with non-white pixels)
            $top = 0;
            for ($y = 0; $y < $qrSourceHeight; $y++) {
                for ($x = 0; $x < $qrSourceWidth; $x++) {
                    if (imagecolorat($qrImage, $x, $y) != $whiteColor) {
                        $top = $y;
                        break 2;
                    }
                }
            }
            
            // Find bottom edge
            $bottom = $qrSourceHeight - 1;
            for ($y = $qrSourceHeight - 1; $y >= 0; $y--) {
                for ($x = 0; $x < $qrSourceWidth; $x++) {
                    if (imagecolorat($qrImage, $x, $y) != $whiteColor) {
                        $bottom = $y;
                        break 2;
                    }
                }
            }
            
            // Find left edge
            $left = 0;
            for ($x = 0; $x < $qrSourceWidth; $x++) {
                for ($y = 0; $y < $qrSourceHeight; $y++) {
                    if (imagecolorat($qrImage, $x, $y) != $whiteColor) {
                        $left = $x;
                        break 2;
                    }
                }
            }
            
            // Find right edge
            $right = $qrSourceWidth - 1;
            for ($x = $qrSourceWidth - 1; $x >= 0; $x--) {
                for ($y = 0; $y < $qrSourceHeight; $y++) {
                    if (imagecolorat($qrImage, $x, $y) != $whiteColor) {
                        $right = $x;
                        break 2;
                    }
                }
            }
            
            // Calculate cropped dimensions
            $croppedWidth = $right - $left + 1;
            $croppedHeight = $bottom - $top + 1;
            
            error_log("QR Surround Preview Debug - QR quiet zone: left=$left, top=$top, right=$right, bottom=$bottom");
            error_log("QR Surround Preview Debug - Cropped QR size: $croppedWidth x $croppedHeight (original: $qrSourceWidth x $qrSourceHeight)");
            
            // Create new image with cropped QR code (no quiet zone)
            $croppedQrImage = imagecreatetruecolor($croppedWidth, $croppedHeight);
            $white = imagecolorallocate($croppedQrImage, 255, 255, 255);
            imagefill($croppedQrImage, 0, 0, $white);
            
            // Copy the cropped portion
            imagecopy($croppedQrImage, $qrImage, 0, 0, $left, $top, $croppedWidth, $croppedHeight);
            imagedestroy($qrImage);
            $qrImage = $croppedQrImage;
            $qrSourceWidth = $croppedWidth;
            $qrSourceHeight = $croppedHeight;
            
            error_log("QR Surround Preview Debug - About to copy cropped QR: source=$qrSourceWidth x $qrSourceHeight, dest=$qrWidth x $qrHeight, pos=$qrX,$qrY");
            
            // Draw a bright red rectangle FIRST to mark where QR should be (for debugging)
            $testRed = imagecolorallocate($image, 255, 0, 0);
            imagefilledrectangle($image, $qrX, $qrY, $qrX + $qrWidth - 1, $qrY + $qrHeight - 1, $testRed);
            
            // Now copy the QR code on top (should cover the red)
            $copyResult = imagecopyresampled($image, $qrImage, $qrX, $qrY, 0, 0, $qrWidth, $qrHeight, $qrSourceWidth, $qrSourceHeight);
            if ($copyResult === false) {
                error_log("QR Surround Preview Debug - WARNING: imagecopyresampled failed!");
                // Keep red rectangle visible
            } else {
                error_log("QR Surround Preview Debug - QR code copied successfully - red rectangle should be covered");
            }
            imagedestroy($qrImage);
            error_log("QR Surround Preview Debug - QR code drawn at x=$qrX, y=$qrY, size=$qrWidth x $qrHeight");
        }
    }
    
    // Draw a border around the QR code area for debugging (remove later)
    $borderColor = imagecolorallocate($image, 128, 128, 128);
    imagerectangle($image, $qrX, $qrY, $qrX + $qrWidth - 1, $qrY + $qrHeight - 1, $borderColor);
    
    // Banners removed for now - focus on QR code only
    
    // Clear any output before sending headers
    ob_clean();
    
    error_log("QR Surround Preview Debug - About to output image");
    
    // Set headers for image output
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=300'); // Cache for 5 minutes
    
    // Output image
    $result = imagepng($image);
    if ($result === false) {
        error_log("QR Surround Preview Debug - Failed to output PNG");
    } else {
        error_log("QR Surround Preview Debug - PNG output successfully");
    }
    imagedestroy($image);
    exit;
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    error_log('Name tag QR surround preview error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    // Output error as image
    if (function_exists('imagecreatetruecolor')) {
        $errorImage = imagecreatetruecolor(324, 224);
        $white = imagecolorallocate($errorImage, 255, 255, 255);
        $red = imagecolorallocate($errorImage, 255, 0, 0);
        imagefill($errorImage, 0, 0, $white);
        imagestring($errorImage, 5, 10, 100, 'Error: ' . substr($e->getMessage(), 0, 40), $red);
        header('Content-Type: image/png');
        imagepng($errorImage);
        imagedestroy($errorImage);
    } else {
        header('Content-Type: text/plain');
        echo 'Error: ' . $e->getMessage();
    }
    exit;
} catch (Throwable $e) {
    ob_clean();
    http_response_code(500);
    error_log('Name tag QR surround preview fatal error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    // Output error as image
    if (function_exists('imagecreatetruecolor')) {
        $errorImage = imagecreatetruecolor(324, 224);
        $white = imagecolorallocate($errorImage, 255, 255, 255);
        $red = imagecolorallocate($errorImage, 255, 0, 0);
        imagefill($errorImage, 0, 0, $white);
        imagestring($errorImage, 5, 10, 100, 'Fatal Error', $red);
        header('Content-Type: image/png');
        imagepng($errorImage);
        imagedestroy($errorImage);
    } else {
        header('Content-Type: text/plain');
        echo 'Fatal Error: ' . $e->getMessage();
    }
    exit;
}
?>

