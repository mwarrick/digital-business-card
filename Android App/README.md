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

2. **UI & Navigation** ✅ (Basic Structure)
   - Tab-based navigation (Home, Cards, Contacts, Leads, Settings)
   - Home screen with counts and links
   - Login, Registration, and Verification screens
   - Navigation graph with proper back stack management

3. **Contacts Management** (Partial)
   - Contact list view
   - Contact details view with formatted display
   - Contact export to device contacts (with runtime permissions)
   - Contact editing
   - Source tracking (manual, converted, qr_scan)

4. **Leads Management** (Partial)
   - Lead list view
   - Lead details view with formatted display
   - Lead conversion to contacts

5. **Settings & Account** ✅
   - Password settings screen (set/change password)
   - Account security section
   - Logout functionality
   - Report Issues link

### 🚧 In Progress / Planned

- Business Cards CRUD operations
- QR Code generation and scanning
- Sync & Data Management
- Media management
- Full Contacts and Leads features

### Development Plan

Implementation follows the modular development plan:
1. ✅ Authentication & Security
2. ✅ UI & Navigation (Basic)
3. 🚧 Business Cards
4. 🚧 Sync & Data Management
5. 🚧 QR Code Features
6. 🚧 Contacts Management (Partial)
7. 🚧 Leads Management (Partial)
8. ✅ Settings & Account

## Notes

- The Gradle wrapper scripts (`gradlew` and `gradlew.bat`) will be generated automatically when Android Studio syncs the project
- If you encounter sync issues, try: `File > Invalidate Caches / Restart`

## Recent Updates

- ✅ **Account Security**: Password settings screen with ability to set password (if none exists) or change password (if password exists)
- ✅ **Demo Account Login**: One-tap demo login button that bypasses verification and navigates directly to home screen
- ✅ **Contact Export**: Export contacts to device's native contacts app with runtime permission handling
- ✅ **UI Improvements**: Formatted detail views for contacts and leads with icons, clickable actions, and text wrapping

## Known Limitations

### Android 7.1.1 (API 25) Compatibility

- **QR Code Scanning for Contacts**: QR code scanning functionality to create contacts from scanned QR codes is not available on Android 7.1.1 (API 25) due to camera API limitations. Contacts created from QR scans on other devices will still display correctly, but the "qr_scan" source indicator is hidden on API 25 devices.
- **QR Code Generation**: QR code generation for business cards works on all supported Android versions including API 25.

