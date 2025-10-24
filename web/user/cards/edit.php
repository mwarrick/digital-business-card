<?php
/**
 * Edit Enhanced Business Card (matches iOS app fields)
 */

require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../../includes/themes.php';

UserAuth::requireAuth();

$user = UserAuth::getUser();
$error = '';
$success = '';
$cardId = $_GET['id'] ?? '';

if (empty($cardId)) {
    header('Location: /user/dashboard.php');
    exit;
}

$db = Database::getInstance();

// Get existing card
$card = $db->querySingle(
    "SELECT * FROM business_cards WHERE id = ? AND user_id = ? AND is_active = 1",
    [$cardId, UserAuth::getUserId()]
);

if (!$card) {
    header('Location: /user/dashboard.php?error=not_found');
    exit;
}

// Get existing emails
$emails = $db->query(
    "SELECT * FROM email_contacts WHERE business_card_id = ? ORDER BY is_primary DESC, created_at ASC",
    [$cardId]
);

// Get existing phones
$phones = $db->query(
    "SELECT * FROM phone_contacts WHERE business_card_id = ? ORDER BY created_at ASC",
    [$cardId]
);

// Get existing websites
$websites = $db->query(
    "SELECT * FROM website_links WHERE business_card_id = ? ORDER BY is_primary DESC, created_at ASC",
    [$cardId]
);

