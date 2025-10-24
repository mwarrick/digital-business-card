<?php
/**
 * Populate Demo Data
 * Fills leads_demo and contacts_demo tables with realistic sample data
 */

require_once __DIR__ . '/../api/includes/Database.php';

$db = Database::getInstance();

echo "<h1>Populating Demo Data</h1>";

try {
    // Clear existing demo data first
    echo "<p>Clearing existing demo data...</p>";
    $db->execute("DELETE FROM leads_demo");
    $db->execute("DELETE FROM contacts_demo");
    echo "<p>✅ Cleared existing demo data</p>";
    
    // Demo business card IDs
    $demoCards = [
        'demo-card-1-uuid' => 'Alex Chen - TechCorp Solutions',
        'demo-card-2-uuid' => 'Sarah Martinez - Design Studio Pro', 
        'demo-card-3-uuid' => 'Michael Thompson - Innovation Ventures'
    ];
    
    // Get the demo user ID from demo_data table
    $demoUserResult = $db->query("SELECT DISTINCT id FROM demo_data WHERE website_type = 'primary' LIMIT 1");
    if (empty($demoUserResult)) {
        echo "<p style='color: red;'>❌ Demo data not found in database</p>";
        exit;
    }
    $actualDemoUserId = $demoUserResult[0]['id'];
    echo "<p>Found demo user ID from demo_data: " . $actualDemoUserId . "</p>";
    
    // Get the demo business card IDs that will be created
    $demoCardIds = [
        'demo-card-1-uuid' => 'Alex Chen - TechCorp Solutions',
        'demo-card-2-uuid' => 'Sarah Martinez - Design Studio Pro', 
        'demo-card-3-uuid' => 'Michael Thompson - Innovation Ventures'
    ];
    
    // Populate leads_demo table (3 leads per card = 9 total)
    echo "<p>Populating leads_demo table...</p>";
    
    $leadsData = [
        // Alex Chen's leads (demo-card-1-uuid)
        [
            'demo_business_card_id' => 'demo-card-1-uuid',
            'demo_lead_id' => 1,
            'first_name' => 'Jennifer',
            'last_name' => 'Williams',
            'email' => 'jennifer.williams@techstartup.com',
            'phone' => '+1 (555) 234-5678',
            'company' => 'TechStartup Inc',
            'job_title' => 'Product Manager',
            'message' => 'Hi Alex, I saw your profile and I\'m very interested in discussing potential collaboration opportunities. We\'re looking for a senior developer to help us scale our platform.',
            'source' => 'web_form',
            'status' => 'new',
            'captured_at' => '2024-01-15 10:30:00',
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            'referrer' => 'https://linkedin.com'
        ],
        [
            'demo_business_card_id' => 'demo-card-1-uuid',
            'demo_lead_id' => 2,
            'first_name' => 'David',
            'last_name' => 'Kim',
            'email' => 'david.kim@consulting.com',
            'phone' => '+1 (555) 345-6789',
            'company' => 'Digital Consulting Group',
            'job_title' => 'Senior Consultant',
            'message' => 'Hello Alex, I\'m impressed by your technical background. We have several projects that could benefit from your expertise in React and Node.js.',
            'source' => 'web_form',
            'status' => 'new',
            'captured_at' => '2024-01-16 14:20:00',
            'ip_address' => '192.168.1.101',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'referrer' => 'https://google.com'
        ],
        [
            'demo_business_card_id' => 'demo-card-1-uuid',
            'demo_lead_id' => 3,
            'first_name' => 'Lisa',
            'last_name' => 'Rodriguez',
            'email' => 'lisa.rodriguez@events.com',
            'phone' => '+1 (555) 456-7890',
            'company' => 'Tech Events Co',
            'job_title' => 'Event Coordinator',
            'message' => 'Hi Alex, we\'re organizing a developer conference and would love to have you as a speaker. Your experience with cloud architecture would be perfect for our audience.',
            'source' => 'web_form',
            'status' => 'new',
            'captured_at' => '2024-01-17 09:15:00',
            'ip_address' => '192.168.1.102',
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15',
            'referrer' => 'https://twitter.com'
        ],
        
        // Sarah Martinez's leads (demo-card-2-uuid)
        [
            'demo_business_card_id' => 'demo-card-2-uuid',
            'demo_lead_id' => 4,
            'first_name' => 'Robert',
            'last_name' => 'Johnson',
            'email' => 'robert.johnson@startup.com',
            'phone' => '+1 (555) 567-8901',
            'company' => 'StartupXYZ',
            'job_title' => 'Founder & CEO',
            'message' => 'Hi Sarah, I\'m launching a new startup and need help with our brand identity and UI/UX design. Your portfolio is exactly what we\'re looking for.',
            'source' => 'web_form',
            'status' => 'new',
            'captured_at' => '2024-01-18 11:45:00',
            'ip_address' => '192.168.1.103',
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            'referrer' => 'https://dribbble.com'
        ],
        [
            'demo_business_card_id' => 'demo-card-2-uuid',
            'demo_lead_id' => 5,
            'first_name' => 'Amanda',
            'last_name' => 'Chen',
            'email' => 'amanda.chen@agency.com',
            'phone' => '+1 (555) 678-9012',
            'company' => 'Creative Agency Plus',
            'job_title' => 'Art Director',
            'message' => 'Hello Sarah, I\'m looking for a creative director to join our team. Your award-winning work caught my attention. Would love to discuss opportunities.',
            'source' => 'web_form',
            'status' => 'new',
            'captured_at' => '2024-01-19 16:30:00',
            'ip_address' => '192.168.1.104',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'referrer' => 'https://behance.net'
        ],
        [
            'demo_business_card_id' => 'demo-card-2-uuid',
            'demo_lead_id' => 6,
            'first_name' => 'Mark',
            'last_name' => 'Wilson',
            'email' => 'mark.wilson@retail.com',
            'phone' => '+1 (555) 789-0123',
            'company' => 'Retail Innovations',
            'job_title' => 'Marketing Director',
            'message' => 'Hi Sarah, we need a complete rebrand for our retail chain. Your expertise in brand identity would be perfect for this project.',
            'source' => 'web_form',
            'status' => 'new',
            'captured_at' => '2024-01-20 13:20:00',
            'ip_address' => '192.168.1.105',
            'user_agent' => 'Mozilla/5.0 (iPad; CPU OS 15_0 like Mac OS X) AppleWebKit/605.1.15',
            'referrer' => 'https://instagram.com'
        ],
        
        // Michael Thompson's leads (demo-card-3-uuid)
        [
            'demo_business_card_id' => 'demo-card-3-uuid',
            'demo_lead_id' => 7,
            'first_name' => 'Emily',
            'last_name' => 'Davis',
            'email' => 'emily.davis@venture.com',
            'phone' => '+1 (555) 890-1234',
            'company' => 'Venture Capital Partners',
            'job_title' => 'Investment Partner',
            'message' => 'Hello Michael, I\'m interested in discussing potential investment opportunities. Your track record in emerging markets is impressive.',
            'source' => 'web_form',
            'status' => 'new',
            'captured_at' => '2024-01-21 10:00:00',
            'ip_address' => '192.168.1.106',
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            'referrer' => 'https://linkedin.com'
        ],
        [
            'demo_business_card_id' => 'demo-card-3-uuid',
            'demo_lead_id' => 8,
            'first_name' => 'James',
            'last_name' => 'Brown',
            'email' => 'james.brown@consulting.com',
            'phone' => '+1 (555) 901-2345',
            'company' => 'Strategic Consulting Group',
            'job_title' => 'Senior Partner',
            'message' => 'Hi Michael, we\'re looking for a strategic advisor for our consulting practice. Your leadership experience would be invaluable.',
            'source' => 'web_form',
            'status' => 'new',
            'captured_at' => '2024-01-22 15:45:00',
            'ip_address' => '192.168.1.107',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'referrer' => 'https://google.com'
        ],
        [
            'demo_business_card_id' => 'demo-card-3-uuid',
            'demo_lead_id' => 9,
            'first_name' => 'Rachel',
            'last_name' => 'Taylor',
            'email' => 'rachel.taylor@conference.com',
            'phone' => '+1 (555) 012-3456',
            'company' => 'Business Conference Group',
            'job_title' => 'Conference Director',
            'message' => 'Hello Michael, we\'re organizing a leadership summit and would love to have you as a keynote speaker. Your insights on innovation would be perfect.',
            'source' => 'web_form',
            'status' => 'new',
            'captured_at' => '2024-01-23 12:30:00',
            'ip_address' => '192.168.1.108',
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15',
            'referrer' => 'https://twitter.com'
        ]
    ];
    
    // Insert leads data
    foreach ($leadsData as $lead) {
        $db->execute("
            INSERT INTO leads_demo (
                id_business_card, demo_business_card_id, demo_lead_id,
                first_name, last_name, email, phone, company, job_title,
                message, source, status, captured_at, ip_address, user_agent, referrer
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $lead['demo_business_card_id'], $lead['demo_business_card_id'], $lead['demo_lead_id'],
            $lead['first_name'], $lead['last_name'], $lead['email'], $lead['phone'], 
            $lead['company'], $lead['job_title'], $lead['message'], $lead['source'], 
            $lead['status'], $lead['captured_at'], $lead['ip_address'], 
            $lead['user_agent'], $lead['referrer']
        ]);
    }
    
    echo "<p>✅ Inserted " . count($leadsData) . " demo leads</p>";
    
    // Populate contacts_demo table (5 contacts per card = 15 total)
    echo "<p>Populating contacts_demo table...</p>";
    
    $contactsData = [
        // Alex Chen's contacts (demo-card-1-uuid)
        [
            'demo_user_id' => $actualDemoUserId,
            'demo_contact_id' => 1,
            'demo_lead_id' => 1, // Converted from lead 1
            'first_name' => 'Jennifer',
            'last_name' => 'Williams',
            'email' => 'jennifer.williams@techstartup.com',
            'phone' => '+1 (555) 234-5678',
            'company' => 'TechStartup Inc',
            'job_title' => 'Product Manager',
            'source' => 'converted_lead',
            'created_at' => '2024-01-15 10:30:00',
            'notes' => 'Converted from lead - interested in collaboration opportunities'
        ],
        [
            'demo_user_id' => $actualDemoUserId,
            'demo_contact_id' => 2,
            'demo_lead_id' => null,
            'first_name' => 'David',
            'last_name' => 'Kim',
            'email' => 'david.kim@consulting.com',
            'phone' => '+1 (555) 345-6789',
            'company' => 'Digital Consulting Group',
            'job_title' => 'Senior Consultant',
            'source' => 'manual',
            'created_at' => '2024-01-10 14:20:00',
            'notes' => 'Met at tech conference - potential collaboration partner'
        ],
        [
            'demo_user_id' => $actualDemoUserId,
            'demo_contact_id' => 3,
            'demo_lead_id' => null,
            'first_name' => 'Emma',
            'last_name' => 'Wilson',
            'email' => 'emma.wilson@design.com',
            'phone' => '+1 (555) 456-7890',
            'company' => 'Creative Design Studio',
            'job_title' => 'UI/UX Designer',
            'source' => 'manual',
            'created_at' => '2024-01-08 09:15:00',
            'notes' => 'Former colleague - excellent designer'
        ],
        [
            'demo_user_id' => $actualDemoUserId,
            'demo_contact_id' => 4,
            'demo_lead_id' => null,
            'first_name' => 'Ryan',
            'last_name' => 'Garcia',
            'email' => 'ryan.garcia@startup.com',
            'phone' => '+1 (555) 567-8901',
            'company' => 'StartupXYZ',
            'job_title' => 'CTO',
            'source' => 'manual',
            'created_at' => '2024-01-05 16:30:00',
            'notes' => 'Tech meetup contact - shared interest in React'
        ],
        [
            'demo_user_id' => $actualDemoUserId,
            'demo_contact_id' => 5,
            'demo_lead_id' => null,
            'first_name' => 'Maria',
            'last_name' => 'Rodriguez',
            'email' => 'maria.rodriguez@events.com',
            'phone' => '+1 (555) 678-9012',
            'company' => 'Tech Events Co',
            'job_title' => 'Event Coordinator',
            'source' => 'manual',
            'created_at' => '2024-01-03 11:45:00',
            'notes' => 'Conference organizer - potential speaking opportunity'
        ],
        
        // Sarah Martinez's contacts (demo-card-2-uuid)
        [
            'demo_user_id' => $actualDemoUserId,
            'demo_contact_id' => 6,
            'demo_lead_id' => 4, // Converted from lead 4
            'first_name' => 'Robert',
            'last_name' => 'Johnson',
            'email' => 'robert.johnson@startup.com',
            'phone' => '+1 (555) 567-8901',
            'company' => 'StartupXYZ',
            'job_title' => 'Founder & CEO',
            'source' => 'converted_lead',
            'created_at' => '2024-01-18 11:45:00',
            'notes' => 'Converted from lead - startup rebrand project'
        ],
        [
            'demo_user_id' => $actualDemoUserId,
            'demo_contact_id' => 7,
            'demo_lead_id' => null,
            'first_name' => 'Amanda',
            'last_name' => 'Chen',
            'email' => 'amanda.chen@agency.com',
            'phone' => '+1 (555) 678-9012',
            'company' => 'Creative Agency Plus',
            'job_title' => 'Art Director',
            'source' => 'manual',
            'created_at' => '2024-01-12 16:30:00',
            'notes' => 'Industry contact - potential collaboration'
        ],
        [
            'demo_user_id' => $actualDemoUserId,
            'demo_contact_id' => 8,
            'demo_lead_id' => null,
            'first_name' => 'Chris',
            'last_name' => 'Anderson',
            'email' => 'chris.anderson@brand.com',
            'phone' => '+1 (555) 789-0123',
            'company' => 'Brand Strategy Co',
            'job_title' => 'Brand Strategist',
            'source' => 'manual',
            'created_at' => '2024-01-09 13:20:00',
            'notes' => 'Brand strategy expert - referral source'
        ],
        [
            'demo_user_id' => $actualDemoUserId,
            'demo_contact_id' => 9,
            'demo_lead_id' => null,
            'first_name' => 'Lisa',
            'last_name' => 'Thompson',
            'email' => 'lisa.thompson@marketing.com',
            'phone' => '+1 (555) 890-1234',
            'company' => 'Marketing Solutions',
            'job_title' => 'Marketing Director',
            'source' => 'manual',
            'created_at' => '2024-01-07 10:00:00',
            'notes' => 'Marketing professional - potential client'
        ],
        [
            'demo_user_id' => $actualDemoUserId,
            'demo_contact_id' => 10,
            'demo_lead_id' => null,
            'first_name' => 'Kevin',
            'last_name' => 'Lee',
            'email' => 'kevin.lee@design.com',
            'phone' => '+1 (555) 901-2345',
            'company' => 'Design Collective',
            'job_title' => 'Creative Director',
            'source' => 'manual',
            'created_at' => '2024-01-04 15:45:00',
            'notes' => 'Design industry contact - mutual connections'
        ],
        
        // Michael Thompson's contacts (demo-card-3-uuid)
        [
            'demo_user_id' => $actualDemoUserId,
            'demo_contact_id' => 11,
            'demo_lead_id' => 7, // Converted from lead 7
            'first_name' => 'Emily',
            'last_name' => 'Davis',
            'email' => 'emily.davis@venture.com',
            'phone' => '+1 (555) 890-1234',
            'company' => 'Venture Capital Partners',
            'job_title' => 'Investment Partner',
            'source' => 'converted_lead',
            'created_at' => '2024-01-21 10:00:00',
            'notes' => 'Converted from lead - investment opportunity discussion'
        ],
        [
            'demo_user_id' => $actualDemoUserId,
            'demo_contact_id' => 12,
            'demo_lead_id' => null,
            'first_name' => 'James',
            'last_name' => 'Brown',
            'email' => 'james.brown@consulting.com',
            'phone' => '+1 (555) 901-2345',
            'company' => 'Strategic Consulting Group',
            'job_title' => 'Senior Partner',
            'source' => 'manual',
            'created_at' => '2024-01-14 12:30:00',
            'notes' => 'Strategic advisor - potential collaboration'
        ],
        [
            'demo_user_id' => $actualDemoUserId,
            'demo_contact_id' => 13,
            'demo_lead_id' => null,
            'first_name' => 'Rachel',
            'last_name' => 'Taylor',
            'email' => 'rachel.taylor@conference.com',
            'phone' => '+1 (555) 012-3456',
            'company' => 'Business Conference Group',
            'job_title' => 'Conference Director',
            'source' => 'manual',
            'created_at' => '2024-01-11 16:30:00',
            'notes' => 'Conference organizer - speaking opportunity'
        ],
        [
            'demo_user_id' => $actualDemoUserId,
            'demo_contact_id' => 14,
            'demo_lead_id' => null,
            'first_name' => 'Daniel',
            'last_name' => 'Miller',
            'email' => 'daniel.miller@innovation.com',
            'phone' => '+1 (555) 123-4567',
            'company' => 'Innovation Labs',
            'job_title' => 'Research Director',
            'source' => 'manual',
            'created_at' => '2024-01-06 14:20:00',
            'notes' => 'Innovation researcher - shared interests'
        ],
        [
            'demo_user_id' => $actualDemoUserId,
            'demo_contact_id' => 15,
            'demo_lead_id' => null,
            'first_name' => 'Sophia',
            'last_name' => 'Clark',
            'email' => 'sophia.clark@leadership.com',
            'phone' => '+1 (555) 234-5678',
            'company' => 'Leadership Institute',
            'job_title' => 'Executive Coach',
            'source' => 'manual',
            'created_at' => '2024-01-02 09:15:00',
            'notes' => 'Leadership coach - potential mentor relationship'
        ]
    ];
    
    // Insert contacts data
    foreach ($contactsData as $contact) {
        $db->execute("
            INSERT INTO contacts_demo (
                id_user, demo_user_id, demo_contact_id, demo_lead_id,
                first_name, last_name, email, phone, company, job_title,
                source, created_at, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $actualDemoUserId, $actualDemoUserId, $contact['demo_contact_id'], $contact['demo_lead_id'],
            $contact['first_name'], $contact['last_name'], $contact['email'], $contact['phone'],
            $contact['company'], $contact['job_title'], $contact['source'], $contact['created_at'], $contact['notes']
        ]);
    }
    
    echo "<p>✅ Inserted " . count($contactsData) . " demo contacts</p>";
    
    echo "<p style='color: green;'>🎉 Demo data population completed successfully!</p>";
    echo "<p><strong>Summary:</strong></p>";
    echo "<ul>";
    echo "<li>✅ 9 demo leads created (3 per business card)</li>";
    echo "<li>✅ 15 demo contacts created (5 per business card)</li>";
    echo "<li>✅ 3 lead-to-contact conversions included</li>";
    echo "<li>✅ Realistic data with proper relationships</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Demo data population failed: " . $e->getMessage() . "</p>";
    echo "<p>Error details: " . $e->getTraceAsString() . "</p>";
}
?>
