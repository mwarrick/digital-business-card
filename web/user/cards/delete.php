<?php
/**
 * Delete Business Card
 * Handles deletion of business cards with proper authentication
 */

require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';

// Check authentication
UserAuth::requireAuth();

$cardId = $_POST['card_id'] ?? '';

if (empty($cardId)) {
    header('Location: /user/dashboard.php?error=invalid_card');
    exit;
}

try {
    $db = Database::getInstance();
    $userId = UserAuth::getUserId();
    
    // Verify the card belongs to the current user
    $card = $db->querySingle(
        "SELECT id, first_name, last_name FROM business_cards WHERE id = ? AND user_id = ?",
        [$cardId, $userId]
    );
    
    if (!$card) {
        header('Location: /user/dashboard.php?error=card_not_found');
        exit;
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    // Delete related data first (foreign key constraints) - handle missing tables gracefully
    try {
        $db->execute("DELETE FROM contact_info WHERE card_id = ?", [$cardId]);
    } catch (Exception $e) {
        error_log("Note: contact_info table doesn't exist or error deleting: " . $e->getMessage());
    }
    
    try {
        $db->execute("DELETE FROM addresses WHERE card_id = ?", [$cardId]);
    } catch (Exception $e) {
        error_log("Note: addresses table doesn't exist or error deleting: " . $e->getMessage());
    }
    
    try {
        $db->execute("DELETE FROM website_links WHERE card_id = ?", [$cardId]);
    } catch (Exception $e) {
        error_log("Note: website_links table doesn't exist or error deleting: " . $e->getMessage());
    }
    
    try {
        $db->execute("DELETE FROM analytics_daily WHERE card_id = ?", [$cardId]);
    } catch (Exception $e) {
        error_log("Note: analytics_daily table doesn't exist or error deleting: " . $e->getMessage());
    }
    
    try {
        $db->execute("DELETE FROM analytics_events WHERE card_id = ?", [$cardId]);
    } catch (Exception $e) {
        error_log("Note: analytics_events table doesn't exist or error deleting: " . $e->getMessage());
    }
    
    try {
        $db->execute("DELETE FROM analytics_sessions WHERE card_id = ?", [$cardId]);
    } catch (Exception $e) {
        error_log("Note: analytics_sessions table doesn't exist or error deleting: " . $e->getMessage());
    }
    
    // Delete the business card
    $db->execute("DELETE FROM business_cards WHERE id = ?", [$cardId]);
    
    // Commit transaction
    $db->commit();
    
    // Redirect to dashboard with success message
    header('Location: /user/dashboard.php?success=card_deleted&name=' . urlencode($card['first_name'] . ' ' . $card['last_name']));
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollback();
    }
    
    error_log("Error deleting card $cardId: " . $e->getMessage());
    header('Location: /user/dashboard.php?error=delete_failed');
    exit;
}
?>
