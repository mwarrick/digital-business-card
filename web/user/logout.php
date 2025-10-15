<?php
/**
 * User Logout
 */

require_once __DIR__ . '/includes/UserAuth.php';

UserAuth::logout();

header('Location: /');
exit;

