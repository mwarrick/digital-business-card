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
        
        // Delete ALL existing demo cards and related data (including user-created ones)
        $db->execute("DELETE FROM website_links WHERE business_card_id IN (SELECT id FROM business_cards WHERE user_id = ?)", [self::DEMO_USER_ID]);
        $db->execute("DELETE FROM email_contacts WHERE business_card_id IN (SELECT id FROM business_cards WHERE user_id = ?)", [self::DEMO_USER_ID]);
        $db->execute("DELETE FROM phone_contacts WHERE business_card_id IN (SELECT id FROM business_cards WHERE user_id = ?)", [self::DEMO_USER_ID]);
        $db->execute("DELETE FROM addresses WHERE business_card_id IN (SELECT id FROM business_cards WHERE user_id = ?)", [self::DEMO_USER_ID]);
        $db->execute("DELETE FROM analytics_daily WHERE card_id IN (SELECT id FROM business_cards WHERE user_id = ?)", [self::DEMO_USER_ID]);
        $db->execute("DELETE FROM analytics_events WHERE card_id IN (SELECT id FROM business_cards WHERE user_id = ?)", [self::DEMO_USER_ID]);
        $db->execute("DELETE FROM analytics_sessions WHERE card_id IN (SELECT id FROM business_cards WHERE user_id = ?)", [self::DEMO_USER_ID]);
        $db->execute("DELETE FROM business_cards WHERE user_id = ?", [self::DEMO_USER_ID]);
        
        // Delete ALL demo user invitations and related data
        $db->execute("DELETE FROM invitations WHERE inviter_user_id = ?", [self::DEMO_USER_ID]);
        
        error_log("Deleted all demo cards (system + user-created), invitations, and related data");
        
        // Create 3 sample business cards
        $cards = [
            [
                'id' => 'demo-card-1-uuid',
                'first_name' => 'Alex',
                'last_name' => 'Chen',
                'phone_number' => '+1 (555) 123-4567',
                'company_name' => 'TechCorp Solutions',
                'job_title' => 'Senior Software Engineer',
                'bio' => 'Passionate about building scalable web applications and leading development teams. 10+ years experience in full-stack development.',
                'theme' => 'professional-blue'
            ],
            [
                'id' => 'demo-card-2-uuid',
                'first_name' => 'Sarah',
                'last_name' => 'Martinez',
                'phone_number' => '+1 (555) 987-6543',
                'company_name' => 'Design Studio Pro',
                'job_title' => 'Creative Director',
                'bio' => 'Award-winning designer with expertise in brand identity, UI/UX design, and creative direction. Helping businesses tell their story through compelling visual design.',
                'theme' => 'creative-purple'
            ],
            [
                'id' => 'demo-card-3-uuid',
                'first_name' => 'Michael',
                'last_name' => 'Thompson',
                'phone_number' => '+1 (555) 456-7890',
                'company_name' => 'Innovation Ventures',
                'job_title' => 'Chief Executive Officer',
                'bio' => 'Visionary leader with 15+ years of experience in strategic planning, business development, and team leadership. Passionate about driving innovation and growth in emerging markets.',
                'theme' => 'executive-gold'
            ]
        ];
        
        // Insert the business cards
        foreach ($cards as $card) {
            $db->execute(
                "INSERT INTO business_cards (
                    id, user_id, first_name, last_name, phone_number, company_name, job_title, bio,
                    profile_photo_path, company_logo_path, cover_graphic_path, theme,
                    profile_photo, company_logo, cover_graphic, is_active,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, NULL, ?, NULL, NULL, NULL, 1, NOW(), NOW())",
                [
                    $card['id'], self::DEMO_USER_ID, $card['first_name'], $card['last_name'],
                    $card['phone_number'], $card['company_name'], $card['job_title'], $card['bio'],
                    $card['theme']
                ]
            );
        }
        
        // Add additional emails for the Creative Designer (demo-card-2)
        $db->execute(
            "INSERT INTO email_contacts (id, business_card_id, email, type, label, is_primary, created_at, updated_at)
             VALUES ('demo-email-1-uuid', 'demo-card-2-uuid', 'sarah.martinez@designstudiopro.com', 'work', 'Work Email', 0, NOW(), NOW())"
        );
        $db->execute(
            "INSERT INTO email_contacts (id, business_card_id, email, type, label, is_primary, created_at, updated_at)
             VALUES ('demo-email-2-uuid', 'demo-card-2-uuid', 'sarah@creativemartinez.com', 'personal', 'Personal Email', 0, NOW(), NOW())"
        );
        
        // Add additional phones for the Creative Designer (demo-card-2)
        $db->execute(
            "INSERT INTO phone_contacts (id, business_card_id, phone_number, type, label, created_at, updated_at)
             VALUES ('demo-phone-1-uuid', 'demo-card-2-uuid', '+1 (555) 987-6544', 'mobile', 'Mobile', NOW(), NOW())"
        );
        $db->execute(
            "INSERT INTO phone_contacts (id, business_card_id, phone_number, type, label, created_at, updated_at)
             VALUES ('demo-phone-2-uuid', 'demo-card-2-uuid', '+1 (555) 987-6545', 'work', 'Office', NOW(), NOW())"
        );
        
        // Add website links for the Business Executive (demo-card-3)
        $db->execute(
            "INSERT INTO website_links (id, business_card_id, name, url, description, is_primary, created_at, updated_at)
             VALUES ('demo-website-1-uuid', 'demo-card-3-uuid', 'Company Website', 'https://innovationventures.com', 'Main company website', 1, NOW(), NOW())"
        );
        $db->execute(
            "INSERT INTO website_links (id, business_card_id, name, url, description, is_primary, created_at, updated_at)
             VALUES ('demo-website-2-uuid', 'demo-card-3-uuid', 'LinkedIn Profile', 'https://linkedin.com/in/michaelthompson', 'Professional networking profile', 0, NOW(), NOW())"
        );
        $db->execute(
            "INSERT INTO website_links (id, business_card_id, name, url, description, is_primary, created_at, updated_at)
             VALUES ('demo-website-3-uuid', 'demo-card-3-uuid', 'Personal Blog', 'https://michaelthompson.blog', 'Thoughts on leadership and innovation', 0, NOW(), NOW())"
        );
        
        // Verify cards were created
        $finalCount = $db->querySingle(
            "SELECT COUNT(*) as count FROM business_cards WHERE user_id = ?",
            [self::DEMO_USER_ID]
        )['count'];
        
        error_log("Demo card reset complete. Final count: $finalCount fresh system cards (all previous changes wiped)");
    }
}
