# Retrospective: QR Code Expiration Fix - November 6, 2025

## Problem Statement
Custom QR codes with expiration dates were still displaying the landing page instead of the expiration notice, even though the list view correctly showed them as expired.

## Investigation Process
1. **Initial Analysis**: Verified that the expiration check logic in `web/public/qr.php` matched the working logic in `web/user/qr/index.php`
2. **Multiple Fix Attempts**: 
   - Updated expiration check to use `new DateTime()` instead of `createFromFormat()`
   - Added extensive debug output
   - Added forced expiration checks
   - Verified database values were correct
   - Added error logging
3. **Deployment Verification**: Confirmed files were being deployed correctly
4. **Test Scripts**: Created test scripts that showed expiration logic worked correctly

## Root Cause
**The `.htaccess` file was routing `/qr/{id}` requests to `/qr.php` (an old file from October 29) instead of `/public/qr.php` (the file we were updating).**

The `.htaccess` file contained:
```apache
RewriteRule ^qr/.*$ /qr.php [NC,L,QSA]
```

This meant all our code changes to `web/public/qr.php` were never being executed. The server was serving a completely different, outdated file.

## Solution
1. **Fixed `.htaccess` routing**: Changed the rewrite rule to point to the correct file:
   ```apache
   RewriteRule ^qr/.*$ /public/qr.php [NC,L,QSA]
   ```

2. **Fixed file paths**: Since the file is in `/public/qr.php`, all include paths needed to go up one directory:
   - Changed `__DIR__ . '/config/secure-config.php'` → `__DIR__ . '/../config/secure-config.php'`
   - Changed `__DIR__ . '/api/includes/...'` → `__DIR__ . '/../api/includes/...'`
   - Changed `__DIR__ . '/public/includes/qr/...'` → `__DIR__ . '/includes/qr/...'`

3. **Added "Clear Expiration" button**: Added a user-friendly button in the edit form to easily remove expiration dates.

## Key Lessons Learned

### 1. **Always Check Routing Configuration First**
   - When code changes aren't taking effect, check `.htaccess`, `router.php`, or any URL rewriting rules
   - Verify which file is actually being served for the URL pattern
   - Don't assume the file path matches the URL structure

### 2. **Verify File Execution**
   - Use simple test outputs (like `die()` statements) to confirm the correct file is executing
   - Check file modification dates on the server
   - Compare file contents between local and server

### 3. **Debug Strategically**
   - While extensive debugging helped identify the issue, checking routing first would have saved significant time
   - When multiple deployments show no change, routing/configuration is often the culprit

### 4. **File Path Awareness**
   - When files are in subdirectories (like `/public/`), all relative paths need adjustment
   - `__DIR__` is relative to the file's location, not the web root

## What Went Well
- The expiration check logic was correct from the start
- Database values were correct
- Test scripts confirmed the logic worked
- Debug output helped identify the issue once routing was fixed

## What Could Be Improved
- **Check routing/configuration files FIRST** when code changes don't take effect
- Verify which file is actually being executed before extensive debugging
- Document routing rules in project documentation
- Add a simple "file executed" check at the top of critical files during debugging

## Final Status
✅ QR code expiration now works correctly
✅ Expired QR codes show expiration notice instead of landing page
✅ Users can clear expiration dates with a button
✅ All file paths corrected
✅ `.htaccess` routing fixed

## Time Spent
- Investigation: ~2 hours
- Fix implementation: ~30 minutes
- Total: ~2.5 hours (could have been ~15 minutes if routing was checked first)

---

**Takeaway**: When code changes don't take effect after multiple deployments, always check routing, configuration files, and which file is actually being executed. Don't assume the file path matches the URL structure.

