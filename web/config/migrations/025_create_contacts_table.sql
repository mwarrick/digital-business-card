-- Migration: Create contacts table
-- Description: Table for storing user contacts and converted leads

CREATE TABLE IF NOT EXISTS contacts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_user CHAR(36) NOT NULL,
    id_lead INT NULL,
    id_business_card INT NULL,
    
    -- Contact Information
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    company VARCHAR(200),
    job_title VARCHAR(200),
    
    -- Additional Contact Details
    phone_mobile VARCHAR(20),
    phone_work VARCHAR(20),
    email_work VARCHAR(255),
    email_personal VARCHAR(255),
    
    -- Address Information
    address_street VARCHAR(255),
    address_city VARCHAR(100),
    address_state VARCHAR(100),
    address_zip VARCHAR(20),
    address_country VARCHAR(100),
    
    -- Social & Web
    website VARCHAR(500),
    linkedin_url VARCHAR(500),
    twitter_url VARCHAR(500),
    
    -- Notes & Tags
    notes TEXT,
    tags VARCHAR(500), -- Comma-separated tags
    
    -- Contact Source
    source VARCHAR(50) DEFAULT 'manual', -- 'converted_lead', 'manual', 'qr_scan', 'import'
    
    -- Relationship Status
    relationship_status VARCHAR(50) DEFAULT 'active', -- 'active', 'inactive', 'archived'
    favorite BOOLEAN DEFAULT FALSE,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_contacted_at TIMESTAMP NULL,
    
    -- Foreign Keys
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (id_lead) REFERENCES leads(id) ON DELETE SET NULL,
    FOREIGN KEY (id_business_card) REFERENCES business_cards(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_user (id_user),
    INDEX idx_lead (id_lead),
    INDEX idx_business_card (id_business_card),
    INDEX idx_name (first_name, last_name),
    INDEX idx_email (email),
    INDEX idx_favorite (favorite),
    INDEX idx_created_at (created_at)
);
