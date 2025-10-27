<?php
// Include Composer autoloader if available
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check if image was uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No image uploaded or upload error']);
    exit;
}

$imageFile = $_FILES['image'];
$imagePath = $imageFile['tmp_name'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
if (!in_array($imageFile['type'], $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type. Only JPEG, PNG, and GIF are allowed.']);
    exit;
}

// Validate file size (max 5MB)
if ($imageFile['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'File too large. Maximum size is 5MB.']);
    exit;
}

try {
    // Create a temporary file for the image
    $tempImagePath = tempnam(sys_get_temp_dir(), 'qr_image_') . '.jpg';
    move_uploaded_file($imagePath, $tempImagePath);
    
    // Try to detect QR code using PHP GD and basic image processing
    $qrData = detectQRCodeFromImage($tempImagePath);
    
    // Clean up temp file
    unlink($tempImagePath);
    
    if ($qrData) {
        // Log the detected data for debugging
        error_log('QR Code detected: ' . substr($qrData, 0, 200) . '...');
        
        // Check if it's a vCard (be more flexible with detection)
        $isVCard = false;
        $vCardData = $qrData;
        
        // Check various vCard formats
        if (strpos($qrData, 'BEGIN:VCARD') === 0) {
            $isVCard = true;
        } elseif (strpos($qrData, 'vcard://') === 0) {
            // Handle vcard:// URLs
            $isVCard = true;
            $vCardData = urldecode($qrData);
        } elseif (strpos($qrData, 'VCARD') !== false || strpos($qrData, 'vcard') !== false) {
            // Look for vCard keywords anywhere in the data
            $isVCard = true;
        } elseif (strpos($qrData, 'FN:') !== false || strpos($qrData, 'N:') !== false) {
            // Look for vCard field markers
            $isVCard = true;
        } elseif (isUrl($qrData)) {
            // Handle URL-based vCard (like ShareMyCard QR codes)
            error_log('Detected URL in QR code: ' . $qrData);
            $vCardData = fetchVCardFromUrl($qrData);
            if ($vCardData) {
                $isVCard = true;
                error_log('Successfully fetched vCard from URL');
            } else {
                error_log('Failed to fetch vCard from URL: ' . $qrData);
            }
        }
        
        if ($isVCard) {
            echo json_encode([
                'success' => true,
                'type' => 'vcard',
                'data' => $vCardData,
                'message' => 'vCard QR code detected and processed successfully',
                'debug' => 'Detected vCard format in QR code'
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'type' => 'text',
                'data' => $qrData,
                'message' => 'QR code detected but it\'s not a vCard format',
                'debug' => 'QR data: ' . substr($qrData, 0, 100) . '...'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No QR code found in image',
            'debug' => 'QR detection failed - image may not contain a readable QR code'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error processing image: ' . $e->getMessage()
    ]);
}

function detectQRCodeFromImage($imagePath) {
    // Check if the image file exists and is readable
    if (!file_exists($imagePath) || !is_readable($imagePath)) {
        return false;
    }
    
    // Try to use the QR detection library if available
    if (class_exists('Zxing\QrReader')) {
        try {
            $qrcode = new Zxing\QrReader($imagePath);
            $text = $qrcode->text();
            
            if ($text) {
                return $text;
            }
        } catch (Exception $e) {
            error_log('QR detection error: ' . $e->getMessage());
        }
    }
    
    // Fallback: Try using exec with zbarimg if available
    if (function_exists('exec')) {
        $command = "zbarimg " . escapeshellarg($imagePath) . " 2>/dev/null";
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0 && !empty($output)) {
            // zbarimg outputs in format "QR-Code:data"
            foreach ($output as $line) {
                if (strpos($line, 'QR-Code:') === 0) {
                    return substr($line, 8); // Remove "QR-Code:" prefix
                }
            }
        }
    }
    
    // Fallback: Try using exec with qrencode/qrdetect if available
    if (function_exists('exec')) {
        $command = "qrdetect " . escapeshellarg($imagePath) . " 2>/dev/null";
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0 && !empty($output)) {
            return implode("\n", $output);
        }
    }
    
    // If no QR detection libraries are available, return false
    return false;
}

function isUrl($string) {
    // Check if the string looks like a URL
    return filter_var($string, FILTER_VALIDATE_URL) !== false;
}

function fetchVCardFromUrl($url) {
    error_log('Attempting to fetch vCard from URL: ' . $url);
    
    // Fetch vCard data from URL
    $context = stream_context_create([
        'http' => [
            'timeout' => 10, // 10 second timeout
            'user_agent' => 'QRCard Scanner/1.0',
            'follow_location' => true, // Follow redirects
            'max_redirects' => 5
        ]
    ]);
    
    try {
        $content = file_get_contents($url, false, $context);
        
        if ($content === false) {
            error_log('Failed to fetch URL: ' . $url);
            return false;
        }
        
        error_log('Fetched content length: ' . strlen($content));
        error_log('Content preview: ' . substr($content, 0, 200));
        
        // Check if the content looks like a vCard
        if (strpos($content, 'BEGIN:VCARD') === 0) {
            error_log('Successfully fetched vCard from URL: ' . $url);
            return $content;
        } else {
            error_log('URL content is not a vCard. Content type: ' . gettype($content));
            error_log('First 200 chars: ' . substr($content, 0, 200));
            return false;
        }
        
    } catch (Exception $e) {
        error_log('Error fetching URL ' . $url . ': ' . $e->getMessage());
        return false;
    }
}
?>
