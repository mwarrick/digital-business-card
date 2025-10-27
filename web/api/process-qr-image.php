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
    // For now, we'll use a simple approach with exec() to call a Python script
    // In production, you'd want to use a proper PHP QR code library
    
    // Create a temporary file for the image
    $tempImagePath = tempnam(sys_get_temp_dir(), 'qr_image_') . '.jpg';
    move_uploaded_file($imagePath, $tempImagePath);
    
    // Try to use Python with qrcode library (if available)
    $pythonScript = __DIR__ . '/qr_reader.py';
    $command = "python3 " . escapeshellarg($pythonScript) . " " . escapeshellarg($tempImagePath) . " 2>&1";
    
    $output = [];
    $returnCode = 0;
    exec($command, $output, $returnCode);
    
    // Clean up temp file
    unlink($tempImagePath);
    
    if ($returnCode === 0 && !empty($output)) {
        $qrData = implode("\n", $output);
        
        // Check if it's a vCard
        if (strpos($qrData, 'BEGIN:VCARD') === 0) {
            echo json_encode([
                'success' => true,
                'type' => 'vcard',
                'data' => $qrData,
                'message' => 'vCard QR code detected and processed successfully'
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'type' => 'text',
                'data' => $qrData,
                'message' => 'QR code detected but it\'s not a vCard format'
            ]);
        }
    } else {
        // Fallback: try using a simple PHP approach or return error
        echo json_encode([
            'success' => false,
            'error' => 'No QR code found in image',
            'debug' => implode("\n", $output)
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error processing image: ' . $e->getMessage()
    ]);
}
?>
