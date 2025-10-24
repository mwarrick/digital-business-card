<?php
/**
 * Login Tracker
 * Tracks user login activity including browser info and IP addresses
 */

require_once __DIR__ . '/Database.php';

class LoginTracker {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Track a user login
     */
    public function trackLogin($userId, $email, $isSuccessful = true, $failureReason = null) {
        try {
            $browserInfo = $this->getBrowserInfo();
            $ipInfo = $this->getIpInfo();
            
            $this->db->execute(
                "INSERT INTO user_logins (
                    user_id, email, ip_address, user_agent, browser_name, 
                    browser_version, operating_system, device_type, 
                    screen_resolution, country, city, session_id, 
                    is_successful, failure_reason
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $userId,
                    $email,
                    $ipInfo['ip'],
                    $browserInfo['user_agent'],
                    $browserInfo['browser_name'],
                    $browserInfo['browser_version'],
                    $browserInfo['os'],
                    $browserInfo['device_type'],
                    $browserInfo['screen_resolution'],
                    $ipInfo['country'],
                    $ipInfo['city'],
                    session_id(),
                    $isSuccessful ? 1 : 0,
                    $failureReason
                ]
            );
            
            error_log("Login tracked for user $userId ($email) from IP {$ipInfo['ip']} using {$browserInfo['browser_name']}");
            
        } catch (Exception $e) {
            error_log("Failed to track login: " . $e->getMessage());
        }
    }
    
    /**
     * Get detailed browser information
     */
    private function getBrowserInfo() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        return [
            'user_agent' => $userAgent,
            'browser_name' => $this->detectBrowser($userAgent),
            'browser_version' => $this->getBrowserVersion($userAgent),
            'os' => $this->detectOperatingSystem($userAgent),
            'device_type' => $this->detectDeviceType($userAgent),
            'screen_resolution' => $this->getScreenResolution()
        ];
    }
    
    /**
     * Detect browser name from user agent
     */
    private function detectBrowser($userAgent) {
        if (strpos($userAgent, 'Chrome') !== false && strpos($userAgent, 'Edg') === false) {
            return 'Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false && strpos($userAgent, 'Chrome') === false) {
            return 'Safari';
        } elseif (strpos($userAgent, 'Edg') !== false) {
            return 'Edge';
        } elseif (strpos($userAgent, 'Opera') !== false || strpos($userAgent, 'OPR') !== false) {
            return 'Opera';
        } elseif (strpos($userAgent, 'SamsungBrowser') !== false) {
            return 'Samsung Internet';
        } else {
            return 'Unknown';
        }
    }
    
    /**
     * Get browser version
     */
    private function getBrowserVersion($userAgent) {
        // Chrome version
        if (preg_match('/Chrome\/([0-9\.]+)/', $userAgent, $matches)) {
            return $matches[1];
        }
        // Firefox version
        if (preg_match('/Firefox\/([0-9\.]+)/', $userAgent, $matches)) {
            return $matches[1];
        }
        // Safari version
        if (preg_match('/Version\/([0-9\.]+).*Safari/', $userAgent, $matches)) {
            return $matches[1];
        }
        // Edge version
        if (preg_match('/Edg\/([0-9\.]+)/', $userAgent, $matches)) {
            return $matches[1];
        }
        
        return 'Unknown';
    }
    
    /**
     * Detect operating system
     */
    private function detectOperatingSystem($userAgent) {
        if (strpos($userAgent, 'Windows NT 10.0') !== false) {
            return 'Windows 10';
        } elseif (strpos($userAgent, 'Windows NT 6.3') !== false) {
            return 'Windows 8.1';
        } elseif (strpos($userAgent, 'Windows NT 6.1') !== false) {
            return 'Windows 7';
        } elseif (strpos($userAgent, 'Mac OS X') !== false) {
            return 'macOS';
        } elseif (strpos($userAgent, 'Linux') !== false) {
            return 'Linux';
        } elseif (strpos($userAgent, 'Android') !== false) {
            return 'Android';
        } elseif (strpos($userAgent, 'iPhone') !== false) {
            return 'iOS';
        } elseif (strpos($userAgent, 'iPad') !== false) {
            return 'iPadOS';
        } else {
            return 'Unknown';
        }
    }
    
    /**
     * Detect device type
     */
    private function detectDeviceType($userAgent) {
        if (strpos($userAgent, 'Mobile') !== false || strpos($userAgent, 'Android') !== false || strpos($userAgent, 'iPhone') !== false) {
            return 'Mobile';
        } elseif (strpos($userAgent, 'Tablet') !== false || strpos($userAgent, 'iPad') !== false) {
            return 'Tablet';
        } else {
            return 'Desktop';
        }
    }
    
    /**
     * Get screen resolution (requires JavaScript)
     */
    private function getScreenResolution() {
        // This would need to be passed from JavaScript
        return 'Unknown';
    }
    
    /**
     * Get IP address and location info
     */
    private function getIpInfo() {
        $ip = $this->getRealIpAddress();
        
        return [
            'ip' => $ip,
            'country' => $this->getCountryFromIp($ip),
            'city' => $this->getCityFromIp($ip)
        ];
    }
    
    /**
     * Get real IP address (handles proxies)
     */
    private function getRealIpAddress() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
    
    /**
     * Get country from IP (simplified - in production, use a proper IP geolocation service)
     */
    private function getCountryFromIp($ip) {
        // For now, return Unknown - in production you'd use a service like ipapi.co or similar
        return 'Unknown';
    }
    
    /**
     * Get city from IP (simplified - in production, use a proper IP geolocation service)
     */
    private function getCityFromIp($ip) {
        // For now, return Unknown - in production you'd use a service like ipapi.co or similar
        return 'Unknown';
    }
    
    /**
     * Get login statistics for admin
     */
    public function getLoginStats($filters = []) {
        $whereClause = "WHERE 1=1";
        $params = [];
        
        // Date filtering
        if (!empty($filters['date_range'])) {
            switch ($filters['date_range']) {
                case 'today':
                    $whereClause .= " AND DATE(login_timestamp) = CURDATE()";
                    break;
                case 'last_7_days':
                    $whereClause .= " AND login_timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'last_2_weeks':
                    $whereClause .= " AND login_timestamp >= DATE_SUB(NOW(), INTERVAL 14 DAY)";
                    break;
                case 'last_30_days':
                    $whereClause .= " AND login_timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
                case 'last_3_months':
                    $whereClause .= " AND login_timestamp >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
                    break;
                case 'last_6_months':
                    $whereClause .= " AND login_timestamp >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
                    break;
            }
        }
        
        // User filtering
        if (!empty($filters['user_id'])) {
            $whereClause .= " AND user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        // Email filtering
        if (!empty($filters['email'])) {
            $whereClause .= " AND email LIKE ?";
            $params[] = '%' . $filters['email'] . '%';
        }
        
        // Browser filtering
        if (!empty($filters['browser'])) {
            $whereClause .= " AND browser_name = ?";
            $params[] = $filters['browser'];
        }
        
        // Device filtering
        if (!empty($filters['device'])) {
            $whereClause .= " AND device_type = ?";
            $params[] = $filters['device'];
        }
        
        $sql = "SELECT 
                    ul.*,
                    u.email as user_email,
                    u.first_name,
                    u.last_name,
                    u.is_active,
                    u.is_admin,
                    u.created_at as user_created_at
                FROM user_logins ul
                LEFT JOIN users u ON ul.user_id = u.id
                $whereClause
                ORDER BY ul.login_timestamp DESC
                LIMIT 1000";
        
        return $this->db->query($sql, $params);
    }
}
