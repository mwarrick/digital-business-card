#!/bin/bash

echo "🔍 CHECKING REMOTE DATABASE SCHEMA"
echo "=================================="

echo ""
echo "📡 Connecting to remote server via SSH..."

# Load server connection details from sharemycard-config/.env
# Note: This script requires SSH_HOST, SSH_PORT, SSH_USER, DB_NAME, and DB_USER to be set
# Source the .env file or set these variables before running this script
SSH_HOST="${SSH_HOST:-your.server.ip}"
SSH_PORT="${SSH_PORT:-your_ssh_port}"
SSH_USER="${SSH_USER:-your_ssh_user}"
DB_NAME="${DB_NAME:-your_database_name}"
DB_USER="${DB_USER:-your_database_user}"

# Check contacts table schema
ssh -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" "
echo '=== CONTACTS TABLE SCHEMA ==='
mysql -u $DB_USER -p'your_database_password' $DB_NAME -e 'DESCRIBE contacts;'

echo ''
echo '=== SAMPLE CONTACT DATA ==='
mysql -u $DB_USER -p'your_database_password' $DB_NAME -e 'SELECT * FROM contacts LIMIT 1;'

echo ''
echo '=== CONTACTS COUNT ==='
mysql -u $DB_USER -p'your_database_password' $DB_NAME -e 'SELECT COUNT(*) as count FROM contacts;'
"

echo ""
echo "✅ Remote database check complete!"
