<?php
/**
 * Admin Leads View
 * Display all leads across all users (read-only)
 */

require_once __DIR__ . '/../includes/AdminAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';

AdminAuth::requireAuth();

$db = Database::getInstance();

// Get all leads with user and business card information
$leads = $db->query("
    SELECT l.*, bc.first_name as card_first_name, bc.last_name as card_last_name,
           u.email as owner_email, u.id as owner_id,
           CASE WHEN EXISTS (SELECT 1 FROM contacts c WHERE c.id_lead = l.id) 
                THEN 'converted' ELSE 'new' END as status
    FROM leads l
    JOIN business_cards bc ON l.id_business_card = bc.id
    JOIN users u ON bc.user_id = u.id
    ORDER BY l.created_at DESC
");

$leadCount = count($leads);
$newLeads = array_filter($leads, function($lead) { return $lead['status'] === 'new'; });
$convertedLeads = array_filter($leads, function($lead) { return $lead['status'] === 'converted'; });

// Get unique users count
$uniqueUsers = $db->querySingle("
    SELECT COUNT(DISTINCT bc.user_id) as count
    FROM leads l
    JOIN business_cards bc ON l.id_business_card = bc.id
")['count'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Leads - Admin Dashboard</title>
    <link rel="stylesheet" href="/admin/includes/admin-style.css">
    <style>
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .admin-title {
            font-size: 2.5em;
            font-weight: 700;
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
            border-left: 4px solid #dc3545;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: 700;
            color: #dc3545;
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
        
        .leads-list {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .lead-item {
            padding: 20px 25px;
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
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
        
        .owner-info {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 6px;
            margin-top: 10px;
        }
        
        .owner-label {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
        }
        
        .owner-details {
            font-weight: 500;
            color: #333;
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
            margin: 0;
            font-size: 1.1em;
        }
        
        .export-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .export-btn:hover {
            transform: translateY(-1px);
        }
        
        @media (max-width: 768px) {
            .admin-header {
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
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container">
        <div class="admin-header">
            <h1 class="admin-title">All Leads</h1>
            <div class="search-filter">
                <input type="text" id="searchInput" class="search-input" placeholder="Search leads...">
                <select id="statusFilter" class="filter-select">
                    <option value="">All Status</option>
                    <option value="new">New</option>
                    <option value="converted">Converted</option>
                </select>
                <select id="userFilter" class="filter-select">
                    <option value="">All Users</option>
                    <?php
                    $users = $db->query("
                        SELECT DISTINCT u.id, u.email, u.first_name, u.last_name
                        FROM users u
                        JOIN business_cards bc ON u.id = bc.user_id
                        JOIN leads l ON bc.id = l.id_business_card
                        ORDER BY u.email
                    ");
                    foreach ($users as $user) {
                        echo '<option value="' . htmlspecialchars($user['id']) . '">' . 
                             htmlspecialchars($user['email']) . '</option>';
                    }
                    ?>
                </select>
                <button class="export-btn" onclick="exportLeads()">
                    📊 Export Data
                </button>
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
                <div class="stat-number"><?= $uniqueUsers ?></div>
                <div class="stat-label">Users with Leads</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $leadCount > 0 ? round((count($convertedLeads) / $leadCount) * 100) : 0 ?>%</div>
                <div class="stat-label">Conversion Rate</div>
            </div>
        </div>
        
        <!-- Leads List -->
        <div class="leads-container">
            <div class="leads-header">
                <h2 class="leads-title">All Leads (Read-Only)</h2>
            </div>
            
            <div class="leads-list" id="leadsList">
                <?php if (empty($leads)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📋</div>
                        <h3>No leads found</h3>
                        <p>No leads have been captured yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($leads as $lead): ?>
                        <div class="lead-item" data-status="<?= $lead['status'] ?>" data-user="<?= $lead['owner_id'] ?>" data-name="<?= htmlspecialchars(strtolower($lead['first_name'] . ' ' . $lead['last_name'])) ?>">
                            <div class="lead-header">
                                <h3 class="lead-name"><?= htmlspecialchars($lead['first_name'] . ' ' . $lead['last_name']) ?></h3>
                                <span class="lead-status status-<?= $lead['status'] ?>"><?= ucfirst($lead['status']) ?></span>
                            </div>
                            
                            <div class="lead-info">
                                <?php if ($lead['email_primary']): ?>
                                    <div class="info-item">
                                        <span class="info-label">Email:</span>
                                        <span class="info-value"><?= htmlspecialchars($lead['email_primary']) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($lead['work_phone']): ?>
                                    <div class="info-item">
                                        <span class="info-label">Phone:</span>
                                        <span class="info-value"><?= htmlspecialchars($lead['work_phone']) ?></span>
                                    </div>
                                <?php endif; ?>
                                
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
                                
                                <div class="info-item">
                                    <span class="info-label">From Card:</span>
                                    <span class="info-value"><?= htmlspecialchars($lead['card_first_name'] . ' ' . $lead['card_last_name']) ?></span>
                                </div>
                            </div>
                            
                            <?php if ($lead['comments_from_lead']): ?>
                                <div style="margin-bottom: 15px;">
                                    <strong>Message:</strong>
                                    <p style="margin: 5px 0 0 0; color: #666; font-style: italic;">
                                        "<?= htmlspecialchars($lead['comments_from_lead']) ?>"
                                    </p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="owner-info">
                                <div class="owner-label">Lead Owner:</div>
                                <div class="owner-details">
                                    <?= htmlspecialchars($lead['owner_email']) ?>
                                    (ID: <?= htmlspecialchars($lead['owner_id']) ?>)
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Search and filter functionality
        document.getElementById('searchInput').addEventListener('input', filterLeads);
        document.getElementById('statusFilter').addEventListener('change', filterLeads);
        document.getElementById('userFilter').addEventListener('change', filterLeads);
        
        function filterLeads() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const userFilter = document.getElementById('userFilter').value;
            const leadItems = document.querySelectorAll('.lead-item');
            
            leadItems.forEach(item => {
                const name = item.dataset.name;
                const status = item.dataset.status;
                const user = item.dataset.user;
                
                const matchesSearch = name.includes(searchTerm);
                const matchesStatus = !statusFilter || status === statusFilter;
                const matchesUser = !userFilter || user === userFilter;
                
                if (matchesSearch && matchesStatus && matchesUser) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        function exportLeads() {
            // TODO: Implement CSV export
            alert('Export functionality coming soon!');
        }
    </script>
</body>
</html>
