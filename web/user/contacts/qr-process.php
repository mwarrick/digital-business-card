<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /user/login.php');
    exit;
}

// Get QR data from POST or GET
$qrData = '';
$qrType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qrData = $_POST['qr_data'] ?? '';
    $qrType = $_POST['qr_type'] ?? '';
} else {
    $qrData = $_GET['qr_data'] ?? '';
    $qrType = $_GET['qr_type'] ?? '';
}

// If it's a URL, fetch the vCard data
$vcardData = '';
if ($qrType === 'url' && !empty($qrData)) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'QRCard Scanner/1.0',
            'follow_location' => true,
            'max_redirects' => 5
        ]
    ]);
    
    try {
        $vcardData = file_get_contents($qrData, false, $context);
        if ($vcardData === false) {
            $vcardData = '';
        }
    } catch (Exception $e) {
        error_log('Error fetching vCard from URL: ' . $e->getMessage());
        $vcardData = '';
    }
} else {
    $vcardData = $qrData;
}

// Parse vCard data
$contactData = [];
if (!empty($vcardData) && strpos($vcardData, 'BEGIN:VCARD') === 0) {
    $lines = explode("\n", $vcardData);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line === 'END:VCARD') continue;
        
        // Handle FN field (Full Name)
        if (strpos($line, 'FN:') === 0) {
            $contactData['name'] = substr($line, 3);
        }
        // Handle N field (Name components)
        elseif (strpos($line, 'N:') === 0) {
            $parts = explode(';', substr($line, 2));
            if (count($parts) >= 2) {
                $contactData['last_name'] = $parts[0];
                $contactData['first_name'] = $parts[1];
            }
        }
        // Handle ORG field (Organization)
        elseif (strpos($line, 'ORG') === 0) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $contactData['company'] = trim($parts[1]);
            }
        }
        // Handle TEL field (Telephone)
        elseif (strpos($line, 'TEL') === 0) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                // If we already have a phone, append this one (for multiple phones)
                if (isset($contactData['phone'])) {
                    $contactData['phone'] .= ', ' . trim($parts[1]);
                } else {
                    $contactData['phone'] = trim($parts[1]);
                }
            }
        }
        // Handle EMAIL field
        elseif (strpos($line, 'EMAIL') === 0) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                // If we already have an email, append this one (for multiple emails)
                if (isset($contactData['email'])) {
                    $contactData['email'] .= ', ' . trim($parts[1]);
                } else {
                    $contactData['email'] = trim($parts[1]);
                }
            }
        }
        // Handle URL field
        elseif (strpos($line, 'URL') === 0) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                // If we already have a website, append this one (for multiple URLs)
                if (isset($contactData['website'])) {
                    $contactData['website'] .= ', ' . trim($parts[1]);
                } else {
                    $contactData['website'] = trim($parts[1]);
                }
            }
        }
        // Handle ADR field (Address)
        elseif (strpos($line, 'ADR') === 0) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $addressParts = explode(';', $parts[1]);
                if (count($addressParts) >= 7) {
                    // ADR format: post_office_box;extended_address;street_address;locality;region;postal_code;country
                    $contactData['address'] = trim($addressParts[2]); // street_address
                    $contactData['city'] = trim($addressParts[3]);     // locality
                    $contactData['state'] = trim($addressParts[4]);    // region
                    $contactData['zip'] = trim($addressParts[5]);      // postal_code
                    $contactData['country'] = trim($addressParts[6]);  // country
                }
            }
        }
        // Handle TITLE field (Job Title)
        elseif (strpos($line, 'TITLE') === 0) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $contactData['title'] = trim($parts[1]);
            }
        }
        // Handle NOTE field (Notes)
        elseif (strpos($line, 'NOTE') === 0) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $contactData['notes'] = trim($parts[1]);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Processing - ShareMyCard</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-section {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e1e5e9;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        input, textarea, select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #007bff;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            margin-right: 10px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        .status {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .status.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add Contact from QR Code</h1>
        
        
        <?php if (!empty($contactData)): ?>
            <div class="status success">
                ✅ Contact data parsed successfully! You can edit the information below and save it.
            </div>
        <?php else: ?>
            <div class="status error">
                ❌ No contact data could be parsed from the QR code.
            </div>
            <div style="text-align: center; margin: 20px 0;">
                <a href="/user/contacts/scan-qr.php" class="btn">Try Scanning Again</a>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($contactData)): ?>
        <div class="form-section">
            <h3>Contact Information</h3>
            <form id="contact-form" method="POST" action="/user/api/create-contact-from-qr.php">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($contactData['first_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($contactData['last_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="organization_name">Company</label>
                    <input type="text" id="organization_name" name="organization_name" value="<?php echo htmlspecialchars($contactData['company'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="job_title">Job Title</label>
                    <input type="text" id="job_title" name="job_title" value="<?php echo htmlspecialchars($contactData['title'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="work_phone">Phone</label>
                    <input type="tel" id="work_phone" name="work_phone" value="<?php echo htmlspecialchars($contactData['phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email_primary">Email</label>
                    <input type="email" id="email_primary" name="email_primary" value="<?php echo htmlspecialchars($contactData['email'] ?? ''); ?>" placeholder="Enter email address">
                </div>
                
                <div class="form-group">
                    <label for="website_url">Website</label>
                    <input type="url" id="website_url" name="website_url" value="<?php echo htmlspecialchars($contactData['website'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="street_address">Address</label>
                    <textarea id="street_address" name="street_address" rows="3"><?php echo htmlspecialchars($contactData['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($contactData['city'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="state">State</label>
                    <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($contactData['state'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="zip_code">ZIP Code</label>
                    <input type="text" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars($contactData['zip'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="country">Country</label>
                    <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($contactData['country'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="comments_from_lead">Notes</label>
                    <textarea id="comments_from_lead" name="comments_from_lead" rows="3"><?php echo htmlspecialchars($contactData['notes'] ?? ''); ?></textarea>
                </div>
                
                <input type="hidden" name="source" value="qr_scan">
                <input type="hidden" name="source_metadata" value="<?php echo htmlspecialchars(json_encode(['qr_data' => $qrData, 'qr_type' => $qrType, 'timestamp' => date('Y-m-d H:i:s')])); ?>">
                
                <button type="submit" class="btn" style="margin-bottom: 15px;">Save Contact</button>
                <a href="/user/contacts/" class="btn btn-secondary" style="margin-left: 10px;">Cancel</a>
            </form>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($contactData)): ?>
    <script>
        // Add form submission handling
        document.getElementById('contact-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('/user/api/create-contact-from-qr.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                return response.text().then(text => {
                    console.log('Raw response:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Failed to parse JSON:', e);
                        throw new Error('Invalid JSON response: ' + text.substring(0, 200));
                    }
                });
            })
            .then(data => {
                console.log('Parsed response:', data);
                if (data.success) {
                    alert('Contact saved successfully!');
                    window.location.href = '/user/contacts/';
                } else {
                    alert('Error saving contact: ' + (data.message || data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving contact: ' + error.message);
            });
        });
    </script>
    <?php endif; ?>
</body>
</html>
