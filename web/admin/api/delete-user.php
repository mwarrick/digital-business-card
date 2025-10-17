<?php
/**
 * Delete User - Session Authenticated
 * POST /admin/api/delete-user.php
 * Deletes a user and all related data using session authentication
 */

require_once __DIR__ . '/../includes/AdminAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';

// Require authentication
AdminAuth::requireAuth();

$adminId = AdminAuth::getUserId();
$db = Database::getInstance();

// Validate required fields
$userId = $_POST['user_id'] ?? null;

if (!$userId) {
    error_log("Delete user request failed: User ID required. Admin ID: $adminId");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit;
}

try {
    // Debug logging
    error_log("Delete user request - User ID: $userId, Admin ID: $adminId");
    
    // Check if user exists and get user details
    $user = $db->querySingle(
        "SELECT id, email, role, is_admin FROM users WHERE id = ?",
        [$userId]
    );
    
    if (!$user) {
        error_log("User not found for ID: $userId. Admin ID: $adminId");
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Prevent deletion of demo user
    if ($user['role'] === 'demo') {
        error_log("Attempt to delete demo user blocked. Admin ID: $adminId, User ID: $userId");
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Demo user cannot be deleted']);
        exit;
    }
    
    // Prevent self-deletion (admin cannot delete themselves)
    if ($userId === $adminId) {
        error_log("Admin self-deletion blocked. Admin ID: $adminId, User ID: $userId");
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You cannot delete yourself']);
        exit;
    }
    
    // Get user's business cards for media file deletion
    $userCards = $db->query(
        "SELECT id, profile_photo_path, company_logo_path, cover_graphic_path, profile_photo, company_logo, cover_graphic FROM business_cards WHERE user_id = ?",
        [$userId]
    );
    
    $cardCount = count($userCards);
    error_log("User has $cardCount business cards to delete");
    
    // Start transaction for atomicity
    $db->beginTransaction();
    
    try {
        $mediaFilesDeleted = 0;
        
        // 1. Delete media files first (if any exist)
        if (!empty($userCards)) {
            foreach ($userCards as $card) {
                $mediaPaths = [
                    $card['profile_photo_path'],
                    $card['company_logo_path'], 
                    $card['cover_graphic_path'],
                    $card['profile_photo'],
                    $card['company_logo'],
                    $card['cover_graphic']
                ];
                
                foreach ($mediaPaths as $path) {
                    if (!empty($path) && file_exists($path)) {
                        if (unlink($path)) {
                            $mediaFilesDeleted++;
                            error_log("Deleted media file: $path");
                        } else {
                            error_log("Failed to delete media file: $path");
                        }
                    }
                }
            }
        }
        error_log("Deleted $mediaFilesDeleted media files");
        
        // 2. Delete verification codes (safe to delete even if none exist)
        try {
            $db->execute("DELETE FROM verification_codes WHERE email = ?", [$user['email']]);
            error_log("Deleted verification codes for email: " . $user['email']);
        } catch (Exception $e) {
            error_log("Note: No verification codes to delete or table doesn't exist: " . $e->getMessage());
        }
        
        // 3. Delete business cards (this will cascade to all related data due to FK constraints)
        if ($cardCount > 0) {
            try {
                $db->execute("DELETE FROM business_cards WHERE user_id = ?", [$userId]);
                error_log("Deleted $cardCount business cards and all related data via CASCADE");
            } catch (Exception $e) {
                error_log("Error deleting business cards: " . $e->getMessage());
                throw $e;
            }
        } else {
            error_log("No business cards to delete for user");
        }
        
        // 4. Finally, delete the user record
        try {
            $db->execute("DELETE FROM users WHERE id = ?", [$userId]);
            error_log("Deleted user record: " . $user['email']);
        } catch (Exception $e) {
            error_log("Error deleting user record: " . $e->getMessage());
            throw $e;
        }
        
        // Commit transaction
        $db->commit();
        
        error_log("User deletion completed successfully - User ID: $userId, Email: " . $user['email']);
        
        echo json_encode([
            'success' => true,
            'message' => 'User and all related data deleted successfully',
            'deleted_user' => [
                'id' => $userId,
                'email' => $user['email'],
                'cards_deleted' => $cardCount,
                'media_files_deleted' => $mediaFilesDeleted
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on any error
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("User deletion error for user ID $userId, admin ID $adminId: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to delete user: ' . $e->getMessage()]);
}
