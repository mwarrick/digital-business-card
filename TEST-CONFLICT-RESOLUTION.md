# Test Cases for Conflict Resolution Files

This document provides test cases for all files that were resolved during the merge conflict. These files were kept from the local version (`--ours`), potentially overwriting remote changes.

**Date**: November 10, 2025  
**Conflict Resolution**: Kept local versions of admin utility files and plan documents

---

## Admin Utility Files

### 1. `web/admin/check-demo-user-status.php`
**Purpose**: Check demo user status in database

**Test Cases**:
- [ ] Access via browser: `https://sharemycard.app/admin/check-demo-user-status.php`
- [ ] Verify it displays demo user information
- [ ] Check that it shows correct role ('demo')
- [ ] Verify it shows demo user email (demo@sharemycard.app)
- [ ] Check that it displays user ID correctly

**Expected Output**: Should display demo user status, role, and related information

---

### 2. `web/admin/check-image-log-table.php`
**Purpose**: Check image creation log table structure and data

**Test Cases**:
- [ ] Access via browser: `https://sharemycard.app/admin/check-image-log-table.php`
- [ ] Verify it shows table structure
- [ ] Check that it displays recent image creation logs
- [ ] Verify it shows user_id, image_type, method, created_at
- [ ] Check for any errors in table structure

**Expected Output**: Should display image_creation_log table structure and recent entries

---

### 3. `web/admin/check-logins.php`
**Purpose**: Check login records and statistics

**Test Cases**:
- [ ] Access via browser: `https://sharemycard.app/admin/check-logins.php`
- [ ] Verify it displays login records
- [ ] Check that it shows login counts per user
- [ ] Verify it displays recent login timestamps
- [ ] Check for any missing or corrupted login data

**Expected Output**: Should display login statistics and recent login records

---

### 4. `web/admin/check-qr-expiration.php`
**Purpose**: Check QR code expiration status

**Test Cases**:
- [ ] Access via browser: `https://sharemycard.app/admin/check-qr-expiration.php`
- [ ] Verify it displays QR codes with expiration dates
- [ ] Check that it shows expired QR codes
- [ ] Verify it shows expiration_notice field values
- [ ] Check that it correctly identifies expired vs active QR codes

**Expected Output**: Should display QR code expiration information

---

### 5. `web/admin/check-tables.php`
**Purpose**: Check database table structure and existence

**Test Cases**:
- [ ] Access via browser: `https://sharemycard.app/admin/check-tables.php`
- [ ] Verify it lists all expected tables
- [ ] Check that it shows table structures
- [ ] Verify it identifies missing tables
- [ ] Check for any table structure mismatches

**Expected Output**: Should display all database tables and their structures

---

### 6. `web/admin/check-user-id-mismatch.php`
**Purpose**: Check for user ID mismatches in database

**Test Cases**:
- [ ] Access via browser: `https://sharemycard.app/admin/check-user-id-mismatch.php`
- [ ] Verify it identifies any user ID mismatches
- [ ] Check that it shows related records with mismatched IDs
- [ ] Verify it doesn't show false positives
- [ ] Check for any data integrity issues

**Expected Output**: Should identify and report any user ID mismatches

---

### 7. `web/admin/create-user-logins.php`
**Purpose**: Create login records for users

**Test Cases**:
- [ ] Access via browser: `https://sharemycard.app/admin/create-user-logins.php`
- [ ] Verify it creates login records correctly
- [ ] Check that it doesn't create duplicate records
- [ ] Verify it handles missing users gracefully
- [ ] Check that it updates existing records if needed

**Expected Output**: Should create or update login records for users

---

### 8. `web/admin/debug-login-data.php`
**Purpose**: Debug login data issues

**Test Cases**:
- [ ] Access via browser: `https://sharemycard.app/admin/debug-login-data.php`
- [ ] Verify it displays login data in readable format
- [ ] Check that it shows user login history
- [ ] Verify it identifies data inconsistencies
- [ ] Check for any error messages

