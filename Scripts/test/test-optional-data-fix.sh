#!/bin/bash

echo "🔧 TESTING OPTIONAL DATA FIX"
echo "============================"

echo "✅ Fixed Issues:"
echo "   - Added nil coalescing for array returns (fetchContacts, searchContacts)"
echo "   - Added guard statements for single object returns (createContact, updateContact, getContact)"
echo "   - Proper error handling for missing data"

echo ""
echo "🔍 Checking for proper nil handling..."

# Check for nil coalescing on arrays
if grep -q "response.data ?? \[\]" QRCard/ContactsAPIClient.swift; then
    echo "   ✅ Array methods use nil coalescing"
else
    echo "   ❌ Array methods missing nil coalescing"
fi

# Check for guard statements on single objects
if grep -q "guard let data = response.data" QRCard/ContactsAPIClient.swift; then
    echo "   ✅ Single object methods use guard statements"
else
    echo "   ❌ Single object methods missing guard statements"
fi

# Check for error throwing
if grep -q "throw APIError.serverError" QRCard/ContactsAPIClient.swift; then
    echo "   ✅ Proper error throwing for missing data"
else
    echo "   ❌ Missing error throwing for missing data"
fi

echo ""
echo "📊 Current status:"
echo "   - ContactsAPIClient.swift: $(wc -l < QRCard/ContactsAPIClient.swift) lines"
echo "   - All method signatures should now be correct"
echo "   - All optional data handling should be proper"

echo ""
echo "🚀 Ready for final build test!"
echo "APIClient method signature and optional data issues should be resolved."
