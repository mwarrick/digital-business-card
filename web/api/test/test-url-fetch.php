<?php
// Test URL fetching functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$testUrl = 'https://sharemycard.app/vcard.php?id=f3ace1c9-3909-45f8-b5f0-8ad3f99ae6af&src=qr';

function isUrl($string) {
    $isValid = filter_var($string, FILTER_VALIDATE_URL) !== false;
    error_log('isUrl check for "' . $string . '": ' . ($isValid ? 'true' : 'false'));
    return $isValid;
}

function fetchVCardFromUrl($url) {
    error_log('Attempting to fetch vCard from URL: ' . $url);
    
    // Fetch vCard data from URL
    $context = stream_context_create([
        'http' => [
            'timeout' => 10, // 10 second timeout
            'user_agent' => 'QRCard Scanner/1.0',
            'follow_location' => true, // Follow redirects
            'max_redirects' => 5
        ]
    ]);
    
    try {
        $content = file_get_contents($url, false, $context);
        
        if ($content === false) {
            error_log('Failed to fetch URL: ' . $url);
            return false;
        }
        
        error_log('Fetched content length: ' . strlen($content));
        error_log('Content preview: ' . substr($content, 0, 200));
        
        // Check if the content looks like a vCard
        if (strpos($content, 'BEGIN:VCARD') === 0) {
            error_log('Successfully fetched vCard from URL: ' . $url);
            return $content;
        } else {
            error_log('URL content is not a vCard. Content type: ' . gettype($content));
            error_log('First 200 chars: ' . substr($content, 0, 200));
            return false;
        }
        
    } catch (Exception $e) {
        error_log('Error fetching URL ' . $url . ': ' . $e->getMessage());
        return false;
    }
}

// Test the functions
$result = [
    'test_url' => $testUrl,
    'is_url_valid' => isUrl($testUrl),
    'fetch_result' => null,
    'vcard_data' => null
];

if (isUrl($testUrl)) {
    $vcardData = fetchVCardFromUrl($testUrl);
    $result['fetch_result'] = $vcardData !== false ? 'success' : 'failed';
    $result['vcard_data'] = $vcardData;
}

echo json_encode($result, JSON_PRETTY_PRINT);
?>
