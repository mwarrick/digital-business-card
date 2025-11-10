#!/bin/bash

echo "📱 Setting up device build for ShareMyCard..."

# Open Xcode with the project
echo "🔧 Opening Xcode..."
open QRCard.xcodeproj

echo ""
echo "📋 Follow these steps in Xcode:"
echo ""
echo "1️⃣  Select the 'QRCard' project in the navigator"
echo "2️⃣  Select the 'ShareMyCard' target"
echo "3️⃣  Click on 'Signing & Capabilities' tab"
echo "4️⃣  Check 'Automatically manage signing'"
echo "5️⃣  Select your Apple Developer Team from the dropdown"
echo "6️⃣  If needed, change the Bundle Identifier to something unique like:"
echo "    net.warrick.ShareMyCard.$(date +%s)"
echo ""
echo "7️⃣  Connect your iPhone via USB"
echo "8️⃣  Select your device as the destination"
echo "9️⃣  Click the 'Run' button (▶️) to build and install"
echo ""
echo "🔍 If you still get errors:"
echo "   - Make sure your iPhone is trusted (check iPhone Settings)"
echo "   - Make sure you have a valid Apple Developer account"
echo "   - Try changing the Bundle Identifier to something unique"
echo ""
echo "✅ Ready to test QR scanning on your device!"
