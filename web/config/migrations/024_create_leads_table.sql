-- Migration: Create leads table
-- Description: Table for storing captured leads from public business cards

CREATE TABLE IF NOT EXISTS leads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_business_card INT NOT NULL,
    
    -- Lead Information
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    company VARCHAR(200),
    job_title VARCHAR(200),
    
    -- Additional Information
    message TEXT,
    notes TEXT,
    
    -- Lead Source & Status
    source VARCHAR(50) DEFAULT 'web_form', -- 'web_form', 'qr_scan', 'manual'
    status VARCHAR(50) DEFAULT 'new', -- 'new', 'contacted', 'qualified', 'converted', 'archived'
    
    -- Conversion Tracking
    converted_to_contact BOOLEAN DEFAULT FALSE,
    id_contact INT NULL,
    converted_at TIMESTAMP NULL,
    
    -- Metadata
    captured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    referrer VARCHAR(500),
    
    -- Foreign Keys
    FOREIGN KEY (id_business_card) REFERENCES business_cards(id) ON DELETE CASCADE,
    FOREIGN KEY (id_contact) REFERENCES contacts(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_business_card (id_business_card),
    INDEX idx_status (status),
    INDEX idx_converted (converted_to_contact),
    INDEX idx_captured_at (captured_at),
    INDEX idx_email (email)
);
