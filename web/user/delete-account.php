<?php
/**
 * Delete Account Page
 * Requires authentication and password confirmation
 */

require_once __DIR__ . '/includes/UserAuth.php';
require_once __DIR__ . '/../api/includes/Database.php';

// Check if user is logged in
if (!UserAuth::isLoggedIn()) {
    // Not logged in - redirect to login
    header('Location: /user/login.php');
    exit;
}

$db = Database::getInstance();
$userId = UserAuth::getUserId();

// Get user's password status - check if user still exists
$userData = $db->querySingle(
    "SELECT id, email, password_hash, role FROM users WHERE id = ?",
    [$userId]
);

// If user doesn't exist (account was already deleted), redirect to confirmation page
if (!$userData) {
    // Clear session and redirect to account deleted page
    UserAuth::logout();
    header('Location: /account-deleted.php');
    exit;
}

// Prevent demo users from accessing delete account page
if ($userData['role'] === 'demo') {
    header('Location: /user/dashboard.php');
    exit;
}

$user = UserAuth::getUser();
$hasPassword = $userData['password_hash'] !== null;
$error = '';
$success = '';

// Form submission is handled via JavaScript/AJAX to call the API
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account - ShareMyCard</title>
    <link rel="stylesheet" href="/user/includes/user-style.css">
    <style>
        .delete-account-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
        }
        
        .warning-banner {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .warning-banner h2 {
            color: #856404;
            margin-top: 0;
            margin-bottom: 15px;
        }
        
        .warning-banner p {
            color: #856404;
            margin-bottom: 10px;
        }
        
        .data-list {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .data-list h3 {
            margin-top: 0;
            color: #333;
        }
        
        .data-list ul {
            margin: 10px 0;
            padding-left: 25px;
        }
        
        .data-list li {
            margin-bottom: 8px;
            color: #555;
        }
        
        .delete-form {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 30px;
            margin-top: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .form-group input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover:not(:disabled) {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }
        
        .btn-danger:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .help-text {
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }
        
        .help-text a {
            color: #667eea;
        }
        
        .info-box {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .info-box p {
            margin: 0;
            color: #1976d2;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navigation.php'; ?>
    
    <div class="delete-account-container">
        <h1 style="margin-bottom: 20px;">🗑️ Delete Your Account</h1>
        
        <div class="warning-banner">
            <h2>⚠️ Warning: This Action Cannot Be Undone</h2>
            <p><strong>Deleting your account is permanent.</strong> Once you confirm deletion, all of your data will be permanently removed and cannot be recovered.</p>
        </div>
        
        <div class="data-list">
            <h3>What Will Be Deleted:</h3>
            <ul>
                <li>All your business cards and related information</li>
                <li>All contact information (emails, phones, addresses, website links)</li>
                <li>All leads and contacts</li>
                <li>All custom QR codes and their analytics</li>
                <li>All analytics data (views, clicks, downloads)</li>
                <li>All uploaded media files (profile photos, company logos, cover graphics)</li>
                <li>All invitations you've sent</li>
                <li>Your account credentials and settings</li>
            </ul>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <div class="delete-form">
            <h2>Confirm Deletion</h2>
            <p>To confirm that you want to delete your account, please enter your password below.</p>
            
            <?php if (!$hasPassword): ?>
                <div class="info-box" style="margin-bottom: 20px;">
                    <p><strong>Password Required:</strong> You must set a password for your account before you can delete it.</p>
                    <p style="margin-top: 10px;">
                        <a href="#" onclick="openAccountSecurity(); return false;" style="display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 6px; font-weight: 500;">
                            Set a Password in Account Security
                        </a>
                    </p>
                    <p style="margin-top: 15px; font-size: 14px; color: #666;">
                        Once you've set a password, return to this page to delete your account.
                    </p>
                </div>
            <?php else: ?>
                <form id="deleteAccountForm">
                    <div class="form-group">
                        <label for="password">Enter Your Password <span style="color: #dc3545;">*</span></label>
                        <input type="password" id="password" name="password" required autocomplete="current-password">
                        <div class="help-text">
                            Forgot your password? Send an email from your account email to <a href="mailto:mark@sharemycard.app">mark@sharemycard.app</a> requesting a password reset.
                        </div>
                    </div>
                    
                    <div id="apiError" class="error-message" style="display: none;"></div>
                    
                    <div class="button-group">
                        <button type="submit" class="btn btn-danger" id="confirmBtn" disabled>
                            Confirm Deletion
                        </button>
                        <a href="/user/dashboard.php" class="btn btn-secondary" style="text-decoration: none; display: inline-block;">
                            Cancel
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="info-box" style="margin-top: 30px;">
            <p><strong>Need help?</strong> Visit our <a href="/delete-account.php">public information page</a> for more details about account deletion, or contact us at <a href="mailto:mark@sharemycard.app">mark@sharemycard.app</a>.</p>
        </div>
    </div>
    
    <script>
        // Enable/disable confirm button based on password input
        const passwordInput = document.getElementById('password');
        const confirmBtn = document.getElementById('confirmBtn');
        const apiError = document.getElementById('apiError');
        const deleteForm = document.getElementById('deleteAccountForm');
        
        if (passwordInput && confirmBtn && deleteForm) {
            // Enable/disable button based on password input
            passwordInput.addEventListener('input', function() {
                confirmBtn.disabled = this.value.length === 0;
            });
            
            // Handle form submission via AJAX
            deleteForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (!passwordInput.value) {
                    apiError.textContent = 'Password is required';
                    apiError.style.display = 'block';
                    return;
                }
                
                if (!confirm('Are you absolutely sure you want to delete your account? This action cannot be undone!')) {
                    return;
                }
                
                // Disable button and show loading
                confirmBtn.disabled = true;
                confirmBtn.textContent = 'Deleting...';
                apiError.style.display = 'none';
                
                // Call API
                const requestData = {
                    password: passwordInput.value
                };
                
                fetch('/user/api/delete-account.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestData)
                })
                .then(response => {
                    // Check if response is ok
                    if (!response.ok) {
                        // Try to get error message from response
                        return response.text().then(text => {
                            try {
                                const json = JSON.parse(text);
                                throw new Error(json.message || 'Server error');
                            } catch (e) {
                                if (e instanceof Error && e.message !== 'Server error') {
                                    throw e;
                                }
                                throw new Error('Server returned an error. Please try again.');
                            }
                        });
                    }
                    
                    // Check content type
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return response.text().then(text => {
                            throw new Error('Invalid response from server');
                        });
                    }
                    
                    return response.json();
                })
                .then(data => {
                    if (data && data.success) {
                        // Account deleted - logout and redirect to confirmation page
                        // First logout to clear session, then redirect
                        fetch('/user/logout.php', { method: 'GET' })
                            .then(() => {
                                window.location.href = '/account-deleted.php';
                            })
                            .catch(() => {
                                // Even if logout fails, redirect (user is already deleted)
                                window.location.href = '/account-deleted.php';
                            });
                    } else {
                        // Show error
                        apiError.textContent = (data && data.message) ? data.message : 'Failed to delete account. Please try again.';
                        apiError.style.display = 'block';
                        confirmBtn.disabled = false;
                        confirmBtn.textContent = 'Confirm Deletion';
                    }
                })
                .catch(error => {
                    apiError.textContent = error.message || 'An error occurred. Please try again.';
                    apiError.style.display = 'block';
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = 'Confirm Deletion';
                });
            });
        }
    </script>
    
    <!-- Account Security Modal -->
    <?php 
    $userId = UserAuth::getUserId();
    include __DIR__ . '/includes/account-security-modal.php'; 
    ?>
</body>
</html>

