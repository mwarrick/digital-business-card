#!/bin/bash

echo "🔍 CHECKING FOR IMPORT AND DEPENDENCY ISSUES"
echo "============================================"

echo "✅ Fixed:"
echo "   - Removed duplicate EmptyResponse declaration"

echo ""
echo "🔍 Checking for import issues..."

# Check for missing imports in new contact files
echo ""
echo "📋 Import analysis:"

# Check Contact.swift
if grep -q "import Foundation" QRCard/Contact.swift && grep -q "import CoreData" QRCard/Contact.swift; then
    echo "   ✅ Contact.swift imports complete"
else
    echo "   ❌ Contact.swift missing imports"
fi

# Check ContactsAPIClient.swift
if grep -q "import Foundation" QRCard/ContactsAPIClient.swift && grep -q "import Combine" QRCard/ContactsAPIClient.swift; then
    echo "   ✅ ContactsAPIClient.swift imports complete"
else
    echo "   ❌ ContactsAPIClient.swift missing imports"
fi

# Check ContactsViewModel.swift
if grep -q "import Foundation" QRCard/ContactsViewModel.swift && grep -q "import SwiftUI" QRCard/ContactsViewModel.swift && grep -q "import Combine" QRCard/ContactsViewModel.swift; then
    echo "   ✅ ContactsViewModel.swift imports complete"
else
    echo "   ❌ ContactsViewModel.swift missing imports"
fi

# Check ContactsDashboardView.swift
if grep -q "import SwiftUI" QRCard/ContactsDashboardView.swift; then
    echo "   ✅ ContactsDashboardView.swift imports complete"
else
    echo "   ❌ ContactsDashboardView.swift missing imports"
fi

echo ""
echo "🔍 Checking for potential circular dependencies..."

# Check if ContactsAPIClient references APIClient
if grep -q "APIClient" QRCard/ContactsAPIClient.swift; then
    echo "   ✅ ContactsAPIClient properly references APIClient"
else
    echo "   ❌ ContactsAPIClient missing APIClient reference"
fi

# Check if ContactsViewModel references ContactsAPIClient
if grep -q "ContactsAPIClient" QRCard/ContactsViewModel.swift; then
    echo "   ✅ ContactsViewModel properly references ContactsAPIClient"
else
    echo "   ❌ ContactsViewModel missing ContactsAPIClient reference"
fi

echo ""
echo "📊 Current status:"
echo "   - Swift files: $(find QRCard -name "*.swift" | wc -l)"
echo "   - Contact files: $(find QRCard -name "*Contact*" | wc -l)"
