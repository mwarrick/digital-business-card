<?php
/**
 * Password Reset Request API Endpoint
 * Sends a password reset code to user's email
 */

require_once __DIR__ . '/../includes/Api.php';

class ResetPasswordRequestHandler extends Api {
    
    public function handle() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error('Method not allowed', 405);
        }
        
        $this->requestPasswordReset();
    }
    
    private function requestPasswordReset() {
        // Validate required fields
        $required = ['email'];
        $missing = $this->validateRequired($required, $this->data);
        
        if (!empty($missing)) {
            $this->error('Missing required fields', 400, ['missing_fields' => $missing]);
        }
        
        $email = trim($this->data['email']);
        
        // Validate email format
        if (!$this->validateEmail($email)) {
            $this->error('Invalid email format', 400);
        }
        
        try {
            // Check if user exists and has a password
            $user = $this->db->querySingle(
                "SELECT id, email, password_hash FROM users WHERE email = ? AND is_active = 1",
                [$email]
            );
            
            if (!$user) {
                // Don't reveal if user exists or not
                $this->success(['message' => 'If an account with that email exists, a password reset code has been sent.']);
                return;
            }
            
            if ($user['password_hash'] === null) {
                // User doesn't have a password set
                $this->error('No password set for this account. Please use email verification to login.', 400);
            }
            
            // Generate reset code
            $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $verificationId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            
            // Store reset code (expires in 30 minutes)
            $this->db->execute(
                "INSERT INTO verification_codes (id, user_id, code, type, expires_at, created_at) 
                 VALUES (?, ?, ?, 'password_reset', DATE_ADD(NOW(), INTERVAL 30 MINUTE), NOW())",
                [$verificationId, $user['id'], $code]
            );
            
            // Send reset email
            try {
                require_once __DIR__ . '/../includes/EmailTemplates.php';
                require_once __DIR__ . '/../includes/GmailClient.php';
                
                $emailData = EmailTemplates::passwordReset($code, $email);
                GmailClient::sendEmail(
                    $email,
                    $emailData['subject'],
                    $emailData['html'],
                    $emailData['text']
                );
                
                $this->success(['message' => 'Password reset code sent to your email.']);
                
            } catch (Exception $e) {
                error_log("Failed to send password reset email: " . $e->getMessage());
                $this->error('Failed to send reset code. Please try again.', 500);
            }
            
        } catch (Exception $e) {
            error_log("Password reset request error: " . $e->getMessage());
            $this->error('Failed to process password reset request', 500);
        }
    }
}

// Handle the request
$handler = new ResetPasswordRequestHandler();
$handler->handle();
