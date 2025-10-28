<?php
// Simple QR processing endpoint for testing JSON input
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

error_log('=== SIMPLE QR PROCESSING REQUEST START ===');
error_log('Request Method: ' . $_SERVER['REQUEST_METHOD']);
error_log('Content Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'Not set'));

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('ERROR: Method not allowed - ' . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
error_log('Raw input length: ' . strlen($input));
error_log('Raw input preview: ' . substr($input, 0, 200));

$data = json_decode($input, true);
error_log('JSON decode result: ' . (json_last_error() === JSON_ERROR_NONE ? 'SUCCESS' : 'FAILED - ' . json_last_error_msg()));
error_log('Decoded data keys: ' . (is_array($data) ? implode(', ', array_keys($data)) : 'Not an array'));

// Check if image data was provided
if (!isset($data['image']) || empty($data['image'])) {
    error_log('ERROR: No image data provided in JSON');
    error_log('Available keys: ' . (is_array($data) ? implode(', ', array_keys($data)) : 'Data is not array'));
    http_response_code(400);
    echo json_encode(['error' => 'No image data provided', 'debug' => 'Available keys: ' . (is_array($data) ? implode(', ', array_keys($data)) : 'Data is not array')]);
    exit;
}

$base64Image = $data['image'];
error_log('Base64 image data length: ' . strlen($base64Image));
error_log('Base64 image preview: ' . substr($base64Image, 0, 100));

// For now, just return success with the data we received
echo json_encode([
    'success' => true,
    'message' => 'JSON input received successfully',
    'debug' => [
        'base64_length' => strlen($base64Image),
        'base64_preview' => substr($base64Image, 0, 50),
        'received_keys' => is_array($data) ? array_keys($data) : 'Not array'
    ]
]);

error_log('=== SIMPLE QR PROCESSING REQUEST END ===');
?>
