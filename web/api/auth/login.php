<?php
/**
 * User Login Endpoint
 * POST /api/auth/login
 */

require_once __DIR__ . '/../includes/Api.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/GmailClient.php';
require_once __DIR__ . '/../includes/EmailTemplates.php';

class LoginApi extends Api {
    private $db;
    
    public function __construct() {
        parent::__construct();
        
        // Apply rate limiting: 50 login attempts per hour (increased for testing)
        $this->applyRateLimit(50, 3600, 'login');
        
        $this->db = Database::getInstance();
        $this->handleRequest();
    }
    
    private function handleRequest() {
        if ($this->method !== 'POST') {
            $this->error('Method not allowed', 405);
        }
        
        $this->login();
    }
    
    private function login() {
        // Validate required fields
        $required = ['email'];
        $missing = $this->validateRequired($required, $this->data);
        
        if (!empty($missing)) {
            $this->error('Missing required fields', 400, ['missing_fields' => $missing]);
        }
        
        // Validate email format
        $email = trim($this->data['email']);
        if (!$this->validateEmail($email)) {
            $this->error('Invalid email format', 400);
        }
        
        try {
            // Find user by email
            $user = $this->db->querySingle(
                "SELECT id, email, is_active, is_admin, password_hash FROM users WHERE email = ?",
                [$email]
            );
            
            if (!$user) {
                $this->error('User not found', 404);
            }
            
            if (!$user['is_active']) {
                $this->error('Account is not active. Please complete registration or contact support.', 403);
            }
            
        $hasPassword = $user['password_hash'] !== null;
        $forceEmailCode = isset($this->data['force_email_code']) && $this->data['force_email_code'] === true;
        
        // If user has password and not forcing email code, return has_password flag without sending code
        if ($hasPassword && !$forceEmailCode) {
            $this->success([
                'user_id' => $user['id'],
                'email' => $user['email'],
                'is_admin' => (bool)$user['is_admin'],
                'has_password' => true,
                'verification_code_sent' => false,
                'message' => 'User has password set. Provide password or request email code.'
            ], 'User found');
            return;
        }
            
            // User doesn't have password - send verification code
            $verificationCode = sprintf('%06d', mt_rand(0, 999999));
            $codeId = $this->generateUUID();
            
            // Store verification code (expires in 10 minutes)
            $this->db->execute(
                "INSERT INTO verification_codes (id, user_id, code, type, expires_at, created_at) 
                 VALUES (?, ?, ?, 'login', DATE_ADD(NOW(), INTERVAL 10 MINUTE), NOW())",
                [$codeId, $user['id'], $verificationCode]
            );
            
            // Send login verification email
            try {
                // Detect if request is from mobile app
                $isApp = isset($_SERVER['HTTP_X_APP_PLATFORM']) && $_SERVER['HTTP_X_APP_PLATFORM'] === 'ios-app';
                
                $emailData = EmailTemplates::loginVerification($verificationCode, $email, false, $isApp);
                GmailClient::sendEmail($email, $emailData['subject'], $emailData['html'], $emailData['text']);
            } catch (Exception $e) {
                error_log("Failed to send login verification email: " . $e->getMessage());
                $this->error('Failed to send verification code. Please try again.', 500);
            }
            
            $this->success([
                'user_id' => $user['id'],
                'email' => $user['email'],
                'is_admin' => (bool)$user['is_admin'],
                'has_password' => false,
                'verification_code_sent' => true,
                'message' => 'Verification code sent to your email. Please enter it to complete login.'
            ], 'Verification code sent');
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $this->error('Login failed', 500);
        }
    }
}

// Initialize API
new LoginApi();

