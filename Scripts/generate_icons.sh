#!/bin/bash

# ShareMyCard App Icon Generator
# Generates all required iOS app icon sizes

echo "🎨 ShareMyCard App Icon Generator"
echo "=================================="
echo ""

# Get script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_ROOT"

echo "📂 Project root: $PROJECT_ROOT"
echo ""

# Use Swift script directly (works on macOS 12+)
echo "📱 Generating app icons using Swift..."
swift "$SCRIPT_DIR/generate_app_icon.swift"

echo ""
echo "✨ Done! App icons generated successfully!"
echo "📱 Open the project in Xcode to see the new icons."
echo ""
echo "💡 Tip: Clean and rebuild the project to see changes:"
echo "   Product → Clean Build Folder (⇧⌘K)"
echo "   Product → Build (⌘B)"

