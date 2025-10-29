# Admin Test Files

This directory contains test files for the ShareMyCard admin functionality.

## Files

- `test-demo-email-generation.php` - Tests demo email generation
- `test-migration.php` - Tests database migrations
- `test-sql-insert.php` - Tests SQL insert operations
- `test-image-logging.php` - Tests image creation logging
- `test-impersonation.php` - Tests user impersonation functionality
- `test-demo-data-population.php` - Tests demo data population
- `test-simple-population.php` - Tests simple data population
- `test-image-logger.php` - Tests image logger functionality

## Usage

These files are for development and testing purposes only. They should not be accessible in production environments.

## Security Note

Ensure these test files are not accessible via web requests in production by:
1. Adding them to `.htaccess` deny rules
2. Moving them outside the web root
3. Using proper server configuration
