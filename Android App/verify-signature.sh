#!/bin/bash

# Script to verify if an AAB or APK is signed
# Usage: ./verify-signature.sh path/to/app-release.aab
#        ./verify-signature.sh path/to/app-release.apk

if [ -z "$1" ]; then
    echo "Usage: ./verify-signature.sh <path-to-aab-or-apk>"
    echo ""
    echo "Example: ./verify-signature.sh app/release/app-release.aab"
    exit 1
fi

FILE="$1"

if [ ! -f "$FILE" ]; then
    echo "❌ File not found: $FILE"
    exit 1
fi

echo "🔍 Verifying signature for: $FILE"
echo ""

# Use Android Studio's bundled JDK if available
if [ -f "/Applications/Android Studio.app/Contents/jbr/Contents/Home/bin/jarsigner" ]; then
    JARSIGNER="/Applications/Android Studio.app/Contents/jbr/Contents/Home/bin/jarsigner"
elif [ -f "/Applications/Android Studio.app/Contents/jre/Contents/Home/bin/jarsigner" ]; then
    JARSIGNER="/Applications/Android Studio.app/Contents/jre/Contents/Home/bin/jarsigner"
elif command -v jarsigner >/dev/null 2>&1; then
    JARSIGNER="jarsigner"
else
    echo "❌ jarsigner not found. Please install Java or use Android Studio's JDK."
    exit 1
fi

# Check if it's an AAB or APK
if [[ "$FILE" == *.aab ]]; then
    echo "📦 Detected: Android App Bundle (.aab)"
    echo ""
    echo "Verifying signature..."
    "$JARSIGNER" -verify -verbose -certs "$FILE" 2>&1 | head -20
    
    if [ $? -eq 0 ]; then
        echo ""
        echo "✅ AAB is properly signed!"
    else
        echo ""
        echo "❌ AAB signature verification failed or file is not signed"
    fi
    
elif [[ "$FILE" == *.apk ]]; then
    echo "📱 Detected: Android APK (.apk)"
    echo ""
    
    # Try using apksigner if available (Android SDK tool)
    if command -v apksigner >/dev/null 2>&1; then
        echo "Using apksigner to verify..."
        apksigner verify --verbose "$FILE"
        
        if [ $? -eq 0 ]; then
            echo ""
            echo "✅ APK is properly signed!"
        else
            echo ""
            echo "❌ APK signature verification failed"
        fi
    else
        echo "Using jarsigner to verify..."
        "$JARSIGNER" -verify -verbose -certs "$FILE" 2>&1 | head -20
        
        if [ $? -eq 0 ]; then
            echo ""
            echo "✅ APK is properly signed!"
        else
            echo ""
            echo "❌ APK signature verification failed or file is not signed"
        fi
    fi
else
    echo "❌ Unknown file type. Expected .aab or .apk"
    exit 1
fi

echo ""
echo "📋 Additional Info:"
echo "   File: $FILE"
echo "   Size: $(ls -lh "$FILE" | awk '{print $5}')"
echo "   Modified: $(ls -l "$FILE" | awk '{print $6, $7, $8}')"

