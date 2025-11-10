# Android App Development Rules

**IMPORTANT: These rules must be followed at all times during Android app development.**

## 1. API Keys & Secrets
- **NEVER** publish API keys (e.g., Gmail API keys) to GitHub
- **NEVER** commit any secrets, tokens, or sensitive credentials to version control
- Use environment variables, local.properties, or secure configuration files that are gitignored

## 2. Configuration Files
- **NEVER** store config files in locations that could be synced with GitHub
- All configuration files must be in `.gitignore`
- Use local configuration files that are not tracked by Git
- **NEVER** store database access info, SSH access info, API keys, passwords, or any credentials in any file that may be synced to GitHub
- All sensitive information must be in gitignored files or environment variables

## 3. Server Access
- **ALWAYS** use information stored in `U:\Github Projects\digital-business-card\sharemycard-config` to connect via SSH to the server
- Server access information is in:
  - `sharemycard-config/database.php` - Database connection details and SSH tunnel config
  - `sharemycard-config/env.production` - Production environment variables
  - `sharemycard-config/gmail.php` - Email service configuration (if needed)
- Use this for database access and website file access when needed
- **NEVER** hardcode server credentials, database passwords, SSH keys, or connection strings in any Android app files
- **NEVER** copy database/SSH credentials into the Android App directory
- Read credentials from `sharemycard-config/` when needed, but never store them in Android app code
- SSH connection details:
  - Host: See `database.php` SSH_HOST
  - Port: See `database.php` SSH_PORT  
  - User: See `database.php` SSH_USER

## 4. Independence from iOS/Web
- **DO NOT** alter ANY files used by the iOS app or the website
- The Android app must have completely independent:
  - API access
  - Tokens
  - Configuration
  - File structure
  - Dependencies
- Android app code should be completely isolated in the `Android App/` directory

## 5. Database Changes
- **DO NOT** modify the database directly
- If database changes are needed, provide a `.SQL` file to run
- Never execute SQL directly - always provide scripts for review and execution

## 6. Database Structure Verification
- **ALWAYS** check the structure of the database before creating fields
- Ensure any fields created match the source table field types
- Verify data types, constraints, and relationships match existing schema

---

**This file should be referenced before making any changes that involve:**
- API configuration
- Database access
- Server connections
- File modifications outside the Android App directory
- Any sensitive data handling
- Storing any credentials, passwords, keys, or connection strings

**CRITICAL REMINDER:**
- Database passwords, SSH credentials, API keys, and connection strings must NEVER appear in any file in the Android App directory that could be committed to Git
- Always use gitignored configuration files or environment variables
- Reference `sharemycard-config/` for server info, but never copy it into Android app files

