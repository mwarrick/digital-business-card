# Database Schema

## Users Table
```sql
CREATE TABLE users (
    id CHAR(36) PRIMARY KEY,  -- UUID format
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NULL DEFAULT NULL,  -- Optional: reserved for future password auth (currently unused)
    is_active TINYINT(1) DEFAULT 0,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Note:** The system currently uses passwordless authentication via email verification codes. The `password_hash` field is nullable and reserved for potential future password authentication features.

## Business Cards Table
```sql
CREATE TABLE business_cards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    company_name VARCHAR(200),
    job_title VARCHAR(200),
    bio TEXT,
    profile_photo VARCHAR(255),
    company_logo VARCHAR(255),
    cover_graphic VARCHAR(255),
    qr_code_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_active (is_active)
);
```

## Additional Contact Info Table
```sql
CREATE TABLE contact_info (
    id INT PRIMARY KEY AUTO_INCREMENT,
    card_id INT NOT NULL,
    type ENUM('email', 'phone') NOT NULL,
    subtype VARCHAR(20) NOT NULL, -- 'personal', 'work', 'mobile', 'home'
    value VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (card_id) REFERENCES business_cards(id) ON DELETE CASCADE,
    INDEX idx_card_id (card_id)
);
```

## Website Links Table
```sql
CREATE TABLE website_links (
    id INT PRIMARY KEY AUTO_INCREMENT,
    card_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    url VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (card_id) REFERENCES business_cards(id) ON DELETE CASCADE,
    INDEX idx_card_id (card_id)
);
```

## Addresses Table
```sql
CREATE TABLE addresses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    card_id INT NOT NULL,
    street VARCHAR(255),
    city VARCHAR(100),
    state VARCHAR(100),
    zip VARCHAR(20),
    country VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (card_id) REFERENCES business_cards(id) ON DELETE CASCADE,
    INDEX idx_card_id (card_id)
);
```

## Authentication Tokens Table
```sql
CREATE TABLE auth_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_token_hash (token_hash),
    INDEX idx_expires (expires_at)
);
```

---

## Custom QR Codes (NEW)

### custom_qr_codes
```sql
CREATE TABLE custom_qr_codes (
    id VARCHAR(36) NOT NULL PRIMARY KEY,            -- UUID
    user_id VARCHAR(36) NOT NULL,                   -- users.id (UUID)
    type ENUM('default','url','social','text','wifi','appstore') NOT NULL DEFAULT 'default',
    payload_json JSON NULL,                         -- type-specific payload
    title VARCHAR(120) NULL,
    slug VARCHAR(160) NULL UNIQUE,
    theme_key VARCHAR(64) NULL,
    cover_image_url VARCHAR(512) NULL,
    landing_title VARCHAR(160) NULL,
    landing_html MEDIUMTEXT NULL,
    show_lead_form TINYINT(1) NOT NULL DEFAULT 1,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_user (user_id),
    KEY idx_status (status),
    KEY idx_type (type),
    KEY idx_created_at (created_at)
);
```

### custom_qr_events
```sql
CREATE TABLE custom_qr_events (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    qr_id VARCHAR(36) NOT NULL,                         -- custom_qr_codes.id
    event ENUM('view','redirect','lead_submit') NOT NULL,
    event_target VARCHAR(255) NULL,
    session_id VARCHAR(64) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    referrer TEXT NULL,
    -- analytics enrichment
    device_type VARCHAR(50) NULL,                       -- Mobile / Tablet / Desktop / Unknown
    browser VARCHAR(100) NULL,                          -- Chrome / Safari / Firefox / Edge / Opera / Other
    os VARCHAR(100) NULL,                               -- iOS / Android / Windows / macOS / Linux / Other
    city VARCHAR(120) NULL,
    country VARCHAR(120) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_qr_created (qr_id, created_at),
    KEY idx_event (event)
);
```

### qr_leads (linking to existing `leads`)
```sql
CREATE TABLE qr_leads (
    qr_id VARCHAR(36) NOT NULL,   -- custom_qr_codes.id
    lead_id INT NOT NULL,         -- leads.id
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_qr (qr_id),
    KEY idx_lead (lead_id)
);
```

### leads table adjustment
```sql
-- Existing leads table updated to support QR sources
ALTER TABLE leads
  MODIFY COLUMN id_business_card VARCHAR(36) NULL,  -- now nullable
  ADD COLUMN qr_id VARCHAR(36) NULL AFTER id_business_card;
```

---

## Routing & Public Handling (Reference)
- Public scans are served by `web/public/qr.php` → `/qr/{uuid}`
- Inactive QR codes return a friendly branded page (`public/includes/qr/inactive.php`)
- Rate limiting is applied via a file-based limiter with IP whitelist

## Future Tables (Backlog Features)

### Connections Table
```sql
CREATE TABLE connections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    connected_card_id INT NOT NULL,
    connected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (connected_card_id) REFERENCES business_cards(id) ON DELETE CASCADE,
    UNIQUE KEY unique_connection (user_id, connected_card_id),
    INDEX idx_user_connections (user_id)
);
```

### Chat Messages Table
```sql
CREATE TABLE chat_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    connection_id INT NOT NULL,
    sender_user_id INT NOT NULL,
    message TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    
    FOREIGN KEY (connection_id) REFERENCES connections(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_connection_messages (connection_id, sent_at)
);
```