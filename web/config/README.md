# Configuration Files

## 🔐 Security Notice

The actual configuration files (`gmail.php` and `database.php`) are **NOT** tracked by git for security reasons. They contain sensitive credentials that should never be committed to a repository.

## 📋 Setup Instructions

### First Time Setup

1. **Copy the template files:**
   ```bash
   cp gmail.php.template gmail.php
   cp database.php.template database.php
   ```

2. **Edit `gmail.php`** with your Gmail API credentials:
   - Get credentials from [Google Cloud Console](https://console.cloud.google.com/)
   - Fill in `GMAIL_CLIENT_ID`
   - Fill in `GMAIL_CLIENT_SECRET`
   - Update `GMAIL_FROM_EMAIL` with your domain

3. **Edit `database.php`** with your database credentials:
   - Fill in `DB_NAME` (your database name)
   - Fill in `DB_USER` (your database username)
   - Fill in `DB_PASS` (your database password)
   - Fill in `SSH_HOST`, `SSH_PORT`, `SSH_USER` for SSH tunnel
   - Update `SSH_KEY` path to your SSH private key

### File Overview

| File | Purpose | Tracked in Git? |
|------|---------|----------------|
| `gmail.php.template` | Template for Gmail API config | ✅ Yes |
| `gmail.php` | **Your actual Gmail credentials** | ❌ No (sensitive) |
| `database.php.template` | Template for database config | ✅ Yes |
| `database.php` | **Your actual database credentials** | ❌ No (sensitive) |
| `schema.sql` | Database schema | ✅ Yes |

## ⚠️ Important

- **NEVER** commit `gmail.php` or `database.php` to git
- **NEVER** share these files publicly
- **ALWAYS** use the `.template` files as reference
- The `.gitignore` is configured to exclude these files

## 🔄 After Setup

Once you've created your config files:

1. Test the database connection: `http://localhost:8000/test-db.php`
2. Authorize Gmail API: `http://localhost:8000/api/auth/gmail-init.php`
3. Test email sending: `http://localhost:8000/api/test/send-test-email.php?to=your@email.com`

## 🆘 Troubleshooting

If you see errors like "Could not find config file":
- Make sure you copied the `.template` files to remove the `.template` extension
- Check file permissions (should be readable by PHP)
- Verify you're in the correct directory

If you accidentally committed credentials:
- Change your passwords/secrets immediately
- Rotate your Gmail API credentials
- Consider using environment variables for production

