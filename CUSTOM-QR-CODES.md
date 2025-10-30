# Custom QR Codes (Web)

This document describes the Custom QR Codes feature implemented in the web app.

## What it does
- Allow users to create and manage custom QR codes separate from business cards
- Supported types:
  - default: landing page + optional lead form
  - url: record scan then redirect to URL
  - social: record scan then redirect to constructed social URL (username based)
  - text: record scan and display text
  - wifi: record scan and present Wi‑Fi info
  - appstore: smart redirect to iOS/Android store or show interstitial
- Theming: landing pages can be themed and show cover image, title, custom HTML
- Analytics: per‑QR and global analytics with charts and recent events
- Admin visibility: admins can view all QR codes and global analytics

## Key files
- Public handler: `web/public/qr.php`
  - Routes `/qr/{uuid}` via `.htaccess`/`router.php`
  - Tracks events, applies rate limiting, sanitization, and renders templates
  - Now shows a branded inactive page for deactivated QR codes: `web/public/includes/qr/inactive.php`
- Templates:
  - `web/public/includes/qr/landing.php` (shared landing)
  - `web/public/includes/qr/text-landing.php`
  - `web/public/includes/qr/wifi-landing.php`
  - `web/public/includes/qr/appstore-interstitial.php`
- User UI:
  - List: `web/user/qr/index.php`
  - Create: `web/user/qr/create.php`
  - Edit: `web/user/qr/edit.php`
  - Analytics (per QR): `web/user/qr/analytics.php`
  - Global analytics: `web/user/qr/global-analytics.php`
- Admin UI:
  - Index: `web/admin/qr/index.php`
  - Global analytics: `web/admin/qr/global-analytics.php`
  - Utilities: `web/admin/qr/truncate-custom-qr-events.php`
- Helpers:
  - Sanitization: `web/api/includes/Sanitize.php`
  - Rate limiting: `web/api/includes/RateLimiter.php`
  - QR generator (external API for now): `web/api/includes/qr/Generator.php`
  - Themes: `web/includes/themes.php`

## Database
- Tables created by migrations:
  - `custom_qr_codes` – main QR records (UUID id, user_id, type, payload_json, theme, landing fields)
  - `custom_qr_events` – analytics events (view/redirect/lead_submit)
    - Columns include: ip_address, user_agent, referrer, device_type, browser, os, city, country, created_at
  - `qr_leads` – optional mapping to existing `leads`
- Relevant migrations:
  - `030_create_custom_qr_codes.sql`
  - `032_alter_custom_qr_user_id.sql` – user_id -> VARCHAR(36)
  - `033_add_analytics_columns.sql` – device_type, browser, location_type
  - `034_add_os_city_country.sql` – os, city, country

## Routing
- `.htaccess` in web root: `RewriteRule ^qr/.*$ /qr.php [NC,L,QSA]`
- `web/router.php` also routes `/qr/{uuid}` when front controller is used

## Analytics
- Stored in `custom_qr_events`
- Per‑QR page (`user/qr/analytics.php`) shows:
  - QR scans, unique visitors, redirects, leads
  - Views over time (backfilled dates), device types, browsers, locations, recent events
- Global page (`user/qr/global-analytics.php`) aggregates across all user QR codes
- Admin global analytics across all users: `admin/qr/global-analytics.php`

## Rate limiting
- File‑based limiter in `web/api/includes/RateLimiter.php`
- Default: 100 views/minute per IP for public scans; lead capture is separately limited
- Supports IP whitelist

## Security & Sanitization
- URLs validated: only http/https
- Usernames and HTML sanitized; landing HTML allow‑list with event/script stripping

## Deletion
- `web/user/qr/delete.php` deletes analytics (`custom_qr_events`, `qr_leads`) before removing the QR

## Smoke tests
- `web/tests/smoke/qr_smoke.php` verifies core routes and expected statuses

## Deployment notes
- Deploy to web root (`/home/<user>/public_html/`), not `/public_html/web/`
- Ensure `.htaccess` contains the `^qr/` rule
- After pulling new migrations, apply in order or run provided admin scripts to execute them using the app DB connection

## Quick start (dev)
1) Create a QR at `/user/qr/create.php`
2) Scan or open `/qr/{uuid}`
3) View analytics at `/user/qr/analytics.php?id={uuid}` or global `/user/qr/global-analytics.php`
