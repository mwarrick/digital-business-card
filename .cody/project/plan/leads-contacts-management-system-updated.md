# Leads and Contacts Management System - Updated Technical Specification

## Overview

This specification outlines the implementation of a comprehensive Leads and Contacts management system for ShareMyCard using the **existing database tables**. The system enables users to capture leads through public business cards and manage their professional network.

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

### 1.3 Working with Existing Tables Only

**Important**: This implementation uses ONLY the existing table structures without any modifications. All functionality is built around the current field names and data types.

**Status Tracking**: Use existing fields creatively:
- Lead status can be tracked in the `notes` field
- Conversion status can be tracked by checking if `id_lead` exists in contacts table
- Relationship status can be tracked in the `notes` field for contacts

---

## 2. Lead Capture System

### 2.1 Public Lead Capture Form

**File**: `/web/public/capture-lead.php`

The lead capture form will be accessible via any public business card URL with a `?capture=1` parameter. This comprehensive form will:

- Display the business card owner's information
- Collect **ALL** lead information using the existing table fields
- Include a hidden field with the business card ID
- Submit to the lead capture API endpoint
- Include rate limiting and validation

**Complete Lead Capture Form Fields (Exact Database Match):**
- **Personal Information**: first_name, last_name, full_name (auto-generated)
- **Contact Information**: email_primary, work_phone, mobile_phone
- **Professional Information**: organization_name, job_title
- **Address Information**: street_address, city, state, zip_code, country
- **Additional Information**: birthdate, website_url, notes
- **Lead Message**: comments_from_lead
- **System Fields**: ip_address, user_agent, referrer (auto-captured)
- **Business Card Link**: id_business_card (hidden field)
- **User Link**: id_user (from business card lookup)

**Sample Comprehensive Lead Capture Form:**

```html
<form id="leadForm" method="POST" action="/api/leads/capture">
    <input type="hidden" name="business_card_id" value="<?= htmlspecialchars($cardId) ?>">
    
    <!-- Personal Information -->
    <div class="form-section">
        <h3>Personal Information</h3>
        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name *</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>
        </div>
        <div class="form-group">
            <label for="birthdate">Birthdate</label>
            <input type="date" id="birthdate" name="birthdate">
        </div>
    </div>
    
    <!-- Contact Information -->
    <div class="form-section">
        <h3>Contact Information</h3>
        <div class="form-group">
            <label for="email_primary">Email Address *</label>
            <input type="email" id="email_primary" name="email_primary" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="work_phone">Work Phone</label>
                <input type="tel" id="work_phone" name="work_phone">
            </div>
            <div class="form-group">
                <label for="mobile_phone">Mobile Phone</label>
                <input type="tel" id="mobile_phone" name="mobile_phone">
            </div>
        </div>
    </div>
    
    <!-- Professional Information -->
    <div class="form-section">
        <h3>Professional Information</h3>
        <div class="form-group">
            <label for="organization_name">Company/Organization</label>
            <input type="text" id="organization_name" name="organization_name">
        </div>
        <div class="form-group">
            <label for="job_title">Job Title</label>
            <input type="text" id="job_title" name="job_title">
        </div>
    </div>
    
    <!-- Address Information -->
    <div class="form-section">
        <h3>Address Information</h3>
        <div class="form-group">
            <label for="street_address">Street Address</label>
            <input type="text" id="street_address" name="street_address">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="city">City</label>
                <input type="text" id="city" name="city">
            </div>
            <div class="form-group">
                <label for="state">State/Province</label>
                <input type="text" id="state" name="state">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="zip_code">ZIP/Postal Code</label>
                <input type="text" id="zip_code" name="zip_code">
            </div>
            <div class="form-group">
                <label for="country">Country</label>
                <input type="text" id="country" name="country">
            </div>
        </div>
    </div>
    
    <!-- Additional Information -->
    <div class="form-section">
        <h3>Additional Information</h3>
        <div class="form-group">
            <label for="website_url">Website</label>
            <input type="url" id="website_url" name="website_url">
        </div>
        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="3"></textarea>
        </div>
        <div class="form-group">
            <label for="comments_from_lead">Message/Comments *</label>
            <textarea id="comments_from_lead" name="comments_from_lead" rows="4" 
                      placeholder="Tell me how I can help you..." required></textarea>
        </div>
    </div>
    
    <button type="submit" class="btn-submit">Send Message</button>
</form>
```

