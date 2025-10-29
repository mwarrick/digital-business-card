<?php
/**
 * Test Impersonation Script
 * This script helps debug impersonation issues
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Impersonation Test</h1>";

try {
    echo "<h2>1. Testing includes...</h2>";
    
    require_once __DIR__ . '/includes/AdminAuth.php';
    echo "✅ AdminAuth.php loaded<br>";
    
    require_once __DIR__ . '/../api/includes/Database.php';
    echo "✅ Database.php loaded<br>";
    
    require_once __DIR__ . '/../api/includes/DemoUserHelper.php';
    echo "✅ DemoUserHelper.php loaded<br>";
    
    echo "<h2>2. Testing database connection...</h2>";
    $db = Database::getInstance();
    echo "✅ Database connection successful<br>";
    
    echo "<h2>3. Testing user lookup...</h2>";
    $userId = '8dfab514-c925-47f3-8a28-340dfad759b2';
    $user = $db->querySingle("SELECT id, email, is_active, role FROM users WHERE id = ?", [$userId]);
    
    if ($user) {
        echo "✅ User found: " . $user['email'] . "<br>";
        echo "   - Active: " . ($user['is_active'] ? 'Yes' : 'No') . "<br>";
        echo "   - Role: " . $user['role'] . "<br>";
    } else {
        echo "❌ User not found<br>";
    }
    
    echo "<h2>4. Testing demo user check...</h2>";
    if ($user) {
        $isDemo = DemoUserHelper::isDemoUser($user['email']);
        echo "✅ Demo user check: " . ($isDemo ? 'Yes' : 'No') . "<br>";
    }
    
    echo "<h2>5. Testing session...</h2>";
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        echo "✅ Session started<br>";
    } else {
        echo "✅ Session already active<br>";
    }
    
    echo "<h2>6. All tests passed!</h2>";
    echo "<p>If you see this, the basic components are working. The issue might be in AdminAuth::requireAuth().</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
