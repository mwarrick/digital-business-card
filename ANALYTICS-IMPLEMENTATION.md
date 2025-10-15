# Analytics Dashboard Implementation

## Overview

ShareMyCard now features a comprehensive analytics system that tracks card engagement, providing valuable insights into how your business cards are being used and shared.

## Key Features

### 📊 Analytics Tracking
- **View Tracking**: Counts total and unique views of public card pages
- **Click Tracking**: Monitors which contact links (email, phone, website) are clicked
- **Download Tracking**: Records when visitors download your vCard
- **Session Tracking**: Uses cookies to identify unique visitors (30-day sessions)

### 🌍 Data Collection
- **Geographic Insights**: IP-based location (country, city via ip-api.com)
- **Device Analytics**: Browser type, device type (mobile/tablet/desktop), operating system
- **Referrer Data**: Source of traffic to your card
- **User Agent Parsing**: Automatic detection of browser/OS/device type

### 📈 Dashboard Features
- **Interactive Charts**: Line charts for views over time, pie charts for devices, bar charts for link clicks
- **Time Periods**: View data for last 7, 30, 90 days, or all time
- **Summary Cards**: Quick overview of total views, unique visitors, clicks, downloads
- **Geographic Distribution**: Tables showing top countries and cities
- **CSV Export**: Download analytics data for external analysis
- **Real-time Updates**: Charts update based on selected time period

## Database Schema

### Tables Created

**1. `analytics_events`** - Detailed event tracking (30 days)
- Stores individual view, click, and download events
- Includes IP address, user agent, device info, geolocation
- Automatically deleted after 30 days

**2. `analytics_daily`** - Aggregated statistics
- Daily summaries for long-term historical data
- Includes total views, unique views, clicks, downloads
- Top referrer and country for each day

**3. `analytics_sessions`** - Unique visitor tracking
- Session ID stored in cookie (30-day expiry)
- Tracks first and last seen timestamps
- Used to calculate unique visitor counts

## API Endpoints

### POST `/api/analytics/track.php`
Logs analytics events
```json
{
  "card_id": "uuid",
  "event_type": "view|click|download",
  "event_target": "optional_url_or_vcard"
}
```

### GET `/api/analytics/stats.php`
Retrieves analytics data
```
?card_id=uuid&period=7|30|90|all
```

Returns:
- Summary statistics
- Time series data
- Top links clicked
- Geographic breakdown
- Device/browser/OS distribution
- Referrer sources

## Privacy & Compliance

### Cookie Consent
- Banner displayed on public card pages
- Stores preference in localStorage
- Respects user choice (accept/decline)
- Implied consent if no choice made

### Do Not Track (DNT)
- Respects browser DNT header
- No tracking if DNT=1

### Data Retention
- Detailed events: 30 days
- Aggregated data: Lifetime of account
- Both deleted when account is deleted

### Privacy Policy
- Updated with analytics tracking disclosure
- Explains what data is collected and why
- Details retention policies
- Provides opt-out options

## Maintenance

### CRON Job
**Location**: `/scripts/aggregate-analytics.php`

**Schedule**: Daily at 2 AM
```bash
0 2 * * * /usr/bin/php /path/to/scripts/aggregate-analytics.php
```

**Tasks**:
1. Aggregate events from 2 days ago into `analytics_daily`
2. Delete events older than 30 days
3. Clean up expired sessions

## User Access

### For Admins
- Access via: `/admin/cards/analytics.php`
- View analytics for any of their cards
- Full dashboard with all features

### For Users
- Access via: `/user/cards/analytics.php`
- View analytics for their own cards only
- Identical features to admin view

### Quick Access
- "📊 View Analytics" button on card view pages
- Card selector dropdown for multi-card users
- Time period filter for date ranges

## Implementation Details

### Analytics Class (`web/api/includes/Analytics.php`)
**Methods**:
- `trackEvent()` - Main tracking method
- `parseUserAgent()` - Extract device/browser/OS
- `getGeolocation()` - IP-based location lookup
- `getOrCreateSession()` - Session management
- `aggregateDailyStats()` - CRON aggregation
- `cleanOldEvents()` - Data cleanup

### Chart.js Integration
- Line chart: Views over time (total + unique)
- Pie chart: Device type distribution
- Bar chart: Top links clicked (top 5)
- Doughnut charts: Browser and OS breakdown
- Responsive design for mobile viewing

### Tracking Integration Points

**1. Public Card (`web/card.php`)**
- Tracks page view on load
- Tracks all link clicks (mailto, tel, http)
- Respects cookie consent
- Checks DNT header

**2. vCard Download (`web/vcard.php`)**
- Tracks download event before serving file
- Silent fail if tracking errors (doesn't interrupt download)

**3. Card View Pages**
- Analytics link added to action buttons
- Links to analytics dashboard with card pre-selected

## Testing Checklist

- [ ] Create test card with public link
- [ ] Visit from different devices/browsers
- [ ] Click various links (email, phone, website)
- [ ] Download vCard
- [ ] Verify events appear in analytics dashboard
- [ ] Test time period filters
- [ ] Export CSV and verify data
- [ ] Test cookie consent (accept/decline)
- [ ] Verify DNT header is respected
- [ ] Run aggregation script manually
- [ ] Check old events are deleted after 30 days

## Future Enhancements

Potential additions:
- [ ] Real-time analytics (WebSockets)
- [ ] Email reports (weekly/monthly summaries)
- [ ] Comparison between cards
- [ ] A/B testing for different card versions
- [ ] Social media share tracking
- [ ] QR code scan tracking (if feasible)
- [ ] Conversion funnels (view → click → download)
- [ ] Heatmaps for link popularity

## Performance Considerations

- Analytics tracking is asynchronous (doesn't block page load)
- IP geolocation has 2-second timeout (won't slow down tracking)
- Database indexes on frequently queried fields
- Aggregation reduces long-term storage needs
- CORS enabled for cross-origin tracking

## Support

For issues or questions:
- Check privacy policy: `https://sharemycard.app/privacy.php`
- View debug logs: `/admin/debug-log.php` (admins only)
- Contact: mark@sharemycard.app

