<?php
/**
 * Invitation Analytics Page
 * Track invitation performance and responses
 */

require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../includes/UserAuth.php';

// Require authentication
UserAuth::requireAuth();

$db = Database::getInstance();
$userId = UserAuth::getUserId();

// Get filters
$cardFilter = $_GET['card_id'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Build query
$whereConditions = ["i.inviter_user_id = ?"];
$params = [$userId];

if ($cardFilter) {
    $whereConditions[] = "i.business_card_id = ?";
    $params[] = $cardFilter;
}

if ($statusFilter) {
    switch ($statusFilter) {
        case 'converted':
            $whereConditions[] = "i.created_account = 1";
            break;
        case 'interested':
            $whereConditions[] = "i.response_type = 'interested'";
            break;
        case 'not_interested':
            $whereConditions[] = "i.response_type = 'not_interested'";
            break;
        case 'no_response':
            $whereConditions[] = "i.response_type = 'no_response'";
            break;
    }
}

if ($dateFrom) {
    $whereConditions[] = "i.sent_at >= ?";
    $params[] = $dateFrom . ' 00:00:00';
}

if ($dateTo) {
    $whereConditions[] = "i.sent_at <= ?";
    $params[] = $dateTo . ' 23:59:59';
}

$whereClause = implode(' AND ', $whereConditions);

// Get invitations
$invitations = $db->query(
    "SELECT i.*, 
            CONCAT(bc.first_name, ' ', bc.last_name) as card_name, 
            bc.company_name, 
            bc.job_title,
            CASE 
                WHEN i.created_account = 1 THEN 'Converted'
                WHEN i.response_type = 'interested' THEN 'Interested'
                WHEN i.response_type = 'not_interested' THEN 'Not Interested'
                WHEN i.opened_at IS NOT NULL THEN 'Viewed'
                ELSE 'Unopened'
            END as status_summary
     FROM invitations i
     JOIN business_cards bc ON i.business_card_id = bc.id
     WHERE {$whereClause}
     ORDER BY i.sent_at DESC",
    $params
);

// Get user's business cards for filter
$userCards = $db->query(
    "SELECT id, first_name, last_name, company_name, job_title FROM business_cards 
     WHERE user_id = ? AND is_active = 1 
     ORDER BY created_at DESC",
    [$userId]
);

// Get summary stats
$stats = $db->querySingle(
    "SELECT 
        COUNT(*) as total_sent,
        SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as total_opened,
        SUM(CASE WHEN response_type = 'interested' THEN 1 ELSE 0 END) as total_interested,
        SUM(CASE WHEN response_type = 'not_interested' THEN 1 ELSE 0 END) as total_not_interested,
        SUM(CASE WHEN created_account = 1 THEN 1 ELSE 0 END) as total_converted,
        ROUND(SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as open_rate,
        ROUND(SUM(CASE WHEN response_type = 'interested' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as interest_rate,
        ROUND(SUM(CASE WHEN created_account = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as conversion_rate
     FROM invitations 
     WHERE inviter_user_id = ?",
    [$userId]
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation Analytics - ShareMyCard</title>
    <link rel="stylesheet" href="/user/includes/user-style.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .filter-row {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 150px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .analytics-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-converted {
            background: #d4edda;
            color: #155724;
        }
        
        .status-interested {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-not-interested {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-viewed {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-unopened {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .comment-cell {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .comment-cell:hover {
            white-space: normal;
            overflow: visible;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-brand">
            <a href="/user/dashboard.php">📱 ShareMyCard</a>
        </div>
        
        <button class="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <div class="nav-links mobile">
            <a href="/user/dashboard.php" class="nav-link">🏠 Dashboard</a>
            <a href="/user/cards/create.php" class="nav-link">➕ New Card</a>
            <a href="/user/cards/analytics.php" class="nav-link">📊 Analytics</a>
            <a href="/user/cards/invitation-analytics.php" class="nav-link">📧 Invitations</a>
            <a href="/user/cards/email-signature.php" class="nav-link">✉️ Email Signature</a>
            <a href="/user/cards/name-tags.php" class="nav-link">🏷️ Name Tags</a>
            <a href="/user/cards/virtual-background.php" class="nav-link">🖼️ Virtual Background</a>
            <a href="https://github.com/mwarrick/digital-business-card/issues" class="nav-link" target="_blank">🐛 Report Issues</a>
            <a href="#" onclick="openAccountSecurity()" class="nav-link">🔒 Security</a>
            <a href="/user/logout.php" class="nav-link">🚪 Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>📧 Invitation Analytics</h1>
            <p>Track the performance of your invitations and see who's interested in joining ShareMyCard.</p>
        </div>

        <!-- Summary Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_sent'] ?? 0; ?></div>
                <div class="stat-label">Total Sent</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['open_rate'] ?? 0; ?>%</div>
                <div class="stat-label">Open Rate</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['interest_rate'] ?? 0; ?>%</div>
                <div class="stat-label">Interest Rate</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['conversion_rate'] ?? 0; ?>%</div>
                <div class="stat-label">Conversion Rate</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" class="filter-row">
                <div class="filter-group">
                    <label for="card_id">Business Card</label>
                    <select name="card_id" id="card_id">
                        <option value="">All Cards</option>
                        <?php foreach ($userCards as $card): ?>
                            <option value="<?php echo htmlspecialchars($card['id']); ?>" 
                                    <?php echo ($cardFilter === $card['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($card['first_name'] . ' ' . $card['last_name']); ?>
                                <?php if ($card['company_name']): ?>
                                    - <?php echo htmlspecialchars($card['company_name']); ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="status">Status</label>
                    <select name="status" id="status">
                        <option value="">All Statuses</option>
                        <option value="converted" <?php echo ($statusFilter === 'converted') ? 'selected' : ''; ?>>Converted</option>
                        <option value="interested" <?php echo ($statusFilter === 'interested') ? 'selected' : ''; ?>>Interested</option>
                        <option value="not_interested" <?php echo ($statusFilter === 'not_interested') ? 'selected' : ''; ?>>Not Interested</option>
                        <option value="no_response" <?php echo ($statusFilter === 'no_response') ? 'selected' : ''; ?>>No Response</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="date_from">From Date</label>
                    <input type="date" name="date_from" id="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                </div>
                
                <div class="filter-group">
                    <label for="date_to">To Date</label>
                    <input type="date" name="date_to" id="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="/user/cards/invitation-analytics.php" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>

        <!-- Invitations Table -->
        <div class="analytics-table">
            <div class="table-container">
                <?php if (empty($invitations)): ?>
                    <div class="no-data">
                        <h3>No invitations found</h3>
                        <p>Start inviting people to see analytics here.</p>
                        <a href="/user/cards/invite.php" class="btn btn-primary">Send First Invitation</a>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Invitee Name</th>
                                <th>Email</th>
                                <th>Business Card</th>
                                <th>Comment</th>
                                <th>Sent</th>
                                <th>Opened</th>
                                <th>Response</th>
                                <th>Account Created</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invitations as $invitation): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($invitation['invitee_first_name'] . ' ' . $invitation['invitee_last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($invitation['invitee_email']); ?></td>
                                    <td>
                                        <a href="/user/cards/view.php?id=<?php echo urlencode($invitation['business_card_id']); ?>" 
                                           style="color: #667eea; text-decoration: none;">
                                            <?php echo htmlspecialchars($invitation['card_name']); ?>
                                        </a>
                                    </td>
                                    <td class="comment-cell" title="<?php echo htmlspecialchars($invitation['comment']); ?>">
                                        <?php echo htmlspecialchars($invitation['comment']); ?>
                                    </td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($invitation['sent_at'])); ?></td>
                                    <td>
                                        <?php if ($invitation['opened_at']): ?>
                                            <?php echo date('M j, Y g:i A', strtotime($invitation['opened_at'])); ?>
                                        <?php else: ?>
                                            <span style="color: #999;">Not opened</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($invitation['response_type'] === 'interested'): ?>
                                            <span style="color: #28a745;">Interested</span>
                                        <?php elseif ($invitation['response_type'] === 'not_interested'): ?>
                                            <span style="color: #dc3545;">Not Interested</span>
                                        <?php else: ?>
                                            <span style="color: #999;">No Response</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($invitation['created_account']): ?>
                                            <span style="color: #28a745;">✓ Yes</span>
                                        <?php else: ?>
                                            <span style="color: #999;">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $invitation['status_summary'])); ?>">
                                            <?php echo htmlspecialchars($invitation['status_summary']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button onclick="resendInvitation('<?php echo htmlspecialchars($invitation['id']); ?>')" 
                                                class="btn-small" 
                                                style="background: #17a2b8; color: white; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-size: 12px;">
                                            📧 Resend
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-title">Modal Title</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p id="modal-message">Modal message</p>
            </div>
            <div class="modal-footer">
                <button onclick="closeModal()" class="btn btn-primary">OK</button>
            </div>
        </div>
    </div>

    <style>
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            color: #333;
        }

        .close {
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            color: #999;
        }

        .close:hover {
            color: #333;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 20px;
            border-top: 1px solid #eee;
            text-align: right;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a6fd8;
        }

        .modal.success .modal-header h3 {
            color: #28a745;
        }

        .modal.error .modal-header h3 {
            color: #dc3545;
        }
    </style>

    <script>
        // Navigation toggle functionality
        document.addEventListener("DOMContentLoaded", function() {
            const navToggle = document.querySelector(".hamburger");
            const navMenu = document.querySelector(".nav-links.mobile");
            
            if (navToggle && navMenu) {
                navToggle.addEventListener("click", function() {
                    navMenu.classList.toggle("active");
                    navToggle.classList.toggle("active");
                });
            }
        });

        // Modal functions
        function showModal(title, message, type = 'info') {
            const modal = document.getElementById('modal');
            const modalTitle = document.getElementById('modal-title');
            const modalMessage = document.getElementById('modal-message');
            
            modalTitle.textContent = title;
            modalMessage.textContent = message;
            
            // Remove existing type classes
            modal.classList.remove('success', 'error', 'info');
            // Add new type class
            modal.classList.add(type);
            
            modal.style.display = 'flex';
        }

        function closeModal() {
            const modal = document.getElementById('modal');
            modal.style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('modal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Resend invitation function
        function resendInvitation(invitationId) {
            console.log('RESEND DEBUG - Starting resend for invitation ID:', invitationId);
            
            // Show loading state
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '⏳ Sending...';
            button.disabled = true;

            console.log('RESEND DEBUG - Making fetch request to /user/api/resend-invitation.php');
            
            fetch('/user/api/resend-invitation-simple.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    invitation_id: invitationId
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    showModal('Success', 'Invitation resent successfully!', 'success');
                    // Optionally refresh the page to show updated timestamp
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showModal('Error', 'Error resending invitation: ' + (data.error || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showModal('Error', 'Error resending invitation. Please try again.', 'error');
            })
            .finally(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }
    </script>
    <script src="/user/includes/user-script.js"></script>
</body>
</html>
