-- Migration: Create contact_interactions table
-- Description: Table for tracking interactions with contacts (future enhancement)

CREATE TABLE IF NOT EXISTS contact_interactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_contact INT NOT NULL,
    id_user CHAR(36) NOT NULL,
    
    -- Interaction Details
    interaction_type VARCHAR(50) NOT NULL, -- 'call', 'email', 'meeting', 'note'
    subject VARCHAR(255),
    description TEXT,
    
    -- Metadata
    interaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (id_contact) REFERENCES contacts(id) ON DELETE CASCADE,
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_contact (id_contact),
    INDEX idx_interaction_date (interaction_date)
);
