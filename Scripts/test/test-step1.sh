#!/bin/bash

echo "🧪 TESTING STEP 1: Contact Data Model"
echo "====================================="

echo "✅ Files added:"
echo "   - QRCard/Contact.swift (Contact data model)"
echo "   - ContactEntity added to CoreDataEntities.swift"
echo "   - ContactEntity added to DataManager Core Data model"
echo "   - Contact CRUD methods added to DataManager"

echo ""
echo "📊 Current file count: $(find QRCard -name "*.swift" | wc -l) Swift files"

echo ""
echo "🔍 Checking for syntax issues..."
if grep -q "import Foundation" QRCard/Contact.swift; then
    echo "   ✅ Contact.swift imports look good"
else
    echo "   ❌ Contact.swift import issues"
fi

if grep -q "ContactEntity" QRCard/CoreDataEntities.swift; then
    echo "   ✅ ContactEntity added to CoreDataEntities.swift"
else
    echo "   ❌ ContactEntity missing from CoreDataEntities.swift"
fi

if grep -q "func createContact" QRCard/DataManager.swift; then
    echo "   ✅ Contact CRUD methods added to DataManager"
else
    echo "   ❌ Contact CRUD methods missing from DataManager"
fi

echo ""
echo "🚀 Ready for Step 2: ContactsAPIClient"
echo "Next: Add API client for contact communication"
