## Virtual Backgrounds – Custom Image + Advanced Positioning

### Core Goals
- Allow users to upload a custom background image.
- Provide live preview with cropping/positioning when the image doesn’t match standard dimensions.
- Keep output resolutions fixed (use existing presets; no changes to final dimensions).
- Add QR positions: top-center and bottom-center (in addition to current corners and bottom).

### UX / UI
- Upload control with drag-and-drop, file size/type hints, and recommended dimensions.
- Live preview canvas showing:
  - Selected resolution preset (unchanged preset list).
  - Uploaded image rendered behind.
  - QR overlay with selectable position (now includes top-center, bottom-center).
- Fit modes for uploaded image:
  - Cover (crop to fill area)
  - Contain (letterbox/pillarbox; keep aspect ratio)
  - Stretch (fill target; warn about potential blur)
- Crop/selection:
  - Reuse Cropper.js approach used for profile photos to allow precise framing.
  - Support moving/scaling crop to match target aspect ratio.
- Inline warnings:
  - Image smaller than target → “May appear blurry. Stretch?” with toggle.
  - Very large image → “We’ll optimize this for performance.”

### Client-Side Preview Logic
- Single HTML canvas to compose background + QR overlay for instant feedback.
- Controls:
  - Resolution preset selector (existing)
  - Background fit mode selector (cover/contain/stretch)
  - Optional manual crop (Cropper.js) to define the region used
  - QR position radio group: top-left, top-center, top-right, bottom-left, bottom-center, bottom-right
  - QR size and padding sliders (existing functionality retained)
- Only send data to server on explicit “Generate/Download”; keep preview purely client-side.

### Server-Side Generation (PHP GD)
- Extend generator to accept:
  - `backgroundMode`: cover | contain | stretch
  - `cropBox`: optional crop region (x, y, w, h) in source coordinates
  - `backgroundPath`: secure stored file path of uploaded image
  - `qrPosition`: add `top-center`, `bottom-center`
- Pipeline:
  1) Load uploaded image (strip EXIF by re-encoding)
  2) If `cropBox` provided, crop first
  3) Scale according to `backgroundMode` to exact target resolution (unchanged presets)
  4) Render QR overlay at requested position/padding/size
  5) Output PNG for download

### Data Model & Storage
- Store uploads under `storage/media/backgrounds/` with user-scoped, hashed filenames.
- Persist last-used background prefs in the existing virtual background preferences table:
  - `background_mode`, `background_path`, `crop_box`, `qr_position`
- Enforce:
  - Allowed file types: jpg, png, webp
  - Max file size (e.g., 10–20 MB)
  - Dimension checks (warn when undersized)

### Validation & Security
- MIME/type validation on upload, size limits, extension verification.
- Re-encode server-side to standardize and remove metadata.
- Rate limits/quotas per user to prevent abuse.

### Performance
- Client: cap preview canvas size; throttle slider interactions.
- Server: limit max source dimensions and downscale early to conserve memory.
- Optional render cache: hash of inputs (path + mode + crop + resolution + qr params) → cached PNG.

### QA & Compatibility
- Test with:
  - Small images (trigger stretch flow + warning)
  - Large images (downscale path)
  - Portrait/landscape/panoramic aspect ratios
  - All QR positions (including new centers)
  - All existing resolutions
- Visual regression: ensure consistent padding, edge clipping, and sharpness.

### Rollout
- Backward compatible; existing gradient backgrounds remain available.
- Add a “Use custom background image” section in the UI with clear help text (recommended sizes, fit modes explained).
- Documentation: update README Virtual Backgrounds section with the custom image workflow.

### Acceptance Criteria
- Users can upload an image, preview (crop/fit), and generate final PNGs at standard resolutions.
- New QR positions (top-center, bottom-center) are available in both preview and final outputs.
- Output dimensions always match the selected preset exactly.
- Undersized images display a clear blur/stretch warning with user choice.


