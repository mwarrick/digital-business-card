<?php
/**
 * Remove insecure Gmail configuration file from web directory
 * This file should not exist in the public_html directory for security
 */

$insecureFile = __DIR__ . '/../config/gmail.php';

if (file_exists($insecureFile)) {
    echo "🔍 Found insecure Gmail config file: " . $insecureFile . "\n";
    
    // Backup the file first (in case it contains important data)
    $backupFile = __DIR__ . '/gmail-config-backup-' . date('Y-m-d-H-i-s') . '.php';
    if (copy($insecureFile, $backupFile)) {
        echo "📁 Backed up to: " . $backupFile . "\n";
    }
    
    // Remove the insecure file
    if (unlink($insecureFile)) {
        echo "✅ Removed insecure Gmail config file\n";
        echo "🔒 Gmail credentials are now only in secure location outside web root\n";
    } else {
        echo "❌ Failed to remove insecure Gmail config file\n";
    }
} else {
    echo "✅ No insecure Gmail config file found - system is secure\n";
}

// Verify the secure config exists
$secureFile = '/home/sharipbf/sharemycard-config/gmail.php';
if (file_exists($secureFile)) {
    echo "✅ Secure Gmail config exists: " . $secureFile . "\n";
} else {
    echo "⚠️  Warning: Secure Gmail config not found at: " . $secureFile . "\n";
}
?>
