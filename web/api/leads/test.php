<?php
/**
 * Test endpoint for leads API
 */

header('Content-Type: application/json');

try {
    echo json_encode([
        'success' => true,
        'message' => 'Leads API is working',
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => $_SERVER['REQUEST_METHOD'],
        'query' => $_GET
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
