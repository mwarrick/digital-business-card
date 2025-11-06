# Custom QR Codes Expiration Functionality - Implementation Tasks

## Phase 1: Database Schema

### Task 1.1: Create Migration File
- [ ] Create `web/config/migrations/044_add_expiration_to_custom_qr_codes.sql`
- [ ] Add `expires_at` DATETIME NULL column to `custom_qr_codes` table
- [ ] Add `expiration_notice` VARCHAR(500) DEFAULT 'Sorry, this QR code has expired.' column
- [ ] Add index on `expires_at` for efficient expiration queries
- [ ] Test migration on development database

### Task 1.2: Update Data Model Documentation
- [ ] Update `custom_qr_codes` table schema documentation
- [ ] Document EST timezone requirement for `expires_at` field
- [ ] Document default value for `expiration_notice`

## Phase 2: Backend Logic

### Task 2.1: Expiration Check Function
- [ ] Create helper function `isQrCodeExpired($qrCode)` in `web/api/includes/qr/Generator.php` or new helper class
- [ ] Function should:
  - Check if `expires_at` is NULL (no expiration)
  - If set, compare current time (EST) with `expires_at`
  - Return boolean (true if expired, false if not)
- [ ] Handle timezone conversion to EST properly
- [ ] Add unit tests for expiration logic

### Task 2.2: Update Public QR Handler
- [ ] Modify `web/public/qr.php`:
  - [ ] Add expiration check after fetching QR code
  - [ ] Always record `qr_view` analytics (even if expired)
  - [ ] If expired: render expiration template, skip type-specific logic
  - [ ] If not expired: proceed with normal type-specific functionality
- [ ] Ensure expiration check happens before any redirects or content display

### Task 2.3: Expiration Template
- [ ] Create `web/public/includes/qr/expired.php` template
- [ ] Display `expiration_notice` message prominently
- [ ] If `show_lead_form=1`, display lead form button/link
- [ ] Style consistently with other QR landing pages
- [ ] Include theme support if applicable

## Phase 3: User Interface - Create/Edit Forms

### Task 3.1: Add Expiration Fields to Create Form
- [ ] Update `web/user/qr/create.php`:
  - [ ] Add date/time picker for `expires_at`
  - [ ] Add clear EST timezone indicator: "All dates and times are in Eastern Time (EST)"
  - [ ] Make date/time picker optional (allow blank/null)
  - [ ] Add text input for `expiration_notice` with default value
  - [ ] Add preview/example of expiration notice
  - [ ] Add help text explaining expiration behavior

### Task 3.2: Add Expiration Fields to Edit Form
- [ ] Update `web/user/qr/edit.php`:
  - [ ] Same fields as create form
  - [ ] Pre-populate with existing values
  - [ ] Allow clearing expiration (set to NULL)
  - [ ] Show current expiration status if set

### Task 3.3: Form Validation
- [ ] Validate `expires_at` is valid datetime if provided
- [ ] Validate `expiration_notice` is not empty if provided (or use default)
- [ ] Ensure timezone is handled correctly in form submission
- [ ] Convert user input to EST before saving to database

### Task 3.4: JavaScript Enhancements
- [ ] Add real-time preview of expiration notice
- [ ] Add visual indicator when expiration date is set
- [ ] Add warning if expiration date is in the past
- [ ] Add "Clear expiration" button to reset to NULL

## Phase 4: User Interface - List View

### Task 4.1: Update QR Codes List
- [ ] Update `web/user/qr/index.php`:
  - [ ] Add expiration status column or badge
  - [ ] Show "Expired" badge for expired QR codes
  - [ ] Show "Expires [date]" for future expiration
  - [ ] Show "No expiration" for NULL `expires_at`
  - [ ] Add visual indicators (red for expired, yellow for expiring soon, green for active)
  - [ ] Sort/filter by expiration status

