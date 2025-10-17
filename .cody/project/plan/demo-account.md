# Demo Account Implementation Plan

## Overview

Add a demo account system to satisfy Apple TestFlight reviewer requirements. The demo account will allow instant login without authentication, pre-populate with sample business cards, and suppress all outbound emails.

## Database Changes

### Add User Role Column

**Create Migration:** `web/config/migrations/008_add_user_role.sql`

```sql
-- Add role column to users table
ALTER TABLE users 
ADD COLUMN role ENUM('user', 'admin', 'demo') DEFAULT 'user' AFTER is_admin;

-- Update existing admin users
UPDATE users SET role = 'admin' WHERE is_admin = 1;

-- Create demo user
INSERT INTO users (id, email, password_hash, is_active, is_admin, role, created_at, updated_at)
VALUES (
    'demo-user-uuid-fixed',
    'demo@sharemycard.app',
    NULL,
    1,
    0,
    'demo',
    NOW(),
    NOW()
);
```

### Add Demo Sample Business Cards

**Create Migration:** `web/config/migrations/009_add_demo_business_cards.sql`

Create 3 sample business cards for demo user:
1. Tech Professional (with profile photo, company logo, cover graphic)
2. Creative Designer (with profile photo, cover graphic, multiple emails/phones)
3. Business Executive (complete profile with all features)

### Add Card Deletion Support

No database changes needed - use CASCADE DELETE on existing foreign keys.

## Backend Changes

### 1. Demo User Detection Helper

**Create:** `web/api/includes/DemoUserHelper.php`

```php
class DemoUserHelper {
    public static function isDemoUser($email) {
        return strtolower($email) === 'demo@sharemycard.app';
    }
    
    public static function isDemoUserId($userId) {
        return $userId === 'demo-user-uuid-fixed';
    }
    
    public static function shouldSuppressEmail($email = null, $userId = null) {
        if ($email && self::isDemoUser($email)) return true;
        if ($userId && self::isDemoUserId($userId)) return true;
        return false;
    }
}
```

### 2. Update Email Sending

**Modify:** `web/api/includes/GmailClient.php`

Add check before all email sends:

```php
public static function sendEmail($to, $subject, $html, $text = null) {
    // Suppress emails for demo users
    if (DemoUserHelper::isDemoUser($to)) {
        error_log("Email suppressed for demo user: $to");
        return true; // Simulate success
    }
    
    // ... existing email sending code
}
```

### 3. Demo Login Bypass

**Modify:** `web/api/auth/login.php`

Add demo user detection at the start:

```php
// Check for demo user
if (DemoUserHelper::isDemoUser($email)) {
    // Skip all authentication
    $user = $this->db->querySingle(
        "SELECT id, email, is_active, is_admin, role FROM users WHERE email = ?",
        [$email]
    );
    
    // Return immediate success (no code sent)
    $this->success([
        'user_id' => $user['id'],
        'email' => $user['email'],
        'is_admin' => false,
        'has_password' => false,
        'verification_code_sent' => false,
        'is_demo' => true,
        'message' => 'Demo user - no verification needed'
    ]);
    return;
}
```

**Modify:** `web/api/auth/verify.php`

Add demo user bypass:

```php
// Check for demo user - skip all verification
if (DemoUserHelper::isDemoUser($email)) {
    $user = $this->db->querySingle(
        "SELECT id, email, is_active, is_admin, role FROM users WHERE email = ?",
        [$email]
    );
    
    // Generate JWT token immediately
    $token = $this->generateJWT($user['id'], $user['email'], $user['is_admin']);
    
    $this->success([
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'is_admin' => (bool)$user['is_admin'],
            'is_demo' => true
        ]
    ]);
    return;
}
```

### 4. Card Deletion API

**Create:** `web/api/cards/delete.php`

```php
class DeleteCard extends Api {
    protected function handleRequest() {
        $cardId = $this->data['card_id'] ?? null;
        
        if (!$cardId) {
            $this->error('Card ID required', 400);
        }
        
        // Verify card belongs to user
        $card = $this->db->querySingle(
            "SELECT id, user_id FROM business_cards WHERE id = ?",
            [$cardId]
        );
        
        if (!$card || $card['user_id'] !== $this->userId) {
            $this->error('Card not found or unauthorized', 404);
        }
        
        // Delete card (cascade will delete related data)
        $this->db->execute(
            "DELETE FROM business_cards WHERE id = ?",
            [$cardId]
        );
        
        $this->success(['message' => 'Card deleted successfully']);
    }
}
```

