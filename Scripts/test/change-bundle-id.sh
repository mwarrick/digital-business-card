#!/bin/bash

echo "🔧 Changing bundle identifier to avoid distribution profile conflict..."

# Generate a unique bundle ID
UNIQUE_ID=$(date +%s)
NEW_BUNDLE_ID="net.warrick.ShareMyCard.dev.$UNIQUE_ID"

echo "📱 New Bundle ID: $NEW_BUNDLE_ID"

# Backup the original project file
cp QRCard.xcodeproj/project.pbxproj QRCard.xcodeproj/project.pbxproj.backup

# Replace the bundle identifier
sed -i '' "s/net.warrick.ShareMyCard/$NEW_BUNDLE_ID/g" QRCard.xcodeproj/project.pbxproj

echo "✅ Bundle identifier updated!"
echo ""
echo "📋 Now in Xcode:"
echo "1. Select the QRCard project"
echo "2. Select the ShareMyCard target"
echo "3. Go to 'Signing & Capabilities'"
echo "4. Make sure 'Automatically manage signing' is checked"
echo "5. Select your Team"
echo "6. The Bundle Identifier should now be: $NEW_BUNDLE_ID"
echo "7. Xcode will create a new development provisioning profile"
echo ""
echo "🎯 This won't affect your distribution profile at all!"
