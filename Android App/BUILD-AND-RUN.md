# How to Build and Run ShareMyCard Android App

## Prerequisites

- **Android Studio**: Hedgehog (2023.1.1) or later
- **JDK**: 17 or later (Android Studio usually includes this)
- **Android SDK**: API 25-34 installed

## Step-by-Step Instructions

### 1. Open the Project

1. Launch **Android Studio**
2. Click **File → Open** (or "Open an Existing Project")
3. Navigate to: `U:\Github Projects\digital-business-card\Android App`
4. Click **OK**

### 2. Wait for Gradle Sync

- Android Studio will automatically detect the project and start syncing
- You'll see "Gradle sync" in the bottom status bar
- Wait for "Gradle sync finished" message
- **First time may take 5-10 minutes** (downloading dependencies)

### 3. Fix Any Sync Issues (if needed)

If Gradle sync fails:

1. **File → Invalidate Caches / Restart**
2. Select **"Invalidate and Restart"**
3. Wait for Android Studio to restart and sync again

If dependencies fail to download:

1. Check your internet connection
2. **File → Settings → Build, Execution, Deployment → Gradle**
3. Ensure "Use Gradle from" is set to "gradle-wrapper.properties file"
4. Try **File → Sync Project with Gradle Files**

### 4. Set Up an Emulator (Recommended for Testing)

1. Click **Tools → Device Manager**
2. Click **Create Device** (or the + icon)
3. Select a device (e.g., **Pixel 6**)
4. Click **Next**
5. Select a system image (e.g., **API 34 - Android 14**)
   - If not downloaded, click **Download** next to it
   - Wait for download to complete
6. Click **Next → Finish**
7. The emulator will appear in Device Manager
8. Click the **Play icon** (▶) next to the emulator to start it
9. Wait for the emulator to boot (first time may take a few minutes)

### 5. Connect a Physical Device (Alternative)

1. Enable **Developer Options** on your Android device:
   - Go to **Settings → About Phone**
   - Tap **Build Number** 7 times
2. Enable **USB Debugging**:
   - Go to **Settings → Developer Options**
   - Enable **USB Debugging**
3. Connect device via USB
4. Allow USB debugging when prompted on device
5. Device should appear in Android Studio's device dropdown

### 6. Build and Run

1. **Select your device/emulator** from the dropdown at the top toolbar
2. Click the **Run button** (green play icon ▶) or press **Shift+F10**
3. Android Studio will:
   - Build the project (first build may take 2-5 minutes)
   - Install the app on your device/emulator
   - Launch the app

### 7. What to Expect

When the app launches, you should see:
- **Login Screen** (if not logged in)
- Options to:
  - Enter email and login
  - Create a new account
  - Navigate to verification screen

## Troubleshooting

### Build Errors

**Error: "Could not resolve all dependencies"**
- Check internet connection
- **File → Sync Project with Gradle Files**
- Try **File → Invalidate Caches / Restart**

**Error: "Gradle sync failed"**
- Check that JDK 17 is configured:
  - **File → Settings → Build, Execution, Deployment → Build Tools → Gradle**
  - Set "Gradle JDK" to 17 or later
- Try deleting `.gradle` folder in project root and re-syncing

**Error: "Hilt not found" or annotation processor errors**
- Ensure Hilt plugin is applied in `build.gradle.kts`
- **Build → Clean Project**
- **Build → Rebuild Project**

### Runtime Errors

**App crashes on launch**
- Check **Logcat** window at bottom of Android Studio
- Look for red error messages
- Common issues:
  - Missing permissions (should be in AndroidManifest.xml)
  - Network errors (check API base URL)

**"App keeps stopping"**
- Check Logcat for stack traces
- Ensure emulator/device meets minimum SDK (API 25 - Android 7.1.1)

### Performance

**Slow builds**
- First build is always slow (downloading dependencies)
- Subsequent builds should be faster
- Consider using **Build → Make Project** instead of full rebuild

## Quick Commands

- **Build**: **Build → Make Project** (Ctrl+F9)
- **Run**: **Run → Run 'app'** (Shift+F10)
- **Clean**: **Build → Clean Project**
- **Rebuild**: **Build → Rebuild Project**
- **Sync**: **File → Sync Project with Gradle Files**

## Testing the Authentication Flow

Once the app is running:

1. **Register a new user**:
   - Click "Create Account"
   - Enter an email address
   - Check your email for verification code
   - Enter code to verify

2. **Login**:
   - Enter your email
   - If you have a password, enter it
   - Otherwise, enter the verification code from email

3. **Set/Change Password**:
   - Navigate to Settings (when implemented)
   - Set or change your password

## Next Steps

After successfully building and running:
- Test the authentication flow
- Verify API connectivity (check Logcat for API calls)
- Report any issues or errors you encounter

