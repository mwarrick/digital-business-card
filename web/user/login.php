<?php
/**
 * User Login Page - Supports both password and email verification
 */

// Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/includes/UserAuth.php';
require_once __DIR__ . '/../api/includes/Database.php';
require_once __DIR__ . '/../api/includes/GmailClient.php';
require_once __DIR__ . '/../api/includes/EmailTemplates.php';
require_once __DIR__ . '/../api/includes/LoginAttemptTracker.php';

// If already logged in, redirect to dashboard
if (UserAuth::isLoggedIn() && !UserAuth::isSessionExpired()) {
    header('Location: /user/dashboard.php');
    exit;
}

$error = '';
$success = '';

// Check if coming from email link with pre-filled code
if (isset($_GET['code']) && isset($_GET['email'])) {
    $_SESSION['pending_user_email'] = $_GET['email'];
    $step = 'verify';
    $email = $_GET['email'];
    $prefillCode = $_GET['code'];
    $authMethod = 'code';
} else {
    $step = $_POST['step'] ?? 'email'; // 'email', 'password', or 'verify'
    $email = $_SESSION['pending_user_email'] ?? '';
    $prefillCode = '';
    $authMethod = $_POST['auth_method'] ?? 'password';
}

// Handle "Use Email Code Instead" from password screen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'email' && $authMethod === 'code') {
    $email = $_SESSION['pending_user_email'] ?? '';
    
    if (empty($email)) {
        $error = 'Session expired. Please start over.';
        $step = 'email';
        $authMethod = 'password';
    } else {
        try {
            $db = Database::getInstance();
            
            // Get user info
            $user = $db->querySingle(
                "SELECT id, email, is_active FROM users WHERE email = ?",
                [$email]
            );
            
            if (!$user || !$user['is_active']) {
                $error = 'Account is not active. Please complete your registration first.';
                unset($_SESSION['pending_user_email']);
                $step = 'email';
                $authMethod = 'password';
            } else {
                // Generate and send verification code
                $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $verificationId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                
                // Store verification code (expires in 10 minutes)
                $db->execute(
                    "INSERT INTO verification_codes (id, user_id, code, type, expires_at) 
                     VALUES (?, ?, ?, 'login', DATE_ADD(NOW(), INTERVAL 10 MINUTE))",
                    [$verificationId, $user['id'], $code]
                );
                
                // Send email via Gmail API
                $emailData = EmailTemplates::loginVerification($code, $user['email']);
                GmailClient::sendEmail(
                    $user['email'],
                    $emailData['subject'],
                    $emailData['html'],
                    $emailData['text']
                );
                
                $_SESSION['pending_user_email'] = $email;
                $_SESSION['pending_user_code'] = $code;
                $step = 'verify';
                $authMethod = 'code';
                $success = 'Verification code sent to your email!';
            }
        } catch (Exception $e) {
            error_log("User email code request error: " . $e->getMessage());
            $error = 'Failed to send verification code: ' . $e->getMessage();
        }
    }
}

