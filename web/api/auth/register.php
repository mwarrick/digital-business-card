<?php
/**
 * User Registration Endpoint
 * POST /api/auth/register
 */

require_once __DIR__ . '/../includes/Api.php';
require_once __DIR__ . '/../includes/Database.php';

class RegisterApi extends Api {
    private $db;
    
    public function __construct() {
        parent::__construct();
        
        // Apply rate limiting: 5 registration attempts per hour
        $this->applyRateLimit(5, 3600, 'register');
        
        $this->db = Database::getInstance();
        $this->handleRequest();
    }
    
    private function handleRequest() {
        if ($this->method !== 'POST') {
            $this->error('Method not allowed', 405);
        }
        
        $this->register();
    }
    
    private function register() {
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
            // Check if email already exists and get account status
            $existing = $this->db->querySingle(
                "SELECT id, is_active, password_hash FROM users WHERE email = ?",
                [$email]
            );
            
            if ($existing) {
                // Account exists - check if it's verified
                if ($existing['is_active']) {
                    // Account is verified - user should login instead
                    $hasPassword = !empty($existing['password_hash']);
                    $this->error('Email already registered. Please sign in instead.', 409, [
                        'account_status' => 'verified',
                        'has_password' => $hasPassword
                    ]);
                } else {
                    // Account exists but not verified - allow resend verification
                    $this->error('Email already registered but not verified. Please verify your account.', 409, [
                        'account_status' => 'unverified'
                    ]);
                }
            }
            
            // Generate user ID and verification code
            $userId = $this->generateUUID();
            $verificationCode = sprintf('%06d', mt_rand(0, 999999));
            $codeId = $this->generateUUID();
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Insert user (inactive until verified)
            $this->db->execute(
                "INSERT INTO users (id, email, is_active, created_at, updated_at) 
                 VALUES (?, ?, 0, NOW(), NOW())",
                [$userId, $email]
            );
            
            // Store verification code (expires in 10 minutes)
            $this->db->execute(
                "INSERT INTO verification_codes (id, user_id, code, type, expires_at, created_at) 
                 VALUES (?, ?, ?, 'register', DATE_ADD(NOW(), INTERVAL 10 MINUTE), NOW())",
                [$codeId, $userId, $verificationCode]
            );
            
            $this->db->commit();
            
            // Send verification email
            try {
                // Load required includes for email sending
                require_once __DIR__ . '/../includes/GmailClient.php';
                require_once __DIR__ . '/../includes/EmailTemplates.php';
                
                $emailData = EmailTemplates::registrationVerification($verificationCode, $email);
                GmailClient::sendEmail($email, $emailData['subject'], $emailData['html'], $emailData['text']);
            } catch (Exception $e) {
                error_log("Failed to send verification email: " . $e->getMessage());
                // Continue anyway - user can request another code
            }
            
            $this->success([
                'user_id' => $userId,
                'email' => $email,
                'message' => 'Verification code sent to your email. Please verify to complete registration.'
            ], 'Registration initiated. Check your email for verification code.', 201);
            
        } catch (Exception $e) {
            if ($this->db->getConnection()->inTransaction()) {
                $this->db->rollback();
            }
            error_log("Registration error: " . $e->getMessage());
            $this->error('Registration failed: ' . $e->getMessage(), 500);
        }
    }
}

// Initialize API
new RegisterApi();

