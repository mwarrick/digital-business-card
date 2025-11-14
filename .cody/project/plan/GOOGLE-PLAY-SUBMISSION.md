# Google Play Store Submission Guide for ShareMyCard Android App

## Overview

This guide walks you through submitting the ShareMyCard Android app to Google Play Store, including testing options similar to Apple TestFlight.

**Yes, Google Play has testing similar to TestFlight!** Google Play offers:
- **Internal Testing** - Immediate testing with up to 100 testers (no review)
- **Closed Testing** - Beta testing with up to 1,000 testers (requires review)
- **Open Testing** - Public beta testing (requires review)

---

## ✅ Prerequisites

Before starting, ensure you have:

- [ ] Google Play Developer Account ($25 one-time fee)
- [ ] Privacy Policy URL (already live: `https://sharemycard.app/privacy.php`)
- [ ] App signing key (we'll create this)
- [ ] App screenshots (required for store listing)
- [ ] App icon (already created: `ic_launcher-playstore.png`)
- [ ] Target SDK 34 (already configured ✅)
- [ ] Min SDK 25 (already configured ✅)

---

## 🔐 Step 1: Create Google Play Developer Account

1. Go to: https://play.google.com/console/signup
2. Pay the **$25 one-time registration fee**
3. Complete your developer profile:
   - Developer name: Your name or company
   - Email: mark@warrick.net
   - Phone number
   - Address
4. Accept the Developer Distribution Agreement

**Note:** Account approval can take 1-2 business days.

---

## 🔑 Step 2: Generate App Signing Key

**IMPORTANT:** You must sign your app with a keystore that you'll use for ALL future updates. Google Play requires consistent signing.

### Create Keystore

1. Open Terminal in your project root:
   ```bash
   cd /Users/markwarrick/Projects/QRCard/Android\ App
   ```

2. Create a keystore (replace `your-keystore-password` with a strong password):
   ```bash
   keytool -genkey -v -keystore sharemycard-release.jks \
     -keyalg RSA -keysize 2048 -validity 10000 \
     -alias sharemycard \
     -storepass your-keystore-password \
     -keypass your-keystore-password \
     -dname "CN=ShareMyCard, OU=Development, O=Warrick, L=City, ST=State, C=US"
   ```

3. **SECURE THE KEYSTORE:**
   - Save `sharemycard-release.jks` in a secure location (NOT in git)
   - Save the password in a password manager
   - **You cannot update the app without this keystore!**

4. Add to `.gitignore` (already done ✅):
   ```
   *.jks
   *.keystore
   ```

### Configure Signing in Gradle

1. Create `keystore.properties` file (DO NOT commit to git):
   ```properties
   storePassword=your-keystore-password
   keyPassword=your-keystore-password
   keyAlias=sharemycard
   storeFile=../sharemycard-release.jks
   ```

2. Add to `.gitignore`:
   ```
   keystore.properties
   ```

3. Update `app/build.gradle.kts` to add signing config:

   ```kotlin
   android {
       // ... existing code ...
       
       signingConfigs {
           create("release") {
               val keystorePropertiesFile = rootProject.file("keystore.properties")
               val keystoreProperties = java.util.Properties()
               if (keystorePropertiesFile.exists()) {
                   keystoreProperties.load(java.io.FileInputStream(keystorePropertiesFile))
                   storeFile = file(keystoreProperties["storeFile"] as String)
                   storePassword = keystoreProperties["storePassword"] as String
                   keyAlias = keystoreProperties["keyAlias"] as String
                   keyPassword = keystoreProperties["keyPassword"] as String
               }
           }
       }
       
       buildTypes {
           release {
               signingConfig = signingConfigs.getByName("release")
               isMinifyEnabled = false
               proguardFiles(
                   getDefaultProguardFile("proguard-android-optimize.txt"),
                   "proguard-rules.pro"
               )
           }
       }
   }
   ```

---

## 📱 Step 3: Prepare App for Release

### Update Version Information

1. Open `Android App/app/build.gradle.kts`

2. Update version information:
   ```kotlin
   defaultConfig {
       applicationId = "com.sharemycard.android"
       minSdk = 25
       targetSdk = 34
       versionCode = 1        // Increment for each release (1, 2, 3...)
       versionName = "1.0.0"  // User-visible version (1.0.0, 1.1.0, 1.2.0...)
   }
   ```

   **Version Code Rules:**
   - Must be an integer
   - Must increase with each release
   - Google Play uses this to determine which version is newer
   - Example: 1, 2, 3, 4...

   **Version Name Rules:**
   - User-visible version string
   - Can be any format (1.0.0, 1.1.0, etc.)
   - Should match iOS version when possible

### Build Release APK/AAB

**Option A: Build AAB (Recommended for Google Play)**

1. In Android Studio:
   - **Build** → **Generate Signed Bundle / APK**
   - Select **Android App Bundle (.aab)**
   - Click **Next**
   - Select your keystore (`sharemycard-release.jks`)
   - Enter passwords
   - Select **release** build variant
   - Click **Finish**

2. Output location: `Android App/app/release/app-release.aab`

**Option B: Build APK (For testing only)**

1. In Android Studio:
   - **Build** → **Generate Signed Bundle / APK**
   - Select **APK**
   - Follow same steps as above

2. Output location: `Android App/app/release/app-release.apk`

**Note:** Google Play requires AAB format for production releases, but you can test with APK first.

---

## 🎨 Step 4: Prepare Store Listing Assets

### Required Assets

1. **App Icon** (512x512px PNG)
   - ✅ Already created: `Android App/app/src/main/ic_launcher-playstore.png`
   - If needed, resize to exactly 512x512px

2. **Feature Graphic** (1024x500px PNG)
   - Promotional banner shown at top of store listing
   - Create or use a design tool

3. **Screenshots** (Required)
   - **Phone:** At least 2 screenshots (up to 8)
   - **Tablet:** Optional (up to 8)
   - **Minimum dimensions:** 320px (shortest side)
   - **Maximum dimensions:** 3840px (longest side)
   - **Aspect ratio:** 16:9 or 9:16 recommended

   **Screenshot Ideas:**
   - Home screen with cards
   - Contact creation form
   - QR code display
   - Contact list
   - Lead management
   - Settings screen

4. **App Description** (Required, 80-4000 characters)
   ```
   ShareMyCard - Digital Business Cards Made Simple
   
   Create, manage, and share beautiful digital business cards instantly. 
   Perfect for professionals, entrepreneurs, and anyone who wants to 
   share contact information in a modern, eco-friendly way.
   
   ✨ Features:
   • Create multiple business cards with all your contact information
   • Add profile photos, company logos, and cover graphics
   • Generate QR codes for instant sharing
   • Sync seamlessly between Android app and web platform
   • Manage contacts and leads in one place
   • Convert leads to contacts with one tap
   • Secure authentication with password or email verification
   • Beautiful, modern UI built with Material Design 3
   
   📱 Perfect For:
   • Networking events and conferences
   • Business meetings and client interactions
   • Social gatherings and personal connections
   • Anyone who wants to go paperless
   
   🔄 Sync & Access:
   Your business cards sync automatically with our web platform 
   (sharemycard.app), so you can access them from any device.
   
   🌱 Eco-Friendly:
   Reduce paper waste by going digital. One QR code replaces 
   hundreds of business cards.
   
   Download ShareMyCard today and start sharing your contact 
   information the modern way!
   ```

5. **Short Description** (Required, 80 characters max)
   ```
   Create and share digital business cards with QR codes. Sync across devices.
   ```

6. **Privacy Policy URL** (Required)
   - ✅ Already live: `https://sharemycard.app/privacy.php`

---

## 🚀 Step 5: Create App in Google Play Console

1. Go to: https://play.google.com/console/

2. Click **Create app**

3. Fill in app details:
   - **App name:** ShareMyCard
   - **Default language:** English (United States)
   - **App or game:** App
   - **Free or paid:** Free
   - **Declare as government app:** No

4. Click **Create app**

5. Accept the **Google Play Developer Program Policies**

---

## 📋 Step 6: Complete Store Listing

1. In Google Play Console, go to **Store presence** → **Main store listing**

2. Fill in required fields:

   **App details:**
   - **App name:** ShareMyCard
   - **Short description:** (80 chars max - see above)
   - **Full description:** (see above)
   - **App icon:** Upload `ic_launcher-playstore.png` (512x512px)
   - **Feature graphic:** Upload 1024x500px banner (optional but recommended)

   **Graphics:**
   - **Phone screenshots:** Upload at least 2 screenshots
   - **Tablet screenshots:** Optional
   - **TV screenshots:** Not required (unless targeting TV)

   **Categorization:**
   - **App category:** Business
   - **Tags:** Business, Productivity, Networking

   **Contact details:**
   - **Email:** mark@warrick.net
   - **Phone:** (optional)
   - **Website:** https://sharemycard.app

   **Privacy Policy:**
   - **Privacy Policy URL:** https://sharemycard.app/privacy.php

3. Click **Save**

---

## 🧪 Step 7: Set Up Internal Testing (Like TestFlight)

**Internal Testing** is Google Play's equivalent to TestFlight's Internal Testing - no review required!

### Create Internal Testing Track

1. Go to **Testing** → **Internal testing**

2. Click **Create new release**

3. Upload your AAB file:
   - Click **Upload**
   - Select `app-release.aab`
   - Wait for processing (5-10 minutes)

4. Add release notes:
   ```
   Internal Testing Build - ShareMyCard v1.0.0
   
   Initial release for internal testing.
   
   Features:
   • Create and manage business cards
   • Contact and lead management
   • QR code generation
   • Sync with web platform
   • Authentication (password or email code)
   
   Please test:
   • Creating business cards
   • Adding contacts
   • QR code scanning
   • Syncing with web (https://sharemycard.app)
   • Authentication flow
   
   Feedback: mark@warrick.net
   ```

5. Click **Save**

6. Click **Review release**

7. Click **Start rollout to Internal testing**

### Add Testers

1. Go to **Testing** → **Internal testing** → **Testers** tab

2. **Option A: Email list (up to 100 testers)**
   - Click **Create email list**
   - Name: "Internal Testers"
   - Add emails: mark@warrick.net, etc.
   - Click **Save**

3. **Option B: Share link (up to 100 testers)**
   - Copy the opt-in URL
   - Share with testers via email, messaging, etc.

4. Testers will receive an email with a link to join testing

### Testing on Device

1. Tester clicks the opt-in link in email
2. Opens link on Android device
3. Accepts invitation to become a tester
4. Opens Google Play Store
5. Searches for "ShareMyCard" or uses direct link
6. Sees "You're a tester" badge
7. Clicks **Install**

**Note:** Internal testing builds are available immediately (no review).

---

## 🔒 Step 8: Set Up Closed Testing (Optional)

**Closed Testing** is like TestFlight's External Testing - requires review but allows more testers.

1. Go to **Testing** → **Closed testing**

2. Click **Create new release**

3. Upload AAB and add release notes (same as Internal)

4. Click **Review release**

5. Click **Start rollout to Closed testing**

6. **Add testers:**
   - Create email lists (up to 1,000 testers)
   - Or share opt-in link

7. **Submit for review:**
   - Google will review the app (1-3 days)
   - You'll receive email when approved/rejected

---

## 📊 Step 9: Complete App Content Rating

1. Go to **Policy** → **App content**

2. Complete the questionnaire:
   - **Category:** Business/Productivity
   - **Does your app collect user data?** Yes (contacts, email)
   - **Does your app share data?** No
   - **Does your app use location?** No (unless you add analytics)
   - **Does your app have ads?** No
   - **Does your app have in-app purchases?** No

3. Submit questionnaire

4. Receive rating (usually "Everyone" for business apps)

---

## 🔐 Step 10: Complete Data Safety Section

1. Go to **Policy** → **Data safety**

2. Answer questions about data collection:
   - **Personal info:** Email addresses, names (for account creation)
   - **Contacts:** User's contacts (for contact management)
   - **Device info:** App activity, device identifiers (for analytics)
   - **Location:** No (unless you add location features)

3. Describe data usage:
   - **Purpose:** App functionality, account management, analytics
   - **Data sharing:** Not shared with third parties
   - **Data security:** Encrypted in transit and at rest

4. Click **Save**

---

## ✅ Step 11: Prepare for Production Release

### Complete Required Sections

1. **App access:**
   - Go to **Policy** → **App access**
   - Declare if app requires special access (ours doesn't)

2. **Ads:**
   - Go to **Monetization** → **Ads**
   - Declare if app shows ads (ours doesn't)

3. **Content rating:** ✅ (Completed in Step 9)

4. **Data safety:** ✅ (Completed in Step 10)

5. **Target audience:**
   - Go to **Policy** → **Target audience**
   - Select: **Everyone** or **13+** (depending on your content)

6. **News apps:**
   - Not applicable (skip)

### Create Production Release

1. Go to **Production** → **Create new release**

2. Upload AAB file:
   - Click **Upload**
   - Select `app-release.aab`
   - Wait for processing

3. Add release notes:
   ```
   ShareMyCard v1.0.0
   
   Initial release of ShareMyCard for Android.
   
   Features:
   • Create and manage digital business cards
   • Contact and lead management
   • QR code generation and scanning
   • Sync with web platform
   • Secure authentication
   • Beautiful Material Design 3 UI
   ```

4. Click **Save**

5. Click **Review release**

6. Review all sections:
   - ✅ Store listing complete
   - ✅ Content rating complete
   - ✅ Data safety complete
   - ✅ App access declared
   - ✅ Target audience set

7. Click **Start rollout to Production**

8. **Select countries:**
   - **All countries** (recommended)
   - Or select specific countries

9. Click **Confirm rollout**

---

## ⏱️ Step 12: Review Timeline

### Internal Testing
- **Processing:** 5-10 minutes
- **Review:** Not required
- **Available:** Immediately after processing

### Closed Testing
- **Processing:** 5-10 minutes
- **Review:** 1-3 business days
- **Available:** After review approval

### Production Release
- **Processing:** 5-10 minutes
- **Review:** 1-7 business days (usually 1-3)
- **Available:** After review approval

**First-time submissions typically take longer (3-7 days).**

---

## 🔄 Step 13: Updating Your App

For each new version:

1. **Increment version:**
   ```kotlin
   versionCode = 2  // Increment by 1
   versionName = "1.0.1"  // Update version name
   ```

2. **Build new AAB:**
   - Build → Generate Signed Bundle / APK
   - Select AAB
   - Use same keystore

3. **Upload to Google Play Console:**
   - Go to your testing track or Production
   - Create new release
   - Upload new AAB
   - Add release notes
   - Review and rollout

4. **Review time:**
   - Internal: Immediate
   - Closed/Production: Usually 24-48 hours for updates

---

## 📸 Screenshot Requirements

### Minimum Requirements
- **Phone:** 2 screenshots (up to 8)
- **Tablet:** Optional (up to 8)
- **Format:** PNG or JPEG
- **Size:** 320px - 3840px (longest side)
- **Aspect ratio:** 16:9 or 9:16 recommended

### How to Capture Screenshots

**Option A: Android Studio**
1. Run app on emulator
2. Click camera icon in emulator toolbar
3. Save screenshots

**Option B: Physical Device**
1. Run app on device
2. Use device screenshot feature
3. Transfer to computer

**Option C: Screenshot Tools**
- Use tools like `adb shell screencap` for automated screenshots

---

## ⚠️ Common Issues & Solutions

### "Upload failed: Invalid AAB"
- **Fix:** Ensure you're uploading `.aab` file, not `.apk`
- **Fix:** Check that signing config is correct

### "Version code already used"
- **Fix:** Increment `versionCode` in `build.gradle.kts`

### "Missing privacy policy"
- **Fix:** Ensure `https://sharemycard.app/privacy.php` is accessible
- **Fix:** Must be HTTPS, not HTTP

### "Content rating incomplete"
- **Fix:** Complete all questions in App content rating

### "Data safety form incomplete"
- **Fix:** Complete all required fields in Data safety section

### "Keystore not found"
- **Fix:** Ensure `keystore.properties` exists and paths are correct
- **Fix:** Ensure keystore file is in the correct location

### "App rejected: Missing features"
- **Fix:** Ensure all features mentioned in description are implemented
- **Fix:** Remove any placeholder or "coming soon" features

---

## 🎯 Testing Checklist

Before submitting to production:

- [ ] App builds successfully in release mode
- [ ] App is signed with release keystore
- [ ] Version code and name are correct
- [ ] All features work correctly
- [ ] No crashes or errors
- [ ] Privacy policy is accessible
- [ ] Screenshots are uploaded
- [ ] App description is complete
- [ ] Content rating is complete
- [ ] Data safety form is complete
- [ ] Tested on multiple devices (if possible)
- [ ] Tested on different Android versions
- [ ] All permissions are declared in manifest
- [ ] App works offline (if applicable)

---

## 📱 Comparison: Google Play vs TestFlight

| Feature | Google Play | TestFlight |
|---------|-------------|------------|
| **Internal Testing** | ✅ Up to 100 testers, no review | ✅ Up to 100 testers, no review |
| **Closed/External Testing** | ✅ Up to 1,000 testers, review required | ✅ Up to 10,000 testers, review required |
| **Open Testing** | ✅ Public beta, review required | ❌ Not available |
| **Review Time** | 1-7 days (first time) | 1-2 days (first time) |
| **Update Review** | 24-48 hours | 24 hours |
| **Build Expiration** | No expiration | 90 days |
| **Cost** | $25 one-time | Free |
| **Account Approval** | 1-2 business days | Immediate |

---

## 🔐 Security Best Practices

1. **Keystore Security:**
   - Store keystore in secure location (password manager, encrypted drive)
   - Never commit keystore to git
   - Backup keystore securely
   - Document keystore location and password

2. **App Signing:**
   - Use Google Play App Signing (recommended)
   - Google manages your signing key
   - Easier key recovery if lost

3. **API Keys:**
   - Never hardcode API keys
   - Use environment variables or secure config
   - Restrict API keys on server side

---

## 📚 Additional Resources

- **Google Play Console:** https://play.google.com/console/
- **Google Play Policies:** https://play.google.com/about/developer-content-policy/
- **Android App Bundle Guide:** https://developer.android.com/guide/app-bundle
- **App Signing:** https://developer.android.com/studio/publish/app-signing
- **Data Safety:** https://support.google.com/googleplay/android-developer/answer/10787469

---

## ✅ Quick Start Checklist

1. [ ] Create Google Play Developer account ($25)
2. [ ] Generate app signing keystore
3. [ ] Configure signing in `build.gradle.kts`
4. [ ] Update version code and name
5. [ ] Build release AAB
6. [ ] Create app in Google Play Console
7. [ ] Complete store listing (description, screenshots, icon)
8. [ ] Complete content rating
9. [ ] Complete data safety form
10. [ ] Upload to Internal testing
11. [ ] Add testers and test
12. [ ] Submit to Production when ready

---

## 🚀 You're Ready!

Follow these steps and your app will be available on Google Play Store within 1-7 business days (first submission).

**Good luck! 🎉**

---

## 📧 Support

**Questions?** Contact Mark Warrick at mark@warrick.net

**Privacy Policy:** https://sharemycard.app/privacy.php

**Website:** https://sharemycard.app

