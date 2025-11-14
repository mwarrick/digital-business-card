# ShareMyCard Android App

Android version of the ShareMyCard digital business card application, built with Kotlin and Jetpack Compose.

## Project Structure

This is a standard Android Studio project that follows the recommended project structure:

```
Android App/
├── app/                          # Main application module
│   ├── src/main/
│   │   ├── java/com/sharemycard/android/
│   │   │   ├── MainActivity.kt  # Main activity entry point
│   │   │   └── ui/theme/        # Compose theme files
│   │   ├── res/                  # Resources (strings, themes, etc.)
│   │   └── AndroidManifest.xml  # App manifest
│   └── build.gradle.kts          # App module build configuration
├── build.gradle.kts              # Project-level build configuration
├── settings.gradle.kts           # Project settings
└── gradle.properties            # Gradle properties

```

## Getting Started

### Prerequisites

- Android Studio Hedgehog (2023.1.1) or later
- JDK 17 or later
- Android SDK (API 25 - 34)

### Opening in Android Studio

1. Open Android Studio
2. Select "Open" or "Open an Existing Project"
3. Navigate to the `Android App` directory
4. Click "OK"
5. Android Studio will automatically sync the Gradle files

### First Run

1. Wait for Gradle sync to complete
2. Create an Android Virtual Device (AVD) or connect a physical device
3. Click the "Run" button (green play icon) or press `Shift+F10`

## Project Configuration

- **Package Name**: `com.sharemycard.android`
- **Min SDK**: 25 (Android 7.1.1 Nougat)
- **Target SDK**: 34 (Android 14)
- **Build Tools**: Gradle 8.2 with Kotlin DSL
- **UI Framework**: Jetpack Compose
- **Architecture**: MVVM with Clean Architecture

## Dependencies

Key libraries included:
- **Jetpack Compose**: Modern UI framework
- **Hilt**: Dependency injection
- **Room**: Local database
- **Retrofit**: HTTP client
- **Coroutines**: Async operations
- **Coil**: Image loading
- **ZXing**: QR code generation/scanning
- **CameraX**: Camera integration

## API Configuration

The app connects to the ShareMyCard API:
- **Base URL**: `https://sharemycard.app/api`
- **Authentication**: JWT Bearer tokens
- See the iOS app's `APIConfig.swift` for endpoint details

## Development Status

### ✅ Completed Features

1. **Authentication & Security** ✅
   - Email-based registration
   - Dual authentication (Password OR email verification code)
   - Demo account login (one-tap, bypasses verification)
   - Password management (set password if none, change password if exists)
   - JWT token storage in EncryptedSharedPreferences
   - User email storage and retrieval

2. **UI & Navigation** ✅
   - Tab-based navigation (Home, Cards, Contacts, Leads, Settings)
   - Home screen with counts, sync status, and links
   - Login, Registration, and Verification screens
   - Navigation graph with proper back stack management
   - Scrollable home screen with compact layout

3. **Business Cards** ✅
   - Card list view with search functionality
   - Card details view with formatted display
   - Card create/edit screen with all fields
   - Card deletion with confirmation
   - QR code generation (with clickable link to public page)
   - Media uploads (profile photo, company logo, cover graphic) - gallery only
   - Theme selection and application (12 themes)
   - Active/inactive status toggle
   - Profile photos displayed in cards list (instead of company logos)
   - Two-way sync working correctly

4. **Contacts Management** ✅
   - Contact list view (alphabetically sorted by first name)
   - Contact details view with formatted display (icons, clickable actions, text wrapping)
   - Contact export to device contacts (with runtime permissions)
   - Contact creation (manual and from QR scan)
   - Contact editing
   - Contact deletion (swipe-to-delete)
   - Source tracking (manual, converted, qr_scan)
   - QR code scanning to add contacts (camera-based)

5. **Leads Management** ✅
   - Lead list view with search
   - Lead details view with formatted display (icons, clickable actions, text wrapping)
   - Lead conversion to contacts
   - Lead deletion with warning for converted leads
   - Swipe-to-delete functionality
   - Link to associated contact from deletion warning

6. **Settings & Account** ✅
   - Password settings screen (set/change password)
   - Account security section
   - Logout functionality
   - Report Issues link

7. **Sync & Data Management** ✅
   - Full sync (push + pull) for cards, contacts, and leads
   - Timestamp-based conflict resolution (most recent wins)
   - Auto-sync after local changes
   - Soft delete implementation (is_deleted field)
   - Demo account data filtering
   - Duplicate detection (with proper handling of leadId: 0)
   - Comprehensive sync logging for debugging

