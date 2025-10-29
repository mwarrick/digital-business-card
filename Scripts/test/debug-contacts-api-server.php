<?php
// Debug the contacts API server-side issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUGGING CONTACTS API SERVER-SIDE ISSUE ===\n\n";

// Test 1: Check if the contacts API file exists and is accessible
echo "1. Testing contacts API file access:\n";
$url = "https://sharemycard.app/api/contacts/";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer test',
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

echo "   HTTP Code: $httpCode\n";
echo "   Response: '$response'\n";
echo "   Response length: " . strlen($response) . "\n";
echo "   Error: $error\n\n";

// Test 2: Check if there's a server error by testing with a different endpoint
echo "2. Testing cards API for comparison:\n";
$url = "https://sharemycard.app/api/cards/";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer test',
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

echo "   HTTP Code: $httpCode\n";
echo "   Response: '$response'\n";
echo "   Response length: " . strlen($response) . "\n\n";

// Test 3: Check if there's a database issue
echo "3. Testing database connectivity:\n";
$url = "https://sharemycard.app/api/contacts/";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer test',
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

echo "   Full response with headers:\n";
echo $response . "\n\n";

echo "=== ANALYSIS ===\n";
echo "If contacts API returns 200 but empty:\n";
echo "- Server-side PHP error (check error logs)\n";
echo "- Database connection issue\n";
echo "- Authentication issue\n";
echo "- Missing table or column\n";
echo "\nIf cards API works but contacts doesn't:\n";
echo "- Contacts API file issue\n";
echo "- Database table missing\n";
echo "- Configuration issue\n";
?>
