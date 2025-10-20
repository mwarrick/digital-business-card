<?php
/**
 * Email Tracking Migration Runner
 * Run database migrations for email tracking functionality
 */

require_once __DIR__ . '/includes/AdminAuth.php';
require_once __DIR__ . '/../api/includes/Database.php';

// Require admin authentication
AdminAuth::requireAuth();

$db = Database::getInstance();

echo "<h1>Running Email Tracking Migrations</h1>";

try {
    // Migration 1: Add email_open to event_type ENUM
    echo "<h2>Migration 1: Update analytics_events table</h2>";
    
    $result = $db->querySingle("SHOW COLUMNS FROM analytics_events LIKE 'event_type'");
    if ($result && strpos($result['Type'], 'email_open') !== false) {
        echo "<p style='color: orange;'>⚠️ Migration already applied - email_open is in event_type ENUM</p>";
    } else {
        echo "<p>Adding email_open to event_type ENUM...</p>";
        $db->execute("ALTER TABLE analytics_events MODIFY COLUMN event_type ENUM('view', 'click', 'download', 'email_open') NOT NULL");
        echo "<p style='color: green;'>✅ Successfully updated analytics_events table</p>";
    }
    
    // Migration 2: Add total_email_opens to analytics_daily table
    echo "<h2>Migration 2: Update analytics_daily table</h2>";
    
    $result = $db->querySingle("SHOW COLUMNS FROM analytics_daily LIKE 'total_email_opens'");
    if ($result) {
        echo "<p style='color: orange;'>⚠️ Migration already applied - total_email_opens column exists</p>";
    } else {
        echo "<p>Adding total_email_opens column...</p>";
        $db->execute("ALTER TABLE analytics_daily ADD COLUMN total_email_opens INT DEFAULT 0 AFTER total_downloads");
        echo "<p style='color: green;'>✅ Successfully added total_email_opens column</p>";
    }
    
    // Test the migrations
    echo "<h2>Testing Migrations</h2>";
    
    // Test 1: Check if we can insert an email_open event
    $testCardId = '550e8400-e29b-41d4-a716-446655440001';
    $testEventId = uniqid('test_', true);
    
    try {
        $db->execute("
            INSERT INTO analytics_events (
                id, card_id, event_type, event_target, session_id,
                ip_address, user_agent, device_type, browser, os,
                country, city, referrer
            ) VALUES (?, ?, 'email_open', NULL, 'test_session', '127.0.0.1', 'Test Agent', 'desktop', 'test', 'test', 'US', 'Test City', '')
        ", [$testEventId, $testCardId]);
        
        echo "<p style='color: green;'>✅ Successfully inserted test email_open event</p>";
        
        // Clean up test data
        $db->execute("DELETE FROM analytics_events WHERE id = ?", [$testEventId]);
        echo "<p style='color: blue;'>🧹 Cleaned up test data</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Failed to insert test email_open event: " . $e->getMessage() . "</p>";
    }
    
    // Test 2: Check if we can query email_open events
    $count = $db->querySingle("SELECT COUNT(*) as count FROM analytics_events WHERE event_type = 'email_open'");
    echo "<p style='color: green;'>✅ Successfully queried email_open events (count: " . $count['count'] . ")</p>";
    
    echo "<p style='color: green; font-weight: bold; font-size: 18px;'>🎉 All email tracking migrations completed successfully!</p>";
    echo "<p>The email tracking pixel should now work correctly.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Migration failed: " . $e->getMessage() . "</p>";
    echo "<p>Error details: " . $e->getTraceAsString() . "</p>";
}
?>