**Expected Output**: Should display detailed login data for debugging

---

### 9. `web/admin/debug-login-reports.php`
**Purpose**: Debug login report issues

**Test Cases**:
- [ ] Access via browser: `https://sharemycard.app/admin/debug-login-reports.php`
- [ ] Verify it displays login reports
- [ ] Check that it shows report generation status
- [ ] Verify it identifies missing or corrupted reports
- [ ] Check for any report generation errors

**Expected Output**: Should display login report debugging information

---

### 10. `web/admin/direct-drop.php`
**Purpose**: Direct database table drop utility

**Test Cases**:
- [ ] **WARNING**: This is a destructive operation - test in development only
- [ ] Verify it requires proper authentication
- [ ] Check that it shows confirmation before dropping
- [ ] Verify it logs the drop operation
- [ ] Check that it handles errors gracefully

**Expected Output**: Should safely drop database tables with proper confirmation

---

### 11. `web/admin/drop-user-logins-debug.php`
**Purpose**: Drop user login debug tables

**Test Cases**:
- [ ] **WARNING**: This is a destructive operation - test in development only
- [ ] Access via browser: `https://sharemycard.app/admin/drop-user-logins-debug.php`
- [ ] Verify it requires proper authentication
- [ ] Check that it shows what will be dropped
- [ ] Verify it handles missing tables gracefully
- [ ] Check that it logs the operation

**Expected Output**: Should drop debug login tables safely

---

### 12. `web/admin/drop-user-logins.php`
**Purpose**: Drop user login tables

**Test Cases**:
- [ ] **WARNING**: This is a destructive operation - test in development only
- [ ] Access via browser: `https://sharemycard.app/admin/drop-user-logins.php`
- [ ] Verify it requires proper authentication
- [ ] Check that it shows confirmation
- [ ] Verify it doesn't drop critical tables
- [ ] Check that it logs the operation

**Expected Output**: Should drop user login tables with proper safeguards

---

### 13. `web/admin/drop-with-database-class.php`
**Purpose**: Drop tables using Database class

**Test Cases**:
- [ ] **WARNING**: This is a destructive operation - test in development only
- [ ] Access via browser: `https://sharemycard.app/admin/drop-with-database-class.php`
- [ ] Verify it uses Database class correctly
- [ ] Check that it requires proper authentication
- [ ] Verify it handles errors from Database class
- [ ] Check that it logs operations

**Expected Output**: Should drop tables using Database class methods

---

### 14. `web/admin/fix-demo-login-records.php`
**Purpose**: Fix demo user login records

**Test Cases**:
- [ ] Access via browser: `https://sharemycard.app/admin/fix-demo-login-records.php`
- [ ] Verify it identifies demo user login issues
- [ ] Check that it fixes corrupted demo login records
- [ ] Verify it doesn't affect other users
- [ ] Check that it reports what was fixed

**Expected Output**: Should fix demo user login records and report changes

---

### 15. `web/admin/fix-login-records.php`
**Purpose**: Fix login records for all users

**Test Cases**:
- [ ] Access via browser: `https://sharemycard.app/admin/fix-login-records.php`
- [ ] Verify it identifies login record issues
- [ ] Check that it fixes corrupted records
- [ ] Verify it doesn't create duplicates
- [ ] Check that it reports all fixes

**Expected Output**: Should fix login records and report all changes

---

### 16. `web/admin/fix-login-reports-final.php`
**Purpose**: Final fix for login reports

**Test Cases**:
- [ ] Access via browser: `https://sharemycard.app/admin/fix-login-reports-final.php`
- [ ] Verify it fixes login report issues
- [ ] Check that it regenerates missing reports
- [ ] Verify it doesn't corrupt existing reports
- [ ] Check that it reports all fixes

