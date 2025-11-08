<?php
/**
 * Resend Verification Code Endpoint
 * POST /api/auth/resend-verification
 * Resends verification code for inactive accounts
 */

require_once __DIR__ . '/../includes/Api.php';
require_once __DIR__ . '/../includes/Database.php';

class ResendVerificationApi extends Api {
    private $db;
    
    public function __construct() {
        parent::__construct();
        
        // Apply rate limiting: 5 resend attempts per hour
        $this->applyRateLimit(5, 3600, 'resend-verification');
        
        $this->db = Database::getInstance();
        $this->handleRequest();
    }
    
    private function handleRequest() {
        if ($this->method !== 'POST') {
            $this->error('Method not allowed', 405);
        }
        
        $this->resendVerification();
    }
    
    private function resendVerification() {
        // Validate required fields
        $required = ['email'];
        $missing = $this->validateRequired($required, $this->data);
        
        if (!empty($missing)) {
            $this->error('Missing required fields', 400, ['missing_fields' => $missing]);
        }
        
        // Validate email format
        $email = trim($this->data['email'] ?? '');
        error_log("Resend Verification API - Raw email from request: " . var_export($this->data['email'] ?? 'NOT SET', true));
        error_log("Resend Verification API - Trimmed email: " . var_export($email, true));
        
        if (!$this->validateEmail($email)) {
            error_log("Resend Verification API - Email validation failed for: " . $email);
            $this->error('Invalid email format', 400);
        }
        
        // Normalize email to lowercase for case-insensitive matching
        $emailLower = strtolower($email);
        error_log("Resend Verification API - Normalized email (lowercase): " . $emailLower);
        
        try {
            // Debug: Check what emails exist in database
            $allEmails = $this->db->query("SELECT email FROM users LIMIT 10");
            error_log("Resend Verification API - Sample emails in database: " . json_encode($allEmails));
            
            // Try exact match first (MySQL default collation is case-insensitive)
            error_log("Resend Verification API - Attempting exact match query with email: '$email'");
            $user = $this->db->querySingle(
                "SELECT id, email, is_active FROM users WHERE email = ?",
                [$email]
            );
            error_log("Resend Verification API - Exact match result: " . ($user ? "FOUND - email: " . $user['email'] : "NOT FOUND"));
            
            // If not found, try case-insensitive match with trimmed email
            if (!$user) {
                error_log("Resend Verification API - Attempting case-insensitive match query with normalized email: '$emailLower'");
                $user = $this->db->querySingle(
                    "SELECT id, email, is_active FROM users WHERE LOWER(TRIM(email)) = ?",
                    [$emailLower]
                );
                error_log("Resend Verification API - Case-insensitive match result: " . ($user ? "FOUND - email: " . $user['email'] : "NOT FOUND"));
            }
            
            if (!$user) {
                // Debug: Try to find similar emails
                $similarEmails = $this->db->query(
                    "SELECT email FROM users WHERE email LIKE ? LIMIT 5",
                    ['%' . str_replace('+', '%', $emailLower) . '%']
                );
                error_log("Resend verification: User not found for email: '$email' (normalized: '$emailLower'). Similar emails found: " . json_encode($similarEmails));
                
                // Also try searching for emails containing the domain
                $domainEmails = $this->db->query(
                    "SELECT email FROM users WHERE email LIKE ? LIMIT 5",
                    ['%@warrick.net']
                );
                error_log("Resend verification: Emails with domain warrick.net: " . json_encode($domainEmails));
                
                $this->error('User not found', 404);
            }
            
            // Only allow resending for inactive accounts
            if ($user['is_active']) {
                $this->error('Account is already active. Please login instead.', 400);
            }
            
            // Generate new verification code
            $verificationCode = sprintf('%06d', mt_rand(0, 999999));
            $codeId = $this->generateUUID();
            
            // Store verification code (expires in 10 minutes)
            $this->db->execute(
                "INSERT INTO verification_codes (id, user_id, code, type, expires_at, created_at) 
                 VALUES (?, ?, ?, 'register', DATE_ADD(NOW(), INTERVAL 10 MINUTE), NOW())",
                [$codeId, $user['id'], $verificationCode]
            );
            
            // Send verification email
            try {
                // Load required includes for email sending
                require_once __DIR__ . '/../includes/GmailClient.php';
                require_once __DIR__ . '/../includes/EmailTemplates.php';
                
                // Use email from database to ensure correct case
                $userEmail = $user['email'];
                $emailData = EmailTemplates::registrationVerification($verificationCode, $userEmail);
                GmailClient::sendEmail($userEmail, $emailData['subject'], $emailData['html'], $emailData['text']);
            } catch (Exception $e) {
                error_log("Failed to send verification email: " . $e->getMessage());
                $this->error('Failed to send verification code. Please try again.', 500);
            }
            
            $this->success([
                'user_id' => $user['id'],
                'email' => $user['email'], // Use email from database
                'message' => 'Verification code sent to your email. Please verify to complete registration.'
            ], 'Verification code resent successfully');
            
        } catch (Exception $e) {
            error_log("Resend verification error: " . $e->getMessage());
            $this->error('Failed to resend verification code: ' . $e->getMessage(), 500);
        }
    }
}

// Initialize API
new ResendVerificationApi();

