# Password Authentication Addition Plan

## Overview

Add optional password authentication to the existing passwordless email verification system. This maintains backward compatibility while satisfying Apple's App Store review requirements.

## Current State

- Database: `password_hash` column exists and is nullable (just migrated)
- All three login systems use email verification codes only
- No password setting or management functionality exists

## Target State

- Dual authentication options: password OR email verification code
- Users with passwords can login directly
- Users without passwords use existing email code flow
- Password setting available in user/admin dashboards
- iOS app supports both authentication methods

## Affected Systems

1. **Admin Web Login** (`web/admin/login.php`)
2. **User Web Login** (`web/user/login.php`)
3. **iOS App Login** (`QRCard/LoginView.swift` + `QRCard/AuthService.swift`)
4. **API Endpoints** (`web/api/auth/login.php`)

---

## Implementation Steps

### Phase 1: Database & Backend

#### 1.1 Password Management API Endpoints

**Create:** `web/api/auth/set-password.php`
- Accept current JWT token + new password
- Validate password strength (min 8 chars, mix of types)
- Hash password using `password_hash()`
- Update user's `password_hash` field
- Return success/error

**Create:** `web/api/auth/change-password.php`
- Accept current password + new password
- Verify current password
- Hash and update new password
- Return success/error

**Create:** `web/api/auth/reset-password-request.php`
- Accept email address
- Generate reset code (6 digits)
- Store in `verification_codes` table with type='password_reset'
- Send email with code
- Return success

**Create:** `web/api/auth/reset-password-complete.php`
- Accept email + reset code + new password
- Verify reset code is valid and not expired
- Hash and set new password
- Mark code as used
- Return success/error

#### 1.2 Update Login API Endpoint

**Modify:** `web/api/auth/login.php`

Current flow: email → send code → verify code → return JWT

New flow:
```
POST /api/auth/login
Body: { "email": "user@example.com" }

Response: {
  "has_password": true/false,
  "verification_code_sent": false (if has_password)
}

If has_password = false:
  - Send verification code to email
  - Return has_password: false, verification_code_sent: true

POST /api/auth/verify
Body: { 
  "email": "user@example.com",
  "password": "userpassword" (optional)
  "code": "123456" (optional)
}

Response: {
  "success": true,
  "token": "jwt-token-here",
  "user": {...}
}
```

Logic:
1. Check if user has `password_hash` IS NOT NULL
2. If has password AND password provided → verify password → return JWT
3. If has password AND no password provided → send email code → return success
4. If no password → send email code → return success
5. On verify endpoint: accept either password OR code

---

### Phase 2: Web Interface Updates

#### 2.1 Admin Login Page

**Modify:** `web/admin/login.php`

Changes:
- After entering email, check if user has password set
- If has password:
  - Show password field
  - Show "Login with Email Code Instead" button
  - Submit password for verification
- If no password OR user clicks "Email Code" button:
  - Use existing email verification flow

#### 2.2 User Login Page

**Modify:** `web/user/login.php`

Same changes as admin login page

#### 2.3 Admin Dashboard - Password Setting

**Create:** `web/admin/includes/password-settings.php` (component)
- Form to set password (if not set)
- Form to change password (if already set)
- Password strength indicator
- Confirmation field

**Modify:** `web/admin/dashboard.php`
- Add "Account Security" section
- Include password settings component
- Show "No password set" or "Password set on [date]"

#### 2.4 User Dashboard - Password Setting

**Create:** `web/user/includes/password-settings.php` (component)
- Same functionality as admin version

**Modify:** `web/user/dashboard.php`
- Add "Account Security" section
- Include password settings component

---

### Phase 3: iOS App Updates

#### 3.1 Update Login Flow

**Modify:** `QRCard/LoginView.swift`

Current: Email → Code Entry → Login

New:
```
State 1: Email Entry
↓
State 2a (has password): Password Entry + "Use Email Code" button
State 2b (no password): Code Entry (existing)
↓
State 3: Logged In
```

UI Changes:
- Add password field (SecureField)
- Add "Login with Email Code Instead" button
- Add "Forgot Password?" link
- Toggle between password and code entry modes

**Modify:** `QRCard/AuthService.swift`

