<?php
/**
 * Check Password Status API Endpoint
 * Returns whether the current user has a password set
 */

require_once __DIR__ . '/../includes/Api.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/JWT.php';

class CheckPasswordStatusApi extends Api {
    private $db;
    private $userId;
    
    public function __construct() {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->userId = $this->requireAuth();
    }
    
    public function handleRequest() {
        // Only allow GET requests
        if ($this->method !== 'GET') {
            $this->error('Method not allowed', 405);
        }
        
        try {
            // Get user from database using authenticated user ID
            $user = $this->db->querySingle(
                "SELECT id, email, password_hash FROM users WHERE id = ?",
                [$this->userId]
            );
            
            if (!$user) {
                $this->error('User not found', 404);
            }
            
            // Check if user has password set
            $hasPassword = $user['password_hash'] !== null;
            
            $this->success([
                'has_password' => $hasPassword,
                'user_id' => $user['id'],
                'email' => $user['email']
            ], 'Password status retrieved');
            
        } catch (Exception $e) {
            $this->error('Failed to check password status: ' . $e->getMessage(), 500);
        }
    }
}

// Handle the request
$api = new CheckPasswordStatusApi();
$api->handleRequest();
?>
