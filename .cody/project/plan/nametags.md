# Name Tag Printing System Implementation Plan

## Overview
Create a system to generate printable name tag PDFs with 8 name tags per standard 8.5" x 11" sheet. Each name tag is 3.375" x 2.33" and includes customizable contact information plus a QR code linking to the public card view.

## Implementation Steps

### 1. Database Schema Updates
**File:** `web/config/migrations/014_name_tag_preferences.sql` (new)

Create table to store user preferences for name tag generation:
```sql
CREATE TABLE name_tag_preferences (
    id CHAR(36) PRIMARY KEY,
    card_id CHAR(36) NOT NULL,
    include_signature ENUM('none', 'profile', 'logo') DEFAULT 'profile',
    include_name BOOLEAN DEFAULT TRUE,
    include_title BOOLEAN DEFAULT TRUE,
    include_phone BOOLEAN DEFAULT TRUE,
    include_email BOOLEAN DEFAULT TRUE,
    include_address BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (card_id) REFERENCES business_cards(id) ON DELETE CASCADE,
    INDEX idx_nt_card_id (card_id)
);
```

### 2. Add TCPDF Library
**Action:** Install TCPDF via Composer or manual download

TCPDF is the most accurate PDF generation library for PHP and handles precise measurements well.

**File:** `web/composer.json` (new or update)
```json
{
    "require": {
        "tecnickcom/tcpdf": "^6.6"
    }
}
```

Or manual installation:
- Download TCPDF from https://github.com/tecnickcom/TCPDF
- Place in `web/api/includes/tcpdf/`

### 3. Name Tag Generator Class
**File:** `web/api/includes/NameTagGenerator.php` (new)

Create PDF generation class using TCPDF:

Key methods:
- `generateNameTagSheet($cardId, $preferences)` - Main method that creates 8-up sheet
- `addNameTag($pdf, $x, $y, $cardData, $preferences)` - Add single name tag at position
- `addSignatureImage($pdf, $x, $y, $imagePath, $imageType)` - Add profile/logo (top center)
- `addQRCode($pdf, $x, $y, $cardUrl)` - Add QR code (right side)
- `addContactInfo($pdf, $x, $y, $cardData, $preferences)` - Add text fields (left side)
- `getNameTagPositions()` - Calculate 8 positions on 8.5x11 sheet

Label specifications (based on reference PDF):
- Sheet: 8.5" x 11" (612pt x 792pt)
- Name tag: 3.375" x 2.33" (243pt x 168pt)
- Layout: 2 columns x 4 rows
- Margins: ~0.4" top, 0.5" between tags
- Positions calculated to match standard label sheets

Layout per name tag:
```
┌─────────────────────────┐
│   [Profile/Logo]        │ Top center (if included)
│                         │
│   JOHN DOE             │ Name (large, bold)
│   Senior Developer      │ Title
│   +1 (555) 123-4567    │ Phone
│   john@example.com     │ Email        [QR]
│   123 Main St...       │ Address      [CODE]
└─────────────────────────┘
```

### 4. Name Tag Customization Page
**File:** `web/user/cards/name-tags.php` (new)

Create page with live preview and customization controls:

Structure:
```php
// Authentication and card loading
UserAuth::requireAuth();
$cardId = $_GET['id'] ?? '';

// Load card, preferences, contact info
$card = $db->querySingle("SELECT * FROM business_cards WHERE id = ? AND user_id = ?", [$cardId, $userId]);
$preferences = loadPreferences($cardId);

// Handle preference saves (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    savePreferences($cardId, $_POST);
}
```

UI Layout:
```html
<div class="name-tag-container">
    <!-- Card Info Header -->
    <div class="card-header">
        <h1>Name Tag Creator</h1>
        <p>Card: John Doe - Senior Developer</p>
    </div>
    
    <!-- Live Preview (single name tag) -->
    <div class="preview-section">
        <h2>Preview</h2>
        <div class="name-tag-preview">
            <!-- Live preview of one name tag with current settings -->
            <img id="preview-image" src="" alt="Name tag preview">
        </div>
    </div>
    
    <!-- Customization Controls -->
    <div class="controls-section">
        <h2>Customize Name Tag</h2>
        
        <div class="control-group">
            <label>Signature Image:</label>
            <select name="include_signature">
                <option value="none">None</option>
                <option value="profile">Profile Photo</option>
                <option value="logo">Company Logo</option>
            </select>
        </div>
        
        <div class="control-group">
            <label><input type="checkbox" name="include_name" checked> Include Name</label>
        </div>
        
        <div class="control-group">
            <label><input type="checkbox" name="include_title" checked> Include Job Title</label>
        </div>
        
        <div class="control-group">
            <label><input type="checkbox" name="include_phone" checked> Include Primary Phone</label>
        </div>
        
        <div class="control-group">
            <label><input type="checkbox" name="include_email" checked> Include Primary Email</label>
        </div>
        
        <div class="control-group">
            <label><input type="checkbox" name="include_address"> Include Address</label>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="actions">
        <button onclick="savePreferences()" class="btn btn-secondary">Save Settings</button>
        <button onclick="downloadPDF()" class="btn btn-primary">Download PDF (8 tags)</button>
    </div>
</div>
```

