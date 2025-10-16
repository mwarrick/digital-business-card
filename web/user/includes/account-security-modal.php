<?php
/**
 * User Account Security Modal
 * Handles password setting and changing for regular users
 */

// Get current user info
$user = $db->querySingle(
    "SELECT id, email, password_hash, updated_at FROM users WHERE id = ?",
    [$userId]
);

$hasPassword = $user['password_hash'] !== null;
$passwordSetDate = $hasPassword ? date('M j, Y', strtotime($user['updated_at'])) : null;
?>

<!-- Account Security Modal -->
<div id="accountSecurityModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>🔒 Account Security</h2>
            <span class="close" onclick="closeAccountSecurity()">&times;</span>
        </div>
        
        <div class="modal-body">
            <div id="passwordAlert" style="display:none;margin:10px 0;padding:10px;border-radius:6px;font-size:14px"></div>
            
            <?php if ($hasPassword): ?>
                <div class="password-status">
                    <p><strong>Password Status:</strong> <span class="text-success">Set</span></p>
                    <p><strong>Last Updated:</strong> <?php echo htmlspecialchars($passwordSetDate); ?></p>
                </div>
                
                <div class="change-password-form">
                    <h4>Change Password</h4>
                    <form id="changePasswordForm" class="needs-validation" novalidate>
                        <div class="form-group">
                            <label for="currentPassword">Current Password</label>
                            <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                            <div class="invalid-feedback">
                                Please enter your current password.
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="newPassword">New Password</label>
                            <input type="password" class="form-control" id="newPassword" name="new_password" required>
                            <div class="invalid-feedback">
                                Please enter a new password.
                            </div>
                            <div class="password-strength mt-2">
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar" id="passwordStrengthBar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small class="text-muted" id="passwordStrengthText">Enter a password to see strength</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirmPassword">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                            <div class="invalid-feedback">
                                Passwords do not match.
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
                
            <?php else: ?>
                <div class="password-status">
                    <p><strong>Password Status:</strong> <span class="text-warning">Not Set</span></p>
                    <p class="text-muted">You can set a password to enable faster login, or continue using email verification codes.</p>
                </div>
                
                <div class="set-password-form">
                    <h4>Set Password</h4>
                    <form id="setPasswordForm" class="needs-validation" novalidate>
                        <div class="form-group">
                            <label for="newPassword">New Password</label>
                            <input type="password" class="form-control" id="newPassword" name="password" required>
                            <div class="invalid-feedback">
                                Please enter a password.
                            </div>
                            <div class="password-strength mt-2">
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar" id="passwordStrengthBar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small class="text-muted" id="passwordStrengthText">Enter a password to see strength</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirmPassword">Confirm Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                            <div class="invalid-feedback">
                                Passwords do not match.
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Set Password</button>
                    </form>
                </div>
            <?php endif; ?>
            
            <div class="password-requirements mt-4">
                <h5>Password Requirements</h5>
                <ul class="list-unstyled">
                    <li><i class="bi bi-check-circle text-success" id="req-length"></i> At least 8 characters</li>
                    <li><i class="bi bi-check-circle text-success" id="req-upper"></i> At least one uppercase letter</li>
                    <li><i class="bi bi-check-circle text-success" id="req-lower"></i> At least one lowercase letter</li>
                    <li><i class="bi bi-check-circle text-success" id="req-number"></i> At least one number</li>
                    <li><i class="bi bi-check-circle text-success" id="req-special"></i> At least one special character</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
/* Modal Styles */
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.modal-header h2 {
    margin: 0;
    color: #333;
}

.close {
    font-size: 28px;
    font-weight: bold;
    color: #aaa;
    cursor: pointer;
    line-height: 1;
}

.close:hover {
    color: #000;
}

