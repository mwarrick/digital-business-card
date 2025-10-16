<?php
/**
 * Password Reset Complete API Endpoint
 * Completes password reset with code and new password
 */

require_once __DIR__ . '/../includes/Api.php';
require_once __DIR__ . '/../includes/PasswordValidator.php';

class ResetPasswordCompleteHandler extends Api {
    
    public function handle() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error('Method not allowed', 405);
        }
        
        $this->completePasswordReset();
    }
    
    private function completePasswordReset() {
        // Validate required fields
        $required = ['email', 'code', 'new_password'];
        $missing = $this->validateRequired($required, $this->data);
        
        if (!empty($missing)) {
            $this->error('Missing required fields', 400, ['missing_fields' => $missing]);
        }
        
        $email = trim($this->data['email']);
        $code = trim($this->data['code']);
        $newPassword = $this->data['new_password'];
        
        // Validate email format
        if (!$this->validateEmail($email)) {
            $this->error('Invalid email format', 400);
        }
        
        // Validate new password strength
        $validation = PasswordValidator::validate($newPassword);
        if (!$validation['valid']) {
            $this->error('New password does not meet requirements', 400, [
                'errors' => $validation['errors']
            ]);
        }
        
        try {
            // Get user
            $user = $this->db->querySingle(
                "SELECT id, email, password_hash FROM users WHERE email = ? AND is_active = 1",
                [$email]
            );
            
            if (!$user) {
                $this->error('User not found', 404);
            }
            
            if ($user['password_hash'] === null) {
                $this->error('No password set for this account. Please use email verification to login.', 400);
            }
            
            // Find valid reset code
            $verification = $this->db->querySingle(
                "SELECT id, type, used_at, expires_at,
                        (expires_at > NOW()) as is_valid
                 FROM verification_codes 
                 WHERE user_id = ? AND code = ? AND type = 'password_reset' AND used_at IS NULL
                 ORDER BY created_at DESC 
                 LIMIT 1",
                [$user['id'], $code]
            );
            
            if (!$verification) {
                $this->error('Invalid or expired reset code', 400);
            }
            
            if (!$verification['is_valid']) {
                $this->error('Reset code has expired. Please request a new one.', 400);
            }
            
            // Hash and set new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $this->db->beginTransaction();
            
            try {
                // Update password
                $this->db->execute(
                    "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?",
                    [$hashedPassword, $user['id']]
                );
                
                // Mark code as used
                $this->db->execute(
                    "UPDATE verification_codes SET used_at = NOW() WHERE id = ?",
                    [$verification['id']]
                );
                
                $this->db->commit();
                
                // Send confirmation email
                try {
                    require_once __DIR__ . '/../includes/EmailTemplates.php';
                    require_once __DIR__ . '/../includes/GmailClient.php';
                    
                    $emailData = EmailTemplates::passwordChanged($user['email']);
                    GmailClient::sendEmail(
                        $user['email'],
                        $emailData['subject'],
                        $emailData['html'],
                        $emailData['text']
                    );
                } catch (Exception $e) {
                    error_log("Failed to send password reset confirmation email: " . $e->getMessage());
                    // Don't fail the request if email fails
                }
                
                $this->success([
                    'message' => 'Password reset successfully',
                    'strength' => PasswordValidator::getStrength($newPassword),
                    'strength_description' => PasswordValidator::getStrengthDescription(
                        PasswordValidator::getStrength($newPassword)
                    )
                ]);
                
            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("Password reset complete error: " . $e->getMessage());
            $this->error('Failed to reset password', 500);
        }
    }
}

// Handle the request
$handler = new ResetPasswordCompleteHandler();
$handler->handle();
