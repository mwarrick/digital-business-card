# Login Routine Cleanup Plan

## Problem

The system is advertised as "passwordless authentication" but contains several inconsistencies:

1. **Database schema requires `password_hash`** - The `users` table has a required `password_hash` column
2. **Dummy password hashes are created** - Both registration endpoints create meaningless password hashes:

   - `web/user/register.php` line 93: `password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT)`
   - `web/api/auth/register.php` line 69: `password_hash('not-used-email-verification-only', PASSWORD_DEFAULT)`

3. **Unused password reset template** - `EmailTemplates::passwordReset()` exists but is never used
4. **Documentation inconsistency** - Original schema doc doesn't show `password_hash`, but production DB has it

## Solution Approach

Make the system truly passwordless by:

1. Making `password_hash` nullable in the database
2. Removing dummy password hash generation
3. Documenting or removing the unused password reset template
4. Updating documentation to reflect current state

## Files to Modify

### 1. Database Migration

**Create:** `web/config/migrations/006_make_password_hash_nullable.sql`

```sql
-- Make password_hash nullable for true passwordless authentication
ALTER TABLE users 
MODIFY COLUMN password_hash VARCHAR(255) NULL DEFAULT NULL;

-- Update existing dummy hashes to NULL
UPDATE users 
SET password_hash = NULL 
WHERE password_hash IS NOT NULL;
```

### 2. Registration - Web Interface

**File:** `web/user/register.php`

**Lines 90-94:** Remove password_hash from INSERT statement

```php
// BEFORE:
$db->execute(
    "INSERT INTO users (id, email, password_hash, is_active, is_admin) 
     VALUES (?, ?, ?, 0, 0)",
    [$userId, $email, password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT)]
);

// AFTER:
$db->execute(
    "INSERT INTO users (id, email, is_active, is_admin) 
     VALUES (?, ?, 0, 0)",
    [$userId, $email]
);
```

### 3. Registration - API

**File:** `web/api/auth/register.php`

**Lines 67-74:** Remove password_hash from INSERT statement and comment

```php
// BEFORE:
// Note: password_hash is required by schema but not used (email verification only)
$dummyHash = password_hash('not-used-email-verification-only', PASSWORD_DEFAULT);
$this->db->execute(
    "INSERT INTO users (id, email, password_hash, is_active, created_at, updated_at) 
     VALUES (?, ?, ?, 0, NOW(), NOW())",
    [$userId, $email, $dummyHash]
);

// AFTER:
$this->db->execute(
    "INSERT INTO users (id, email, is_active, created_at, updated_at) 
     VALUES (?, ?, 0, NOW(), NOW())",
    [$userId, $email]
);
```

### 4. Email Templates - Document or Remove

**File:** `web/api/includes/EmailTemplates.php`

**Lines 115-142:** Either:

- Option A: Remove `passwordReset()` method entirely (recommended if no password feature planned)
- Option B: Add a comment: `// Reserved for future password authentication feature (not currently used)`

### 5. Update Documentation

**File:** `QRCard/docsdatabase-schema.md`

**Lines 5-13:** Add password_hash field to match production reality

```sql
CREATE TABLE users (
    id CHAR(36) PRIMARY KEY,  -- UUID format
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NULL DEFAULT NULL,  -- Optional: for future password auth
    is_active TINYINT(1) DEFAULT 0,
    is_admin TINYINT(1) DEFAULT 0,
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Testing Checklist

After implementation:

- [ ] Run migration on development database
- [ ] Test web registration (should create user with NULL password_hash)
- [ ] Test API registration via iOS app (should create user with NULL password_hash)
- [ ] Test login flow still works (email verification codes)
- [ ] Verify existing users can still log in
- [ ] Check database to confirm no dummy hashes are being created
- [ ] Test on production with backup database first

## Deployment Steps

1. Backup production database
2. Run migration: `006_make_password_hash_nullable.sql`
3. Deploy updated PHP code
4. Test registration and login on production
5. Monitor for any authentication errors

## Future Considerations

If password authentication is desired in the future:

- Keep the `password_hash` column (now nullable)
- Add a `has_password` boolean flag to distinguish users
- Allow users to optionally set a password in addition to email codes
- Update login flow to check for password first, fall back to email codes

## To-dos

- [ ] Create migration file to make password_hash nullable and clear existing dummy hashes
- [ ] Remove password_hash from web registration INSERT statement
- [ ] Remove password_hash from API registration INSERT statement and misleading comment
- [ ] Remove or document unused passwordReset() email template method
- [ ] Update database schema documentation to reflect production reality
- [ ] Test both web and API registration flows work with nullable password_hash
- [ ] Verify login flow still works correctly after changes
- [ ] Deploy to production with database backup and migration

