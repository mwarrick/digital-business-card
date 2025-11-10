# Account Deletion Feature Implementation Plan

## Overview

Implement a complete account deletion system to comply with Google's App Store policies. This includes a public information page (no login required) and a secure deletion page for logged-in users that requires password confirmation.

## Database Deletion Order

All data must be explicitly deleted one table at a time by `user_id`. DO NOT rely on CASCADE deletes. Delete in this specific order to avoid constraint violations:

### Phase 1: Media Files (Physical Storage)
- Delete all media files from `/web/storage/media/` directory
- Files to delete: `profile_photo_path`, `company_logo_path`, `cover_graphic_path` from all user's business cards
- Also check legacy fields: `profile_photo`, `company_logo`, `cover_graphic`

### Phase 2: Child Tables (Delete by user_id or related IDs)
1. **verification_codes** - Delete by `user_id`
2. **auth_tokens** - Delete by `user_id`
3. **image_creation_log** - Delete by `user_id`
4. **demo_data** - Delete by `user_id` (if exists)
5. **contacts** - Delete by `user_id`
6. **invitations** - Delete by `user_id`
7. **leads** - Delete by `user_id`
8. **qr_leads** - Delete by `qr_id` (first get all `qr_id` values from user's custom_qr_codes)
9. **custom_qr_events** - Delete by `qr_id` (use same QR IDs from step 8)
10. **analytics_events** - Delete by `card_id` (first get all `card_id` values from user's business_cards)
11. **analytics_sessions** - Delete by `card_id` (use same card IDs from step 10)
12. **analytics_daily** - Delete by `card_id` (use same card IDs from step 10)

### Phase 3: Business Card Related Tables (Delete by card_id)
13. **card_emails** - Delete by `card_id` (get all card IDs from user's business_cards)
14. **card_phones** - Delete by `card_id` (use same card IDs)
15. **website_links** - Delete by `card_id` (use same card IDs)
16. **addresses** - Delete by `card_id` (use same card IDs)

### Phase 4: Parent Tables
17. **business_cards** - Delete by `user_id`
18. **custom_qr_codes** - Delete by `user_id`

### Phase 5: User Record
19. **users** - Delete by `id` (final step)

## Implementation Components

### 1. Public Information Page
**File**: `web/delete-account.php` (new file)

- No authentication required
- Explain account deletion process
- List what data will be deleted
- Provide instructions:
  - Must be logged in to delete
  - Password confirmation required
  - If password forgotten, email mark@sharemycard.app
- Link to login page and account deletion page (if logged in)

### 2. User Dashboard Integration
**File**: `web/user/dashboard.php`

- Add new card after "Security" card (around line 503)
- Card should link to `/user/delete-account.php`
- Icon: 🗑️ or ⚠️
- Title: "Delete Account"
- Description: "Permanently delete your account and all data"

### 3. Delete Account Page (Logged In)
**File**: `web/user/delete-account.php` (new file)

**Requirements:**
- Require authentication (`UserAuth::requireAuth()`)
- Display warning about permanent deletion
- Password confirmation form
- Password verification using `password_verify()`
- Handle users without passwords (email code users)
- Instructions for password reset if forgotten
- Link to public information page

**UI Elements:**
- Warning banner about permanent action
- List of data that will be deleted
- Password input field
- "Confirm Deletion" button (disabled until password entered)
- "Cancel" button
- Error/success messages

### 4. Account Deletion API Endpoint
**File**: `web/user/api/delete-account.php` (new file)

**Functionality:**
- Require authentication
- Verify password (if user has one)
- Handle users without passwords (require email verification or special handling)
- Execute deletion in transaction
- Delete all data in correct order (as specified above)
- Return success/error response

**Deletion Logic:**
```php
// 1. Get all user's business cards and QR codes (to get IDs for related tables)
// 2. Delete media files
// 3. Delete verification_codes by user_id
// 4. Delete auth_tokens by user_id
// 5. Delete image_creation_log by user_id
// 6. Delete demo_data by user_id (if exists)
// 7. Delete contacts by user_id
// 8. Delete invitations by user_id
// 9. Delete leads by user_id
// 10. Get all qr_id values from custom_qr_codes
// 11. Delete qr_leads by qr_id
// 12. Delete custom_qr_events by qr_id
// 13. Get all card_id values from business_cards
// 14. Delete analytics_events by card_id
// 15. Delete analytics_sessions by card_id
// 16. Delete analytics_daily by card_id
// 17. Delete card_emails by card_id
// 18. Delete card_phones by card_id
// 19. Delete website_links by card_id
// 20. Delete addresses by card_id
// 21. Delete business_cards by user_id
// 22. Delete custom_qr_codes by user_id
// 23. Delete users by id
// 24. Commit transaction
// 25. Logout user and redirect
```

### 5. Update Privacy Policy
**File**: `web/privacy.php`

- Update "Delete Account" section (line 274)
- Change from "Contact us at mark@warrick.net" to link to `/delete-account.php`
- Update "Account Deletion" section (line 283) to reflect new self-service process

### 6. Account Security Modal Update
**File**: `web/user/includes/account-security-modal.php`

- Add link/button to delete account page
- Place after password management section
- Style as warning/danger button

## Technical Considerations

### Password Verification
- Use `password_verify($password, $user['password_hash'])` 
- Handle users without passwords (password_hash is NULL)
- For users without passwords, consider:
  - Requiring email verification code, OR
  - Allowing deletion with email confirmation sent to their account

### Transaction Safety
- Wrap entire deletion in database transaction
- Rollback on any error
- Log all deletion steps for audit trail

### Error Handling
- Gracefully handle missing tables (some may not exist in all deployments)
- Continue deletion even if some media files are missing
- Log errors but don't fail entire deletion if non-critical steps fail
- Use try-catch blocks for each table deletion to handle missing tables gracefully

### Security
- Rate limit deletion endpoint (prevent abuse)
- Require password confirmation (even if user is logged in)
- Log deletion events for audit
- Send confirmation email before deletion (optional enhancement)

### Media File Deletion
- Check both new path fields (`*_path`) and legacy fields
- Use absolute paths from `__DIR__ . '/../../storage/media/'`
- Verify file exists before attempting deletion
- Handle permission errors gracefully

### Explicit Deletion Pattern
- For each table, explicitly query and delete by the appropriate ID
- Do NOT rely on CASCADE deletes
- Get related IDs first (card_ids, qr_ids) before deleting child tables
- Use prepared statements for all deletions

## File Structure

```
web/
├── delete-account.php (new - public info page)
├── privacy.php (update - link to delete page)
└── user/
    ├── dashboard.php (update - add delete account card)
    ├── delete-account.php (new - deletion page)
    ├── includes/
    │   └── account-security-modal.php (update - add delete link)
    └── api/
        └── delete-account.php (new - deletion API)
```

## Testing Checklist

1. Test deletion with user that has password
2. Test deletion with user that has no password (email-only auth)
3. Test deletion with user that has business cards
4. Test deletion with user that has custom QR codes
5. Test deletion with user that has leads/contacts
6. Test deletion with user that has media files
7. Test password verification failure
8. Test transaction rollback on error
9. Test public page accessibility (no login)
10. Test logged-in page requires authentication
11. Verify all related data is deleted from each table explicitly
12. Verify media files are deleted from storage
13. Verify no CASCADE deletes are used
14. Test deletion with missing tables (graceful handling)

## Notes

- Reference existing admin deletion code in `web/admin/api/delete-user.php` for deletion pattern, but modify to explicitly delete each table
- All tables must be deleted explicitly by user_id or related IDs - NO CASCADE deletes
- Some tables may not have foreign key constraints - delete explicitly to be safe
- Consider adding a "soft delete" option in the future (mark as deleted, purge after 30 days)
- Update privacy policy to reflect immediate deletion vs. 30-day retention
- Log each deletion step for audit purposes

