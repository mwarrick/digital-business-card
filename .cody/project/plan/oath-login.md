# OAuth Login Integration Plan

## Overview
Add OAuth authentication for Apple, Google, and LinkedIn to support social login during registration and as an additional authentication option for existing accounts. OAuth providers will be manually linked through account settings, with the original authentication method remaining primary.

## Database Schema Changes

### New Table: `oauth_providers`
```sql
CREATE TABLE oauth_providers (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    provider ENUM('google', 'apple', 'linkedin') NOT NULL,
    provider_user_id VARCHAR(255) NOT NULL,
    provider_email VARCHAR(255),
    provider_name VARCHAR(255),
    provider_picture VARCHAR(500),
    access_token TEXT,
    refresh_token TEXT,
    token_expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_provider_user (provider, provider_user_id),
    INDEX idx_user_provider (user_id, provider)
);
```

**Migration file**: `web/config/migrations/019_add_oauth_providers.sql`

### Update `users` table
Add column to track OAuth account status:
```sql
ALTER TABLE users ADD COLUMN has_oauth TINYINT(1) DEFAULT 0 AFTER password_hash;
```

**Migration file**: `web/config/migrations/020_add_oauth_flag_to_users.sql`

## OAuth Configuration

### New file: `web/config/oauth.php`
Store OAuth credentials for all three providers:
```php
<?php
// Google OAuth
define('GOOGLE_CLIENT_ID', 'your-google-client-id');
define('GOOGLE_CLIENT_SECRET', 'your-google-client-secret');
define('GOOGLE_REDIRECT_URI', 'https://sharemycard.app/api/auth/oauth-callback?provider=google');

// Apple OAuth
define('APPLE_CLIENT_ID', 'your-apple-service-id');
define('APPLE_TEAM_ID', 'your-apple-team-id');
define('APPLE_KEY_ID', 'your-apple-key-id');
define('APPLE_PRIVATE_KEY_PATH', '/path/to/apple-private-key.p8');
define('APPLE_REDIRECT_URI', 'https://sharemycard.app/api/auth/oauth-callback?provider=apple');

// LinkedIn OAuth
define('LINKEDIN_CLIENT_ID', 'your-linkedin-client-id');
define('LINKEDIN_CLIENT_SECRET', 'your-linkedin-client-secret');
define('LINKEDIN_REDIRECT_URI', 'https://sharemycard.app/api/auth/oauth-callback?provider=linkedin');
```

**Template file**: `web/config/oauth.php.template`

## API Endpoints

### 1. OAuth Initialization: `web/api/auth/oauth-init.php`
**Purpose**: Generate OAuth authorization URL and redirect user to provider
**Parameters**: `provider` (google|apple|linkedin), `action` (login|register|link)
**Flow**:
- Validate provider and action
- Generate state token (UUID) and store in session with action and timestamp
- Build provider-specific authorization URL
- Redirect to OAuth provider

### 2. OAuth Callback: `web/api/auth/oauth-callback.php`
**Purpose**: Handle OAuth provider callback and process authentication
**Parameters**: `provider`, `code`, `state`
**Flow**:
- Validate state token against session
- Exchange authorization code for access token
- Fetch user profile from provider
- Based on action from state:
  - **login**: Check if provider account exists, log user in or show error
  - **register**: Create new user, link OAuth provider, optionally import profile
  - **link**: Require authenticated session, link provider to current user

### 3. OAuth Link: `web/api/auth/oauth-link.php`
**Purpose**: Link OAuth provider to existing authenticated account
**Method**: POST
**Parameters**: `provider` (google|apple|linkedin)
**Authentication**: Requires active user session
**Flow**:
- Verify user is authenticated
- Check provider not already linked to this user
- Initiate OAuth flow with action='link'

### 4. OAuth Unlink: `web/api/auth/oauth-unlink.php`
**Purpose**: Remove OAuth provider from account
**Method**: POST
**Parameters**: `provider` (google|apple|linkedin)
**Authentication**: Requires active user session
**Flow**:
- Verify user is authenticated
- Check user has at least one other auth method (password or email)
- Delete OAuth provider record
- Update `users.has_oauth` flag

### 5. OAuth Login: `web/api/auth/oauth-login.php`
**Purpose**: Check OAuth connection status
**Method**: GET
**Parameters**: `provider` (google|apple|linkedin)
**Authentication**: Requires active user session
**Returns**: Connection status and provider email

## OAuth Service Class

### New file: `web/api/includes/OAuthService.php`
Centralized OAuth handling for all three providers:

```php
class OAuthService {
    private $db;
    
    // Provider-specific methods
    public function getAuthorizationUrl($provider, $state) { }
    public function exchangeCodeForToken($provider, $code) { }
    public function getUserProfile($provider, $accessToken) { }
    
    // Database operations
    public function linkProvider($userId, $provider, $providerData) { }
    public function unlinkProvider($userId, $provider) { }
    public function findUserByProvider($provider, $providerUserId) { }
    public function getLinkedProviders($userId) { }
    
    // Apple-specific: JWT generation for client_secret
    private function generateAppleClientSecret() { }
    
    // LinkedIn-specific: API v2 profile fetch
    private function fetchLinkedInProfile($accessToken) { }
}
```