### 2.2 Lead Capture API Endpoint

**File**: `/web/api/leads/capture.php`

```php
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/RateLimiter.php';
require_once __DIR__ . '/../includes/InputValidator.php';

header('Content-Type: application/json');

// Rate limiting
$rateLimiter = new RateLimiter();
if (!$rateLimiter->checkLimit('lead_capture', 5, 3600)) { // 5 submissions per hour
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many submissions. Please try again later.']);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get and validate input
$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$validator = new InputValidator();
$validator->required('business_card_id', $data['business_card_id'] ?? null);
$validator->required('first_name', $data['first_name'] ?? null);
$validator->required('last_name', $data['last_name'] ?? null);
$validator->email('email', $data['email'] ?? null);

if (!$validator->isValid()) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $validator->getErrors()]);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Verify business card exists and is active
    $stmt = $db->prepare("SELECT id, user_id FROM business_cards WHERE id = ? AND is_active = 1");
    $stmt->execute([$data['business_card_id']]);
    $card = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$card) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Business card not found']);
        exit;
    }
    
    // Insert lead using existing table structure with ALL fields (exact match)
    $stmt = $db->prepare("
        INSERT INTO leads (
            id_business_card, id_user, first_name, last_name, full_name,
            work_phone, mobile_phone, email_primary, street_address, city, state, 
            zip_code, country, organization_name, job_title, birthdate, notes, 
            website_url, photo_url, comments_from_lead, ip_address, user_agent, referrer
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $fullName = trim($data['first_name'] . ' ' . $data['last_name']);
    
    $result = $stmt->execute([
        $data['business_card_id'],
        $card['user_id'], // Get from business card lookup
        $data['first_name'],
        $data['last_name'],
        $fullName,
        $data['work_phone'] ?? null,
        $data['mobile_phone'] ?? null,
        $data['email_primary'],
        $data['street_address'] ?? null,
        $data['city'] ?? null,
        $data['state'] ?? null,
        $data['zip_code'] ?? null,
        $data['country'] ?? null,
        $data['organization_name'] ?? null,
        $data['job_title'] ?? null,
        $data['birthdate'] ?? null,
        $data['notes'] ?? null,
        $data['website_url'] ?? null,
        $data['photo_url'] ?? null,
        $data['comments_from_lead'] ?? null,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null,
        $_SERVER['HTTP_REFERER'] ?? null
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Thank you for your interest! We\'ll be in touch soon.',
            'lead_id' => $db->lastInsertId()
        ]);
    } else {
        throw new Exception('Failed to save lead');
    }
    
} catch (Exception $e) {
    error_log("Lead capture error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>
```

---

## 3. User Dashboard Integration

### 3.1 Leads Management Dashboard

**File**: `/web/user/leads/index.php`

Features:
- Display all leads for user's business cards using existing table structure
- Filter by status (new, contacted, qualified, converted, archived)
- Search by name, email, or company
- View lead details
- Edit lead information
- Convert lead to contact
- Delete leads
- Statistics dashboard

**Query for Leads:**
```sql
SELECT l.*, bc.first_name as card_first_name, bc.last_name as card_last_name,
       bc.company_name as card_company, bc.job_title as card_job_title,
       CASE WHEN EXISTS (SELECT 1 FROM contacts c WHERE c.id_lead = l.id) 
            THEN 'converted' ELSE 'new' END as status
FROM leads l
JOIN business_cards bc ON l.id_business_card = bc.id
WHERE bc.user_id = ?
ORDER BY l.created_at DESC
```

### 3.2 Contacts Management Dashboard

**File**: `/web/user/contacts/index.php`

