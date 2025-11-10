<?php
/**
 * Delete Account API Endpoint
 * POST /user/api/delete-account.php
 * Deletes user account and all related data
 */

// Set JSON content type header first
header('Content-Type: application/json');

// Start output buffering to catch any errors
ob_start();

// Log request start
error_log("DELETE ACCOUNT API: Request started - Method: " . $_SERVER['REQUEST_METHOD'] . ", IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

// Only allow POST requests - check early before loading files
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $output = ob_get_contents();
    ob_end_clean();
    http_response_code(405);
    $response = json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    error_log("DELETE ACCOUNT API: Method not allowed - " . $_SERVER['REQUEST_METHOD']);
    echo $response;
    exit;
}

try {
    require_once __DIR__ . '/../includes/UserAuth.php';
    require_once __DIR__ . '/../../api/includes/Database.php';
    
    error_log("DELETE ACCOUNT API: Files required successfully");
    
    // Check authentication (don't use requireAuth as it redirects)
    if (!UserAuth::isLoggedIn()) {
        $output = ob_get_contents();
        ob_end_clean();
        error_log("DELETE ACCOUNT API: Authentication failed. Output buffer had: " . substr($output, 0, 200));
        http_response_code(401);
        $response = json_encode(['success' => false, 'message' => 'Authentication required. Please log in.']);
        error_log("DELETE ACCOUNT API: Sending response: " . $response);
        echo $response;
        exit;
    }
    
    error_log("DELETE ACCOUNT API: User authenticated - ID: " . UserAuth::getUserId());
} catch (Exception $e) {
    $output = ob_get_contents();
    ob_end_clean();
    error_log("DELETE ACCOUNT API: Exception during auth - " . $e->getMessage());
    error_log("DELETE ACCOUNT API: Output buffer had: " . substr($output, 0, 200));
    http_response_code(500);
    $response = json_encode(['success' => false, 'message' => 'Authentication error: ' . $e->getMessage()]);
    error_log("DELETE ACCOUNT API: Sending error response: " . $response);
    echo $response;
    exit;
} catch (Error $e) {
    $output = ob_get_contents();
    ob_end_clean();
    error_log("DELETE ACCOUNT API: Fatal error during auth - " . $e->getMessage());
    error_log("DELETE ACCOUNT API: Output buffer had: " . substr($output, 0, 200));
    http_response_code(500);
    $response = json_encode(['success' => false, 'message' => 'Server error during authentication']);
    error_log("DELETE ACCOUNT API: Sending fatal error response: " . $response);
    echo $response;
    exit;
}

// Clear any output that might have been generated
$output = ob_get_contents();
if (!empty($output)) {
    error_log("DELETE ACCOUNT API: Warning - Output buffer had content before processing: " . substr($output, 0, 200));
}
ob_end_clean();

$userId = UserAuth::getUserId();
$db = Database::getInstance();

// Get request data
$rawInput = file_get_contents('php://input');
error_log("DELETE ACCOUNT API: Raw input received: " . substr($rawInput, 0, 50) . " (length: " . strlen($rawInput) . ")");

$input = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("DELETE ACCOUNT API: JSON decode error: " . json_last_error_msg());
    http_response_code(400);
    $response = json_encode(['success' => false, 'message' => 'Invalid JSON in request']);
    error_log("DELETE ACCOUNT API: Sending response: " . $response);
    echo $response;
    exit;
}

$password = $input['password'] ?? '';

// Password is required for account deletion
if (empty($password)) {
    error_log("DELETE ACCOUNT API: Password validation failed - empty password");
    http_response_code(400);
    $response = json_encode(['success' => false, 'message' => 'Password is required to delete your account']);
    error_log("DELETE ACCOUNT API: Sending response: " . $response);
    echo $response;
    exit;
}

error_log("DELETE ACCOUNT API: Password provided (length: " . strlen($password) . ")");

