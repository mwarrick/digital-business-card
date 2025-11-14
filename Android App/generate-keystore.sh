#!/bin/bash

# Script to generate the ShareMyCard release keystore
# This will prompt you for a password - use a strong password and save it securely!

echo "🔐 Generating ShareMyCard Release Keystore"
echo ""
echo "⚠️  IMPORTANT: You will need to enter a password twice."
echo "⚠️  Save this password securely - you'll need it for all future app updates!"
echo ""

# Prompt for password
read -sp "Enter keystore password: " KEYSTORE_PASSWORD
echo ""
read -sp "Confirm keystore password: " KEYSTORE_PASSWORD_CONFIRM
echo ""

if [ "$KEYSTORE_PASSWORD" != "$KEYSTORE_PASSWORD_CONFIRM" ]; then
    echo "❌ Passwords don't match. Please try again."
    exit 1
fi

if [ -z "$KEYSTORE_PASSWORD" ]; then
    echo "❌ Password cannot be empty. Please try again."
    exit 1
fi

echo ""
echo "📝 Generating keystore..."

# Use Android Studio's bundled JDK if available, otherwise try system Java
if [ -f "/Applications/Android Studio.app/Contents/jbr/Contents/Home/bin/keytool" ]; then
    KEYTOOL="/Applications/Android Studio.app/Contents/jbr/Contents/Home/bin/keytool"
    echo "   Using Android Studio's bundled JDK"
elif [ -f "/Applications/Android Studio.app/Contents/jre/Contents/Home/bin/keytool" ]; then
    KEYTOOL="/Applications/Android Studio.app/Contents/jre/Contents/Home/bin/keytool"
    echo "   Using Android Studio's bundled JDK (legacy)"
elif command -v keytool >/dev/null 2>&1; then
    KEYTOOL="keytool"
    echo "   Using system keytool"
else
    echo "❌ keytool not found. Please install Java or use Android Studio's JDK."
    exit 1
fi

"$KEYTOOL" -genkey -v -keystore sharemycard-release.jks \
  -keyalg RSA -keysize 2048 -validity 10000 \
  -alias sharemycard \
  -storepass "$KEYSTORE_PASSWORD" \
  -keypass "$KEYSTORE_PASSWORD" \
  -dname "CN=ShareMyCard, OU=Development, O=Warrick, L=City, ST=State, C=US"

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Keystore created successfully: sharemycard-release.jks"
    echo ""
    echo "📝 Now creating keystore.properties file..."
    
    # Create keystore.properties file
    cat > keystore.properties << EOF
storePassword=$KEYSTORE_PASSWORD
keyPassword=$KEYSTORE_PASSWORD
keyAlias=sharemycard
storeFile=../sharemycard-release.jks
EOF
    
    echo "✅ keystore.properties created"
    echo ""
    echo "🔒 SECURITY REMINDERS:"
    echo "   • Keystore file: sharemycard-release.jks (already in .gitignore)"
    echo "   • Properties file: keystore.properties (already in .gitignore)"
    echo "   • Save your password in a password manager!"
    echo "   • Backup the keystore file securely!"
    echo ""
    echo "✅ Setup complete! You can now build a signed release APK/AAB."
else
    echo ""
    echo "❌ Failed to create keystore. Please check the error above."
    exit 1
fi