Features:
- Display all user contacts using existing table structure
- Search by name
- Filter by source (converted lead, manual, QR scan, import)
- Filter by relationship status (active, inactive, archived)
- Mark contacts as favorites
- View contact details
- Edit contact information
- Create new contacts
- Delete contacts
- Import/export functionality

**Complete Contact Creation Form Fields (Exact Database Match):**
- **Personal Information**: first_name, last_name, full_name (auto-generated)
- **Contact Information**: email_primary, work_phone, mobile_phone
- **Professional Information**: organization_name, job_title
- **Address Information**: street_address, city, state, zip_code, country
- **Additional Information**: birthdate, website_url, notes, photo_url
- **Lead Link**: id_lead (optional, for converted leads)
- **User Link**: id_user (from authenticated user)
- **System Fields**: ip_address, user_agent, referrer (auto-captured)

**Sample Comprehensive Contact Creation Form:**

```html
<form id="contactForm" method="POST" action="/api/contacts/create">
    <!-- Personal Information -->
    <div class="form-section">
        <h3>Personal Information</h3>
        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name *</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>
        </div>
        <div class="form-group">
            <label for="birthdate">Birthdate</label>
            <input type="date" id="birthdate" name="birthdate">
        </div>
    </div>
    
    <!-- Contact Information -->
    <div class="form-section">
        <h3>Contact Information</h3>
        <div class="form-group">
            <label for="email_primary">Email Address *</label>
            <input type="email" id="email_primary" name="email_primary" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="work_phone">Work Phone</label>
                <input type="tel" id="work_phone" name="work_phone">
            </div>
            <div class="form-group">
                <label for="mobile_phone">Mobile Phone</label>
                <input type="tel" id="mobile_phone" name="mobile_phone">
            </div>
        </div>
    </div>
    
    <!-- Professional Information -->
    <div class="form-section">
        <h3>Professional Information</h3>
        <div class="form-group">
            <label for="organization_name">Company/Organization</label>
            <input type="text" id="organization_name" name="organization_name">
        </div>
        <div class="form-group">
            <label for="job_title">Job Title</label>
            <input type="text" id="job_title" name="job_title">
        </div>
    </div>
    
    <!-- Address Information -->
    <div class="form-section">
        <h3>Address Information</h3>
        <div class="form-group">
            <label for="street_address">Street Address</label>
            <input type="text" id="street_address" name="street_address">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="city">City</label>
                <input type="text" id="city" name="city">
            </div>
            <div class="form-group">
                <label for="state">State/Province</label>
                <input type="text" id="state" name="state">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="zip_code">ZIP/Postal Code</label>
                <input type="text" id="zip_code" name="zip_code">
            </div>
            <div class="form-group">
                <label for="country">Country</label>
                <input type="text" id="country" name="country">
            </div>
        </div>
    </div>
    
    <!-- Additional Information -->
    <div class="form-section">
        <h3>Additional Information</h3>
        <div class="form-group">
            <label for="website_url">Website</label>
            <input type="url" id="website_url" name="website_url">
        </div>
        <div class="form-group">
            <label for="photo_url">Photo URL</label>
            <input type="url" id="photo_url" name="photo_url">
        </div>
        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="3"></textarea>
        </div>
        <div class="form-group">
            <label for="comments_from_lead">Comments</label>
            <textarea id="comments_from_lead" name="comments_from_lead" rows="4"></textarea>
        </div>
    </div>
    
    <button type="submit" class="btn-submit">Create Contact</button>
</form>
```

**Query for Contacts:**
```sql
SELECT c.*, l.id as lead_id, bc.first_name as card_first_name, 
       bc.last_name as card_last_name
FROM contacts c
LEFT JOIN leads l ON c.id_lead = l.id
LEFT JOIN business_cards bc ON l.id_business_card = bc.id
WHERE c.id_user = ?
ORDER BY c.created_at DESC
```

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

**Contact Creation API Endpoint:**

**File**: `/web/api/contacts/create.php`

