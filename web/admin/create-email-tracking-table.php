<?php
/**
 * Create email_tracking table directly
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Create the email_tracking table
    $sql = "CREATE TABLE IF NOT EXISTS email_tracking (
        id INT PRIMARY KEY AUTO_INCREMENT,
        token VARCHAR(32) NOT NULL UNIQUE,
        email_type VARCHAR(50) NOT NULL,
        to_email VARCHAR(255) NOT NULL,
        from_email VARCHAR(255) NOT NULL,
        subject VARCHAR(500),
        card_id VARCHAR(36),
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(20) DEFAULT 'sent',
        opened_at TIMESTAMP NULL,
        clicked_at TIMESTAMP NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        
        INDEX idx_token (token),
        INDEX idx_email_type (email_type),
        INDEX idx_to_email (to_email),
        INDEX idx_from_email (from_email),
        INDEX idx_card_id (card_id),
        INDEX idx_sent_at (sent_at),
        INDEX idx_status (status)
    )";
    
    $db->exec($sql);
    
    echo "✅ Email tracking table created successfully!\n";
    
    // Test the table
    $stmt = $db->query("SELECT COUNT(*) as count FROM email_tracking");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "📊 Table has {$result['count']} records\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
