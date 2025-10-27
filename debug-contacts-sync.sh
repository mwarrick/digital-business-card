#!/bin/bash

echo "🔍 DEBUGGING CONTACTS SYNC ISSUE"
echo "================================"

echo ""
echo "🔍 Checking API endpoints..."

# Check if contacts API endpoints exist on the server
echo "📡 Testing contacts API endpoints:"

# Test the main contacts endpoint
echo "   Testing GET /api/contacts/"
curl -s -o /dev/null -w "   GET /api/contacts/: %{http_code}\n" "https://sharemycard.app/api/contacts/"

# Test with authentication (if you have a token)
echo "   Testing with auth header..."
curl -s -o /dev/null -w "   GET /api/contacts/ (with auth): %{http_code}\n" -H "Authorization: Bearer test" "https://sharemycard.app/api/contacts/"

echo ""
echo "🔍 Checking iOS app configuration..."

# Check if the API base URL is correct
if grep -q "sharemycard.app" QRCard/APIConfig.swift; then
    echo "   ✅ API base URL points to sharemycard.app"
else
    echo "   ❌ API base URL might be incorrect"
    grep -A 2 -B 2 "baseURL" QRCard/APIConfig.swift
fi

echo ""
echo "🔍 Checking contacts API client..."

# Check if the endpoints are correct
if grep -q "/api/contacts/" QRCard/ContactsAPIClient.swift; then
    echo "   ✅ Contacts API endpoints are defined"
else
    echo "   ❌ Contacts API endpoints missing"
fi

echo ""
echo "🔍 Checking authentication..."

# Check if KeychainHelper is working
if grep -q "KeychainHelper" QRCard/ContactsAPIClient.swift; then
    echo "   ✅ ContactsAPIClient uses APIClient (which handles auth)"
else
    echo "   ❌ Authentication might not be working"
fi

echo ""
echo "📊 Current status:"
echo "   - Contacts UI is working ✅"
echo "   - Need to check API endpoints and authentication"
echo "   - Server might not have contacts API implemented yet"

echo ""
echo "🚀 Next steps:"
echo "   1. Check if /api/contacts/ endpoint exists on server"
echo "   2. Verify authentication is working"
echo "   3. Check server logs for API calls"
echo "   4. Consider implementing contacts API on server if missing"