**Expected Output**: Should fix login report issues and regenerate missing reports

---

### 17. `web/admin/image-creation-history-public.php`
**Purpose**: Public view of image creation history

**Test Cases**:
- [ ] Access via browser: `https://sharemycard.app/admin/image-creation-history-public.php`
- [ ] Verify it displays image creation history
- [ ] Check that it shows recent image creations
- [ ] Verify it displays user information
- [ ] Check that it's properly formatted for public viewing

**Expected Output**: Should display image creation history in a public-friendly format

---

### 18. `web/admin/image-creation-history-simple.php`
**Purpose**: Simple view of image creation history

**Test Cases**:
- [ ] Access via browser: `https://sharemycard.app/admin/image-creation-history-simple.php`
- [ ] Verify it displays image creation history
- [ ] Check that it shows essential information only
- [ ] Verify it loads quickly
- [ ] Check that it's properly formatted

**Expected Output**: Should display simplified image creation history

---

### 19. `web/admin/recreate-user-logins.php`
**Purpose**: Recreate user login records

**Test Cases**:
- [ ] Access via browser: `https://sharemycard.app/admin/recreate-user-logins.php`
- [ ] Verify it recreates login records correctly
- [ ] Check that it doesn't create duplicates
- [ ] Verify it handles missing users gracefully
- [ ] Check that it reports what was created

**Expected Output**: Should recreate user login records and report changes

---

### 20. `web/admin/run-user-id-migration.php`
**Purpose**: Run user ID migration script

**Test Cases**:
- [ ] **WARNING**: This is a data migration - test in development first
- [ ] Access via browser: `https://sharemycard.app/admin/run-user-id-migration.php`
- [ ] Verify it requires proper authentication
- [ ] Check that it shows migration progress
- [ ] Verify it doesn't corrupt data
- [ ] Check that it logs all changes
- [ ] Verify it can be run multiple times safely (idempotent)

**Expected Output**: Should migrate user IDs safely and report progress

---

### 21. `web/admin/simple-drop.php`
**Purpose**: Simple table drop utility

**Test Cases**:
- [ ] **WARNING**: This is a destructive operation - test in development only
- [ ] Access via browser: `https://sharemycard.app/admin/simple-drop.php`
- [ ] Verify it requires proper authentication
- [ ] Check that it shows confirmation
- [ ] Verify it handles errors gracefully
- [ ] Check that it logs operations

**Expected Output**: Should drop tables with simple confirmation

---

### 22. `web/admin/test-expiration-live.php`
**Purpose**: Test QR code expiration on live system

**Test Cases**:
- [ ] Access via browser: `https://sharemycard.app/admin/test-expiration-live.php`
- [ ] Verify it tests QR code expiration correctly
- [ ] Check that it shows expiration status
- [ ] Verify it tests with real QR codes
- [ ] Check that it reports test results

**Expected Output**: Should test QR code expiration and report results

---

### 23. `web/admin/test-qr-expiration.php`
**Purpose**: Test QR code expiration functionality

**Test Cases**:
- [ ] Access via browser: `https://sharemycard.app/admin/test-qr-expiration.php`
- [ ] Verify it tests QR expiration logic
- [ ] Check that it shows test cases
- [ ] Verify it tests expired QR codes
- [ ] Check that it tests active QR codes
- [ ] Verify it reports all test results

**Expected Output**: Should test QR code expiration and report all test results

---

## API Includes

### 24. `web/api/includes/log-image-creation.php`
**Purpose**: Log image creation events

**Test Cases**:
- [ ] Create a business card with profile photo - verify it logs
- [ ] Create a business card with company logo - verify it logs
- [ ] Create a business card with cover graphic - verify it logs
- [ ] Check database: `SELECT * FROM image_creation_log ORDER BY created_at DESC LIMIT 10`
- [ ] Verify logs include: user_id, image_type, method, created_at
- [ ] Check that logs are created for web image editor usage
- [ ] Verify logs are created for iOS app uploads

