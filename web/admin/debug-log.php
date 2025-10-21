<?php
/**
 * Debug Log Viewer
 * View the debug log in your browser
 */

// Enable error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/includes/AdminAuth.php';
    require_once __DIR__ . '/../api/includes/Database.php';
    require_once __DIR__ . '/../api/includes/DebugLogger.php';
    
    // Require admin authentication
    AdminAuth::requireAuth();
    
    // Handle actions
    if (isset($_GET['action'])) {
        if ($_GET['action'] === 'clear') {
            DebugLogger::clear();
            header('Location: /admin/debug-log.php?cleared=1');
            exit;
        }
    }
    
    $lines = isset($_GET['lines']) ? intval($_GET['lines']) : 100;
    $log = DebugLogger::getLog($lines);
    
} catch (Exception $e) {
    die("Error loading debug log: " . $e->getMessage() . "<br><br>File: " . $e->getFile() . "<br>Line: " . $e->getLine());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Log - ShareMyCard Admin</title>
    <link rel="stylesheet" href="/admin/includes/admin-style.css">
    <style>
        .log-container {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Monaco', 'Menlo', 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .log-container .error {
            color: #f48771;
        }
        
        .log-container .success {
            color: #89d185;
        }
        
        .log-container .warning {
            color: #e5c07b;
        }
        
        .controls {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
        }
        
        .btn-small {
            padding: 8px 16px;
            font-size: 13px;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <?php if (file_exists(__DIR__ . '/includes/header.php')) { include __DIR__ . '/includes/header.php'; } ?>
    
    <div class="main-container" style="padding: 20px; max-width: 1200px; margin: 0 auto;">
        <h1>🔍 Debug Log</h1>
        
        <?php if (isset($_GET['cleared'])): ?>
            <div class="alert alert-success">
                ✅ Debug log cleared successfully!
            </div>
        <?php endif; ?>
        
        <div class="controls">
            <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                <label for="lines">Show last:</label>
                <select name="lines" id="lines" onchange="this.form.submit()">
                    <option value="50" <?php echo $lines == 50 ? 'selected' : ''; ?>>50 lines</option>
                    <option value="100" <?php echo $lines == 100 ? 'selected' : ''; ?>>100 lines</option>
                    <option value="200" <?php echo $lines == 200 ? 'selected' : ''; ?>>200 lines</option>
                    <option value="500" <?php echo $lines == 500 ? 'selected' : ''; ?>>500 lines</option>
                    <option value="1000" <?php echo $lines == 1000 ? 'selected' : ''; ?>>1000 lines</option>
                </select>
            </form>
            
            <button onclick="location.reload()" class="btn btn-secondary btn-small">
                🔄 Refresh
            </button>
            
            <button onclick="if(confirm('Clear the debug log?')) location.href='?action=clear'" class="btn btn-danger btn-small">
                🗑️ Clear Log
            </button>
            
            <a href="/admin/dashboard.php" class="btn btn-secondary btn-small">
                ← Back to Dashboard
            </a>
        </div>
        
        <div class="log-container">
<?php echo htmlspecialchars($log); ?>
        </div>
        
        <p style="margin-top: 20px; color: #666; font-size: 13px;">
            <strong>Tip:</strong> The log auto-refreshes when you reload this page. 
            Use Cmd/Ctrl + R to refresh and see new entries.
        </p>
    </div>
    
    <!-- Account Security Modal -->
    <?php 
    $userId = AdminAuth::getUserId();
    include __DIR__ . '/includes/account-security-modal.php'; 
    ?>
</body>
</html>

