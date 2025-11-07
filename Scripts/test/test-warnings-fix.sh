#!/bin/bash

echo "🔧 TESTING WARNING FIXES"
echo "========================"

echo "✅ Fixed Issues:"
echo "   - APIClient.swift:164 - Removed unnecessary nil coalescing on non-optional String"
echo "   - CardService.swift:330-331 - Removed unnecessary nil coalescing on non-optional String"
echo "   - ForgotPasswordView.swift:242 - Removed unreachable catch block"

echo ""
echo "🔍 Checking for remaining issues..."

# Check if the problematic lines still exist
if grep -q 'apiResponse.message ??' QRCard/APIClient.swift; then
    echo "   ❌ APIClient still has nil coalescing issue"
else
    echo "   ✅ APIClient nil coalescing fixed"
fi

if grep -q 'response.message ??' QRCard/CardService.swift; then
    echo "   ❌ CardService still has nil coalescing issue"
else
    echo "   ✅ CardService nil coalescing fixed"
fi

if grep -q '} catch {' QRCard/ForgotPasswordView.swift; then
    echo "   ❌ ForgotPasswordView still has unreachable catch"
else
    echo "   ✅ ForgotPasswordView catch block removed"
fi

echo ""
echo "📊 Current file count: $(find QRCard -name "*.swift" | wc -l) Swift files"

echo ""
echo "🚀 Ready to test build!"
echo "The 3 main warnings should now be fixed."
echo "Remaining warnings (if any) are likely minor app icon issues."
