<?php
// Enable error reporting and logging (but suppress deprecation warnings from output)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable display_errors to prevent HTML output
ini_set('log_errors', 1);

// Include Composer autoloader if available
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Increase memory limit for image processing
ini_set('memory_limit', '512M');

// Log the start of the request
error_log('=== QR IMAGE PROCESSING REQUEST START ===');
error_log('Request Method: ' . $_SERVER['REQUEST_METHOD']);
error_log('Content Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'Not set'));
error_log('Content Length: ' . ($_SERVER['CONTENT_LENGTH'] ?? 'Not set'));

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('ERROR: Method not allowed - ' . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check if image was uploaded via multipart/form-data
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    error_log('ERROR: No image uploaded or upload error');
    error_log('Upload error: ' . ($_FILES['image']['error'] ?? 'No file uploaded'));
    http_response_code(400);
    echo json_encode(['error' => 'No image uploaded or upload error', 'debug' => 'Upload error: ' . ($_FILES['image']['error'] ?? 'No file uploaded')]);
    exit;
}

$imageFile = $_FILES['image'];
$imagePath = $imageFile['tmp_name'];

error_log('Image file uploaded: ' . $imageFile['name']);
error_log('Image file size: ' . $imageFile['size'] . ' bytes');
error_log('Image file type: ' . $imageFile['type']);

// Validate file type
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
if (!in_array($imageFile['type'], $allowedTypes)) {
    error_log('ERROR: Invalid file type - ' . $imageFile['type']);
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type. Only JPEG, PNG, and GIF are allowed.', 'debug' => 'File type: ' . $imageFile['type']]);
    exit;
}

// Validate file size (max 5MB)
if ($imageFile['size'] > 5 * 1024 * 1024) {
    error_log('ERROR: File too large - ' . $imageFile['size'] . ' bytes');
    http_response_code(400);
    echo json_encode(['error' => 'File too large. Maximum size is 5MB.', 'debug' => 'File size: ' . $imageFile['size'] . ' bytes']);
    exit;
}

try {
    // Resize image if it's too large to prevent memory exhaustion
    // QR codes don't need high resolution - max 2000px on longest side
    $tempImagePath = resizeImageIfNeeded($imagePath, 2000);
    error_log('Using image file: ' . $tempImagePath . ($tempImagePath !== $imagePath ? ' (resized)' : ' (original)'));
    
    // Try to detect QR code using PHP GD and basic image processing
    error_log('Starting QR code detection...');
    $qrData = detectQRCodeFromImage($tempImagePath);
    error_log('QR detection result: ' . ($qrData ? 'SUCCESS - ' . substr($qrData, 0, 100) : 'FAILED'));
    
    // Clean up temp file (only if we created a new one)
    if ($tempImagePath !== $imagePath && file_exists($tempImagePath)) {
        unlink($tempImagePath);
    }
    
    if ($qrData) {
        // Log the detected data for debugging
        error_log('QR Code detected: ' . substr($qrData, 0, 200) . '...');
        
        // Check if it's a vCard (be more flexible with detection)
        $isVCard = false;
        $vCardData = $qrData;
        
        // Check various vCard formats - prioritize URL detection first
        if (isUrl($qrData)) {
            // Handle URL-based vCard (like ShareMyCard QR codes) - check this FIRST
            error_log('Detected URL in QR code: ' . $qrData);
            $vCardData = fetchVCardFromUrl($qrData);
            if ($vCardData) {
                $isVCard = true;
                error_log('Successfully fetched vCard from URL');
            } else {
                error_log('Failed to fetch vCard from URL: ' . $qrData);
            }
        } elseif (strpos($qrData, 'BEGIN:VCARD') === 0) {
            // Direct vCard data
            $isVCard = true;
        } elseif (strpos($qrData, 'vcard://') === 0) {
            // Handle vcard:// URLs
            $isVCard = true;
            $vCardData = urldecode($qrData);
        } elseif (strpos($qrData, 'FN:') !== false || strpos($qrData, 'N:') !== false) {
            // Look for vCard field markers in non-URL data
            $isVCard = true;
        } elseif (strpos($qrData, 'VCARD') !== false || strpos($qrData, 'vcard') !== false) {
            // Look for vCard keywords anywhere in non-URL data
            $isVCard = true;
        } else {
            // Debug: log what we detected but didn't recognize
            error_log('QR data not recognized as vCard or URL: ' . substr($qrData, 0, 100));
        }
        
        if ($isVCard) {
            // Parse vCard data into contact fields
            $contactData = parseVCardData($vCardData);
            
            echo json_encode([
                'success' => true,
                'type' => 'vcard',
                'data' => $vCardData,
                'contact_data' => $contactData,
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
        // Provide more detailed error information
        $errorDetails = 'QR detection failed - image may not contain a readable QR code. ';
        $errorDetails .= 'Please ensure the QR code is: clearly visible, well-lit, not blurry, and not partially obscured.';
        
        echo json_encode([
            'success' => false,
            'error' => 'No QR code found in image',
            'debug' => $errorDetails,
            'message' => $errorDetails
        ]);
    }
    
} catch (Exception $e) {
    error_log('QR processing error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error processing image: ' . $e->getMessage(),
        'debug' => 'Exception details: ' . $e->getMessage()
    ]);
}

error_log('=== QR IMAGE PROCESSING REQUEST END ===');

function resizeImageIfNeeded($imagePath, $maxDimension = 2000) {
    // Check if GD library is available
    if (!function_exists('getimagesize') || !function_exists('imagecreatefromjpeg')) {
        error_log('GD library not available, using original image');
        return $imagePath;
    }
    
    // Get image dimensions
    $imageInfo = @getimagesize($imagePath);
    if ($imageInfo === false) {
        error_log('Could not get image size, using original image');
        return $imagePath;
    }
    
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $mimeType = $imageInfo['mime'];
    
    error_log('Image dimensions: ' . $width . 'x' . $height . ', type: ' . $mimeType);
    
    // Check if resizing is needed
    if ($width <= $maxDimension && $height <= $maxDimension) {
        error_log('Image is already small enough, no resizing needed');
        return $imagePath;
    }
    
    // Calculate new dimensions while maintaining aspect ratio
    $ratio = min($maxDimension / $width, $maxDimension / $height);
    $newWidth = (int)round($width * $ratio);
    $newHeight = (int)round($height * $ratio);
    
    error_log('Resizing image from ' . $width . 'x' . $height . ' to ' . $newWidth . 'x' . $newHeight);
    
    // Create a new image based on MIME type
    $sourceImage = null;
    switch ($mimeType) {
        case 'image/jpeg':
            $sourceImage = @imagecreatefromjpeg($imagePath);
            break;
        case 'image/png':
            $sourceImage = @imagecreatefrompng($imagePath);
            break;
        case 'image/gif':
            $sourceImage = @imagecreatefromgif($imagePath);
            break;
        default:
            error_log('Unsupported image type: ' . $mimeType);
            return $imagePath;
    }
    
    if ($sourceImage === false) {
        error_log('Could not create source image, using original');
        return $imagePath;
    }
    
    // Create resized image
    $resizedImage = @imagecreatetruecolor($newWidth, $newHeight);
    if ($resizedImage === false) {
        error_log('Could not create resized image, using original');
        imagedestroy($sourceImage);
        return $imagePath;
    }
    
    // Preserve transparency for PNG and GIF
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagealphablending($resizedImage, false);
        imagesavealpha($resizedImage, true);
        $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
        imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize the image
    if (!@imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height)) {
        error_log('Could not resize image, using original');
        imagedestroy($sourceImage);
        imagedestroy($resizedImage);
        return $imagePath;
    }
    
    // Create temporary file for resized image
    $tempFile = tempnam(sys_get_temp_dir(), 'qr_resized_');
    $success = false;
    
    // Save resized image based on original type
    switch ($mimeType) {
        case 'image/jpeg':
            $success = @imagejpeg($resizedImage, $tempFile, 85); // 85% quality
            break;
        case 'image/png':
            $success = @imagepng($resizedImage, $tempFile, 6); // Compression level 6
            break;
        case 'image/gif':
            $success = @imagegif($resizedImage, $tempFile);
            break;
    }
    
    // Clean up memory
    imagedestroy($sourceImage);
    imagedestroy($resizedImage);
    
    if (!$success || !file_exists($tempFile)) {
        error_log('Could not save resized image, using original');
        if (file_exists($tempFile)) {
            @unlink($tempFile);
        }
        return $imagePath;
    }
    
    error_log('Successfully resized image to ' . filesize($tempFile) . ' bytes');
    return $tempFile;
}

function preprocessImageForQR($imagePath) {
    // Check if GD library is available
    if (!function_exists('getimagesize') || !function_exists('imagecreatefromjpeg')) {
        return false;
    }
    
    // Get image info
    $imageInfo = @getimagesize($imagePath);
    if ($imageInfo === false) {
        return false;
    }
    
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $mimeType = $imageInfo['mime'];
    
    // Load source image
    $sourceImage = null;
    switch ($mimeType) {
        case 'image/jpeg':
            $sourceImage = @imagecreatefromjpeg($imagePath);
            break;
        case 'image/png':
            $sourceImage = @imagecreatefrompng($imagePath);
            break;
        case 'image/gif':
            $sourceImage = @imagecreatefromgif($imagePath);
            break;
        default:
            return false;
    }
    
    if ($sourceImage === false) {
        return false;
    }
    
    // Create a new image for preprocessing
    // Resize to max 1200px if larger (QR codes need some resolution but not too much)
    // This reduces memory usage significantly while maintaining QR code readability
    $maxSize = 1200;
    $needsResize = ($width > $maxSize || $height > $maxSize);
    
    if ($needsResize) {
        $ratio = min($maxSize / $width, $maxSize / $height);
        $newWidth = (int)round($width * $ratio);
        $newHeight = (int)round($height * $ratio);
        
        $processedImage = @imagecreatetruecolor($newWidth, $newHeight);
        if ($processedImage === false) {
            imagedestroy($sourceImage);
            return false;
        }
        
        // Resize
        @imagecopyresampled($processedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($sourceImage);
        $sourceImage = $processedImage;
        $width = $newWidth;
        $height = $newHeight;
    } else {
        // Copy source image to processed image
        $processedImage = @imagecreatetruecolor($width, $height);
        if ($processedImage === false) {
            imagedestroy($sourceImage);
            return false;
        }
        @imagecopy($processedImage, $sourceImage, 0, 0, 0, 0, $width, $height);
        imagedestroy($sourceImage);
    }
    
    // Convert to grayscale using built-in filter (much faster than pixel-by-pixel)
    // This is often all that's needed for QR code detection
    @imagefilter($processedImage, IMG_FILTER_GRAYSCALE);
    
    // Only apply contrast if image is very large (helps with high-res images)
    // For smaller images, grayscale alone is often sufficient
    if ($width > 1000 || $height > 1000) {
        @imagefilter($processedImage, IMG_FILTER_CONTRAST, -5);
    }
    
    // Save preprocessed image
    $tempFile = tempnam(sys_get_temp_dir(), 'qr_preprocessed_');
    $success = @imagejpeg($processedImage, $tempFile, 90); // High quality JPEG
    imagedestroy($processedImage);
    
    if (!$success || !file_exists($tempFile)) {
        if (file_exists($tempFile)) {
            @unlink($tempFile);
        }
        return false;
    }
    
    error_log('Successfully preprocessed image: ' . filesize($tempFile) . ' bytes');
    return $tempFile;
}

function detectQRCodeFromImage($imagePath) {
    // Check if the image file exists and is readable
    if (!file_exists($imagePath) || !is_readable($imagePath)) {
        return false;
    }
    
    // Try original image first (sometimes works better)
    if (class_exists('Zxing\QrReader')) {
        try {
            error_log('Attempting QR detection with Zxing\QrReader on original image');
            $qrcode = new Zxing\QrReader($imagePath);
            $text = $qrcode->text();
            
            if ($text) {
                error_log('QR code detected successfully from original image: ' . substr($text, 0, 100));
                return $text;
            } else {
                error_log('QR detection on original image returned no text, trying preprocessed version');
            }
        } catch (Exception $e) {
            error_log('QR detection error on original image: ' . $e->getMessage());
        }
    }
    
    // Preprocess image to improve QR detection (convert to grayscale, optimize for QR scanning)
    $preprocessedPath = preprocessImageForQR($imagePath);
    if ($preprocessedPath === false) {
        error_log('Image preprocessing failed, already tried original');
        return false;
    }
    
    // Try to use the QR detection library if available
    if (class_exists('Zxing\QrReader')) {
        try {
            error_log('Attempting QR detection with Zxing\QrReader on preprocessed image');
            $qrcode = new Zxing\QrReader($preprocessedPath);
            $text = $qrcode->text();
            
            // Clean up preprocessed file if we created one
            if ($preprocessedPath !== $imagePath && file_exists($preprocessedPath)) {
                @unlink($preprocessedPath);
            }
            
            if ($text) {
                error_log('QR code detected successfully from preprocessed image: ' . substr($text, 0, 100));
                return $text;
            } else {
                error_log('QR detection on preprocessed image returned no text');
            }
        } catch (Exception $e) {
            error_log('QR detection error on preprocessed image: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            // Clean up preprocessed file if we created one
            if ($preprocessedPath !== $imagePath && file_exists($preprocessedPath)) {
                @unlink($preprocessedPath);
            }
        }
    }
    
    // Clean up preprocessed file if we created one
    if (isset($preprocessedPath) && $preprocessedPath !== $imagePath && file_exists($preprocessedPath)) {
        @unlink($preprocessedPath);
    }
    
    // Fallback: Try using exec with zbarimg if available (try both original and preprocessed)
    if (function_exists('exec')) {
        error_log('Trying zbarimg fallback on original image');
        $command = "zbarimg " . escapeshellarg($imagePath) . " 2>/dev/null";
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0 && !empty($output)) {
            // zbarimg outputs in format "QR-Code:data"
            foreach ($output as $line) {
                if (strpos($line, 'QR-Code:') === 0) {
                    error_log('QR code detected via zbarimg on original image');
                    return substr($line, 8); // Remove "QR-Code:" prefix
                }
            }
        }
        
        // Try preprocessed image if available
        if (isset($preprocessedPath) && $preprocessedPath !== $imagePath && file_exists($preprocessedPath)) {
            error_log('Trying zbarimg fallback on preprocessed image');
            $command = "zbarimg " . escapeshellarg($preprocessedPath) . " 2>/dev/null";
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && !empty($output)) {
                foreach ($output as $line) {
                    if (strpos($line, 'QR-Code:') === 0) {
                        error_log('QR code detected via zbarimg on preprocessed image');
                        @unlink($preprocessedPath);
                        return substr($line, 8);
                    }
                }
            }
        }
    }
    
    // Fallback: Try using exec with qrencode/qrdetect if available
    if (function_exists('exec')) {
        error_log('Trying qrdetect fallback on original image');
        $command = "qrdetect " . escapeshellarg($imagePath) . " 2>/dev/null";
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0 && !empty($output)) {
            error_log('QR code detected via qrdetect on original image');
            if (isset($preprocessedPath) && $preprocessedPath !== $imagePath && file_exists($preprocessedPath)) {
                @unlink($preprocessedPath);
            }
            return implode("\n", $output);
        }
        
        // Try preprocessed image if available
        if (isset($preprocessedPath) && $preprocessedPath !== $imagePath && file_exists($preprocessedPath)) {
            error_log('Trying qrdetect fallback on preprocessed image');
            $command = "qrdetect " . escapeshellarg($preprocessedPath) . " 2>/dev/null";
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && !empty($output)) {
                error_log('QR code detected via qrdetect on preprocessed image');
                @unlink($preprocessedPath);
                return implode("\n", $output);
            }
        }
    }
    
    // Clean up preprocessed file if still exists
    if (isset($preprocessedPath) && $preprocessedPath !== $imagePath && file_exists($preprocessedPath)) {
        @unlink($preprocessedPath);
    }
    
    error_log('All QR detection methods failed');
    // If no QR detection libraries are available, return false
    return false;
}

