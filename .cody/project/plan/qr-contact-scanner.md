# QR Code Contact Scanner Implementation

## Overview

Implement a web-based QR code scanner that allows authenticated users to scan vCard format QR codes, parse the contact data, review/edit it in a confirmation screen, and save it as a new contact with source tracking.

## Implementation Steps

### 1. Database Schema Update

Add a `source` field to the `contacts` table to track contact origin:

- Add column: `source` VARCHAR(50) DEFAULT 'manual' - tracks contact creation method (e.g., 'qr_scan', 'manual', 'converted')
- Add column: `source_metadata` TEXT - JSON field for additional tracking data (device info, scan timestamp)

**File**: Create new migration file at `web/config/migrations/add_contact_source_tracking.sql`

### 2. Create QR Scanner Page Component

Create a new page with camera access and QR code scanning capabilities:

**File**: `web/user/contacts/scan-qr.php`

- Require user authentication via `UserAuth::requireAuth()`
- Include navigation from `web/user/includes/navigation.php`
- Use HTML5 QR Code Scanner library (html5-qrcode)
- Camera selector (front/back camera for mobile devices)
- Start/Stop scanning controls
- Real-time video preview with scanning overlay

### 3. VCard Parser (Client-side JavaScript)

Parse vCard format data into structured contact fields:

**Functionality** (in `scan-qr.php` inline script):

- Parse `BEGIN:VCARD` ... `END:VCARD` format
- Extract fields: FN (full name), N (structured name), TEL (phones), EMAIL, ORG (organization), TITLE (job title), ADR (address), URL (website), BDAY (birthday), NOTE
- Handle multiple phone/email entries
- Map vCard fields to contact database fields
- Display error for invalid/non-vCard QR codes

### 4. Data Confirmation/Edit Screen

Create an inline form showing parsed data with full editing capability:

**Features** (in `scan-qr.php`):

- Display all parsed fields in editable form fields
- Show "Scanned Data" vs "Contact Fields" mapping
- Pre-populate form with parsed vCard data
- Allow full editing before submission
- "Accept & Save" and "Cancel" buttons
- Field validation (required: first name, last name, email)

### 5. Backend API Endpoint for QR-Scanned Contacts

Create dedicated endpoint for saving QR-scanned contacts with metadata:

**File**: `web/user/api/create-contact-from-qr.php`

- Authenticate user via `UserAuth::isLoggedIn()`
- Validate required fields (first_name, last_name, email_primary)
- Insert contact with `source='qr_scan'`
- Save metadata in `source_metadata` JSON:
  - scan_timestamp (ISO 8601)
  - user_agent
  - device_type (mobile/desktop)
  - camera_used (front/back/unknown)
- Return JSON response with contact_id and success status
- Use database transaction for atomicity

### 6. Add "Scan QR Code" Buttons

Add navigation to QR scanner from two locations:

**File**: `web/user/dashboard.php` (line ~365, after "Create New Card" button)

```php
<a href="/user/contacts/scan-qr.php" class="btn-large" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    📷 Scan QR Code
</a>
```

**File**: `web/user/contacts/index.php` (line ~395, in search-filter div)

```php
<a href="/user/contacts/scan-qr.php" class="add-contact-btn" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    📷 Scan QR Code
</a>
```

### 7. Update Contact Display to Show Source

Enhance contact listing to show source badge:

**File**: `web/user/contacts/index.php` (line ~442)

- Add conditional badge for `source='qr_scan'` contacts
- Display as "QR Scanned" badge with distinct color (purple/blue)
- Update filter dropdown to include "QR Scanned" option

### 8. iOS Future Implementation Note

Create documentation file for future iOS implementation:

**File**: `QRCard/QR-SCANNER-TODO.md`

- Document the web implementation approach
- Note the vCard format parsing logic
- Reference Swift libraries: AVFoundation for camera, VisionKit for QR detection
- Link to the web API endpoint for consistency

## Key Files to Create/Modify

**New Files:**

1. `web/config/migrations/add_contact_source_tracking.sql` - Database migration
2. `web/user/contacts/scan-qr.php` - Main QR scanner page
3. `web/user/api/create-contact-from-qr.php` - Backend API endpoint
4. `QRCard/QR-SCANNER-TODO.md` - Future iOS implementation notes

**Modified Files:**

1. `web/user/dashboard.php` - Add "Scan QR Code" button
2. `web/user/contacts/index.php` - Add "Scan QR Code" button and source filter
3. `web/user/contacts/create.php` - (Optional) Add link to QR scanner as alternative

## Technical Details

**QR Code Library**: html5-qrcode (v2.3.8)

- CDN: `https://unpkg.com/html5-qrcode`
- Zero dependencies, works on all modern browsers
- Mobile-friendly with camera selection

**vCard Format Support**: vCard 3.0 and 4.0

- Handle both `\n` and `\r\n` line breaks
- Support escaped characters in values
- Parse multiple TYPE parameters (e.g., `TEL;TYPE=WORK,VOICE`)

**Security Considerations:**

- Session-based authentication (existing UserAuth system)
- SQL injection prevention via prepared statements
- XSS prevention via input sanitization
- Camera permissions requested via browser API

**Browser Compatibility:**

- Chrome/Edge: Full support (desktop + mobile)
- Safari: iOS 14.3+ required for camera API
- Firefox: Full support
- Graceful degradation message for unsupported browsers