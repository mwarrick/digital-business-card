<?php
/**
 * Change Password API Endpoint
 * Allows users to change their existing password
 */

require_once __DIR__ . '/../includes/Api.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/PasswordValidator.php';

class ChangePasswordHandler extends Api {
    private $db;
    private $userId;
    
    public function __construct() {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->userId = $this->requireAuth();
    }
    
    public function handleRequest() {
        if ($this->method !== 'POST') {
            $this->error('Method not allowed', 405);
        }
        
        $this->changePassword();
    }
    
    private function changePassword() {
        // Validate required fields
        $required = ['current_password', 'new_password'];
        $missing = $this->validateRequired($required, $this->data);
        
        if (!empty($missing)) {
            $this->error('Missing required fields', 400, ['missing_fields' => $missing]);
        }
        
        $currentPassword = $this->data['current_password'];
        $newPassword = $this->data['new_password'];
        
        // Validate new password strength
        $validation = PasswordValidator::validate($newPassword);
        if (!$validation['valid']) {
            $this->error('New password does not meet requirements', 400, [
                'errors' => $validation['errors']
            ]);
        }
        
        try {
            // Get user and verify current password
            $user = $this->db->querySingle(
                "SELECT id, password_hash, email FROM users WHERE id = ?",
                [$this->userId]
            );
            
            if (!$user) {
                $this->error('User not found', 404);
            }
            
            if ($user['password_hash'] === null) {
                $this->error('No password set. Use set-password endpoint instead.', 400);
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password_hash'])) {
                $this->error('Current password is incorrect', 400);
            }
            
            // Hash and update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $this->db->execute(
                "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?",
                [$hashedPassword, $this->userId]
            );
            
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
                error_log("Failed to send password changed confirmation email: " . $e->getMessage());
                // Don't fail the request if email fails
            }
            
            $this->success([
                'message' => 'Password changed successfully',
                'strength' => PasswordValidator::getStrength($newPassword),
                'strength_description' => PasswordValidator::getStrengthDescription(
                    PasswordValidator::getStrength($newPassword)
                )
            ]);
            
        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            $this->error('Failed to change password', 500);
        }
    }
}

// Handle the request
$handler = new ChangePasswordHandler();
$handler->handleRequest();
