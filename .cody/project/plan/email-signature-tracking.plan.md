# Email Signature Tracking Pixel Implementation

## Overview

Implement tracking pixel functionality for email signatures to measure open rates. This will add a new analytics event type "email_open" alongside existing "view", "click", and "download" events.

## Core Components

### 1. Database Schema Updates

**File**: `web/config/migrations/017_add_email_tracking.sql` (new)

- Update `analytics_events` table to add `'email_open'` to the event_type ENUM
- Update aggregation queries to include email open tracking

### 2. Tracking Pixel Endpoint

**File**: `web/api/analytics/pixel.php` (new)

- Create GET endpoint that serves a 1x1 transparent GIF pixel
- Extract card_id from query parameters
- Track "email_open" event using existing Analytics class
- Return pixel image with proper headers (image/gif, cache-control)
- Handle errors gracefully without breaking email rendering

### 3. Email Signature Generator Updates

**Files**:

- `web/user/cards/view.php` (lines ~1224-1322)
- `web/admin/cards/view.php` (lines ~996-1094)

**Changes**:

- Add hidden tracking pixel image to generated HTML signature
- Pixel URL format: `https://sharemycard.app/api/analytics/pixel.php?card_id={CARD_ID}&t={TIMESTAMP}`
- Use 1x1 transparent pixel positioned at bottom of signature
- Add timestamp parameter to prevent email client caching
- Make tracking pixel optional with checkbox in signature generator UI

### 4. Analytics Class Enhancement

**File**: `web/api/includes/Analytics.php`

- Add 'email_open' to valid event types in trackEvent() method
- Ensure email_open events respect DNT and privacy settings
- Update aggregation logic to include email opens

### 5. Stats API Enhancement  

**File**: `web/api/analytics/stats.php`

- Add email_open counting to summary statistics query (line ~45)
- Add email_open to time series data (line ~60)
- Return email open counts in API response

### 6. Dashboard UI Updates

**Files**:

- `web/admin/analytics.php`
- `web/admin/cards/analytics.php` 
- `web/user/cards/analytics.php`

**Add**:

- New stat card showing "Email Opens" with 📧 icon
- Include email opens in daily activity charts
- Add email open rate calculation (opens / sent) - note: "sent" count needs manual input or separate tracking
- Display email opens in time series alongside views/clicks/downloads

### 7. Aggregation Script Update

**File**: `Scripts/aggregate-analytics.php`

- Update daily aggregation to include `total_email_opens` column
- Add email_open to summary calculations

### 8. Migration for Daily Stats Table

**File**: `web/config/migrations/018_add_email_opens_to_daily.sql` (new)

- Add `total_email_opens INT DEFAULT 0` column to `analytics_daily` table

## Implementation Details

### Tracking Pixel URL Structure

```
https://sharemycard.app/api/analytics/pixel.php?card_id={UUID}&t={UNIX_TIMESTAMP}
```

### Email Signature HTML Addition

```html
<!-- Tracking Pixel (at end of signature) -->
<img src="https://sharemycard.app/api/analytics/pixel.php?card_id=xxxxx&t=yyyyy" 
     width="1" height="1" style="display:block;" alt="">
```

### Privacy Considerations

- Respect DNT (Do Not Track) headers
- Honor cookie consent preferences
- Disclose email open tracking in privacy policy
- Provide opt-out mechanism in signature generator
- 30-day data retention consistent with existing analytics

## Testing Plan

1. Generate email signature with tracking pixel
2. Send test email to Gmail, Outlook, Apple Mail
3. Open emails and verify events tracked in dashboard
4. Test pixel with DNT enabled (should not track)
5. Verify pixel works with images disabled (graceful degradation)
6. Check that pixel doesn't break email rendering
7. Validate analytics dashboard shows email open data correctly

## Privacy Policy Update

**File**: `PRIVACY-POLICY.md` and `privacy-policy.html`

Add section on email signature tracking pixels explaining:

- What data is collected (email opens, timestamp, device info)
- Why it's collected (help users understand email signature effectiveness)
- How to opt-out (don't include tracking pixel when generating signature)
- Data retention (30 days detailed, lifetime aggregated)

## To-dos

- [ ] Create database migrations for email_open event type and daily stats column
- [ ] Build pixel.php endpoint that serves 1x1 GIF and tracks events
- [ ] Update email signature generators to include optional tracking pixel
- [ ] Enhance Analytics class to support email_open event type
- [ ] Update stats.php API to return email open metrics
- [ ] Add email opens to all analytics dashboards with charts and stat cards
- [ ] Update daily aggregation script to include email opens
- [ ] Update privacy policy to disclose email tracking pixel usage
- [ ] Test tracking pixel across major email clients and verify dashboard display