// Handle email submission (Step 1)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'email' && $authMethod !== 'code') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Email is required';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        try {
            $db = Database::getInstance();
            
            // Check if user exists
            $user = $db->querySingle(
                "SELECT id, email, is_active, password_hash FROM users WHERE email = ?",
                [$email]
            );
            
            if (!$user) {
                $error = 'User not found. Please <a href="/user/register.php">register</a> first.';
            } else if (!$user['is_active']) {
                $error = 'Account is not active. Please complete your registration first.';
            } else {
                $hasPassword = $user['password_hash'] !== null;
                
                if ($hasPassword && $authMethod !== 'code') {
                    // User has password - show password field
                    $_SESSION['pending_user_email'] = $email;
                    $step = 'password';
                    $authMethod = 'password';
                } else {
                    // User doesn't have password OR user chose email code - send verification code
                    $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                    $verificationId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                        mt_rand(0, 0xffff),
                        mt_rand(0, 0x0fff) | 0x4000,
                        mt_rand(0, 0x3fff) | 0x8000,
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                    );
                    
                    // Store verification code (expires in 10 minutes)
                    $db->execute(
                        "INSERT INTO verification_codes (id, user_id, code, type, expires_at) 
                         VALUES (?, ?, ?, 'login', DATE_ADD(NOW(), INTERVAL 10 MINUTE))",
                        [$verificationId, $user['id'], $code]
                    );
                    
                    // For production, try to send email via Gmail API, but continue if it fails
                    try {
                        $emailData = EmailTemplates::loginVerification($code, $user['email']);
                        GmailClient::sendEmail(
                            $user['email'],
                            $emailData['subject'],
                            $emailData['html'],
                            $emailData['text']
                        );
                        $emailSent = true;
                    } catch (Exception $e) {
                        error_log("Failed to send login verification email: " . $e->getMessage());
                        $emailSent = false;
                    }
                    
                    $_SESSION['pending_user_email'] = $email;
                    $_SESSION['pending_user_code'] = $code; // Store for display if email fails
                    $step = 'verify';
                    $authMethod = 'code';
                    $success = $emailSent 
                        ? 'Verification code sent to your email!' 
                        : 'Email service unavailable. Your verification code is: <strong>' . $code . '</strong>';
                }
            }
        } catch (PDOException $e) {
            error_log("User login PDO error: " . $e->getMessage());
            $error = 'Database error: ' . $e->getMessage();
        } catch (Exception $e) {
            error_log("User login error: " . $e->getMessage());
            $error = 'Login failed: ' . $e->getMessage();
        }
    }
}

// Handle password submission (Step 2 - Password)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'password') {
    $email = $_SESSION['pending_user_email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($password)) {
        $error = 'Password is required';
    } else {
        try {
            $db = Database::getInstance();
            
            // Get user
            $user = $db->querySingle(
                "SELECT id, email, is_active, password_hash FROM users WHERE email = ?",
                [$email]
            );
            
            if (!$user) {
                $error = 'User not found';
            } else {
                // Check for rate limiting
                if (LoginAttemptTracker::isLockedOut($user['id'])) {
                    $remaining = LoginAttemptTracker::getRemainingLockoutTime($user['id']);
                    $error = "Account locked due to too many failed attempts. Try again in {$remaining} seconds.";
                } else {
                    // Verify password
                    if (password_verify($password, $user['password_hash'])) {
                        // Clear failed attempts on successful login
                        LoginAttemptTracker::clearFailedAttempts($user['id']);
                        
                        // Log user in
                        UserAuth::login($user['id'], $user['email']);
                        unset($_SESSION['pending_user_email']);
                        
                        header('Location: /user/dashboard.php');
                        exit;
                    } else {
                        // Record failed attempt
                        LoginAttemptTracker::recordFailedAttempt($user['id'], $_SERVER['REMOTE_ADDR'] ?? null);
                        $error = 'Invalid password';
                    }
                }
            }
        } catch (Exception $e) {
            error_log("User password login error: " . $e->getMessage());
            $error = 'Login failed: ' . $e->getMessage();
        }
    }
}

