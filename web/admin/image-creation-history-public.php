<?php
/**
 * Image Creation History - Public Version (No Auth)
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../api/includes/ImageCreationLogger.php';

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
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🖼️ Image Creation History</h1>
        <p><strong>Note:</strong> This is a public version for testing. The actual admin page requires authentication.</p>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_images']; ?></div>
                <div class="stat-label">Total Images</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['recent_24h']; ?></div>
                <div class="stat-label">Last 24 Hours</div>
            </div>
        </div>
        
        <h2>Recent Image Creation Events</h2>
        
        <?php if (empty($history)): ?>
            <div class="empty-state">
                <p>No image creation events found.</p>
                <p>Images will appear here when they are created in the system.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Image</th>
                        <th>Type</th>
                        <th>Method</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $event): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($event['user_email'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($event['image_name'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($event['image_type'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($event['creation_method'] ?? 'Unknown'); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($event['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>

