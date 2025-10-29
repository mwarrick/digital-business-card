#!/bin/bash

echo "🧪 TESTING RATE LIMIT BYPASS"
echo "============================"

echo ""
echo "✅ Changes Applied:"
echo "   - Added IP 76.175.179.85 to bypass list"
echo "   - Added comprehensive IP detection"
echo "   - Added logging for bypassed requests"

echo ""
echo "🔍 Testing multiple API calls to verify bypass:"

# Test 1: Make multiple calls to contacts API
echo "1. Testing contacts API (multiple calls):"
for i in {1..5}; do
    echo "   Call $i:"
    response=$(curl -s "https://sharemycard.app/api/contacts/" -H "Authorization: Bearer test")
    echo "   Response: $response"
    sleep 1
done

echo ""
echo "2. Testing cards API (multiple calls):"
for i in {1..3}; do
    echo "   Call $i:"
    response=$(curl -s "https://sharemycard.app/api/cards/" -H "Authorization: Bearer test")
    echo "   Response: $response"
    sleep 1
done

echo ""
echo "📊 Expected Results:"
echo "   - All calls should succeed (no rate limit errors)"
echo "   - Server logs should show 'Rate limiting bypassed for IP: 76.175.179.85'"
echo "   - No 'too many requests' errors"

echo ""
echo "💡 If you still get rate limit errors:"
echo "   1. Check if your IP is being detected correctly"
echo "   2. The IP might be behind a proxy/load balancer"
echo "   3. We can add additional IP detection methods"

echo ""
echo "🎯 Rate limiting bypass is now active for your IP!"