// Handle verification code submission (Step 2 - Code)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'verify') {
    $email = $_SESSION['pending_user_email'] ?? '';
    $code = trim($_POST['code'] ?? '');
    
    if (empty($code)) {
        $error = 'Verification code is required';
    } else {
        try {
            $db = Database::getInstance();
            
            // Get user
            $user = $db->querySingle(
                "SELECT id, email, is_active FROM users WHERE email = ?",
                [$email]
            );
            
            if (!$user) {
                $error = 'User not found';
            } else {
                // Find valid verification code (use MySQL time for comparison)
                $verification = $db->querySingle(
                    "SELECT id, type, used_at, expires_at,
                            (expires_at > NOW()) as is_valid
                     FROM verification_codes 
                     WHERE user_id = ? AND code = ? AND used_at IS NULL
                     ORDER BY created_at DESC 
                     LIMIT 1",
                    [$user['id'], $code]
                );
                
                if (!$verification) {
                    $error = 'Invalid or expired verification code';
                } else if (!$verification['is_valid']) {
                    $error = 'Verification code has expired. Please request a new one.';
                } else {
                    // Mark code as used
                    $db->execute(
                        "UPDATE verification_codes SET used_at = NOW() WHERE id = ?",
                        [$verification['id']]
                    );
                    
                    // Log user in
                    UserAuth::login($user['id'], $user['email']);
                    unset($_SESSION['pending_user_email']);
                    
                    header('Location: /user/dashboard.php');
                    exit;
                }
            }
        } catch (Exception $e) {
            error_log("User verification error: " . $e->getMessage());
            $error = 'Verification failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ShareMyCard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 420px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            font-size: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }
        
        .logo p {
            color: #666;
            font-size: 14px;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        
        .alert-error a {
            color: #c62828;
            text-decoration: underline;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        input[type="email"],
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="email"]:focus,
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .info-box {
            margin-top: 20px;
            margin-bottom: 16px;
            padding: 16px;
            background: #e9f7ef; /* light green */
            border: 1px solid #c8e6c9; /* subtle green border */
            border-radius: 8px;
            font-size: 13px;
            color: #256029; /* darker green text */
        }
        
        .info-box strong {
            color: #333;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>📱 ShareMyCard</h1>
            <p>Welcome Back</p>
        </div>
        
        <?php if ($error && $step === 'email'): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success && $step === 'email'): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($step === 'email'): ?>
            <!-- Step 1: Enter Email -->
            <div class="info-box">
                <strong>ℹ️ User Login</strong><br>
                Enter your email address. We'll check if you have a password set or send you a verification code.
            </div>

            <form method="POST" action="">
                <input type="hidden" name="step" value="email">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="Enter your email"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        required
                        autofocus
                    >
                </div>
                
                <button type="submit" class="btn">
                    Continue
                </button>
            </form>
            
        <?php elseif ($step === 'password'): ?>
            <!-- Step 2: Enter Password -->
            <div class="info-box" style="margin-top: 10px;">
                <strong>🔐 Password Login</strong><br>
                Enter your password for <strong><?php echo htmlspecialchars($email); ?></strong>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="step" value="password">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password"
                        required
                        autofocus
                    >
                </div>
                
                <button type="submit" class="btn">
                    Sign In
                </button>
            </form>
            
            <form method="POST" action="" style="margin-top: 15px;">
                <input type="hidden" name="step" value="email">
                <input type="hidden" name="auth_method" value="code">
                <button type="submit" class="btn" style="background: #6c757d;">
                    Use Email Code Instead
                </button>
            </form>
            
            <form method="POST" action="" style="margin-top: 10px;">
                <input type="hidden" name="step" value="email">
                <button type="submit" class="btn" style="background: #6c757d;">
                    ← Use Different Email
                </button>
            </form>
            
        <?php else: ?>
            <!-- Step 2: Enter Verification Code -->
            <div class="info-box" style="margin-top: 10px;">
                <strong>📧 Code Sent!</strong><br>
                A verification code has been sent to <strong><?php echo htmlspecialchars($email); ?></strong>. Please check your email and enter the code below.
            </div>

            <form method="POST" action="">
                <input type="hidden" name="step" value="verify">
                <div class="form-group">
                    <label for="code">Verification Code</label>
                    <input 
                        type="text" 
                        id="code" 
                        name="code" 
                        value="<?php echo htmlspecialchars($prefillCode); ?>"
                        maxlength="6"
                        pattern="[0-9]{6}"
                        required
                        autofocus
                        style="font-size: 24px; text-align: center; letter-spacing: 0.5em;"
                    >
                </div>
                
                <button type="submit" class="btn">
                    Verify & Sign In
                </button>
            </form>
            
            <form method="POST" action="" style="margin-top: 15px;">
                <input type="hidden" name="step" value="email">
                <button type="submit" class="btn" style="background: #6c757d;">
                    ← Use Different Email
                </button>
            </form>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="/user/register.php">Don't have an account? Register</a><br>
            <a href="/">← Back to Home</a>
        </div>
    </div>
</body>
</html>

