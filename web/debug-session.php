<?php
/**
 * Debug Session Status
 */

session_start();

echo "<h1>Session Debug</h1>";
echo "<h2>Session ID: " . session_id() . "</h2>";
echo "<h2>Session Status: " . session_status() . "</h2>";
echo "<h2>Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Cookies:</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

echo "<h2>Headers:</h2>";
echo "<pre>";
print_r(getallheaders());
echo "</pre>";

// Test UserAuth
require_once __DIR__ . '/user/includes/UserAuth.php';

echo "<h2>UserAuth Status:</h2>";
echo "<p>isLoggedIn(): " . (UserAuth::isLoggedIn() ? 'true' : 'false') . "</p>";
echo "<p>getUserId(): " . (UserAuth::getUserId() ?? 'null') . "</p>";
echo "<p>getEmail(): " . (UserAuth::getEmail() ?? 'null') . "</p>";
?>
