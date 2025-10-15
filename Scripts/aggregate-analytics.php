#!/usr/bin/env php
<?php
/**
 * Analytics Aggregation CRON Job
 * 
 * Run daily at 2 AM to aggregate old events into daily summaries
 * and clean up old data
 * 
 * Add to crontab:
 * 0 2 * * * /usr/local/bin/php /home/sharipbf/scripts/aggregate-analytics.php >> /home/sharipbf/logs/analytics-cron.log 2>&1
 */

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Determine the base directory (web root is in public_html)
$homeDir = getenv('HOME') ?: dirname(__DIR__);
$webRoot = $homeDir . '/public_html';

// Include dependencies with absolute paths
require_once $webRoot . '/api/includes/Database.php';
require_once $webRoot . '/api/includes/Analytics.php';

echo "Analytics Aggregation CRON Job\n";
echo "Started: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Initialize database and analytics
    $db = Database::getInstance()->getConnection();
    $analytics = new Analytics($db);
    
    // Aggregate events from 2 days ago
    $targetDate = date('Y-m-d', strtotime('-2 days'));
    echo "Aggregating events for: {$targetDate}\n";
    
    $cardsAggregated = $analytics->aggregateDailyStats($targetDate);
    echo "✓ Aggregated {$cardsAggregated} cards\n\n";
    
    // Clean old events (keep last 30 days)
    echo "Cleaning old events (older than 30 days)...\n";
    $eventsDeleted = $analytics->cleanOldEvents(30);
    echo "✓ Deleted {$eventsDeleted} old events\n\n";
    
    // Clean old sessions (older than 30 days)
    echo "Cleaning old sessions (older than 30 days)...\n";
    $sessionsDeleted = $analytics->cleanOldSessions(30);
    echo "✓ Deleted {$sessionsDeleted} old sessions\n\n";
    
    echo "Completed: " . date('Y-m-d H:i:s') . "\n";
    echo "Status: SUCCESS\n";
    
    exit(0);
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Completed: " . date('Y-m-d H:i:s') . "\n";
    echo "Status: FAILED\n";
    
    exit(1);
}

