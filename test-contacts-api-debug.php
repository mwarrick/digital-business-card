<?php
// Test the contacts API with error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing contacts API...\n";

// Test the API endpoint with error reporting
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

echo "HTTP Code: $httpCode\n";
echo "Response: '$response'\n";
echo "Response length: " . strlen($response) . "\n";
echo "Error: $error\n";

// Test with a different approach - check if there's a server error
echo "\nTesting with error reporting...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer test',
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

echo "Full response with headers:\n";
echo $response;
?>
