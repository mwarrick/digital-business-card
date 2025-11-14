<?php
/**
 * Demo User Helper
 * 
 * Provides utility functions for detecting and handling demo users
 */

require_once __DIR__ . '/log-image-creation.php';

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
        
        // ALWAYS reset demo cards on every login - clean slate approach
        error_log("Resetting demo cards (clean slate for new session)...");
        
        // Demo system now uses file copying approach - no image generation
        error_log("DEMO DEBUG: Using file copying demo system");
        
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
        
        // Delete ALL demo user leads FIRST (before deleting business cards)
        try {
            $db->execute("DELETE FROM leads WHERE id_business_card IN (SELECT id FROM business_cards WHERE user_id = ?)", [self::DEMO_USER_ID]);
            error_log("DEMO DEBUG: Deleted leads");
        } catch (Exception $e) {
            error_log("DEMO DEBUG: Error deleting leads: " . $e->getMessage());
        }
        
        // Delete ALL demo user contacts
        try {
            $db->execute("DELETE FROM contacts WHERE id_user = ?", [self::DEMO_USER_ID]);
            error_log("DEMO DEBUG: Deleted contacts");
        } catch (Exception $e) {
            error_log("DEMO DEBUG: Error deleting contacts: " . $e->getMessage());
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
        
        // Handle demo images using file copying approach
        try {
            error_log("DEMO DEBUG: About to call handleDemoImages()");
            self::handleDemoImages();
            error_log("DEMO DEBUG: handleDemoImages() completed");
        } catch (Exception $e) {
            error_log("DEMO DEBUG: handleDemoImages() failed: " . $e->getMessage());
        }
        
        // Create 3 sample business cards
        // Get demo card data from database table (primary records only)
        try {
            $demoData = $db->query("SELECT card_id, first_name, last_name, email_primary, phone_number, company_name, job_title, bio, theme, profile_photo_path, company_logo_path, cover_graphic_path, street, city, state, zip, country, website_name, website_url FROM demo_data WHERE website_type = 'primary' ORDER BY card_id");
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
                'email_primary' => $row['email_primary'],
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
            
            // PREVENT IMAGE GENERATION: Keep image paths but don't generate new images
            error_log("DEMO DEBUG: Using existing image paths without generation");
            
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
        
        // Add email contacts for each business card
        try {
            error_log("DEMO DEBUG: Adding email contacts for demo cards");
            foreach ($cards as $card) {
                if (!empty($card['email_primary'])) {
                    $db->execute(
                        "INSERT INTO email_contacts (id, business_card_id, email, type, label, is_primary, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())",
                        [
                            'demo-email-' . $card['id'],
                            $card['id'],
                            $card['email_primary'],
                            'work',
                            'Work Email',
                            1
                        ]
                    );
                    error_log("DEMO DEBUG: Added email contact for {$card['first_name']} {$card['last_name']}: {$card['email_primary']}");
                }
            }
        } catch (Exception $e) {
            error_log("DEMO DEBUG: Error adding email contacts: " . $e->getMessage());
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
        
        // Populate demo leads and contacts from demo tables
        self::populateDemoLeadsAndContacts();
    }
    
    /**
     * Handle demo images using file copying approach
     * 1. Delete existing demo images from /storage/media
     * 2. Copy images from /storage/media/demo to /storage/media
     * 3. Set permissions to 444 (read-only) to prevent overwriting
     */
    private static function handleDemoImages() {
        // Note: Storage path should be configured in sharemycard-config/.env
        $baseStoragePath = getenv('UPLOAD_PATH') ?: dirname(dirname(dirname(__DIR__))) . '/storage/media/';
        $mediaDir = rtrim($baseStoragePath, '/') . '/';
        $demoDir = rtrim($baseStoragePath, '/') . '/demo/';
        
        error_log("DEMO DEBUG: Starting image file copying process");
        
        // 1. Delete existing demo images from /storage/media
        $demoImageFiles = [
            'demo-alex-profile.jpg',
            'demo-alex-logo.jpg', 
            'demo-alex-cover.jpg',
            'demo-michael-profile.jpg',
            'demo-michael-logo.jpg',
            'demo-michael-cover.jpg',
            'demo-sarah-profile.jpg',
            'demo-sarah-logo.jpg',
            'demo-sarah-cover.jpg',
            'demo-techcorp-logo.jpg',
            'demo-techcorp-cover.jpg',
            'demo-designstudio-logo.jpg',
            'demo-designstudio-cover.jpg',
            'demo-innovation-logo.jpg',
            'demo-innovation-cover.jpg'
        ];
        
        foreach ($demoImageFiles as $filename) {
            $filePath = $mediaDir . $filename;
            if (file_exists($filePath)) {
                unlink($filePath);
                error_log("DEMO DEBUG: Deleted existing image: $filename");
            }
        }
        
        // 2. Copy images from /storage/media/demo to /storage/media
        if (!is_dir($demoDir)) {
            error_log("DEMO DEBUG: Demo directory does not exist: $demoDir");
            return;
        }
        
        $demoFiles = glob($demoDir . '*');
        foreach ($demoFiles as $sourceFile) {
            $filename = basename($sourceFile);
            $destFile = $mediaDir . $filename;
            
            if (copy($sourceFile, $destFile)) {
                error_log("DEMO DEBUG: Copied image: $filename");
                
                // 3. Set permissions to 644 (read-write for owner, read-only for others)
                chmod($destFile, 0644);
                error_log("DEMO DEBUG: Set permissions to 644 for: $filename");
                
                // Log image creation
                $imageType = 'other';
                if (strpos($filename, 'profile') !== false) {
                    $imageType = 'profile_photo';
                } elseif (strpos($filename, 'logo') !== false) {
                    $imageType = 'company_logo';
                } elseif (strpos($filename, 'cover') !== false) {
                    $imageType = 'cover_graphic';
                }
                
                $fileSize = file_exists($destFile) ? filesize($destFile) : null;
                $dimensions = null;
                if (function_exists('getimagesize')) {
                    $imageInfo = getimagesize($destFile);
                    if ($imageInfo) {
                        $dimensions = $imageInfo[0] . 'x' . $imageInfo[1];
                    }
                }
                
                logImageCreation(
                    $filename,
                    $destFile,
                    $imageType,
                    'demo_copy',
                    $fileSize,
                    $dimensions
                );
            } else {
                error_log("DEMO DEBUG: Failed to copy image: $filename");
            }
        }
        
        error_log("DEMO DEBUG: Image file copying process completed");
    }
    
    /**
     * Populate demo leads and contacts from demo tables
     * This method copies data from leads_demo and contacts_demo to the real tables
     */
    private static function populateDemoLeadsAndContacts() {
        $db = Database::getInstance();
        
        try {
            error_log("DEMO DEBUG: Starting demo leads and contacts population");
            
            // Get the demo user ID from demo_data table
            $demoUserResult = $db->query("SELECT DISTINCT id FROM demo_data WHERE website_type = 'primary' LIMIT 1");
            if (empty($demoUserResult)) {
                error_log("DEMO DEBUG: No demo data found in demo_data table");
                return;
            }
            $demoUserId = $demoUserResult[0]['id'];
            error_log("DEMO DEBUG: Using demo user ID: " . $demoUserId);
            
            // Get all demo leads
            $demoLeads = $db->query("SELECT * FROM leads_demo");
            error_log("DEMO DEBUG: Found " . count($demoLeads) . " demo leads to populate");
            
            // Get all demo contacts
            $demoContacts = $db->query("SELECT * FROM contacts_demo");
            error_log("DEMO DEBUG: Found " . count($demoContacts) . " demo contacts to populate");
            
            // Get the actual business card IDs that were created
            $businessCards = $db->query("SELECT id, first_name, last_name FROM business_cards WHERE user_id = ?", [self::DEMO_USER_ID]);
            $cardMapping = [];
            foreach ($businessCards as $card) {
                $cardMapping[$card['first_name'] . ' ' . $card['last_name']] = $card['id'];
            }
            error_log("DEMO DEBUG: Business card mapping: " . print_r($cardMapping, true));
            
            // Populate leads
            foreach ($demoLeads as $demoLead) {
                // Map demo business card ID to actual business card ID
                $actualCardId = null;
                switch ($demoLead['demo_business_card_id']) {
                    case 'demo-card-1-uuid':
                        $actualCardId = $cardMapping['Alex Chen'] ?? null;
                        break;
                    case 'demo-card-2-uuid':
                        $actualCardId = $cardMapping['Sarah Martinez'] ?? null;
                        break;
                    case 'demo-card-3-uuid':
                        $actualCardId = $cardMapping['Michael Thompson'] ?? null;
                        break;
                }
                
                // If no mapping found, try to get the first available card
                if (!$actualCardId && !empty($businessCards)) {
                    $actualCardId = $businessCards[0]['id'];
                    error_log("DEMO DEBUG: Using first available card ID: " . $actualCardId);
                }
                
                if ($actualCardId) {
                    $db->execute("
                        INSERT INTO leads (
                            id_business_card, first_name, last_name, email_primary, work_phone, organization_name, job_title,
                            comments_from_lead, notes, ip_address, user_agent, referrer, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ", [
                        $actualCardId, $demoLead['first_name'], $demoLead['last_name'], $demoLead['email'],
                        $demoLead['phone'], $demoLead['company'], $demoLead['job_title'], $demoLead['message'],
                        $demoLead['notes'], $demoLead['ip_address'], $demoLead['user_agent'], $demoLead['referrer'],
                        $demoLead['captured_at']
                    ]);
                    error_log("DEMO DEBUG: Inserted lead for " . $demoLead['first_name'] . " " . $demoLead['last_name']);
                } else {
                    error_log("DEMO DEBUG: Could not find business card for lead " . $demoLead['first_name'] . " " . $demoLead['last_name']);
                }
            }
            
            // Populate contacts
            foreach ($demoContacts as $demoContact) {
                $db->execute("
                    INSERT INTO contacts (
                        id_user, first_name, last_name, email_primary, work_phone, organization_name, job_title,
                        created_at, notes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ", [
                    self::DEMO_USER_ID, $demoContact['first_name'], $demoContact['last_name'],
                    $demoContact['email'], $demoContact['phone'], $demoContact['company'],
                    $demoContact['job_title'], $demoContact['created_at'], $demoContact['notes']
                ]);
                error_log("DEMO DEBUG: Inserted contact for " . $demoContact['first_name'] . " " . $demoContact['last_name']);
            }
            
            error_log("DEMO DEBUG: Demo leads and contacts population completed successfully");
            
        } catch (Exception $e) {
            error_log("DEMO DEBUG: Error populating demo leads and contacts: " . $e->getMessage());
        }
    }
    
}