function isUrl($string) {
    // Check if the string looks like a URL
    $isValid = filter_var($string, FILTER_VALIDATE_URL) !== false;
    error_log('isUrl check for "' . $string . '": ' . ($isValid ? 'true' : 'false'));
    return $isValid;
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

function parseVCardData($vcardData) {
    $lines = explode("\n", $vcardData);
    $contactData = [
        'first_name' => '',
        'last_name' => '',
        'email_primary' => '',
        'work_phone' => '',
        'mobile_phone' => '',
        'organization_name' => '',
        'job_title' => '',
        'street_address' => '',
        'city' => '',
        'state' => '',
        'zip_code' => '',
        'country' => '',
        'website_url' => '',
        'notes' => ''
    ];
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Handle FN (Full Name)
        if (strpos($line, 'FN:') === 0) {
            $fullName = substr($line, 3);
            $nameParts = explode(' ', $fullName, 2);
            if (count($nameParts) >= 2) {
                $contactData['first_name'] = $nameParts[0];
                $contactData['last_name'] = $nameParts[1];
            } else {
                $contactData['first_name'] = $fullName;
            }
        }
        
        // Handle N (Name)
        elseif (strpos($line, 'N:') === 0) {
            $nameData = substr($line, 2);
            $nameParts = explode(';', $nameData);
            if (count($nameParts) >= 2) {
                $contactData['last_name'] = $nameParts[0];
                $contactData['first_name'] = $nameParts[1];
            }
        }
        
        // Handle EMAIL
        elseif (strpos($line, 'EMAIL') === 0) {
            $emailData = substr($line, strpos($line, ':') + 1);
            $contactData['email_primary'] = $emailData;
        }
        
        // Handle TEL (Telephone)
        elseif (strpos($line, 'TEL') === 0) {
            $telData = substr($line, strpos($line, ':') + 1);
            if (strpos($line, 'TYPE=CELL') !== false || strpos($line, 'TYPE=MOBILE') !== false) {
                $contactData['mobile_phone'] = $telData;
            } else {
                $contactData['work_phone'] = $telData;
            }
        }
        
        // Handle ORG (Organization)
        elseif (strpos($line, 'ORG:') === 0) {
            $contactData['organization_name'] = substr($line, 4);
        }
        
        // Handle TITLE
        elseif (strpos($line, 'TITLE:') === 0) {
            $contactData['job_title'] = substr($line, 6);
        }
        
        // Handle ADR (Address)
        elseif (strpos($line, 'ADR') === 0) {
            $addressData = substr($line, strpos($line, ':') + 1);
            $addressParts = explode(';', $addressData);
            if (count($addressParts) >= 6) {
                $contactData['street_address'] = $addressParts[2];
                $contactData['city'] = $addressParts[3];
                $contactData['state'] = $addressParts[4];
                $contactData['zip_code'] = $addressParts[5];
                $contactData['country'] = $addressParts[6];
            }
        }
        
        // Handle URL
        elseif (strpos($line, 'URL:') === 0) {
            $contactData['website_url'] = substr($line, 4);
        }
        
        // Handle NOTE
        elseif (strpos($line, 'NOTE:') === 0) {
            $contactData['notes'] = substr($line, 5);
        }
    }
    
    // Ensure we have at least a first name
    if (empty($contactData['first_name'])) {
        $contactData['first_name'] = 'QR';
        $contactData['last_name'] = 'Contact';
    }
    
    return $contactData;
}
?>
