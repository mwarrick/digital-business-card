#!/bin/bash

echo "🧪 TESTING CONTACT CREATION AND SYNC"
echo "===================================="

echo ""
echo "📝 Creating a test contact via API..."

# Create a test contact
curl -X POST "https://sharemycard.app/api/contacts/" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer test" \
  -d '{
    "first_name": "John",
    "last_name": "Doe", 
    "email_primary": "john.doe@example.com",
    "work_phone": "+1-555-123-4567",
    "organization_name": "Test Company",
    "job_title": "Software Engineer",
    "city": "San Francisco",
    "state": "CA",
    "country": "USA"
  }' \
  -w "\nHTTP Status: %{http_code}\n" \
  -s

echo ""
echo "📋 Fetching contacts to verify creation..."

# Fetch contacts to see if it was created
curl -X GET "https://sharemycard.app/api/contacts/" \
  -H "Authorization: Bearer test" \
  -w "\nHTTP Status: %{http_code}\n" \
  -s | jq . 2>/dev/null || echo "Response not JSON or empty"

echo ""
echo "🔍 Analysis:"
echo "   - If HTTP Status is 401: Authentication issue"
echo "   - If HTTP Status is 200 but empty: No contacts in database"
echo "   - If HTTP Status is 200 with data: Contacts exist and sync should work"