8. **QR Code Features** ✅
   - QR code generation for business cards
   - QR code display with clickable link to public page
   - QR code scanning for contacts (camera-based with ML Kit)
   - Contact form pre-filling from scanned QR codes
   - Public card API access for QR scanning

### 🚧 Remaining Tasks

#### High Priority
1. **Testing & Quality Assurance**
   - [ ] Media upload testing (verify all upload scenarios work)
   - [ ] Theme selection testing (verify all 12 themes work and persist)
   - [ ] QR code generation testing (verify for all card types)
   - [ ] Complete sync flow testing (verify all sync scenarios)
   - [ ] Form validation testing
   - [ ] Error handling testing
   - [ ] Performance testing with large datasets

2. **Missing Features**
   - [ ] Duplicate card functionality
   - [ ] Share card functionality (via Android share sheet)
   - [ ] Background sync (WorkManager integration)
   - [ ] Offline support (queue for network failures)

#### Medium Priority
1. **Enhancements**
   - [ ] Image cropping functionality for media uploads
   - [ ] Media deletion functionality
   - [ ] Enhanced error messages for users
   - [ ] Pull-to-refresh on all list screens
   - [ ] Better loading states and empty states

2. **Platform-Specific**
   - [ ] Testing on Android 7.1.1 (API 25) - verify all features work
   - [ ] Testing on different screen sizes
   - [ ] Landscape orientation support
   - [ ] Tablet support (if needed)

#### Low Priority / Future Enhancements
- [ ] Biometric authentication (fingerprint/face unlock)
- [ ] Home screen widgets
- [ ] Share target (receive shared content)
- [ ] App shortcuts
- [ ] Export features (PDF, PNG, CSV)
- [ ] Advanced search and filtering
- [ ] Bulk operations

### Development Plan

Implementation follows the modular development plan:
1. ✅ Authentication & Security
2. ✅ UI & Navigation
3. ✅ Business Cards (CRUD, Media, Themes, QR)
4. ✅ Sync & Data Management
5. ✅ QR Code Features (Generation & Scanning)
6. ✅ Contacts Management (Complete)
7. ✅ Leads Management (Complete)
8. ✅ Settings & Account
9. 🚧 Testing & Quality Assurance
10. 🚧 Missing Features (Duplicate, Share, Background Sync)

## Notes

- The Gradle wrapper scripts (`gradlew` and `gradlew.bat`) will be generated automatically when Android Studio syncs the project
- If you encounter sync issues, try: `File > Invalidate Caches / Restart`

## Recent Updates

### November 13, 2025
- ✅ **QR Code Scanning Fix**: Fixed QR scan to add contact functionality for both Android and iOS - cards API now allows public access for QR scanning
- ✅ **Contact Sync Fix**: Fixed issue where contacts with `leadId: 0` were incorrectly treated as duplicates - all contacts now sync correctly
- ✅ **Cards List UI**: Changed cards list to display profile photos instead of company logos for better visual consistency
- ✅ **Enhanced Logging**: Added comprehensive logging throughout sync process for easier debugging

### Previous Updates
- ✅ **Account Security**: Password settings screen with ability to set password (if none exists) or change password (if password exists)
- ✅ **Demo Account Login**: One-tap demo login button that bypasses verification and navigates directly to home screen
- ✅ **Contact Export**: Export contacts to device's native contacts app with runtime permission handling
- ✅ **UI Improvements**: Formatted detail views for contacts and leads with icons, clickable actions, and text wrapping
- ✅ **Business Cards CRUD**: Complete create, edit, delete functionality with media uploads and theme selection
- ✅ **QR Code Features**: QR code generation and scanning for contacts
- ✅ **Sync Implementation**: Full two-way sync with conflict resolution for cards, contacts, and leads

## Known Limitations

### Android 7.1.1 (API 25) Compatibility

- **QR Code Scanning for Contacts**: QR code scanning functionality to create contacts from scanned QR codes is now **fully implemented and working** on all supported Android versions including API 25. The app uses ML Kit and CameraX which support API 25+.
- **QR Code Generation**: QR code generation for business cards works on all supported Android versions including API 25.
- **Dynamic Colors**: Dynamic theming (Material You) is only available on Android 12 (API 31) and above. The app gracefully falls back to static colors on older versions.

### Current Limitations

- **Media Uploads**: Currently only supports gallery selection (camera capture removed per user request)
- **Image Cropping**: Image cropping functionality not yet implemented
- **Background Sync**: Automatic background syncing not yet implemented (requires WorkManager)
- **Offline Support**: No offline queue for failed syncs (requires network connectivity)