try {
    // Get user data
    $user = $db->querySingle(
        "SELECT id, email, password_hash, role FROM users WHERE id = ?",
        [$userId]
    );
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Store email address BEFORE deletion (we'll need it for confirmation email)
    $userEmail = $user['email'];
    
    // Prevent deletion of demo user
    if ($user['role'] === 'demo') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Demo user cannot be deleted']);
        exit;
    }
    
    // Verify password (required)
    if ($user['password_hash'] === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No password set for this account. Please set a password first.']);
        exit;
    }
    
    if (!password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid password. Please try again.']);
        exit;
    }
    
    error_log("DELETE ACCOUNT API: Password verified successfully");
    
    // Get all business cards and QR codes for related data deletion
    $userCards = $db->query(
        "SELECT id, profile_photo_path, company_logo_path, cover_graphic_path, profile_photo, company_logo, cover_graphic FROM business_cards WHERE user_id = ?",
        [$userId]
    );
    
    $userQRCodes = $db->query(
        "SELECT id FROM custom_qr_codes WHERE user_id = ?",
        [$userId]
    );
    
    $cardIds = array_column($userCards, 'id');
    $qrIds = array_column($userQRCodes, 'id');
    
    error_log("Starting account deletion for user ID: $userId, Email: " . $user['email']);
    error_log("Found " . count($cardIds) . " business cards and " . count($qrIds) . " QR codes");
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        $mediaFilesDeleted = 0;
        $storageDir = __DIR__ . '/../../../storage/media/';
        
        // Phase 1: Delete media files
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
                
                foreach ($mediaPaths as $filename) {
                    if (!empty($filename)) {
                        // Handle both full paths and filenames
                        if (strpos($filename, '/') === 0 || strpos($filename, '\\') !== false) {
                            // Full path
                            $filepath = $filename;
                        } else {
                            // Just filename - prepend storage directory
                            $filepath = $storageDir . $filename;
                        }
                        
                        if (file_exists($filepath)) {
                            if (unlink($filepath)) {
                                $mediaFilesDeleted++;
                                error_log("Deleted media file: $filepath");
                            } else {
                                error_log("Failed to delete media file: $filepath");
                            }
                        }
                    }
                }
            }
        }
        error_log("Deleted $mediaFilesDeleted media files");
        
        // Phase 2: Delete child tables by user_id
        // 1. verification_codes
        try {
            $db->execute("DELETE FROM verification_codes WHERE user_id = ?", [$userId]);
            error_log("Deleted verification_codes for user_id: $userId");
        } catch (Exception $e) {
            error_log("Note: verification_codes table doesn't exist or error: " . $e->getMessage());
        }
        
        // 2. auth_tokens
        try {
            $db->execute("DELETE FROM auth_tokens WHERE user_id = ?", [$userId]);
            error_log("Deleted auth_tokens for user_id: $userId");
        } catch (Exception $e) {
            error_log("Note: auth_tokens table doesn't exist or error: " . $e->getMessage());
        }
        
        // 3. image_creation_log
        try {
            $db->execute("DELETE FROM image_creation_log WHERE user_id = ?", [$userId]);
            error_log("Deleted image_creation_log for user_id: $userId");
        } catch (Exception $e) {
            error_log("Note: image_creation_log table doesn't exist or error: " . $e->getMessage());
        }
        
        // 4. demo_data
        try {
            $db->execute("DELETE FROM demo_data WHERE user_id = ?", [$userId]);
            error_log("Deleted demo_data for user_id: $userId");
        } catch (Exception $e) {
            error_log("Note: demo_data table doesn't exist or error: " . $e->getMessage());
        }
        
        // 5. contacts
        try {
            $db->execute("DELETE FROM contacts WHERE user_id = ?", [$userId]);
            error_log("Deleted contacts for user_id: $userId");
        } catch (Exception $e) {
            error_log("Note: contacts table doesn't exist or error: " . $e->getMessage());
        }
        
        // 6. invitations
        try {
            $db->execute("DELETE FROM invitations WHERE user_id = ?", [$userId]);
            error_log("Deleted invitations for user_id: $userId");
        } catch (Exception $e) {
            error_log("Note: invitations table doesn't exist or error: " . $e->getMessage());
        }
        
        // 7. leads
        try {
            $db->execute("DELETE FROM leads WHERE user_id = ?", [$userId]);
            error_log("Deleted leads for user_id: $userId");
        } catch (Exception $e) {
            error_log("Note: leads table doesn't exist or error: " . $e->getMessage());
        }
        
        // Phase 2 continued: Delete by qr_id
        // 8. qr_leads
        if (!empty($qrIds)) {
            try {
                $placeholders = implode(',', array_fill(0, count($qrIds), '?'));
                $db->execute("DELETE FROM qr_leads WHERE qr_id IN ($placeholders)", $qrIds);
                error_log("Deleted qr_leads for " . count($qrIds) . " QR codes");
            } catch (Exception $e) {
                error_log("Note: qr_leads table doesn't exist or error: " . $e->getMessage());
            }
        }
        
        // 9. custom_qr_events
        if (!empty($qrIds)) {
            try {
                $placeholders = implode(',', array_fill(0, count($qrIds), '?'));
                $db->execute("DELETE FROM custom_qr_events WHERE qr_id IN ($placeholders)", $qrIds);
                error_log("Deleted custom_qr_events for " . count($qrIds) . " QR codes");
            } catch (Exception $e) {
                error_log("Note: custom_qr_events table doesn't exist or error: " . $e->getMessage());
            }
        }
        
        // Phase 2 continued: Delete by card_id
        // 10. analytics_events
        if (!empty($cardIds)) {
            try {
                $placeholders = implode(',', array_fill(0, count($cardIds), '?'));
                $db->execute("DELETE FROM analytics_events WHERE card_id IN ($placeholders)", $cardIds);
                error_log("Deleted analytics_events for " . count($cardIds) . " business cards");
            } catch (Exception $e) {
                error_log("Note: analytics_events table doesn't exist or error: " . $e->getMessage());
            }
        }
        
        // 11. analytics_sessions
        if (!empty($cardIds)) {
            try {
                $placeholders = implode(',', array_fill(0, count($cardIds), '?'));
                $db->execute("DELETE FROM analytics_sessions WHERE card_id IN ($placeholders)", $cardIds);
                error_log("Deleted analytics_sessions for " . count($cardIds) . " business cards");
            } catch (Exception $e) {
                error_log("Note: analytics_sessions table doesn't exist or error: " . $e->getMessage());
            }
        }
        
        // 12. analytics_daily
        if (!empty($cardIds)) {
            try {
                $placeholders = implode(',', array_fill(0, count($cardIds), '?'));
                $db->execute("DELETE FROM analytics_daily WHERE card_id IN ($placeholders)", $cardIds);
                error_log("Deleted analytics_daily for " . count($cardIds) . " business cards");
            } catch (Exception $e) {
                error_log("Note: analytics_daily table doesn't exist or error: " . $e->getMessage());
            }
        }
        
        // Phase 3: Delete business card related tables by card_id
        // 13. card_emails
        if (!empty($cardIds)) {
            try {
                $placeholders = implode(',', array_fill(0, count($cardIds), '?'));
                $db->execute("DELETE FROM card_emails WHERE card_id IN ($placeholders)", $cardIds);
                error_log("Deleted card_emails for " . count($cardIds) . " business cards");
            } catch (Exception $e) {
                error_log("Note: card_emails table doesn't exist or error: " . $e->getMessage());
            }
        }
        
        // 14. card_phones
        if (!empty($cardIds)) {
            try {
                $placeholders = implode(',', array_fill(0, count($cardIds), '?'));
                $db->execute("DELETE FROM card_phones WHERE card_id IN ($placeholders)", $cardIds);
                error_log("Deleted card_phones for " . count($cardIds) . " business cards");
            } catch (Exception $e) {
                error_log("Note: card_phones table doesn't exist or error: " . $e->getMessage());
            }
        }
        
        // 15. website_links
        if (!empty($cardIds)) {
            try {
                $placeholders = implode(',', array_fill(0, count($cardIds), '?'));
                $db->execute("DELETE FROM website_links WHERE card_id IN ($placeholders)", $cardIds);
                error_log("Deleted website_links for " . count($cardIds) . " business cards");
            } catch (Exception $e) {
                error_log("Note: website_links table doesn't exist or error: " . $e->getMessage());
            }
        }
        
        // 16. addresses
        if (!empty($cardIds)) {
            try {
                $placeholders = implode(',', array_fill(0, count($cardIds), '?'));
                $db->execute("DELETE FROM addresses WHERE card_id IN ($placeholders)", $cardIds);
                error_log("Deleted addresses for " . count($cardIds) . " business cards");
            } catch (Exception $e) {
                error_log("Note: addresses table doesn't exist or error: " . $e->getMessage());
            }
        }
        
        // Phase 4: Delete parent tables
        // 17. business_cards
        if (!empty($cardIds)) {
            try {
                $db->execute("DELETE FROM business_cards WHERE user_id = ?", [$userId]);
                error_log("Deleted " . count($cardIds) . " business cards for user_id: $userId");
            } catch (Exception $e) {
                error_log("Error deleting business_cards: " . $e->getMessage());
                throw $e;
            }
        }
        
        // 18. custom_qr_codes
        if (!empty($qrIds)) {
            try {
                $db->execute("DELETE FROM custom_qr_codes WHERE user_id = ?", [$userId]);
                error_log("Deleted " . count($qrIds) . " custom_qr_codes for user_id: $userId");
            } catch (Exception $e) {
                error_log("Error deleting custom_qr_codes: " . $e->getMessage());
                throw $e;
            }
        }
        
        // Phase 5: Delete user record
        // 19. users
        try {
            $db->execute("DELETE FROM users WHERE id = ?", [$userId]);
            error_log("Deleted user record: " . $userEmail);
        } catch (Exception $e) {
            error_log("Error deleting user record: " . $e->getMessage());
            throw $e;
        }
        
        // Commit transaction
        $db->commit();
        
        error_log("Account deletion completed successfully - User ID: $userId, Email: " . $userEmail);
        
        // Send confirmation email AFTER deletion is complete
        // Use the stored email address since user record is now deleted
        try {
            require_once __DIR__ . '/../../api/includes/EmailTemplates.php';
            require_once __DIR__ . '/../../api/includes/GmailClient.php';
            
            $emailTemplate = EmailTemplates::accountDeleted($userEmail);
            
            GmailClient::sendEmail(
                $userEmail,
                $emailTemplate['subject'],
                $emailTemplate['html'],
                $emailTemplate['text'],
                null, // no CC
                'noreply@sharemycard.app', // from email
                'ShareMyCard', // from name
                null // to name
            );
            
            error_log("DELETE ACCOUNT API: Confirmation email sent to " . $userEmail);
        } catch (Exception $e) {
            // Log email error but don't fail - account is already deleted
            error_log("DELETE ACCOUNT API: Failed to send confirmation email to " . $userEmail . ": " . $e->getMessage());
        }
        
        $response = json_encode([
            'success' => true,
            'message' => 'Account and all related data deleted successfully',
            'deleted_user' => [
                'id' => $userId,
                'email' => $userEmail,
                'cards_deleted' => count($cardIds),
                'qr_codes_deleted' => count($qrIds),
                'media_files_deleted' => $mediaFilesDeleted
            ]
        ]);
        
        error_log("DELETE ACCOUNT API: Sending success response (length: " . strlen($response) . ")");
        error_log("DELETE ACCOUNT API: Response preview: " . substr($response, 0, 200));
        
        // Ensure no output before this
        if (ob_get_level() > 0) {
            ob_clean();
        }
        
        echo $response;
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on any error
        $db->rollBack();
        error_log("Account deletion error - User ID: $userId: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        throw $e;
    }
    
} catch (Exception $e) {
    // Make sure we're outputting JSON
    $output = ob_get_contents();
    if (ob_get_level() > 0) {
        ob_clean();
    }
    
    error_log("DELETE ACCOUNT API: Exception caught - " . $e->getMessage());
    error_log("DELETE ACCOUNT API: Stack trace: " . $e->getTraceAsString());
    if (!empty($output)) {
        error_log("DELETE ACCOUNT API: Output buffer had: " . substr($output, 0, 500));
    }
    
    http_response_code(500);
    header('Content-Type: application/json');
    $response = json_encode(['success' => false, 'message' => 'Failed to delete account: ' . $e->getMessage()]);
    error_log("DELETE ACCOUNT API: Sending error response: " . $response);
    echo $response;
    exit;
} catch (Error $e) {
    // Catch PHP fatal errors
    $output = ob_get_contents();
    if (ob_get_level() > 0) {
        ob_clean();
    }
    
    error_log("DELETE ACCOUNT API: Fatal error - " . $e->getMessage());
    if (!empty($output)) {
        error_log("DELETE ACCOUNT API: Output buffer had: " . substr($output, 0, 500));
    }
    
    http_response_code(500);
    header('Content-Type: application/json');
    $response = json_encode(['success' => false, 'message' => 'A server error occurred. Please try again.']);
    error_log("DELETE ACCOUNT API: Sending fatal error response: " . $response);
    echo $response;
    exit;
}

