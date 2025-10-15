# Gmail API Setup Guide

## 🎯 Overview

The Gmail API integration allows ShareMyCard to send emails for:
- User registration verification
- Login codes (passwordless authentication)
- Password reset (if implemented)
- Welcome emails

You'll need to configure your own Gmail OAuth credentials to use this feature.

## 🚀 Setup Steps

### 1. Configure Google Cloud Console

You need to add the redirect URI to your Google Cloud project:

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Select your project (or create one if needed)
3. Navigate to **APIs & Services** → **Credentials**
4. Find your OAuth 2.0 Client ID (ending in `.apps.googleusercontent.com`)
5. Click **Edit**
6. Under **Authorized redirect URIs**, add:
   ```
   http://localhost:8000/api/auth/gmail-callback.php
   ```
7. Click **Save**

### 2. Enable Gmail API

1. In Google Cloud Console, go to **APIs & Services** → **Library**
2. Search for "Gmail API"
3. Click on it and press **Enable**

### 3. Authorize the Application

1. Visit: `http://localhost:8000/api/auth/gmail-init.php`
2. Click **Authorize with Google**
3. Sign in with your Google account
4. Grant the requested permissions
5. You'll be redirected back with a success message

### 4. Test Email Sending

Once authorized, you can test sending emails:

```bash
# Test registration email
curl -X POST http://localhost:8000/api/auth/register.php \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com"}'
```

The system will automatically send verification emails when:
- Users register
- Users log in (verification code)
- Password reset is requested

## 📧 Email Templates Available

### Registration Verification
- Subject: "Verify your ShareMyCard account"
- Contains: 6-digit verification code
- Expires: 10 minutes

### Login Verification
- Subject: "Your ShareMyCard login code"
- Contains: 6-digit login code
- Expires: 10 minutes

### Password Reset
- Subject: "Reset your ShareMyCard password"
- Contains: 6-digit reset code
- Expires: 30 minutes

### Welcome Email
- Subject: "Welcome to ShareMyCard!"
- Sent after successful verification
- Includes getting started guide

## 🔐 Security Notes

1. **Token Storage**: OAuth tokens are stored in `/storage/gmail-token.json`
2. **Automatic Refresh**: Access tokens are automatically refreshed when expired
3. **Secure Storage**: Make sure `/storage/` directory has proper permissions
4. **Gitignore**: Token file should be in `.gitignore` to prevent accidental commits

## 🔧 Troubleshooting

### "No tokens available" error
- Run the authorization flow: `http://localhost:8000/api/auth/gmail-init.php`

### "Failed to refresh access token" error
- Delete `/storage/gmail-token.json` and re-authorize

### "Redirect URI mismatch" error
- Ensure the redirect URI in Google Cloud Console matches exactly: `http://localhost:8000/api/auth/gmail-callback.php`

### Emails not sending
- Check that Gmail API is enabled in Google Cloud Console
- Verify the token file exists and is not expired
- Check PHP error logs for detailed error messages

## 📝 Production Setup

For production deployment, you'll need to:

1. Update the redirect URI in `config/gmail.php`:
   ```php
   define('GMAIL_REDIRECT_URI', 'https://yourdomain.com/api/auth/gmail-callback.php');
   ```

2. Add the production redirect URI to Google Cloud Console

3. Update the from email address in `config/gmail.php`:
   ```php
   define('GMAIL_FROM_EMAIL', 'noreply@yourdomain.com');
   ```

4. Re-authorize the application with the new redirect URI

## 🎨 Customizing Email Templates

Email templates are in `/api/includes/EmailTemplates.php`. You can:
- Modify the HTML/CSS design
- Change email content
- Add new template types
- Customize colors and branding

## ✅ Integration Complete!

The Gmail API is fully integrated and ready to use. Just complete the authorization flow and start sending emails!

