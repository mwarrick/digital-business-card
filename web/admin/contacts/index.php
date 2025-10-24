<?php
/**
 * Admin Contacts View
 * Display all contacts across all users (read-only)
 */

require_once __DIR__ . '/../includes/AdminAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';

AdminAuth::requireAuth();

$db = Database::getInstance();

// Get all contacts with user and lead information
$contacts = $db->query("
    SELECT c.*, u.email as owner_email, u.id as owner_id,
           l.id as lead_id, bc.first_name as card_first_name, bc.last_name as card_last_name
    FROM contacts c
    JOIN users u ON c.id_user = u.id
    LEFT JOIN leads l ON c.id_lead = l.id
    LEFT JOIN business_cards bc ON l.id_business_card = bc.id
    ORDER BY c.created_at DESC
");

$contactCount = count($contacts);
$convertedFromLeads = array_filter($contacts, function($contact) { return $contact['lead_id'] !== null; });
$manualContacts = array_filter($contacts, function($contact) { return $contact['lead_id'] === null; });

// Get unique users count
$uniqueUsers = $db->querySingle("
    SELECT COUNT(DISTINCT c.id_user) as count
    FROM contacts c
")['count'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Contacts - Admin Dashboard</title>
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
        
        .contacts-title {
            font-size: 1.5em;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .contacts-list {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .contact-item {
            padding: 20px 25px;
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
        
        .contact-info {
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
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container">
        <div class="admin-header">
            <h1 class="admin-title">All Contacts</h1>
            <div class="search-filter">
                <input type="text" id="searchInput" class="search-input" placeholder="Search contacts...">
                <select id="sourceFilter" class="filter-select">
                    <option value="">All Sources</option>
                    <option value="converted">From Leads</option>
                    <option value="manual">Manual</option>
                </select>
                <select id="userFilter" class="filter-select">
                    <option value="">All Users</option>
                    <?php
                    $users = $db->query("
                        SELECT DISTINCT u.id, u.email, u.first_name, u.last_name
                        FROM users u
                        JOIN contacts c ON u.id = c.id_user
                        ORDER BY u.email
                    ");
                    foreach ($users as $user) {
                        echo '<option value="' . htmlspecialchars($user['id']) . '">' . 
                             htmlspecialchars($user['email']) . '</option>';
                    }
                    ?>
                </select>
                <button class="export-btn" onclick="exportContacts()">
                    📊 Export Data
                </button>
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
                <div class="stat-number"><?= $uniqueUsers ?></div>
                <div class="stat-label">Users with Contacts</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $contactCount > 0 ? round((count($convertedFromLeads) / $contactCount) * 100) : 0 ?>%</div>
                <div class="stat-label">From Leads</div>
            </div>
        </div>
        
        <!-- Contacts List -->
        <div class="contacts-container">
            <div class="contacts-header">
                <h2 class="contacts-title">All Contacts (Read-Only)</h2>
            </div>
            
            <div class="contacts-list" id="contactsList">
                <?php if (empty($contacts)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">👥</div>
                        <h3>No contacts found</h3>
                        <p>No contacts have been created yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($contacts as $contact): ?>
                        <div class="contact-item" data-source="<?= $contact['lead_id'] ? 'converted' : 'manual' ?>" data-user="<?= $contact['owner_id'] ?>" data-name="<?= htmlspecialchars(strtolower($contact['first_name'] . ' ' . $contact['last_name'])) ?>">
                            <div class="contact-header">
                                <h3 class="contact-name"><?= htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']) ?></h3>
                                <span class="contact-source source-<?= $contact['lead_id'] ? 'converted' : 'manual' ?>">
                                    <?= $contact['lead_id'] ? 'From Lead' : 'Manual' ?>
                                </span>
                            </div>
                            
                            <div class="contact-info">
                                <?php if ($contact['email_primary']): ?>
                                    <div class="info-item">
                                        <span class="info-label">Email:</span>
                                        <span class="info-value"><?= htmlspecialchars($contact['email_primary']) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($contact['work_phone']): ?>
                                    <div class="info-item">
                                        <span class="info-label">Work:</span>
                                        <span class="info-value"><?= htmlspecialchars($contact['work_phone']) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($contact['mobile_phone']): ?>
                                    <div class="info-item">
                                        <span class="info-label">Mobile:</span>
                                        <span class="info-value"><?= htmlspecialchars($contact['mobile_phone']) ?></span>
                                    </div>
                                <?php endif; ?>
                                
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
                                
                                <?php if ($contact['card_first_name']): ?>
                                    <div class="info-item">
                                        <span class="info-label">From Card:</span>
                                        <span class="info-value"><?= htmlspecialchars($contact['card_first_name'] . ' ' . $contact['card_last_name']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($contact['notes']): ?>
                                <div style="margin-bottom: 15px;">
                                    <strong>Notes:</strong>
                                    <p style="margin: 5px 0 0 0; color: #666; font-style: italic;">
                                        <?= htmlspecialchars($contact['notes']) ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="owner-info">
                                <div class="owner-label">Contact Owner:</div>
                                <div class="owner-details">
                                    <?= htmlspecialchars($contact['owner_email']) ?>
                                    (ID: <?= htmlspecialchars($contact['owner_id']) ?>)
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
        document.getElementById('searchInput').addEventListener('input', filterContacts);
        document.getElementById('sourceFilter').addEventListener('change', filterContacts);
        document.getElementById('userFilter').addEventListener('change', filterContacts);
        
        function filterContacts() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const sourceFilter = document.getElementById('sourceFilter').value;
            const userFilter = document.getElementById('userFilter').value;
            const contactItems = document.querySelectorAll('.contact-item');
            
            contactItems.forEach(item => {
                const name = item.dataset.name;
                const source = item.dataset.source;
                const user = item.dataset.user;
                
                const matchesSearch = name.includes(searchTerm);
                const matchesSource = !sourceFilter || source === sourceFilter;
                const matchesUser = !userFilter || user === userFilter;
                
                if (matchesSearch && matchesSource && matchesUser) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        function exportContacts() {
            // TODO: Implement CSV export
            alert('Export functionality coming soon!');
        }
    </script>
</body>
</html>
