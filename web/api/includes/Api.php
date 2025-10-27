<?php
/**
 * Base API Class
 * Handles HTTP requests, responses, and common API functionality
 */

class Api {
    protected $method;
    protected $endpoint;
    protected $params = [];
    protected $data = [];
    
    public function __construct() {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Content-Type: application/json; charset=UTF-8");
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
        
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->parseRequest();
    }
    
    /**
     * Parse incoming request
     */
    protected function parseRequest() {
        // Get request body for POST/PUT
        if (in_array($this->method, ['POST', 'PUT', 'PATCH'])) {
            $input = file_get_contents('php://input');
            $this->data = json_decode($input, true) ?? [];
        }
        
        // Get URL parameters
        $this->params = $_GET;
    }
    
    /**
     * Send JSON response
     */
    protected function respond($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit();
    }
    
    /**
     * Send success response
     */
    protected function success($data = [], $message = 'Success', $statusCode = 200) {
        $this->respond([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }
    
    /**
     * Send error response
     */
    protected function error($message, $statusCode = 400, $errors = []) {
        $this->respond([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }
    
    /**
     * Validate required fields
     */
    protected function validateRequired($fields, $data) {
        $missing = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        return $missing;
    }
    
    /**
     * Validate email format
     */
    protected function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    /**
     * Validate UUID format
     */
    protected function validateUUID($uuid) {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
    }
    
    /**
     * Generate UUID v4
     */
    protected function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    /**
     * Get authorization token from headers
     */
    protected function getAuthToken() {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $auth = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
    
    /**
     * Apply rate limiting
     * 
     * @param int $maxRequests - Maximum requests allowed
     * @param int $windowSeconds - Time window in seconds
     * @param string $endpoint - Endpoint name for tracking
     */
    protected function applyRateLimit($maxRequests = 1000, $windowSeconds = 3600, $endpoint = 'global') {
        require_once __DIR__ . '/RateLimiter.php';
        require_once __DIR__ . '/JWT.php';
        
        // Get identifier (prefer user_id from token, fallback to IP)
        $identifier = null;
        $token = $this->getAuthToken();
        if ($token) {
            $payload = JWT::decode($token);
            if ($payload && isset($payload['user_id'])) {
                $identifier = $payload['user_id'];
            }
        }
        
        if (!$identifier) {
            $identifier = RateLimiter::getIdentifier();
        } else {
            $identifier = 'user:' . $identifier;
        }
        
        RateLimiter::middleware($identifier, $maxRequests, $windowSeconds, $endpoint);
    }
    
    /**
     * Verify user authentication with JWT
     */
    protected function requireAuth() {
        require_once __DIR__ . '/JWT.php';
        
        $token = $this->getAuthToken();
        if (!$token) {
            $this->error('Authentication required', 401);
        }
        
        // Decode and verify JWT token
        $payload = JWT::decode($token);
        
        if (!$payload) {
            $this->error('Invalid or expired authentication token', 401);
        }
        
        // Return user_id from token
        return $payload['user_id'];
    }
    
    /**
     * Get full auth payload (including email, is_admin, etc.)
     */
    protected function getAuthPayload() {
        require_once __DIR__ . '/JWT.php';
        
        $token = $this->getAuthToken();
        if (!$token) {
            return null;
        }
        
        return JWT::decode($token);
    }
}

