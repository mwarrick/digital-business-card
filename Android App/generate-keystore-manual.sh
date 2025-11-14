#!/bin/bash

# Manual keystore generation script
# Usage: ./generate-keystore-manual.sh YOUR_PASSWORD

if [ -z "$1" ]; then
    echo "Usage: ./generate-keystore-manual.sh YOUR_PASSWORD"
    echo ""
    echo "Example: ./generate-keystore-manual.sh MySecurePassword123!"
    exit 1
fi

KEYSTORE_PASSWORD="$1"

# Use Android Studio's bundled JDK if available
if [ -f "/Applications/Android Studio.app/Contents/jbr/Contents/Home/bin/keytool" ]; then
    KEYTOOL="/Applications/Android Studio.app/Contents/jbr/Contents/Home/bin/keytool"
elif [ -f "/Applications/Android Studio.app/Contents/jre/Contents/Home/bin/keytool" ]; then
    KEYTOOL="/Applications/Android Studio.app/Contents/jre/Contents/Home/bin/keytool"
elif command -v keytool >/dev/null 2>&1; then
    KEYTOOL="keytool"
else
    echo "❌ keytool not found. Please install Java or use Android Studio's JDK."
    exit 1
fi

echo "🔐 Generating ShareMyCard Release Keystore"
echo "   Using: $KEYTOOL"
echo ""

# Generate keystore (one level up from Android App directory)
cd "$(dirname "$0")/.." || exit 1

"$KEYTOOL" -genkey -v -keystore sharemycard-release.jks \
  -keyalg RSA -keysize 2048 -validity 10000 \
  -alias sharemycard \
  -storepass "$KEYSTORE_PASSWORD" \
  -keypass "$KEYSTORE_PASSWORD" \
  -dname "CN=ShareMyCard, OU=Development, O=Warrick, L=City, ST=State, C=US"

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Keystore created successfully: $(pwd)/sharemycard-release.jks"
    echo ""
    echo "📝 Now creating keystore.properties file..."
    
    # Create keystore.properties in Android App directory
    cd "Android App" || exit 1
    cat > keystore.properties << EOF
storePassword=$KEYSTORE_PASSWORD
keyPassword=$KEYSTORE_PASSWORD
keyAlias=sharemycard
storeFile=../sharemycard-release.jks
EOF
    
    echo "✅ keystore.properties created in: $(pwd)/keystore.properties"
    echo ""
    echo "🔒 SECURITY REMINDERS:"
    echo "   • Keystore file: $(cd .. && pwd)/sharemycard-release.jks"
    echo "   • Properties file: $(pwd)/keystore.properties"
    echo "   • Both files are in .gitignore and will NOT be committed"
    echo "   • Save your password in a password manager!"
    echo "   • Backup the keystore file securely!"
    echo ""
    echo "✅ Setup complete! You can now build a signed release APK/AAB."
else
    echo ""
    echo "❌ Failed to create keystore. Please check the error above."
    exit 1
fi

