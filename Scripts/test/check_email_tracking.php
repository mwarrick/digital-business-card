<?php
require_once 'web/config/database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

echo "Checking email tracking data...\n";

// Check if email_open events exist
$stmt = $pdo->query("SELECT COUNT(*) as count FROM analytics_events WHERE event_type = 'email_open'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Total email_open events: " . $result['count'] . "\n";

// Check recent events for the test card
$stmt = $pdo->prepare("SELECT * FROM analytics_events WHERE card_id = ? AND event_type = 'email_open' ORDER BY created_at DESC LIMIT 5");
$stmt->execute(['550e8400-e29b-41d4-a716-446655440001']);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Recent email_open events for test card:\n";
foreach ($events as $event) {
    echo "- " . $event['created_at'] . " - " . $event['event_type'] . "\n";
}

// Check if the event_type column supports email_open
$stmt = $pdo->query("SHOW COLUMNS FROM analytics_events LIKE 'event_type'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Current event_type column definition: " . $result['Type'] . "\n";

echo "Check complete!\n";
?>