**Update Router:** `web/router.php`

Add route: `'/api/cards/delete' => 'api/cards/delete.php'`

### 5. Login As Feature for Admins

**Create:** `web/admin/api/login-as.php`

```php
// Admin-only session-authenticated endpoint
require_once __DIR__ . '/../includes/AdminAuth.php';

if (!AdminAuth::isLoggedIn() || !AdminAuth::isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_POST['user_id'] ?? null;

// Create special "impersonation" session
$_SESSION['impersonated_user_id'] = $userId;
$_SESSION['impersonating_admin_id'] = $_SESSION['user_id'];
$_SESSION['impersonation_started'] = time();

echo json_encode([
    'success' => true,
    'redirect_url' => '/user/dashboard.php?impersonated=true'
]);
```

## Web Interface Changes

### 1. Admin Dashboard Updates

**Modify:** `web/admin/users.php`

In the users table, change "Actions" column:
- Replace "View" button with "Edit" button
- Add "Login As" button (opens new window/tab)

```php
<td class="actions">
    <a href="/admin/users/edit.php?id=<?= $user['id'] ?>" class="btn-edit">Edit</a>
    <button onclick="loginAsUser('<?= $user['id'] ?>')" class="btn-login-as">Login As</button>
</td>
```

JavaScript:

```javascript
function loginAsUser(userId) {
    if (!confirm('Login as this user? A new window will open.')) return;
    
    fetch('/admin/api/login-as.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'user_id=' + userId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.open(data.redirect_url, '_blank');
        }
    });
}
```

**Modify:** `web/admin/login.php`

Remove "Admin login" link from anywhere it appears in headers/navigation.

### 2. User Dashboard Updates

**Modify:** `web/user/dashboard.php`

Add delete button to each card:

```php
<button onclick="deleteCard('<?= $card['id'] ?>')" class="btn-delete">Delete Card</button>
```

JavaScript:

```javascript
function deleteCard(cardId) {
    if (!confirm('Delete this business card? This will also delete all analytics and media.')) return;
    
    fetch('/api/cards/delete', {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + getJwtToken(),
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ card_id: cardId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}
```

**Modify:** `web/user/cards/view.php`

Add delete button on card view page.

### 3. Homepage Updates

**Modify:** `web/index.php`

Add "Demo Login" button in the CTA section:

```php
<div class="cta-buttons">
    <a href="/user/login.php" class="btn btn-primary">Login to Your Account</a>
    <a href="/user/register.php" class="btn btn-secondary">Create Account</a>
    <a href="/user/login.php?demo=true" class="btn btn-demo">Demo Login</a>
</div>
```

CSS for demo button:

```css
.btn-demo {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.3);
}
```

**Modify:** `web/user/login.php`

Detect demo parameter and auto-populate email:

```php
$isDemoLogin = isset($_GET['demo']) && $_GET['demo'] === 'true';
$prefillEmail = $isDemoLogin ? 'demo@sharemycard.app' : '';

// Auto-submit for demo
if ($isDemoLogin && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Auto-redirect to verification with demo email
    $_SESSION['pending_user_email'] = 'demo@sharemycard.app';
    header('Location: /user/login.php');
    exit;
}
```

Handle demo login bypass in the POST handler (similar to admin login).

### 4. Login Page Updates

**Modify:** `web/admin/login.php` and `web/user/login.php`

Add demo user detection:

```php
// Check for demo user at email submission step
if (DemoUserHelper::isDemoUser($email)) {
    // Skip all auth, create session immediately
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $email;
    $_SESSION['is_admin'] = false;
    $_SESSION['is_demo'] = true;
    header('Location: /user/dashboard.php');
    exit;
}
```

## iOS App Changes

### 1. Update Login View

**Modify:** `QRCard/LoginView.swift`

Add demo login button below the email/continue section:

```swift
VStack(spacing: 20) {
    // ... existing email input and continue button
    
    Button(action: handleDemoLogin) {
        HStack {
            Image(systemName: "person.crop.circle.fill")
            Text("Demo Login")
        }
        .frame(maxWidth: .infinity)
        .padding()
        .background(
            LinearGradient(colors: [.purple, .pink], startPoint: .leading, endPoint: .trailing)
        )
        .foregroundColor(.white)
        .cornerRadius(10)
    }
}
```

### 2. Update Auth Service

**Modify:** `QRCard/AuthService.swift`

Add demo login method:

