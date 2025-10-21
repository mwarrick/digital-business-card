<?php
/**
 * User Registration Page
 */

// Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/includes/UserAuth.php';
require_once __DIR__ . '/../api/includes/Database.php';
require_once __DIR__ . '/../api/includes/GmailClient.php';
require_once __DIR__ . '/../api/includes/EmailTemplates.php';

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
} else {
    $step = $_POST['step'] ?? 'email'; // 'email' or 'verify'
    $email = $_SESSION['pending_user_email'] ?? '';
    $prefillCode = '';
}

// Handle email submission (Step 1)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'email') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Email is required';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        try {
            $db = Database::getInstance();
            
            // Check if user already exists
            $existingUser = $db->querySingle(
                "SELECT id, email, is_active FROM users WHERE email = ?",
                [$email]
            );
            
            if ($existingUser && $existingUser['is_active']) {
                $error = 'An account with this email already exists. Please <a href="/user/login.php">login</a> instead.';
            } else if ($existingUser && !$existingUser['is_active']) {
                // User exists but not active - resend verification
                $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $verificationId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                
                $db->execute(
                    "INSERT INTO verification_codes (id, user_id, code, type, expires_at) 
                     VALUES (?, ?, ?, 'register', DATE_ADD(NOW(), INTERVAL 10 MINUTE))",
                    [$verificationId, $existingUser['id'], $code]
                );
                
                $emailData = EmailTemplates::registrationVerification($code, $email);
                GmailClient::sendEmail($email, $emailData['subject'], $emailData['html'], $emailData['text']);
                
                $_SESSION['pending_user_email'] = $email;
                $step = 'verify';
                $success = 'Verification code sent to your email!';
            } else {
                // Create new inactive user
                $userId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                
                $db->execute(
                    "INSERT INTO users (id, email, is_active, is_admin) 
                     VALUES (?, ?, 0, 0)",
                    [$userId, $email]
                );
                
                // Generate verification code
                $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $verificationId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                
                $db->execute(
                    "INSERT INTO verification_codes (id, user_id, code, type, expires_at) 
                     VALUES (?, ?, ?, 'register', DATE_ADD(NOW(), INTERVAL 10 MINUTE))",
                    [$verificationId, $userId, $code]
                );
                
                // Send email
                $emailData = EmailTemplates::registrationVerification($code, $email);
                GmailClient::sendEmail($email, $emailData['subject'], $emailData['html'], $emailData['text']);
                
                $_SESSION['pending_user_email'] = $email;
                $step = 'verify';
                $success = 'Verification code sent to your email!';
            }
        } catch (PDOException $e) {
            error_log("Registration PDO error: " . $e->getMessage());
            $error = 'Database error: ' . $e->getMessage();
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}

// Handle verification code submission (Step 2)
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
                // Find valid verification code
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
                    
                    // Activate user
                    $db->execute(
                        "UPDATE users SET is_active = 1 WHERE id = ?",
                        [$user['id']]
                    );
                    
                    // Check for invitation tracking
                    if (isset($_GET['ref']) && $_GET['ref'] === 'invitation' && isset($_GET['token'])) {
                        $invitationToken = $_GET['token'];
                        
                        // Find and update invitation record
                        $invitation = $db->querySingle(
                            "SELECT id FROM invitations WHERE invitation_token = ?",
                            [$invitationToken]
                        );
                        
                        if ($invitation) {
                            $db->execute(
                                "UPDATE invitations 
                                 SET created_account = 1, created_account_at = NOW(), registered_user_id = ? 
                                 WHERE invitation_token = ?",
                                [$user['id'], $invitationToken]
                            );
                        }
                    }
                    
                    // Log user in
                    UserAuth::login($user['id'], $user['email']);
                    unset($_SESSION['pending_user_email']);
                    
                    header('Location: /user/dashboard.php');
                    exit;
                }
            }
        } catch (Exception $e) {
            error_log("Verification error: " . $e->getMessage());
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
    <title>Register - ShareMyCard</title>
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
        
        .register-container {
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
        input[type="text"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="email"]:focus,
        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        /* Verification code input styling */
        #code {
            font-size: 24px;
            text-align: center;
        }
        
        /* Apply letter-spacing only when there's a value, not to placeholder */
        #code:not(:placeholder-shown) {
            letter-spacing: 0.5em;
        }
        
        #code::placeholder {
            font-size: 16px;
            letter-spacing: normal;
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
    <div class="register-container">
        <div class="logo">
            <h1>📱 ShareMyCard</h1>
            <p>Create Your Account</p>
        </div>
        
        <?php if ($error): ?>
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
                    Create Account
                </button>
            </form>
            
            <div class="info-box">
                <strong>ℹ️ Email Verification</strong><br>
                Enter your email address. We'll send you a verification code to complete your registration.
            </div>
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
                    >
                </div>
                
                <button type="submit" class="btn">
                    Verify & Complete Registration
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
            <a href="/user/login.php">Already have an account? Login</a><br>
            <a href="/">← Back to Home</a>
        </div>
    </div>
</body>
</html>

