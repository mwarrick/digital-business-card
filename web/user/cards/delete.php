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
    
    // Verify the card belongs to the current user and is not already deleted
    $card = $db->querySingle(
        "SELECT id, first_name, last_name, is_deleted FROM business_cards WHERE id = ? AND user_id = ? AND (is_deleted = 0 OR is_deleted IS NULL)",
        [$cardId, $userId]
    );
    
    if (!$card) {
        error_log("Delete card - Card not found, already deleted, or doesn't belong to user. Card ID: $cardId, User ID: $userId");
        header('Location: /user/dashboard.php?error=card_not_found');
        exit;
    }
    
    // Double-check if card is already deleted
    if (isset($card['is_deleted']) && $card['is_deleted'] == 1) {
        error_log("Delete card - Card is already deleted. Card ID: $cardId");
        header('Location: /user/dashboard.php?error=card_already_deleted');
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
    
    // Soft delete the business card
    $db->execute("UPDATE business_cards SET is_deleted = 1, updated_at = NOW() WHERE id = ?", [$cardId]);
    
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