JavaScript for preview updates:
```javascript
function updatePreview() {
    const params = new URLSearchParams({
        card_id: cardId,
        preview: 'true',
        include_signature: form.include_signature.value,
        include_name: form.include_name.checked ? '1' : '0',
        include_title: form.include_title.checked ? '1' : '0',
        include_phone: form.include_phone.checked ? '1' : '0',
        include_email: form.include_email.checked ? '1' : '0',
        include_address: form.include_address.checked ? '1' : '0'
    });
    
    document.getElementById('preview-image').src = 
        `/user/cards/preview-name-tag.php?${params.toString()}`;
}

function downloadPDF() {
    const params = new URLSearchParams(/* same as above but preview: false */);
    window.location.href = `/user/cards/download-name-tags.php?${params.toString()}`;
}
```

### 5. Preview Generation Endpoint
**File:** `web/user/cards/preview-name-tag.php` (new)

Generate PNG preview of single name tag:
```php
// Load card and preferences from GET params
// Use PHP GD library to generate image preview
// Output as PNG with proper headers
// No authentication required (like virtual background preview)

$generator = new NameTagGenerator();
$image = $generator->generatePreviewImage($cardId, $preferences);

header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);
```

### 6. PDF Download Endpoint
**File:** `web/user/cards/download-name-tags.php` (new)

Generate and download full PDF with 8 name tags:
```php
require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../../api/includes/NameTagGenerator.php';

UserAuth::requireAuth();

// Validate parameters
$cardId = $_GET['card_id'] ?? '';
$preferences = [
    'include_signature' => $_GET['include_signature'] ?? 'profile',
    'include_name' => ($_GET['include_name'] ?? '1') === '1',
    'include_title' => ($_GET['include_title'] ?? '1') === '1',
    'include_phone' => ($_GET['include_phone'] ?? '1') === '1',
    'include_email' => ($_GET['include_email'] ?? '1') === '1',
    'include_address' => ($_GET['include_address'] ?? '0') === '1'
];

// Verify card ownership
$card = $db->querySingle(
    "SELECT * FROM business_cards WHERE id = ? AND user_id = ? AND is_active = 1",
    [$cardId, UserAuth::getUserId()]
);

if (!$card) {
    die('Card not found or access denied');
}

// Generate PDF
$generator = new NameTagGenerator();
$pdf = $generator->generateNameTagSheet($cardId, $preferences);

// Output PDF
$filename = str_replace(' ', '_', $card['first_name'] . '_' . $card['last_name']) . '_name_tags.pdf';
$pdf->Output($filename, 'D'); // 'D' = download
```

### 7. API Endpoint for Mobile
**File:** `web/api/cards/name-tags.php` (new)

JWT-authenticated endpoint for iOS app:
```php
// Validate JWT token
// Verify card ownership
// Generate PDF
// Stream or return base64 encoded PDF
```

### 8. Preferences Save/Load API
**File:** `web/api/cards/name-tag-preferences.php` (new)

Session-based API for web interface:
- GET: Load saved preferences
- POST: Save new preferences
- Returns JSON with preference data

### 9. Add "Print Tools" Navigation Section

**Update:** `web/user/cards/view.php`

Add new section in the action buttons area (around line 200-250):
```php
<div class="action-section">
    <h3>Print & Download Tools</h3>
    <div class="action-buttons">
        <a href="/user/cards/qr.php?id=<?= $card['id'] ?>" class="btn">
            📱 QR Code
        </a>
        <a href="/user/cards/virtual-background.php?id=<?= $card['id'] ?>" class="btn">
            🖼️ Virtual Background
        </a>
        <a href="/user/cards/name-tags.php?id=<?= $card['id'] ?>" class="btn">
            🏷️ Name Tags
        </a>
    </div>
</div>
```

**Update:** `web/user/dashboard.php`

Add name tags button to card actions (around line 150-180):
```php
<div class="card-actions">
    <a href="/user/cards/view.php?id=<?= $card['id'] ?>" class="btn-small">View</a>
    <a href="/user/cards/edit.php?id=<?= $card['id'] ?>" class="btn-small">Edit</a>
    <a href="/user/cards/name-tags.php?id=<?= $card['id'] ?>" class="btn-small">🏷️ Name Tags</a>
</div>
```

**Update:** `web/user/cards/qr.php`

