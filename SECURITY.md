# Security Guide for ShareMyCard

## 🚨 CRITICAL: Repository Security

This repository has been cleaned of sensitive information. **DO NOT** commit the following files:

### Files to NEVER Commit:
- `web/config/database.php` - Contains database credentials
- `web/config/gmail.php` - Contains Gmail API credentials
- `.env` - Contains all environment variables
- `web/storage/gmail-token.json` - Contains OAuth tokens
- Any files with passwords, API keys, or secrets

### Files Safe to Commit:
- `web/config/database.php.template` - Template for database config
- `web/config/gmail.php.template` - Template for Gmail config
- `web/config/env.example` - Example environment file
- `web/config/Config.php` - Secure configuration manager

## 🔧 Setup Instructions

### 1. Create Configuration Files

```bash
# Copy templates to actual config files
cp web/config/database.php.template web/config/database.php
cp web/config/gmail.php.template web/config/gmail.php
cp web/config/env.example .env

# Edit the files with your actual credentials
nano web/config/database.php
nano web/config/gmail.php
nano .env
```

### 2. Set File Permissions

```bash
# Make config files readable only by owner
chmod 600 web/config/database.php
chmod 600 web/config/gmail.php
chmod 600 .env

# Ensure storage directory is writable
chmod 755 web/storage/
chmod 755 web/storage/media/
```

### 3. Update .gitignore

The `.gitignore` file has been created to prevent sensitive files from being committed. Verify it includes:

```
web/config/database.php
web/config/gmail.php
.env
web/storage/gmail-token.json
```

## 🔐 Gmail API Security

### If Your Credentials Were Exposed:

1. **Immediately revoke** the exposed credentials in Google Cloud Console
2. **Generate new** OAuth 2.0 credentials
3. **Update** your `gmail.php` file with new credentials
4. **Test** email functionality

### Google Cloud Console Steps:
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Select your project
3. Go to "APIs & Services" > "Credentials"
4. Find your OAuth 2.0 client ID
5. Click "Delete" to revoke old credentials
6. Click "Create Credentials" > "OAuth 2.0 Client ID"
7. Add authorized redirect URIs:
   - `https://sharemycard.app/api/auth/gmail-callback.php` (production)
   - `http://localhost:8000/api/auth/gmail-callback.php` (development)

## 🛡️ Server Security

### If Your Server IP Was Exposed:

1. **Change SSH port** from default 22 to a non-standard port
2. **Disable root login** and use key-based authentication only
3. **Set up fail2ban** to prevent brute force attacks
4. **Update firewall** rules to restrict access
5. **Monitor logs** for suspicious activity

### Recommended Server Hardening:

```bash
# Change SSH port
sudo nano /etc/ssh/sshd_config
# Change: Port 22 to Port 21098 (or another port)

# Disable root login
# Change: PermitRootLogin yes to PermitRootLogin no

# Restart SSH
sudo systemctl restart sshd

# Install fail2ban
sudo apt install fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

## 🔒 Database Security

### Best Practices:
1. **Use strong passwords** (minimum 16 characters)
2. **Limit database user permissions** (only what's needed)
3. **Use SSL connections** for remote database access
4. **Regular backups** with encryption
5. **Monitor database logs** for suspicious activity

## 📋 Security Checklist

- [ ] Sensitive files removed from Git history
- [ ] Configuration templates created
- [ ] .gitignore file updated
- [ ] Gmail API credentials revoked and regenerated
- [ ] Database credentials updated
- [ ] SSH server hardened
- [ ] File permissions set correctly
- [ ] Environment variables configured
- [ ] Security monitoring enabled

## 🚨 Emergency Response

If you suspect a security breach:

1. **Immediately change** all passwords and API keys
2. **Revoke** all OAuth tokens
3. **Check server logs** for unauthorized access
4. **Update** all credentials
5. **Monitor** for suspicious activity
6. **Consider** temporarily taking the site offline

## 📞 Support

For security questions or incidents:
- Check server logs: `/var/log/auth.log`, `/var/log/apache2/error.log`
- Monitor database access logs
- Review Gmail API usage in Google Cloud Console
- Check for failed login attempts

Remember: **Security is an ongoing process, not a one-time setup!**