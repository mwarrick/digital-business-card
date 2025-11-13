# 🧪 Testing Checklist for QRCard App

## ✅ Recently Implemented Features to Test

### 1. Detail Screen Updates (Contact & Lead Details)
**Status**: Just implemented - needs testing

#### Contact Details Screen
- [ ] Open a contact detail view
- [ ] Verify all fields are displayed even when empty
- [ ] Verify empty fields show "Not provided" in secondary color
- [ ] Verify sections are organized:
  - [ ] Basic Information (First Name, Last Name, Email, Work Phone, Mobile Phone)
  - [ ] Professional Information (Company, Job Title)
  - [ ] Address (Street Address, City, State, ZIP Code, Country)
  - [ ] Additional Information (Website, Notes, Birthdate, Source)
- [ ] Verify clickable fields work (Email, Phone, Website, Address)
- [ ] Test with a contact that has all fields populated
- [ ] Test with a contact that has minimal fields populated
- [ ] Verify "Converted from Lead" banner appears when applicable

#### Lead Details Screen
- [ ] Open a lead detail view
- [ ] Verify all fields are displayed even when empty
- [ ] Verify empty fields show "Not provided" in secondary color
- [ ] Verify sections are organized:
  - [ ] Basic Information (First Name, Last Name, Email, Work Phone, Mobile Phone)
  - [ ] Professional Information (Company, Job Title)
  - [ ] Address (Street Address, City, State, ZIP Code, Country)
  - [ ] Additional Information (Website, Comments, Birthdate)
  - [ ] Source Information (From Business Card, QR Code, Captured Date)
- [ ] Verify clickable fields work (Email, Phone, Website, Address)
- [ ] Verify "Converted to Contact" banner appears when applicable
- [ ] Verify "Convert to Contact" button appears at bottom (if not converted)
- [ ] Test with a lead that has all fields populated
- [ ] Test with a lead that has minimal fields populated

---

### 2. Soft Delete Functionality
**Status**: Previously implemented - verify still working

#### Contacts
- [ ] Swipe to delete a contact in the contacts list
- [ ] Verify contact disappears from list immediately
- [ ] Verify contact is soft-deleted (not permanently removed)
- [ ] Sync with server and verify deleted contact doesn't reappear
- [ ] Delete a contact that was converted from a lead
- [ ] Verify the lead reverts to "new" status after contact deletion
- [ ] Verify lead appears in leads list after reversion

#### Leads
- [ ] Delete a lead from the leads list
- [ ] Verify lead disappears from list immediately
- [ ] Verify lead is soft-deleted (not permanently removed)
- [ ] Sync with server and verify deleted lead doesn't reappear
- [ ] Try to delete a lead that has been converted to a contact
- [ ] Verify error message appears (cannot delete converted lead)

#### Business Cards
- [ ] Delete a business card
- [ ] Verify card disappears from list immediately
- [ ] Verify card is soft-deleted (not permanently removed)
- [ ] Sync with server and verify deleted card doesn't reappear

---

### 3. Lead to Contact Conversion
**Status**: Previously implemented - verify still working

- [ ] Open a lead detail view (non-converted lead)
- [ ] Tap "Convert to Contact" button at bottom
- [ ] Verify loading state shows "Converting..."
- [ ] Verify success - lead status changes to "converted"
- [ ] Verify contact is created and appears in contacts list
- [ ] Verify contact has all data from the lead
- [ ] Verify lead no longer shows "Convert to Contact" button
- [ ] Verify "Converted to Contact" banner appears on lead
- [ ] Sync and verify conversion persists on server
- [ ] Test conversion with a lead that has minimal data
- [ ] Test conversion with a lead that has all fields populated

---

### 4. Sync Functionality
**Status**: Previously implemented - verify still working

#### Contacts Sync
- [ ] Open contacts list
- [ ] Tap sync/refresh button
- [ ] Verify sync completes successfully
- [ ] Verify contacts are updated from server
- [ ] Verify deleted contacts don't reappear
- [ ] Verify new contacts from server appear
- [ ] Verify error message appears if sync fails

