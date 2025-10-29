<?php
// Simple test to check what's on the server
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

echo json_encode([
    'success' => true,
    'message' => 'QR endpoint test - server is responding',
    'timestamp' => date('Y-m-d H:i:s'),
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'Not set',
    'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 'Not set',
    'has_files' => isset($_FILES['image']),
    'has_json_input' => !empty(file_get_contents('php://input'))
]);
?>
