<?php
/**
 * Router for PHP Built-in Server
 * This file routes requests to the appropriate API endpoints
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve static files directly (including CSS)
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    // For CSS files, set correct content type
    if (preg_match('/\.css$/', $uri)) {
        header('Content-Type: text/css');
    }
    return false;
}

// Route API requests
if (preg_match('/^\/api\/auth\/register/', $uri)) {
    require __DIR__ . '/api/auth/register.php';
    exit;
}

if (preg_match('/^\/api\/auth\/login/', $uri)) {
    require __DIR__ . '/api/auth/login.php';
    exit;
}

if (preg_match('/^\/api\/auth\/verify/', $uri)) {
    require __DIR__ . '/api/auth/verify.php';
    exit;
}

if (preg_match('/^\/api\/auth\/gmail-init/', $uri)) {
    require __DIR__ . '/api/auth/gmail-init.php';
    exit;
}

if (preg_match('/^\/api\/auth\/gmail-callback/', $uri)) {
    require __DIR__ . '/api/auth/gmail-callback.php';
    exit;
}

if (preg_match('/^\/api\/cards\/qrcode/', $uri)) {
    require __DIR__ . '/api/cards/qrcode.php';
    exit;
}

if (preg_match('/^\/api\/cards/', $uri)) {
    require __DIR__ . '/api/cards/index.php';
    exit;
}

if (preg_match('/^\/api\/media\/upload/', $uri)) {
    require __DIR__ . '/api/media/upload.php';
    exit;
}

if (preg_match('/^\/api\/media\/delete/', $uri)) {
    require __DIR__ . '/api/media/delete.php';
    exit;
}

if (preg_match('/^\/api\/media\/view/', $uri)) {
    require __DIR__ . '/api/media/view.php';
    exit;
}

if (preg_match('/^\/api\/test\/send-test-email/', $uri)) {
    require __DIR__ . '/api/test/send-test-email.php';
    exit;
}

// User routes
if (preg_match('/^\/user\/register/', $uri)) {
    require __DIR__ . '/user/register.php';
    exit;
}

if (preg_match('/^\/user\/login/', $uri)) {
    require __DIR__ . '/user/login.php';
    exit;
}

if (preg_match('/^\/user\/dashboard/', $uri)) {
    require __DIR__ . '/user/dashboard.php';
    exit;
}

if (preg_match('/^\/user\/logout/', $uri)) {
    require __DIR__ . '/user/logout.php';
    exit;
}

// User card management routes
if (preg_match('/^\/user\/cards\/create/', $uri)) {
    require __DIR__ . '/user/cards/create.php';
    exit;
}

if (preg_match('/^\/user\/cards\/edit/', $uri)) {
    require __DIR__ . '/user/cards/edit.php';
    exit;
}

if (preg_match('/^\/user\/cards\/view/', $uri)) {
    require __DIR__ . '/user/cards/view.php';
    exit;
}

if (preg_match('/^\/user\/cards\/qr/', $uri)) {
    require __DIR__ . '/user/cards/qr.php';
    exit;
}

// Admin routes
if (preg_match('/^\/admin\/login/', $uri)) {
    require __DIR__ . '/admin/login.php';
    exit;
}

if (preg_match('/^\/admin\/dashboard/', $uri)) {
    require __DIR__ . '/admin/dashboard.php';
    exit;
}

if (preg_match('/^\/admin\/users/', $uri)) {
    require __DIR__ . '/admin/users.php';
    exit;
}

if (preg_match('/^\/admin\/my-cards/', $uri)) {
    require __DIR__ . '/admin/my-cards.php';
    exit;
}

// Admin card management routes (for own cards)
if (preg_match('/^\/admin\/cards\/create/', $uri)) {
    require __DIR__ . '/admin/cards/create.php';
    exit;
}

if (preg_match('/^\/admin\/cards\/edit/', $uri)) {
    require __DIR__ . '/admin/cards/edit.php';
    exit;
}

if (preg_match('/^\/admin\/cards\/view/', $uri)) {
    require __DIR__ . '/admin/cards/view.php';
    exit;
}

if (preg_match('/^\/admin\/cards\/qr/', $uri)) {
    require __DIR__ . '/admin/cards/qr.php';
    exit;
}

// All business cards (admin overview)
if (preg_match('/^\/admin\/cards/', $uri)) {
    require __DIR__ . '/admin/cards.php';
    exit;
}

if (preg_match('/^\/admin\/logout/', $uri)) {
    require __DIR__ . '/admin/logout.php';
    exit;
}

if (preg_match('/^\/admin\/debug-log/', $uri)) {
    require __DIR__ . '/admin/debug-log.php';
    exit;
}

// Default routes
if ($uri === '/' || $uri === '/index.php') {
    require __DIR__ . '/index.php';
    exit;
}

// 404
http_response_code(404);
echo json_encode([
    'success' => false,
    'message' => 'Not Found',
    'path' => $uri
]);

