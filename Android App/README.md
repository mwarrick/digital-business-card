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
- Android SDK (API 26 - 34)

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
- **Min SDK**: 26 (Android 8.0)
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

This is a fresh project setup. Implementation will follow the modular development plan:
1. Authentication & Security
2. UI & Navigation
3. Business Cards
4. Sync & Data Management
5. QR Code Features
6. Contacts Management
7. Leads Management
8. Settings & Account

## Notes

- The Gradle wrapper scripts (`gradlew` and `gradlew.bat`) will be generated automatically when Android Studio syncs the project
- If you encounter sync issues, try: `File > Invalidate Caches / Restart`

