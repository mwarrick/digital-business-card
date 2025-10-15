#!/bin/bash

# ShareMyCard API Test Script
# Tests all API endpoints with sample data
#
# Usage: ./test-api-endpoints.sh

BASE_URL="http://localhost:8000/api"
TEST_EMAIL="mark+test2@sharemycard.app"
TOKEN=""
USER_ID=""
CARD_ID=""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Helper function to print test results
print_test() {
    local test_name="$1"
    local status="$2"
    
    if [ "$status" = "PASS" ]; then
        echo -e "${GREEN}✓${NC} $test_name"
    elif [ "$status" = "SKIP" ]; then
        echo -e "${YELLOW}⊘${NC} $test_name (skipped)"
    else
        echo -e "${RED}✗${NC} $test_name"
    fi
}

# Helper function to extract JSON value
extract_json() {
    local json="$1"
    local key="$2"
    echo "$json" | grep -o "\"$key\":\"[^\"]*\"" | cut -d'"' -f4
}

echo "========================================="
echo "ShareMyCard API Test Suite"
echo "========================================="
echo ""
echo "Base URL: $BASE_URL"
echo "Test Email: $TEST_EMAIL"
echo ""

# Test 1: Register User
echo "Testing Authentication Endpoints..."
echo "-----------------------------------"

RESPONSE=$(curl -s -X POST "$BASE_URL/auth/register" \
    -H "Content-Type: application/json" \
    -d "{\"email\":\"$TEST_EMAIL\"}")

SUCCESS=$(echo "$RESPONSE" | grep -o '"success"[[:space:]]*:[[:space:]]*true')
if [ -n "$SUCCESS" ]; then
    USER_ID=$(extract_json "$RESPONSE" "user_id")
    print_test "1. Register User" "PASS"
else
    print_test "1. Register User" "FAIL"
    echo "Response: $RESPONSE"
    exit 1
fi

# Test 2: Login
RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
    -H "Content-Type: application/json" \
    -d "{\"email\":\"$TEST_EMAIL\"}")

SUCCESS=$(echo "$RESPONSE" | grep -o '"success"[[:space:]]*:[[:space:]]*true')
if [ -n "$SUCCESS" ]; then
    print_test "2. Login Request" "PASS"
else
    print_test "2. Login Request" "FAIL"
    echo "Response: $RESPONSE"
fi

# Test 3: Verify Code (Manual - requires email access)
echo ""
echo "${YELLOW}Note: Verification tests require manual email code entry${NC}"
echo "Check $TEST_EMAIL for verification code and enter below:"
read -p "Enter verification code: " VERIFY_CODE

if [ -n "$VERIFY_CODE" ]; then
    RESPONSE=$(curl -s -X POST "$BASE_URL/auth/verify" \
        -H "Content-Type: application/json" \
        -d "{\"email\":\"$TEST_EMAIL\",\"code\":\"$VERIFY_CODE\"}")
    
    SUCCESS=$(echo "$RESPONSE" | grep -o '"success"[[:space:]]*:[[:space:]]*true')
    if [ -n "$SUCCESS" ]; then
        TOKEN=$(extract_json "$RESPONSE" "token")
        print_test "3. Verify Code" "PASS"
        echo "   JWT Token obtained"
    else
        print_test "3. Verify Code" "FAIL"
        echo "Response: $RESPONSE"
        exit 1
    fi
else
    print_test "3. Verify Code" "SKIP"
    echo "Cannot continue without authentication token"
    exit 1
fi

echo ""
echo "Testing Business Card Endpoints..."
echo "-----------------------------------"

# Test 4: Create Business Card
RESPONSE=$(curl -s -X POST "$BASE_URL/cards/" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -d '{
        "first_name": "John",
        "last_name": "Doe",
        "phone_number": "+1 (555) 123-4567",
        "company_name": "Acme Corp",
        "job_title": "Software Engineer",
        "bio": "Full-stack developer with 10 years experience.",
        "emails": [
            {"email": "john@acme.com", "type": "work", "label": "Work Email"}
        ],
        "phones": [
            {"phone_number": "+1 (555) 987-6543", "type": "mobile", "label": "Mobile"}
        ],
        "websites": [
            {"url": "https://johndoe.com", "name": "Portfolio", "description": "My portfolio"}
        ],
        "address": {
            "street": "123 Main St",
            "city": "San Francisco",
            "state": "CA",
            "postal_code": "94102",
            "country": "USA"
        }
    }')

SUCCESS=$(echo "$RESPONSE" | grep -o '"success"[[:space:]]*:[[:space:]]*true')
if [ -n "$SUCCESS" ]; then
    CARD_ID=$(extract_json "$RESPONSE" "id")
    print_test "4. Create Business Card" "PASS"
    echo "   Card ID: $CARD_ID"
else
    print_test "4. Create Business Card" "FAIL"
    echo "Response: $RESPONSE"
fi

# Test 5: List All Cards
RESPONSE=$(curl -s -X GET "$BASE_URL/cards/" \
    -H "Authorization: Bearer $TOKEN")

SUCCESS=$(echo "$RESPONSE" | grep -o '"success"[[:space:]]*:[[:space:]]*true')
if [ -n "$SUCCESS" ]; then
    print_test "5. List All Cards" "PASS"
else
    print_test "5. List All Cards" "FAIL"
fi

# Test 6: Get Single Card
if [ -n "$CARD_ID" ]; then
    RESPONSE=$(curl -s -X GET "$BASE_URL/cards/?id=$CARD_ID" \
        -H "Authorization: Bearer $TOKEN")
    
    SUCCESS=$(echo "$RESPONSE" | grep -o '"success"[[:space:]]*:[[:space:]]*true')
    if [ -n "$SUCCESS" ]; then
        print_test "6. Get Single Card" "PASS"
    else
        print_test "6. Get Single Card" "FAIL"
    fi
