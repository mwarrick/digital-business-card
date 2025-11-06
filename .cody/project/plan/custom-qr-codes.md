# Custom QR Codes (Web-Only)

## Scope

- Web dashboard only: new “Custom QR Codes” section under `web/user/` (not in iOS app).
- Public scan URL: `/qr/{id}` (UUID). Handled via `web/router.php` → `web/public/qr.php`.
- Default behavior: render a landing page and optional lead form (same backend leads). Users can disable the lead form per QR code.
- Optional types override default behavior: Custom URL, Social profiles, Custom text, Wi‑Fi info, App Store links (smart redirect).

## Data model

- New table `custom_qr_codes`:
- `id` (UUID PK), `user_id` (FK), `type` ENUM('default','url','social','text','wifi','appstore'),
- `payload_json` JSON (URL, usernames, text, Wi‑Fi ssid/auth, app links),
- `title` VARCHAR(120), `slug` VARCHAR(160) NULL UNIQUE (future vanity),
- `theme_key` VARCHAR(64) NULL (reuse business card themes),
- `cover_image_url` VARCHAR(512) NULL,
- `landing_title` VARCHAR(160) NULL,
- `landing_html` MEDIUMTEXT NULL (sanitized HTML snippet shown above lead link),
- `show_lead_form` TINYINT(1) NOT NULL DEFAULT 1,
- `expires_at` DATETIME NULL (date and time when QR code expires; NULL = no expiration),
- `expiration_notice` VARCHAR(500) DEFAULT 'Sorry, this QR code has expired.' (customizable message shown when expired),
- `status` ENUM('active','inactive'), `created_at`, `updated_at`.
- Events (option A: reuse existing analytics): use `api/analytics/track.php` with `entity_type=qr` and `entity_id`.
- Option B (if needed): `custom_qr_events(qr_id,event,ua,ip,referrer,created_at)`.
- Leads: reuse existing leads tables; add mapping `qr_leads(qr_id, lead_id)` or add nullable `qr_id` to leads.

## Routing and controllers

- `web/router.php`: dispatch `/qr/{id}` → `web/public/qr.php`.
- `web/public/qr.php` flow:
1) Fetch by `id` and `status`.
2) **Check expiration**: If `expires_at` is set and current time (EST) >= `expires_at`, mark as expired.
3) **Record `qr_view` analytics** (always record, even if expired).
4) **If expired**: 
   - Display expiration notice (`expiration_notice` field or default message).
   - If `show_lead_form=1`, display button/link to access lead form (separate from expired notice).
   - Do NOT execute type-specific functionality (no redirect, no content display, etc.).
   - Stop processing here.
5) **If not expired**, switch by `type`:
- `default`: render landing template using customization (cover image, `landing_title`, `landing_html`), show lead form link/button if `show_lead_form=1`.
- `url`: record and 302 redirect to `payload.url` (validated).
- `social`: build URL from platform + username; record and redirect.
- `text`: render landing with custom text block (still supports cover/title/html and optional lead form link).
- `wifi`: render landing with SSID/security/password and copy buttons.
- `appstore`: UA detect; redirect to Apple/Google; fallback interstitial with both links.

## Views

- New templates in `web/public/includes/qr/`:
- `landing.php` (shared container styles matching `privacy.php`), accepts cover image, title, HTML, and optional lead form CTA.
- `expired.php` (expiration notice template) - displays `expiration_notice` message and optional lead form button if `show_lead_form=1`.
- `text.php`, `wifi.php`, `interstitial-appstore.php` (special sections embedded in landing when applicable).
- Lead form include reused from cards; hide entirely when `show_lead_form=0`.

## Dashboard (user)

- `web/user/qr/`: `index.php` (list + basic stats), `create.php`, `edit.php`, `view.php`, `analytics.php`.
- Create/Edit forms:
- Step 1: choose type.
- Step 2: type-specific fields and common customization (cover upload/URL, landing title, landing HTML, show/hide lead form).
- **Expiration settings**:
  - Date/time picker for `expires_at` (default: blank/null = no expiration).
  - **Clear EST timezone indicator**: Display "All dates and times are in Eastern Time (EST)" prominently near date/time picker.
  - Text input for `expiration_notice` (default: "Sorry, this QR code has expired.").
  - Preview/example of how expiration notice will appear.
- Preview QR and landing; on save show share URL and PNG/SVG download.
- **List view (`index.php`)**: Show expiration status (expired, expires soon, no expiration) with visual indicators.

## QR generation

- Extract QR generator to `web/api/includes/qr/Generator.php`; reuse cards code; support PNG/SVG, size, error correction.

## Analytics

- Track events via `api/analytics/track.php` with `entity_type=qr`.
- Events: `qr_view`, `qr_redirect`, `qr_lead`.
- User dashboard shows counts and mini charts per QR with date filters.
- Admin: global analytics across ALL QR codes (aggregate + per-QR) with filters (date range, type, owner) and drill-down.

## Security and privacy

- Public pages unauthenticated by design.
- Sanitize `landing_html` allowlist (basic tags/attributes) to prevent XSS.
- Validate URLs; allow only http/https; block javascript: and data: except for images where safe.
- Rate limit lead submissions (reuse existing).

## Migrations

- `web/config/migrations/YYYYMMDD_add_custom_qr_tables.php` for `custom_qr_codes` (+ optional `qr_leads`).
- `web/config/migrations/YYYYMMDD_add_expiration_to_custom_qr_codes.php` for `expires_at` and `expiration_notice` fields.

## Admin

- `web/admin/qr/`: list all QR codes with owner, status, type; toggle status; search and paginate.
- Admin analytics page with global and per-QR charts; link from list.

## URLs and examples

- Public: `/qr/550e8400-e29b-41d4-a716-446655440001`
- Dashboard: `/user/qr/`

## Reuse/Change Highlights

- Reuse: lead form, analytics tracker, CSS container styles, auth middleware.
- New: table, router path, user CRUD pages, public handler, QR helper, sanitization.

## Expiration Functionality ✨ NEW!

### Overview
Custom QR codes can have optional expiration dates with customizable expiration notices. Expired QR codes stop functioning but still record analytics.

### Key Features
- **Expiration Date/Time**: Optional `expires_at` field (DATETIME, NULL = no expiration).
- **Timezone**: All dates/times are in Eastern Time (EST) - must be clearly communicated to users.
- **Custom Expiration Notice**: User-configurable message (default: "Sorry, this QR code has expired.").
- **Analytics**: Scans are always recorded, even for expired QR codes.
- **Functionality**: Expired QR codes stop all type-specific functionality (no redirects, no content display).
- **Lead Form Access**: If `show_lead_form=1`, expired QR codes still show button/link to access lead form.

### Behavior
1. **Expiration Check**: On scan, check if `expires_at` is set and current time (EST) >= `expires_at`.
2. **If Expired**:
   - Display `expiration_notice` message.
   - If `show_lead_form=1`, display lead form button/link.
   - Do NOT execute type-specific functionality (no redirect, no content).
   - Still record `qr_view` analytics event.
3. **If Not Expired**: Normal functionality proceeds.

### UI Requirements
- Date/time picker with clear EST timezone indicator.
- Default expiration notice text field with preview.
- List view shows expiration status (expired, expires soon, no expiration).
- Visual indicators for expired/expiring QR codes.

## Future enhancements

- Gate content behind mandatory lead capture (require form submission before showing destination or sensitive content like Wi‑Fi credentials).
- Vanity slugs and custom domains for QR.
- Bulk import/export.
- Expiration warnings (email notifications before expiration).
- Automatic status change to 'inactive' when expired.