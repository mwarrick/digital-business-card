<?php
/**
 * Set Password API Endpoint
 * Allows users to set a password for the first time
 */

require_once __DIR__ . '/../includes/Api.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/PasswordValidator.php';

class SetPasswordHandler extends Api {
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
        
        $this->setPassword();
    }
    
    private function setPassword() {
        // Validate required fields
        $required = ['password'];
        $missing = $this->validateRequired($required, $this->data);
        
        if (!empty($missing)) {
            $this->error('Missing required fields', 400, ['missing_fields' => $missing]);
        }
        
        $password = $this->data['password'];
        
        // Validate password strength
        $validation = PasswordValidator::validate($password);
        if (!$validation['valid']) {
            $this->error('Password does not meet requirements', 400, [
                'errors' => $validation['errors']
            ]);
        }
        
        try {
            // Check if user already has a password
            $user = $this->db->querySingle(
                "SELECT id, password_hash, email FROM users WHERE id = ?",
                [$this->userId]
            );
            
            if (!$user) {
                $this->error('User not found', 404);
            }
            
            if ($user['password_hash'] !== null) {
                $this->error('Password already set. Use change-password endpoint instead.', 400);
            }
            
            // Hash and set password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $this->db->execute(
                "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?",
                [$hashedPassword, $this->userId]
            );
            
            // Send confirmation email
            try {
                require_once __DIR__ . '/../includes/EmailTemplates.php';
                require_once __DIR__ . '/../includes/GmailClient.php';
                
                $emailData = EmailTemplates::passwordSet($user['email']);
                GmailClient::sendEmail(
                    $user['email'],
                    $emailData['subject'],
                    $emailData['html'],
                    $emailData['text']
                );
            } catch (Exception $e) {
                error_log("Failed to send password set confirmation email: " . $e->getMessage());
                // Don't fail the request if email fails
            }
            
            $this->success([
                'message' => 'Password set successfully',
                'strength' => PasswordValidator::getStrength($password),
                'strength_description' => PasswordValidator::getStrengthDescription(
                    PasswordValidator::getStrength($password)
                )
            ]);
            
        } catch (Exception $e) {
            error_log("Set password error: " . $e->getMessage());
            $this->error('Failed to set password', 500);
        }
    }
}

// Handle the request
$handler = new SetPasswordHandler();
$handler->handleRequest();