### Task 4.2: Expiration Status Helper
- [ ] Create helper function `getQrExpirationStatus($qrCode)`:
  - [ ] Returns: 'expired', 'expires_soon', 'expires_future', 'no_expiration'
  - [ ] "Expires soon" = within 7 days
  - [ ] Handle timezone correctly

## Phase 5: Admin Interface

### Task 5.1: Update Admin QR List
- [ ] Update `web/admin/qr/index.php`:
  - [ ] Add expiration status column
  - [ ] Show expiration information for all QR codes
  - [ ] Add filter by expiration status
  - [ ] Visual indicators matching user interface

### Task 5.2: Admin Analytics
- [ ] Update admin analytics to show expired QR code statistics
- [ ] Track scans on expired QR codes separately
- [ ] Add expiration-related metrics

## Phase 6: Testing

### Task 6.1: Unit Tests
- [ ] Test expiration check function with various scenarios:
  - [ ] NULL expiration (no expiration)
  - [ ] Future expiration (not expired)
  - [ ] Past expiration (expired)
  - [ ] Current time exactly at expiration
  - [ ] Timezone edge cases

### Task 6.2: Integration Tests
- [ ] Test expired QR code with each type:
  - [ ] `default` type - shows expiration notice, no landing page
  - [ ] `url` type - shows expiration notice, no redirect
  - [ ] `social` type - shows expiration notice, no redirect
  - [ ] `text` type - shows expiration notice, no text display
  - [ ] `wifi` type - shows expiration notice, no WiFi info
  - [ ] `appstore` type - shows expiration notice, no redirect
- [ ] Test expired QR code with `show_lead_form=1` - shows lead form button
- [ ] Test expired QR code with `show_lead_form=0` - no lead form button
- [ ] Verify analytics are recorded for expired QR codes

### Task 6.3: UI Testing
- [ ] Test date/time picker with EST timezone
- [ ] Test expiration notice customization
- [ ] Test clearing expiration date
- [ ] Test form validation
- [ ] Test list view expiration indicators
- [ ] Test on mobile devices

### Task 6.4: Edge Cases
- [ ] Test QR code that expires exactly at scan time
- [ ] Test timezone conversion accuracy
- [ ] Test very long expiration notices
- [ ] Test empty expiration notice (should use default)
- [ ] Test expired QR code with inactive status

## Phase 7: Documentation

### Task 7.1: User Documentation
- [ ] Update user guide with expiration functionality
- [ ] Document EST timezone requirement
- [ ] Document default expiration notice
- [ ] Document behavior when expired

### Task 7.2: Code Documentation
- [ ] Add PHPDoc comments to expiration functions
- [ ] Document timezone handling approach
- [ ] Document expiration check logic
- [ ] Update API documentation if applicable

## Phase 8: Deployment

### Task 8.1: Database Migration
- [ ] Run migration on staging environment
- [ ] Verify migration success
- [ ] Test with sample data
- [ ] Run migration on production (during maintenance window)

### Task 8.2: Code Deployment
- [ ] Deploy backend changes
- [ ] Deploy frontend changes
- [ ] Clear any caches
- [ ] Verify functionality on production

### Task 8.3: Post-Deployment Verification
- [ ] Test expiration functionality on production
- [ ] Verify analytics recording for expired QR codes
- [ ] Check timezone handling
- [ ] Monitor error logs for issues

## Notes

- **Timezone Handling**: All expiration checks must use EST. Consider using PHP's `DateTime` with timezone conversion or storing all times in UTC and converting to EST for display/checks.
- **Analytics**: Ensure `qr_view` events are recorded even for expired QR codes for accurate statistics.
- **Performance**: Index on `expires_at` will help with queries filtering expired QR codes.
- **User Experience**: Clear EST timezone indicators are critical to prevent user confusion.
- **Backward Compatibility**: Existing QR codes without expiration should continue to work normally (NULL `expires_at` = no expiration).

