# Leads and Contacts Management System - Technical Specification

## Overview

This specification outlines the implementation of a comprehensive Leads and Contacts management system for ShareMyCard, enabling users to capture leads through public business cards and manage their professional network.

---

## 1. Database Schema (Existing Tables)

### 1.1 Leads Table (Existing Structure)

The existing `leads` table has the following structure:

```sql
-- Existing leads table structure
CREATE TABLE leads (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(255),
    last_name VARCHAR(255),
    full_name VARCHAR(255),
    work_phone VARCHAR(20),
    mobile_phone VARCHAR(20),
    email_primary VARCHAR(255),
    street_address VARCHAR(255),
    city VARCHAR(100),
    state VARCHAR(100),
    zip_code VARCHAR(20),
    country VARCHAR(100),
    organization_name VARCHAR(255),
    job_title VARCHAR(255),
    birthdate DATE,
    notes TEXT,
    website_url VARCHAR(255),
    photo_url VARCHAR(255),
    id_business_card VARCHAR(36) NOT NULL,
    id_user VARCHAR(36) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    comments_from_lead VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NOT NULL,
    referrer VARCHAR(500) NOT NULL
);
```

**Field Mapping for Lead Capture:**
- `first_name` → Lead's first name
- `last_name` → Lead's last name  
- `email_primary` → Lead's email address
- `work_phone` → Lead's work phone
- `mobile_phone` → Lead's mobile phone
- `organization_name` → Lead's company
- `job_title` → Lead's job title
- `comments_from_lead` → Lead's message/notes
- `id_business_card` → Business card that captured the lead
- `id_user` → Business card owner's user ID

### 1.2 Contacts Table (Existing Structure)

The existing `contacts` table has the following structure:

```sql
-- Existing contacts table structure
CREATE TABLE contacts (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(255),
    last_name VARCHAR(255),
    full_name VARCHAR(255),
    work_phone VARCHAR(20),
    mobile_phone VARCHAR(20),
    email_primary VARCHAR(255),
    street_address VARCHAR(255),
    city VARCHAR(100),
    state VARCHAR(100),
    zip_code VARCHAR(20),
    country VARCHAR(100),
    organization_name VARCHAR(255),
    job_title VARCHAR(255),
    birthdate DATE,
    notes TEXT,
    website_url VARCHAR(255),
    photo_url VARCHAR(255),
    id_lead INT(11) NOT NULL,
    id_user VARCHAR(36) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    comments_from_lead VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NOT NULL,
    referrer VARCHAR(500) NOT NULL
);
```

**Field Mapping for Contact Management:**
- `first_name` → Contact's first name
- `last_name` → Contact's last name
- `email_primary` → Contact's primary email
- `work_phone` → Contact's work phone
- `mobile_phone` → Contact's mobile phone
- `organization_name` → Contact's company
- `job_title` → Contact's job title
- `id_lead` → Source lead ID (if converted from lead)
- `id_user` → Contact owner's user ID

### 1.3 Additional Tables Needed

We may need to add some additional fields to support the full functionality:

```sql
-- Add status and conversion tracking to leads table
ALTER TABLE leads ADD COLUMN status VARCHAR(50) DEFAULT 'new';
ALTER TABLE leads ADD COLUMN converted_to_contact BOOLEAN DEFAULT FALSE;
ALTER TABLE leads ADD COLUMN converted_at TIMESTAMP NULL;

-- Add relationship status and favorites to contacts table  
ALTER TABLE contacts ADD COLUMN relationship_status VARCHAR(50) DEFAULT 'active';
ALTER TABLE contacts ADD COLUMN favorite BOOLEAN DEFAULT FALSE;
ALTER TABLE contacts ADD COLUMN last_contacted_at TIMESTAMP NULL;
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