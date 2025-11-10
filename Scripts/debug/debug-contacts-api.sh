#!/bin/bash

echo "🔍 DEBUGGING CONTACTS API ISSUE"
echo "==============================="

echo ""
echo "🔍 Testing different API endpoints..."

# Test 1: Check if the contacts API file exists and is accessible
echo "1. Testing contacts API file access:"
curl -s -o /dev/null -w "   /api/contacts/: %{http_code}\n" "https://sharemycard.app/api/contacts/"

# Test 2: Check if there's a different path
echo "2. Testing alternative paths:"
curl -s -o /dev/null -w "   /api/contacts: %{http_code}\n" "https://sharemycard.app/api/contacts"
curl -s -o /dev/null -w "   /api/contacts/index.php: %{http_code}\n" "https://sharemycard.app/api/contacts/index.php"

# Test 3: Check if there's a server error
echo "3. Testing with verbose output:"
echo "   GET /api/contacts/ response:"
curl -s "https://sharemycard.app/api/contacts/" -H "Authorization: Bearer test" | head -5

echo ""
echo "🔍 Comparing with working cards API:"
echo "   GET /api/cards/ response:"
curl -s "https://sharemycard.app/api/cards/" -H "Authorization: Bearer test" | head -5

echo ""
echo "🔍 Checking server configuration..."

# Check if there's a .htaccess or routing issue
echo "4. Testing server configuration:"
curl -s -I "https://sharemycard.app/api/contacts/" | grep -E "(HTTP|Content-Type|Location)"

echo ""
echo "📊 Analysis:"
echo "   - If contacts API returns 200 but empty: Server-side issue"
echo "   - If contacts API returns 404: File not found"
echo "   - If contacts API returns 500: PHP error"
echo "   - If cards API works but contacts doesn't: Configuration issue"
