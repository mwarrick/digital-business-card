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
            error_log("DEMO DEBUG: Custom demo images detected - preserving user-provided images");
        } else {
            error_log("DEMO DEBUG: No custom demo images found - will use generated placeholder images");
        }
        
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
                'theme' => 'professional-blue',
                'profile_photo_path' => 'demo-alex-profile.jpg',
                'company_logo_path' => 'demo-techcorp-logo.jpg',
                'cover_graphic_path' => 'demo-techcorp-cover.jpg'
            ],
            [
                'id' => 'demo-card-2-uuid',
                'first_name' => 'Sarah',
                'last_name' => 'Martinez',
                'phone_number' => '+1 (555) 987-6543',
                'company_name' => 'Design Studio Pro',
                'job_title' => 'Creative Director',
                'bio' => 'Award-winning designer with expertise in brand identity, UI/UX design, and creative direction. Helping businesses tell their story through compelling visual design.',
                'theme' => 'creative-sunset',
                'profile_photo_path' => 'demo-sarah-profile.jpg',
                'company_logo_path' => 'demo-designstudio-logo.jpg',
                'cover_graphic_path' => 'demo-designstudio-cover.jpg'
            ],
            [
                'id' => 'demo-card-3-uuid',
                'first_name' => 'Michael',
                'last_name' => 'Thompson',
                'phone_number' => '+1 (555) 456-7890',
                'company_name' => 'Innovation Ventures',
                'job_title' => 'Chief Executive Officer',
                'bio' => 'Visionary leader with 15+ years of experience in strategic planning, business development, and team leadership. Passionate about driving innovation and growth in emerging markets.',
                'theme' => 'minimalist-gray',
                'profile_photo_path' => 'demo-michael-profile.jpg',
                'company_logo_path' => 'demo-innovation-logo.jpg',
                'cover_graphic_path' => 'demo-innovation-cover.jpg'
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
        
        // Add website links for all cards (if website_links table exists)
        try {
            // Add website links for the Software Engineer (demo-card-1)
            $db->execute(
                "INSERT INTO website_links (id, business_card_id, name, url, description, is_primary, created_at, updated_at)
                 VALUES ('demo-website-1-uuid', 'demo-card-1-uuid', 'Company Website', 'https://techcorpsolutions.com', 'Main company website', 1, NOW(), NOW())"
            );
            $db->execute(
                "INSERT INTO website_links (id, business_card_id, name, url, description, is_primary, created_at, updated_at)
                 VALUES ('demo-website-2-uuid', 'demo-card-1-uuid', 'LinkedIn Profile', 'https://linkedin.com/in/alexchen', 'Professional networking profile', 0, NOW(), NOW())"
            );
            
            // Add website links for the Creative Director (demo-card-2)
            $db->execute(
                "INSERT INTO website_links (id, business_card_id, name, url, description, is_primary, created_at, updated_at)
                 VALUES ('demo-website-3-uuid', 'demo-card-2-uuid', 'Portfolio Website', 'https://sarahmartinez.design', 'Creative portfolio and design work', 1, NOW(), NOW())"
            );
            $db->execute(
                "INSERT INTO website_links (id, business_card_id, name, url, description, is_primary, created_at, updated_at)
                 VALUES ('demo-website-4-uuid', 'demo-card-2-uuid', 'LinkedIn Profile', 'https://linkedin.com/in/sarahmartinez', 'Professional networking profile', 0, NOW(), NOW())"
            );
            
            // Add website links for the Business Executive (demo-card-3)
            $db->execute(
                "INSERT INTO website_links (id, business_card_id, name, url, description, is_primary, created_at, updated_at)
                 VALUES ('demo-website-5-uuid', 'demo-card-3-uuid', 'Company Website', 'https://innovationventures.com', 'Main company website', 1, NOW(), NOW())"
            );
            $db->execute(
                "INSERT INTO website_links (id, business_card_id, name, url, description, is_primary, created_at, updated_at)
                 VALUES ('demo-website-6-uuid', 'demo-card-3-uuid', 'LinkedIn Profile', 'https://linkedin.com/in/michaelthompson', 'Professional networking profile', 0, NOW(), NOW())"
            );
            $db->execute(
                "INSERT INTO website_links (id, business_card_id, name, url, description, is_primary, created_at, updated_at)
                 VALUES ('demo-website-7-uuid', 'demo-card-3-uuid', 'Personal Blog', 'https://michaelthompson.blog', 'Thoughts on leadership and innovation', 0, NOW(), NOW())"
            );
            error_log("Demo website links added successfully");
        } catch (Exception $e) {
            error_log("Demo website links not added (table may not exist): " . $e->getMessage());
        }
        
        // Add addresses for all three cards (if addresses table exists)
        try {
            $db->execute(
                "INSERT INTO addresses (id, card_id, street, city, state, zip, country, created_at)
                 VALUES ('demo-address-1-uuid', 'demo-card-1-uuid', '1234 Market Street, Suite 500', 'San Francisco', 'CA', '94105', 'United States', NOW())"
            );
            $db->execute(
                "INSERT INTO addresses (id, card_id, street, city, state, zip, country, created_at)
                 VALUES ('demo-address-2-uuid', 'demo-card-2-uuid', '567 Broadway, Floor 12', 'New York', 'NY', '10012', 'United States', NOW())"
            );
            $db->execute(
                "INSERT INTO addresses (id, card_id, street, city, state, zip, country, created_at)
                 VALUES ('demo-address-3-uuid', 'demo-card-3-uuid', '890 Boylston Street, Suite 200', 'Boston', 'MA', '02115', 'United States', NOW())"
            );
            error_log("Demo addresses added successfully");
        } catch (Exception $e) {
            error_log("Demo addresses not added (table may not exist): " . $e->getMessage());
        }
        
        // Generate demo images only if custom images don't exist
        error_log("DEMO DEBUG: About to call generateDemoImagesIfNeeded");
        self::generateDemoImagesIfNeeded($mediaDir);
        error_log("DEMO DEBUG: Finished generateDemoImagesIfNeeded");
        
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
        return $existingCount >= 3;
    }
    
    /**
     * Generate demo images only if custom images don't exist
     */
    private static function generateDemoImagesIfNeeded($mediaDir) {
        // Check if we need to generate any images
        $imagesToGenerate = [];
        
        $imageSets = [
            'demo-alex-profile.jpg' => 'Alex Chen profile',
            'demo-techcorp-logo.jpg' => 'TechCorp logo',
            'demo-techcorp-cover.jpg' => 'TechCorp cover',
            'demo-sarah-profile.jpg' => 'Sarah Martinez profile', 
            'demo-designstudio-logo.jpg' => 'Design Studio logo',
            'demo-designstudio-cover.jpg' => 'Design Studio cover',
            'demo-michael-profile.jpg' => 'Michael Thompson profile',
            'demo-innovation-logo.jpg' => 'Innovation Ventures logo',
            'demo-innovation-cover.jpg' => 'Innovation Ventures cover'
        ];
        
        foreach ($imageSets as $filename => $description) {
            if (!file_exists($mediaDir . '/' . $filename)) {
                $imagesToGenerate[$filename] = $description;
            }
        }
        
        if (empty($imagesToGenerate)) {
            error_log("All demo images exist - no generation needed");
            return;
        }
        
        error_log("Generating " . count($imagesToGenerate) . " missing demo images: " . implode(', ', array_keys($imagesToGenerate)));
        
        // Generate only the missing images individually instead of running the full script
        self::generateIndividualImages($mediaDir, $imagesToGenerate);
    }
    
    /**
     * Generate individual missing images without overwriting existing ones
     */
    private static function generateIndividualImages($mediaDir, $imagesToGenerate) {
        // Demo card data for image generation
        $demoCards = [
            [
                'name' => 'Alex Chen',
                'initials' => 'AC',
                'company' => 'TechCorp Solutions',
                'colors' => ['#667eea', '#764ba2'],
                'files' => [
                    'profile' => 'demo-alex-profile.jpg',
                    'logo' => 'demo-techcorp-logo.jpg',
                    'cover' => 'demo-techcorp-cover.jpg'
                ]
            ],
            [
                'name' => 'Sarah Martinez',
                'initials' => 'SM',
                'company' => 'Design Studio Pro',
                'colors' => ['#ff6b6b', '#ffa726'], // Creative sunset colors (coral to orange)
                'files' => [
                    'profile' => 'demo-sarah-profile.jpg',
                    'logo' => 'demo-designstudio-logo.jpg',
                    'cover' => 'demo-designstudio-cover.jpg'
                ]
            ],
            [
                'name' => 'Michael Thompson',
                'initials' => 'MT',
                'company' => 'Innovation Ventures',
                'colors' => ['#6c757d', '#495057'], // Minimalist gray theme (light gray to dark gray)
                'files' => [
                    'profile' => 'demo-michael-profile.jpg',
                    'logo' => 'demo-innovation-logo.jpg',
                    'cover' => 'demo-innovation-cover.jpg'
                ]
            ]
        ];
        
        foreach ($demoCards as $card) {
            foreach ($card['files'] as $type => $filename) {
                if (isset($imagesToGenerate[$filename])) {
                    error_log("DEMO DEBUG: Generating missing image: $filename (type: $type)");
                    
                    switch ($type) {
                        case 'profile':
                            error_log("DEMO DEBUG: Calling generateProfilePhoto for $filename");
                            self::generateProfilePhoto($card['name'], $card['initials'], $card['colors'], $filename, $mediaDir);
                            break;
                        case 'logo':
                            error_log("DEMO DEBUG: Calling generateCompanyLogo for $filename");
                            self::generateCompanyLogo($card['company'], $card['colors'], $filename, $mediaDir);
                            break;
                        case 'cover':
                            error_log("DEMO DEBUG: Calling generateCoverGraphic for $filename");
                            self::generateCoverGraphic($card['company'], $card['colors'], $filename, $mediaDir);
                            break;
                    }
                } else {
                    error_log("DEMO DEBUG: Skipping $filename - already exists or not needed");
                }
            }
        }
    }
    
    /**
     * Generate profile photo (400x400px with initials)
     */
    private static function generateProfilePhoto($name, $initials, $colors, $filename, $mediaDir) {
        $size = 400;
        $image = imagecreatetruecolor($size, $size);
        
        // Create solid color background
        $rgb = self::hexToRgb($colors[0]);
        $bgColor = imagecolorallocate($image, $rgb['r'], $rgb['g'], $rgb['b']);
        imagefill($image, 0, 0, $bgColor);
        
        // Add initials
        $white = imagecolorallocate($image, 255, 255, 255);
        $font = 5; // Built-in font
        
        // Calculate text position (centered)
        $textWidth = imagefontwidth($font) * strlen($initials);
        $textHeight = imagefontheight($font);
        $x = ($size - $textWidth) / 2;
        $y = ($size - $textHeight) / 2;
        
        imagestring($image, $font, $x, $y, $initials, $white);
        
        // Save image
        $filepath = $mediaDir . '/' . $filename;
        imagejpeg($image, $filepath, 90);
        imagedestroy($image);
        
        error_log("Generated profile photo: $filename");
    }
    
    /**
     * Generate company logo (400x400px geometric design)
     */
    private static function generateCompanyLogo($company, $colors, $filename, $mediaDir) {
        $size = 400;
        $image = imagecreatetruecolor($size, $size);
        
        // White background
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);
        
        // Draw geometric logo
        $rgb = self::hexToRgb($colors[0]);
        $logoColor = imagecolorallocate($image, $rgb['r'], $rgb['g'], $rgb['b']);
        
        // Draw overlapping circles for logo
        $centerX = $size / 2;
        $centerY = $size / 2;
        $radius = 80;
        
        imagefilledellipse($image, $centerX - 30, $centerY, $radius, $radius, $logoColor);
        imagefilledellipse($image, $centerX + 30, $centerY, $radius, $radius, $logoColor);
        
        // Add company name initials
        $textColor = imagecolorallocate($image, 255, 255, 255);
        $font = 5;
        $initials = substr($company, 0, 2);
        $textWidth = imagefontwidth($font) * strlen($initials);
        $textHeight = imagefontheight($font);
        $x = ($size - $textWidth) / 2;
        $y = ($size - $textHeight) / 2;
        
        imagestring($image, $font, $x, $y, $initials, $textColor);
        
        // Save image
        $filepath = $mediaDir . '/' . $filename;
        imagejpeg($image, $filepath, 90);
        imagedestroy($image);
        
        error_log("Generated company logo: $filename");
    }
    
    /**
     * Generate cover graphic (1200x400px banner with company name)
     */
    private static function generateCoverGraphic($company, $colors, $filename, $mediaDir) {
        $width = 1200;
        $height = 400;
        $image = imagecreatetruecolor($width, $height);
        
        // Create solid color background
        $rgb = self::hexToRgb($colors[0]);
        $bgColor = imagecolorallocate($image, $rgb['r'], $rgb['g'], $rgb['b']);
        imagefill($image, 0, 0, $bgColor);
        
        // Add company name
        $white = imagecolorallocate($image, 255, 255, 255);
        $font = 5;
        
        // Calculate text position (centered)
        $textWidth = imagefontwidth($font) * strlen($company);
        $textHeight = imagefontheight($font);
        $x = ($width - $textWidth) / 2;
        $y = ($height - $textHeight) / 2;
        
        imagestring($image, $font, $x, $y, $company, $white);
        
        // Save image
        $filepath = $mediaDir . '/' . $filename;
        imagejpeg($image, $filepath, 90);
        imagedestroy($image);
        
        error_log("Generated cover graphic: $filename");
    }
    
    /**
     * Convert hex color to RGB
     */
    private static function hexToRgb($hex) {
        $hex = ltrim($hex, '#');
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2))
        ];
    }
}
