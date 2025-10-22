<?php
/**
 * Demo User Helper
 * 
 * Provides utility functions for detecting and handling demo users
 */

class DemoUserHelper {
    const DEMO_EMAIL = 'demo@sharemycard.app';
    const DEMO_USER_ID = 'demo-user-uuid-fixed';
    
    /**
     * Check if an email belongs to a demo user
     */
    public static function isDemoUser($email) {
        return strtolower(trim($email)) === self::DEMO_EMAIL;
    }
    
    /**
     * Check if a user ID belongs to a demo user
     */
    public static function isDemoUserId($userId) {
        return $userId === self::DEMO_USER_ID;
    }
    
    /**
     * Check if emails should be suppressed for this user
     */
    public static function shouldSuppressEmail($email = null, $userId = null) {
        if ($email && self::isDemoUser($email)) {
            return true;
        }
        if ($userId && self::isDemoUserId($userId)) {
            return true;
        }
        return false;
    }
    
    /**
     * Get demo user data for login bypass
     */
    public static function getDemoUserData() {
        return [
            'id' => self::DEMO_USER_ID,
            'email' => self::DEMO_EMAIL,
            'is_active' => 1,
            'is_admin' => 0,
            'role' => 'demo'
        ];
    }
    
    /**
     * Ensure demo user has 3 sample business cards
     * Regenerates missing cards on each login
     */
    public static function ensureDemoCards() {
        require_once __DIR__ . '/Database.php';
        $db = Database::getInstance();
        
        // Check current card counts for logging
        $systemCardCount = $db->querySingle(
            "SELECT COUNT(*) as count FROM business_cards WHERE user_id = ? AND is_active = 1 AND id IN ('demo-card-1-uuid', 'demo-card-2-uuid', 'demo-card-3-uuid')",
            [self::DEMO_USER_ID]
        )['count'];
        
        $totalCardCount = $db->querySingle(
            "SELECT COUNT(*) as count FROM business_cards WHERE user_id = ? AND is_active = 1",
            [self::DEMO_USER_ID]
        )['count'];
        
        error_log("Demo user has $systemCardCount system cards and $totalCardCount total cards");
        
        // ALWAYS reset demo cards on every login - no changes persist between sessions
        error_log("Resetting demo cards (clean slate for new session)...");
        
        // Check if custom demo images exist (preserve user-provided images)
        $mediaDir = __DIR__ . '/../../storage/media';
        error_log("DEMO DEBUG: Media directory path: $mediaDir");
        error_log("DEMO DEBUG: Media directory exists: " . (is_dir($mediaDir) ? 'YES' : 'NO'));
        
        $customImagesExist = self::checkCustomDemoImages($mediaDir);
        error_log("DEMO DEBUG: Custom images exist check result: " . ($customImagesExist ? 'YES' : 'NO'));
        
        // Log each individual image status
        $requiredImages = [
            'demo-alex-profile.jpg', 'demo-techcorp-logo.jpg', 'demo-techcorp-cover.jpg',
            'demo-sarah-profile.jpg', 'demo-designstudio-logo.jpg', 'demo-designstudio-cover.jpg',
            'demo-michael-profile.jpg', 'demo-innovation-logo.jpg', 'demo-innovation-cover.jpg'
        ];
        
        foreach ($requiredImages as $image) {
            $fullPath = $mediaDir . '/' . $image;
            $exists = file_exists($fullPath);
            $size = $exists ? filesize($fullPath) : 0;
            error_log("DEMO DEBUG: $image - exists: " . ($exists ? 'YES' : 'NO') . ", size: $size bytes");
        }
        
        if ($customImagesExist) {
            error_log("DEMO DEBUG: Custom demo images detected - will preserve user-provided images but still reset demo cards");
        } else {
            error_log("DEMO DEBUG: No custom demo images found - will reset demo cards");
        }
        
        // Delete ALL existing demo cards and related data (including user-created ones)
        $db->execute("DELETE FROM website_links WHERE card_id IN (SELECT id FROM business_cards WHERE user_id = ?)", [self::DEMO_USER_ID]);
        $db->execute("DELETE FROM contact_info WHERE card_id IN (SELECT id FROM business_cards WHERE user_id = ?)", [self::DEMO_USER_ID]);
        $db->execute("DELETE FROM addresses WHERE card_id IN (SELECT id FROM business_cards WHERE user_id = ?)", [self::DEMO_USER_ID]);
        $db->execute("DELETE FROM analytics_daily WHERE card_id IN (SELECT id FROM business_cards WHERE user_id = ?)", [self::DEMO_USER_ID]);
        $db->execute("DELETE FROM analytics_events WHERE card_id IN (SELECT id FROM business_cards WHERE user_id = ?)", [self::DEMO_USER_ID]);
        $db->execute("DELETE FROM analytics_sessions WHERE card_id IN (SELECT id FROM business_cards WHERE user_id = ?)", [self::DEMO_USER_ID]);
        $db->execute("DELETE FROM business_cards WHERE user_id = ?", [self::DEMO_USER_ID]);
        
        // Delete ALL demo user invitations and related data
        $db->execute("DELETE FROM invitations WHERE inviter_user_id = ?", [self::DEMO_USER_ID]);
        
        error_log("Deleted all demo cards (system + user-created), invitations, and related data");
        
        // Create 3 sample business cards
        // Get demo card data from database table
        $demoData = $db->query("SELECT DISTINCT card_id, first_name, last_name, phone_number, company_name, job_title, bio, theme, profile_photo_path, company_logo_path, cover_graphic_path FROM demo_data ORDER BY card_id");
        
        if (empty($demoData)) {
            error_log("No demo data found in demo_data table. Please run migration 021_create_demo_data_table.sql");
            return;
        }
        
        // Group by card_id to get unique cards
        $cards = [];
        foreach ($demoData as $row) {
            $cards[$row['card_id']] = [
                'id' => $row['card_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'phone_number' => $row['phone_number'],
                'company_name' => $row['company_name'],
                'job_title' => $row['job_title'],
                'bio' => $row['bio'],
                'theme' => $row['theme'],
                'profile_photo_path' => $row['profile_photo_path'],
                'company_logo_path' => $row['company_logo_path'],
                'cover_graphic_path' => $row['cover_graphic_path']
            ];
        }
        
        $cards = array_values($cards); // Convert back to indexed array
        
        // Insert the business cards
        foreach ($cards as $card) {
            $db->execute(
                "INSERT INTO business_cards (
                    id, user_id, first_name, last_name, phone_number, company_name, job_title, bio,
                    profile_photo_path, company_logo_path, cover_graphic_path, theme,
                    profile_photo, company_logo, cover_graphic, is_active,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())",
                [
                    $card['id'], self::DEMO_USER_ID, $card['first_name'], $card['last_name'],
                    $card['phone_number'], $card['company_name'], $card['job_title'], $card['bio'],
                    $card['profile_photo_path'], $card['company_logo_path'], $card['cover_graphic_path'], $card['theme'],
                    $card['profile_photo_path'], $card['company_logo_path'], $card['cover_graphic_path']
                ]
            );
        }
        
        // Add additional emails for the Creative Designer (demo-card-2)
        $db->execute(
            "INSERT INTO contact_info (card_id, type, subtype, value, created_at)
             VALUES ('demo-card-2-uuid', 'email', 'work', 'sarah.martinez@designstudiopro.com', NOW())"
        );
        $db->execute(
            "INSERT INTO contact_info (card_id, type, subtype, value, created_at)
             VALUES ('demo-card-2-uuid', 'email', 'personal', 'sarah@creativemartinez.com', NOW())"
        );
        
        // Add additional phones for the Creative Designer (demo-card-2)
        $db->execute(
            "INSERT INTO contact_info (card_id, type, subtype, value, created_at)
             VALUES ('demo-card-2-uuid', 'phone', 'mobile', '+1 (555) 987-6544', NOW())"
        );
        $db->execute(
            "INSERT INTO contact_info (card_id, type, subtype, value, created_at)
             VALUES ('demo-card-2-uuid', 'phone', 'work', '+1 (555) 987-6545', NOW())"
        );
        
        // Add website links from demo_data table
        try {
            $websiteData = $db->query("SELECT card_id, website_name, website_url FROM demo_data WHERE website_url IS NOT NULL AND website_url != ''");
            
            foreach ($websiteData as $website) {
                $db->execute(
                    "INSERT INTO website_links (card_id, name, url, created_at)
                     VALUES (?, ?, ?, NOW())",
                    [$website['card_id'], $website['website_name'], $website['website_url']]
                );
            }
            
            error_log("Demo website links added successfully from database");
        } catch (Exception $e) {
            error_log("Demo website links not added (table may not exist): " . $e->getMessage());
        }
        
        // Add addresses from demo_data table
        try {
            $addressData = $db->query("SELECT DISTINCT card_id, street, city, state, zip, country FROM demo_data WHERE street IS NOT NULL AND street != ''");
            
            foreach ($addressData as $address) {
                $db->execute(
                    "INSERT INTO addresses (id, card_id, street, city, state, zip, country, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
                    [
                        'demo-address-' . substr($address['card_id'], -8) . '-uuid',
                        $address['card_id'],
                        $address['street'],
                        $address['city'],
                        $address['state'],
                        $address['zip'],
                        $address['country']
                    ]
                );
            }
            
            error_log("Demo addresses added successfully from database");
        } catch (Exception $e) {
            error_log("Demo addresses not added (table may not exist): " . $e->getMessage());
        }
        
        
        // Verify cards were created
        $finalCount = $db->querySingle(
            "SELECT COUNT(*) as count FROM business_cards WHERE user_id = ?",
            [self::DEMO_USER_ID]
        )['count'];
        
        error_log("Demo card reset complete. Final count: $finalCount fresh system cards (all previous changes wiped)");
    }
    
    /**
     * Check if custom demo images exist (user-provided images)
     */
    private static function checkCustomDemoImages($mediaDir) {
        $requiredImages = [
            'demo-alex-profile.jpg',
            'demo-techcorp-logo.jpg', 
            'demo-techcorp-cover.jpg',
            'demo-sarah-profile.jpg',
            'demo-designstudio-logo.jpg',
            'demo-designstudio-cover.jpg',
            'demo-michael-profile.jpg',
            'demo-innovation-logo.jpg',
            'demo-innovation-cover.jpg'
        ];
        
        $existingCount = 0;
        foreach ($requiredImages as $image) {
            if (file_exists($mediaDir . '/' . $image)) {
                $existingCount++;
            }
        }
        
        // If at least 3 images exist (one complete set), consider custom images present
        // No image generation - only check for existing files
        return $existingCount >= 3;
    }
}
