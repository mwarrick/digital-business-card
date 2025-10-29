#!/bin/bash

echo "🔍 CHECKING REMOTE DATABASE SCHEMA"
echo "=================================="

echo ""
echo "📡 Connecting to remote server via SSH..."

# Check contacts table schema
ssh -p 21098 sharipbf@69.57.162.186 "
echo '=== CONTACTS TABLE SCHEMA ==='
mysql -u sharipbf_sharemycard -p'your_database_password' sharipbf_sharemycard -e 'DESCRIBE contacts;'

echo ''
echo '=== SAMPLE CONTACT DATA ==='
mysql -u sharipbf_sharemycard -p'your_database_password' sharipbf_sharemycard -e 'SELECT * FROM contacts LIMIT 1;'

echo ''
echo '=== CONTACTS COUNT ==='
mysql -u sharipbf_sharemycard -p'your_database_password' sharipbf_sharemycard -e 'SELECT COUNT(*) as count FROM contacts;'
"

echo ""
echo "✅ Remote database check complete!"
