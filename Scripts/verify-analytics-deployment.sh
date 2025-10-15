#!/bin/bash

# Analytics Deployment Verification Script
# Run this on your production server via SSH

echo "========================================"
echo "Analytics Deployment Verification"
echo "========================================"
echo ""

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Base directory (adjust if needed)
BASE_DIR="$HOME/public_html"

echo "Checking base directory: $BASE_DIR"
echo ""

# Counter
MISSING=0
FOUND=0

# Function to check file
check_file() {
    if [ -f "$1" ]; then
        echo -e "${GREEN}✓${NC} Found: $1"
        ((FOUND++))
    else
        echo -e "${RED}✗${NC} Missing: $1"
        ((MISSING++))
    fi
}

# Function to check directory
check_dir() {
    if [ -d "$1" ]; then
        echo -e "${GREEN}✓${NC} Directory exists: $1"
    else
        echo -e "${RED}✗${NC} Directory missing: $1"
        ((MISSING++))
    fi
}

echo "=== API Files ==="
check_dir "$BASE_DIR/api/analytics"
check_file "$BASE_DIR/api/analytics/track.php"
check_file "$BASE_DIR/api/analytics/stats.php"
check_file "$BASE_DIR/api/includes/Analytics.php"
echo ""

echo "=== Admin Analytics ==="
check_file "$BASE_DIR/admin/cards/analytics.php"
echo ""

echo "=== User Analytics ==="
check_file "$BASE_DIR/user/cards/analytics.php"
echo ""

echo "=== Modified Files (tracking integration) ==="
check_file "$BASE_DIR/card.php"
check_file "$BASE_DIR/vcard.php"
check_file "$BASE_DIR/admin/cards/view.php"
check_file "$BASE_DIR/user/cards/view.php"
check_file "$BASE_DIR/privacy.php"
echo ""

echo "=== Shared Components ==="
check_file "$BASE_DIR/includes/cookie-banner.php"
echo ""

echo "=== CRON Job Script ==="
check_file "$HOME/scripts/aggregate-analytics.php"
if [ -f "$HOME/scripts/aggregate-analytics.php" ]; then
    if [ -x "$HOME/scripts/aggregate-analytics.php" ]; then
        echo -e "${GREEN}✓${NC} Script is executable"
    else
        echo -e "${YELLOW}⚠${NC} Script exists but is not executable"
        echo "  Run: chmod +x $HOME/scripts/aggregate-analytics.php"
    fi
fi
echo ""

echo "=== Database Tables ==="
echo "Checking if analytics tables exist..."

# Try to check database tables (requires MySQL credentials)
if command -v mysql &> /dev/null; then
    echo "Enter your MySQL password when prompted:"
    TABLES=$(mysql -u sharipbf -p sharipbf_sharemycard -e "SHOW TABLES LIKE 'analytics%';" 2>/dev/null | grep analytics)
    
    if echo "$TABLES" | grep -q "analytics_events"; then
        echo -e "${GREEN}✓${NC} Table: analytics_events"
    else
        echo -e "${RED}✗${NC} Missing: analytics_events"
        ((MISSING++))
    fi
    
    if echo "$TABLES" | grep -q "analytics_daily"; then
        echo -e "${GREEN}✓${NC} Table: analytics_daily"
    else
        echo -e "${RED}✗${NC} Missing: analytics_daily"
        ((MISSING++))
    fi
    
    if echo "$TABLES" | grep -q "analytics_sessions"; then
        echo -e "${GREEN}✓${NC} Table: analytics_sessions"
    else
        echo -e "${RED}✗${NC} Missing: analytics_sessions"
        ((MISSING++))
    fi
else
    echo -e "${YELLOW}⚠${NC} MySQL client not found, skipping database check"
    echo "  Manually verify tables exist: analytics_events, analytics_daily, analytics_sessions"
fi
echo ""

echo "=== CRON Job Check ==="
echo "Checking if CRON job is configured..."
if crontab -l 2>/dev/null | grep -q "aggregate-analytics.php"; then
    echo -e "${GREEN}✓${NC} CRON job is configured"
    echo "  Current entry:"
    crontab -l 2>/dev/null | grep "aggregate-analytics.php"
else
    echo -e "${RED}✗${NC} CRON job not found"
    echo "  Add this to crontab (run: crontab -e):"
    echo "  0 2 * * * /usr/bin/php $HOME/scripts/aggregate-analytics.php >> $HOME/logs/analytics-cron.log 2>&1"
    ((MISSING++))
fi
echo ""

echo "========================================"
echo "Summary"
echo "========================================"
echo -e "${GREEN}Found: $FOUND files${NC}"
if [ $MISSING -gt 0 ]; then
    echo -e "${RED}Missing: $MISSING items${NC}"
    echo ""
    echo "⚠️  Deployment incomplete. Please upload missing files."
    exit 1
else
    echo -e "${GREEN}✓ All files deployed successfully!${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Test tracking: curl -X POST https://sharemycard.app/api/analytics/track.php \\"
    echo "   -H 'Content-Type: application/json' \\"
    echo "   -d '{\"card_id\":\"test\",\"event_type\":\"view\"}'"
    echo ""
    echo "2. Visit: https://sharemycard.app/admin/cards/analytics.php"
    echo ""
    echo "3. Test CRON job: /usr/bin/php $HOME/scripts/aggregate-analytics.php"
    exit 0
fi