**Expected Output**: Should log all image creation events to image_creation_log table

**Manual Test Steps**:
1. Log in as a user
2. Create/edit a business card
3. Upload a profile photo
4. Check admin panel: `image-creation-history-simple.php`
5. Verify the upload is logged

---

## Plan Documents

### 25. `.cody/project/plan/apple-watch-qr-code.md`
**Purpose**: Plan document for Apple Watch QR code feature

**Test Cases**:
- [ ] Open file and verify content is readable
- [ ] Check that plan structure is intact
- [ ] Verify no content was lost
- [ ] Check that markdown formatting is correct

**Expected Output**: Should be a readable markdown plan document

---

### 26. `.cody/project/plan/ios-widget-qr-code.md`
**Purpose**: Plan document for iOS widget QR code feature

**Test Cases**:
- [ ] Open file and verify content is readable
- [ ] Check that plan structure is intact
- [ ] Verify no content was lost
- [ ] Check that markdown formatting is correct

**Expected Output**: Should be a readable markdown plan document

---

## Documentation Files

### 27. `DEPLOYMENT-RULE.md`
**Purpose**: Deployment guidelines and rules

**Test Cases**:
- [ ] Open file and verify content is readable
- [ ] Check that deployment instructions are clear
- [ ] Verify no critical information was lost
- [ ] Check that formatting is correct

**Expected Output**: Should contain clear deployment instructions

---

### 28. `RETROSPECTIVE-IOS-SESSION.md`
**Purpose**: Retrospective document for iOS session work

**Test Cases**:
- [ ] Open file and verify content is readable
- [ ] Check that retrospective information is intact
- [ ] Verify no important notes were lost
- [ ] Check that formatting is correct

**Expected Output**: Should contain retrospective information about iOS session work

---

### 29. `RETROSPECTIVE-QR-EXPIRATION-FIX.md`
**Purpose**: Retrospective document for QR expiration fix

**Test Cases**:
- [ ] Open file and verify content is readable
- [ ] Check that fix information is documented
- [ ] Verify no important details were lost
- [ ] Check that formatting is correct

**Expected Output**: Should contain retrospective information about QR expiration fix

---

## Testing Checklist Summary

### Critical Files (Test First)
1. ✅ `web/api/includes/log-image-creation.php` - Core functionality
2. ✅ `web/admin/run-user-id-migration.php` - Data migration (if used)
3. ✅ `web/admin/check-*.php` - Diagnostic tools

### Important Files (Test Second)
4. ✅ `web/admin/fix-*.php` - Data repair tools
5. ✅ `web/admin/debug-*.php` - Debugging tools
6. ✅ `web/admin/image-creation-history-*.php` - Reporting tools

### Utility Files (Test Third)
7. ✅ `web/admin/drop-*.php` - Destructive operations (test in dev only)
8. ✅ `web/admin/create-*.php` - Data creation tools
9. ✅ `web/admin/test-*.php` - Testing utilities

### Documentation (Verify Last)
10. ✅ Plan documents - Verify readability
11. ✅ Retrospective documents - Verify content
12. ✅ Deployment rules - Verify instructions

---

## How to Test

1. **For PHP files**: Access via browser at `https://sharemycard.app/admin/[filename]`
2. **For API includes**: Test by using the feature that calls it (e.g., upload image to test log-image-creation.php)
3. **For markdown files**: Open in editor and verify content

## Recovery Plan

If any file shows issues:
1. Check remote version: `git show origin/main:web/admin/[filename]`
2. Compare with local: `git diff origin/main HEAD -- web/admin/[filename]`
3. Restore remote version if needed: `git checkout origin/main -- web/admin/[filename]`

---

## Notes

- All admin files should require authentication
- Destructive operations (drop-*.php) should have confirmation
- All operations should log to error_log
- Test files should not affect production data

