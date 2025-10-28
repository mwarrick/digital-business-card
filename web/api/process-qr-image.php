<?php
// Enable error reporting and logging (but suppress deprecation warnings from output)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable display_errors to prevent HTML output
ini_set('log_errors', 1);

// Include Composer autoloader if available
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

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
    // Use the uploaded file directly
    $tempImagePath = $imagePath;
    error_log('Using uploaded file: ' . $tempImagePath);
    
    // Try to detect QR code using PHP GD and basic image processing
    error_log('Starting QR code detection...');
    $qrData = detectQRCodeFromImage($tempImagePath);
    error_log('QR detection result: ' . ($qrData ? 'SUCCESS - ' . substr($qrData, 0, 100) : 'FAILED'));
    
    // Clean up temp file
    unlink($tempImagePath);
    
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
        echo json_encode([
            'success' => false,
            'error' => 'No QR code found in image',
            'debug' => 'QR detection failed - image may not contain a readable QR code'
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
