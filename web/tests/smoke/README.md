# QR Smoke Tests

Quick smoke tests for public QR routes. Requires a running site and a few sample QR IDs.

## Run

```
BASE_URL=https://sharemycard.app \
QR_ID_URL=... \
QR_ID_SOCIAL=... \
QR_ID_APPSTORE=... \
QR_ID_DEFAULT=... \
php web/tests/smoke/qr_smoke.php
```

- BASE_URL: The base URL to test against.
- QR_ID_URL: Active QR of type `url`.
- QR_ID_SOCIAL: Active QR of type `social`.
- QR_ID_APPSTORE: Active QR of type `appstore`.
- QR_ID_DEFAULT: Active QR of type `default` (landing).

Any missing variables will cause that check to be skipped.
