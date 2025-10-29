## Contact Update Requests – Website-Only Plan

### Objective
Enable a user (account holder) to request updated information from a contact via email. The email contains a never‑expiring link to a web form where the contact can either confirm their existing info or submit updates.

### Scope
- Website only (triggered while viewing a contact)
- Uses existing contact email if available; otherwise informs the user no request can be sent

---

### User Flow (Account Holder)
1) User views a contact on the website and clicks “Request Update”.
2) System checks if the contact record has a valid email address.
   - If missing/invalid: show a non-blocking error toast/modal: “No valid email on file. Cannot send update request.”
   - If valid: continue.
3) System creates or reuses an update token (never expires) and stores audit fields.
4) System sends an email to the contact with the update link (similar styling to invitations).
5) UI shows confirmation “Update request sent” and displays the last request timestamp on the contact detail page.

### Contact Flow (Recipient)
1) Contact clicks the link in the email → lands on secure page with a prefilled form showing current info.
2) Options:
   - Click “Confirm Info is Current” → no data changes.
   - Edit fields and click “Submit Updates” → applies changes to the contact record.
3) In both cases, the system records “Last Known Good Contact” timestamp.

---

### Data Model
- Table: `contacts`
  - `last_update_request_at` DATETIME NULL – when a request email was last sent
  - `last_confirmed_at` DATETIME NULL – when the contact last confirmed/updated
  - `update_token` VARCHAR(128) NULL – stable, unique token per contact (unguessable)
  - `update_token_created_at` DATETIME NULL – audit for token issuance
  - `update_token_used_at` DATETIME NULL – optional audit for last time link was used

Notes:
- Token never expires per requirement, but we still store timestamps for audit and optional future revocation/rotation.
- If `update_token` is NULL, generate one; if present, reuse it.

---

### Validation
- Email validity check before sending: RFC compliant basic validation.
- On contact form: validate key fields (first/last name, email format). Only update provided/allowed fields.

---

### Endpoints & Pages
- Authenticated (user side):
  - `POST /user/api/request-contact-update.php`
    - Input: `contact_id`
    - Checks email; if invalid, returns `{ success:false, message }`
    - Ensures token exists (create if missing)
    - Sends email; sets `last_update_request_at = NOW()`
    - Returns `{ success:true }`

- Public (contact side):
  - `GET /public/contact-update.php?token=...`
    - Loads contact by `update_token`
    - Shows prefilled, minimal form (first name, last name, email, phones, org, title, address, website, notes [optional])
    - Buttons:
      - “Confirm Info is Current” → `POST` to same endpoint, record `last_confirmed_at = NOW()`, optional `update_token_used_at = NOW()`
      - “Submit Updates” → applies changes, sets `updated_at = NOW()`, sets `last_confirmed_at = NOW()`
    - On success: friendly confirmation page; link to privacy policy

---

### Email
- Sender: same as existing system invitation emails
- Subject: “Please confirm your contact details for {Account Holder/Company}”
- Content (HTML):
  - Brief context statement
  - Prominent CTA button → `contact-update.php?token=...`
  - Secondary plain link for fallback
  - Footer consistent with brand; include privacy link
- Design: reuse styles/templates from invite emails for visual consistency

---

### Notifications
- When a contact completes the form (either confirms or updates), send an email notification to the account holder.
  - Include contact name, timestamp, and list of changed fields (if any), plus a link to view the contact.
  - Use existing invite email styling for consistency.

---

### Security
- Token is long, random, unguessable (≥ 128 bits of entropy). Store as opaque string.
- Link never expires per requirement; allow manual rotation by user/admin (optional UI later).
- Only exposes fields for the specific contact tied to token.
- Rate limit: limit number of outbound emails per contact per hour/day.
- Logging: record send attempts, token usage, IP and User-Agent on form submit (server logs), without storing sensitive data.

---

### UI Changes
- Contact Details (website):
  - “Request Update” button
  - Show:
    - “Last Update Request: {timestamp or ‘Never’}”
    - “Last Confirmed: {timestamp or ‘Never’}”

---

### Implementation Steps
1) Database
   - Add columns: `last_update_request_at`, `last_confirmed_at`, `update_token`, `update_token_created_at`, `update_token_used_at`.
   - Backfill `update_token` for existing contacts lazily on first request.
2) API – User Side
   - Implement `request-contact-update.php` (session-authenticated) to validate email, generate/send email, and set timestamps.
3) Public Form
   - Implement `public/contact-update.php`:
     - GET: fetch by token, render prefilled form
     - POST (Confirm): record `last_confirmed_at` without changes
     - POST (Update): validate and update allowed fields; set `last_confirmed_at`
4) Email Template
   - Create/update HTML template consistent with invitation emails; include CTA button.
5) UI Integration
   - Add “Request Update” button to contact view page; handle error/success toasts.
6) Account Holder Notification
   - After successful confirm/update, send notification email to the account holder with summary and link.
7) Logging & Rate Limits
   - Log events; add simple rate-limit checks to API endpoint.

---

### Testing
- Unit/Integration
  - Token creation and reuse
  - Email send path with valid/invalid contact emails
  - Form GET by token (valid/missing/unknown)
  - Confirm path (no changes) sets `last_confirmed_at`
  - Update path persists fields and sets `last_confirmed_at`
- UX
  - Error copy when no email exists
  - Success toasts after request
  - Accessibility on public form

---

### Metrics & Audit
- Track counts of requests sent, confirmations, updates
- Store `last_update_request_at`, `last_confirmed_at`, and (optionally) IP/UA for POSTs in server logs

---

### Future Enhancements (Optional)
- Allow contact to add a missing email before starting (would change requirement); otherwise keep user informed of missing email.
- Token rotation/revocation UI on the website.
- Signed token alternative (JWT) with server verification but no DB lookup.