```swift
func loginDemo() async throws -> User {
    // Special handling for demo user
    let response: LoginResponse = try await apiClient.request(
        endpoint: "/auth/login",
        method: "POST",
        body: ["email": "demo@sharemycard.app"]
    )
    
    // Demo user gets immediate access
    if response.isDemo == true {
        let verifyResponse: VerifyResponse = try await apiClient.request(
            endpoint: "/auth/verify",
            method: "POST",
            body: ["email": "demo@sharemycard.app"]
        )
        
        // Store token
        try KeychainHelper.save(token: verifyResponse.token, for: "jwt_token")
        
        return verifyResponse.user
    }
    
    throw AuthError.loginFailed
}
```

Update `LoginResponse` struct:

```swift
struct LoginResponse: Decodable {
    let userId: String?
    let email: String?
    let hasPassword: Bool?
    let verificationCodeSent: Bool?
    let isDemo: Bool?
    let message: String?
}
```

### 3. Add Demo Login Handler

**Modify:** `QRCard/LoginView.swift`

```swift
private func handleDemoLogin() {
    isLoading = true
    errorMessage = nil
    
    Task {
        do {
            let user = try await authService.loginDemo()
            await MainActor.run {
                isLoggedIn = true
            }
        } catch {
            await MainActor.run {
                errorMessage = "Demo login failed: \(error.localizedDescription)"
                isLoading = false
            }
        }
    }
}
```

### 4. Email Suppression

No changes needed - iOS app doesn't send emails directly. Backend suppression handles all email sends.

## Testing Checklist

### Web - Demo Login
- [ ] Demo button appears on homepage
- [ ] Clicking demo button logs in as demo user
- [ ] Demo user can view pre-populated cards
- [ ] Demo user can create new cards
- [ ] Demo user can delete cards
- [ ] Demo user can set password (no email sent)
- [ ] No emails sent to demo@sharemycard.app

### Web - Login As Feature
- [ ] "Login As" button appears for admins
- [ ] Clicking opens new window with user session
- [ ] Admin can edit user's cards
- [ ] Closing window expires impersonation session
- [ ] Regular users don't see "Login As" button

### Web - Card Deletion
- [ ] Delete button appears on user dashboard
- [ ] Delete button appears on card view page
- [ ] Deletion requires confirmation
- [ ] Card is deleted from database
- [ ] Analytics entries are deleted (cascade)
- [ ] Media files are deleted (cascade)
- [ ] User can't delete other users' cards

### iOS - Demo Login
- [ ] Demo login button appears on login screen
- [ ] Clicking demo button logs in immediately
- [ ] Demo user can view pre-populated cards
- [ ] Demo user can create new cards
- [ ] Demo user can delete cards
- [ ] No emails are triggered

### General
- [ ] Demo user cannot receive emails
- [ ] Demo user has full functionality (no restrictions)
- [ ] Demo user can set/change password
- [ ] "Admin login" link removed from homepage
- [ ] Migration creates demo user successfully
- [ ] Migration creates 3 sample cards

## Implementation Order

1. **Database Migrations** (008 and 009) - creates demo user and sample cards
2. **Backend Email Suppression** - DemoUserHelper and GmailClient updates
3. **Backend Demo Login Bypass** - login.php and verify.php updates
4. **Backend Card Deletion** - delete.php endpoint
5. **Backend Login As** - admin API endpoint
6. **Web Admin Updates** - users.php changes
7. **Web User Updates** - dashboard and card deletion
8. **Web Homepage** - demo button and layout updates
9. **Web Login Pages** - demo detection and bypass
10. **iOS Login View** - demo button UI
11. **iOS Auth Service** - demo login method
12. **Testing** - comprehensive testing across all platforms

## Files to Create

- `web/config/migrations/008_add_user_role.sql`
- `web/config/migrations/009_add_demo_business_cards.sql`
- `web/api/includes/DemoUserHelper.php`
- `web/api/cards/delete.php`
- `web/admin/api/login-as.php`

## Files to Modify

### Backend
- `web/api/includes/GmailClient.php`
- `web/api/auth/login.php`
- `web/api/auth/verify.php`
- `web/router.php`

### Web Interface
- `web/index.php`
- `web/admin/login.php`
- `web/admin/users.php`
- `web/user/login.php`
- `web/user/dashboard.php`
- `web/user/cards/view.php`

### iOS
- `QRCard/LoginView.swift`
- `QRCard/AuthService.swift`

### Documentation
- `QRCard/docsdatabase-schema.md`

