<?php
/**
 * Image Creation History - Admin Dashboard
 */

require_once __DIR__ . '/includes/AdminAuth.php';
require_once __DIR__ . '/../api/includes/ImageCreationLogger.php';

// Require authentication
AdminAuth::requireAuth();

// Check session expiry
if (AdminAuth::isSessionExpired()) {
    AdminAuth::logout();
    header('Location: /admin/login.php');
    exit;
}

try {
    $logger = new ImageCreationLogger();
    $history = $logger->getImageCreationHistory(100);
    $stats = $logger->getImageCreationStats();
} catch (Exception $e) {
    error_log("ImageCreationLogger error: " . $e->getMessage());
    $history = [];
    $stats = [
        'total_images' => 0,
        'recent_24h' => 0,
        'by_type' => [],
        'by_method' => []
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Creation History - ShareMyCard Admin</title>
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
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
        }
        
        .history-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: top;
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        .image-type-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .type-profile_photo { background: #e3f2fd; color: #1976d2; }
        .type-company_logo { background: #f3e5f5; color: #7b1fa2; }
        .type-cover_graphic { background: #e8f5e8; color: #388e3c; }
        .type-virtual_background { background: #fff3e0; color: #f57c00; }
        .type-name_tag { background: #fce4ec; color: #c2185b; }
        .type-other { background: #f5f5f5; color: #616161; }
        
        .method-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: 500;
        }
        
        .method-upload { background: #d4edda; color: #155724; }
        .method-generated { background: #d1ecf1; color: #0c5460; }
        .method-placeholder { background: #fff3cd; color: #856404; }
        .method-demo_copy { background: #f8d7da; color: #721c24; }
        
        .view-image-btn {
            display: inline-block;
            padding: 6px 12px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.8em;
        }
        
        .view-image-btn:hover {
            background: #0056b3;
        }
        
        .url-info {
            font-family: monospace;
            font-size: 0.8em;
            color: #666;
            word-break: break-all;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="main-container">
        <div class="page-header">
            <h1>🖼️ Image Creation History</h1>
            <p style="color: #666; margin-bottom: 0;">Track when and where images are created in the system</p>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_images'] ?? 0; ?></div>
                <div class="stat-label">Total Images Created</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['recent_24h'] ?? 0; ?></div>
                <div class="stat-label">Last 24 Hours</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['by_type']['profile_photo'] ?? 0; ?></div>
                <div class="stat-label">Profile Photos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['by_type']['company_logo'] ?? 0; ?></div>
                <div class="stat-label">Company Logos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['by_type']['cover_graphic'] ?? 0; ?></div>
                <div class="stat-label">Cover Graphics</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['by_method']['generated'] ?? 0; ?></div>
                <div class="stat-label">Auto-Generated</div>
            </div>
        </div>
        
        <!-- History Table -->
        <div class="history-table">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Image</th>
                        <th>Type</th>
                        <th>Method</th>
                        <th>Page</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="7" class="empty-state">
                                No image creation events found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($history as $event): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($event['user_email']); ?></strong>
                                    <?php if (!empty($event['first_name']) || !empty($event['last_name'])): ?>
                                        <br><small><?php echo htmlspecialchars(trim($event['first_name'] . ' ' . $event['last_name'])); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($event['image_name']); ?></strong>
                                    <?php if ($event['file_size']): ?>
                                        <br><small><?php echo number_format($event['file_size'] / 1024, 1); ?> KB</small>
                                    <?php endif; ?>
                                    <?php if ($event['image_dimensions']): ?>
                                        <br><small><?php echo htmlspecialchars($event['image_dimensions']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="image-type-badge type-<?php echo $event['image_type']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $event['image_type'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="method-badge method-<?php echo $event['creation_method']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $event['creation_method'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="url-info">
                                        <strong><?php echo htmlspecialchars(parse_url($event['page_url'], PHP_URL_PATH)); ?></strong>
                                        <?php if ($event['url_parameters']): ?>
                                            <br><small><?php echo htmlspecialchars($event['url_parameters']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo date('M j, Y g:i A', strtotime($event['created_at'])); ?>
                                    <br><small><?php 
                                        $timeAgo = time() - strtotime($event['created_at']);
                                        if ($timeAgo < 60) echo 'Just now';
                                        elseif ($timeAgo < 3600) echo floor($timeAgo/60) . ' minutes ago';
                                        elseif ($timeAgo < 86400) echo floor($timeAgo/3600) . ' hours ago';
                                        else echo floor($timeAgo/86400) . ' days ago';
                                    ?></small>
                                </td>
                                <td>
                                    <?php if (file_exists($event['image_path'])): ?>
                                        <a href="/storage/media/<?php echo basename($event['image_path']); ?>" 
                                           target="_blank" 
                                           class="view-image-btn">
                                            View Image
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #dc3545; font-size: 0.8em;">File Missing</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
