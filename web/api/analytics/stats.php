<?php
/**
 * Analytics Stats API Endpoint
 * GET /api/analytics/stats.php?card_id={id}&period={7|30|90|all}
 * 
 * Returns analytics data for a specific card
 */

header('Content-Type: application/json');

// Include dependencies
require_once __DIR__ . '/../includes/Database.php';

try {
    // Validate card_id parameter
    if (!isset($_GET['card_id'])) {
        throw new Exception('card_id parameter is required');
    }
    
    $cardId = $_GET['card_id'];
    $period = $_GET['period'] ?? '30';
    
    // Validate period
    $validPeriods = ['7', '30', '90', 'all'];
    if (!in_array($period, $validPeriods)) {
        throw new Exception('Invalid period. Must be: 7, 30, 90, or all');
    }
    
    // Initialize database
    $db = Database::getInstance()->getConnection();
    
    // Calculate date range
    $dateCondition = "1=1";
    if ($period !== 'all') {
        $dateCondition = "DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL {$period} DAY)";
    }
    
    // Get summary statistics
    $stmt = $db->prepare("
        SELECT 
            COUNT(CASE WHEN event_type = 'view' THEN 1 END) as total_views,
            COUNT(DISTINCT CASE WHEN event_type = 'view' THEN session_id END) as unique_views,
            COUNT(CASE WHEN event_type = 'click' THEN 1 END) as total_clicks,
            COUNT(CASE WHEN event_type = 'download' THEN 1 END) as total_downloads,
            COUNT(CASE WHEN event_type = 'qr_scan' THEN 1 END) as total_scans,
            COUNT(CASE WHEN event_type = 'email_open' THEN 1 END) as total_email_opens
        FROM analytics_events
        WHERE card_id = ? AND {$dateCondition}
    ");
    $stmt->execute([$cardId]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get time series data (daily breakdown)
    $stmt = $db->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(CASE WHEN event_type = 'view' THEN 1 END) as views,
            COUNT(DISTINCT CASE WHEN event_type = 'view' THEN session_id END) as unique_views,
            COUNT(CASE WHEN event_type = 'click' THEN 1 END) as clicks,
            COUNT(CASE WHEN event_type = 'download' THEN 1 END) as downloads,
            COUNT(CASE WHEN event_type = 'qr_scan' THEN 1 END) as scans,
            COUNT(CASE WHEN event_type = 'email_open' THEN 1 END) as email_opens
        FROM analytics_events
        WHERE card_id = ? AND {$dateCondition}
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$cardId]);
    $timeSeries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get top clicked links
    $stmt = $db->prepare("
        SELECT 
            event_target as url,
            COUNT(*) as clicks
        FROM analytics_events
        WHERE card_id = ? AND event_type = 'click' AND {$dateCondition}
        GROUP BY event_target
        ORDER BY clicks DESC
        LIMIT 10
    ");
    $stmt->execute([$cardId]);
    $topLinks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get geographic breakdown
    $stmt = $db->prepare("
        SELECT 
            country,
            COUNT(*) as count
        FROM analytics_events
        WHERE card_id = ? AND country IS NOT NULL AND {$dateCondition}
        GROUP BY country
        ORDER BY count DESC
        LIMIT 10
    ");
    $stmt->execute([$cardId]);
    $geoData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get city breakdown (top 10)
    $stmt = $db->prepare("
        SELECT 
            city,
            country,
            COUNT(*) as count
        FROM analytics_events
        WHERE card_id = ? AND city IS NOT NULL AND {$dateCondition}
        GROUP BY city, country
        ORDER BY count DESC
        LIMIT 10
    ");
    $stmt->execute([$cardId]);
    $cityData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get device breakdown
    $stmt = $db->prepare("
        SELECT 
            device_type,
            COUNT(*) as count
        FROM analytics_events
        WHERE card_id = ? AND device_type IS NOT NULL AND {$dateCondition}
        GROUP BY device_type
        ORDER BY count DESC
    ");
    $stmt->execute([$cardId]);
    $deviceData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get browser breakdown
    $stmt = $db->prepare("
        SELECT 
            browser,
            COUNT(*) as count
        FROM analytics_events
        WHERE card_id = ? AND browser IS NOT NULL AND {$dateCondition}
        GROUP BY browser
        ORDER BY count DESC
        LIMIT 10
    ");
    $stmt->execute([$cardId]);
    $browserData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get OS breakdown
    $stmt = $db->prepare("
        SELECT 
            os,
            COUNT(*) as count
        FROM analytics_events
        WHERE card_id = ? AND os IS NOT NULL AND {$dateCondition}
        GROUP BY os
        ORDER BY count DESC
    ");
    $stmt->execute([$cardId]);
    $osData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get top referrers
    $stmt = $db->prepare("
        SELECT 
            referrer,
            COUNT(*) as count
        FROM analytics_events
        WHERE card_id = ? AND referrer IS NOT NULL AND referrer != '' AND {$dateCondition}
        GROUP BY referrer
        ORDER BY count DESC
        LIMIT 10
    ");
    $stmt->execute([$cardId]);
    $referrerData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return complete analytics data
    echo json_encode([
        'success' => true,
        'data' => [
            'card_id' => $cardId,
            'period' => $period,
            'summary' => [
                'total_views' => (int)$summary['total_views'],
                'unique_views' => (int)$summary['unique_views'],
                'total_clicks' => (int)$summary['total_clicks'],
                'total_downloads' => (int)$summary['total_downloads'],
                'total_scans' => (int)$summary['total_scans'],
                'total_email_opens' => (int)$summary['total_email_opens']
            ],
            'time_series' => $timeSeries,
            'top_links' => $topLinks,
            'geography' => [
                'countries' => $geoData,
                'cities' => $cityData
            ],
            'devices' => [
                'types' => $deviceData,
                'browsers' => $browserData,
                'operating_systems' => $osData
            ],
            'referrers' => $referrerData
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

