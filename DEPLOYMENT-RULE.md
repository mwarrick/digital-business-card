# 🚀 DEPLOYMENT RULE

## ⚠️ CRITICAL: All Changes Must Be Deployed to Live Server

**IMPORTANT:** After making any changes to files in the `web/` directory, you **MUST** deploy them to the live server for the changes to take effect.

## Quick Deployment

### For Single File Changes:
```bash
# Deploy a specific file
scp -P 21098 web/router.php sharipbf@69.57.162.186:public_html/router.php

# Or deploy the entire web directory (recommended for multiple changes)
./deploy.sh
```

### For Multiple Changes:
```bash
# Use the deployment script (excludes sensitive files automatically)
./deploy.sh
```

## Deployment Details

- **Server:** sharipbf@69.57.162.186
- **Port:** 21098
- **Remote Path:** public_html
- **Local Path:** web

## What Gets Deployed

The `deploy.sh` script automatically:
- ✅ Deploys all files from `web/` to `public_html/`
- ❌ Excludes sensitive files (database.php, gmail.php, etc.)
- ❌ Excludes .git, node_modules, logs

## When to Deploy

**ALWAYS deploy when you change:**
- API endpoints (`web/api/`)
- Router files (`web/router.php`)
- PHP files (`web/*.php`)
- JavaScript/CSS files (`web/**/*.js`, `web/**/*.css`)
- Configuration templates (`web/config/*.template`)
- Public pages (`web/*.php`)

**DO NOT deploy:**
- Sensitive config files (these are excluded automatically)
- iOS app files (QRCard/ directory)
- Local development files

## Verification

After deployment, verify changes are live:
```bash
# Check if file exists on server
ssh -p 21098 sharipbf@69.57.162.186 "ls -la public_html/router.php"

# Test API endpoint
curl https://sharemycard.app/api/leads/
```

## Emergency Deployment

If you need to quickly fix a critical issue:
```bash
# Single file deployment
scp -P 21098 web/[filename] sharipbf@69.57.162.186:public_html/[filename]

# Full deployment
./deploy.sh
```

---

**Remember:** Local changes don't affect the live site until deployed! 🚀



