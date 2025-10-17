# Virtual Backgrounds Implementation Plan

## Overview
Create a system to generate downloadable virtual background images (for Zoom, Teams, etc.) with embedded business card QR codes. Users can customize QR code placement, size, and text overlay, with theme-matched gradient backgrounds.

## Implementation Steps

### 1. Database Schema Updates
**Files:** `web/config/migrations/012_virtual_backgrounds_preferences.sql` (new)

Create table to store user preferences for virtual background generation:
```sql
CREATE TABLE virtual_background_preferences (
    id CHAR(36) PRIMARY KEY,
    card_id CHAR(36) NOT NULL,
    qr_position ENUM('top-left', 'top-right', 'bottom-left', 'bottom-right') DEFAULT 'bottom-right',
    qr_size INT DEFAULT 300,
    padding_x INT DEFAULT 50,
    padding_y INT DEFAULT 50,
    text_option ENUM('qr-only', 'qr-name', 'qr-name-company') DEFAULT 'qr-only',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (card_id) REFERENCES business_cards(id) ON DELETE CASCADE
);
```

### 2. Virtual Background Generator Class
**Files:** `web/api/includes/VirtualBackgroundGenerator.php` (new)

Create PHP GD library-based image generator:
- `generateBackground($cardId, $width, $height, $preferences)` - Main method
- `createGradientBackground($width, $height, $theme)` - Generate gradient from theme colors
- `embedQRCode($image, $qrCodeData, $position, $size, $padding)` - Place QR code
- `addTextOverlay($image, $text, $position, $qrSize)` - Add name/company text
- `getThemeColors($theme)` - Map themes to gradient colors:
  - Professional Blue: `#667eea` to `#764ba2`
  - Minimalist Gray: `#bdc3c7` to `#2c3e50`
  - Creative Sunset: `#f093fb` to `#f5576c`
  - Corporate Green: `#11998e` to `#38ef7d`
  - Tech Purple: `#4776e6` to `#8e54e9`

### 3. Virtual Background Page
**Files:** `web/user/cards/virtual-background.php` (new)

Create page accessible from multiple locations:
- Check authentication and card ownership
- Load card data and theme
- Display live preview with current settings
- Provide customization controls:
  - QR Position: 4 corner buttons with icons
  - QR Size: Slider (200-500px)
  - Padding X/Y: Sliders (20-200px)
  - Text Option: Radio buttons (QR only / + Name / + Name & Company)
  - Resolution: Dropdown (1920x1080, 1366x768, 2560x1440, 3840x2160)
- Save preferences button
- Download button (generates and downloads image)

UI Layout:
```
[Card Info Header]
[Live Preview Area - shows current background with QR]
[Customization Panel]
  - QR Position (4 corner buttons)
  - QR Size (slider with live value)
  - Padding X/Y (sliders)
  - Text Display (radio buttons)
  - Resolution (dropdown)
[Save Settings] [Download Background]
```

### 4. Background Generation API
**Files:** `web/api/cards/generate-background.php` (new)

Create endpoint to generate and stream image:
- Validate JWT token and card ownership
- Load card data, theme, and preferences
- Call `VirtualBackgroundGenerator` with parameters
- Set proper headers (`Content-Type: image/png`)
- Stream image output
- Log generation for analytics

### 5. Preferences API
**Files:** `web/api/cards/background-preferences.php` (new)

CRUD operations for virtual background preferences:
- `GET` - Retrieve saved preferences for card
- `POST` - Save new preferences
- `PUT` - Update existing preferences
- Returns JSON with preference data

### 6. Integration Points

**Add navigation links:**

**User Dashboard** (`web/user/dashboard.php`):
```html
<a href="/user/cards/virtual-background.php?id=<?= $card['id'] ?>" class="btn-small">
    🖼️ Virtual Background
</a>
```

**Card View Page** (`web/user/cards/view.php`):
```html
<a href="/user/cards/virtual-background.php?id=<?= $card['id'] ?>" class="btn btn-primary">
    🖼️ Create Virtual Background
</a>
```

**QR Code Page** (`web/user/cards/qr.php`):
```html
<a href="/user/cards/virtual-background.php?id=<?= $card['id'] ?>" class="btn">
    🖼️ Virtual Background
</a>
```

### 7. iOS App Integration (Future Phase)

**Files:** `QRCard/VirtualBackgroundView.swift` (new)

- Add "Virtual Background" option to card menu
- Display customization interface
- Call API to generate background
- Save to photo library with proper permissions

## Technical Details

### QR Code Generation
Use existing QR code generation logic from `web/user/cards/qr.php`:
```php
$qrUrl = "https://sharemycard.app/card.php?id=" . $card['id'];
$qrCode = QRCode::generate($qrUrl, 300); // Size adjustable
```

### Gradient Generation Algorithm
```php
function createLinearGradient($image, $width, $height, $color1, $color2) {
    for ($y = 0; $y < $height; $y++) {
        $ratio = $y / $height;
        $r = $color1['r'] + ($color2['r'] - $color1['r']) * $ratio;
        $g = $color1['g'] + ($color2['g'] - $color1['g']) * $ratio;
        $b = $color1['b'] + ($color2['b'] - $color1['b']) * $ratio;
        $color = imagecolorallocate($image, $r, $g, $b);
        imageline($image, 0, $y, $width, $y, $color);
    }
}
```

### Text Overlay Positioning
Calculate text position based on QR position:
- Top positions: Text below QR code
- Bottom positions: Text above QR code
- Use white text with subtle shadow for readability
- Font: Arial, size 48-72pt depending on resolution

### Resolution Scaling
All elements scale proportionally:
- Base resolution: 1920x1080
- QR size multiplier: `$targetWidth / 1920`
- Padding multiplier: Same as QR size
- Font size multiplier: Same as QR size

## File Structure
```
web/
├── user/cards/
│   └── virtual-background.php (new)
├── api/cards/
│   ├── generate-background.php (new)
│   └── background-preferences.php (new)
├── api/includes/
│   └── VirtualBackgroundGenerator.php (new)
└── config/migrations/
    └── 012_virtual_backgrounds_preferences.sql (new)
```

## CSS Styling
Match existing ShareMyCard design:
- Card-style containers with shadows
- Theme-colored accent elements
- Responsive controls with proper spacing
- Preview area with border and loading state
- Button consistency (primary for download, secondary for save)

## Security & Validation
- Verify card ownership before generation
- Rate limit generation requests (10 per hour per user)
- Validate all input parameters (position, size, padding within ranges)
- Sanitize text inputs for overlay
- Check file size limits (max 10MB output)

## Testing Checklist
- [ ] Generate background for each theme
- [ ] Test all 4 corner positions
- [ ] Verify QR code scannability at all sizes
- [ ] Test all 4 resolutions
- [ ] Verify text overlay rendering
- [ ] Test preference save/load
- [ ] Verify downloads work in all browsers
- [ ] Test on mobile devices
- [ ] Verify card ownership validation
- [ ] Test with/without profile photos

## Implementation To-dos
1. Create database migration for virtual background preferences table
2. Implement VirtualBackgroundGenerator class with gradient generation and QR embedding
3. Create virtual-background.php page with live preview and customization controls
4. Build generate-background.php API endpoint for image generation and streaming
5. Create background-preferences.php API for saving user preferences
6. Add Virtual Background navigation links to dashboard, view, and QR pages
7. Test all resolutions, positions, and theme combinations

