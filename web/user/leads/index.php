<?php
/**
 * User Leads Dashboard
 * Display and manage captured leads
 */

require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';

UserAuth::requireAuth();

$user = UserAuth::getUser();
$db = Database::getInstance();

// Get user's leads from both sources: business cards and (optionally) custom QRs
$userId = UserAuth::getUserId();

// Detect if leads.id_custom_qr_code or leads.qr_id column exists
$hasIdCustomQr = false;
$hasQrId = false;
$hasQrLeads = false;
try {
    $col = $db->querySingle("SHOW COLUMNS FROM leads LIKE 'id_custom_qr_code'");
    $hasIdCustomQr = !empty($col);
} catch (Exception $e) {
    $hasIdCustomQr = false;
}
if (!$hasIdCustomQr) {
    try { $col = $db->querySingle("SHOW COLUMNS FROM leads LIKE 'qr_id'"); $hasQrId = !empty($col); } catch (Exception $e) { $hasQrId = false; }
}
// Detect qr_leads mapping table
try {
    $tbl = $db->querySingle("SHOW TABLES LIKE 'qr_leads'");
    $hasQrLeads = !empty($tbl);
} catch (Exception $e) {
    $hasQrLeads = false;
}

$params = [$userId, $userId];
$selectQr = ", NULL AS qr_title, NULL AS qr_type";
$joinQrLead = "";
$joinQr   = "";
$whereQr  = "";

// Build joins to resolve QR linkage from any available schema: prefer qr_leads mapping, then direct columns
if ($hasIdCustomQr || $hasQrId || $hasQrLeads) {
    $selectQr = ", cqr.title AS qr_title, cqr.type AS qr_type";
    $onParts = [];
    if ($hasQrLeads) {
        $joinQrLead = " LEFT JOIN qr_leads ql ON ql.lead_id = l.id";
        // Prefer mapping table
        $onParts[] = "ql.qr_id = cqr.id";
    }
    if ($hasIdCustomQr) { $onParts[] = "l.id_custom_qr_code = cqr.id"; }
    if ($hasQrId) { $onParts[] = "l.qr_id = cqr.id"; }
    $onClause = implode(' OR ', $onParts);
    $joinQr   = " LEFT JOIN custom_qr_codes cqr ON (" . $onClause . ")";
    $whereQr  = " OR (cqr.user_id = ?)";
    $params[] = $userId;
}

$sql = "
    SELECT l.*,
           bc.first_name  AS card_first_name,
           bc.last_name   AS card_last_name,
           bc.company_name AS card_company,
           bc.job_title   AS card_job_title
           $selectQr,
           cqr.id AS qr_cqr_id,
           CASE WHEN EXISTS (SELECT 1 FROM contacts c WHERE c.id_lead = l.id)
                THEN 'converted' ELSE 'new' END AS status
    FROM leads l
    LEFT JOIN business_cards bc ON l.id_business_card = bc.id
    $joinQrLead
    $joinQr
    WHERE (l.id_user = ?)
       OR (bc.user_id = ?)
       $whereQr
    ORDER BY l.created_at DESC
";

$leads = $db->query($sql, $params);