Add methods:
- `checkUserHasPassword(email: String) async throws -> Bool`
- `loginWithPassword(email: String, password: String) async throws -> User`
- `requestPasswordReset(email: String) async throws`
- `resetPassword(email: String, code: String, newPassword: String) async throws`
- `setPassword(password: String) async throws`
- `changePassword(currentPassword: String, newPassword: String) async throws`

#### 3.2 Password Management Views

**Create:** `QRCard/PasswordSettingsView.swift`
- Form to set password (first time)
- Form to change password
- Password strength validation
- Accessible from user profile/settings

**Create:** `QRCard/ForgotPasswordView.swift`
- Enter email → Send reset code
- Enter code + new password → Reset

**Modify:** `QRCard/ContentView.swift`
- Add navigation to Password Settings

---

### Phase 4: Email Templates

**Modify:** `web/api/includes/EmailTemplates.php`

**Restore and update:** `passwordReset()` method
```php
public static function passwordReset($resetCode, $email) {
    // Email template for password reset code
    // Include code and link
}
```

**Add:** `passwordSet()` method
```php
public static function passwordSet($email) {
    // Confirmation email when password is first set
}
```

**Add:** `passwordChanged()` method
```php
public static function passwordChanged($email) {
    // Notification email when password is changed
}
```

---

## Security Considerations

### Password Requirements
- Minimum 8 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one number
- Optional: At least one special character

### Password Hashing
- Use PHP's `password_hash()` with `PASSWORD_DEFAULT`
- Use `password_verify()` for verification
- Never store plain text passwords

### Rate Limiting
- Limit password attempts (5 failed attempts = 15 minute lockout)
- Store failed attempts in new table or session
- Reset counter on successful login

### Security Notifications
- Email notification when password is set
- Email notification when password is changed
- Email notification on failed login attempts (after 3 failures)

---

## Database Changes

### New Table: Password Reset Codes
Already handled by existing `verification_codes` table with `type='password_reset'`

### New Table: Failed Login Attempts

**Create:** `web/config/migrations/007_add_failed_login_tracking.sql`

```sql
CREATE TABLE IF NOT EXISTS failed_login_attempts (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    ip_address VARCHAR(45),
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_attempts (user_id, attempted_at)
);
```

---

## Testing Checklist

### Web Admin Login
- [ ] Login with password (existing user with password)
- [ ] Login with email code (existing user with password)
- [ ] Login with email code (user without password)
- [ ] Set password for first time
- [ ] Change existing password
- [ ] Reset forgotten password
- [ ] Invalid password attempt (rate limiting)

### Web User Login
- [ ] Login with password (existing user with password)
- [ ] Login with email code (existing user with password)
- [ ] Login with email code (user without password)
- [ ] Set password for first time
- [ ] Change existing password
- [ ] Reset forgotten password
- [ ] Invalid password attempt (rate limiting)

### iOS App
- [ ] Login with password (existing user with password)
- [ ] Login with email code (existing user with password)
- [ ] Login with email code (user without password)
- [ ] Set password in app
- [ ] Change password in app
- [ ] Reset forgotten password flow
- [ ] Invalid password attempt (rate limiting)

### API Endpoints
- [ ] `/api/auth/login` - check has_password flag
- [ ] `/api/auth/verify` - password verification
- [ ] `/api/auth/verify` - code verification (still works)
- [ ] `/api/auth/set-password` - first time password set
- [ ] `/api/auth/change-password` - password change
- [ ] `/api/auth/reset-password-request` - send reset code
- [ ] `/api/auth/reset-password-complete` - complete reset

---

## Deployment Plan

### Step 1: Database Migration
- [x] Already completed - password_hash is nullable
- [ ] Run migration 007 for failed login tracking

### Step 2: Backend API (No user impact)
- [ ] Deploy new API endpoints
- [ ] Deploy updated login endpoint
- [ ] Deploy email templates

### Step 3: Web Interface (Gradual rollout)
- [ ] Deploy admin password settings
- [ ] Deploy user password settings
- [ ] Deploy updated login pages
- [ ] Test with existing users

### Step 4: iOS App (App Store submission)
- [ ] Update iOS app with password support
- [ ] Test thoroughly
- [ ] Submit to TestFlight
- [ ] Submit to App Store
- [ ] Include password authentication in review notes

