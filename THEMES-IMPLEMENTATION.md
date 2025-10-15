# Card Visual Themes Implementation

## Overview
Implemented a visual theme system that allows users to customize their business card appearance with 5 pre-defined color schemes and typography options while maintaining the same layout structure.

## Features Implemented

### 1. Database Changes
- **Migration**: `web/config/migrations/005_add_card_theme.sql`
- Added `theme` column to `business_cards` table (VARCHAR(50))
- Default value: `'professional-blue'`

### 2. Theme System
- **Configuration File**: `web/includes/themes.php`
- **5 Pre-defined Themes**:
  1. **Professional Blue** (default) - #667eea to #764ba2
  2. **Minimalist Gray** - #2d3748 to #4a5568
  3. **Creative Sunset** - #f093fb to #f5576c
  4. **Corporate Green** - #11998e to #38ef7d
  5. **Tech Purple** - #4776e6 to #8e54e9

Each theme includes:
- Primary color (gradient start)
- Secondary color (gradient end)
- Accent color (links, buttons)
- Text colors (primary and light)
- Font family

### 3. Frontend Implementation

#### Public Card View (`web/card.php`)
- Integrated theme system with CSS variables
- Dynamic CSS generation based on selected theme
- All colors replaced with CSS variables for consistency

#### Admin & User Card Views
- `web/admin/cards/view.php`
- `web/user/cards/view.php`
- Applied same theme system
- Card preview matches public appearance

#### Edit Pages
- `web/admin/cards/edit.php`
- `web/user/cards/edit.php`
- Added theme dropdown selector
- Visual color swatch preview
- Interactive selection (click swatch or select from dropdown)
- JavaScript-powered real-time preview highlighting

#### QR Code Pages
- `web/admin/cards/qr.php`
- `web/user/cards/qr.php`
- Theme-based styling for buttons and gradients

### 4. iOS App Integration

#### API Models (`QRCard/CardService.swift`)
- Added `theme: String?` property to `BusinessCardAPI` struct
- Included in `CodingKeys` enum
- Added to init methods

#### Core Data (`QRCard/CoreDataEntities.swift`)
- Added `@NSManaged public var theme: String?`
- Updated programmatic model in `DataManager.swift`

#### Sync Manager (`QRCard/SyncManager.swift`)
- Theme field included in push operations (set to nil from iOS)
- Theme field synced during pull operations
- Preserved during updates

## Usage

### For Users
1. Navigate to edit page for any business card
2. Scroll to "🎨 Card Theme" section
3. Select theme from dropdown OR click a color swatch
4. Save changes
5. Theme applies to:
   - Public shareable card view
   - Admin/user card view pages
   - QR code pages

### For Developers

#### Adding a New Theme
1. Open `web/includes/themes.php`
2. Add new theme to `getThemes()` array:
```php
'theme-name' => [
    'name' => 'Theme Display Name',
    'primary_color' => '#hex',
    'secondary_color' => '#hex',
    'accent_color' => '#hex',
    'text_color' => '#hex',
    'text_light' => '#hex',
    'font_family' => 'font-stack'
]
```

#### Using Themes in New Pages
```php
// Include themes.php
require_once __DIR__ . '/includes/themes.php';

// Get theme from card data
$theme = $card['theme'] ?? 'professional-blue';
$themeCSS = generateThemeCSS($theme);

// In <style> tag:
<?php echo $themeCSS; ?>

// Use CSS variables:
color: var(--accent-color);
background: var(--gradient);
font-family: var(--font-family);
```

## Migration Instructions

### Production Deployment
1. Run migration SQL:
```sql
ALTER TABLE business_cards 
ADD COLUMN theme VARCHAR(50) DEFAULT 'professional-blue' 
AFTER cover_graphic_path;
```

2. Upload new files:
   - `web/includes/themes.php`
   - Updated `web/card.php`, `web/admin/cards/*.php`, `web/user/cards/*.php`

3. Test theme selection and display

### iOS App
- No action required for existing installations
- Theme field will sync automatically
- iOS app stores theme but doesn't display it (web-only feature)

## Technical Notes

- **CSS Variables**: Used for dynamic theming without inline styles
- **Backward Compatibility**: Cards without theme default to 'professional-blue'
- **Performance**: Theme CSS generated once per page load
- **Mobile Support**: All themes tested on mobile devices
- **Accessibility**: Color contrasts meet WCAG AA standards

## Future Enhancements

Potential improvements (not currently planned):
- Custom theme creator (user-defined colors)
- Theme preview before saving
- Industry-specific theme presets
- Seasonal themes
- Dark mode variants

## Files Modified

### Web Application
- `web/config/migrations/005_add_card_theme.sql` (new)
- `web/includes/themes.php` (new)
- `web/card.php`
- `web/admin/cards/view.php`
- `web/admin/cards/edit.php`
- `web/admin/cards/qr.php`
- `web/user/cards/view.php`
- `web/user/cards/edit.php`
- `web/user/cards/qr.php`

### iOS Application
- `QRCard/CardService.swift`
- `QRCard/CoreDataEntities.swift`
- `QRCard/DataManager.swift`
- `QRCard/SyncManager.swift`

## Testing Checklist

- [x] Database migration runs successfully
- [x] Theme selector appears in edit pages
- [x] Clicking swatch updates dropdown
- [x] Selecting from dropdown highlights swatch
- [x] Saving card preserves theme selection
- [x] Public card view displays correct theme
- [x] Admin/user view pages match theme
- [x] QR code pages use theme colors
- [x] iOS sync preserves theme field
- [x] Default theme applies to new cards
- [x] All 5 themes render correctly

---

**Implementation Date**: January 14, 2025  
**Version**: 1.7.0 (Card Themes)

