<?php
/**
 * Test endpoint to check if an email exists in the database
 * GET /api/test/check-email?email=mark+20251107a@warrick.net
 */

require_once __DIR__ . '/../includes/Database.php';

header('Content-Type: application/json; charset=UTF-8');

$email = $_GET['email'] ?? '';

if (empty($email)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email parameter required']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Get all emails for debugging
    $allEmails = $db->query("SELECT email, is_active FROM users ORDER BY created_at DESC LIMIT 20");
    
    // Try exact match
    $exactMatch = $db->querySingle(
        "SELECT id, email, is_active FROM users WHERE email = ?",
        [$email]
    );
    
    // Try case-insensitive
    $caseInsensitive = $db->querySingle(
        "SELECT id, email, is_active FROM users WHERE LOWER(TRIM(email)) = ?",
        [strtolower(trim($email))]
    );
    
    // Try with LIKE
    $likeMatch = $db->query(
        "SELECT id, email, is_active FROM users WHERE email LIKE ? LIMIT 5",
        ['%' . $email . '%']
    );
    
    echo json_encode([
        'search_email' => $email,
        'normalized_email' => strtolower(trim($email)),
        'exact_match' => $exactMatch,
        'case_insensitive_match' => $caseInsensitive,
        'like_matches' => $likeMatch,
        'sample_emails' => $allEmails
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