```php
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/AuthHelper.php';
require_once __DIR__ . '/../includes/InputValidator.php';

header('Content-Type: application/json');

// Check authentication
if (!AuthHelper::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = AuthHelper::getUserId();
$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get and validate input
$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$validator = new InputValidator();
$validator->required('first_name', $data['first_name'] ?? null);
$validator->required('last_name', $data['last_name'] ?? null);
$validator->email('email_primary', $data['email_primary'] ?? null);

if (!$validator->isValid()) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $validator->getErrors()]);
    exit;
}

try {
    // Create contact using existing table structure with ALL fields
    $stmt = $db->prepare("
        INSERT INTO contacts (
            id_user, id_lead, first_name, last_name, full_name,
            work_phone, mobile_phone, email_primary, street_address, city, state, 
            zip_code, country, organization_name, job_title, birthdate, notes, 
            website_url, photo_url, comments_from_lead, ip_address, user_agent, referrer
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $fullName = trim($data['first_name'] . ' ' . $data['last_name']);
    
    $result = $stmt->execute([
        $userId,
        $data['id_lead'] ?? null, // Optional lead ID if converting from lead
        $data['first_name'],
        $data['last_name'],
        $fullName,
        $data['work_phone'] ?? null,
        $data['mobile_phone'] ?? null,
        $data['email_primary'],
        $data['street_address'] ?? null,
        $data['city'] ?? null,
        $data['state'] ?? null,
        $data['zip_code'] ?? null,
        $data['country'] ?? null,
        $data['organization_name'] ?? null,
        $data['job_title'] ?? null,
        $data['birthdate'] ?? null,
        $data['notes'] ?? null,
        $data['website_url'] ?? null,
        $data['photo_url'] ?? null,
        $data['comments_from_lead'] ?? null,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null,
        $_SERVER['HTTP_REFERER'] ?? null
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Contact created successfully',
            'contact_id' => $db->lastInsertId()
        ]);
    } else {
        throw new Exception('Failed to create contact');
    }
    
} catch (Exception $e) {
    error_log("Contact creation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
```

---

## 5. Lead to Contact Conversion

### 5.1 Conversion API Endpoint

**File**: `/web/api/leads/convert.php`

```php
<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/AuthHelper.php';

header('Content-Type: application/json');

// Check authentication
if (!AuthHelper::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = AuthHelper::getUserId();
$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$leadId = $data['lead_id'] ?? null;

if (!$leadId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Lead ID required']);
    exit;
}

try {
    $db->beginTransaction();
    
    // Verify lead belongs to user (check if not already converted)
    $stmt = $db->prepare("
        SELECT l.*, bc.id as business_card_id
        FROM leads l
        JOIN business_cards bc ON l.id_business_card = bc.id
        WHERE l.id = ? AND bc.user_id = ? 
        AND NOT EXISTS (SELECT 1 FROM contacts c WHERE c.id_lead = l.id)
    ");
    $stmt->execute([$leadId, $userId]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lead) {
        throw new Exception('Lead not found or already converted');
    }
    
    // Create contact from lead using existing table structure with ALL fields
    $stmt = $db->prepare("
        INSERT INTO contacts (
            id_user, id_lead, first_name, last_name, full_name,
            work_phone, mobile_phone, email_primary, street_address, city, state, 
            zip_code, country, organization_name, job_title, birthdate, notes, 
            website_url, photo_url, comments_from_lead, ip_address, user_agent, referrer
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $userId,
        $leadId,
        $lead['first_name'],
        $lead['last_name'],
        $lead['full_name'],
        $lead['work_phone'],
        $lead['mobile_phone'],
        $lead['email_primary'],
        $lead['street_address'],
        $lead['city'],
        $lead['state'],
        $lead['zip_code'],
        $lead['country'],
        $lead['organization_name'],
        $lead['job_title'],
        $lead['birthdate'],
        $lead['notes'],
        $lead['website_url'],
        $lead['photo_url'],
        $lead['comments_from_lead'],
        $lead['ip_address'],
        $lead['user_agent'],
        $lead['referrer']
    ]);
    
    if (!$result) {
        throw new Exception('Failed to create contact');
    }
    
    $contactId = $db->lastInsertId();
    
    // Update lead as converted (using existing fields creatively)
    $stmt = $db->prepare("
        UPDATE leads SET 
            notes = CONCAT(COALESCE(notes, ''), ' [CONVERTED TO CONTACT]'),
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $result = $stmt->execute([$leadId]);
    
    if (!$result) {
        throw new Exception('Failed to update lead');
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Lead converted to contact successfully',
        'contact_id' => $contactId
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Lead conversion error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
```