#### Leads Sync
- [ ] Open leads list
- [ ] Tap sync/refresh button
- [ ] Verify sync completes successfully
- [ ] Verify leads are updated from server
- [ ] Verify deleted leads don't reappear
- [ ] Verify new leads from server appear
- [ ] Verify error message appears if sync fails

#### Full Sync
- [ ] Perform full sync from home screen
- [ ] Verify all data types sync correctly
- [ ] Verify no duplicates are created
- [ ] Verify soft-deleted items stay deleted

---

### 5. Duplicate Contact Prevention
**Status**: Previously implemented - verify still working

- [ ] Convert a lead to contact
- [ ] Sync with server
- [ ] Verify only one contact exists (no duplicates)
- [ ] Convert same lead again (if possible)
- [ ] Verify duplicate prevention works
- [ ] Check database for duplicate contacts with same `leadId`

---

### 6. Cross-Platform Consistency
**Status**: Verify consistency across platforms

- [ ] Compare Android contact detail view with iOS
- [ ] Compare Android lead detail view with iOS
- [ ] Verify field layouts match between platforms
- [ ] Verify "Not provided" text appears consistently
- [ ] Verify section organization matches iOS

---

## 🔍 Edge Cases to Test

### Data Scenarios
- [ ] Contact/Lead with all fields empty
- [ ] Contact/Lead with all fields populated
- [ ] Contact/Lead with special characters in fields
- [ ] Contact/Lead with very long text in fields
- [ ] Contact/Lead with null vs empty string values

### Sync Scenarios
- [ ] Delete item locally, then sync
- [ ] Delete item on server, then sync locally
- [ ] Create item locally, then sync
- [ ] Create item on server, then sync locally
- [ ] Modify item locally, then sync
- [ ] Modify item on server, then sync locally

### Conversion Scenarios
- [ ] Convert lead with all fields
- [ ] Convert lead with minimal fields
- [ ] Convert lead, then delete the contact
- [ ] Convert lead, then try to delete the lead
- [ ] Convert lead, sync, then check for duplicates

### Network Scenarios
- [ ] Sync with no internet connection (should show error)
- [ ] Sync with slow connection
- [ ] Sync with intermittent connection
- [ ] Sync after network timeout

---

## 🐛 Known Issues to Verify Fixed

- [ ] Red edges around contact items during swipe-to-delete (should be fixed)
- [ ] Sync refresh buttons on contacts and leads pages (should work now)
- [ ] Lead conversion 500 error (should be fixed)
- [ ] Duplicate contacts from lead conversion (should be prevented)
- [ ] Missing leads in Android app (should be fixed)

---

## 📱 Platform-Specific Testing

### Android App
- [ ] Test on Android 7.1.1 (API 25) - minimum supported version
- [ ] Test on latest Android version
- [ ] Test on different screen sizes
- [ ] Test in portrait and landscape orientations
- [ ] Test with different Material Design themes

### iOS App
- [ ] Verify detail screens match Android implementation
- [ ] Test on different iOS versions
- [ ] Test on different device sizes (iPhone, iPad)

### Website
- [ ] Verify soft delete works on website
- [ ] Verify deleted items don't appear in lists
- [ ] Verify lead reversion works on website

---

## 🚨 Critical Paths to Test

1. **User Flow**: Create lead → Convert to contact → Delete contact → Verify lead reversion
2. **User Flow**: Delete contact → Sync → Verify contact stays deleted
3. **User Flow**: View contact/lead details → Verify all fields visible
4. **User Flow**: Convert lead → Sync → Verify no duplicates

---

## 📝 Notes

- After making changes, always rebuild and reinstall the app
- Test on real devices when possible, not just emulators
- Verify server-side changes are deployed
- Check logs for any errors during testing
- Test with real user accounts, not just test data

