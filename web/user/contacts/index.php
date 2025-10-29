<?php
/**
 * User Contacts Dashboard
 * Display and manage contacts
 */

require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';

UserAuth::requireAuth();

$user = UserAuth::getUser();
$db = Database::getInstance();

// Get user's contacts with lead information and source tracking
$contacts = $db->query("
    SELECT c.*, l.id as lead_id, bc.first_name as card_first_name, 
           bc.last_name as card_last_name,
           COALESCE(c.source, 
                    CASE WHEN c.id_lead IS NOT NULL AND c.id_lead > 0 THEN 'converted' ELSE 'manual' END
           ) as source_type
    FROM contacts c
    LEFT JOIN leads l ON c.id_lead = l.id
    LEFT JOIN business_cards bc ON l.id_business_card = bc.id
    WHERE c.id_user = ?
    ORDER BY c.created_at DESC
", [UserAuth::getUserId()]);

$contactCount = count($contacts);
$convertedFromLeads = array_filter($contacts, function($contact) { return $contact['source_type'] === 'converted'; });
$manualContacts = array_filter($contacts, function($contact) { return $contact['source_type'] === 'manual'; });
$qrScannedContacts = array_filter($contacts, function($contact) { return $contact['source_type'] === 'qr_scan'; });

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Contacts - ShareMyCard</title>
    <link rel="stylesheet" href="/user/includes/user-style.css">
    <style>
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .dashboard-title {
            font-size: 2.5em;
            font-weight: 700;
            color: #333;
            margin: 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #28a745;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: 700;
            color: #28a745;
            margin: 0 0 10px 0;
        }
        
        .stat-label {
            color: #666;
            font-size: 1.1em;
            margin: 0;
        }
        
        .contacts-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .contacts-header {
            background: #f8f9fa;
            padding: 20px 25px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .bulk-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .select-all-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            color: #333;
        }
        
        .contacts-title {
            font-size: 1.5em;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .search-filter {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-input {
            padding: 8px 12px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 14px;
            min-width: 200px;
        }
        
        .filter-select {
            padding: 8px 12px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .contacts-list {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .contact-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s ease;
        }
        
        .contact-item:hover {
            background: #f8f9fa;
        }
        
        .contact-item:last-child {
            border-bottom: none;
        }
        
        .contact-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .contact-name {
            font-size: 1.3em;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .contact-source {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 500;
        }
        
        .source-converted {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .source-manual {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .source-qr_scan {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-label {
            font-weight: 500;
            color: #666;
            min-width: 80px;
        }
        
        .info-value {
            color: #333;
        }
        
        .contact-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-primary {
            background: #28a745;
            color: white;
        }
        
        .btn-primary:hover {
            background: #218838;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-1px);
        }
        
        .btn-success {
            background: #17a2b8;
            color: white;
        }
        
        .btn-success:hover {
            background: #138496;
            transform: translateY(-1px);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state-icon {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            margin: 0 0 10px 0;
            font-size: 1.5em;
        }
        
        .empty-state p {
            margin: 0 0 20px 0;
            font-size: 1.1em;
        }
        
        .add-contact-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .add-contact-btn:hover {
            transform: translateY(-2px);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-size: 1.5em;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 1.5em;
            cursor: pointer;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-filter {
                width: 100%;
                justify-content: stretch;
            }
            
            .search-input {
                flex: 1;
                min-width: 150px;
            }
            
            .contact-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .contact-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navigation.php'; ?>
    
    <div class="container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">My Contacts</h1>
            <div class="search-filter">
                <input type="text" id="searchInput" class="search-input" placeholder="Search contacts...">
                <select id="sourceFilter" class="filter-select">
                    <option value="">All Sources</option>
                    <option value="converted">From Leads</option>
                    <option value="manual">Manual</option>
                    <option value="qr_scan">QR Scanned</option>
                </select>
                <a href="/user/contacts/create.php" class="add-contact-btn">
                    ➕ Add Contact
                </a>
                <a href="/user/contacts/scan-qr.php" class="add-contact-btn" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    📷 Scan QR Code
                </a>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $contactCount ?></div>
                <div class="stat-label">Total Contacts</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($convertedFromLeads) ?></div>
                <div class="stat-label">From Leads</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($manualContacts) ?></div>
                <div class="stat-label">Manual</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($qrScannedContacts) ?></div>
                <div class="stat-label">QR Scanned</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $contactCount > 0 ? round((count($convertedFromLeads) / $contactCount) * 100) : 0 ?>%</div>
                <div class="stat-label">From Leads</div>
            </div>
        </div>
        
        <!-- Contacts List -->
        <div class="contacts-container">
            <div class="contacts-header">
                <h2 class="contacts-title">All Contacts</h2>
                <div class="bulk-actions">
                    <label class="select-all-label">
                        <input type="checkbox" id="selectAllContacts">
                        Select All
                    </label>
                    <button id="deleteSelectedBtn" class="btn btn-danger" disabled>
                        🗑️ Delete Selected
                    </button>
                </div>
            </div>
            
            <div class="contacts-list" id="contactsList">
                <?php if (empty($contacts)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">👥</div>
                        <h3>No contacts yet</h3>
                        <p>Start building your network by adding contacts or converting leads.</p>
                        <a href="/user/contacts/create.php" class="add-contact-btn">
                            ➕ Add Your First Contact
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($contacts as $contact): ?>
                        <div class="contact-item" data-source="<?= $contact['source_type'] ?>" data-name="<?= htmlspecialchars(strtolower($contact['first_name'] . ' ' . $contact['last_name'])) ?>">
                            <div class="contact-header">
                                <label class="select-all-label" style="margin-right:8px;">
                                    <input type="checkbox" class="select-contact" value="<?= (int)$contact['id'] ?>">
                                    <span style="font-size:12px;color:#666;">Select</span>
                                </label>
                                <h3 class="contact-name"><?= htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']) ?></h3>
                                <span class="contact-source source-<?= $contact['source_type'] ?>">
                                    <?php 
                                    switch($contact['source_type']) {
                                        case 'converted': echo 'From Lead'; break;
                                        case 'qr_scan': echo 'QR Scanned'; break;
                                        default: echo 'Manual'; break;
                                    }
                                    ?>
                                </span>
                            </div>
                            
                            <div class="contact-info">
                                <?php if ($contact['organization_name']): ?>
                                    <div class="info-item">
                                        <span class="info-label">Company:</span>
                                        <span class="info-value"><?= htmlspecialchars($contact['organization_name']) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($contact['job_title']): ?>
                                    <div class="info-item">
                                        <span class="info-label">Title:</span>
                                        <span class="info-value"><?= htmlspecialchars($contact['job_title']) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="info-item">
                                    <span class="info-label">Added:</span>
                                    <span class="info-value"><?= date('M j, Y g:i A', strtotime($contact['created_at'])) ?></span>
                                </div>
                            </div>
                            
                            
                            <div class="contact-actions">
                                <button class="btn btn-primary" onclick="viewContact(<?= $contact['id'] ?>)">
                                    👁️ View Details
                                </button>
                                
                                <button class="btn btn-secondary" onclick="editContact(<?= $contact['id'] ?>)">
                                    ✏️ Edit
                                </button>
                                
                                <a class="btn btn-success" href="/user/api/export-contact-vcf.php?id=<?= (int)$contact['id'] ?>">
                                    📤 Export
                                </a>
                                
                                
                                <button class="btn btn-danger" onclick="deleteContact(<?= $contact['id'] ?>)">
                                    🗑️ Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Contact Details Modal -->
    <div id="contactModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Contact Details</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div id="contactDetails">
                <!-- Contact details will be loaded here -->
            </div>
        </div>
    </div>
    
    <script>
        // Search and filter functionality
        document.getElementById('searchInput').addEventListener('input', filterContacts);
        document.getElementById('sourceFilter').addEventListener('change', filterContacts);
        const selectAllCheckbox = document.getElementById('selectAllContacts');
        const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
        const selectedIds = new Set();
        function refreshSelectionUI() {
            const allBoxes = Array.from(document.querySelectorAll('.select-contact'));
            const total = allBoxes.length;
            const selected = allBoxes.filter(cb => cb.checked).length;
            selectAllCheckbox.checked = total > 0 && selected === total;
            selectAllCheckbox.indeterminate = selected > 0 && selected < total;
            deleteSelectedBtn.disabled = selected === 0;
        }
        function handleContactCheckboxChange(e) {
            const id = e.target.value;
            if (e.target.checked) {
                selectedIds.add(id);
            } else {
                selectedIds.delete(id);
            }
            refreshSelectionUI();
        }
        function bindContactCheckboxes() {
            document.querySelectorAll('.select-contact').forEach(cb => {
                cb.removeEventListener('change', handleContactCheckboxChange);
                cb.addEventListener('change', handleContactCheckboxChange);
            });
        }
        bindContactCheckboxes();
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const allBoxes = document.querySelectorAll('.select-contact');
                allBoxes.forEach(cb => {
                    cb.checked = selectAllCheckbox.checked;
                    if (cb.checked) {
                        selectedIds.add(cb.value);
                    } else {
                        selectedIds.delete(cb.value);
                    }
                });
                refreshSelectionUI();
            });
        }
        if (deleteSelectedBtn) {
            deleteSelectedBtn.addEventListener('click', function() {
                const ids = Array.from(selectedIds).map(id => parseInt(id, 10)).filter(n => !isNaN(n));
                if (ids.length === 0) return;
                if (!confirm(`Are you sure you want to delete ${ids.length} selected contact(s)? This cannot be undone. Any associated leads will be reverted to \"New\" status.`)) {
                    return;
                }
                deleteSelectedBtn.textContent = '🗑️ Deleting...';
                deleteSelectedBtn.disabled = true;
                const formData = new FormData();
                ids.forEach(id => formData.append('contact_ids[]', id));
                fetch('/user/api/delete-contacts-bulk.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert(`Deleted ${data.deleted_count} contact(s).` + (data.leads_reverted_count ? `\n${data.leads_reverted_count} associated lead(s) reverted.` : ''));
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to delete selected contacts'));
                        deleteSelectedBtn.textContent = '🗑️ Delete Selected';
                        deleteSelectedBtn.disabled = false;
                    }
                })
                .catch(err => {
                    console.error('Bulk delete error', err);
                    alert('Error deleting selected contacts. Please try again.');
                    deleteSelectedBtn.textContent = '🗑️ Delete Selected';
                    deleteSelectedBtn.disabled = false;
                });
            });
        }
        
        function filterContacts() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const sourceFilter = document.getElementById('sourceFilter').value;
            const contactItems = document.querySelectorAll('.contact-item');
            
            contactItems.forEach(item => {
                const name = item.dataset.name;
                const source = item.dataset.source;
                
                const matchesSearch = name.includes(searchTerm);
                const matchesSource = !sourceFilter || source === sourceFilter;
                
                if (matchesSearch && matchesSource) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        function viewContact(contactId) {
            // Show loading state
            const modal = document.getElementById('contactModal');
            const contactDetails = document.getElementById('contactDetails');
            contactDetails.innerHTML = '<div style="text-align: center; padding: 20px;">Loading contact details...</div>';
            modal.style.display = 'block';
            
            // Fetch contact details
            fetch(`/user/api/get-contact.php?id=${contactId}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    return response.text().then(text => {
                        console.log('Raw response:', text);
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('JSON parse error:', e);
                            console.error('Raw text:', text);
                            throw new Error('Invalid JSON response');
                        }
                    });
                })
                .then(data => {
                    console.log('Parsed data:', data);
                    if (data.success) {
                        displayContactDetails(data.data);
                    } else {
                        contactDetails.innerHTML = `<div style="color: red; text-align: center; padding: 20px;">Error: ${data.message}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching contact details:', error);
                    contactDetails.innerHTML = '<div style="color: red; text-align: center; padding: 20px;">Error loading contact details. Please try again.</div>';
                });
        }
        
        function displayContactDetails(contact) {
            const contactDetails = document.getElementById('contactDetails');
            
            // Format dates
            const createdDate = new Date(contact.created_at).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            const updatedDate = new Date(contact.updated_at).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            // Source type badge
            const sourceClass = contact.source_type === 'converted' ? 'status-converted' : 'status-manual';
            const sourceText = contact.source_type === 'converted' ? 'Converted from Lead' : 'Manual Entry';
            
            // Lead information (if converted from lead)
            let leadInfo = '';
            if (contact.lead_id) {
                const leadDate = new Date(contact.lead_created_at).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                leadInfo = `
                    <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #28a745;">
                        <h4 style="margin: 0 0 10px 0; color: #2e7d32;">📋 Converted from Lead</h4>
                        <p style="margin: 0; color: #2e7d32;">
                            <strong>Original Lead ID:</strong> ${contact.lead_id}<br>
                            <strong>Lead Captured:</strong> ${leadDate}
                        </p>
                    </div>
                `;
            }
            
            contactDetails.innerHTML = `
                <div style="padding: 20px;">
                    <!-- Contact Header -->
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                        <div>
                            <h2 style="margin: 0 0 5px 0; color: #333;">${contact.first_name} ${contact.last_name}</h2>
                            <p style="margin: 0; color: #666; font-size: 16px;">${contact.email_primary || 'No email provided'}</p>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <span class="status-badge ${sourceClass}">${sourceText}</span>
                        </div>
                    </div>
                    
                    ${leadInfo}
                    
                    <!-- Contact Information -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 20px;">
                        <!-- Contact Details -->
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                            <h3 style="margin: 0 0 15px 0; color: #333; border-bottom: 2px solid #007bff; padding-bottom: 8px;">Contact Information</h3>
                            <div style="display: grid; gap: 10px;">
                                ${contact.work_phone ? `<div><strong>Work Phone:</strong> ${contact.work_phone}</div>` : ''}
                                ${contact.mobile_phone ? `<div><strong>Mobile Phone:</strong> ${contact.mobile_phone}</div>` : ''}
                                ${contact.organization_name ? `<div><strong>Company:</strong> ${contact.organization_name}</div>` : ''}
                                ${contact.job_title ? `<div><strong>Job Title:</strong> ${contact.job_title}</div>` : ''}
                                ${contact.website_url ? `<div><strong>Website:</strong> <a href="${contact.website_url}" target="_blank">${contact.website_url}</a></div>` : ''}
                            </div>
                        </div>
                        
                        <!-- Address Information -->
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                            <h3 style="margin: 0 0 15px 0; color: #333; border-bottom: 2px solid #28a745; padding-bottom: 8px;">Address</h3>
                            <div style="display: grid; gap: 10px;">
                                ${contact.street_address ? `<div><strong>Street:</strong> ${contact.street_address}</div>` : ''}
                                ${contact.city ? `<div><strong>City:</strong> ${contact.city}</div>` : ''}
                                ${contact.state ? `<div><strong>State:</strong> ${contact.state}</div>` : ''}
                                ${contact.zip_code ? `<div><strong>ZIP:</strong> ${contact.zip_code}</div>` : ''}
                                ${contact.country ? `<div><strong>Country:</strong> ${contact.country}</div>` : ''}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Message/Comments -->
                    ${contact.comments_from_lead ? `
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                            <h3 style="margin: 0 0 15px 0; color: #333; border-bottom: 2px solid #ffc107; padding-bottom: 8px;">Message from Lead</h3>
                            <p style="margin: 0; white-space: pre-wrap; line-height: 1.6;">${contact.comments_from_lead}</p>
                        </div>
                    ` : ''}
                    
                    <!-- Business Card Information (if converted from lead) -->
                    ${contact.card_first_name ? `
                        <div style="background: #e3f2fd; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                            <h3 style="margin: 0 0 15px 0; color: #333; border-bottom: 2px solid #2196f3; padding-bottom: 8px;">From Business Card</h3>
                            <div style="display: grid; gap: 10px;">
                                <div><strong>Card Owner:</strong> ${contact.card_first_name} ${contact.card_last_name}</div>
                                ${contact.card_company ? `<div><strong>Company:</strong> ${contact.card_company}</div>` : ''}
                                ${contact.card_job_title ? `<div><strong>Job Title:</strong> ${contact.card_job_title}</div>` : ''}
                                ${contact.card_phone ? `<div><strong>Phone:</strong> ${contact.card_phone}</div>` : ''}
                                ${contact.card_bio ? `<div><strong>Bio:</strong> ${contact.card_bio}</div>` : ''}
                            </div>
                        </div>
                    ` : ''}
                    
                    <!-- Metadata -->
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #6c757d;">
                        <h3 style="margin: 0 0 15px 0; color: #333;">Contact Information</h3>
                        <div style="display: grid; gap: 8px; font-size: 14px; color: #666;">
                            <div><strong>Created:</strong> ${createdDate}</div>
                            <div><strong>Last Updated:</strong> ${updatedDate}</div>
                            ${contact.ip_address ? `<div><strong>IP Address:</strong> ${contact.ip_address}</div>` : ''}
                            ${contact.user_agent ? `<div><strong>User Agent:</strong> ${contact.user_agent.substring(0, 100)}${contact.user_agent.length > 100 ? '...' : ''}</div>` : ''}
                            ${contact.referrer ? `<div><strong>Referrer:</strong> ${contact.referrer}</div>` : ''}
                        </div>
                    </div>
                </div>
            `;
        }
        
        function editContact(contactId) {
            // Show loading state
            const modal = document.getElementById('contactModal');
            const contactDetails = document.getElementById('contactDetails');
            contactDetails.innerHTML = '<div style="text-align: center; padding: 20px;">Loading contact for editing...</div>';
            modal.style.display = 'block';
            
            // Fetch contact details for editing
            fetch(`/user/api/get-contact.php?id=${contactId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('JSON parse error:', e);
                            throw new Error('Invalid JSON response');
                        }
                    });
                })
                .then(data => {
                    if (data.success) {
                        displayEditForm(data.data);
                    } else {
                        contactDetails.innerHTML = `<div style="color: red; text-align: center; padding: 20px;">Error: ${data.message}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching contact for editing:', error);
                    contactDetails.innerHTML = '<div style="color: red; text-align: center; padding: 20px;">Error loading contact for editing. Please try again.</div>';
                });
        }
        
        function displayEditForm(contact) {
            const contactDetails = document.getElementById('contactDetails');
            
            contactDetails.innerHTML = `
                <div style="padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2 style="margin: 0; color: #333;">Edit Contact: ${contact.first_name} ${contact.last_name}</h2>
                        <button onclick="viewContact(${contact.id})" style="background: #6c757d; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
                            Cancel
                        </button>
                    </div>
                    
                    <form id="editContactForm" onsubmit="saveContact(${contact.id}); return false;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 20px;">
                            <!-- Personal Information -->
                            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                                <h3 style="margin: 0 0 15px 0; color: #333; border-bottom: 2px solid #007bff; padding-bottom: 8px;">Personal Information</h3>
                                <div style="display: grid; gap: 15px;">
                                    <div>
                                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">First Name *</label>
                                        <input type="text" name="first_name" value="${contact.first_name || ''}" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    </div>
                                    <div>
                                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Last Name *</label>
                                        <input type="text" name="last_name" value="${contact.last_name || ''}" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    </div>
                                    <div>
                                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Email *</label>
                                        <input type="email" name="email_primary" value="${contact.email_primary || ''}" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    </div>
                                    <div>
                                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Birthdate</label>
                                        <input type="date" name="birthdate" value="${contact.birthdate || ''}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Contact Information -->
                            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                                <h3 style="margin: 0 0 15px 0; color: #333; border-bottom: 2px solid #28a745; padding-bottom: 8px;">Contact Information</h3>
                                <div style="display: grid; gap: 15px;">
                                    <div>
                                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Work Phone</label>
                                        <input type="tel" name="work_phone" value="${contact.work_phone || ''}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    </div>
                                    <div>
                                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Mobile Phone</label>
                                        <input type="tel" name="mobile_phone" value="${contact.mobile_phone || ''}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    </div>
                                    <div>
                                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Website</label>
                                        <input type="url" name="website_url" value="${contact.website_url || ''}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Professional Information -->
                            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                                <h3 style="margin: 0 0 15px 0; color: #333; border-bottom: 2px solid #ffc107; padding-bottom: 8px;">Professional Information</h3>
                                <div style="display: grid; gap: 15px;">
                                    <div>
                                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Company</label>
                                        <input type="text" name="organization_name" value="${contact.organization_name || ''}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    </div>
                                    <div>
                                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Job Title</label>
                                        <input type="text" name="job_title" value="${contact.job_title || ''}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Address Information -->
                            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                                <h3 style="margin: 0 0 15px 0; color: #333; border-bottom: 2px solid #17a2b8; padding-bottom: 8px;">Address</h3>
                                <div style="display: grid; gap: 15px;">
                                    <div>
                                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Street Address</label>
                                        <input type="text" name="street_address" value="${contact.street_address || ''}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                        <div>
                                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">City</label>
                                            <input type="text" name="city" value="${contact.city || ''}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                        </div>
                                        <div>
                                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">State</label>
                                            <input type="text" name="state" value="${contact.state || ''}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                        </div>
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                        <div>
                                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">ZIP Code</label>
                                            <input type="text" name="zip_code" value="${contact.zip_code || ''}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                        </div>
                                        <div>
                                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Country</label>
                                            <input type="text" name="country" value="${contact.country || ''}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Comments -->
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                            <h3 style="margin: 0 0 15px 0; color: #333; border-bottom: 2px solid #6c757d; padding-bottom: 8px;">Comments</h3>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Notes</label>
                                <textarea name="comments_from_lead" rows="4" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; resize: vertical;">${contact.comments_from_lead || ''}</textarea>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 20px;">
                            <button type="button" onclick="viewContact(${contact.id})" style="background: #6c757d; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer;">
                                Cancel
                            </button>
                            <button type="submit" style="background: #28a745; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer;">
                                💾 Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            `;
        }
        
        function saveContact(contactId) {
            const form = document.getElementById('editContactForm');
            const formData = new FormData(form);
            formData.append('contact_id', contactId);
            
            // Show loading state
            const saveBtn = form.querySelector('button[type="submit"]');
            const originalText = saveBtn.textContent;
            saveBtn.textContent = '💾 Saving...';
            saveBtn.disabled = true;
            
            fetch('/user/api/update-contact.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Contact updated successfully: ${data.contact_name}`);
                    // Reload the page to show updated contact
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to update contact'));
                    
                    // Restore button
                    saveBtn.textContent = originalText;
                    saveBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error updating contact:', error);
                alert('Error updating contact. Please try again.');
                
                // Restore button
                saveBtn.textContent = originalText;
                saveBtn.disabled = false;
            });
        }
        
        
        function deleteContact(contactId) {
            if (confirm('Are you sure you want to delete this contact? This action cannot be undone and will revert any associated lead back to "New" status.')) {
                const deleteBtn = document.querySelector(`[onclick="deleteContact(${contactId})"]`);
                const originalText = deleteBtn.textContent;
                deleteBtn.textContent = '🗑️ Deleting...';
                deleteBtn.disabled = true;
                
                const formData = new FormData();
                formData.append('contact_id', contactId);
                
                fetch('/user/api/delete-contact.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let message = `Contact deleted successfully: ${data.contact_name}`;
                        if (data.lead_reverted) {
                            message += `\n\nAssociated lead has been reverted back to "New" status.`;
                        }
                        alert(message);
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to delete contact'));
                        deleteBtn.textContent = originalText;
                        deleteBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error deleting contact:', error);
                    alert('Error deleting contact. Please try again.');
                    deleteBtn.textContent = originalText;
                    deleteBtn.disabled = false;
                });
            }
        }
        
        function closeModal() {
            document.getElementById('contactModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('contactModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
