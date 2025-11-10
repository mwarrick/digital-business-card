# Android App Development Retrospective - January 8, 2025

## Summary

Today's session focused on fixing critical issues with contact synchronization, user filtering, and source mapping in the Android app. We identified and resolved problems with contacts being pulled from the wrong user account and incorrect "Converted" source labeling.

## Completed Work

### 1. Fixed Contact Source Mapping Issue
**Problem:** All contacts were showing as "Converted" (meaning they came from leads) when they should have been "manual".

**Root Cause:** 
- Server query was using `CASE WHEN c.id_lead IS NOT NULL THEN 'converted'` which incorrectly treated `id_lead = 0` (default for manual contacts) as converted
- Manual contacts have `id_lead = 0` in the database, which is NOT NULL, so they were being marked as converted

**Solution:**
- Updated server queries to check: `c.id_lead IS NOT NULL AND c.id_lead != 0 AND c.id_lead != ''`
- Updated Android `ContactDtoMapper` to validate `leadId > 0` before marking as converted
- Fixed queries in:
  - `web/api/contacts/index.php` (GET list, GET single, POST create)
  - `web/api/contacts/get.php` (GET single)

**Files Changed:**
- `web/api/contacts/index.php`
- `web/api/contacts/get.php`
- `Android App/app/src/main/java/com/sharemycard/android/data/remote/mapper/ContactDtoMapper.kt`

### 2. Fixed User ID Filtering Issue
**Problem:** Local app was pulling contacts from the demo account (wrong user) instead of the authenticated user's contacts.

**Root Cause:**
- `id_user` field in contacts table is VARCHAR
- JWT token `user_id` might be integer or string
- Type mismatch caused incorrect filtering

**Solution:**
- Convert `$userId` to string after extraction: `$userId = (string)$userId;`
- Added extensive logging to track user ID type and value
- Added safety check to filter out contacts with wrong `user_id` (defensive programming)
- Added logging to show current user info during sync in Android app

**Files Changed:**
- `web/api/contacts/index.php`
- `Android App/app/src/main/java/com/sharemycard/android/domain/sync/SyncManager.kt`
- `Android App/app/src/main/java/com/sharemycard/android/data/local/TokenManager.kt` (added `getUserIdFromToken()` and `isDemoAccount()` methods)

### 3. Enhanced Debugging and Logging
**Added Logging:**
- Server-side: User ID type, converted value, auth payload, email
- Android-side: Current user ID, email, demo account status during sync
- Safety checks to verify contacts belong to correct user

**Files Changed:**
- `web/api/contacts/index.php`
- `Android App/app/src/main/java/com/sharemycard/android/domain/sync/SyncManager.kt`
- `Android App/app/src/main/java/com/sharemycard/android/data/local/TokenManager.kt`

### 4. Identified Issue: Contact Deletion Sync
**Problem Identified:**
- When a contact is deleted on the server, the local app still has it
- During sync, the local app tries to push the deleted contact back to the server
- This creates a sync conflict

**Proposed Solution (Documented, Not Implemented):**
- Implement soft delete with `is_deleted` flag
- Server: Add `is_deleted` field, update DELETE to set flag instead of hard delete
- Android: Check `is_deleted` flag during sync, don't push deleted contacts
- Documented in `.cody/project/plan/android-app-sync.md`

## Technical Details

### Server-Side Changes
1. **User ID Type Conversion:**
   ```php
   $userId = AuthHelper::getUserId();
   $userId = (string)$userId; // Convert to string for VARCHAR field matching
   ```

2. **Source Type Query Fix:**
   ```sql
   CASE WHEN c.id_lead IS NOT NULL AND c.id_lead != 0 AND c.id_lead != '' 
        THEN 'converted' 
        ELSE COALESCE(c.source, 'manual') 
   END as source_type
   ```

3. **Safety Check:**
   ```php
   // Verify all contacts belong to the correct user
   $wrongUserContacts = array_filter($contacts, function($contact) use ($userId) {
       return isset($contact['id_user']) && $contact['id_user'] != $userId;
   });
   ```

### Android-Side Changes
1. **Source Determination Logic:**
   ```kotlin
   val source = when {
       dto.source != null && dto.source.isNotBlank() -> dto.source
       dto.sourceType == "converted" && dto.leadId != null && dto.leadId != "0" && dto.leadId.isNotBlank() -> "converted"
       dto.leadId != null && dto.leadId != "0" && dto.leadId.isNotBlank() && dto.leadId.toIntOrNull()?.let { it > 0 } == true -> "converted"
       else -> "manual"
   }
   ```

2. **User Info Logging:**
   ```kotlin
   val userId = tokenManager.getUserIdFromToken()
   val userEmail = tokenManager.getEmail()
   val isDemo = tokenManager.isDemoAccount()
   Log.d("SyncManager", "👤 Current user - ID: $userId, Email: $userEmail, IsDemo: $isDemo")
   ```

## Issues Encountered

1. **Type Mismatch:** VARCHAR vs Integer for user ID filtering
   - **Resolution:** Explicit string conversion

2. **False Positive "Converted" Source:** `id_lead = 0` treated as converted
   - **Resolution:** Added checks for `!= 0` and `!= ''`

3. **Contact Deletion Sync Conflict:** Deleted contacts being pushed back
   - **Status:** Documented for future implementation (soft delete)

## What Worked Well

1. **Systematic Debugging:** Added comprehensive logging to identify root causes
2. **Defensive Programming:** Added safety checks to prevent wrong user data
3. **Type Safety:** Explicit type conversion for database field matching
4. **Documentation:** Properly documented the soft delete solution for future work

## Areas for Improvement

1. **Contact Deletion:** Need to implement soft delete solution
2. **Error Handling:** Could add more specific error messages for sync conflicts
3. **Testing:** Should add unit tests for source determination logic
4. **Database Schema:** Consider adding `is_deleted` flag proactively

## Next Steps

1. **Implement Soft Delete:**
   - Add `is_deleted` field to contacts table
   - Update server DELETE endpoint
   - Update Android sync logic
   - Test deletion sync flow

2. **Testing:**
   - Test with multiple user accounts
   - Test source mapping with various scenarios
   - Test sync with deleted contacts

3. **Documentation:**
   - Update API documentation with soft delete behavior
   - Document sync conflict resolution strategy

## Files Modified

### Server-Side:
- `web/api/contacts/index.php` - User ID conversion, source type fix, logging
- `web/api/contacts/get.php` - Source type fix

### Android-Side:
- `Android App/app/src/main/java/com/sharemycard/android/data/remote/mapper/ContactDtoMapper.kt` - Source determination logic
- `Android App/app/src/main/java/com/sharemycard/android/domain/sync/SyncManager.kt` - User info logging
- `Android App/app/src/main/java/com/sharemycard/android/data/local/TokenManager.kt` - User ID and demo account detection

### Documentation:
- `.cody/project/plan/android-app-sync.md` - Added soft delete solution documentation

## Deployment Status

- ✅ Server files deployed to live server
- ✅ Android app changes ready for testing
- ⏳ Soft delete implementation pending

