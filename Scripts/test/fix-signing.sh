#!/bin/bash

echo "🔧 Fixing code signing configuration..."

# Backup the original project file
cp QRCard.xcodeproj/project.pbxproj QRCard.xcodeproj/project.pbxproj.backup

# Change CODE_SIGN_STYLE from Manual to Automatic
sed -i '' 's/CODE_SIGN_STYLE = Manual;/CODE_SIGN_STYLE = Automatic;/g' QRCard.xcodeproj/project.pbxproj

# Remove manual code sign identity settings
sed -i '' '/CODE_SIGN_IDENTITY = "Apple Development";/d' QRCard.xcodeproj/project.pbxproj
sed -i '' '/"CODE_SIGN_IDENTITY\[sdk=iphoneos\*\]" = "iPhone Distribution";/d' QRCard.xcodeproj/project.pbxproj

echo "✅ Code signing configuration updated!"
echo "📱 Now open the project in Xcode and:"
echo "   1. Select the project"
echo "   2. Select the ShareMyCard target"
echo "   3. Go to 'Signing & Capabilities'"
echo "   4. Make sure 'Automatically manage signing' is checked"
echo "   5. Select your Apple Developer Team"
echo "   6. Try building again!"
