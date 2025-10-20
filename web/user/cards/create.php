<?php
/**
 * Create Enhanced Business Card (matches iOS app fields)
 */

require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';

UserAuth::requireAuth();

$user = UserAuth::getUser();
$error = '';
$success = '';

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
                    UserAuth::getUserId(),
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
                        $db->execute(
                            "INSERT INTO email_contacts (id, business_card_id, email, type, label) VALUES (?, ?, ?, ?, ?)",
                            [$emailId, $cardId, $email['email'], $email['type'] ?? 'work', $email['label'] ?: null]
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
                        $db->execute(
                            "INSERT INTO website_links (id, business_card_id, name, url, description) VALUES (?, ?, ?, ?, ?)",
                            [$websiteId, $cardId, $website['name'] ?: 'Website', $website['url'], $website['description'] ?: null]
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
            
            // Redirect to dashboard
            header('Location: /user/dashboard.php?created=1');
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
    <link rel="stylesheet" href="/user/includes/user-style.css">
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
    <nav class="navbar">
        <div class="nav-brand">📱 ShareMyCard</div>
        <div class="nav-links">
            <a href="/user/dashboard.php" class="nav-link">Dashboard</a>
            <a href="/user/cards/create.php" class="nav-link active">Create Card</a>
            <?php if ($user['is_admin']): ?>
                <a href="/admin/dashboard.php" class="nav-link" style="background: rgba(255,255,255,0.2);">⚙️ Admin Panel</a>
            <?php endif; ?>
            <a href="https://github.com/mwarrick/digital-business-card/issues" target="_blank" class="nav-link">🐛 Report Issues</a>
            <a href="/user/logout.php" class="nav-link">Logout</a>
        </div>
    </nav>
    
    <div class="main-container">
        <header class="page-header">
            <h1>Create New Business Card</h1>
            <p>Complete contact information matching iOS app</p>
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
                                value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
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
                                value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
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
                            value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
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
                            value="<?php echo htmlspecialchars($_POST['job_title'] ?? ''); ?>"
                            placeholder="e.g., Senior Developer"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="company_name">Company Name</label>
                        <input 
                            type="text" 
                            id="company_name" 
                            name="company_name" 
                            value="<?php echo htmlspecialchars($_POST['company_name'] ?? ''); ?>"
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
                        ><?php echo htmlspecialchars($_POST['bio'] ?? ''); ?></textarea>
                        <div class="help-text">URLs will be automatically converted to links</div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="/user/dashboard.php" class="btn btn-secondary">Cancel</a>
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
                </div>
            `;
            container.appendChild(div);
            websiteCount++;
        }
    </script>
</body>
</html>

