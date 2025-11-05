# Standard Name Tag Custom Fonts - Retrospective

**Date:** November 5, 2025  
**Feature:** Custom font support for standard name tags (preview, PDF, and HTML generation)

## Overview

Added custom font support to the standard name tag functionality, allowing users to select from available TrueType fonts stored in `/web/fonts/` and have those fonts applied consistently across preview images, PDF generation, and HTML output.

## What Was Built

### 1. Font Management
- **Font Discovery**: Added `getAvailableFonts()` method to scan `/web/fonts/` directory for TTF files
- **Font Selection UI**: Added dropdown selector in `/user/cards/name-tags.php` populated with available fonts
- **Font Path Resolution**: Implemented `getFontPath()` method to locate TTF files across multiple possible paths

### 2. Preview Image Generation
- **GD Library Integration**: Extended `generatePreviewImage()` to use custom fonts via `imagettftext()`
- **Font Fallback**: Implemented graceful fallback to built-in GD fonts if custom font file not found
- **Consistent Rendering**: Preview now uses same font rendering as PDF generation

### 3. PDF Generation
- **Initial Approach**: Attempted to use TCPDF's native font system with `TCPDF_FONTS::addTTFfont()`
- **Challenge**: TCPDF font processing proved complex and unreliable despite successful font definition file generation
- **Final Solution**: Switched to image-based approach - generate complete name tag as PNG using GD (same as preview), then embed into PDF
- **Result**: Perfect font consistency between preview and PDF

### 4. Additional Features
- **QR Code Size Control**: Added `qr_size_percentage` field (25-150%) to allow users to resize QR codes
- **Line Spacing Control**: Added `line_spacing` field (-5.0 to 5.0) for fine-tuning text spacing
- **Database Schema**: Added `font_family`, `qr_size_percentage`, and `line_spacing` columns to `name_tag_preferences` table

## Technical Decisions

### Why Image-Based PDF Generation?

**The Problem:**
- TCPDF's custom font system requires complex processing (TTF → font definition files)
- Despite successful font definition file generation, fonts weren't visually appearing in PDF
- Logs showed fonts were "set" correctly, but PDF output didn't reflect the custom fonts

**The Solution:**
- Generate complete name tag as PNG image using GD library (same code as preview)
- Embed the PNG image directly into the PDF
- This approach is identical to the QR surround variant, which already worked perfectly

**Benefits:**
- ✅ Guaranteed consistency between preview and PDF
- ✅ No complex TCPDF font processing needed
- ✅ GD library directly supports TTF fonts via `imagettftext()`
- ✅ Same proven approach already working in QR surround variant

### Font Path Resolution

**Challenges:**
- Server path restrictions (cannot access below `/public_html`)
- Multiple potential font locations
- Path normalization (double slashes, trailing slashes)

**Solution:**
- Prioritize `/public_html/fonts/` as absolute path
- Fallback to `$_SERVER['DOCUMENT_ROOT'] . '/fonts/'`
- Normalize paths using `rtrim()` and `str_replace('//', '/', $path)`

## Challenges Overcome

### 1. TCPDF Font Integration Issues
**Problem:** Custom fonts processed successfully but didn't appear in PDF output  
**Solution:** Switched to image-based approach instead of native TCPDF text rendering

### 2. Font Path Resolution
**Problem:** Multiple path format issues (double slashes, invalid paths)  
**Solution:** Implemented robust path normalization and multiple fallback paths

### 3. Preview/PDF Consistency
**Problem:** Preview and PDF used different rendering methods, causing inconsistencies  
**Solution:** Unified approach - both now use GD image generation, PDF embeds the image

### 4. Output Buffering Issues
**Problem:** PDF generation showing code instead of PDF ("headers already sent")  
**Solution:** Implemented comprehensive output buffering with `ob_start()`, `ob_clean()`, and `while (ob_get_level()) { ob_end_clean(); }`

## Success Metrics

- ✅ Custom fonts work correctly in preview images
- ✅ Custom fonts work correctly in PDF generation
- ✅ Custom fonts work correctly in HTML output
- ✅ Perfect consistency between preview and PDF
- ✅ Font selection UI integrated into existing name tag form
- ✅ QR code size control added and working
- ✅ Line spacing control added and working

## Key Learnings

1. **Image-Based PDF Generation is Reliable**: When dealing with complex styling (custom fonts, precise layouts), generating the content as an image and embedding it is more reliable than trying to replicate the styling in the PDF library's native text system.

2. **Leverage Existing Working Code**: The QR surround variant already solved the custom font problem using GD images. Replicating that approach saved significant time and ensured reliability.

3. **Debug Logging is Critical**: Extensive logging helped identify that TCPDF thought fonts were set correctly, but they weren't actually rendering. This led to the decision to switch approaches.

4. **User Experience Matters**: Adding font selection, QR size control, and line spacing gives users the customization they need while maintaining technical simplicity.

## Areas for Improvement

1. **Font Preview**: Could add a preview of font styles in the dropdown (e.g., "Apple Garamond - serif, elegant")
2. **Font Upload**: Currently fonts must be manually placed in `/web/fonts/`. Could add UI for uploading new fonts.
3. **Font Validation**: Could validate TTF files before allowing selection to prevent errors.
4. **Performance**: Image generation for PDFs is slightly slower than native text rendering, but the reliability trade-off is worth it.

## Files Modified

### Core Implementation
- `web/api/includes/NameTagGenerator.php`
  - Added `getAvailableFonts()` method
  - Added `getFontPath()` method
  - Modified `generatePreviewImage()` to use custom fonts
  - Added `generateCompleteNameTagImage()` method
  - Added `addCompleteNameTagImage()` method
  - Modified `generateNameTagSheetHTML()` to use image-based approach
  - Updated `addContactInfoToGD()` to use custom fonts
  - Updated `getWebFontFamily()` for HTML generation

### User Interface
- `web/user/cards/name-tags.php`
  - Added font family dropdown
  - Added QR size percentage input
  - Added line spacing input
  - Updated preview generation to include new fields
  - Updated save/load logic for new preferences

### Database
- `web/config/migrations/041_add_qr_size_to_name_tag_preferences.sql`
- `web/config/migrations/042_add_font_family_to_name_tag_preferences.sql`
- `web/config/migrations/043_add_line_spacing_to_name_tag_preferences.sql`

### Preview Endpoint
- `web/user/cards/preview-name-tag.php`
  - Updated to handle font family parameter
  - Updated to handle QR size percentage parameter
  - Updated to handle line spacing parameter

### PDF Generation
- `web/user/cards/download-name-tags-html.php`
  - Updated output buffering for robustness
  - Updated to pass font preferences to generator

## Comparison with QR Surround Variant

The standard name tags now use the **exact same approach** as the QR surround variant:

| Aspect | QR Surround | Standard Name Tags |
|--------|------------|-------------------|
| Preview | GD image generation | GD image generation |
| PDF | Embed PNG image | Embed PNG image |
| Font Support | TTF via `imagettftext()` | TTF via `imagettftext()` |
| HTML | Web fonts (Google Fonts) | Web fonts (Google Fonts) |

This consistency ensures:
- Same reliability across both variants
- Same code patterns for maintenance
- Same user experience expectations

## Conclusion

The custom font implementation for standard name tags is now complete and working. The decision to use image-based PDF generation (matching the QR surround variant) proved to be the right approach, ensuring perfect consistency between preview and PDF while avoiding the complexity of TCPDF's custom font system.

The feature is production-ready and provides users with the customization they need while maintaining technical simplicity and reliability.

