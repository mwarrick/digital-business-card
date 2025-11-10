<?php
require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';

header('Content-Type: application/json');

UserAuth::requireAuth();
$userId = UserAuth::getUserId();

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$password = $input['password'] ?? '';

if (empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password is required']);
    exit;
}

try {
    $db = Database::getInstance();
    $user = $db->querySingle('SELECT id, email, password_hash FROM users WHERE id = ?', [$userId]);
    if (!$user) {
        throw new Exception('User not found');
    }
    if ($user['password_hash'] !== null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Password already set.']);
        exit;
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $db->execute('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?', [$hash, $userId]);
    echo json_encode(['success' => true, 'message' => 'Password set successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to set password']);
}


