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
            error_log("Change password - Missing required fields: " . json_encode($missing));
            error_log("Change password - Received data keys: " . json_encode(array_keys($this->data)));
            $this->error('Missing required fields', 400, ['missing_fields' => $missing]);
        }
        
        $currentPassword = $this->data['current_password'];
        $newPassword = $this->data['new_password'];
        
        error_log("Change password - User ID: " . $this->userId);
        error_log("Change password - Current password length: " . strlen($currentPassword));
        error_log("Change password - New password length: " . strlen($newPassword));
        
        // Validate new password strength
        $validation = PasswordValidator::validate($newPassword);
        if (!$validation['valid']) {
            error_log("Change password - Password validation failed: " . json_encode($validation['errors']));
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
                error_log("Change password - User not found for ID: " . $this->userId);
                $this->error('User not found', 404);
            }
            
            error_log("Change password - User found: " . $user['email']);
            error_log("Change password - User has password_hash: " . ($user['password_hash'] !== null ? 'YES' : 'NO'));
            
            if ($user['password_hash'] === null) {
                error_log("Change password - User has no password set");
                $this->error('No password set. Use set-password endpoint instead.', 400);
            }
            
            // Verify current password
            $passwordMatch = password_verify($currentPassword, $user['password_hash']);
            error_log("Change password - Password verification result: " . ($passwordMatch ? 'MATCH' : 'NO MATCH'));
            
            if (!$passwordMatch) {
                error_log("Change password - Current password is incorrect");
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
