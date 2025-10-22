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
     * 
     * IMPORTANT: Demo system should NEVER generate/create images
     * - Always use existing demo images from /storage/media/
     * - Never create placeholder images, cover graphics, profile photos, or company logos
     * - Only reference existing image filenames in the database
     * - Demo cards are intentionally reset on each login, but images remain unchanged
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
        
        // Demo system now uses database-driven approach - no image generation
        error_log("DEMO DEBUG: Using database-driven demo system");
        
        // Delete ALL existing demo cards and related data (including user-created ones)
        try {
            // Check if website_links table exists and has correct column name
            $tableInfo = $db->query("SHOW TABLES LIKE 'website_links'");
            if (!empty($tableInfo)) {
                $columns = $db->query("SHOW COLUMNS FROM website_links");
                $hasCardId = false;
                $hasBusinessCardId = false;
                foreach ($columns as $col) {
                    if ($col['Field'] === 'card_id') $hasCardId = true;
                    if ($col['Field'] === 'business_card_id') $hasBusinessCardId = true;
                }
                
                if ($hasCardId) {
                    $db->execute("DELETE FROM website_links WHERE card_id IN (SELECT id FROM business_cards WHERE user_id = ?)", [self::DEMO_USER_ID]);
                    error_log("DEMO DEBUG: Deleted website_links using card_id");
                } elseif ($hasBusinessCardId) {
                    $db->execute("DELETE FROM website_links WHERE business_card_id IN (SELECT id FROM business_cards WHERE user_id = ?)", [self::DEMO_USER_ID]);
                    error_log("DEMO DEBUG: Deleted website_links using business_card_id");
                } else {
                    error_log("DEMO DEBUG: website_links table exists but no card_id or business_card_id column found");
                }
            } else {
                error_log("DEMO DEBUG: website_links table does not exist");
            }
        } catch (Exception $e) {
            error_log("DEMO DEBUG: Error deleting website_links: " . $e->getMessage());
        }
        
        try {
            // Check if contact_info table exists
            $tableInfo = $db->query("SHOW TABLES LIKE 'contact_info'");
            if (!empty($tableInfo)) {
                $db->execute("DELETE FROM contact_info WHERE card_id IN (SELECT id FROM business_cards WHERE user_id = ?)", [self::DEMO_USER_ID]);
                error_log("DEMO DEBUG: Deleted contact_info");
            } else {
                error_log("DEMO DEBUG: contact_info table does not exist - skipping");
            }
        } catch (Exception $e) {
            error_log("DEMO DEBUG: Error deleting contact_info: " . $e->getMessage());
        }
        
        try {
            // Check if addresses table exists and has correct column name
            $tableInfo = $db->query("SHOW TABLES LIKE 'addresses'");
            if (!empty($tableInfo)) {
                $columns = $db->query("SHOW COLUMNS FROM addresses");
                $hasCardId = false;
                $hasBusinessCardId = false;
                foreach ($columns as $col) {
                    if ($col['Field'] === 'card_id') $hasCardId = true;
                    if ($col['Field'] === 'business_card_id') $hasBusinessCardId = true;
                }
                
                if ($hasCardId) {
                    $db->execute("DELETE FROM addresses WHERE card_id IN (SELECT id FROM business_cards WHERE user_id = ?)", [self::DEMO_USER_ID]);
                    error_log("DEMO DEBUG: Deleted addresses using card_id");
                } elseif ($hasBusinessCardId) {
                    $db->execute("DELETE FROM addresses WHERE business_card_id IN (SELECT id FROM business_cards WHERE user_id = ?)", [self::DEMO_USER_ID]);
                    error_log("DEMO DEBUG: Deleted addresses using business_card_id");
                } else {
                    error_log("DEMO DEBUG: addresses table exists but no card_id or business_card_id column found");
                }
            } else {
                error_log("DEMO DEBUG: addresses table does not exist");
            }
        } catch (Exception $e) {
            error_log("DEMO DEBUG: Error deleting addresses: " . $e->getMessage());
        }
        
        try {
            $db->execute("DELETE FROM analytics_daily WHERE card_id IN (SELECT id FROM business_cards WHERE user_id = ?)", [self::DEMO_USER_ID]);
            error_log("DEMO DEBUG: Deleted analytics_daily");
        } catch (Exception $e) {
            error_log("DEMO DEBUG: Error deleting analytics_daily: " . $e->getMessage());
        }
        
        try {
            $db->execute("DELETE FROM analytics_events WHERE card_id IN (SELECT id FROM business_cards WHERE user_id = ?)", [self::DEMO_USER_ID]);
            error_log("DEMO DEBUG: Deleted analytics_events");
        } catch (Exception $e) {
            error_log("DEMO DEBUG: Error deleting analytics_events: " . $e->getMessage());
        }
        
        try {
            $db->execute("DELETE FROM analytics_sessions WHERE card_id IN (SELECT id FROM business_cards WHERE user_id = ?)", [self::DEMO_USER_ID]);
            error_log("DEMO DEBUG: Deleted analytics_sessions");
        } catch (Exception $e) {
            error_log("DEMO DEBUG: Error deleting analytics_sessions: " . $e->getMessage());
        }
        
        try {
            $db->execute("DELETE FROM business_cards WHERE user_id = ?", [self::DEMO_USER_ID]);
            error_log("DEMO DEBUG: Deleted business_cards");
        } catch (Exception $e) {
            error_log("DEMO DEBUG: Error deleting business_cards: " . $e->getMessage());
        }
        
        // Delete ALL demo user invitations and related data
        $db->execute("DELETE FROM invitations WHERE inviter_user_id = ?", [self::DEMO_USER_ID]);
        
        error_log("Deleted all demo cards (system + user-created), invitations, and related data");
        
        // Create 3 sample business cards
        // Get demo card data from database table (primary records only)
        try {
            $demoData = $db->query("SELECT card_id, first_name, last_name, phone_number, company_name, job_title, bio, theme, profile_photo_path, company_logo_path, cover_graphic_path, street, city, state, zip, country, website_name, website_url FROM demo_data WHERE website_type = 'primary' ORDER BY card_id");
            error_log("DEMO DEBUG: Found " . count($demoData) . " primary demo data records");
            
            if (empty($demoData)) {
                error_log("No demo data found in demo_data table. Please run migration 022_fix_demo_data_structure.sql");
                return;
            }
        } catch (Exception $e) {
            error_log("DEMO DEBUG: Error querying demo_data table: " . $e->getMessage());
            return;
        }
        
        // Create cards array with primary data
        $cards = [];
        foreach ($demoData as $row) {
            $cards[] = [
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
                'cover_graphic_path' => $row['cover_graphic_path'],
                'street' => $row['street'],
                'city' => $row['city'],
                'state' => $row['state'],
                'zip' => $row['zip'],
                'country' => $row['country'],
                'website_name' => $row['website_name'],
                'website_url' => $row['website_url']
            ];
        }
        
        // Insert the business cards
        foreach ($cards as $card) {
            error_log("DEMO DEBUG: Inserting card for {$card['first_name']} {$card['last_name']}");
            error_log("DEMO DEBUG: Profile photo: {$card['profile_photo_path']}");
            error_log("DEMO DEBUG: Company logo: {$card['company_logo_path']}");
            error_log("DEMO DEBUG: Cover graphic: {$card['cover_graphic_path']}");
            
            try {
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
                error_log("DEMO DEBUG: Successfully inserted card for {$card['first_name']} {$card['last_name']}");
            } catch (Exception $e) {
                error_log("DEMO DEBUG: Failed to insert card for {$card['first_name']} {$card['last_name']}: " . $e->getMessage());
            }
        }
        
        // Add contact info from demo_data table (if contact_info table exists)
        try {
            $contactData = $db->query("SELECT DISTINCT card_id, phone_number FROM demo_data WHERE phone_number IS NOT NULL AND phone_number != ''");
            error_log("DEMO DEBUG: Found " . count($contactData) . " contact records");
            
            foreach ($contactData as $contact) {
                $db->execute(
                    "INSERT INTO contact_info (card_id, type, subtype, value, created_at)
                     VALUES (?, 'phone', 'work', ?, NOW())",
                    [$contact['card_id'], $contact['phone_number']]
                );
                error_log("DEMO DEBUG: Added phone contact: " . $contact['phone_number']);
            }
            
            error_log("Demo contact info added successfully from database");
        } catch (Exception $e) {
            error_log("Demo contact info not added (table may not exist): " . $e->getMessage());
        }
        
        // Add primary website links from cards data
        try {
            foreach ($cards as $card) {
                if (!empty($card['website_url'])) {
                    $db->execute(
                        "INSERT INTO website_links (id, business_card_id, name, url, is_primary, created_at)
                         VALUES (?, ?, ?, ?, 1, NOW())",
                        [
                            'demo-website-' . substr($card['id'], -8) . '-uuid',
                            $card['id'],
                            $card['website_name'],
                            $card['website_url']
                        ]
                    );
                    error_log("DEMO DEBUG: Added primary website for " . $card['first_name'] . " " . $card['last_name']);
                }
            }
            
            error_log("Demo primary website links added successfully from cards data");
        } catch (Exception $e) {
            error_log("Demo website links not added (table may not exist): " . $e->getMessage());
        }
        
        // Add addresses from cards data
        try {
            foreach ($cards as $card) {
                if (!empty($card['street'])) {
                    $db->execute(
                        "INSERT INTO addresses (id, business_card_id, street, city, state, zip_code, country, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
                        [
                            'demo-address-' . substr($card['id'], -8) . '-uuid',
                            $card['id'],
                            $card['street'],
                            $card['city'],
                            $card['state'],
                            $card['zip'],
                            $card['country']
                        ]
                    );
                    error_log("DEMO DEBUG: Added address for " . $card['first_name'] . " " . $card['last_name']);
                }
            }
            
            error_log("Demo addresses added successfully from cards data");
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
    
}
