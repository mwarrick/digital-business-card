#!/bin/bash

# Secure deployment script for ShareMyCard
# This script prevents sensitive files from being deployed to public_html
#
# ⚠️ DEPLOYMENT RULE: Always run this script after making changes to web/ files
# See DEPLOYMENT-RULE.md for complete guidelines
#
# Server connection info is read from sharemycard-config/database.php if available,
# otherwise uses default values below.

set -e  # Exit on any error

# Try to read server connection info from sharemycard-config
CONFIG_DIR="sharemycard-config"
if [ -f "$CONFIG_DIR/database.php" ]; then
    # Extract SSH connection info from database.php if it exists (works on both GNU and BSD grep)
    SSH_HOST=$(grep "define('SSH_HOST'" "$CONFIG_DIR/database.php" 2>/dev/null | sed "s/.*'SSH_HOST',[[:space:]]*'\([^']*\)'.*/\1/" || echo "")
    SSH_PORT=$(grep "define('SSH_PORT'" "$CONFIG_DIR/database.php" 2>/dev/null | sed "s/.*'SSH_PORT',[[:space:]]*'\([^']*\)'.*/\1/" || echo "")
    SSH_USER=$(grep "define('SSH_USER'" "$CONFIG_DIR/database.php" 2>/dev/null | sed "s/.*'SSH_USER',[[:space:]]*'\([^']*\)'.*/\1/" || echo "")
    
    # Use config values if found, otherwise use defaults
    SERVER_USER="${SSH_USER:-your_ssh_user}"
    SERVER_HOST="${SSH_HOST:-your.server.ip}"
    PORT="${SSH_PORT:-your_ssh_port}"
else
    # Default values if config file doesn't exist
    # Note: These should be configured in sharemycard-config/.env
    SERVER_USER="your_ssh_user"
    SERVER_HOST="your.server.ip"
    PORT="your_ssh_port"
fi

# Build server connection string
SERVER="${SERVER_USER}@${SERVER_HOST}"
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