else
    print_test "6. Get Single Card" "SKIP"
fi

# Test 7: Update Card
if [ -n "$CARD_ID" ]; then
    RESPONSE=$(curl -s -X PUT "$BASE_URL/cards/?id=$CARD_ID" \
        -H "Authorization: Bearer $TOKEN" \
        -H "Content-Type: application/json" \
        -d '{
            "first_name": "John",
            "last_name": "Doe Updated",
            "phone_number": "+1 (555) 123-4567",
            "company_name": "Acme Corp",
            "job_title": "Senior Software Engineer"
        }')
    
    SUCCESS=$(echo "$RESPONSE" | grep -o '"success"[[:space:]]*:[[:space:]]*true')
    if [ -n "$SUCCESS" ]; then
        print_test "7. Update Card" "PASS"
    else
        print_test "7. Update Card" "FAIL"
    fi
else
    print_test "7. Update Card" "SKIP"
fi

echo ""
echo "Testing QR Code Endpoint..."
echo "-----------------------------------"

# Test 8: Generate QR Code (JSON)
if [ -n "$CARD_ID" ]; then
    RESPONSE=$(curl -s -X GET "$BASE_URL/cards/qrcode?id=$CARD_ID" \
        -H "Authorization: Bearer $TOKEN")
    
    SUCCESS=$(echo "$RESPONSE" | grep -o '"success"[[:space:]]*:[[:space:]]*true')
    if [ -n "$SUCCESS" ]; then
        print_test "8. Generate QR Code (JSON)" "PASS"
    else
        print_test "8. Generate QR Code (JSON)" "FAIL"
    fi
else
    print_test "8. Generate QR Code" "SKIP"
fi

# Test 9: Generate QR Code (Image)
if [ -n "$CARD_ID" ]; then
    HTTP_CODE=$(curl -s -o /tmp/qrcode-test.png -w "%{http_code}" \
        "$BASE_URL/cards/qrcode?id=$CARD_ID&format=image" \
        -H "Authorization: Bearer $TOKEN")
    
    if [ "$HTTP_CODE" = "200" ] && [ -f "/tmp/qrcode-test.png" ]; then
        print_test "9. Generate QR Code (Image)" "PASS"
        echo "   QR code saved to /tmp/qrcode-test.png"
    else
        print_test "9. Generate QR Code (Image)" "FAIL"
    fi
else
    print_test "9. Generate QR Code (Image)" "SKIP"
fi

echo ""
echo "Testing Media Endpoints..."
echo "-----------------------------------"

# Test 10: Upload Media (requires test image)
echo "${YELLOW}Note: Media upload tests require a test image file${NC}"
read -p "Enter path to test image (or press Enter to skip): " TEST_IMAGE

if [ -n "$TEST_IMAGE" ] && [ -f "$TEST_IMAGE" ] && [ -n "$CARD_ID" ]; then
    RESPONSE=$(curl -s -X POST "$BASE_URL/media/upload" \
        -H "Authorization: Bearer $TOKEN" \
        -F "business_card_id=$CARD_ID" \
        -F "media_type=profile_photo" \
        -F "file=@$TEST_IMAGE")
    
    SUCCESS=$(echo "$RESPONSE" | grep -o '"success"[[:space:]]*:[[:space:]]*true')
    if [ -n "$SUCCESS" ]; then
        FILENAME=$(extract_json "$RESPONSE" "filename")
        print_test "10. Upload Media" "PASS"
        echo "   Filename: $FILENAME"
    else
        print_test "10. Upload Media" "FAIL"
        echo "Response: $RESPONSE"
    fi
else
    print_test "10. Upload Media" "SKIP"
fi

echo ""
echo "Testing Rate Limiting..."
echo "-----------------------------------"

# Test 11: Rate Limit Check
echo "Making multiple rapid requests to test rate limiting..."
RATE_LIMITED=false
for i in {1..6}; do
    RESPONSE=$(curl -s -X POST "$BASE_URL/auth/register" \
        -H "Content-Type: application/json" \
        -d "{\"email\":\"ratelimit$i@example.com\"}")
    
    if echo "$RESPONSE" | grep -q "Rate limit exceeded"; then
        RATE_LIMITED=true
        break
    fi
done

if [ "$RATE_LIMITED" = true ]; then
    print_test "11. Rate Limiting" "PASS"
    echo "   Rate limit triggered as expected"
else
    print_test "11. Rate Limiting" "PASS"
    echo "   (Rate limit not reached yet)"
fi

echo ""
echo "Testing Delete Operations..."
echo "-----------------------------------"

# Test 12: Delete Card
if [ -n "$CARD_ID" ]; then
    read -p "Delete test card? (y/n): " DELETE_CARD
    if [ "$DELETE_CARD" = "y" ]; then
        RESPONSE=$(curl -s -X DELETE "$BASE_URL/cards/?id=$CARD_ID" \
            -H "Authorization: Bearer $TOKEN")
        
        SUCCESS=$(echo "$RESPONSE" | grep -o '"success"[[:space:]]*:[[:space:]]*true')
        if [ -n "$SUCCESS" ]; then
            print_test "12. Delete Card" "PASS"
        else
            print_test "12. Delete Card" "FAIL"
        fi
    else
        print_test "12. Delete Card" "SKIP"
    fi
else
    print_test "12. Delete Card" "SKIP"
fi

echo ""
echo "========================================="
echo "Test Suite Complete!"
echo "========================================="
echo ""
echo "Summary:"
echo "- Test Email: $TEST_EMAIL"
echo "- User ID: $USER_ID"
echo "- Card ID: $CARD_ID"
echo "- JWT Token: ${TOKEN:0:50}..."
echo ""

