#!/bin/bash

echo "🔍 CHECKING APICLIENT ISSUES"
echo "============================"

echo "🔍 Checking for missing imports..."

# Check if Contact types are accessible
if grep -q "Contact" QRCard/ContactsAPIClient.swift; then
    echo "   ✅ Contact type referenced in ContactsAPIClient"
else
    echo "   ❌ Contact type missing from ContactsAPIClient"
fi

# Check if APIResponse is accessible
if grep -q "APIResponse" QRCard/ContactsAPIClient.swift; then
    echo "   ✅ APIResponse type referenced in ContactsAPIClient"
else
    echo "   ❌ APIResponse type missing from ContactsAPIClient"
fi

echo ""
echo "🔍 Checking for potential type issues..."

# Check if Contact is defined
if grep -q "struct Contact:" QRCard/Contact.swift; then
    echo "   ✅ Contact struct defined"
else
    echo "   ❌ Contact struct missing"
fi

# Check if ContactCreateData is defined
if grep -q "struct ContactCreateData:" QRCard/Contact.swift; then
    echo "   ✅ ContactCreateData struct defined"
else
    echo "   ❌ ContactCreateData struct missing"
fi

echo ""
echo "🔍 Checking for method signature issues..."

# Check if APIClient has the request method
if grep -q "func request" QRCard/APIClient.swift; then
    echo "   ✅ APIClient has request method"
else
    echo "   ❌ APIClient missing request method"
fi

# Check if the request method signature matches usage
if grep -q "func request.*endpoint.*method" QRCard/APIClient.swift; then
    echo "   ✅ APIClient request method signature looks correct"
else
    echo "   ❌ APIClient request method signature might be wrong"
fi

echo ""
echo "📊 Current file status:"
echo "   - APIClient.swift: $(wc -l < QRCard/APIClient.swift) lines"
echo "   - ContactsAPIClient.swift: $(wc -l < QRCard/ContactsAPIClient.swift) lines"
echo "   - Contact.swift: $(wc -l < QRCard/Contact.swift) lines"
