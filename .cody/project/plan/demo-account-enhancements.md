# Enhance Demo Cards with Complete Data

## Overview

Enhance the three demo business cards to include all available features: websites, full addresses, profile photos, company logos, and cover graphics. This will provide a comprehensive demonstration of ShareMyCard's capabilities.

## Implementation Steps

### Step 1: Create Demo Media Files

Create three sets of placeholder images in `/web/storage/media/`:

**Card 1 - Alex Chen (TechCorp Solutions) - Professional Blue Theme:**
- Profile photo: `demo-alex-profile.jpg` (professional headshot style)
- Company logo: `demo-techcorp-logo.jpg` (tech company logo)
- Cover graphic: `demo-techcorp-cover.jpg` (tech/code themed banner)

**Card 2 - Sarah Martinez (Design Studio Pro) - Creative Purple Theme:**
- Profile photo: `demo-sarah-profile.jpg` (creative professional style)
- Company logo: `demo-designstudio-logo.jpg` (design company logo)
- Cover graphic: `demo-designstudio-cover.jpg` (colorful creative banner)

**Card 3 - Michael Thompson (Innovation Ventures) - Executive Gold Theme:**
- Profile photo: `demo-michael-profile.jpg` (executive headshot style)
- Company logo: `demo-innovation-logo.jpg` (corporate logo)
- Cover graphic: `demo-innovation-cover.jpg` (professional business banner)

Use PHP GD library to generate simple, professional placeholder images with:
- Profile photos: 400x400px, colored circle with initials
- Company logos: 400x400px, simple geometric logo design
- Cover graphics: 1200x400px (3:1 ratio), gradient background with company name

### Step 2: Update DemoUserHelper.php

**File:** `web/api/includes/DemoUserHelper.php`

**Changes needed:**

1. **Add website links for all three cards:**
   - Card 1 (Alex Chen): Add company website and LinkedIn profile
   - Card 2 (Sarah Martinez): Add portfolio website and LinkedIn profile
   - Card 3 (Michael Thompson): Keep existing 3 websites

2. **Add full addresses for all three cards:**
   - Card 1: San Francisco, CA tech company address
   - Card 2: New York, NY design studio address
   - Card 3: Boston, MA corporate headquarters address

3. **Update business card INSERT to include media paths:**
   - Change NULL values to actual demo image paths
   - Format: `profile_photo_path = 'demo-alex-profile.jpg'`, etc.

4. **Add address INSERT statements:**
   ```sql
   INSERT INTO addresses (id, business_card_id, street_address, city, state, postal_code, country, created_at, updated_at)
   VALUES (uuid, card_id, street, city, state, zip, country, NOW(), NOW())
   ```

### Step 3: Create Image Generation Script

**New file:** `Scripts/generate-demo-images.php`

This script will generate all 9 demo images programmatically using PHP GD:

- Generate profile photos with colored backgrounds and initials
- Generate company logos with simple geometric shapes
- Generate cover graphics with gradients matching card themes
- Save to `/web/storage/media/` directory
- Use appropriate colors for each theme (blue, purple, gold)

### Step 4: Integration

**Sequence:**
1. Run `Scripts/generate-demo-images.php` to create image files
2. Update `DemoUserHelper.php` with complete card data
3. Test by logging in with demo account
4. Verify all three cards display complete information

## Expected Results

After implementation, each demo card will showcase:

**Card 1 - Alex Chen (Software Engineer):**
- ✅ Profile photo (blue theme)
- ✅ Company logo (TechCorp)
- ✅ Cover graphic (tech themed)
- ✅ Website (company site, LinkedIn)
- ✅ Address (San Francisco, CA)
- ✅ Phone number
- ✅ Bio

**Card 2 - Sarah Martinez (Creative Director):**
- ✅ Profile photo (purple theme)
- ✅ Company logo (Design Studio)
- ✅ Cover graphic (creative themed)
- ✅ Website (portfolio, LinkedIn)
- ✅ Address (New York, NY)
- ✅ 2 additional emails
- ✅ 2 additional phones
- ✅ Bio

**Card 3 - Michael Thompson (CEO):**
- ✅ Profile photo (gold theme)
- ✅ Company logo (Innovation Ventures)
- ✅ Cover graphic (executive themed)
- ✅ 3 Websites (company, LinkedIn, blog)
- ✅ Address (Boston, MA)
- ✅ Phone number
- ✅ Bio

## Testing

1. Clear browser cache and cookies
2. Login to demo account (`demo@sharemycard.app`)
3. Verify all three cards display on dashboard
4. Click each card to verify:
   - Profile photo displays
   - Company logo displays
   - Cover graphic displays
   - Website links are present and formatted correctly
   - Address displays with full details
   - Public card view shows all media
   - QR code generation includes all contact info
5. Test card features:
   - Email signature generation with images
   - Virtual background generation
   - Name tag generation with images
6. Verify demo data resets on next login
