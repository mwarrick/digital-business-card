# Analytics Dashboard - Deployment Instructions

## Prerequisites

Before deploying the analytics feature, ensure you have:
- MySQL database access
- SSH access to production server
- PHP 8.1+ on production server
- Ability to set up CRON jobs

## Deployment Steps

### 1. Database Migration

Run the migration script to create the analytics tables:

```bash
# Local development (using existing connection)
mysql -u your_user -p your_database < web/config/migrations/004_add_analytics_tables.sql

# Production (via SSH tunnel or direct connection)
mysql -h your_host -u your_user -p your_database < web/config/migrations/004_add_analytics_tables.sql
```

**Verify tables were created:**
```sql
SHOW TABLES LIKE 'analytics%';
```

Expected output:
- `analytics_events`
- `analytics_daily`
- `analytics_sessions`

### 2. Upload Files to Production

**New files to upload:**
```
web/api/analytics/track.php
web/api/analytics/stats.php
web/api/includes/Analytics.php
web/admin/cards/analytics.php
web/user/cards/analytics.php
web/includes/cookie-banner.php
web/config/migrations/004_add_analytics_tables.sql
scripts/aggregate-analytics.php
```

**Modified files to upload:**
```
web/card.php
web/vcard.php
web/admin/cards/view.php
web/user/cards/view.php
web/privacy.php
```

**Upload command (rsync example):**
```bash
rsync -avz --progress \
  web/api/analytics/ \
  user@server:/path/to/public_html/api/analytics/

rsync -avz --progress \
  web/api/includes/Analytics.php \
  user@server:/path/to/public_html/api/includes/

rsync -avz --progress \
  web/admin/cards/analytics.php \
  web/user/cards/analytics.php \
  user@server:/path/to/public_html/admin/cards/
  user@server:/path/to/public_html/user/cards/

rsync -avz --progress \
  web/includes/cookie-banner.php \
  user@server:/path/to/public_html/includes/

rsync -avz --progress \
  scripts/aggregate-analytics.php \
  user@server:/path/to/scripts/

# Upload modified files
rsync -avz --progress \
  web/card.php \
  web/vcard.php \
  web/privacy.php \
  user@server:/path/to/public_html/
```

### 3. Set File Permissions

Ensure the aggregation script is executable:
```bash
ssh user@server 'chmod +x /path/to/scripts/aggregate-analytics.php'
```

### 4. Configure CRON Job

Add the daily aggregation job to crontab:

```bash
# Edit crontab
crontab -e

# Add this line (runs daily at 2 AM)
0 2 * * * /usr/bin/php /path/to/scripts/aggregate-analytics.php >> /path/to/logs/analytics-cron.log 2>&1
```

**Test the CRON job manually:**
```bash
/usr/bin/php /path/to/scripts/aggregate-analytics.php
```

Expected output:
```
Analytics Aggregation CRON Job
Started: YYYY-MM-DD HH:MM:SS

Aggregating events for: YYYY-MM-DD
✓ Aggregated X cards

Cleaning old events (older than 30 days)...
✓ Deleted X old events

Cleaning old sessions (older than 30 days)...
✓ Deleted X old sessions

Completed: YYYY-MM-DD HH:MM:SS
Status: SUCCESS
```

### 5. Verify Deployment

**Test the tracking API:**
```bash
curl -X POST https://sharemycard.app/api/analytics/track.php \
  -H "Content-Type: application/json" \
  -d '{"card_id":"test-id","event_type":"view"}'
```

Expected response:
```json
{
  "success": true,
  "message": "Event tracked successfully",
  "data": {
    "tracked": true,
    "event_id": "uuid",
    "session_id": "session-hash"
  }
}
```

**Test the stats API:**
```bash
curl https://sharemycard.app/api/analytics/stats.php?card_id=YOUR_CARD_ID&period=7
```

**Test the dashboard pages:**
- Visit: `https://sharemycard.app/admin/cards/analytics.php`
- Visit: `https://sharemycard.app/user/cards/analytics.php`
- Verify charts load correctly
- Test time period selector
- Test CSV export

**Test public tracking:**
- Visit a public card page: `https://sharemycard.app/card.php?id=YOUR_CARD_ID`
- Check browser console for tracking confirmation
- Click on email/phone/website links
- Download vCard
- Check analytics dashboard for new events

### 6. Verify Privacy Compliance

**Cookie Banner:**
- Visit public card page
- Verify cookie banner appears
- Test "Accept" button (banner should disappear)
- Clear localStorage and test "Decline" button
- Verify tracking respects user choice

**Do Not Track:**
- Enable DNT in browser settings
- Visit public card page
- Verify no tracking events are logged

**Privacy Policy:**
- Visit: `https://sharemycard.app/privacy.php`
- Verify analytics section is present
- Check "Last Updated" date is current

### 7. Monitor Initial Performance

**Check database growth:**
```sql
-- Count events
SELECT COUNT(*) FROM analytics_events;

-- Count sessions
SELECT COUNT(*) FROM analytics_sessions;

-- Check latest events
SELECT * FROM analytics_events ORDER BY created_at DESC LIMIT 10;
```

**Monitor logs:**
```bash
# Check CRON log
tail -f /path/to/logs/analytics-cron.log

# Check PHP error log
tail -f /path/to/logs/error_log
```

## Rollback Plan

If issues occur, rollback steps:

1. **Disable tracking:**
   - Comment out tracking code in `web/card.php`
   - Upload modified file

2. **Hide analytics pages:**
   - Rename analytics.php files temporarily
   - Or add authentication check at top of files

3. **Stop CRON job:**
   ```bash
   crontab -e
   # Comment out the analytics CRON line
   ```

4. **Drop tables (if necessary):**
   ```sql
   DROP TABLE IF EXISTS analytics_events;
   DROP TABLE IF EXISTS analytics_daily;
   DROP TABLE IF EXISTS analytics_sessions;
   ```

## Post-Deployment Testing Checklist

- [ ] Database tables created successfully
- [ ] All files uploaded to production
- [ ] CRON job configured and tested
- [ ] Tracking API responds correctly
- [ ] Stats API returns data
- [ ] Admin analytics page loads
- [ ] User analytics page loads
- [ ] Charts display correctly
- [ ] Time period filter works
- [ ] CSV export works
- [ ] Cookie banner appears
- [ ] Cookie consent is respected
- [ ] DNT header is respected
- [ ] Privacy policy updated
- [ ] Public card tracking works
- [ ] Link click tracking works
- [ ] vCard download tracking works
- [ ] Geolocation data appears
- [ ] Device/browser detection works
- [ ] No PHP errors in logs
- [ ] No JavaScript errors in console

## Troubleshooting

### No events are being tracked
- Check browser console for errors
- Verify API endpoint is accessible
- Check PHP error logs
- Ensure database tables exist
- Test tracking API with curl

### Charts not displaying
- Check browser console for JavaScript errors
- Verify Chart.js CDN is accessible
- Check if stats API returns data
- Clear browser cache

### Geolocation not working
- Verify ip-api.com is accessible from server
- Check if server firewall blocks outbound requests
- Review Analytics.php for timeout issues

### CRON job failing
- Check script permissions (executable)
- Verify PHP path (`which php`)
- Check CRON log for errors
- Test script manually

## Support

For issues:
1. Check `/admin/debug-log.php` (admins only)
2. Review `ANALYTICS-IMPLEMENTATION.md`
3. Contact: mark@sharemycard.app

