<?php
/**
 * Admin Invitations Analytics
 * System-wide invitation tracking and analytics
 */

require_once __DIR__ . '/includes/AdminAuth.php';
require_once __DIR__ . '/../api/includes/Database.php';

// Require authentication
AdminAuth::requireAuth();

$db = Database::getInstance();

// Get filters
$userFilter = $_GET['user_id'] ?? '';
$cardFilter = $_GET['card_id'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Build query
$whereConditions = ["1=1"];
$params = [];

if ($userFilter) {
    $whereConditions[] = "i.inviter_user_id = ?";
    $params[] = $userFilter;
}

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
    "SELECT i.*, bc.name as card_name, bc.company, bc.title,
            u.email as inviter_email, u.first_name as inviter_first_name, u.last_name as inviter_last_name,
            CASE 
                WHEN i.created_account = 1 THEN 'Converted'
                WHEN i.response_type = 'interested' THEN 'Interested'
                WHEN i.response_type = 'not_interested' THEN 'Not Interested'
                WHEN i.opened_at IS NOT NULL THEN 'Viewed'
                ELSE 'Unopened'
            END as status_summary
     FROM invitations i
     JOIN business_cards bc ON i.business_card_id = bc.id
     JOIN users u ON i.inviter_user_id = u.id
     WHERE {$whereClause}
     ORDER BY i.sent_at DESC",
    $params
);

// Get all users for filter
$allUsers = $db->query(
    "SELECT id, email, first_name, last_name FROM users 
     WHERE is_active = 1 
     ORDER BY created_at DESC"
);

// Get all business cards for filter
$allCards = $db->query(
    "SELECT id, first_name, last_name, company_name, job_title FROM business_cards 
     WHERE is_active = 1 
     ORDER BY created_at DESC"
);

// Get system-wide summary stats
$stats = $db->querySingle(
    "SELECT 
        COUNT(*) as total_sent,
        COUNT(DISTINCT inviter_user_id) as total_inviters,
        SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as total_opened,
        SUM(CASE WHEN response_type = 'interested' THEN 1 ELSE 0 END) as total_interested,
        SUM(CASE WHEN response_type = 'not_interested' THEN 1 ELSE 0 END) as total_not_interested,
        SUM(CASE WHEN created_account = 1 THEN 1 ELSE 0 END) as total_converted,
        ROUND(SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as open_rate,
        ROUND(SUM(CASE WHEN response_type = 'interested' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as interest_rate,
        ROUND(SUM(CASE WHEN created_account = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as conversion_rate
     FROM invitations"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation Analytics - ShareMyCard Admin</title>
    <link rel="stylesheet" href="/admin/includes/admin-style.css">
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
    <?php include __DIR__ . '/includes/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>📧 Invitation Analytics</h1>
            <p>System-wide invitation tracking and performance metrics.</p>
        </div>

        <!-- Summary Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_sent'] ?? 0; ?></div>
                <div class="stat-label">Total Sent</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_inviters'] ?? 0; ?></div>
                <div class="stat-label">Active Inviters</div>
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
                    <label for="user_id">Inviter</label>
                    <select name="user_id" id="user_id">
                        <option value="">All Users</option>
                        <?php foreach ($allUsers as $user): ?>
                            <option value="<?php echo htmlspecialchars($user['id']); ?>" 
                                    <?php echo ($userFilter === $user['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['email']); ?>
                                <?php if ($user['first_name'] || $user['last_name']): ?>
                                    (<?php echo htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="card_id">Business Card</label>
                    <select name="card_id" id="card_id">
                        <option value="">All Cards</option>
                        <?php foreach ($allCards as $card): ?>
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
                    <a href="/admin/invitations.php" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>

        <!-- Invitations Table -->
        <div class="analytics-table">
            <div class="table-container">
                <?php if (empty($invitations)): ?>
                    <div class="no-data">
                        <h3>No invitations found</h3>
                        <p>No invitations match the current filters.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Inviter</th>
                                <th>Invitee Name</th>
                                <th>Email</th>
                                <th>Business Card</th>
                                <th>Comment</th>
                                <th>Sent</th>
                                <th>Opened</th>
                                <th>Response</th>
                                <th>Account Created</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invitations as $invitation): ?>
                                <tr>
                                    <td>
                                        <a href="/admin/users.php?search=<?php echo urlencode($invitation['inviter_email']); ?>" 
                                           style="color: #667eea; text-decoration: none;">
                                            <?php echo htmlspecialchars($invitation['inviter_email']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($invitation['invitee_first_name'] . ' ' . $invitation['invitee_last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($invitation['invitee_email']); ?></td>
                                    <td>
                                        <a href="/admin/cards/view.php?id=<?php echo urlencode($invitation['business_card_id']); ?>" 
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
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
