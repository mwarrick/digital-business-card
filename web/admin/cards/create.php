<?php
/**
 * Create Enhanced Business Card (matches iOS app fields)
 */

require_once __DIR__ . '/../includes/AdminAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';

AdminAuth::requireAuth();

$error = '';
$success = '';

// Handle duplicate card functionality
$duplicateCardId = $_GET['duplicate'] ?? '';
$duplicateCard = null;
$duplicateEmails = [];
$duplicatePhones = [];
$duplicateWebsites = [];
$duplicateAddress = null;

if (!empty($duplicateCardId)) {
    $db = Database::getInstance();
    
    // Get the card to duplicate (must belong to the current admin user)
    $duplicateCard = $db->querySingle(
        "SELECT * FROM business_cards WHERE id = ? AND user_id = ? AND is_active = 1",
        [$duplicateCardId, AdminAuth::getUserId()]
    );
    
    if ($duplicateCard) {
        // Get additional contact info
        $duplicateEmails = $db->query(
            "SELECT * FROM email_contacts WHERE business_card_id = ? ORDER BY is_primary DESC, created_at ASC",
            [$duplicateCardId]
        );
        
        $duplicatePhones = $db->query(
            "SELECT * FROM phone_contacts WHERE business_card_id = ? ORDER BY created_at ASC",
            [$duplicateCardId]
        );
        
        $duplicateWebsites = $db->query(
            "SELECT * FROM website_links WHERE business_card_id = ? ORDER BY is_primary DESC, created_at ASC",
            [$duplicateCardId]
        );
        
        $duplicateAddress = $db->querySingle(
            "SELECT * FROM addresses WHERE business_card_id = ?",
            [$duplicateCardId]
        );
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $jobTitle = trim($_POST['job_title'] ?? '');
    $companyName = trim($_POST['company_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    // Validation
    if (empty($firstName)) {
        $error = 'First name is required';
    } else if (empty($lastName)) {
        $error = 'Last name is required';
    } else if (empty($phone)) {
        $error = 'Phone number is required';
    } else {
        try {
            $db = Database::getInstance();
            
            // Begin transaction
            $db->beginTransaction();
            
            // Generate UUID for card
            $cardId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            
            // Insert business card
            $db->execute(
                "INSERT INTO business_cards (id, user_id, first_name, last_name, phone_number, job_title, company_name, bio, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [
                    $cardId,
                    AdminAuth::getUserId(),
                    $firstName,
                    $lastName,
                    $phone,
                    $jobTitle ?: null,
                    $companyName ?: null,
                    $bio ?: null
                ]
            );
            
            // Insert additional emails
            if (!empty($_POST['emails'])) {
                foreach ($_POST['emails'] as $email) {
                    if (!empty($email['email'])) {
                        $emailId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                        );
                        $isPrimary = isset($email['is_primary']) ? 1 : 0;
                        $db->execute(
                            "INSERT INTO email_contacts (id, business_card_id, email, type, label, is_primary) VALUES (?, ?, ?, ?, ?, ?)",
                            [$emailId, $cardId, $email['email'], $email['type'] ?? 'work', $email['label'] ?: null, $isPrimary]
                        );
                    }
                }
            }
            
            // Insert additional phones
            if (!empty($_POST['phones'])) {
                foreach ($_POST['phones'] as $phoneContact) {
                    if (!empty($phoneContact['phone'])) {
                        $phoneId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                        );
                        $db->execute(
                            "INSERT INTO phone_contacts (id, business_card_id, phone_number, type, label) VALUES (?, ?, ?, ?, ?)",
                            [$phoneId, $cardId, $phoneContact['phone'], $phoneContact['type'] ?? 'mobile', $phoneContact['label'] ?: null]
                        );
                    }
                }
            }
            
            // Insert website links
            if (!empty($_POST['websites'])) {
                foreach ($_POST['websites'] as $website) {
                    if (!empty($website['url'])) {
                        $websiteId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                        );
                        $isPrimary = isset($website['is_primary']) ? 1 : 0;
                        $db->execute(
                            "INSERT INTO website_links (id, business_card_id, name, url, description, is_primary) VALUES (?, ?, ?, ?, ?, ?)",
                            [$websiteId, $cardId, $website['name'] ?: 'Website', $website['url'], $website['description'] ?: null, $isPrimary]
                        );
                    }
                }
            }
            
            // Insert address
            $street = trim($_POST['street'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $state = trim($_POST['state'] ?? '');
            $zipCode = trim($_POST['zip_code'] ?? '');
            $country = trim($_POST['country'] ?? '');
            
            if (!empty($street) || !empty($city) || !empty($state) || !empty($zipCode) || !empty($country)) {
                $addressId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                $db->execute(
                    "INSERT INTO addresses (id, business_card_id, street, city, state, zip_code, country) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$addressId, $cardId, $street ?: null, $city ?: null, $state ?: null, $zipCode ?: null, $country ?: null]
                );
            }
            
            // Commit transaction
            $db->commit();
            
            // Redirect to my cards page
            header('Location: /admin/my-cards.php?created=1');
            exit;
            
        } catch (Exception $e) {
            $db->rollback();
            error_log("Card creation error: " . $e->getMessage());
            $error = 'Failed to create business card: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Business Card - ShareMyCard</title>
    <link rel="stylesheet" href="/admin/includes/admin-style.css">
    <style>
        .form-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .form-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-section h2 {
            font-size: 20px;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group label .required {
            color: #e74c3c;
        }
        
        .form-group input[type="text"],
        .form-group input[type="tel"],
        .form-group input[type="email"],
        .form-group input[type="url"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-group .help-text {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        /* Dynamic fields */
        .dynamic-group {
            margin-top: 20px;
        }
        
        .dynamic-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            position: relative;
        }
        
        .dynamic-item-fields {
            display: grid;
            gap: 15px;
        }
        
        .dynamic-item-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 10px;
            align-items: end;
        }
        
        .btn-remove {
            padding: 10px 16px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-remove:hover {
            background: #c0392b;
        }
        
        .btn-add {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .btn-add:hover {
            background: #5568d3;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        .btn {
            padding: 14px 32px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #f5f5f5;
            color: #666;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        
        @media (max-width: 768px) {
            .form-row,
            .dynamic-item-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="main-container">
        <header class="page-header">
            <h1><?php echo $duplicateCard ? 'Duplicate Business Card' : 'Create New Business Card'; ?></h1>
            <p><?php echo $duplicateCard ? 'Edit the copied information and save as a new card' : 'Complete contact information matching iOS app'; ?></p>
        </header>
        
        <div class="form-container">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="cardForm">
                <div class="form-section">
                    <h2>📋 Basic Information</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">
                                First Name <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="first_name" 
                                name="first_name" 
                                value="<?php echo htmlspecialchars($_POST['first_name'] ?? ($duplicateCard['first_name'] ?? '')); ?>"
                                required
                                autofocus
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">
                                Last Name <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="last_name" 
                                name="last_name" 
                                value="<?php echo htmlspecialchars($_POST['last_name'] ?? ($duplicateCard['last_name'] ?? '')); ?>"
                                required
                            >
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">
                            Primary Phone Number <span class="required">*</span>
                        </label>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            value="<?php echo htmlspecialchars($_POST['phone'] ?? ($duplicateCard['phone_number'] ?? '')); ?>"
                            placeholder="+1 (555) 123-4567"
                            required
                        >
                        <div class="help-text">This will be your primary contact number</div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>💼 Professional Information</h2>
                    
                    <div class="form-group">
                        <label for="job_title">Job Title</label>
                        <input 
                            type="text" 
                            id="job_title" 
                            name="job_title" 
                            value="<?php echo htmlspecialchars($_POST['job_title'] ?? ($duplicateCard['job_title'] ?? '')); ?>"
                            placeholder="e.g., Senior Developer"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="company_name">Company Name</label>
                        <input 
                            type="text" 
                            id="company_name" 
                            name="company_name" 
                            value="<?php echo htmlspecialchars($_POST['company_name'] ?? ($duplicateCard['company_name'] ?? '')); ?>"
                            placeholder="e.g., Acme Corporation"
                        >
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>📧 Additional Email Addresses</h2>
                    <div id="emailsContainer"></div>
                    <button type="button" class="btn-add" onclick="addEmail()">+ Add Email</button>
                </div>
                
                <div class="form-section">
                    <h2>📱 Additional Phone Numbers</h2>
                    <div id="phonesContainer"></div>
                    <button type="button" class="btn-add" onclick="addPhone()">+ Add Phone</button>
                </div>
                
                <div class="form-section">
                    <h2>🌐 Website Links</h2>
                    <div id="websitesContainer"></div>
                    <button type="button" class="btn-add" onclick="addWebsite()">+ Add Website</button>
                </div>
                
                <div class="form-section">
                    <h2>📍 Address</h2>
                    
                    <div class="form-group">
                        <label for="street">Street Address</label>
                        <input type="text" id="street" name="street" placeholder="123 Main St">
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
                
                <div class="form-section">
                    <h2>ℹ️ About</h2>
                    
                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea 
                            id="bio" 
                            name="bio" 
                            placeholder="Tell people about yourself..."
                        ><?php echo htmlspecialchars($_POST['bio'] ?? ($duplicateCard['bio'] ?? '')); ?></textarea>
                        <div class="help-text">URLs will be automatically converted to links</div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="/admin/my-cards.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Business Card</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let emailCount = 0;
        let phoneCount = 0;
        let websiteCount = 0;
        
        function addEmail() {
            const container = document.getElementById('emailsContainer');
            const div = document.createElement('div');
            div.className = 'dynamic-item';
            div.innerHTML = `
                <div class="dynamic-item-row">
                    <div class="form-group" style="margin: 0;">
                        <input type="email" name="emails[${emailCount}][email]" placeholder="email@example.com">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <select name="emails[${emailCount}][type]">
                            <option value="work">Work</option>
                            <option value="personal">Personal</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <input type="text" name="emails[${emailCount}][label]" placeholder="Label (optional)">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label style="font-size: 12px;">
                            <input type="checkbox" name="emails[${emailCount}][is_primary]" value="1"> Primary
                        </label>
                    </div>
                    <button type="button" class="btn-remove" onclick="this.parentElement.parentElement.remove()">Remove</button>
                </div>
            `;
            container.appendChild(div);
            emailCount++;
        }
        
        function addPhone() {
            const container = document.getElementById('phonesContainer');
            const div = document.createElement('div');
            div.className = 'dynamic-item';
            div.innerHTML = `
                <div class="dynamic-item-row">
                    <div class="form-group" style="margin: 0;">
                        <input type="tel" name="phones[${phoneCount}][phone]" placeholder="+1 (555) 123-4567">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <select name="phones[${phoneCount}][type]">
                            <option value="mobile">Mobile</option>
                            <option value="work">Work</option>
                            <option value="home">Home</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <input type="text" name="phones[${phoneCount}][label]" placeholder="Label (optional)">
                    </div>
                    <button type="button" class="btn-remove" onclick="this.parentElement.parentElement.remove()">Remove</button>
                </div>
            `;
            container.appendChild(div);
            phoneCount++;
        }
        
        function addWebsite() {
            const container = document.getElementById('websitesContainer');
            const div = document.createElement('div');
            div.className = 'dynamic-item';
            div.innerHTML = `
                <div class="dynamic-item-fields">
                    <div class="form-group" style="margin: 0;">
                        <input type="text" name="websites[${websiteCount}][name]" placeholder="Website Name (e.g., LinkedIn, Portfolio)">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr auto; gap: 10px;">
                        <div class="form-group" style="margin: 0;">
                            <input type="url" name="websites[${websiteCount}][url]" placeholder="https://example.com">
                        </div>
                        <button type="button" class="btn-remove" onclick="this.parentElement.parentElement.parentElement.remove()">Remove</button>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <input type="text" name="websites[${websiteCount}][description]" placeholder="Description (optional)">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label style="font-size: 12px;">
                            <input type="checkbox" name="websites[${websiteCount}][is_primary]" value="1"> Primary
                        </label>
                    </div>
                </div>
            `;
            container.appendChild(div);
            websiteCount++;
        }
        
        // Pre-populate form when duplicating a card
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($duplicateCard): ?>
                console.log('🔄 Starting duplicate card pre-population...');
                
                // Pre-populate additional emails
                <?php foreach ($duplicateEmails as $email): ?>
                    console.log('📧 Adding email: <?php echo addslashes($email['email']); ?>');
                    addEmail();
                    setTimeout(function() {
                        const emailContainer = document.getElementById('emailsContainer');
                        const lastEmailItem = emailContainer.lastElementChild;
                        console.log('Email container found:', !!emailContainer);
                        console.log('Last email item found:', !!lastEmailItem);
                        
                        if (lastEmailItem) {
                            // Try multiple selectors to find the elements
                            const emailInput = lastEmailItem.querySelector('input[type="email"]') || 
                                             lastEmailItem.querySelector('input[name*="email"]') ||
                                             lastEmailItem.querySelector('input[name*="[email]"]');
                            const typeSelect = lastEmailItem.querySelector('select[name*="type"]') ||
                                             lastEmailItem.querySelector('select[name*="[type]"]');
                            const labelInput = lastEmailItem.querySelector('input[placeholder*="Label"]') ||
                                             lastEmailItem.querySelector('input[name*="label"]') ||
                                             lastEmailItem.querySelector('input[name*="[label]"]');
                            const primaryCheckbox = lastEmailItem.querySelector('input[type="checkbox"]') ||
                                                  lastEmailItem.querySelector('input[name*="is_primary"]') ||
                                                  lastEmailItem.querySelector('input[name*="[is_primary]"]');
                            
                            console.log('Email input found:', !!emailInput);
                            console.log('Type select found:', !!typeSelect);
                            console.log('Label input found:', !!labelInput);
                            console.log('Primary checkbox found:', !!primaryCheckbox);
                            
                            if (emailInput) {
                                emailInput.value = '<?php echo addslashes($email['email']); ?>';
                                console.log('Email value set to:', emailInput.value);
                            }
                            if (typeSelect) {
                                typeSelect.value = '<?php echo addslashes($email['type']); ?>';
                                console.log('Type value set to:', typeSelect.value);
                            }
                            if (labelInput) {
                                labelInput.value = '<?php echo addslashes($email['label'] ?? ''); ?>';
                                console.log('Label value set to:', labelInput.value);
                            }
                            if (primaryCheckbox && <?php echo $email['is_primary'] ? 'true' : 'false'; ?>) {
                                primaryCheckbox.checked = true;
                                console.log('Primary checkbox checked');
                            }
                            console.log('✅ Email populated successfully');
                        } else {
                            console.log('❌ Last email item not found');
                        }
                    }, 200);
                <?php endforeach; ?>
                
                // Pre-populate additional phones
                <?php foreach ($duplicatePhones as $phone): ?>
                    console.log('📱 Adding phone: <?php echo addslashes($phone['phone_number']); ?>');
                    addPhone();
                    setTimeout(function() {
                        const phoneContainer = document.getElementById('phonesContainer');
                        const lastPhoneItem = phoneContainer.lastElementChild;
                        if (lastPhoneItem) {
                            const phoneInput = lastPhoneItem.querySelector('input[name*="[phone]"]');
                            const typeSelect = lastPhoneItem.querySelector('select[name*="[type]"]');
                            const labelInput = lastPhoneItem.querySelector('input[name*="[label]"]');
                            
                            if (phoneInput) phoneInput.value = '<?php echo addslashes($phone['phone_number']); ?>';
                            if (typeSelect) typeSelect.value = '<?php echo addslashes($phone['type']); ?>';
                            if (labelInput) labelInput.value = '<?php echo addslashes($phone['label'] ?? ''); ?>';
                            console.log('✅ Phone populated successfully');
                        }
                    }, 100);
                <?php endforeach; ?>
                
                // Pre-populate websites
                <?php foreach ($duplicateWebsites as $website): ?>
                    console.log('🌐 Adding website: <?php echo addslashes($website['name']); ?>');
                    addWebsite();
                    setTimeout(function() {
                        const websiteContainer = document.getElementById('websitesContainer');
                        const lastWebsiteItem = websiteContainer.lastElementChild;
                        console.log('Website container found:', !!websiteContainer);
                        console.log('Last website item found:', !!lastWebsiteItem);
                        
                        if (lastWebsiteItem) {
                            // The website form has a different structure - look inside dynamic-item-fields
                            const fieldsContainer = lastWebsiteItem.querySelector('.dynamic-item-fields');
                            console.log('Fields container found:', !!fieldsContainer);
                            
                            if (fieldsContainer) {
                                const nameInput = fieldsContainer.querySelector('input[placeholder*="Website Name"]') ||
                                                fieldsContainer.querySelector('input[name*="name"]') ||
                                                fieldsContainer.querySelector('input[name*="[name]"]');
                                const urlInput = fieldsContainer.querySelector('input[type="url"]') ||
                                               fieldsContainer.querySelector('input[placeholder*="https://"]') ||
                                               fieldsContainer.querySelector('input[name*="url"]') ||
                                               fieldsContainer.querySelector('input[name*="[url]"]');
                                const descInput = fieldsContainer.querySelector('input[placeholder*="Description"]') ||
                                                fieldsContainer.querySelector('input[name*="description"]') ||
                                                fieldsContainer.querySelector('input[name*="[description]"]');
                                const primaryCheckbox = fieldsContainer.querySelector('input[type="checkbox"]') ||
                                                      fieldsContainer.querySelector('input[name*="is_primary"]') ||
                                                      fieldsContainer.querySelector('input[name*="[is_primary]"]');
                                
                                console.log('Name input found:', !!nameInput);
                                console.log('URL input found:', !!urlInput);
                                console.log('Description input found:', !!descInput);
                                console.log('Primary checkbox found:', !!primaryCheckbox);
                                
                                if (nameInput) {
                                    nameInput.value = '<?php echo addslashes($website['name']); ?>';
                                    console.log('Name value set to:', nameInput.value);
                                }
                                if (urlInput) {
                                    urlInput.value = '<?php echo addslashes($website['url']); ?>';
                                    console.log('URL value set to:', urlInput.value);
                                }
                                if (descInput) {
                                    descInput.value = '<?php echo addslashes($website['description'] ?? ''); ?>';
                                    console.log('Description value set to:', descInput.value);
                                }
                                if (primaryCheckbox && <?php echo $website['is_primary'] ? 'true' : 'false'; ?>) {
                                    primaryCheckbox.checked = true;
                                    console.log('Primary checkbox checked');
                                }
                                console.log('✅ Website populated successfully');
                            } else {
                                console.log('❌ Fields container not found');
                            }
                        } else {
                            console.log('❌ Last website item not found');
                        }
                    }, 200);
                <?php endforeach; ?>
                
                // Pre-populate address
                <?php if ($duplicateAddress): ?>
                    console.log('🏠 Pre-populating address...');
                    setTimeout(function() {
                        const streetField = document.getElementById('street');
                        const cityField = document.getElementById('city');
                        const stateField = document.getElementById('state');
                        const zipField = document.getElementById('zip_code');
                        const countryField = document.getElementById('country');
                        
                        if (streetField) streetField.value = '<?php echo addslashes($duplicateAddress['street'] ?? ''); ?>';
                        if (cityField) cityField.value = '<?php echo addslashes($duplicateAddress['city'] ?? ''); ?>';
                        if (stateField) stateField.value = '<?php echo addslashes($duplicateAddress['state'] ?? ''); ?>';
                        if (zipField) zipField.value = '<?php echo addslashes($duplicateAddress['zip_code'] ?? ''); ?>';
                        if (countryField) countryField.value = '<?php echo addslashes($duplicateAddress['country'] ?? ''); ?>';
                        console.log('✅ Address populated successfully');
                    }, 200);
                <?php endif; ?>
                
                console.log('🎉 Duplicate card pre-population complete!');
            <?php endif; ?>
        });
    </script>
</body>
</html>

