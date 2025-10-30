<?php
require_once __DIR__ . '/../../config/secure-config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $sql = file_get_contents(__DIR__ . '/../../config/migrations/032_alter_custom_qr_user_id.sql');
    $pdo->exec($sql);
    echo "Migration 032 applied.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error: ' . $e->getMessage();
}


