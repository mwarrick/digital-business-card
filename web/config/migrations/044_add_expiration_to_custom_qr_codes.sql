-- Migration: 044_add_expiration_to_custom_qr_codes.sql
-- Description: Add expiration date and expiration notice fields to custom_qr_codes table

ALTER TABLE custom_qr_codes
ADD COLUMN expires_at DATETIME NULL COMMENT 'Date and time when QR code expires (EST timezone). NULL = no expiration.',
ADD COLUMN expiration_notice VARCHAR(500) DEFAULT 'Sorry, this QR code has expired.' COMMENT 'Customizable message shown when QR code has expired.';

-- Add index on expires_at for efficient expiration queries
ALTER TABLE custom_qr_codes
ADD INDEX idx_expires_at (expires_at);

