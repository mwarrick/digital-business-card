<?php
/**
 * Business Cards Management Page
 */

require_once __DIR__ . '/includes/AdminAuth.php';

// Require authentication
AdminAuth::requireAuth();

$db = Database::getInstance();
$message = '';
$error = '';

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $card_id = $_POST['card_id'] ?? '';
    try {
        $db->execute("UPDATE business_cards SET is_active = 0, updated_at = NOW() WHERE id = ?", [$card_id]);
        $message = 'Business card deactivated successfully';
    } catch (Exception $e) {
        error_log("Card delete error: " . $e->getMessage());
        $error = 'Failed to deactivate card';
    }
}

// Get filters
$user_filter = $_GET['user'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where = ["bc.is_active = 1"];
$params = [];

if (!empty($user_filter)) {
    $where[] = "bc.user_id = ?";
    $params[] = $user_filter;
}

if (!empty($search)) {
    $where[] = "(bc.first_name LIKE ? OR bc.last_name LIKE ? OR bc.company_name LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$whereClause = implode(' AND ', $where);

// Get business cards with user info
$cards = $db->query(
    "SELECT bc.*, u.email as user_email
     FROM business_cards bc
     JOIN users u ON bc.user_id = u.id
     WHERE {$whereClause}
     ORDER BY bc.created_at DESC",
    $params
);

// Get total count
$total_cards = $db->querySingle("SELECT COUNT(*) as count FROM business_cards WHERE is_active = 1")['count'];

// Get users for filter
$users = $db->query("SELECT id, email FROM users WHERE is_active = 1 ORDER BY email");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Cards - ShareMyCard Admin</title>
    <link rel="stylesheet" href="/admin/includes/admin-style.css">
    <style>
        .card-preview {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .card-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 20px;
        }
        
        .card-info h3 {
            margin: 0;
            font-size: 16px;
            color: #333;
        }
        
        .card-info p {
            margin: 0;
            font-size: 13px;
            color: #666;
        }
        
        .filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .search-box {
            flex: 1;
            min-width: 200px;
        }
        
        .search-box input,
        .search-box select {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
        }
        
        .btn-impersonate {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .btn-impersonate:hover {
            background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
            text-decoration: none;
            color: white;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="container">
        <h1>📇 Business Cards</h1>
        <p class="subtitle">View and manage all business cards</p>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📇</div>
                <div class="stat-value"><?php echo number_format($total_cards); ?></div>
                <div class="stat-label">Total Business Cards</div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="section">
            <form method="GET" action="">
                <div class="filters">
                    <div class="search-box">
                        <select name="user">
                            <option value="">All Users</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?php echo $u['id']; ?>" <?php echo $user_filter === $u['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($u['email']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="search-box">
                        <input 
                            type="text" 
                            name="search" 
                            placeholder="Search by name or company..." 
                            value="<?php echo htmlspecialchars($search); ?>"
                        >
                    </div>
                    
                    <button type="submit" class="btn-small">Filter</button>
                    <a href="/admin/cards.php" class="btn-small">Clear</a>
                </div>
            </form>
        </div>
        
        <!-- Cards Table -->
        <div class="section">
            <h2>Business Cards (<?php echo count($cards); ?>)</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Card</th>
                            <th>Owner</th>
                            <th>Created</th>
                            <th>Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cards as $card): ?>
                        <tr>
                            <td>
                                <div class="card-preview">
                                    <div class="card-avatar">
                                        <?php echo strtoupper(substr($card['first_name'], 0, 1) . substr($card['last_name'], 0, 1)); ?>
                                    </div>
                                    <div class="card-info">
                                        <h3><?php echo htmlspecialchars($card['first_name'] . ' ' . $card['last_name']); ?></h3>
                                        <p><?php echo htmlspecialchars($card['company_name'] ?: $card['phone_number']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($card['user_email']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($card['created_at'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($card['updated_at'])); ?></td>
                            <td>
                                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                    <a href="/card.php?id=<?php echo $card['id']; ?>" 
                                       target="_blank" 
                                       class="btn-small btn-primary"
                                       title="View public card">
                                        👁️ View Public
                                    </a>
                                    <a href="/admin/impersonate.php?user_id=<?php echo $card['user_id']; ?>" 
                                       target="_blank" 
                                       class="btn-small btn-impersonate"
                                       title="Login as this user">
                                        👤 Login As
                                    </a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Deactivate this business card?');">
                                        <input type="hidden" name="card_id" value="<?php echo $card['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn-small btn-danger">Deactivate</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($cards)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: #999;">
                                No business cards found
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Account Security Modal -->
    <?php 
    $userId = AdminAuth::getUserId();
    include __DIR__ . '/includes/account-security-modal.php'; 
    ?>
</body>
</html>

