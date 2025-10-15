# TestFlight Setup Guide for ShareMyCard

## ✅ **Step 1: Privacy Policy URL**

**Privacy Policy is already live!** ✅

**URL:** `https://sharemycard.app/privacy.php`

This page is already created and will be deployed with your next web update. You can use this URL immediately in App Store Connect.

To deploy to production:
```bash
# Upload web/privacy.php to your server
scp web/privacy.php your_server:/public_html/
```

**You'll need this URL for App Store Connect!**

---

## ✅ **Step 2: Configure Xcode**

### Update Version Information
1. Open project:
   ```bash
   cd /Users/markwarrick/Projects/QRCard
   open QRCard.xcodeproj
   ```

2. Select **ShareMyCard** target in left sidebar

3. Under **General → Identity**:
   - Version: `1.5.0`
   - Build: `1` (increment for each upload: 2, 3, 4...)

4. Verify **Signing & Capabilities**:
   - Team: Y92B9F9V5R ✅
   - Bundle Identifier: net.warrick.ShareMyCard ✅
   - Automatically manage signing: Checked ✅

---

## ✅ **Step 3: Create Archive**

1. In Xcode menu bar:
   - Select: **Any iOS Device (arm64)** from device dropdown

2. Click: **Product → Archive**
   - Wait 2-5 minutes for build
   - Organizer window opens automatically

3. In Organizer:
   - Select your archive
   - Click **Distribute App**
   - Choose: **App Store Connect**
   - Click: **Upload**
   - Accept defaults and click **Upload**
   - Wait for upload to complete

---

## ✅ **Step 4: App Store Connect Setup**

### Create New App
1. Go to: https://appstoreconnect.apple.com/
2. Click **My Apps** → **+** icon → **New App**
3. Fill in:
   - **Platform**: iOS
   - **Name**: ShareMyCard
   - **Primary Language**: English (U.S.)
   - **Bundle ID**: net.warrick.ShareMyCard
   - **SKU**: sharemycard-001
   - **User Access**: Full Access
4. Click **Create**

### Configure App Information
1. Go to **App Information** tab:
   - **Category**: 
     - Primary: Productivity
     - Secondary: Business
   - **Privacy Policy URL**: https://sharemycard.app/privacy.php
   - **Subtitle**: Digital Business Cards Made Simple
   - **Description**: 
     ```
     ShareMyCard is the simplest way to create and share digital business cards. 
     Create beautiful business cards with your contact info, generate QR codes, 
     and share instantly. Sync across devices with our web app.
     
     Features:
     • Create multiple business cards
     • Add profile photos, company logos, and cover graphics
     • Generate QR codes for instant sharing
     • Sync across iOS app and web
     • Email-based passwordless authentication
     • Export vCards for easy contact import
     • Beautiful, modern UI
     
     Perfect for professionals, entrepreneurs, and anyone who wants to share 
     their contact information in a modern, eco-friendly way.
     ```

2. **Pricing and Availability**:
   - Price: Free
   - Availability: All countries

---

## ✅ **Step 5: TestFlight Configuration**

### Wait for Processing
- After upload, wait 10-30 minutes
- You'll get email: "Your build has been processed"
- Check App Store Connect → **TestFlight** tab

### Add Test Information
1. Click on your build under "iOS Builds"
2. Fill required fields:
   - **What to Test**:
     ```
     TestFlight Preview - ShareMyCard v1.5.0
     
     Please test:
     • Creating and editing business cards
     • Image uploads (profile photo, company logo)
     • QR code generation and scanning
     • Syncing between app and web (https://sharemycard.app)
     • Email authentication (passwordless login)
     
     Known issues:
     • None at this time
     
     Feedback welcome at mark@warrick.net
     ```
   
   - **Description**:
     ```
     ShareMyCard lets you create beautiful digital business cards and share 
     them via QR codes. Create cards with your contact info, upload images, 
     and sync across devices.
     ```
   
   - **Feedback Email**: mark@warrick.net
   - **Privacy Policy URL**: https://sharemycard.app/privacy.php

3. Click **Submit for Review**

### Beta App Review Timeline
- First time: 1-2 business days
- Subsequent updates: Usually 24 hours
- You'll get email when approved

---

## ✅ **Step 6: Add Testers**

### Internal Testing (No Review Required)
1. **TestFlight** → **Internal Testing**
2. Click **+** next to testers
3. Add yourself: mark@warrick.net
4. Testers get email with TestFlight invitation
5. Download TestFlight app from App Store
6. Click link in email to install

### External Testing (After Beta Review)
1. **TestFlight** → **External Testing**
2. Create a group: "Beta Testers"
3. Add testers by email
4. Or create public link to share

---

## 🎯 **Quick Checklist**

Before submitting to TestFlight:

- [ ] Privacy policy uploaded and accessible
- [ ] Xcode version set to 1.5.0
- [ ] Archive created successfully
- [ ] Build uploaded to App Store Connect
- [ ] App created in App Store Connect
- [ ] App Information filled out
- [ ] TestFlight test information completed
- [ ] Submitted for Beta App Review

---

## 📱 **Testing on Your Device**

Once approved for TestFlight:

1. Download **TestFlight** app from App Store
2. Open invitation email
3. Click **View in TestFlight**
4. Install ShareMyCard
5. Test all features:
   - Authentication (email codes)
   - Create business card
   - Upload images
   - Generate QR code
   - Sync with web (https://sharemycard.app)

---

## 🔄 **Updating Builds**

For each new version:

1. **Increment Build Number**:
   - Xcode → General → Build: `2`, `3`, `4`...
   - Keep version same (1.5.0) for minor updates
   - Change version (1.6.0) for feature updates

2. **Archive & Upload** (repeat Step 3)

3. **Add to TestFlight**:
   - New builds appear automatically
   - Update "What to Test" notes
   - Submit for review if needed

---

## ⚠️ **Common Issues**

### "No valid signing certificate found"
- **Fix**: Xcode → Preferences → Accounts → Download Manual Profiles

### "Archive not showing in Organizer"
- **Fix**: Select "Any iOS Device" not a simulator

### "Build is processing" for hours
- **Fix**: Normal for first build. Check email for errors.

### "Privacy Policy URL is not accessible"
- **Fix**: Verify URL works in browser, must be HTTPS

---

## 📧 **What Testers Will See**

Invitation email contains:
- App name and icon
- Your invitation message
- Link to install TestFlight
- Link to install your app

Testers can:
- Install multiple builds
- Send feedback via TestFlight
- See what's new in each version
- Uninstall anytime

---

## 🚀 **Next Steps: Full App Store Release**

After TestFlight testing is successful:

1. **Create App Store Release**:
   - App Store Connect → App Store tab
   - Add screenshots (required)
   - Add app preview video (optional)
   - Submit for review

2. **App Review Timeline**:
   - Usually 24-48 hours
   - More thorough than TestFlight review

3. **Release**:
   - Auto-release or manual
   - Available worldwide

---

## 📝 **Notes**

- **Privacy Policy** is required for both TestFlight and App Store
- **TestFlight** builds expire after 90 days
- **Internal testers** can test immediately
- **External testers** need Beta App Review first
- You can have up to 10,000 external testers
- Builds can be tested for 90 days

---

## ✅ **You're Ready!**

Your app is configured and ready to submit to TestFlight. Follow the steps above and you'll be testing on your device within 2-3 days!

**Good luck! 🚀**

---

**Questions?** Contact Mark Warrick at mark@warrick.net

