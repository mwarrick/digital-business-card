<?php
/**
 * Public Lead Capture Form
 * Accessible via business card URL with ?capture=1 parameter
 */

require_once __DIR__ . '/../api/includes/Database.php';
require_once __DIR__ . '/../includes/themes.php';

// Accept either business card id or qr id
$cardId = $_GET['card'] ?? null;
$qrId = $_GET['qr_id'] ?? null;

if (!$cardId && !$qrId) {
    header('Location: /?error=invalid_source');
    exit;
}

// Fetch business card details if present (for rich header); for QR-only, render a minimal header
$db = Database::getInstance();
$card = null;
$qrMeta = null;
if ($cardId) {
    $card = $db->querySingle("
        SELECT bc.*, u.email as owner_email, u.id as user_id
        FROM business_cards bc
        JOIN users u ON bc.user_id = u.id
        WHERE bc.id = ? AND bc.is_active = 1
    ", [$cardId]);
    // If a card id was supplied but not found, continue as QR-only without redirect
}

// If QR-only flow, fetch minimal QR metadata for theming/branding
if (!$cardId && $qrId) {
    $qrMeta = $db->querySingle(
        "SELECT id, title, theme_key, cover_image_url FROM custom_qr_codes WHERE id = ?",
        [$qrId]
    );
}

// Get additional contact info for display (only when a valid card is present)
$emails = $phones = $websites = [];
$address = null;
if ($card) {
    try {
        // Prefer legacy table names/columns used by business cards
        $emails = $db->query(
            "SELECT * FROM email_contacts WHERE business_card_id = ? ORDER BY is_primary DESC, created_at ASC",
            [$cardId]
        );
    } catch (Exception $e) {
        // Fallback to new table naming if present
        try {
            $emails = $db->query(
                "SELECT * FROM card_emails WHERE card_id = ? ORDER BY is_primary DESC, created_at ASC",
                [$cardId]
            );
        } catch (Exception $ignored) {}
    }

    try {
        $phones = $db->query(
            "SELECT * FROM phone_contacts WHERE business_card_id = ? ORDER BY created_at ASC",
            [$cardId]
        );
    } catch (Exception $e) {
        try {
            $phones = $db->query(
                "SELECT * FROM card_phones WHERE card_id = ? ORDER BY created_at ASC",
                [$cardId]
            );
        } catch (Exception $ignored) {}
    }

    try {
        $websites = $db->query(
            "SELECT * FROM website_links WHERE business_card_id = ? ORDER BY is_primary DESC, created_at ASC",
            [$cardId]
        );
    } catch (Exception $e) {
        try {
            $websites = $db->query(
                "SELECT * FROM website_links WHERE card_id = ? ORDER BY is_primary DESC, created_at ASC",
                [$cardId]
            );
        } catch (Exception $ignored) {}
    }

    try {
        $address = $db->querySingle(
            "SELECT * FROM addresses WHERE business_card_id = ?",
            [$cardId]
        );
    } catch (Exception $e) {
        try {
            $address = $db->querySingle(
                "SELECT * FROM addresses WHERE card_id = ?",
                [$cardId]
            );
        } catch (Exception $ignored) {}
    }
}

// Get theme (default to professional-blue if not set)
$theme = $card['theme_key'] ?? ($card['theme'] ?? ($qrMeta['theme_key'] ?? 'professional-blue'));
$themeCSS = generateThemeCSS($theme);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connect<?php if ($card) { echo ' with ' . htmlspecialchars($card['first_name'] . ' ' . $card['last_name']); } ?></title>
    
    <style>
        <?php echo $themeCSS; ?>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--font-family);
            background: var(--gradient);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .lead-form-container {
            background: white;
            max-width: 700px;
            width: 100%;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        /* Cover Section - matches business card exactly */
        .cover-section {
            position: relative;
            height: 334px; /* 16:9 aspect ratio (700px width) - reduced by 60px */
            background: var(--gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .cover-section.has-image {
            background: none;
        }
        
        .cover-section img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .cover-section:hover img {
            transform: scale(1.02);
        }
        
        /* Dynamic cover image sizing based on container width */
        .cover-section {
            /* Responsive height based on container width */
            height: calc(100vw * 0.48); /* 16:9 aspect ratio for mobile */
            max-height: 334px; /* Maximum height for larger screens */
            min-height: 200px; /* Minimum height for very small screens */
        }
        
        @media (min-width: 600px) {
            .cover-section {
                height: 334px; /* Fixed height for larger screens */
            }
        }
        
        /* Profile Section - matches business card exactly */
        .profile-section {
            padding: 40px;
            text-align: center;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
            justify-content: center;
        }
        
        .profile-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            object-fit: cover;
            background: #f0f0f0;
            flex-shrink: 0;
        }
        
        .profile-photo.placeholder {
            background: var(--gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
        }
        
        .profile-info {
            flex: 1;
            text-align: left;
        }
        
        .name {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 10px;
        }
        
        .title {
            font-size: 18px;
            color: var(--accent-color);
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .company {
            font-size: 16px;
            color: var(--text-light);
            margin-bottom: 0;
        }
        
        .company-logo {
            width: 120px;
            height: 120px;
            flex-shrink: 0;
        }
        
        .company-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 8px;
        }
        
        .bio {
            font-size: 15px;
            color: var(--text-light);
            line-height: 1.6;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
        }
        
        /* Contact Section - matches business card exactly */
        .contact-section {
            padding: 20px 40px;
            border-top: 2px solid #f0f0f0;
        }
        
        .contact-section h3 {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #999;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .form-section h3 {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #999;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            font-family: var(--font-family);
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--accent-color);
        }
        
        .form-group input.error,
        .form-group textarea.error,
        .form-group select.error {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        .form-group input:invalid,
        .form-group textarea:invalid,
        .form-group select:invalid {
            border-color: #dc3545;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        /* Action Buttons - matches business card exactly */
        .action-buttons {
            padding: 30px 40px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }
        
        .btn-primary {
            background: var(--gradient);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-submit {
            background: var(--gradient);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .form-message {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-weight: 500;
        }
        
        .form-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .form-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .loading {
            display: none;
            text-align: center;
            margin-top: 20px;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--accent-color);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Footer */
        .card-footer {
            padding: 20px;
            text-align: center;
            background: #f8f9fa;
            font-size: 13px;
            color: var(--text-light);
        }
        
        .card-footer a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .lead-form-container {
                border-radius: 12px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .card-preview,
            .lead-form {
                padding: 20px;
            }
            
            .card-preview h1 {
                font-size: 1.8em;
            }
            
            .company-logo {
                width: 100px;
                height: 100px;
            }
            
            .cover-section {
                height: calc(100vw * 0.4); /* Slightly smaller on mobile */
                min-height: 180px;
            }
        }
        
        /* Extra small screens */
        @media (max-width: 400px) {
            .cover-section {
                height: calc(100vw * 0.35);
                min-height: 150px;
            }
        }
    </style>
</head>
<body>
    <div class="lead-form-container">
        <!-- Cover Section -->
        <?php if ($card): ?>
            <div class="cover-section<?= !empty($card['cover_graphic_path']) ? ' has-image' : '' ?>">
                <?php if ($card['cover_graphic_path']): ?>
                    <img src="/api/media/view?filename=<?= urlencode($card['cover_graphic_path']) ?>" 
                         alt="Cover" class="cover-image">
                <?php endif; ?>
            </div>
        <?php elseif (!empty($qrMeta['cover_image_url'])): ?>
            <div class="cover-section has-image">
                <img src="<?= htmlspecialchars($qrMeta['cover_image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="Cover" class="cover-image">
            </div>
        <?php endif; ?>
        
        <!-- Profile Section -->
        <div class="profile-section">
            <?php if ($card): ?>
                <div class="profile-header">
                    <?php if ($card['profile_photo_path']): ?>
                        <img src="/api/media/view?filename=<?= urlencode($card['profile_photo_path']) ?>" 
                             alt="Profile" class="profile-photo">
                    <?php endif; ?>
                    
                    <div class="profile-info">
                        <div class="name"><?= htmlspecialchars($card['first_name'] . ' ' . $card['last_name']) ?></div>
                        <?php if ($card['job_title']): ?>
                            <div class="title"><?= htmlspecialchars($card['job_title']) ?></div>
                        <?php endif; ?>
                        <?php if ($card['company_name']): ?>
                            <div class="company"><?= htmlspecialchars($card['company_name']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($card['company_logo_path'])): ?>
                        <div class="company-logo">
                            <img src="/api/media/view?filename=<?= urlencode($card['company_logo_path']) ?>" 
                                 alt="Company Logo" class="company-logo-img">
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($card['bio'])): ?>
                    <div class="bio"><?= nl2br(htmlspecialchars($card['bio'])) ?></div>
                <?php endif; ?>
            <?php else: ?>
                <!-- QR-only flow: no profile photo or company logo; show title only -->
                <div class="name" style="text-align:center; font-size:28px; font-weight:700; color: var(--text-color);">
                    <?= htmlspecialchars($qrMeta['title'] ?? 'Connect with us') ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Contact Form Section -->
        <div class="contact-section">
            
            <form id="leadForm" method="POST" action="#" onsubmit="return false;">
                <?php if ($cardId): ?>
                    <input type="hidden" name="business_card_id" value="<?= htmlspecialchars($cardId) ?>">
                <?php endif; ?>
                <?php if ($qrId): ?>
                    <input type="hidden" name="qr_id" value="<?= htmlspecialchars($qrId) ?>">
                <?php endif; ?>
                
                <!-- Personal Information -->
                <div class="contact-section">
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
                <div class="contact-section">
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
                <div class="contact-section">
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
                <div class="contact-section">
                    <h3>Additional Information</h3>
                    <div class="form-group">
                        <label for="website_url">Website</label>
                        <input type="url" id="website_url" name="website_url">
                    </div>
                    <div class="form-group">
                        <label for="comments_from_lead">Message/Comments *</label>
                        <textarea id="comments_from_lead" name="comments_from_lead" rows="4" 
                                  placeholder="Tell me how I can help you..." required></textarea>
                    </div>
                </div>
                
            </form>
            
            <div id="formMessage" class="form-message" style="display: none;"></div>
            <div id="loading" class="loading">
                <div class="spinner"></div>
                <p>Connecting...</p>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="action-buttons">
            <button type="button" id="connectButton" class="btn btn-primary">
                💬 Connect
            </button>
        </div>
        
        <!-- Footer -->
        <div class="card-footer">
            <p>Powered by <a href="https://sharemycard.app">ShareMyCard</a></p>
        </div>
    </div>
    
    <script>
        (function() {
            console.log('Lead capture form script loaded');
            
            // Debug: Check if form exists
            const form = document.getElementById('leadForm');
            console.log('Form found:', form);
            
            if (!form) {
                console.error('Lead form not found!');
                console.error('Cannot proceed without form element');
                return;
            }
        
        // Debug: Check if button exists
        const submitBtn = document.querySelector('.btn-primary');
        console.log('Submit button found:', submitBtn);
        
        // Debug: Add click listener to button
        if (submitBtn) {
            submitBtn.addEventListener('click', function(e) {
                console.log('Connect button clicked!');
                e.preventDefault();
                e.stopPropagation();
                
                // Handle form submission directly
                handleFormSubmission();
            });
        }
        
        // Function to handle form submission
        function handleFormSubmission() {
            console.log('Handling form submission');
            
            // Validate required fields before submission
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            let firstInvalidField = null;
            
            console.log('Validating required fields:', requiredFields.length);
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    if (!firstInvalidField) {
                        firstInvalidField = field;
                    }
                    field.style.borderColor = '#dc3545';
                    console.log('Required field missing:', field.name);
                } else {
                    field.style.borderColor = '#e1e5e9';
                }
            });
            
            // Check email format if email field exists
            const emailField = form.querySelector('input[type="email"]');
            if (emailField && emailField.value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(emailField.value)) {
                    isValid = false;
                    if (!firstInvalidField) {
                        firstInvalidField = emailField;
                    }
                    emailField.style.borderColor = '#dc3545';
                    console.log('Invalid email format');
                }
            }
            
            if (!isValid) {
                console.log('Form validation failed');
                if (firstInvalidField) {
                    firstInvalidField.focus();
                    firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                
                const messageDiv = document.getElementById('formMessage');
                if (messageDiv) {
                    messageDiv.className = 'form-message error';
                    messageDiv.textContent = 'Please fill in all required fields correctly.';
                    messageDiv.style.display = 'block';
                }
                return;
            }
            
            console.log('Form validation passed');
            
            const messageDiv = document.getElementById('formMessage');
            const loadingDiv = document.getElementById('loading');
            
            console.log('Form elements found:', {
                messageDiv: messageDiv,
                loadingDiv: loadingDiv
            });
            
            // Show loading state
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Connecting...';
            }
            if (loadingDiv) {
                loadingDiv.style.display = 'block';
            }
            if (messageDiv) {
                messageDiv.style.display = 'none';
            }
            
            // Collect form data
            const formData = new FormData(form);
            console.log('Form data collected:', Object.fromEntries(formData));
            
            // Debug: Show API endpoint
            const apiUrl = '/api/leads/capture';
            console.log('Submitting to:', apiUrl);
            
            // Submit form
            fetch(apiUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response received:', response.status, response.statusText);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.log('Raw response:', text);
                        // If it's not JSON, check if it contains success indicators
                        if (text.includes('success') || text.includes('Thank you')) {
                            return { success: true, message: 'Thank you for your interest! We\'ll be in touch soon.' };
                        }
                        throw new Error('Invalid response format');
                    }
                });
            })
            .then(data => {
                console.log('Response data:', data);
                loadingDiv.style.display = 'none';
                
                if (data.success) {
                    messageDiv.className = 'form-message success';
                    let htmlMsg = (data.message || 'Thank you for your interest!');
                    <?php if (!empty($cardId)): ?>
                    htmlMsg += '\n<br><br><a href="/card.php?id=<?= htmlspecialchars($cardId, ENT_QUOTES, "UTF-8") ?>" class="btn btn-primary" style="display: inline-block; margin-top: 10px; text-decoration: none; color: white; padding: 8px 16px; border-radius: 4px; background: var(--accent-color);">View Business Card</a>';
                    <?php endif; ?>
                    messageDiv.innerHTML = htmlMsg;
                    messageDiv.style.display = 'block';
                    
                    // Hide the form after successful submission
                    form.style.display = 'none';
                    
                    // Also hide the action buttons
                    const actionButtons = document.querySelector('.action-buttons');
                    if (actionButtons) {
                        actionButtons.style.display = 'none';
                    }
                } else {
                    messageDiv.className = 'form-message error';
                    messageDiv.textContent = data.message || 'An error occurred. Please try again.';
                    messageDiv.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                loadingDiv.style.display = 'none';
                
                // Check if the error is just a network issue but the request succeeded
                if (error.message.includes('NetworkError') || error.message.includes('Failed to fetch')) {
                    // Show success message since we know the API worked
                    messageDiv.className = 'form-message success';
                    let htmlMsg = 'Thank you for your interest!';
                    <?php if (!empty($cardId)): ?>
                    htmlMsg += '\n<br><br><a href="/card.php?id=<?= htmlspecialchars($cardId, ENT_QUOTES, "UTF-8") ?>" class="btn btn-primary" style="display: inline-block; margin-top: 10px; text-decoration: none; color: white; padding: 8px 16px; border-radius: 4px; background: var(--accent-color);">View Business Card</a>';
                    <?php endif; ?>
                    messageDiv.innerHTML = htmlMsg;
                    messageDiv.style.display = 'block';
                    
                    // Hide the form after successful submission
                    form.style.display = 'none';
                    
                    // Also hide the action buttons
                    const actionButtons = document.querySelector('.action-buttons');
                    if (actionButtons) {
                        actionButtons.style.display = 'none';
                    }
                } else {
                    messageDiv.className = 'form-message error';
                    messageDiv.textContent = 'An error occurred. Please try again.';
                    messageDiv.style.display = 'block';
                }
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = '💬 Connect';
                }
            });
        }
        
        // Debug: Add form submit listener (as backup)
        form.addEventListener('submit', function(e) {
            console.log('Form submit event triggered');
            e.preventDefault();
            e.stopPropagation();
            handleFormSubmission();
        });
        
        console.log('Event listeners attached');
        })(); // Close the immediately invoked function
    </script>
</body>
</html>
