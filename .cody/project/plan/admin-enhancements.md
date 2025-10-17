# Admin User Management Enhancement Plan

## Overview
Enhance admin capabilities to provide complete user management control including editing all user data, full deletion with cascade cleanup, user impersonation ("login as"), clickable statistics cards, and admin role management with self-demotion protection.

## Implementation Steps

### 1. User Edit Modal System
**Files:** `web/admin/users.php`, `web/admin/api/edit-user.php` (new)

Create a comprehensive user editing modal on the users.php page:
- Add "Edit" button to each user row
- Create modal with form fields:
  - Email (with validation)
  - Password (optional, show/hide toggle)
  - Active/Inactive status (toggle)
  - Admin/User role (dropdown)
  - Created date (read-only display)
- Implement session-based API endpoint `/admin/api/edit-user.php`
- Validate that admins cannot demote themselves (check current admin ID vs target user ID)
- Update `users` table with all modified fields
- Show success/error messages in modal

### 2. Complete User Deletion with Cascade
**Files:** `web/admin/users.php`, `web/admin/api/delete-user.php` (new)

Implement hard delete (not soft delete) with full cascade cleanup:
- Add "Delete User" button with warning modal
- Create session-based API endpoint `/admin/api/delete-user.php`
- Delete in this order (cascade):
  1. Analytics data (`analytics_daily`, `analytics_events`, `analytics_sessions`)
  2. Media references (profile photos, logos, cover graphics)
  3. Contact information (`email_contacts`, `phone_contacts`, `website_links`, `addresses`)
  4. Business cards (`business_cards`)
  5. Verification codes (`verification_codes`)
  6. User record (`users`)
- Wrap in database transaction for atomicity
- Prevent deletion of demo user (role = 'demo')
- Show confirmation modal with list of what will be deleted

### 3. "Login As" User Impersonation
**Files:** `web/admin/users.php`, `web/admin/impersonate.php` (new)

Create secure user impersonation system:
- Add "Login As" button to each user row (except demo users)
- Create `/admin/impersonate.php` endpoint that:
  - Validates admin session
  - Stores original admin session data in separate session variables:
    - `$_SESSION['impersonating_admin_id']`
    - `$_SESSION['impersonating_admin_email']`
    - `$_SESSION['impersonating_from_time']`
  - Creates new user session for target user
  - Sets `$_SESSION['is_impersonating'] = true`
  - Opens in new browser window/tab via `target="_blank"`
- Add impersonation banner to user dashboard:
  - Display at top: "You are viewing as [user email]. [Return to Admin]"
  - Style with warning colors (orange/yellow background)
  - "Return to Admin" button that clears impersonation and redirects to admin dashboard
- Maintain original admin window session (do not destroy)

### 4. Clickable Statistics Cards on Dashboard
**Files:** `web/admin/dashboard.php`

Make the statistics cards interactive:
- Convert stat cards to clickable links:
  - "Active Users" → `/admin/users.php?filter=all`
  - "Admins" → `/admin/users.php?filter=admins`
  - "Inactive Users" → `/admin/users.php?filter=inactive`
  - "Total Business Cards" → `/admin/cards.php`
- Add hover effects (scale, shadow, cursor pointer)
- Update CSS to make cards look clickable
- Preserve existing counts and styling

### 5. Admin Demotion with Self-Protection
**Files:** `web/admin/users.php`, `web/admin/api/edit-user.php`

Enhance role management with protection:
- In edit user modal, check if editing current admin's own profile
- If `$targetUserId === AdminAuth::getUserId()`:
  - Disable role dropdown (make it read-only)
  - Show message: "You cannot change your own role"
- Allow demotion of other admins (admin → user)
- Update `is_admin` field in database
- Update `role` field to match (`admin` or `user`)
- Show confirmation when demoting an admin

## Security Considerations

- All endpoints must validate admin session via `AdminAuth::requireAuth()`
- Prevent self-demotion (admins cannot demote themselves)
- Prevent deletion/editing of demo user (except by system)
- Use prepared statements for all database queries
- Wrap deletions in transactions to prevent partial deletes
- Store impersonation state securely in session
- Log all admin actions (user edits, deletions, impersonations) for audit trail

## Database Queries

### User Deletion Cascade Order
```sql
DELETE FROM analytics_daily WHERE card_id IN (SELECT id FROM business_cards WHERE user_id = ?);
DELETE FROM analytics_events WHERE card_id IN (SELECT id FROM business_cards WHERE user_id = ?);
DELETE FROM analytics_sessions WHERE card_id IN (SELECT id FROM business_cards WHERE user_id = ?);
DELETE FROM website_links WHERE business_card_id IN (SELECT id FROM business_cards WHERE user_id = ?);
DELETE FROM email_contacts WHERE business_card_id IN (SELECT id FROM business_cards WHERE user_id = ?);
DELETE FROM phone_contacts WHERE business_card_id IN (SELECT id FROM business_cards WHERE user_id = ?);
DELETE FROM addresses WHERE business_card_id IN (SELECT id FROM business_cards WHERE user_id = ?);
DELETE FROM business_cards WHERE user_id = ?;
DELETE FROM verification_codes WHERE email = (SELECT email FROM users WHERE id = ?);
DELETE FROM users WHERE id = ?;
```

### User Edit Query
```sql
UPDATE users 
SET email = ?, 
    password_hash = ?, 
    is_active = ?, 
    is_admin = ?, 
    role = ?,
    updated_at = NOW() 
WHERE id = ?;
```

## UI/UX Enhancements

- Use modals for edit/delete (avoid page reloads)
- Show loading states during operations
- Display success/error messages prominently
- Add confirmation dialogs for destructive actions
- Make statistics cards visually clickable (hover effects)
- Add impersonation banner with clear exit path
- Use consistent button styles (Edit: blue, Delete: red, Login As: green)

## Testing Checklist

- [ ] Edit user email, password, status, role
- [ ] Verify admin cannot demote themselves
- [ ] Delete user and verify all related data is removed
- [ ] Verify demo user cannot be deleted
- [ ] "Login As" opens new window with user session
- [ ] Original admin session remains active
- [ ] Return from impersonation works correctly
- [ ] Statistics cards link to correct filtered views
- [ ] All error cases handled gracefully

