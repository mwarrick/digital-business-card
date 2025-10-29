## iOS → Web SSO Plan (App-initiated One-Click Login)

### Goal
Enable a signed-in iOS user to tap a link that opens the website already authenticated (no password/code step), landing on a target page like the contacts dashboard.

### High-Level Flow
1) iOS app requests a short-lived one-time login token from the API.
2) App opens a special website endpoint with the token in the URL (HTTPS only).
3) Website validates the token, creates a PHP session, sets secure cookies, and redirects to the requested page.
4) The token becomes immediately invalid (single-use), preventing replay.

### Token Design
- **Type**: One-Time Login Token (OTLT)
- **Claims**: `user_id`, `issued_at`, `expires_at` (<= 60 seconds), `nonce`
- **Format**: JWT signed with existing API secret or HMAC payload; consider an opaque random token stored server-side if preferred.
- **Storage**: If JWT, no DB storage required but keep a replay cache (e.g., Redis) keyed by token `jti`/`nonce` until expiration. If opaque, store in DB/Redis with TTL and used flag.
- **Scope**: Authentication only; no other permissions encoded.

### Endpoints
- API (authenticated by app’s Bearer token):
  - `POST /api/auth/create-sso-token` → `{ token, expires_at }`
  - Validates the app’s JWT, generates OTLT, caches for single-use, returns to app.

- Website (public, HTTPS only):
  - `GET /user/login-with-token.php?token=...&redirect=/user/contacts/index.php`
  - Validates OTLT (`signature`, `exp`, `user_id`, `nonce`, replay check)
  - Creates PHP session for `user_id`, sets secure, HTTP-only cookie
  - Marks token as used (replay prevention)
  - Redirects to `redirect` if it’s an allow-listed relative path

### Security Controls
- HTTPS required end-to-end
- Short TTL (≤ 60s) and single-use
- Replay protection (mark used; deny second use)
- Allow-list `redirect` paths (deny external or absolute URLs)
- Sign with existing API secret; rotate keys as per current policy
- Log token generation and consumption events with IP/UA for audit
- Rate-limit token creation per user/device (burst + hourly cap)

### iOS App Changes
- Add `AuthService.createSSOToken()`:
  - `POST /api/auth/create-sso-token` with current Bearer JWT
  - Receive `{ token, expires_at }`
- From the Home screen button (or Settings), build URL:
  - `https://sharemycard.app/user/login-with-token.php?token={token}&redirect=/user/contacts/index.php`
  - Open with `UIApplication.shared.open(...)`

### Website Changes
- Add `web/user/login-with-token.php`:
  - Parse `token`, `redirect`
  - Validate token:
    - For JWT: verify signature, `exp`, `user_id`; check replay store for `jti/nonce`
    - For opaque: look up by token, confirm not used and not expired
  - Create PHP session for `user_id`, set cookies `Secure; HttpOnly; SameSite=Lax`
  - Mark token as used
  - Redirect to allow-listed path (default `/user/contacts/index.php`)

### Backend/API Changes
- Implement `POST /api/auth/create-sso-token` in `web/api/auth/`:
  - Auth required via Bearer token; extract `user_id`
  - Generate OTLT with `nonce`, `exp` (now + 60s), `user_id`
  - Store replay key (e.g., `sso:{nonce}`) with TTL in Redis or DB
  - Return token

### Optional Enhancements
- Bind OTLT to device or IP range (might impact mobility)
- Include `redirect` hash inside token to prevent tampering (or sign the entire query)
- Deep link into a specific web page and preserve app context via query params

### Testing Plan
1) iOS happy path: request token, open link within 60s → lands authenticated on contacts page
2) Expired token (wait past TTL) → shows error and link to normal login
3) Replay token (use twice) → first succeeds, second fails
4) Tampered `redirect` (external URL) → rejected; default to safe page
5) Cross-browser cookies (Safari/iOS, Chrome/Android, desktop) → session persists as expected
6) Audit logs: token creation/consumption recorded

### Rollout
- Deploy API endpoint and website handler
- Ship iOS update with “Open Web (SSO)” button
- Monitor logs and add rate limits and alerts as needed