// Get existing address
$address = $db->querySingle(
    "SELECT * FROM addresses WHERE business_card_id = ?",
    [$cardId]
);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $jobTitle = trim($_POST['job_title'] ?? '');
    $companyName = trim($_POST['company_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $theme = trim($_POST['theme'] ?? 'professional-blue');
    
    // Validation
    if (empty($firstName)) {
        $error = 'First name is required';
    } else if (empty($lastName)) {
        $error = 'Last name is required';
    } else if (empty($phone)) {
        $error = 'Phone number is required';
    } else {
        try {
            // Begin transaction
            $db->beginTransaction();
            
            // Update business card
            $db->execute(
                "UPDATE business_cards SET 
                    first_name = ?, 
                    last_name = ?, 
                    phone_number = ?, 
                    job_title = ?, 
                    company_name = ?, 
                    bio = ?,
                    theme = ?,
                    updated_at = NOW()
                WHERE id = ? AND user_id = ?",
                [
                    $firstName,
                    $lastName,
                    $phone,
                    $jobTitle ?: null,
                    $companyName ?: null,
                    $bio ?: null,
                    $theme,
                    $cardId,
                    UserAuth::getUserId()
                ]
            );
            
            // Delete existing emails, phones, websites
            $db->execute("DELETE FROM email_contacts WHERE business_card_id = ?", [$cardId]);
            $db->execute("DELETE FROM phone_contacts WHERE business_card_id = ?", [$cardId]);
            $db->execute("DELETE FROM website_links WHERE business_card_id = ?", [$cardId]);
            
            // Insert new emails
            if (!empty($_POST['emails'])) {
                $hasPrimaryEmail = false;
                foreach ($_POST['emails'] as $email) {
                    if (!empty($email['email'])) {
                        $isPrimary = isset($email['is_primary']) && $email['is_primary'] === '1';
                        if ($isPrimary && $hasPrimaryEmail) {
                            $isPrimary = false; // Only allow one primary
                        } elseif ($isPrimary) {
                            $hasPrimaryEmail = true;
                        }
                        
                        $emailId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                        );
                        $db->execute(
                            "INSERT INTO email_contacts (id, business_card_id, email, type, label, is_primary) VALUES (?, ?, ?, ?, ?, ?)",
                            [$emailId, $cardId, $email['email'], $email['type'] ?? 'work', $email['label'] ?: null, $isPrimary]
                        );
                    }
                }
            }
            
            // Insert new phones
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
            
            // Insert new websites
            if (!empty($_POST['websites'])) {
                $hasPrimaryWebsite = false;
                foreach ($_POST['websites'] as $website) {
                    if (!empty($website['url'])) {
                        $isPrimary = isset($website['is_primary']) && $website['is_primary'] === '1';
                        if ($isPrimary && $hasPrimaryWebsite) {
                            $isPrimary = false; // Only allow one primary
                        } elseif ($isPrimary) {
                            $hasPrimaryWebsite = true;
                        }
                        
                        $websiteId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                        );
                        $db->execute(
                            "INSERT INTO website_links (id, business_card_id, name, url, description, is_primary) VALUES (?, ?, ?, ?, ?, ?)",
                            [$websiteId, $cardId, $website['name'] ?: 'Website', $website['url'], $website['description'] ?: null, $isPrimary]
                        );
                    }
                }
            }
            
            // Update or insert address
            $street = trim($_POST['street'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $state = trim($_POST['state'] ?? '');
            $zipCode = trim($_POST['zip_code'] ?? '');
            $country = trim($_POST['country'] ?? '');
            
            if ($address) {
                // Update existing address
                $db->execute(
                    "UPDATE addresses SET street = ?, city = ?, state = ?, zip_code = ?, country = ? WHERE business_card_id = ?",
                    [$street ?: null, $city ?: null, $state ?: null, $zipCode ?: null, $country ?: null, $cardId]
                );
                
                // If all fields are empty, delete the address
                if (empty($street) && empty($city) && empty($state) && empty($zipCode) && empty($country)) {
                    $db->execute("DELETE FROM addresses WHERE business_card_id = ?", [$cardId]);
                }
            } else {
                // Insert new address if at least one field is filled
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
            }
            
            // Handle image uploads
            $uploadedImages = [];
            $storageDir = __DIR__ . '/../../storage/media/';
            
            // Ensure storage directory exists
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0755, true);
            }
            
            // Process each image type
            $imageFields = [
                'profile_photo' => 'profile_photo_path',
                'company_logo' => 'company_logo_path',
                'cover_graphic' => 'cover_graphic_path'
            ];
            
            foreach ($imageFields as $fileKey => $dbColumn) {
                if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES[$fileKey];
                    
                    // Validate file type
                    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);
                    
                    if (!in_array($mimeType, $allowedTypes)) {
                        throw new Exception("Invalid file type for $fileKey. Only JPG, PNG, GIF, and WebP allowed.");
                    }
                    
                    // Validate file size (25MB max)
                    if ($file['size'] > 25 * 1024 * 1024) {
                        throw new Exception("File too large for $fileKey. Maximum 25MB.");
                    }
                    
                    // Generate unique filename
                    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $filename = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                    ) . '.' . $extension;
                    
                    // Move uploaded file
                    $destination = $storageDir . $filename;
                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        $uploadedImages[$dbColumn] = $filename;
                        
                        // Delete old image if it exists
                        $oldPath = $card[$dbColumn];
                        if (!empty($oldPath) && file_exists($storageDir . $oldPath)) {
                            @unlink($storageDir . $oldPath);
                        }
                    }
                }
            }
            
            // Update image paths in database if any were uploaded
            if (!empty($uploadedImages)) {
                $updateFields = [];
                $updateValues = [];
                
                foreach ($uploadedImages as $column => $filename) {
                    $updateFields[] = "$column = ?";
                    $updateValues[] = $filename;
                }
                
                $updateValues[] = $cardId;
                $updateValues[] = UserAuth::getUserId();
                
                $db->execute(
                    "UPDATE business_cards SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = ? AND user_id = ?",
                    $updateValues
                );
            }
            
            // Commit transaction
            $db->commit();
            
            // Redirect to view
            header('Location: /user/cards/view.php?id=' . urlencode($cardId) . '&updated=1');
            exit;
            
        } catch (Exception $e) {
            $db->rollback();
            error_log("Card update error: " . $e->getMessage());
            $error = 'Failed to update business card: ' . $e->getMessage();
        }
    }
    
    // Reload data after error
    $emails = $db->query(
        "SELECT * FROM email_contacts WHERE business_card_id = ? ORDER BY created_at",
        [$cardId]
    );
    $phones = $db->query(
        "SELECT * FROM phone_contacts WHERE business_card_id = ? ORDER BY created_at",
        [$cardId]
    );
    $websites = $db->query(
        "SELECT * FROM website_links WHERE business_card_id = ? ORDER BY created_at",
        [$cardId]
    );
    $address = $db->querySingle(
        "SELECT * FROM addresses WHERE business_card_id = ?",
        [$cardId]
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Business Card - ShareMyCard</title>
    <link rel="stylesheet" href="/user/includes/user-style.css">
    <style>
        .main-container {
            padding: 20px;
        }
        
        .form-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
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
    <?php include __DIR__ . '/../includes/navigation.php'; ?>
    
    <div class="main-container">
        <header class="page-header">
            <h1>Edit Business Card</h1>
            <p><?php echo htmlspecialchars($card['first_name'] . ' ' . $card['last_name']); ?></p>
        </header>
        
        <div class="form-container">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="cardForm" enctype="multipart/form-data">
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
                                value="<?php echo htmlspecialchars($card['first_name']); ?>"
                                required
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
                                value="<?php echo htmlspecialchars($card['last_name']); ?>"
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
                            value="<?php echo htmlspecialchars($card['phone_number']); ?>"
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
                            value="<?php echo htmlspecialchars($card['job_title'] ?? ''); ?>"
                            placeholder="e.g., Senior Developer"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="company_name">Company Name</label>
                        <input 
                            type="text" 
                            id="company_name" 
                            name="company_name" 
                            value="<?php echo htmlspecialchars($card['company_name'] ?? ''); ?>"
                            placeholder="e.g., Acme Corporation"
                        >
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>📧 Additional Email Addresses</h2>
                    <div id="emailsContainer">
                        <?php foreach ($emails as $index => $email): ?>
                        <div class="dynamic-item">
                            <div class="dynamic-item-row">
                                <div class="form-group" style="margin: 0;">
                                    <input 
                                        type="email" 
                                        name="emails[<?php echo $index; ?>][email]" 
                                        placeholder="email@example.com"
                                        value="<?php echo htmlspecialchars($email['email']); ?>"
                                    >
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <select name="emails[<?php echo $index; ?>][type]">
                                        <option value="work" <?php echo $email['type'] === 'work' ? 'selected' : ''; ?>>Work</option>
                                        <option value="personal" <?php echo $email['type'] === 'personal' ? 'selected' : ''; ?>>Personal</option>
                                        <option value="other" <?php echo $email['type'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <input 
                                        type="text" 
                                        name="emails[<?php echo $index; ?>][label]" 
                                        placeholder="Label (optional)"
                                        value="<?php echo htmlspecialchars($email['label'] ?? ''); ?>"
                                    >
                                </div>
                                <div class="form-group" style="margin: 0; display: flex; align-items: center; gap: 5px;">
                                    <input 
                                        type="radio" 
                                        name="primary_email" 
                                        value="<?php echo $index; ?>"
                                        <?php echo $email['is_primary'] ? 'checked' : ''; ?>
                                        onchange="updatePrimaryEmail(this)"
                                    >
                                    <label style="margin: 0; font-size: 12px; color: #666;">Primary</label>
                                    <input type="hidden" name="emails[<?php echo $index; ?>][is_primary]" value="<?php echo $email['is_primary'] ? '1' : '0'; ?>">
                                </div>
                                <button type="button" class="btn-remove" onclick="this.parentElement.parentElement.remove()">Remove</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn-add" onclick="addEmail()">+ Add Email</button>
                </div>
                
                <div class="form-section">
                    <h2>📱 Additional Phone Numbers</h2>
                    <div id="phonesContainer">
                        <?php foreach ($phones as $index => $phone): ?>
                        <div class="dynamic-item">
                            <div class="dynamic-item-row">
                                <div class="form-group" style="margin: 0;">
                                    <input 
                                        type="tel" 
                                        name="phones[<?php echo $index; ?>][phone]" 
                                        placeholder="+1 (555) 123-4567"
                                        value="<?php echo htmlspecialchars($phone['phone_number']); ?>"
                                    >
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <select name="phones[<?php echo $index; ?>][type]">
                                        <option value="mobile" <?php echo $phone['type'] === 'mobile' ? 'selected' : ''; ?>>Mobile</option>
                                        <option value="work" <?php echo $phone['type'] === 'work' ? 'selected' : ''; ?>>Work</option>
                                        <option value="home" <?php echo $phone['type'] === 'home' ? 'selected' : ''; ?>>Home</option>
                                        <option value="other" <?php echo $phone['type'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <input 
                                        type="text" 
                                        name="phones[<?php echo $index; ?>][label]" 
                                        placeholder="Label (optional)"
                                        value="<?php echo htmlspecialchars($phone['label'] ?? ''); ?>"
                                    >
                                </div>
                                <button type="button" class="btn-remove" onclick="this.parentElement.parentElement.remove()">Remove</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn-add" onclick="addPhone()">+ Add Phone</button>
                </div>
                
                <div class="form-section">
                    <h2>🌐 Website Links</h2>
                    <div id="websitesContainer">
                        <?php foreach ($websites as $index => $website): ?>
                        <div class="dynamic-item">
                            <div class="dynamic-item-fields">
                                <div class="form-group" style="margin: 0;">
                                    <input 
                                        type="text" 
                                        name="websites[<?php echo $index; ?>][name]" 
                                        placeholder="Website Name (e.g., LinkedIn, Portfolio)"
                                        value="<?php echo htmlspecialchars($website['name']); ?>"
                                    >
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr auto; gap: 10px;">
                                    <div class="form-group" style="margin: 0;">
                                        <input 
                                            type="url" 
                                            name="websites[<?php echo $index; ?>][url]" 
                                            placeholder="https://example.com"
                                            value="<?php echo htmlspecialchars($website['url']); ?>"
                                        >
                                    </div>
                                    <button type="button" class="btn-remove" onclick="this.parentElement.parentElement.parentElement.remove()">Remove</button>
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <input 
                                        type="text" 
                                        name="websites[<?php echo $index; ?>][description]" 
                                        placeholder="Description (optional)"
                                        value="<?php echo htmlspecialchars($website['description'] ?? ''); ?>"
                                    >
                                </div>
                                <div class="form-group" style="margin: 0; display: flex; align-items: center; gap: 5px;">
                                    <input 
                                        type="radio" 
                                        name="primary_website" 
                                        value="<?php echo $index; ?>"
                                        <?php echo $website['is_primary'] ? 'checked' : ''; ?>
                                        onchange="updatePrimaryWebsite(this)"
                                    >
                                    <label style="margin: 0; font-size: 12px; color: #666;">Primary</label>
                                    <input type="hidden" name="websites[<?php echo $index; ?>][is_primary]" value="<?php echo $website['is_primary'] ? '1' : '0'; ?>">
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn-add" onclick="addWebsite()">+ Add Website</button>
                </div>
                
                <div class="form-section">
                    <h2>📍 Address</h2>
                    
                    <div class="form-group">
                        <label for="street">Street Address</label>
                        <input 
                            type="text" 
                            id="street" 
                            name="street" 
                            placeholder="123 Main St"
                            value="<?php echo htmlspecialchars($address['street'] ?? ''); ?>"
                        >
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City</label>
                            <input 
                                type="text" 
                                id="city" 
                                name="city"
                                value="<?php echo htmlspecialchars($address['city'] ?? ''); ?>"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="state">State/Province</label>
                            <input 
                                type="text" 
                                id="state" 
                                name="state"
                                value="<?php echo htmlspecialchars($address['state'] ?? ''); ?>"
                            >
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="zip_code">ZIP/Postal Code</label>
                            <input 
                                type="text" 
                                id="zip_code" 
                                name="zip_code"
                                value="<?php echo htmlspecialchars($address['zip_code'] ?? ''); ?>"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="country">Country</label>
                            <input 
                                type="text" 
                                id="country" 
                                name="country"
                                value="<?php echo htmlspecialchars($address['country'] ?? ''); ?>"
                            >
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
                        ><?php echo htmlspecialchars($card['bio'] ?? ''); ?></textarea>
                        <div class="help-text">URLs will be automatically converted to links</div>
                    </div>
                </div>
                
                <!-- Theme Section -->
                <div class="form-section">
                    <h2>🎨 Card Theme</h2>
                    
                    <div class="form-group">
                        <label for="theme">Visual Theme</label>
                        <select id="theme" name="theme" style="font-family: -apple-system, sans-serif;">
                            <?php 
                            $themes = getThemes();
                            $currentTheme = $card['theme'] ?? 'professional-blue';
                            foreach ($themes as $themeKey => $themeData): 
                            ?>
                                <option value="<?php echo $themeKey; ?>" 
                                    <?php echo ($themeKey === $currentTheme) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($themeData['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="help-text">Choose a color scheme for your business card</div>
                    </div>
                    
                    <!-- Theme Preview -->
                    <div id="theme-preview" style="margin-top: 20px; padding: 20px; border-radius: 12px; text-align: center;">
                        <div style="font-size: 18px; font-weight: 600; margin-bottom: 10px;">Preview</div>
                        <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; max-width: 300px; margin: 0 auto;">
                            <?php foreach ($themes as $themeKey => $themeData): ?>
                                <div class="theme-swatch" data-theme="<?php echo $themeKey; ?>" 
                                     style="width: 60px; height: 60px; border-radius: 8px; cursor: pointer; border: 3px solid transparent; background: linear-gradient(135deg, <?php echo $themeData['primary_color']; ?> 0%, <?php echo $themeData['secondary_color']; ?> 100%);"
                                     title="<?php echo htmlspecialchars($themeData['name']); ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Media Section -->
                <div class="form-section">
                    <h2>🖼️ Media</h2>
                    
                    <div class="form-group">
                        <label for="profile_photo">Profile Photo</label>
                        <?php if (!empty($card['profile_photo_path'])): ?>
                            <div style="margin-bottom: 10px;">
                                <img src="/api/media/view?filename=<?php echo urlencode($card['profile_photo_path']); ?>" 
                                     alt="Current profile photo" 
                                     style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 3px solid #4CAF50;">
                                <p style="font-size: 12px; color: #666; margin-top: 5px;">Current photo (upload new to replace)</p>
                            </div>
                        <?php endif; ?>
                        <input type="file" id="profile_photo" name="profile_photo" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                        <div class="help-text">Square image, max 25MB (JPG, PNG, GIF, WebP)</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="company_logo">Company Logo</label>
                        <?php if (!empty($card['company_logo_path'])): ?>
                            <div style="margin-bottom: 10px;">
                                <div style="width: 200px; height: 200px; background: #f8f9fa; border: 2px solid #ddd; border-radius: 8px; display: flex; align-items: center; justify-content: center; padding: 20px;">
                                    <img src="/api/media/view?filename=<?php echo urlencode($card['company_logo_path']); ?>" 
                                         alt="Current company logo" 
                                         style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                </div>
                                <p style="font-size: 12px; color: #666; margin-top: 5px;">Current logo (upload new to replace)</p>
                            </div>
                        <?php endif; ?>
                        <input type="file" id="company_logo" name="company_logo" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                        <div class="help-text">Square image, max 25MB (JPG, PNG, GIF, WebP)</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="cover_graphic">Cover Graphic</label>
                        <?php if (!empty($card['cover_graphic_path'])): ?>
                            <div style="margin-bottom: 10px;">
                                <div style="width: 100%; max-width: 500px; height: 150px; background: #f8f9fa; border: 2px solid #ddd; border-radius: 8px; display: flex; align-items: center; justify-content: center; padding: 10px;">
                                    <img src="/api/media/view?filename=<?php echo urlencode($card['cover_graphic_path']); ?>" 
                                         alt="Current cover graphic" 
                                         style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                </div>
                                <p style="font-size: 12px; color: #666; margin-top: 5px;">Current cover (upload new to replace)</p>
                            </div>
                        <?php endif; ?>
                        <input type="file" id="cover_graphic" name="cover_graphic" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                        <div class="help-text">Wide image, max 25MB (JPG, PNG, GIF, WebP)</div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="/user/cards/view.php?id=<?php echo urlencode($cardId); ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let emailCount = <?php echo count($emails); ?>;
        let phoneCount = <?php echo count($phones); ?>;
        let websiteCount = <?php echo count($websites); ?>;
        
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
                    <div class="form-group" style="margin: 0; display: flex; align-items: center; gap: 5px;">
                        <input type="radio" name="primary_email" value="${emailCount}" onchange="updatePrimaryEmail(this)">
                        <label style="margin: 0; font-size: 12px; color: #666;">Primary</label>
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
                    <div class="form-group" style="margin: 0; display: flex; align-items: center; gap: 5px;">
                        <input type="radio" name="primary_website" value="${websiteCount}" onchange="updatePrimaryWebsite(this)">
                        <label style="margin: 0; font-size: 12px; color: #666;">Primary</label>
                    </div>
                </div>
            `;
            container.appendChild(div);
            websiteCount++;
        }
        
        // Primary designation functions
        function updatePrimaryEmail(radio) {
            // Clear all is_primary hidden inputs first
            document.querySelectorAll('input[name*="[is_primary]"]').forEach(input => {
                if (input.name.includes('emails[')) {
                    input.value = '0';
                }
            });
            
            // Set the selected one as primary
            if (radio.checked) {
                const index = radio.value;
                const hiddenInput = document.querySelector(`input[name="emails[${index}][is_primary]"]`);
                if (hiddenInput) {
                    hiddenInput.value = '1';
                } else {
                    // Create hidden input if it doesn't exist
                    const formGroup = radio.closest('.dynamic-item-row');
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = `emails[${index}][is_primary]`;
                    hiddenInput.value = '1';
                    formGroup.appendChild(hiddenInput);
                }
            }
        }
        
        function updatePrimaryWebsite(radio) {
            // Clear all is_primary hidden inputs first
            document.querySelectorAll('input[name*="[is_primary]"]').forEach(input => {
                if (input.name.includes('websites[')) {
                    input.value = '0';
                }
            });
            
            // Set the selected one as primary
            if (radio.checked) {
                const index = radio.value;
                const hiddenInput = document.querySelector(`input[name="websites[${index}][is_primary]"]`);
                if (hiddenInput) {
                    hiddenInput.value = '1';
                } else {
                    // Create hidden input if it doesn't exist
                    const formGroup = radio.closest('.dynamic-item-fields');
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = `websites[${index}][is_primary]`;
                    hiddenInput.value = '1';
                    formGroup.appendChild(hiddenInput);
                }
            }
        }
        
        // Theme selector interactivity
        document.addEventListener('DOMContentLoaded', function() {
            const themeSelect = document.getElementById('theme');
            const themeSwatches = document.querySelectorAll('.theme-swatch');
            
            // Highlight selected theme swatch
            function updateThemeSelection(themeName) {
                themeSwatches.forEach(swatch => {
                    if (swatch.dataset.theme === themeName) {
                        swatch.style.border = '3px solid #000';
                        swatch.style.transform = 'scale(1.1)';
                    } else {
                        swatch.style.border = '3px solid transparent';
                        swatch.style.transform = 'scale(1)';
                    }
                });
            }
            
            // Initial selection
            updateThemeSelection(themeSelect.value);
            
            // Handle dropdown change
            themeSelect.addEventListener('change', function() {
                updateThemeSelection(this.value);
            });
            
            // Handle swatch click
            themeSwatches.forEach(swatch => {
                swatch.addEventListener('click', function() {
                    const themeName = this.dataset.theme;
                    themeSelect.value = themeName;
                    updateThemeSelection(themeName);
                });
            });
        });
        
    </script>
    
    <?php include __DIR__ . '/../../includes/image-editor.php'; ?>
</body>
</html>
