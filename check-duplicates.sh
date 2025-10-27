#!/bin/bash

echo "🔍 CHECKING FOR DUPLICATE DECLARATIONS"
echo "======================================"

echo "✅ Fixed:"
echo "   - Removed duplicate EmptyResponse from ContactsAPIClient.swift"

echo ""
echo "🔍 Checking for other potential duplicates..."

# Check for duplicate struct/class names
echo ""
echo "📋 Struct/Class names across all Swift files:"
grep -h "^struct\|^class\|^enum" QRCard/*.swift | grep -v "//" | sort | uniq -c | sort -nr

echo ""
echo "🔍 Checking for common naming conflicts..."

# Check for Contact-related duplicates
if grep -q "struct Contact" QRCard/Contact.swift && grep -q "struct Contact" QRCard/CoreDataEntities.swift; then
    echo "   ❌ Contact struct defined in multiple files"
else
    echo "   ✅ Contact struct properly defined"
fi

# Check for API client duplicates
if grep -q "class APIClient" QRCard/APIClient.swift && grep -q "class APIClient" QRCard/ContactsAPIClient.swift; then
    echo "   ❌ APIClient class defined in multiple files"
else
    echo "   ✅ APIClient class properly defined"
fi

echo ""
echo "📊 Current file count: $(find QRCard -name "*.swift" | wc -l) Swift files"
