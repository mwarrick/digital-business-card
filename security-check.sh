#!/bin/bash

# Security check script for ShareMyCard
# This script scans for sensitive information before deployment

set -e

echo "🔍 Running security check before deployment..."

# Patterns to search for (sensitive information)
SENSITIVE_PATTERNS=(
    "GOCSPX-"
    "AIza[0-9A-Za-z_-]{35}"
    "sk-[0-9A-Za-z]{48}"
    "pk_[0-9A-Za-z]{48}"
    "X\$.*\$.*\$.*\$"
    "sharipbf_webapp"
    "sharipbf_sharemycard"
    "69\.57\.162\.186"
    "sharipbf@69\.57\.162\.186"
)

# Files to check
FILES_TO_CHECK=(
    "web/config/*.php"
    "web/api/**/*.php"
    "web/admin/**/*.php"
    "web/user/**/*.php"
    "web/*.php"
)

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

FOUND_ISSUES=0

echo "📁 Checking files for sensitive information..."

for pattern in "${SENSITIVE_PATTERNS[@]}"; do
    for file_pattern in "${FILES_TO_CHECK[@]}"; do
        if ls $file_pattern 1> /dev/null 2>&1; then
            # Use grep to search for patterns, excluding .git and node_modules
            if grep -r -E "$pattern" $file_pattern --exclude-dir=.git --exclude-dir=node_modules 2>/dev/null; then
                echo -e "${RED}❌ SECURITY ISSUE FOUND:${NC} Pattern '$pattern' found in files matching '$file_pattern'"
                FOUND_ISSUES=1
            fi
        fi
    done
done

# Check for specific sensitive files
SENSITIVE_FILES=(
    "web/config/database.php"
    "web/config/gmail.php"
    "web/storage/gmail-token.json"
)

for file in "${SENSITIVE_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${YELLOW}⚠️  WARNING:${NC} Sensitive file '$file' exists and will be deployed"
        FOUND_ISSUES=1
    fi
done

if [ $FOUND_ISSUES -eq 0 ]; then
    echo -e "${GREEN}✅ Security check passed! No sensitive information found.${NC}"
    exit 0
else
    echo -e "${RED}❌ Security check failed! Please fix the issues above before deploying.${NC}"
    exit 1
fi
