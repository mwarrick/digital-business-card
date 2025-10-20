<?php
/**
 * Analytics Helper Class
 * Handles event tracking, session management, and data aggregation
 */

class Analytics {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Main tracking method - logs an analytics event
     */
    public function trackEvent($cardId, $eventType, $eventTarget = null, $requestData = null) {
        // Debug: Log the tracking attempt
        error_log("Analytics::trackEvent - Card ID: $cardId, Event Type: $eventType");
        
        // Check for Do Not Track header
        if (isset($requestData['HTTP_DNT']) && $requestData['HTTP_DNT'] == '1') {
            return ['success' => true, 'tracked' => false, 'reason' => 'DNT enabled'];
        }
        
        // Get or create session
        $ipAddress = $this->getClientIp($requestData);
        $userAgent = $requestData['HTTP_USER_AGENT'] ?? '';
        $referrer = $requestData['HTTP_REFERER'] ?? '';
        
        $sessionId = $this->getOrCreateSession($cardId, $ipAddress, $userAgent);
        
        // Parse user agent for device info
        $deviceInfo = $this->parseUserAgent($userAgent);
        
        // Get geographic location from IP
        $geoInfo = $this->getGeolocation($ipAddress);
        
        // Create event ID
        $eventId = $this->generateUUID();
        
        // Insert event
        try {
            $stmt = $this->db->prepare("
                INSERT INTO analytics_events (
                    id, card_id, event_type, event_target, session_id,
                    ip_address, user_agent, device_type, browser, os,
                    country, city, referrer
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $eventId,
                $cardId,
                $eventType,
                $eventTarget,
                $sessionId,
                $ipAddress,
                $userAgent,
                $deviceInfo['device_type'],
                $deviceInfo['browser'],
                $deviceInfo['os'],
                $geoInfo['country'] ?? null,
                $geoInfo['city'] ?? null,
                $referrer
            ]);
            
            error_log("Analytics::trackEvent - Successfully inserted event for card $cardId, type $eventType");
            
        } catch (Exception $e) {
            error_log("Analytics::trackEvent - Database error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
        
        return [
            'success' => true,
            'tracked' => true,
            'event_id' => $eventId,
            'session_id' => $sessionId
        ];
    }
    
    /**
     * Get or create session for tracking unique visitors
     */
    public function getOrCreateSession($cardId, $ipAddress, $userAgent) {
        // Check for existing session cookie
        $sessionId = $_COOKIE['analytics_session'] ?? null;
        
        if ($sessionId) {
            // Update last_seen timestamp
            $stmt = $this->db->prepare("
                UPDATE analytics_sessions 
                SET last_seen = NOW() 
                WHERE session_id = ? AND card_id = ?
            ");
            $stmt->execute([$sessionId, $cardId]);
            
            // Check if update was successful
            if ($stmt->rowCount() > 0) {
                return $sessionId;
            }
        }
        
        // Create new session
        $sessionId = bin2hex(random_bytes(32));
        
        $stmt = $this->db->prepare("
            INSERT INTO analytics_sessions (session_id, card_id, ip_address, user_agent)
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([$sessionId, $cardId, $ipAddress, $userAgent]);
        
        // Set cookie for 30 days
        setcookie('analytics_session', $sessionId, time() + (30 * 24 * 60 * 60), '/');
        
        return $sessionId;
    }
    
    /**
     * Parse user agent to extract device, browser, and OS
     */
    public function parseUserAgent($userAgent) {
        $result = [
            'device_type' => 'desktop',
            'browser' => 'Unknown',
            'os' => 'Unknown'
        ];
        
        // Detect device type
        if (preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile/i', $userAgent)) {
            if (preg_match('/iPad|Tablet/i', $userAgent)) {
                $result['device_type'] = 'tablet';
            } else {
                $result['device_type'] = 'mobile';
            }
        }
        
        // Detect browser
        if (preg_match('/Chrome/i', $userAgent) && !preg_match('/Edg/i', $userAgent)) {
            $result['browser'] = 'Chrome';
        } elseif (preg_match('/Safari/i', $userAgent) && !preg_match('/Chrome/i', $userAgent)) {
            $result['browser'] = 'Safari';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $result['browser'] = 'Firefox';
        } elseif (preg_match('/Edg/i', $userAgent)) {
            $result['browser'] = 'Edge';
        } elseif (preg_match('/MSIE|Trident/i', $userAgent)) {
            $result['browser'] = 'Internet Explorer';
        } elseif (preg_match('/Opera|OPR/i', $userAgent)) {
            $result['browser'] = 'Opera';
        }
        
        // Detect OS
        if (preg_match('/Windows/i', $userAgent)) {
            $result['os'] = 'Windows';
        } elseif (preg_match('/Mac OS X|Macintosh/i', $userAgent)) {
            $result['os'] = 'macOS';
        } elseif (preg_match('/iPhone|iPad|iPod/i', $userAgent)) {
            $result['os'] = 'iOS';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $result['os'] = 'Android';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $result['os'] = 'Linux';
        }
        
        return $result;
    }
    
    /**
     * Get geographic location from IP address using ip-api.com (free tier)
     */
    public function getGeolocation($ipAddress) {
        // Skip for localhost/private IPs
        if ($this->isPrivateIp($ipAddress)) {
            return ['country' => 'Local', 'city' => 'Local'];
        }
        
        try {
            $url = "http://ip-api.com/json/{$ipAddress}?fields=country,city,status";
            $context = stream_context_create([
                'http' => [
                    'timeout' => 2, // 2 second timeout
                    'ignore_errors' => true
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            
            if ($response) {
                $data = json_decode($response, true);
                
                if ($data && $data['status'] === 'success') {
                    return [
                        'country' => $data['country'] ?? null,
                        'city' => $data['city'] ?? null
                    ];
                }
            }
        } catch (Exception $e) {
            // Silently fail - geo data is not critical
        }
        
        return ['country' => null, 'city' => null];
    }
    
    /**
     * Check if IP is private/local
     */
    private function isPrivateIp($ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }
        return false;
    }
    
    /**
     * Get client IP address (handles proxies)
     */
    private function getClientIp($requestData) {
        $ipAddress = '';
        
        if (isset($requestData['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = explode(',', $requestData['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (isset($requestData['HTTP_CLIENT_IP'])) {
            $ipAddress = $requestData['HTTP_CLIENT_IP'];
        } elseif (isset($requestData['REMOTE_ADDR'])) {
            $ipAddress = $requestData['REMOTE_ADDR'];
        }
        
        return trim($ipAddress);
    }
    
    /**
     * Generate UUID v4
     */
    private function generateUUID() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    
    /**
     * Aggregate events into daily statistics
     * Should be run daily via CRON job
     */
    public function aggregateDailyStats($date = null) {
        if (!$date) {
            // Default to 2 days ago to ensure all events are captured
            $date = date('Y-m-d', strtotime('-2 days'));
        }
        
        // Get unique cards that have events on this date
        $stmt = $this->db->prepare("
            SELECT DISTINCT card_id 
            FROM analytics_events 
            WHERE DATE(created_at) = ?
        ");
        $stmt->execute([$date]);
        $cardIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($cardIds as $cardId) {
            // Count total views
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM analytics_events 
                WHERE card_id = ? AND DATE(created_at) = ? AND event_type = 'view'
            ");
            $stmt->execute([$cardId, $date]);
            $totalViews = $stmt->fetchColumn();
            
            // Count unique views (unique sessions)
            $stmt = $this->db->prepare("
                SELECT COUNT(DISTINCT session_id) FROM analytics_events 
                WHERE card_id = ? AND DATE(created_at) = ? AND event_type = 'view'
            ");
            $stmt->execute([$cardId, $date]);
            $uniqueViews = $stmt->fetchColumn();
            
            // Count total clicks
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM analytics_events 
                WHERE card_id = ? AND DATE(created_at) = ? AND event_type = 'click'
            ");
            $stmt->execute([$cardId, $date]);
            $totalClicks = $stmt->fetchColumn();
            
            // Count total downloads
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM analytics_events 
                WHERE card_id = ? AND DATE(created_at) = ? AND event_type = 'download'
            ");
            $stmt->execute([$cardId, $date]);
            $totalDownloads = $stmt->fetchColumn();
            
            // Count total email opens
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM analytics_events 
                WHERE card_id = ? AND DATE(created_at) = ? AND event_type = 'email_open'
            ");
            $stmt->execute([$cardId, $date]);
            $totalEmailOpens = $stmt->fetchColumn();
            
            // Get top referrer
            $stmt = $this->db->prepare("
                SELECT referrer, COUNT(*) as count 
                FROM analytics_events 
                WHERE card_id = ? AND DATE(created_at) = ? AND referrer IS NOT NULL AND referrer != ''
                GROUP BY referrer 
                ORDER BY count DESC 
                LIMIT 1
            ");
            $stmt->execute([$cardId, $date]);
            $topReferrer = $stmt->fetchColumn();
            
            // Get top country
            $stmt = $this->db->prepare("
                SELECT country, COUNT(*) as count 
                FROM analytics_events 
                WHERE card_id = ? AND DATE(created_at) = ? AND country IS NOT NULL
                GROUP BY country 
                ORDER BY count DESC 
                LIMIT 1
            ");
            $stmt->execute([$cardId, $date]);
            $topCountry = $stmt->fetchColumn();
            
            // Insert or update daily stats
            $dailyId = $this->generateUUID();
            $stmt = $this->db->prepare("
                INSERT INTO analytics_daily 
                    (id, card_id, date, total_views, unique_views, total_clicks, total_downloads, total_email_opens, top_referrer, top_country)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    total_views = VALUES(total_views),
                    unique_views = VALUES(unique_views),
                    total_clicks = VALUES(total_clicks),
                    total_downloads = VALUES(total_downloads),
                    total_email_opens = VALUES(total_email_opens),
                    top_referrer = VALUES(top_referrer),
                    top_country = VALUES(top_country),
                    updated_at = NOW()
            ");
            
            $stmt->execute([
                $dailyId,
                $cardId,
                $date,
                $totalViews,
                $uniqueViews,
                $totalClicks,
                $totalDownloads,
                $totalEmailOpens,
                $topReferrer,
                $topCountry
            ]);
        }
        
        return count($cardIds);
    }
    
    /**
     * Delete events older than specified days
     * Should be run daily via CRON job
     */
    public function cleanOldEvents($daysToKeep = 30) {
        $stmt = $this->db->prepare("
            DELETE FROM analytics_events 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$daysToKeep]);
        
        return $stmt->rowCount();
    }
    
    /**
     * Clean old sessions (older than 30 days)
     */
    public function cleanOldSessions($daysToKeep = 30) {
        $stmt = $this->db->prepare("
            DELETE FROM analytics_sessions 
            WHERE last_seen < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$daysToKeep]);
        
        return $stmt->rowCount();
    }
}

