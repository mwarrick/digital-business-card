<?php
/**
 * Test Includes
 * Test if all required files can be included
 */

header('Content-Type: application/json');

try {
    error_log("TEST INCLUDES - Starting include test");
    
    require_once __DIR__ . '/../../config/Config.php';
    error_log("TEST INCLUDES - Config.php included");
    
    require_once __DIR__ . '/../../api/includes/Database.php';
    error_log("TEST INCLUDES - Database.php included");
    
    require_once __DIR__ . '/../../api/includes/UserAuth.php';
    error_log("TEST INCLUDES - UserAuth.php included");
    
    require_once __DIR__ . '/../../api/includes/GmailClient.php';
    error_log("TEST INCLUDES - GmailClient.php included");
    
    require_once __DIR__ . '/../../api/includes/EmailTemplates.php';
    error_log("TEST INCLUDES - EmailTemplates.php included");
    
    echo json_encode([
        'success' => true,
        'message' => 'All includes successful',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("TEST INCLUDES - Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
