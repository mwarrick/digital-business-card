-- Add image path columns to demo_data table
-- This allows demo cards to properly reference existing demo images

ALTER TABLE demo_data 
ADD COLUMN profile_photo_path VARCHAR(255) NULL,
ADD COLUMN company_logo_path VARCHAR(255) NULL,
ADD COLUMN cover_graphic_path VARCHAR(255) NULL;

-- Update existing demo data with correct image filenames
UPDATE demo_data SET 
    profile_photo_path = 'demo-alex-profile.jpg',
    company_logo_path = 'demo-techcorp-logo.jpg',
    cover_graphic_path = 'demo-techcorp-cover.jpg'
WHERE card_id = 'demo-card-alex-chen';

UPDATE demo_data SET 
    profile_photo_path = 'demo-sarah-profile.jpg',
    company_logo_path = 'demo-designstudio-logo.jpg',
    cover_graphic_path = 'demo-designstudio-cover.jpg'
WHERE card_id = 'demo-card-sarah-martinez';

UPDATE demo_data SET 
    profile_photo_path = 'demo-michael-profile.jpg',
    company_logo_path = 'demo-innovation-logo.jpg',
    cover_graphic_path = 'demo-innovation-cover.jpg'
WHERE card_id = 'demo-card-michael-thompson';
