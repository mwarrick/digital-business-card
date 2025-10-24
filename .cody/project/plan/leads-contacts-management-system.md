# Leads and Contacts Management System - Technical Specification

## Overview

This specification outlines the implementation of a comprehensive Leads and Contacts management system for ShareMyCard, enabling users to capture leads through public business cards and manage their professional network.

---

## 1. Database Schema

### 1.1 Leads Table

```sql
CREATE TABLE leads (
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
```

### 1.2 Contacts Table

```sql
CREATE TABLE contacts (
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
```

### 1.3 Contact Interactions Table (Future Enhancement)

```sql
CREATE TABLE contact_interactions (
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
```

---

## 2. Lead Capture System

### 2.1 Public Lead Capture Form

**File**: `/web/public/capture-lead.php`

The lead capture form will be accessible via any public business card URL with a `?capture=1` parameter. This form will:

- Display the business card owner's information
- Collect lead information (name, email, phone, company, message)
- Include a hidden field with the business card ID
- Submit to the lead capture API endpoint
- Include rate limiting and validation

### 2.2 Lead Capture API Endpoint

**File**: `/web/api/leads/capture.php`

The API endpoint will:

- Validate the business card exists and is active
- Validate lead information
- Apply rate limiting (5 submissions per hour per IP)
- Store lead data with metadata (IP, user agent, referrer)
- Return success/error response

---

## 3. User Dashboard Integration

### 3.1 Leads Management Dashboard

**File**: `/web/user/leads/index.php`

Features:
- Display all leads for user's business cards
- Filter by status (new, contacted, qualified, converted, archived)
- Search by name, email, or company
- View lead details
- Edit lead information
- Convert lead to contact
- Delete leads
- Statistics dashboard

### 3.2 Contacts Management Dashboard

**File**: `/web/user/contacts/index.php`

Features:
- Display all user contacts
- Search by name
- Filter by source (converted lead, manual, QR scan, import)
- Filter by relationship status (active, inactive, archived)
- Mark contacts as favorites
- View contact details
- Edit contact information
- Create new contacts
- Delete contacts
- Import/export functionality

---

## 4. API Endpoints

### 4.1 Leads API (`/web/api/leads/`)

- `GET /` - Get all leads for authenticated user
- `PUT /` - Update lead information
- `DELETE /?id={lead_id}` - Delete lead
- `POST /capture` - Capture new lead (public endpoint)
- `POST /convert` - Convert lead to contact

### 4.2 Contacts API (`/web/api/contacts/`)

- `GET /` - Get all contacts for authenticated user
- `POST /` - Create new contact
- `PUT /` - Update contact information
- `DELETE /?id={contact_id}` - Delete contact
- `POST /{contact_id}/favorite` - Toggle favorite status

---

## 5. Admin Functionality

### 5.1 Admin Leads View

**File**: `/web/admin/leads/index.php`

Features:
- View all leads across all users
- Filter by user, status, date range
- View lead statistics
- Export lead data
- Cannot edit leads (read-only)

### 5.2 Admin Contacts View

**File**: `/web/admin/contacts/index.php`

Features:
- View all contacts across all users
- Filter by user, source, status
- View contact statistics
- Export contact data
- Cannot edit contacts (read-only)

---

## 6. Implementation Checklist

### 6.1 Database Setup
- [x] Create leads table migration
- [x] Create contacts table migration  
- [x] Create contact_interactions table migration
- [ ] Run migrations on production database
- [ ] Verify table structures and indexes

### 6.2 Lead Capture System
- [ ] Create public lead capture form
- [ ] Implement lead capture API endpoint
- [ ] Add rate limiting to lead capture
- [ ] Create lead capture form styling
- [ ] Add form validation and error handling
- [ ] Test lead capture functionality

### 6.3 User Dashboard Integration
- [ ] Create leads management dashboard
- [ ] Create contacts management dashboard
- [ ] Implement leads CRUD API
- [ ] Implement contacts CRUD API
- [ ] Add lead to contact conversion API
- [ ] Create dashboard styling
- [ ] Add JavaScript functionality

### 6.4 Admin Functionality
- [ ] Create admin leads view
- [ ] Create admin contacts view
- [ ] Add admin navigation
- [ ] Implement admin statistics
- [ ] Test admin functionality

### 6.5 Testing & Deployment
- [ ] Test all API endpoints
- [ ] Test form submissions
- [ ] Test user permissions
- [ ] Test admin permissions
- [ ] Performance testing
- [ ] Security testing
- [ ] Deploy to production

---

## 7. Future Enhancements

### 7.1 iOS Integration
- QR code scanning to capture leads
- Contact form integration
- Push notifications for new leads
- Offline lead capture

### 7.2 Advanced Features
- Lead scoring system
- Automated follow-up emails
- Contact interaction tracking
- CRM integration
- Analytics and reporting
- Bulk import/export
- Contact merging
- Tag management
- Custom fields

### 7.3 Integration Possibilities
- Email marketing platforms
- CRM systems
- Calendar applications
- Social media platforms
- Analytics tools

---

This comprehensive specification provides a complete blueprint for implementing the Leads and Contacts management system for ShareMyCard, enabling users to capture leads through public business cards and manage their professional network effectively.