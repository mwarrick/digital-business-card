#!/bin/bash

echo "🔧 TESTING DUPLICATE DECLARATION FIXES"
echo "======================================"

echo "✅ Fixed Issues:"
echo "   - Removed duplicate EmptyResponse from ContactsAPIClient.swift"
echo "   - Removed duplicate ContactEntity from Contact.swift"
echo "   - Moved ContactEntity extensions to CoreDataEntities.swift"

echo ""
echo "🔍 Checking for remaining duplicates..."

# Check for ContactEntity duplicates
contactEntityCount=$(grep -c "class ContactEntity" QRCard/*.swift)
if [ "$contactEntityCount" -eq 1 ]; then
    echo "   ✅ ContactEntity defined only once"
else
    echo "   ❌ ContactEntity still has duplicates ($contactEntityCount found)"
fi

# Check for EmptyResponse duplicates
emptyResponseCount=$(grep -c "struct EmptyResponse" QRCard/*.swift)
if [ "$emptyResponseCount" -eq 1 ]; then
    echo "   ✅ EmptyResponse defined only once"
else
    echo "   ❌ EmptyResponse still has duplicates ($emptyResponseCount found)"
fi

echo ""
echo "🔍 Checking for other potential issues..."

# Check if Contact.swift still has ContactEntity references
if grep -q "ContactEntity" QRCard/Contact.swift; then
    echo "   ⚠️  Contact.swift still references ContactEntity (should be OK if just comments)"
else
    echo "   ✅ Contact.swift clean of ContactEntity references"
fi

# Check if CoreDataEntities.swift has the extensions
if grep -q "func updateFromContact" QRCard/CoreDataEntities.swift; then
    echo "   ✅ ContactEntity extensions properly moved to CoreDataEntities.swift"
else
    echo "   ❌ ContactEntity extensions missing from CoreDataEntities.swift"
fi

echo ""
echo "📊 Current status:"
echo "   - Swift files: $(find QRCard -name "*.swift" | wc -l)"
echo "   - Contact files: $(find QRCard -name "*Contact*" | wc -l)"

echo ""
echo "🚀 Ready for next build test!"
echo "Major duplicate declaration issues should be resolved."
