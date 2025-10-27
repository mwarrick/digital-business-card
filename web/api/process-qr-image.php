<?php
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
    // For now, we'll return a placeholder response
    // In a real implementation, you'd use a PHP QR code library like ZXing or similar
    
    // Create a temporary file for the image
    $tempImagePath = tempnam(sys_get_temp_dir(), 'qr_image_') . '.jpg';
    move_uploaded_file($imagePath, $tempImagePath);
    
    // For testing, let's return a sample vCard response
    $sampleVCard = "BEGIN:VCARD
VERSION:3.0
FN:Test Contact
N:Contact;Test;;;
ORG:Test Company
TEL:+1-555-123-4567
EMAIL:test@example.com
END:VCARD";
    
    // Clean up temp file
    unlink($tempImagePath);
    
    // Return success response with sample data
    echo json_encode([
        'success' => true,
        'type' => 'vcard',
        'data' => $sampleVCard,
        'message' => 'QR code processed successfully (sample data)',
        'debug' => 'Using sample vCard data for testing'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error processing image: ' . $e->getMessage()
    ]);
}
?>
