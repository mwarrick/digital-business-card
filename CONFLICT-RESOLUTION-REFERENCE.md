# Conflict Resolution Reference Guide

## Quick Recovery Commands

If you need to check what was in the remote version before we overwrote it:

```bash
# See what the remote version looked like
git show origin/main:web/admin/[filename]

# Compare local vs remote
git diff origin/main HEAD -- web/admin/[filename]

# Restore remote version if needed
git checkout origin/main -- web/admin/[filename]
```

## Files That Were Overwritten

### Admin Utility Files (29 files)
1. `web/admin/check-demo-user-status.php`
2. `web/admin/check-image-log-table.php`
3. `web/admin/check-logins.php`
4. `web/admin/check-qr-expiration.php` (both added)
5. `web/admin/check-tables.php`
6. `web/admin/check-user-id-mismatch.php`
7. `web/admin/create-user-logins.php`
8. `web/admin/debug-login-data.php`
9. `web/admin/debug-login-reports.php`
10. `web/admin/direct-drop.php`
11. `web/admin/drop-user-logins-debug.php`
12. `web/admin/drop-user-logins.php`
13. `web/admin/drop-with-database-class.php`
14. `web/admin/fix-demo-login-records.php`
15. `web/admin/fix-login-records.php`
16. `web/admin/fix-login-reports-final.php`
17. `web/admin/image-creation-history-public.php`
18. `web/admin/image-creation-history-simple.php`
19. `web/admin/recreate-user-logins.php`
20. `web/admin/run-user-id-migration.php`
21. `web/admin/simple-drop.php`
22. `web/admin/test-expiration-live.php` (both added)
23. `web/admin/test-qr-expiration.php` (both added)

### API Includes (1 file)
24. `web/api/includes/log-image-creation.php`

### Plan Documents (2 files)
25. `.cody/project/plan/apple-watch-qr-code.md`
26. `.cody/project/plan/ios-widget-qr-code.md`

### Documentation (3 files)
27. `DEPLOYMENT-RULE.md`
28. `RETROSPECTIVE-IOS-SESSION.md`
29. `RETROSPECTIVE-QR-EXPIRATION-FIX.md` (both added)

## What Changed in Remote

The remote commit (`cc56445`) was: "Fix contact sync issues: source mapping and user ID filtering"

This suggests the remote changes were related to:
- Contact sync functionality
- Source mapping fixes
- User ID filtering improvements

## How to Check for Issues

1. **Test image creation logging**:
   - Upload an image to a business card
   - Check: `SELECT * FROM image_creation_log ORDER BY created_at DESC LIMIT 5`
   - Verify the log entry was created

2. **Test admin utilities**:
   - Access each check-*.php file via browser
   - Verify they display expected information
   - Check for PHP errors in error.log

3. **Test login functionality**:
   - Log in as a user
   - Verify login works correctly
   - Check login records are created

4. **Test QR expiration**:
   - Check if QR codes expire correctly
   - Verify expiration notices work
   - Test expired QR code handling

## If You Find Issues

1. Identify which file has the problem
2. Check remote version: `git show cc56445:web/admin/[filename]`
3. Compare differences: `git diff cc56445 HEAD -- web/admin/[filename]`
4. If remote version is better, restore it:
   ```bash
   git checkout cc56445 -- web/admin/[filename]
   git add web/admin/[filename]
   git commit -m "Restore remote version of [filename]"
   git push origin main
   ```

## Most Critical Files to Test

1. **`web/api/includes/log-image-creation.php`** - Core functionality, affects image uploads
2. **`web/admin/run-user-id-migration.php`** - Data migration, could affect data integrity
3. **`web/admin/check-*.php`** - Diagnostic tools, need to work for troubleshooting

## Testing Priority

**High Priority** (Test First):
- Image creation logging
- Login functionality
- User ID migrations (if used)

**Medium Priority** (Test Second):
- Admin check utilities
- Debug tools
- Fix utilities

**Low Priority** (Test Third):
- Drop utilities (destructive, rarely used)
- Test utilities
- Documentation files