---

## Backward Compatibility

✅ **Existing users without passwords:**
- Can continue using email verification codes
- No disruption to current workflow
- Can optionally set password later

✅ **Existing email code flow:**
- Remains fully functional
- Available as fallback for users with passwords
- No code changes to verification system

✅ **API compatibility:**
- Old API calls still work (email → code → verify)
- New API calls add password option
- Clients can upgrade gradually

---

## Apple App Store Compliance

This implementation addresses Apple's password requirement by:
1. ✅ Providing password-based authentication option
2. ✅ Maintaining security best practices (hashing, rate limiting)
3. ✅ Offering password reset functionality
4. ✅ Not forcing existing users to change their auth method
5. ✅ Giving users choice of authentication method

**Review Notes for Apple:**
"ShareMyCard supports both password-based authentication and passwordless email verification. Users can choose their preferred method. Password authentication uses industry-standard bcrypt hashing with rate limiting and password reset functionality."

---

## Files to Create

### API
- `web/api/auth/set-password.php`
- `web/api/auth/change-password.php`
- `web/api/auth/reset-password-request.php`
- `web/api/auth/reset-password-complete.php`

### Web Components
- `web/admin/includes/password-settings.php`
- `web/user/includes/password-settings.php`

### iOS Views
- `QRCard/PasswordSettingsView.swift`
- `QRCard/ForgotPasswordView.swift`

### Database
- `web/config/migrations/007_add_failed_login_tracking.sql`

### Helpers
- `web/api/includes/PasswordValidator.php` (password strength validation)
- `web/api/includes/LoginAttemptTracker.php` (rate limiting)

---

## Files to Modify

### Backend
- `web/api/auth/login.php` (add has_password check)
- `web/api/includes/EmailTemplates.php` (restore passwordReset, add new templates)

### Web
- `web/admin/login.php` (add password field option)
- `web/user/login.php` (add password field option)
- `web/admin/dashboard.php` (add password settings section)
- `web/user/dashboard.php` (add password settings section)

### iOS
- `QRCard/LoginView.swift` (add password field + toggle)
- `QRCard/AuthService.swift` (add password methods)
- `QRCard/ContentView.swift` (add navigation to password settings)
- `QRCard/APIClient.swift` (add password endpoints)

---

## Estimated Timeline

- **Phase 1 (Backend)**: 2-3 days
- **Phase 2 (Web)**: 2-3 days
- **Phase 3 (iOS)**: 3-4 days
- **Phase 4 (Testing)**: 2-3 days
- **Total**: 9-13 days

---

## Success Metrics

- [ ] All existing users can still login with email codes
- [ ] Users can successfully set passwords
- [ ] Users can login with passwords
- [ ] Password reset flow works correctly
- [ ] Rate limiting prevents brute force attacks
- [ ] iOS app passes Apple review
- [ ] Zero disruption to current users
- [ ] Security audit passes (no password vulnerabilities)

## To-dos

- [ ] Create password management API endpoints (set, change, reset-request, reset-complete)
- [ ] Update login API to check for password and support dual authentication
- [ ] Create PasswordValidator and LoginAttemptTracker helper classes
- [ ] Create and run migration for failed_login_attempts table
- [ ] Restore passwordReset() and add passwordSet(), passwordChanged() email templates
- [ ] Create admin password settings component and integrate into dashboard
- [ ] Create user password settings component and integrate into dashboard
- [ ] Update admin login page to support password or email code authentication
- [ ] Update user login page to support password or email code authentication
- [ ] Add password authentication methods to iOS AuthService
- [ ] Update iOS LoginView to support password entry with toggle to email code
- [ ] Create iOS PasswordSettingsView for setting/changing passwords
- [ ] Create iOS ForgotPasswordView for password reset flow
- [ ] Add password-related endpoints to iOS APIClient
- [ ] Test all password flows in admin web interface
- [ ] Test all password flows in user web interface
- [ ] Test all password flows in iOS app
- [ ] Verify existing email code flow still works for all users
- [ ] Perform security audit of password implementation
- [ ] Deploy backend API and migrations to production
- [ ] Deploy updated web interfaces to production
- [ ] Submit updated iOS app to App Store with password authentication

