<?php
/**
 * Image Creation Logger
 * Tracks when and where images are created in the system
 */

require_once __DIR__ . '/Database.php';

class ImageCreationLogger {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Log an image creation event
     */
    public function logImageCreation($imageName, $imagePath, $imageType, $creationMethod, $fileSize = null, $dimensions = null) {
        try {
            // Start session if not already started
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Get current user info
            $userId = $_SESSION['user_id'] ?? null;
            $userEmail = $_SESSION['user_email'] ?? 'unknown';
            
            // Get current page info
            $pageUrl = $this->getCurrentPageUrl();
            $urlParameters = $this->getUrlParameters();
            
            // Get request info
            $ipAddress = $this->getClientIp();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            // Insert log entry
            $this->db->execute("
                INSERT INTO image_creation_log (
                    user_id, user_email, page_url, url_parameters, 
                    image_name, image_path, image_type, creation_method,
                    file_size, image_dimensions, ip_address, user_agent
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [
                $userId,
                $userEmail,
                $pageUrl,
                $urlParameters,
                $imageName,
                $imagePath,
                $imageType,
                $creationMethod,
                $fileSize,
                $dimensions,
                $ipAddress,
                $userAgent
            ]);
            
            error_log("IMAGE CREATION LOGGED: $imageName ($imageType) by $userEmail on $pageUrl");
            
        } catch (Exception $e) {
            error_log("Failed to log image creation: " . $e->getMessage());
            error_log("Image creation log error details: " . print_r($e, true));
        }
    }
    
    /**
     * Get current page URL
     */
    private function getCurrentPageUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'unknown';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return $protocol . '://' . $host . $uri;
    }
    
    /**
     * Get URL parameters as JSON string
     */
    private function getUrlParameters() {
        $params = $_GET;
        return !empty($params) ? json_encode($params) : null;
    }
    
    /**
     * Get client IP address
     */
    private function getClientIp() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs (from proxies)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        return 'unknown';
    }
    
    /**
     * Get image creation history for admin dashboard
     */
    public function getImageCreationHistory($limit = 50) {
        try {
            $result = $this->db->query("
                SELECT 
                    icl.*,
                    u.email as user_email,
                    u.first_name,
                    u.last_name
                FROM image_creation_log icl
                LEFT JOIN users u ON icl.user_id = u.id
                ORDER BY icl.created_at DESC
                LIMIT ?
            ", [$limit]);
            
            return $result ?: [];
        } catch (Exception $e) {
            error_log("Failed to get image creation history: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get statistics for admin dashboard
     */
    public function getImageCreationStats() {
        try {
            $stats = [];
            
            // Total images created
            $total = $this->db->querySingle("SELECT COUNT(*) as count FROM image_creation_log");
            $stats['total_images'] = $total ? $total['count'] : 0;
            
            // Images by type
            $byType = $this->db->query("
                SELECT image_type, COUNT(*) as count 
                FROM image_creation_log 
                GROUP BY image_type 
                ORDER BY count DESC
            ");
            $stats['by_type'] = [];
            if ($byType) {
                foreach ($byType as $row) {
                    $stats['by_type'][$row['image_type']] = $row['count'];
                }
            }
            
            // Images by method
            $byMethod = $this->db->query("
                SELECT creation_method, COUNT(*) as count 
                FROM image_creation_log 
                GROUP BY creation_method 
                ORDER BY count DESC
            ");
            $stats['by_method'] = [];
            if ($byMethod) {
                foreach ($byMethod as $row) {
                    $stats['by_method'][$row['creation_method']] = $row['count'];
                }
            }
            
            // Recent activity (last 24 hours)
            $recent = $this->db->querySingle("
                SELECT COUNT(*) as count 
                FROM image_creation_log 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stats['recent_24h'] = $recent ? $recent['count'] : 0;
            
            return $stats;
        } catch (Exception $e) {
            error_log("Failed to get image creation stats: " . $e->getMessage());
            return [
                'total_images' => 0,
                'recent_24h' => 0,
                'by_type' => [],
                'by_method' => []
            ];
        }
    }
}
?>
