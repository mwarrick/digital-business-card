#!/bin/bash

# Secure deployment script for ShareMyCard
# This script prevents sensitive files from being deployed to public_html
#
# ⚠️ DEPLOYMENT RULE: Always run this script after making changes to web/ files
# See DEPLOYMENT-RULE.md for complete guidelines

set -e  # Exit on any error

# Configuration
SERVER="sharipbf@69.57.162.186"
PORT="21098"
REMOTE_PATH="public_html"
LOCAL_PATH="web"

# Files to NEVER deploy (sensitive configuration)
EXCLUDE_FILES=(
    "config/database.php"
    "config/gmail.php"
    "config/database.prod.php"
    "config/production.php"
    "config/staging.php"
    "storage/gmail-token.json"
    "storage/oauth-tokens/*"
    "*.log"
    ".env"
    ".env.*"
)

# Build exclude string for rsync
EXCLUDE_ARGS=""
for file in "${EXCLUDE_FILES[@]}"; do
    EXCLUDE_ARGS="$EXCLUDE_ARGS --exclude=$file"
done

echo "🚀 Deploying ShareMyCard to production..."
echo "📁 Excluding sensitive files: ${EXCLUDE_FILES[*]}"

# Deploy with exclusions
rsync -avz \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='*.log' \
    $EXCLUDE_ARGS \
    "$LOCAL_PATH/" \
    "$SERVER:$REMOTE_PATH/" \
    -e "ssh -p $PORT"

echo "✅ Deployment complete!"
echo "🔒 Sensitive files protected and excluded"

# Verify sensitive files are not in public_html
echo "🔍 Verifying sensitive files are not deployed..."
ssh -p $PORT $SERVER "ls -la $REMOTE_PATH/config/database.php 2>/dev/null || echo '✅ database.php not in public_html'"
ssh -p $PORT $SERVER "ls -la $REMOTE_PATH/config/gmail.php 2>/dev/null || echo '✅ gmail.php not in public_html'"