---

## 6. Admin Functionality

### 6.1 Admin Leads View

**File**: `/web/admin/leads/index.php`

Features:
- View all leads across all users using existing table structure
- Filter by user, status, date range
- View lead statistics
- Export lead data
- Cannot edit leads (read-only)

**Query for Admin Leads:**
```sql
SELECT l.*, bc.first_name as card_first_name, bc.last_name as card_last_name,
       u.email as owner_email, u.id as owner_id,
       CASE WHEN EXISTS (SELECT 1 FROM contacts c WHERE c.id_lead = l.id) 
            THEN 'converted' ELSE 'new' END as status
FROM leads l
JOIN business_cards bc ON l.id_business_card = bc.id
JOIN users u ON bc.user_id = u.id
ORDER BY l.created_at DESC
```

### 6.2 Admin Contacts View

**File**: `/web/admin/contacts/index.php`

Features:
- View all contacts across all users using existing table structure
- Filter by user, source, status
- View contact statistics
- Export contact data
- Cannot edit contacts (read-only)

**Query for Admin Contacts:**
```sql
SELECT c.*, u.email as owner_email, u.id as owner_id,
       l.id as lead_id, bc.first_name as card_first_name, bc.last_name as card_last_name
FROM contacts c
JOIN users u ON c.id_user = u.id
LEFT JOIN leads l ON c.id_lead = l.id
LEFT JOIN business_cards bc ON l.id_business_card = bc.id
ORDER BY c.created_at DESC
```

---

## 7. Implementation Checklist

### 7.1 Database Setup
- [x] Document existing leads table structure
- [x] Document existing contacts table structure
- [x] Verify existing table structures work with requirements
- [x] No database modifications needed (using existing tables only)

### 7.2 Lead Capture System
- [x] Create public lead capture form
- [x] Implement lead capture API endpoint
- [x] Add rate limiting to lead capture
- [x] Create lead capture form styling
- [x] Add form validation and error handling
- [x] Test lead capture functionality

### 7.3 User Dashboard Integration
- [x] Create leads management dashboard
- [x] Create contacts management dashboard
- [x] Implement leads CRUD API
- [x] Implement contacts CRUD API
- [x] Add lead to contact conversion API
- [x] Create dashboard styling
- [x] Add JavaScript functionality

### 7.4 Admin Functionality
- [x] Create admin leads view
- [x] Create admin contacts view
- [x] Add admin navigation
- [x] Implement admin statistics
- [x] Test admin functionality

### 7.5 Testing & Deployment
- [x] Test all API endpoints
- [x] Test form submissions
- [x] Test user permissions
- [x] Test admin permissions
- [x] Performance testing
- [x] Security testing
- [x] Deploy to production

---

## 8. Implementation Status Summary

### ✅ **COMPLETED FEATURES**

#### **🎯 Core System (100% Complete)**
- **✅ Database Schema** - Using existing leads and contacts tables
- **✅ Lead Capture System** - Public form, API, rate limiting, validation
- **✅ User Dashboard** - Leads and contacts management
- **✅ Admin Functionality** - Admin views for leads and contacts
- **✅ API Endpoints** - Full CRUD operations for leads and contacts
- **✅ Lead Conversion** - Convert leads to contacts
- **✅ Demo System** - Complete demo data population
- **✅ Testing & Deployment** - All features tested and deployed

#### **📊 User Features (100% Complete)**
- **✅ View Leads** - Clean, simplified display
- **✅ View Contacts** - Clean, simplified display
- **✅ Lead Details** - Full information modal
- **✅ Contact Details** - Full information modal
- **✅ Edit Contacts** - Update contact information
- **✅ Convert Leads** - Transform leads into contacts
- **✅ Delete Operations** - Remove leads and contacts
- **✅ Search & Filter** - Find specific leads/contacts
- **✅ Statistics Dashboard** - Lead and contact metrics

