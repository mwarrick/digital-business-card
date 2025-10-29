## Contacts Export - iOS and Website

### Objectives

- Add an Export capability in the iOS app to create a native device contact from an existing app Contact.
- Add an Export capability on the website to download a contact as a VCF (vCard) file.

### Scope and Order

1) Implement iOS Export (first)
2) Implement Website Export

---

### iOS Export

#### UI Placement
- Add an “Export” button to `ContactDetailsView` action area (e.g., toolbar or action list) for a single contact.
- Defer multi-select export for a later iteration.

#### Behavior
- Tapping “Export” opens the native Add New Contact screen pre-filled with the app’s Contact details.
- Use Contacts frameworks:
  - `Contacts` for building a `CNMutableContact` from our model
  - `ContactsUI` to present `CNContactViewController(forNewContact:)`

#### Field Mapping
- Name: `first_name`, `last_name`
- Phone numbers:
  - `work_phone` → phone with label `.work`
  - `mobile_phone` → phone with label `.mobile`
- Email: `email_primary`
- Organization: `organization_name`
- Job Title: `job_title`
- Address: `street_address`, `city`, `state`, `zip_code`, `country` → `CNMutablePostalAddress`
- Website: `website_url` (as labeled URL, suggested label “homepage”)
- Notes: include `notes` (append `comments_from_lead` if present)
- Photo: if `photo_url` is available and fetchable quickly, set `imageData` (best-effort, non-blocking)

#### Permissions and UX
- Ensure `NSContactsUsageDescription` is present and user-friendly.
- Use `CNContactStore` authorization flow; request permission before presenting the add-contact controller.
- Handle cancel vs save gracefully; on cancel simply dismiss, on save optionally show a brief confirmation.

#### Edge Cases
- Only set fields that are present; avoid placeholder values.
- Do not attempt custom formatting for international numbers.
- Let the system manage duplicate detection/merge suggestions.

#### Testing
- Test on device: first-time permission prompt, data mapping correctness, cancel/save paths, large text / unicode handling, photo attachment performance.

---

### Website Export

#### UI Placement
- Add an “Export” button next to each contact in the user contacts interface (e.g., list row actions or contact details page).

#### Behavior
- Clicking “Export” hits a new endpoint, e.g. `web/user/api/export-contact-vcf.php?id={id}`.
- The endpoint returns a `text/vcard` response with headers set to download as attachment: `Content-Disposition: attachment; filename="contact-{id}.vcf"`.

#### VCF Generation
- Prefer vCard 3.0 for broad compatibility (iOS, Android, Outlook, Google Contacts).
- Map fields:
  - `N` and `FN` for name
  - `ORG` and `TITLE`
  - `EMAIL;TYPE=WORK` (and TYPE=HOME if needed later)
  - `TEL;TYPE=WORK` and `TEL;TYPE=CELL`
  - `ADR;TYPE=WORK` (PO Box empty; street, city, state, postal code, country)
  - `URL`
  - `NOTE` (include notes and comments)
  - `BDAY` if `YYYY-MM-DD` is present
  - `PHOTO` may be included as URL (embedding image data can be added later)

#### Encoding and Formatting
- Output UTF-8 with CRLF line endings (`\r\n`).
- Optionally fold long lines at 75 bytes (recommended for strict readers).

#### Security and Access
- Require user login; verify the contact belongs to the authenticated user before generating VCF.

#### Testing
- Validate downloads and imports across:
  - iOS Contacts (Safari download → share → Contacts)
  - Android Contacts
  - Google Contacts Web import
  - Outlook (desktop/web)

---

### Implementation Notes

- iOS:
  - Add an export action in `ContactDetailsView`.
  - Implement mapping helper to convert app `Contact` → `CNMutableContact`.
  - Present `CNContactViewController` modally with a `UINavigationController` if needed.
  - Ensure app Info.plist includes `NSContactsUsageDescription`.

- Website:
  - Add `export-contact-vcf.php` in `web/user/api/`.
  - Validate user/session and contact ownership.
  - Render vCard 3.0, set headers for download, ensure CRLF endings.
  - Add “Export” buttons in relevant contact UI templates to link to the endpoint.

---

### Out of Scope (Future)
- Batch/export multiple contacts in a single `.vcf`.
- Custom label mapping beyond work/mobile.
- Embedded `PHOTO` binary data with base64 and line folding.

