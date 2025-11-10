<?php
/**
 * Verify Code Endpoint
 * POST /api/auth/verify
 * Verifies the email code and completes login/registration
 */

require_once __DIR__ . '/../includes/Api.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/JWT.php';
require_once __DIR__ . '/../includes/DemoUserHelper.php';

// Load database configuration for JWT secret key
require_once __DIR__ . '/../../config/database.php';

class VerifyApi extends Api {
    private $db;
    
    public function __construct() {
        parent::__construct();
        
        // Check if this is a demo user before applying rate limiting
        $email = trim($this->data['email'] ?? '');
        if (!DemoUserHelper::isDemoUser($email)) {
            // Apply rate limiting: 100 verification attempts per hour (only for non-demo users)
            $this->applyRateLimit(100, 3600, 'verify');
        }
        
        $this->db = Database::getInstance();
        $this->handleRequest();
    }
    
    private function handleRequest() {
        if ($this->method !== 'POST') {
            $this->error('Method not allowed', 405);
        }
        
        $this->verify();
    }
    
    private function verify() {
        // Validate required fields - either code OR password
        $email = trim($this->data['email'] ?? '');
        $code = trim($this->data['code'] ?? '');
        $password = $this->data['password'] ?? '';
        
        if (empty($email)) {
            $this->error('Email is required', 400);
        }
        
        // Check for demo user - skip all verification
        if (DemoUserHelper::isDemoUser($email)) {
            $demoUser = DemoUserHelper::getDemoUserData();
            
            // Reset demo user password to 123456789 on each login
            $passwordHash = password_hash('123456789', PASSWORD_DEFAULT);
            $this->db->execute(
                "UPDATE users SET password_hash = ? WHERE email = ?",
                [$passwordHash, $email]
            );
            
            // Only ensure demo cards during actual login, not during session verification
            // Check if this is a login request (has password or code) vs session verification
            if (!empty($code) || !empty($password)) {
                error_log("DEMO LOGIN: Ensuring demo cards during login");
                DemoUserHelper::ensureDemoCards();
            } else {
                error_log("DEMO SESSION: Skipping demo card creation during session verification");
            }
            
            // Update last login timestamp and increment login count for demo user
            $this->db->execute(
                "UPDATE users SET last_login = NOW(), login_count = login_count + 1 WHERE id = ?",
                [$demoUser['id']]
            );
            
            // Generate JWT token immediately
            $token = JWT::encode([
                'user_id' => $demoUser['id'],
                'email' => $demoUser['email'],
                'is_admin' => false,
                'is_demo' => true
            ]);
            
            $this->success([
                'token' => $token,
                'user' => [
                    'id' => $demoUser['id'],
                    'email' => $demoUser['email'],
                    'is_admin' => false,
                    'is_demo' => true
                ]
            ], 'Demo user verified');
            return;
        }
        
        if (empty($code) && empty($password)) {
            $this->error('Either verification code or password is required', 400);
        }
        
        if (!empty($code) && !empty($password)) {
            $this->error('Provide either verification code OR password, not both', 400);
        }
        
        // Debug logging for email validation
        error_log("Verify API - Raw email from request: " . var_export($this->data['email'] ?? 'NOT SET', true));
        error_log("Verify API - Trimmed email: " . var_export($email, true));
        error_log("Verify API - Email length: " . strlen($email));
        error_log("Validate email result: " . var_export($this->validateEmail($email), true));
        error_log("Email bytes: " . bin2hex($email));
        
        // Additional email cleaning - remove any null bytes or control characters
        $email = preg_replace('/[\x00-\x1F\x7F]/', '', $email);
        $email = trim($email);
        
        // Try to find user first (case-insensitive) to get the actual email from database
        // This helps if the email has case/encoding issues
        try {
            $userCheck = $this->db->querySingle(
                "SELECT email FROM users WHERE LOWER(TRIM(email)) = LOWER(?) LIMIT 1",
                [trim($email)]
            );
            if ($userCheck) {
                // Use the email from database (correct case/format)
                $email = $userCheck['email'];
                error_log("Verify API - Found user in database, using email from DB: '$email'");
            }
        } catch (Exception $e) {
            error_log("Verify API - Error checking user: " . $e->getMessage());
        }
        
        // Validate email format
        if (!$this->validateEmail($email)) {
            error_log("Verify API - Email validation failed for: '$email' (length: " . strlen($email) . ", bytes: " . bin2hex($email) . ")");
            $this->error('Invalid email format', 400);
        }
        
        // Validate code format (6 digits) if provided
        if (!empty($code) && !preg_match('/^\d{6}$/', $code)) {
            $this->error('Invalid code format. Must be 6 digits.', 400);
        }
        
        try {
            // Find user
            $user = $this->db->querySingle(
                "SELECT id, email, is_admin, is_active, password_hash FROM users WHERE email = ?",
                [$email]
            );
            
            if (!$user) {
                $this->error('User not found', 404);
            }
            
            $verification = null;
            
            // Handle password authentication
            if (!empty($password)) {
                if ($user['password_hash'] === null) {
                    $this->error('No password set for this account. Please use email verification.', 400);
                }
                
                // Check for rate limiting
                require_once __DIR__ . '/../includes/LoginAttemptTracker.php';
                if (LoginAttemptTracker::isLockedOut($user['id'])) {
                    $remaining = LoginAttemptTracker::getRemainingLockoutTime($user['id']);
                    $this->error("Account locked due to too many failed attempts. Try again in {$remaining} seconds.", 429);
                }
                
                // Verify password
                if (!password_verify($password, $user['password_hash'])) {
                    // Record failed attempt
                    LoginAttemptTracker::recordFailedAttempt($user['id'], $_SERVER['REMOTE_ADDR'] ?? null);
                    $this->error('Invalid password', 401);
                }
                
                // Clear failed attempts on successful login
                LoginAttemptTracker::clearFailedAttempts($user['id']);
                
                // Create a mock verification object for consistency
                $verification = ['type' => 'password'];
            }
            
            // Handle code authentication
            if (!empty($code)) {
                // Find valid verification code (use MySQL time for comparison to avoid timezone issues)
                $verification = $this->db->querySingle(
                    "SELECT id, type, used_at, expires_at,
                            (expires_at > NOW()) as is_valid
                     FROM verification_codes 
                     WHERE user_id = ? AND code = ? AND used_at IS NULL
                     ORDER BY created_at DESC 
                     LIMIT 1",
                    [$user['id'], $code]
                );
                
                if (!$verification) {
                    $this->error('Invalid or expired verification code', 400);
                }
                
                // Check if expired (using MySQL's time comparison)
                if (!$verification['is_valid']) {
                    $this->error('Verification code has expired. Please request a new one.', 400);
                }
                
                // Mark code as used
                $this->db->execute(
                    "UPDATE verification_codes SET used_at = NOW() WHERE id = ?",
                    [$verification['id']]
                );
            }
            
            // If registration verification, activate the user
            if ($verification['type'] === 'register') {
                $this->db->execute(
                    "UPDATE users SET is_active = 1, updated_at = NOW() WHERE id = ?",
                    [$user['id']]
                );
                
                // Send welcome email
                try {
                    // Load required includes for email sending
                    require_once __DIR__ . '/../includes/GmailClient.php';
                    require_once __DIR__ . '/../includes/EmailTemplates.php';
                    
                    $emailData = EmailTemplates::welcome();
                    GmailClient::sendEmail($email, $emailData['subject'], $emailData['html'], $emailData['text']);
                } catch (Exception $e) {
                    error_log("Failed to send welcome email: " . $e->getMessage());
                }
            }
            
            // Update last login timestamp and increment login count
            $this->db->execute(
                "UPDATE users SET last_login = NOW(), login_count = login_count + 1 WHERE id = ?",
                [$user['id']]
            );
            
            // Generate JWT token (valid for 30 days)
            $token = JWT::createUserToken(
                $user['id'], 
                $user['email'], 
                (bool)$user['is_admin'],
                2592000 // 30 days
            );
            
            $this->success([
                'token' => $token,
                'user_id' => $user['id'],
                'email' => $user['email'],
                'is_admin' => (bool)$user['is_admin'],
                'is_active' => true,
                'verification_type' => $verification['type'],
                'token_expires_in' => 2592000,
                'message' => $verification['type'] === 'register' 
                    ? 'Registration completed successfully!' 
                    : 'Login successful!'
            ], 'Verification successful');
            
        } catch (Exception $e) {
            error_log("Verification error: " . $e->getMessage());
            $this->error('Verification failed', 500);
        }
    }
}

// Initialize API
new VerifyApi();