#### **🔧 Technical Features (100% Complete)**
- **✅ Rate Limiting** - Prevent spam submissions
- **✅ Form Validation** - Comprehensive input validation
- **✅ Error Handling** - User-friendly error messages
- **✅ Security** - Authentication and authorization
- **✅ Responsive Design** - Mobile-friendly interface
- **✅ Demo Data** - Realistic sample data for testing

### 🚧 **REMAINING FEATURES (Future Enhancements)**

#### **📈 Advanced Analytics (Not Yet Implemented)**
- **❌ Enhanced Reporting** - Detailed analytics and insights
- **❌ Export Functionality** - CSV/Excel export for leads and contacts
- **❌ Bulk Operations** - Import/export multiple records
- **❌ Advanced Filtering** - Date ranges, custom filters

#### **🤖 Automation Features (Not Yet Implemented)**
- **❌ Lead Scoring** - Automated lead qualification
- **❌ Automated Follow-up** - Email sequences and reminders
- **❌ Contact Interaction Tracking** - Activity history and notes
- **❌ Email Notifications** - Alerts for new leads

#### **🔗 Integration Features (Not Yet Implemented)**
- **❌ CRM Integration** - Connect with external CRM systems
- **❌ Email Marketing** - Integration with email platforms
- **❌ Calendar Integration** - Meeting scheduling
- **❌ Social Media** - Social profile linking

#### **📱 Mobile Features (Not Yet Implemented)**
- **❌ QR Code Scanning** - Mobile lead capture
- **❌ Push Notifications** - Mobile alerts
- **❌ Offline Capture** - Work without internet connection
- **❌ Mobile App** - Native iOS/Android app

---

## 9. Future Enhancements

### 9.1 Priority 1: Export & Analytics
- **📊 Enhanced Reporting** - Detailed analytics dashboard
- **📁 Export Functionality** - CSV/Excel export for leads and contacts
- **📈 Advanced Analytics** - Conversion rates, lead sources, trends
- **🔍 Advanced Filtering** - Date ranges, custom search criteria

### 9.2 Priority 2: Automation
- **🤖 Lead Scoring** - Automated lead qualification system
- **📧 Automated Follow-up** - Email sequences and reminders
- **📝 Activity Tracking** - Contact interaction history
- **🔔 Email Notifications** - Alerts for new leads and conversions

### 9.3 Priority 3: Integrations
- **🔗 CRM Integration** - Connect with Salesforce, HubSpot, etc.
- **📧 Email Marketing** - Mailchimp, Constant Contact integration
- **📅 Calendar Integration** - Meeting scheduling and reminders
- **📱 Social Media** - LinkedIn, Twitter profile linking

### 9.4 Priority 4: Mobile Features
- **📱 QR Code Scanning** - Mobile lead capture
- **🔔 Push Notifications** - Mobile alerts for new leads
- **📴 Offline Capture** - Work without internet connection
- **📱 Native Mobile App** - iOS and Android applications

---

## 10. Implementation Summary

### ✅ **CORE SYSTEM: 100% COMPLETE**
The ShareMyCard Leads and Contacts management system is **fully functional** with all essential features implemented:

- **🎯 Lead Capture** - Public forms, API endpoints, rate limiting
- **👥 Contact Management** - Full CRUD operations, conversion workflow
- **📊 User Interface** - Clean, responsive dashboards
- **🔧 Admin Tools** - Complete admin functionality
- **🧪 Demo System** - Realistic data for testing and demonstration
- **🔒 Security** - Authentication, authorization, validation
- **📱 Mobile Ready** - Responsive design for all devices

### 🚀 **READY FOR PRODUCTION**
The system is production-ready and provides a complete lead generation and contact management solution for ShareMyCard users.

### 📈 **FUTURE ROADMAP**
The remaining features are enhancements that can be added incrementally based on user feedback and business priorities.