$leadCount = count($leads);
$newLeads = array_filter($leads, function($lead) { return $lead['status'] === 'new'; });
$convertedLeads = array_filter($leads, function($lead) { return $lead['status'] === 'converted'; });

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Leads - ShareMyCard</title>
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
            border-left: 4px solid #667eea;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: 700;
            color: #667eea;
            margin: 0 0 10px 0;
        }
        
        .stat-label {
            color: #666;
            font-size: 1.1em;
            margin: 0;
        }
        
        .leads-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .leads-header {
            background: #f8f9fa;
            padding: 20px 25px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .leads-title {
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
        
        .leads-list {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .lead-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s ease;
        }
        
        .lead-item:hover {
            background: #f8f9fa;
        }
        
        .lead-item:last-child {
            border-bottom: none;
        }
        
        .lead-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .lead-name {
            font-size: 1.3em;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .lead-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 500;
        }
        
        .status-new {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .status-converted {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .lead-info {
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
        
        .lead-actions {
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
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-1px);
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
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
            
            .lead-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .lead-info {
                grid-template-columns: 1fr;
            }
        }
        
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navigation.php'; ?>
    
    <div class="container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">My Leads</h1>
            <div class="search-filter">
                <input type="text" id="searchInput" class="search-input" placeholder="Search leads...">
                <select id="statusFilter" class="filter-select">
                    <option value="">All Status</option>
                    <option value="new">New</option>
                    <option value="converted">Converted</option>
                </select>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $leadCount ?></div>
                <div class="stat-label">Total Leads</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($newLeads) ?></div>
                <div class="stat-label">New Leads</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($convertedLeads) ?></div>
                <div class="stat-label">Converted</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $leadCount > 0 ? round((count($convertedLeads) / $leadCount) * 100) : 0 ?>%</div>
                <div class="stat-label">Conversion Rate</div>
            </div>
        </div>
        
        <!-- Leads List -->
        <div class="leads-container">
            <div class="leads-header">
                <h2 class="leads-title">All Leads</h2>
            </div>
            
            <div class="leads-list" id="leadsList">
                <?php if (empty($leads)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📋</div>
                        <h3>No leads yet</h3>
                        <p>When people fill out your lead capture form, they'll appear here.</p>
                        <p>Share your business card to start capturing leads!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($leads as $lead): ?>
                        <div class="lead-item" data-status="<?= $lead['status'] ?>" data-name="<?= htmlspecialchars(strtolower($lead['first_name'] . ' ' . $lead['last_name'])) ?>">
                            <div class="lead-header">
                                <h3 class="lead-name"><?= htmlspecialchars($lead['first_name'] . ' ' . $lead['last_name']) ?></h3>
                                <span class="lead-status status-<?= $lead['status'] ?>"><?= ucfirst($lead['status']) ?></span>
                            </div>
                            
                            <div class="lead-info">
                                <div class="info-item">
                                    <span class="info-label">Source:</span>
                                    <span class="info-value">
                                        <?php if (!empty($lead['id_business_card'])): ?>
                                            <a href="/card.php?id=<?= urlencode($lead['id_business_card']) ?>" target="_blank" style="color:#667eea; text-decoration:none;">
                                                Card <?= htmlspecialchars(trim(($lead['card_first_name'] ?? '') . ' ' . ($lead['card_last_name'] ?? ''))) ?>
                                            </a>
                                        <?php elseif (!empty($lead['id_custom_qr_code']) || !empty($lead['qr_id']) || !empty($lead['qr_title']) || !empty($lead['qr_cqr_id'])): ?>
                                            <?php $qrEditId = $lead['qr_cqr_id'] ?? ($lead['qr_id'] ?? $lead['id_custom_qr_code'] ?? ''); ?>
                                            <?php $qrLabel = 'QR ' . htmlspecialchars($lead['qr_type'] ?? 'custom') . (!empty($lead['qr_title']) ? ': ' . htmlspecialchars($lead['qr_title']) : ''); ?>
                                            <?php if (!empty($qrEditId)): ?>
                                                <a href="/user/qr/edit.php?id=<?= urlencode($qrEditId) ?>" style="color:#667eea; text-decoration:none;">
                                                    <?= $qrLabel ?>
                                                </a>
                                            <?php else: ?>
                                                <?= $qrLabel ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            Unknown
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php if ($lead['organization_name']): ?>
                                    <div class="info-item">
                                        <span class="info-label">Company:</span>
                                        <span class="info-value"><?= htmlspecialchars($lead['organization_name']) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($lead['job_title']): ?>
                                    <div class="info-item">
                                        <span class="info-label">Title:</span>
                                        <span class="info-value"><?= htmlspecialchars($lead['job_title']) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="info-item">
                                    <span class="info-label">Captured:</span>
                                    <span class="info-value"><?= date('M j, Y g:i A', strtotime($lead['created_at'])) ?></span>
                                </div>
                            </div>
                            
                            
                            <div class="lead-actions">
                                <button class="btn btn-primary" onclick="viewLead(<?= $lead['id'] ?>)">
                                    👁️ View Details
                                </button>
                                
                                <?php if ($lead['status'] === 'new'): ?>
                                    <button class="btn btn-success" onclick="convertLead(<?= $lead['id'] ?>)">
                                        ➕ Convert to Contact
                                    </button>
                                <?php endif; ?>
                                
                                
                                <button class="btn btn-danger" onclick="deleteLead(<?= $lead['id'] ?>)">
                                    🗑️ Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Lead Details Modal -->
    <div id="leadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Lead Details</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div id="leadDetails">
                <!-- Lead details will be loaded here -->
            </div>
        </div>
    </div>
    
    <script>
        // Search and filter functionality
        document.getElementById('searchInput').addEventListener('input', filterLeads);
        document.getElementById('statusFilter').addEventListener('change', filterLeads);
        
        function filterLeads() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const leadItems = document.querySelectorAll('.lead-item');
            
            leadItems.forEach(item => {
                const name = item.dataset.name;
                const status = item.dataset.status;
                
                const matchesSearch = name.includes(searchTerm);
                const matchesStatus = !statusFilter || status === statusFilter;
                
                if (matchesSearch && matchesStatus) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        function viewLead(leadId) {
            // Show loading state
            const modal = document.getElementById('leadModal');
            const leadDetails = document.getElementById('leadDetails');
            leadDetails.innerHTML = '<div style="text-align: center; padding: 20px;">Loading lead details...</div>';
            modal.style.display = 'block';
            
            // Fetch lead details
            fetch(`/user/api/get-lead.php?id=${leadId}`)
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
                            console.error('JSON parse error:', e);
                            console.error('Raw text:', text);
                            throw new Error('Invalid JSON response');
                        }
                    });
                })
                .then(data => {
                    console.log('Parsed data:', data);
                    if (data.success) {
                        displayLeadDetails(data.data);
                    } else {
                        leadDetails.innerHTML = `<div style="color: red; text-align: center; padding: 20px;">Error: ${data.message}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching lead details:', error);
                    leadDetails.innerHTML = '<div style="color: red; text-align: center; padding: 20px;">Error loading lead details. Please try again.</div>';
                });
        }
        
        function displayLeadDetails(lead) {
            const leadDetails = document.getElementById('leadDetails');
            
            // Format dates
            const capturedDate = new Date(lead.created_at).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            const updatedDate = new Date(lead.updated_at).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            // Status badge
            const statusClass = lead.status === 'converted' ? 'status-converted' : 'status-new';
            const statusText = lead.status === 'converted' ? 'Converted' : 'New';
            
            // Converted contact info
            let convertedInfo = '';
            if (lead.converted_contact) {
                convertedInfo = `
                    <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #28a745;">
                        <h4 style="margin: 0 0 10px 0; color: #2e7d32;">✅ Converted to Contact</h4>
                        <p style="margin: 0; color: #2e7d32;">
                            <strong>Contact Name:</strong> ${lead.converted_contact.first_name} ${lead.converted_contact.last_name}<br>
                            <strong>Contact Email:</strong> ${lead.converted_contact.email_primary || 'Not provided'}<br>
                            <strong>Converted:</strong> ${new Date(lead.converted_contact.created_at).toLocaleDateString()}
                        </p>
                    </div>
                `;
            }
            
            leadDetails.innerHTML = `
                <div style="padding: 20px;">
                    <!-- Lead Header -->
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                        <div>
                            <h2 style="margin: 0 0 5px 0; color: #333;">${lead.first_name} ${lead.last_name}</h2>
                            <p style="margin: 0; color: #666; font-size: 16px;">${lead.email_primary || 'No email provided'}</p>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <span class="status-badge ${statusClass}">${statusText}</span>
                        </div>
                    </div>
                    
                    ${convertedInfo}
                    
                    <!-- Lead Information -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 20px;">
                        <!-- Contact Information -->
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                            <h3 style="margin: 0 0 15px 0; color: #333; border-bottom: 2px solid #007bff; padding-bottom: 8px;">Contact Information</h3>
                            <div style="display: grid; gap: 10px;">
                                ${lead.work_phone ? `<div><strong>Work Phone:</strong> ${lead.work_phone}</div>` : ''}
                                ${lead.mobile_phone ? `<div><strong>Mobile Phone:</strong> ${lead.mobile_phone}</div>` : ''}
                                ${lead.organization_name ? `<div><strong>Company:</strong> ${lead.organization_name}</div>` : ''}
                                ${lead.job_title ? `<div><strong>Job Title:</strong> ${lead.job_title}</div>` : ''}
                                ${lead.website_url ? `<div><strong>Website:</strong> <a href="${lead.website_url}" target="_blank">${lead.website_url}</a></div>` : ''}
                            </div>
                        </div>
                        
                        <!-- Address Information -->
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                            <h3 style="margin: 0 0 15px 0; color: #333; border-bottom: 2px solid #28a745; padding-bottom: 8px;">Address</h3>
                            <div style="display: grid; gap: 10px;">
                                ${lead.street_address ? `<div><strong>Street:</strong> ${lead.street_address}</div>` : ''}
                                ${lead.city ? `<div><strong>City:</strong> ${lead.city}</div>` : ''}
                                ${lead.state ? `<div><strong>State:</strong> ${lead.state}</div>` : ''}
                                ${lead.zip_code ? `<div><strong>ZIP:</strong> ${lead.zip_code}</div>` : ''}
                                ${lead.country ? `<div><strong>Country:</strong> ${lead.country}</div>` : ''}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Message/Comments -->
                    ${lead.comments_from_lead ? `
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                            <h3 style="margin: 0 0 15px 0; color: #333; border-bottom: 2px solid #ffc107; padding-bottom: 8px;">Message from Lead</h3>
                            <p style="margin: 0; white-space: pre-wrap; line-height: 1.6;">${lead.comments_from_lead}</p>
                        </div>
                    ` : ''}
                    
                    <!-- Business Card Information -->
                    <div style="background: #e3f2fd; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <h3 style="margin: 0 0 15px 0; color: #333; border-bottom: 2px solid #2196f3; padding-bottom: 8px;">From Business Card</h3>
                        <div style="display: grid; gap: 10px;">
                            <div><strong>Card Owner:</strong> ${lead.card_first_name} ${lead.card_last_name}</div>
                            ${lead.card_company ? `<div><strong>Company:</strong> ${lead.card_company}</div>` : ''}
                            ${lead.card_job_title ? `<div><strong>Job Title:</strong> ${lead.card_job_title}</div>` : ''}
                            ${lead.card_phone ? `<div><strong>Phone:</strong> ${lead.card_phone}</div>` : ''}
                            ${lead.card_bio ? `<div><strong>Bio:</strong> ${lead.card_bio}</div>` : ''}
                        </div>
                    </div>
                    
                    <!-- Metadata -->
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #6c757d;">
                        <h3 style="margin: 0 0 15px 0; color: #333;">Lead Information</h3>
                        <div style="display: grid; gap: 8px; font-size: 14px; color: #666;">
                            <div><strong>Captured:</strong> ${capturedDate}</div>
                            <div><strong>Last Updated:</strong> ${updatedDate}</div>
                            ${lead.ip_address ? `<div><strong>IP Address:</strong> ${lead.ip_address}</div>` : ''}
                            ${lead.user_agent ? `<div><strong>User Agent:</strong> ${lead.user_agent.substring(0, 100)}${lead.user_agent.length > 100 ? '...' : ''}</div>` : ''}
                            ${lead.referrer ? `<div><strong>Referrer:</strong> ${lead.referrer}</div>` : ''}
                        </div>
                    </div>
                </div>
            `;
        }
        
        function convertLead(leadId) {
            if (confirm('Convert this lead to a contact? This will create a new contact and link it to this lead.')) {
                // Show loading state
                const convertBtn = document.querySelector(`[onclick="convertLead(${leadId})"]`);
                const originalText = convertBtn.textContent;
                convertBtn.textContent = 'Converting...';
                convertBtn.disabled = true;
                
                // Convert lead to contact
                const formData = new FormData();
                formData.append('lead_id', leadId);
                
                fetch('/user/api/convert-lead.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        alert(`Lead successfully converted to contact: ${data.contact_name}`);
                        
                        // Reload the page to show updated status
                        location.reload();
                    } else {
                        // Show error message
                        alert('Error: ' + (data.message || 'Failed to convert lead'));
                        
                        // Restore button
                        convertBtn.textContent = originalText;
                        convertBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error converting lead:', error);
                    alert('Error converting lead. Please try again.');
                    
                    // Restore button
                    convertBtn.textContent = originalText;
                    convertBtn.disabled = false;
                });
            }
        }
        
        
        function deleteLead(leadId) {
            if (confirm('Are you sure you want to delete this lead? This action cannot be undone.')) {
                // Show loading state
                const deleteBtn = document.querySelector(`[onclick="deleteLead(${leadId})"]`);
                const originalText = deleteBtn.textContent;
                deleteBtn.textContent = 'Deleting...';
                deleteBtn.disabled = true;
                
                // Delete lead
                const formData = new FormData();
                formData.append('lead_id', leadId);
                
                fetch('/user/api/delete-lead.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        alert(`Lead deleted successfully: ${data.lead_name}`);
                        
                        // Reload the page to remove the deleted lead
                        location.reload();
                    } else {
                        // Show error message
                        alert('Error: ' + (data.message || 'Failed to delete lead'));
                        
                        // Restore button
                        deleteBtn.textContent = originalText;
                        deleteBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error deleting lead:', error);
                    alert('Error deleting lead. Please try again.');
                    
                    // Restore button
                    deleteBtn.textContent = originalText;
                    deleteBtn.disabled = false;
                });
            }
        }
        
        function closeModal() {
            document.getElementById('leadModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('leadModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
