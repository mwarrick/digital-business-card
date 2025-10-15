<?php
/**
 * Admin Login Page
 */

require_once __DIR__ . '/includes/AdminAuth.php';
require_once __DIR__ . '/../api/includes/Database.php';
require_once __DIR__ . '/../api/includes/GmailClient.php';
require_once __DIR__ . '/../api/includes/EmailTemplates.php';

// If already logged in, redirect to dashboard
if (AdminAuth::isLoggedIn() && !AdminAuth::isSessionExpired()) {
    header('Location: /admin/dashboard.php');
    exit;
}

$error = '';
$success = '';

// Check if coming from email link with pre-filled code
if (isset($_GET['code']) && isset($_GET['email'])) {
    $_SESSION['pending_admin_email'] = $_GET['email'];
    $step = 'verify';
    $email = $_GET['email'];
    $prefillCode = $_GET['code'];
} else {
    $step = $_POST['step'] ?? 'email'; // 'email' or 'verify'
    $email = $_SESSION['pending_admin_email'] ?? '';
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
            
            // Check if user exists and is admin
            $user = $db->querySingle(
                "SELECT id, email, is_active, is_admin FROM users WHERE email = ?",
                [$email]
            );
            
            if (!$user) {
                $error = 'User not found';
            } else if (!$user['is_active']) {
                $error = 'Account is not active';
            } else if (!$user['is_admin']) {
                $error = 'Access denied. Admin privileges required.';
            } else {
                // Generate verification code
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
                $emailData = EmailTemplates::loginVerification($code, $user['email'], true); // true = isAdmin
                GmailClient::sendEmail(
                    $user['email'],
                    $emailData['subject'],
                    $emailData['html'],
                    $emailData['text']
                );
                
                $_SESSION['pending_admin_email'] = $email;
                $step = 'verify';
                $success = 'Verification code sent to your email!';
            }
        } catch (PDOException $e) {
            error_log("Admin login PDO error: " . $e->getMessage());
            $error = 'Database error: ' . $e->getMessage();
        } catch (Exception $e) {
            error_log("Admin login error: " . $e->getMessage());
            $error = 'Login failed: ' . $e->getMessage();
        }
    }
}

// Handle verification code submission (Step 2)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 'verify') {
    $email = $_SESSION['pending_admin_email'] ?? '';
    $code = trim($_POST['code'] ?? '');
    
    if (empty($code)) {
        $error = 'Verification code is required';
    } else {
        try {
            $db = Database::getInstance();
            
            // Get user
            $user = $db->querySingle(
                "SELECT id, email, is_active, is_admin FROM users WHERE email = ?",
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
                } else if (!$user['is_admin']) {
                    $error = 'Access denied. Admin privileges required.';
                    unset($_SESSION['pending_admin_email']);
                    $step = 'email';
                } else {
                    // Mark code as used
                    $db->execute(
                        "UPDATE verification_codes SET used_at = NOW() WHERE id = ?",
                        [$verification['id']]
                    );
                    
                    // Set admin session
                    $_SESSION['admin_user_id'] = $user['id'];
                    $_SESSION['admin_email'] = $user['email'];
                    $_SESSION['admin_login_time'] = time();
                    unset($_SESSION['pending_admin_email']);
                    
                    header('Location: /admin/dashboard.php');
                    exit;
                }
            }
        } catch (Exception $e) {
            error_log("Admin verification error: " . $e->getMessage());
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
    <title>Admin Login - ShareMyCard</title>
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
        
        input[type="email"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="email"]:focus {
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
    <div class="login-container">
        <div class="logo">
            <h1>📱 ShareMyCard</h1>
            <p>Admin Panel</p>
        </div>
        
        <?php if ($error && $step === 'email'): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
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
                    <label for="email">Admin Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="Enter your admin email"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        required
                        autofocus
                    >
                </div>
                
                <button type="submit" class="btn">
                    Send Verification Code
                </button>
            </form>
            
            <div class="info-box">
                <strong>ℹ️ Email Verification</strong><br>
                Enter your admin email address. We'll send you a verification code to complete the login.
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
            <a href="/">← Back to Main Site</a>
        </div>
    </div>
</body>
</html>