## Web Interface Updates

### User Registration Page: `web/user/register.php`
Add OAuth buttons above the email input:
```html
<div class="oauth-login-section">
    <h3>Sign up with</h3>
    <div class="oauth-buttons">
        <a href="/api/auth/oauth-init.php?provider=google&action=register" class="oauth-btn google">
            <img src="/images/google-icon.svg" alt="Google"> Continue with Google
        </a>
        <a href="/api/auth/oauth-init.php?provider=apple&action=register" class="oauth-btn apple">
            <img src="/images/apple-icon.svg" alt="Apple"> Continue with Apple
        </a>
        <a href="/api/auth/oauth-init.php?provider=linkedin&action=register" class="oauth-btn linkedin">
            <img src="/images/linkedin-icon.svg" alt="LinkedIn"> Continue with LinkedIn
        </a>
    </div>
    <div class="divider">or</div>
</div>
```

### User Login Page: `web/user/login.php`
Add OAuth buttons above the email input (same structure as registration)

### Account Settings Modal: `web/user/includes/account-security-modal.php`
Add new "Connected Accounts" section:
```html
<div class="connected-accounts-section">
    <h3>Connected Accounts</h3>
    <p>Link social accounts for quick and easy login</p>
    
    <!-- Google -->
    <div class="provider-row">
        <div class="provider-info">
            <img src="/images/google-icon.svg" alt="Google">
            <span>Google</span>
        </div>
        <?php if ($googleLinked): ?>
            <button onclick="unlinkProvider('google')" class="btn-unlink">Disconnect</button>
        <?php else: ?>
            <a href="/api/auth/oauth-init.php?provider=google&action=link" class="btn-link">Connect</a>
        <?php endif; ?>
    </div>
    
    <!-- Similar for Apple and LinkedIn -->
</div>
```

### Profile Import Dialog: `web/user/oauth-import-profile.php`
New page shown after successful OAuth registration:
```html
<h2>Import Profile Information?</h2>
<p>We found the following information from your [Provider] account:</p>
<div class="profile-preview">
    <img src="[provider_picture]" alt="Profile Photo">
    <p><strong>Name:</strong> [provider_name]</p>
    <p><strong>Email:</strong> [provider_email]</p>
</div>
<form method="POST">
    <label>
        <input type="checkbox" name="import_name" checked> Import name
    </label>
    <label>
        <input type="checkbox" name="import_photo" checked> Import profile photo
    </label>
    <button type="submit" name="action" value="import">Import Selected</button>
    <button type="submit" name="action" value="skip">Skip</button>
</form>
```

## iOS App Integration

### Update AuthService: `QRCard/AuthService.swift`
Add OAuth login methods:
```swift
func loginWithOAuth(provider: String, accessToken: String) async throws -> User { }
func linkOAuthProvider(provider: String, accessToken: String) async throws { }
func unlinkOAuthProvider(provider: String) async throws { }
func getLinkedProviders() async throws -> [String] { }
```

### Add OAuth Views:
- `OAuthLoginView.swift` - OAuth provider selection for login
- `AccountSettingsView.swift` - Update to show linked providers
- Use native AuthenticationServices for Apple Sign In
- Use ASWebAuthenticationSession for Google and LinkedIn

## Security Considerations

1. **State Token Validation**: All OAuth flows use cryptographically secure state tokens stored in session
2. **CSRF Protection**: State tokens prevent CSRF attacks
3. **Token Storage**: OAuth tokens encrypted at rest in database
4. **Rate Limiting**: Apply same rate limits as email/password authentication
5. **Account Lockout**: Prevent account takeover by requiring authentication before linking
6. **Minimum Auth Methods**: Users must have at least one auth method (can't unlink all OAuth if no password)

## Testing Strategy

1. **OAuth Registration Flow**:
   - Register new user via Google/Apple/LinkedIn
   - Verify account created and marked as verified
   - Test profile import acceptance and rejection

2. **OAuth Login Flow**:
   - Login with linked OAuth provider
   - Verify session creation and dashboard access

3. **Account Linking Flow**:
   - Login with email/password
   - Link Google/Apple/LinkedIn accounts
   - Verify provider saved and accessible

4. **Account Unlinking Flow**:
   - Unlink OAuth provider
   - Verify cannot unlink if it's the only auth method
   - Test login still works with remaining methods

5. **Error Handling**:
   - Test expired OAuth tokens
   - Test OAuth provider errors
   - Test email conflicts (OAuth email matches existing user)

## Documentation Updates

Update `README.md`:
- Add OAuth authentication to feature list
- Document supported OAuth providers
- Add setup instructions for OAuth credentials

Update `PRIVACY-POLICY.md`:
- Disclose OAuth data collection
- Explain what profile data is imported
- Document data sharing with OAuth providers

## Implementation Order

1. Database migrations (019, 020)
2. OAuth configuration files
3. OAuthService class
4. API endpoints (init, callback, link, unlink)
5. Web interface updates (register, login, account settings)
6. Profile import dialog
7. iOS app integration
8. Testing and documentation
