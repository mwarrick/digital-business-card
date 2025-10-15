# ShareMyCard Icon Generator Scripts

## Quick Start

Generate all app icon sizes in one command:

```bash
cd /Users/markwarrick/Projects/QRCard
./Scripts/generate_icons.sh
```

## What It Does

The script automatically generates **all 19 iOS app icon sizes** from your `AppIconGenerator.swift` design:

- 📱 iPhone icons (2x, 3x)
- 📱 iPad icons (1x, 2x)
- 🔍 Spotlight & Settings icons
- 📢 Notification icons
- 🏪 App Store icon (1024x1024)

## Requirements

- **macOS 12.0+** (for SwiftUI rendering)
- **Xcode** (for Swift compiler)
- **Optional**: ImageMagick for better quality
  ```bash
  brew install imagemagick
  ```

## Files

- `generate_icons.sh` - Main script (uses ImageMagick if available)
- `generate_app_icon.swift` - Swift-based icon generator (fallback)

## After Running

1. **Clean the build** in Xcode: `Product → Clean Build Folder` (⇧⌘K)
2. **Rebuild** the project: `Product → Build` (⌘B)
3. **Run** on simulator or device to see the new icon

## Customizing the Icon

Edit `/QRCard/AppIconGenerator.swift` to change the icon design, then run the script again.

## Output

Icons are saved to:
```
/QRCard/Assets.xcassets/AppIcon.appiconset/
```