.modal-body {
    padding: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.password-strength {
    margin-top: 8px;
}

.progress {
    background-color: #e0e0e0;
    border-radius: 3px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    transition: width 0.3s;
}

.text-success { color: #28a745; }
.text-warning { color: #ffc107; }
.text-muted { color: #6c757d; }

.password-requirements ul {
    margin: 0;
    padding-left: 0;
}

.password-requirements li {
    margin-bottom: 5px;
    font-size: 14px;
}

.invalid-feedback {
    display: none;
    color: #dc3545;
    font-size: 12px;
    margin-top: 5px;
}

.was-validated .form-control:invalid ~ .invalid-feedback {
    display: block;
}
</style>

<script>
function openAccountSecurity() {
    document.getElementById('accountSecurityModal').style.display = 'flex';
}

function closeAccountSecurity() {
    document.getElementById('accountSecurityModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('accountSecurityModal');
    if (event.target === modal) {
        closeAccountSecurity();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('newPassword');
    const confirmInput = document.getElementById('confirmPassword');
    const strengthBar = document.getElementById('passwordStrengthBar');
    const strengthText = document.getElementById('passwordStrengthText');
    const requirements = {
        length: document.getElementById('req-length'),
        upper: document.getElementById('req-upper'),
        lower: document.getElementById('req-lower'),
        number: document.getElementById('req-number'),
        special: document.getElementById('req-special')
    };
    
    function showAlert(message, type) {
        const el = document.getElementById('passwordAlert');
        if (!el) return;
        el.style.display = 'block';
        el.textContent = message;
        el.style.background = type === 'success' ? '#e8f5e9' : '#ffebee';
        el.style.color = type === 'success' ? '#2e7d32' : '#c62828';
        el.style.border = '1px solid ' + (type === 'success' ? '#c8e6c9' : '#ef9a9a');
    }
    
    // Password strength checking
    function checkPasswordStrength(password) {
        let score = 0;
        const checks = {
            length: password.length >= 8,
            upper: /[A-Z]/.test(password),
            lower: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[^A-Za-z0-9]/.test(password)
        };
        
        // Update requirement indicators
        Object.keys(checks).forEach(key => {
            const icon = requirements[key];
            if (checks[key]) {
                icon.className = 'bi bi-check-circle text-success';
                score += 20;
            } else {
                icon.className = 'bi bi-circle text-muted';
            }
        });
        
        // Update strength bar and text
        strengthBar.style.width = score + '%';
        if (score < 30) {
            strengthBar.className = 'progress-bar bg-danger';
            strengthText.textContent = 'Weak';
        } else if (score < 60) {
            strengthBar.className = 'progress-bar bg-warning';
            strengthText.textContent = 'Fair';
        } else if (score < 80) {
            strengthBar.className = 'progress-bar bg-info';
            strengthText.textContent = 'Good';
        } else {
            strengthBar.className = 'progress-bar bg-success';
            strengthText.textContent = 'Strong';
        }
    }
    
    // Real-time password strength checking
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            checkPasswordStrength(this.value);
            validatePasswordMatch();
        });
    }
    
    // Password confirmation validation
    function validatePasswordMatch() {
        const password = passwordInput ? passwordInput.value : '';
        const confirm = confirmInput ? confirmInput.value : '';
        
        if (confirm && password !== confirm) {
            confirmInput.setCustomValidity('Passwords do not match');
        } else {
            confirmInput.setCustomValidity('');
        }
    }
    
    if (confirmInput) {
        confirmInput.addEventListener('input', validatePasswordMatch);
    }
    
    // Form submission
    const setPasswordForm = document.getElementById('setPasswordForm');
    const changePasswordForm = document.getElementById('changePasswordForm');
    
    if (setPasswordForm) {
        setPasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!this.checkValidity()) {
                e.stopPropagation();
                this.classList.add('was-validated');
                return;
            }
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            fetch('/user/api/set-password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Password set successfully!', 'success');
                    setTimeout(() => {
                        closeAccountSecurity();
                        location.reload();
                    }, 1500);
                } else {
                    showAlert(data.message || 'Failed to set password', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'danger');
            });
        });
    }
    
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!this.checkValidity()) {
                e.stopPropagation();
                this.classList.add('was-validated');
                return;
            }
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            fetch('/user/api/change-password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Password changed successfully!', 'success');
                    this.reset();
                    this.classList.remove('was-validated');
                } else {
                    showAlert(data.message || 'Failed to change password', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'danger');
            });
        });
    }
});
</script>
