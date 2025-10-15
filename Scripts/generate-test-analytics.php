#!/usr/bin/env php
<?php
/**
 * Generate Test Analytics Data
 * Creates realistic test traffic for existing business cards
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Use same path structure as CRON script
$homeDir = getenv('HOME') ?: dirname(__DIR__);
$webRoot = $homeDir . '/public_html';

require_once $webRoot . '/api/includes/Database.php';
require_once $webRoot . '/api/includes/Analytics.php';

echo "🎯 Analytics Test Data Generator\n";
echo "================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    $analytics = new Analytics($db);
    
    // Get active business cards
    $stmt = $db->prepare("SELECT id, first_name, last_name FROM business_cards WHERE is_active = 1 LIMIT 5");
    $stmt->execute();
    $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($cards)) {
        echo "❌ No active business cards found\n";
        exit(1);
    }
    
    echo "Found " . count($cards) . " card(s):\n";
    foreach ($cards as $card) {
        echo "  - {$card['first_name']} {$card['last_name']} (ID: {$card['id']})\n";
    }
    echo "\n";
    
    // Generate data for the last 14 days
    $daysToGenerate = 14;
    $totalEvents = 0;
    
    // Sample data
    $browsers = ['Chrome', 'Safari', 'Firefox', 'Edge', 'Opera'];
    $devices = ['desktop', 'mobile', 'tablet'];
    $oses = ['Windows', 'macOS', 'iOS', 'Android', 'Linux'];
    $countries = ['United States', 'Canada', 'United Kingdom', 'Australia', 'Germany', 'France', 'Japan', 'Brazil'];
    $cities = ['New York', 'Los Angeles', 'London', 'Toronto', 'Sydney', 'Tokyo', 'Paris', 'Berlin'];
    $referrers = ['https://google.com', 'https://facebook.com', 'https://twitter.com', 'https://linkedin.com', ''];
    $urls = [
        'mailto:user@example.com',
        'tel:+15551234567',
        'https://example.com',
        'https://linkedin.com/in/profile',
        'https://twitter.com/handle'
    ];
    
    echo "Generating test data for last {$daysToGenerate} days...\n\n";
    
    foreach ($cards as $card) {
        $cardId = $card['id'];
        $cardName = "{$card['first_name']} {$card['last_name']}";
        
        echo "📊 Generating data for: {$cardName}\n";
        
        for ($day = $daysToGenerate; $day >= 0; $day--) {
            $date = date('Y-m-d', strtotime("-{$day} days"));
            $timestamp = strtotime($date . ' ' . rand(8, 20) . ':' . rand(0, 59) . ':' . rand(0, 59));
            
            // Generate 5-20 views per day
            $viewsPerDay = rand(5, 20);
            $clicksPerDay = rand(2, 8);
            $downloadsPerDay = rand(0, 3);
            
            // Generate view events
            for ($i = 0; $i < $viewsPerDay; $i++) {
                $eventId = sprintf('%s-%s-%s-%s-%s',
                    bin2hex(random_bytes(4)),
                    bin2hex(random_bytes(2)),
                    bin2hex(random_bytes(2)),
                    bin2hex(random_bytes(2)),
                    bin2hex(random_bytes(6))
                );
                
                $sessionId = bin2hex(random_bytes(32));
                $ipAddress = rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255);
                $userAgent = $browsers[array_rand($browsers)] . ' ' . rand(90, 120);
                $device = $devices[array_rand($devices)];
                $browser = $browsers[array_rand($browsers)];
                $os = $oses[array_rand($oses)];
                $country = $countries[array_rand($countries)];
                $city = $cities[array_rand($cities)];
                $referrer = $referrers[array_rand($referrers)];
                
                $eventTime = date('Y-m-d H:i:s', $timestamp + ($i * 3600));
                
                $stmt = $db->prepare("
                    INSERT INTO analytics_events 
                    (id, card_id, event_type, event_target, session_id, ip_address, user_agent, 
                     device_type, browser, os, country, city, referrer, created_at)
                    VALUES (?, ?, 'view', NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $eventId, $cardId, $sessionId, $ipAddress, $userAgent,
                    $device, $browser, $os, $country, $city, $referrer, $eventTime
                ]);
                
                $totalEvents++;
            }
            
            // Generate click events
            for ($i = 0; $i < $clicksPerDay; $i++) {
                $eventId = sprintf('%s-%s-%s-%s-%s',
                    bin2hex(random_bytes(4)),
                    bin2hex(random_bytes(2)),
                    bin2hex(random_bytes(2)),
                    bin2hex(random_bytes(2)),
                    bin2hex(random_bytes(6))
                );
                
                $sessionId = bin2hex(random_bytes(32));
                $ipAddress = rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255);
                $userAgent = $browsers[array_rand($browsers)] . ' ' . rand(90, 120);
                $device = $devices[array_rand($devices)];
                $browser = $browsers[array_rand($browsers)];
                $os = $oses[array_rand($oses)];
                $country = $countries[array_rand($countries)];
                $city = $cities[array_rand($cities)];
                $referrer = $referrers[array_rand($referrers)];
                $targetUrl = $urls[array_rand($urls)];
                
                $eventTime = date('Y-m-d H:i:s', $timestamp + ($i * 3600) + 1800);
                
                $stmt = $db->prepare("
                    INSERT INTO analytics_events 
                    (id, card_id, event_type, event_target, session_id, ip_address, user_agent, 
                     device_type, browser, os, country, city, referrer, created_at)
                    VALUES (?, ?, 'click', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $eventId, $cardId, $targetUrl, $sessionId, $ipAddress, $userAgent,
                    $device, $browser, $os, $country, $city, $referrer, $eventTime
                ]);
                
                $totalEvents++;
            }
            
            // Generate download events
            for ($i = 0; $i < $downloadsPerDay; $i++) {
                $eventId = sprintf('%s-%s-%s-%s-%s',
                    bin2hex(random_bytes(4)),
                    bin2hex(random_bytes(2)),
                    bin2hex(random_bytes(2)),
                    bin2hex(random_bytes(2)),
                    bin2hex(random_bytes(6))
                );
                
                $sessionId = bin2hex(random_bytes(32));
                $ipAddress = rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255);
                $userAgent = $browsers[array_rand($browsers)] . ' ' . rand(90, 120);
                $device = $devices[array_rand($devices)];
                $browser = $browsers[array_rand($browsers)];
                $os = $oses[array_rand($oses)];
                $country = $countries[array_rand($countries)];
                $city = $cities[array_rand($cities)];
                $referrer = $referrers[array_rand($referrers)];
                
                $eventTime = date('Y-m-d H:i:s', $timestamp + ($i * 3600) + 3600);
                
                $stmt = $db->prepare("
                    INSERT INTO analytics_events 
                    (id, card_id, event_type, event_target, session_id, ip_address, user_agent, 
                     device_type, browser, os, country, city, referrer, created_at)
                    VALUES (?, ?, 'download', 'vcard', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $eventId, $cardId, $sessionId, $ipAddress, $userAgent,
                    $device, $browser, $os, $country, $city, $referrer, $eventTime
                ]);
                
                $totalEvents++;
            }
        }
        
        echo "  ✓ Generated events for {$cardName}\n";
    }
    
    echo "\n";
    echo "✅ SUCCESS!\n";
    echo "Generated {$totalEvents} test events across " . count($cards) . " card(s)\n";
    echo "Timeframe: Last {$daysToGenerate} days\n\n";
    echo "🎯 Now view analytics at:\n";
    echo "   Admin: https://sharemycard.app/admin/cards/analytics.php\n";
    echo "   User:  https://sharemycard.app/user/cards/analytics.php\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