Add name tags link alongside virtual background link (around line 100-120):
```php
<div class="download-options">
    <a href="/user/cards/virtual-background.php?id=<?= $cardId ?>" class="btn">
        🖼️ Virtual Background
    </a>
    <a href="/user/cards/name-tags.php?id=<?= $cardId ?>" class="btn">
        🏷️ Name Tags
    </a>
</div>
```

### 10. CSS Styling
**File:** `web/user/includes/user-style.css` (update)

Add name tag specific styles:
```css
.name-tag-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 30px;
}

.preview-section {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin: 20px 0;
    text-align: center;
}

.name-tag-preview {
    display: inline-block;
    border: 2px dashed #ccc;
    padding: 20px;
    background: #f9f9f9;
}

.name-tag-preview img {
    max-width: 100%;
    height: auto;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.controls-section {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin: 20px 0;
}

.control-group {
    margin: 15px 0;
}

.control-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

.control-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
}

.control-group input[type="checkbox"] {
    margin-right: 8px;
}
```

## Technical Specifications

### PDF Layout Calculations
Based on Avery 5395 / similar 8-up labels (3.375" x 2.33"):
- Sheet dimensions: 612pt x 792pt (8.5" x 11")
- Label dimensions: 243pt x 168pt (3.375" x 2.33")
- Column spacing: 243pt + margins
- Row spacing: 168pt + margins
- Top margin: ~36pt
- Left margin: ~13pt
- Horizontal gap: ~18pt
- Vertical gap: ~0pt

8 positions (column, row):
1. (1,1) - Top left
2. (2,1) - Top right
3. (1,2) - Second row left
4. (2,2) - Second row right
5. (1,3) - Third row left
6. (2,3) - Third row right
7. (1,4) - Fourth row left
8. (2,4) - Fourth row right

### QR Code Details
- Size: 1 inch (72pt) square
- Position: Right side, vertically centered
- URL format: `https://sharemycard.app/card.php?id={card_id}`
- Error correction: Medium (M)

### Signature Image Specifications
- Position: Top center, above name
- Profile photo: Circular crop, 0.75" diameter (54pt)
- Company logo: Rectangle, max 1.5" wide x 0.6" tall (108pt x 43pt)
- Maintain original colors (no black/white conversion)

### Typography
- Name: Bold, 14pt
- Title: Regular, 10pt
- Contact info: Regular, 9pt
- All text: Black (#000000)
- Font: Helvetica (built-in to PDF)

### Contact Info Display Rules
- Show primary phone only (if exists and enabled)
- Show primary email only (if exists and enabled)
- Show full address formatted as: "Street, City, ST ZIP" (if enabled)
- Truncate long text with ellipsis if exceeds space

## Security & Validation
- Verify card ownership before PDF generation
- Rate limit: 20 generations per hour per user
- Validate all boolean flags
- Validate signature image selection (none/profile/logo)
- Sanitize all text inputs
- Check file size limits (max 5MB PDF output)
- Verify media files exist before including in PDF

## Testing Checklist
- [ ] Generate name tags with all combinations of included fields
- [ ] Test with profile photo, company logo, and no signature
- [ ] Verify QR code scannability when printed
- [ ] Test PDF print alignment on actual label sheets
- [ ] Verify 8 name tags appear correctly on sheet
- [ ] Test with missing data (no email, no phone, etc.)
- [ ] Test with very long names/titles/addresses
- [ ] Verify card ownership validation
- [ ] Test preference save/load functionality
- [ ] Test live preview updates
- [ ] Verify downloads work in all browsers
- [ ] Print actual labels and verify alignment

## File Structure
```
web/
├── user/cards/
│   ├── name-tags.php (new)
│   ├── download-name-tags.php (new)
│   └── preview-name-tag.php (new)
├── api/cards/
│   ├── name-tags.php (new)
│   └── name-tag-preferences.php (new)
├── api/includes/
│   ├── NameTagGenerator.php (new)
│   └── tcpdf/ (library)
└── config/migrations/
    └── 014_name_tag_preferences.sql (new)
```

## Implementation To-dos

- [ ] Install TCPDF library via Composer or manual download
- [ ] Create database migration for name tag preferences table
- [ ] Implement NameTagGenerator class with TCPDF integration and 8-up layout
- [ ] Create name-tags.php page with live preview and customization controls
- [ ] Build preview-name-tag.php endpoint for single tag PNG preview
- [ ] Build download-name-tags.php endpoint for full PDF generation
- [ ] Create name-tag-preferences.php API for save/load functionality
- [ ] Add Print Tools navigation section to view.php, dashboard.php, and qr.php
- [ ] Add CSS styling for name tag interface to user-style.css
- [ ] Test PDF generation with all field combinations and verify print alignment

