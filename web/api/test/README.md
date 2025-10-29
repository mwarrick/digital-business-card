# API Test Files

This directory contains test files for the ShareMyCard API endpoints.

## Files

- `test-qr-endpoint.php` - Tests QR code generation endpoints
- `test-url-fetch.php` - Tests URL fetching functionality

## Usage

These files are for development and testing purposes only. They should not be accessible in production environments.

## Security Note

Ensure these test files are not accessible via web requests in production by:
1. Adding them to `.htaccess` deny rules
2. Moving them outside the web root
3. Using proper server configuration
