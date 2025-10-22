-- Create addresses table for business card addresses
-- This table stores physical addresses for business cards

CREATE TABLE IF NOT EXISTS addresses (
    id CHAR(36) PRIMARY KEY,  -- UUID format
    card_id CHAR(36) NOT NULL,  -- References business_cards.id
    street VARCHAR(255),
    city VARCHAR(100),
    state VARCHAR(100),
    zip VARCHAR(20),
    country VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_card_id (card_id)
);

-- Add foreign key constraint if business_cards table exists
-- Note: This will only work if business_cards.id is CHAR(36) UUID format
-- If the constraint fails, the table will still be created without the FK
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'business_cards') > 0,
    'ALTER TABLE addresses ADD CONSTRAINT fk_addresses_card_id FOREIGN KEY (card_id) REFERENCES business_cards(id) ON DELETE CASCADE',
    'SELECT "business_cards table not found, skipping foreign key constraint" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
