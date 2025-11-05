# Name Tag Variant: "Hello My Name Is" (QR Code Surrounding Name)

## Overview
Create a new name tag variant called "Hello My Name Is" where the QR code dynamically wraps around/surrounds the person's name, creating a unique visual design. The QR code shape adapts to the name length (rectangular for long names, square for short names).

## Goals
- Create a new name tag variant that doesn't modify existing name tags
- QR code surrounds/wraps around the person's name
- Adaptive QR code shape based on name length
- Customizable top and bottom banner sections
- Maintain same dimensions as existing name tags (3.375" x 2.33")
- Generate 8 tags per 8.5"x11" sheet (same as existing)

## Technical Requirements

### 1. Dimensions & Layout

#### Tag Dimensions (Same as Existing)
- **Width**: 243pt (3.375" x 72pt/inch)
- **Height**: 168pt (2.33" x 72pt/inch)
- **Sheet**: 8.5" x 11" (612pt x 792pt)
- **Layout**: 2 columns x 4 rows = 8 tags per sheet

#### Layout Structure
```
┌─────────────────────────────────┐
│ Top Banner (colored, text)      │ ← Customizable height (~15-20pt)
├─────────────────────────────────┤
│                                 │
│  ┌──────────────────────────┐  │
│  │  QR Code (wrapping name)  │  │ ← Adaptive size based on name
│  │  ┌──────────────┐         │  │
│  │  │   NAME       │         │  │ ← Single word, centered
│  │  └──────────────┘         │  │
│  │  (QR code continues)     │  │
│  └──────────────────────────┘  │
│                                 │
├─────────────────────────────────┤
│ Bottom Banner (colored, text)   │ ← Customizable height (~15-20pt)
└─────────────────────────────────┘
```

### 2. QR Code Generation Strategy

#### QR Code Wrapping Concept
- Generate QR code that contains the name as the central text element
- QR code modules (black/white squares) form around the name
- Name text is overlaid on QR code with white background "cutout"
- QR code must remain scannable despite name overlay

#### Implementation Approach
**Option A: Generate QR with Name Embedded (Recommended)**
- Generate standard QR code with business card URL
- Overlay name text with white background/padding
- Ensure QR code remains scannable (high error correction)
- Use QR code library that supports error correction level H (30% recovery)

**Option B: Generate QR with Name as Part of Data**
- Include name in QR code data (but still link to business card)
- Generate QR code, then overlay text
- Less reliable for scanning

**Recommendation**: Option A - Generate standard QR, overlay name text

#### QR Code Size Calculation
- **Available Space**: Tag height - (top banner + bottom banner + padding)
- **Base Calculation**: 
  - Top banner: ~20pt
  - Bottom banner: ~20pt
  - Padding: ~10pt top/bottom
  - Available: ~108pt (168 - 20 - 20 - 20)
- **Name Length Impact**:
  - Short name (3-5 chars): ~70x70pt QR code (square)
  - Medium name (6-8 chars): ~90x60pt QR code (rectangular)
  - Long name (9-12 chars): ~100x50pt QR code (rectangular)
- **Adaptive Algorithm**: Calculate QR size based on name width + padding

### 3. Top Banner Section

#### Design
- **Height**: 15-20pt (configurable)
- **Background Color**: Black (default, customizable)
- **Text Color**: White
- **Text**: Customizable (default: "Hello My Name Is...")
- **Font**: Bold, readable size (~8-10pt)
- **Alignment**: Centered horizontally

#### Customization Options
- Text content (user input field)
- Background color (color picker, default: black)
- Text color (default: white, but could be customizable)
- Font size (optional, default: 8pt)

### 4. Bottom Banner Section

#### Design
- **Height**: 15-20pt (configurable)
- **Background Color**: Black (default, customizable)
- **Text Color**: White
- **Text**: Customizable (user-defined)
- **Font**: Bold, readable size (~8-10pt)
- **Alignment**: Centered horizontally

#### Customization Options
- Text content (user input field)
- Background color (color picker, default: black)
- Text color (default: white, but could be customizable)
- Font size (optional, default: 8pt)

### 5. Name Display

#### Constraints
- **Single Word Only**: Only one word allowed (name validation)
- **Centered**: Horizontally and vertically centered in QR area
- **Font**: Bold, large, readable (~24-36pt depending on name length)
- **Color**: Black text on white background (within QR code)
- **Background**: White "cutout" area around name in QR code

#### Name Validation
- Check for spaces (reject if multiple words)
- Check for special characters (allow hyphens, apostrophes in single word)
- Maximum length: ~12-15 characters (to fit in QR code)
- Minimum length: 2 characters

#### Text Sizing Algorithm
```php
function calculateNameFontSize($name, $qrWidth, $qrHeight) {
    $nameLength = mb_strlen($name);
    $maxWidth = $qrWidth * 0.8; // 80% of QR width
    $maxHeight = $qrHeight * 0.5; // 50% of QR height
    
    // Start with large font
    $fontSize = 36;
    
    // Test text width
    while ($this->getTextWidth($name, $fontSize) > $maxWidth && $fontSize > 12) {
        $fontSize -= 2;
    }
    
    // Ensure doesn't exceed height
    if ($fontSize > $maxHeight) {
        $fontSize = $maxHeight;
    }
    
    return max(12, $fontSize); // Minimum 12pt
}
```

### 6. QR Code Generation

#### QR Code Library
- Use existing QR code library (likely TCPDF's QR code or external API)
- Ensure high error correction level (Level H - 30% recovery)
- Generate QR code with business card URL: `https://sharemycard.app/card.php?id={id}&src=nametag`

#### QR Code Overlay Process
1. Generate standard QR code image
2. Calculate name text dimensions
3. Create white "cutout" rectangle for name (with padding)
4. Draw white rectangle on QR code
5. Draw black name text on white rectangle
6. Ensure QR code remains scannable (test with QR scanner)

#### Error Correction
- Use Level H (High) error correction (30% recovery)
- Allows QR code to be scanned even with name overlay
- Larger QR code size (more modules) but more reliable

### 7. Database Schema

#### New Preferences Table or Add to Existing
```sql
CREATE TABLE name_tag_qr_surround_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(36) NOT NULL,
    card_id VARCHAR(36) NOT NULL,
    top_banner_text VARCHAR(100) DEFAULT 'Hello My Name Is...',
    top_banner_color VARCHAR(7) DEFAULT '#000000', -- Hex color
    bottom_banner_text VARCHAR(100) DEFAULT '',
    bottom_banner_color VARCHAR(7) DEFAULT '#000000',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (card_id) REFERENCES business_cards(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_card (user_id, card_id)
);
```

#### Migration
- Add to existing `name_tag_preferences` table OR
- Create new table for this variant (recommended for separation)

### 8. User Interface

#### New Page: `name-tags-qr-surround.php`
- Page title: "Hello My Name Is"
- Similar to existing `name-tags.php` but for QR surround variant
- Form fields:
  - Name (single word input with validation)
  - Top banner text (default: "Hello My Name Is...")
  - Top banner color (color picker, default: black)
  - Bottom banner text (optional)
  - Bottom banner color (color picker, default: black)
  - Preview button
  - Generate PDF button

#### Validation
- Name must be single word (no spaces)
- Name length: 2-15 characters
- Top/bottom banner text: max 100 characters
- Color validation (hex format)

#### Preview
- Live preview of single name tag
- Shows how QR code wraps around name
- Shows top and bottom banners
- Updates in real-time as user changes options

### 9. Implementation Details

#### File Structure
```
web/user/cards/
  ├── name-tags-qr-surround.php (new) - User interface
  ├── preview-name-tag-qr-surround.php (new) - Preview generation
  ├── download-name-tags-qr-surround.php (new) - PDF download
  └── download-name-tags-qr-surround-image.php (new) - Image download

web/api/includes/
  └── NameTagGenerator.php - Add new methods for QR surround variant
```

#### New Methods in NameTagGenerator Class

```php
/**
 * Generate QR surround name tag sheet
 */
public function generateQRSurroundNameTagSheet($cardId, $preferences, $name) {
    // Validate name (single word)
    // Generate PDF with 8 tags
    // Each tag uses QR surround layout
}

/**
 * Generate single QR surround name tag
 */
private function addQRSurroundNameTag($pdf, $x, $y, $cardData, $preferences, $name) {
    // Draw top banner
    // Generate and draw QR code with name overlay
    // Draw bottom banner
}

/**
 * Generate QR code with name overlay
 */
private function generateQRCodeWithNameOverlay($cardId, $name, $size) {
    // Generate QR code
    // Calculate name text size
    // Create white cutout
    // Overlay name text
    // Return image
}

/**
 * Calculate adaptive QR code size based on name
 */
private function calculateQRSizeForName($name, $availableWidth, $availableHeight) {
    // Calculate name width
    // Determine optimal QR dimensions
    // Return width, height
}
```

### 10. QR Code Overlay Algorithm

#### Step-by-Step Process
1. **Generate Base QR Code**
   - Generate QR code with Level H error correction
   - URL: `https://sharemycard.app/card.php?id={id}&src=nametag-qr-surround`
   - Size: Calculate based on available space and name length

2. **Calculate Name Dimensions**
   - Measure name text width at various font sizes
   - Determine optimal font size (12-36pt range)
   - Calculate padding around name (10-15pt)

3. **Create White Cutout**
   - Calculate cutout rectangle: name width + padding, name height + padding
   - Center cutout in QR code
   - Draw white rectangle on QR code image

4. **Overlay Name Text**
   - Draw black text on white cutout
   - Center horizontally and vertically
   - Ensure text is readable and professional

5. **Verify Scannability**
   - Test QR code with scanner after overlay
   - Adjust padding if needed
   - Ensure error correction is sufficient

### 11. Adaptive QR Code Sizing

#### Name Length Categories
- **Short (3-5 chars)**: "Mark", "John", "Anna"
  - QR: ~70x70pt (square)
  - Font: ~28-32pt
  
- **Medium (6-8 chars)**: "Michael", "Jennifer"
  - QR: ~85x60pt (rectangular, wider)
  - Font: ~24-28pt
  
- **Long (9-12 chars)**: "Christopher", "Elizabeth"
  - QR: ~100x50pt (rectangular, wider)
  - Font: ~20-24pt

#### Calculation Algorithm
```php
function calculateAdaptiveQRSize($name, $maxWidth, $maxHeight) {
    $nameLength = mb_strlen($name);
    $nameWidth = $this->estimateTextWidth($name, 24); // Estimate at 24pt
    
    // Padding around name
    $namePadding = 20; // 10pt on each side
    
    // Minimum QR size (for short names)
    $minSize = 60;
    
    // Calculate QR width (name width + padding, but at least minSize)
    $qrWidth = max($minSize, $nameWidth + $namePadding);
    
    // Calculate QR height (maintain aspect ratio, but adapt to name)
    if ($nameLength <= 5) {
        // Square for short names
        $qrHeight = $qrWidth;
    } else if ($nameLength <= 8) {
        // Slightly rectangular for medium names
        $qrHeight = $qrWidth * 0.75;
    } else {
        // More rectangular for long names
        $qrHeight = $qrWidth * 0.6;
    }
    
    // Ensure fits in available space
    $qrWidth = min($qrWidth, $maxWidth);
    $qrHeight = min($qrHeight, $maxHeight);
    
    return ['width' => $qrWidth, 'height' => $qrHeight];
}
```

### 12. Color Customization

#### Initial Implementation
- **Top Banner**: Black background (#000000), white text
- **Bottom Banner**: Black background (#000000), white text
- **Future Enhancement**: Color picker for both banners

#### Color Storage
- Store as hex color codes (#RRGGBB)
- Validate hex format
- Default: #000000 (black)

#### Future Color Options
- Predefined color palette
- Custom color picker
- Contrast validation (ensure text is readable)

### 13. PDF Generation

#### TCPDF Integration
- Use existing TCPDF setup
- Same sheet layout as existing name tags (2x4 grid)
- Same margins and spacing
- Generate 8 identical tags per sheet

#### High-Resolution Support
- 300 DPI for printing
- Generate high-res images for preview
- Ensure QR code is scannable at print size

### 14. Testing Requirements

#### QR Code Scannability
- [ ] Test with multiple QR scanners (iPhone, Android, web)
- [ ] Test with different name lengths
- [ ] Verify error correction handles name overlay
- [ ] Test at various print sizes

#### Name Validation
- [ ] Single word only (reject multiple words)
- [ ] Minimum length (2 characters)
- [ ] Maximum length (15 characters)
- [ ] Special characters (hyphens, apostrophes allowed)
- [ ] Empty name handling

#### Layout Testing
- [ ] Short names (3-5 chars) - square QR
- [ ] Medium names (6-8 chars) - rectangular QR
- [ ] Long names (9-12 chars) - wider rectangular QR
- [ ] Top banner displays correctly
- [ ] Bottom banner displays correctly
- [ ] Name is centered in QR code
- [ ] White cutout is visible and readable

#### Print Testing
- [ ] 8 tags fit on 8.5"x11" sheet
- [ ] Tags align with standard label sheets
- [ ] Colors print correctly (black/white)
- [ ] Text is readable at print size
- [ ] QR codes scan after printing

### 15. User Experience Flow

#### Step 1: Access Page
- User navigates to "Hello My Name Is" name tags (new menu item or variant selector)
- Page loads with default settings
- Page title displays "Hello My Name Is"

#### Step 2: Enter Name
- User enters single word name
- Real-time validation (shows error if multiple words)
- Character count indicator

#### Step 3: Customize Banners
- User enters top banner text (default: "Hello My Name Is...")
- User enters bottom banner text (optional)
- Color pickers for both banners (future: start with black)

#### Step 4: Preview
- User clicks "Preview" button
- Shows single name tag preview
- Updates in real-time as user changes options

#### Step 5: Generate PDF
- User clicks "Generate PDF" button
- Creates PDF with 8 name tags
- Downloads to user's device

### 16. Implementation Phases

#### Phase 1: Core Functionality (Week 1)
1. Create database table for preferences
2. Create user interface page
3. Implement basic QR code generation
4. Implement name overlay on QR code
5. Test QR code scannability

#### Phase 2: Adaptive Sizing (Week 1-2)
1. Implement adaptive QR size calculation
2. Test with various name lengths
3. Refine sizing algorithm
4. Ensure readability and scannability

#### Phase 3: Banners (Week 2)
1. Implement top banner rendering
2. Implement bottom banner rendering
3. Add customization options
4. Test banner display

#### Phase 4: PDF Generation (Week 2)
1. Integrate with TCPDF
2. Generate 8-tag sheet
3. Test print layout
4. Verify dimensions match existing name tags

#### Phase 5: Polish & Testing (Week 2-3)
1. Add validation and error handling
2. Test with real QR scanners
3. Print test on physical labels
4. Refine UI/UX
5. Documentation

## Files to Create/Modify

### New Files
- `web/user/cards/name-tags-qr-surround.php` - User interface (Page title: "Hello My Name Is")
- `web/user/cards/preview-name-tag-qr-surround.php` - Preview generation
- `web/user/cards/download-name-tags-qr-surround.php` - PDF download
- `web/user/cards/download-name-tags-qr-surround-image.php` - Image download
- `web/config/migrations/XXX_add_qr_surround_preferences.sql` - Database migration

### Modified Files
- `web/api/includes/NameTagGenerator.php` - Add QR surround methods
- `web/user/cards/name-tags.php` - Add link to "Hello My Name Is" variant (optional)
- `web/user/name-tags/index.php` - Add link to "Hello My Name Is" variant
- Navigation menu - Add option for "Hello My Name Is" variant (optional)

## Technical Challenges

### Challenge 1: QR Code Scannability
**Problem**: Overlaying name text on QR code may break scanning
**Solution**: 
- Use Level H error correction (30% recovery)
- Ensure white cutout doesn't break too many QR modules
- Test extensively with real scanners
- Adjust padding if needed

### Challenge 2: Adaptive Sizing
**Problem**: QR code must adapt to name length while remaining scannable
**Solution**:
- Calculate name text width first
- Determine optimal QR dimensions based on name
- Maintain minimum QR size for scannability
- Test with various name lengths

### Challenge 3: Name Validation
**Problem**: Ensuring only single word is entered
**Solution**:
- Real-time validation on input
- Server-side validation
- Clear error messages
- Character count indicator

### Challenge 4: Text Sizing
**Problem**: Name text must fit in QR code while being readable
**Solution**:
- Dynamic font sizing algorithm
- Test with various name lengths
- Ensure minimum readable size (12pt)
- Balance readability with QR code size

## Future Enhancements

- **Color Customization**: Full color picker for banners
- **Font Options**: Different fonts for name text
- **Multiple Variants**: Different QR surround styles
- **Profile Photo**: Optional small photo in corner
- **Company Logo**: Optional logo in banner
- **Template Library**: Pre-designed templates
- **Batch Generation**: Generate for multiple cards at once

## Resources

- [TCPDF Documentation](https://tcpdf.org/docs/)
- [QR Code Error Correction](https://en.wikipedia.org/wiki/QR_code#Error_correction)
- [PHP QR Code Libraries](https://github.com/search?q=php+qr+code)
- [Name Tag Label Specifications](https://www.onlinelabels.com/name-tags/3-375-x-2-33)

## Implementation Task List

### Phase 1: Database & Setup
- [ ] Create database migration for `name_tag_qr_surround_preferences` table
- [ ] Add navigation link to new variant in name tags menu

### Phase 2: Core Functionality
- [ ] Create user interface page (`name-tags-qr-surround.php`) with form fields
- [ ] Implement name validation (single word only, 2-15 characters)
- [ ] Add QR code generation method with Level H error correction
- [ ] Implement adaptive QR size calculation based on name length
- [ ] Create QR code overlay algorithm (white cutout + name text)

### Phase 3: UI Components
- [ ] Implement top banner rendering (customizable text and color)
- [ ] Implement bottom banner rendering (customizable text and color)

### Phase 4: PDF/Image Generation
- [ ] Add `generateQRSurroundNameTagSheet` method to NameTagGenerator class
- [ ] Create preview generation page (`preview-name-tag-qr-surround.php`)
- [ ] Create PDF download page (`download-name-tags-qr-surround.php`)
- [ ] Create image download page (`download-name-tags-qr-surround-image.php`)

### Phase 5: Testing
- [ ] Test QR code scannability with various name lengths
- [ ] Test with multiple QR scanners (iPhone, Android, web)
- [ ] Test print layout and dimensions (8 tags per sheet)

## Notes

- This is a new variant - existing name tags remain unchanged
- Same dimensions ensure compatibility with existing label sheets
- QR code must remain scannable - this is critical
- Start with black/white color scheme, add color customization later
- Test extensively with physical QR scanners before release
- Consider user feedback on QR code scannability and adjust if needed

