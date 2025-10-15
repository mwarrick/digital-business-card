<?php
/**
 * Admin Logout
 */

require_once __DIR__ . '/includes/AdminAuth.php';

AdminAuth::logout();
header('Location: /admin/login.php');
exit;

