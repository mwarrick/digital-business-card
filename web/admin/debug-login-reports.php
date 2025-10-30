<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Debug Login Reports...<br>";

try {
    require_once __DIR__ . '/../api/includes/Database.php';
    require_once __DIR__ . '/../api/includes/LoginTracker.php';
    $db = Database::getInstance();
    
    // Test the LoginTracker directly
    echo "<h3>Testing LoginTracker::getLoginStats()</h3>";
    $loginTracker = new LoginTracker();
    $filters = [
        'date_range' => 'all_time',
        'user_id' => '',
        'email' => '',
        'browser' => '',
        'device' => ''
    ];
    
    $logins = $loginTracker->getLoginStats($filters);
    echo "<p>Number of logins returned: " . count($logins) . "</p>";
    
    echo "<h3>Raw login data:</h3>";
    foreach ($logins as $login) {
        echo "<pre>" . print_r($login, true) . "</pre>";
    }
    
    // Test the statistics calculation
    echo "<h3>Statistics calculation:</h3>";
    $userIds = array_column($logins, 'user_id');
    $uniqueUserIds = array_unique(array_filter($userIds));
    
    $stats = [
        'total_logins' => count($logins),
        'unique_users' => count($uniqueUserIds),
        'mobile_logins' => count(array_filter($logins, function($login) { return isset($login['device_type']) && $login['device_type'] === 'Mobile'; })),
        'desktop_logins' => count(array_filter($logins, function($login) { return isset($login['device_type']) && $login['device_type'] === 'Desktop'; })),
        'tablet_logins' => count(array_filter($logins, function($login) { return isset($login['device_type']) && $login['device_type'] === 'Tablet'; })),
        'chrome_logins' => count(array_filter($logins, function($login) { return isset($login['browser_name']) && $login['browser_name'] === 'Chrome'; })),
        'firefox_logins' => count(array_filter($logins, function($login) { return isset($login['browser_name']) && $login['browser_name'] === 'Firefox'; })),
        'safari_logins' => count(array_filter($logins, function($login) { return isset($login['browser_name']) && $login['browser_name'] === 'Safari'; }))
    ];
    
    echo "<pre>" . print_r($stats, true) . "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}
?>




