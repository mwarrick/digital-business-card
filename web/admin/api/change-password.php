<?php
require_once __DIR__ . '/../includes/AdminAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../../api/includes/PasswordValidator.php';

header('Content-Type: application/json');

AdminAuth::requireAuth();
$userId = AdminAuth::getUserId();

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$current = $input['current_password'] ?? '';
$new = $input['new_password'] ?? '';

if (empty($current) || empty($new)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$validation = PasswordValidator::validate($new);
if (!$validation['valid']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'New password does not meet requirements', 'errors' => $validation['errors']]);
    exit;
}

try {
    $db = Database::getInstance();
    $user = $db->querySingle('SELECT id, email, password_hash FROM users WHERE id = ?', [$userId]);
    if (!$user || $user['password_hash'] === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No password set']);
        exit;
    }
    if (!password_verify($current, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit;
    }
    $hash = password_hash($new, PASSWORD_DEFAULT);
    $db->execute('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?', [$hash, $userId]);
    echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to change password']);
}


