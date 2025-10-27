<?php
/**
 * Populate Demo Data Fields
 * Fills empty fields in demo_data table with realistic fake data
 */

require_once __DIR__ . '/../api/includes/Database.php';

$db = Database::getInstance();

echo "<h1>Populate Demo Data Fields</h1>";

try {
    // First, ensure email_primary column exists
    $columns = $db->query("SHOW COLUMNS FROM demo_data LIKE 'email_primary'");
    if (count($columns) === 0) {
        echo "<p>Adding email_primary column...</p>";
        $db->execute("ALTER TABLE demo_data ADD COLUMN email_primary VARCHAR(255) AFTER last_name");
        $db->execute("CREATE INDEX idx_demo_data_email_primary ON demo_data (email_primary)");
        echo "<p>✅ Added email_primary column</p>";
    }
    
    // Get all demo data records
    $demoRecords = $db->query("SELECT * FROM demo_data");
    echo "<p><strong>Found " . count($demoRecords) . " demo records to process</strong></p>";
    
    if (count($demoRecords) === 0) {
        echo "<p style='color: red;'>❌ No demo data found in database</p>";
        exit;
    }
    
    // Function to generate email from first name and website URL
    function generateEmail($firstName, $websiteUrl) {
        $cleanFirstName = strtolower(trim($firstName));
        
        $domain = '';
        if (!empty($websiteUrl)) {
            $parsedUrl = parse_url($websiteUrl);
            if (isset($parsedUrl['host'])) {
                $domain = $parsedUrl['host'];
            }
        }
        
        if (empty($domain)) {
            $domain = 'example.com';
        }
        
        return $cleanFirstName . '@' . $domain;
    }
    
    // Function to generate fake phone number
    function generatePhone() {
        $areaCodes = ['555', '123', '456', '789', '321', '654', '987'];
        $areaCode = $areaCodes[array_rand($areaCodes)];
        $exchange = rand(100, 999);
        $number = rand(1000, 9999);
        return "+1 ({$areaCode}) {$exchange}-{$number}";
    }
    
    // Function to generate fake address
    function generateAddress() {
        $streets = ['Main St', 'Oak Ave', 'Pine Rd', 'Cedar Ln', 'Elm St', 'Maple Dr', 'First St', 'Second Ave'];
        $cities = ['San Francisco', 'New York', 'Los Angeles', 'Chicago', 'Boston', 'Seattle', 'Austin', 'Denver'];
        $states = ['CA', 'NY', 'IL', 'TX', 'WA', 'CO', 'MA', 'FL'];
        $zips = ['94105', '10001', '90210', '60601', '98101', '80202', '02101', '33101'];
        
        $street = rand(100, 9999) . ' ' . $streets[array_rand($streets)];
        $city = $cities[array_rand($cities)];
        $state = $states[array_rand($states)];
        $zip = $zips[array_rand($zips)];
        
        return [
            'street' => $street,
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'country' => 'United States'
        ];
    }
    
    // Function to generate fake bio
    function generateBio($jobTitle, $companyName) {
        $bios = [
            "Experienced {$jobTitle} at {$companyName} with a passion for innovation and excellence.",
            "Dedicated professional specializing in {$jobTitle} with {$companyName}. Committed to delivering outstanding results.",
            "Results-driven {$jobTitle} with {$companyName}. Focused on creating value and driving growth.",
            "Passionate {$jobTitle} at {$companyName}. Always looking for new challenges and opportunities to make an impact.",
            "Experienced {$jobTitle} with {$companyName}. Dedicated to excellence and continuous improvement."
        ];
        
        return $bios[array_rand($bios)];
    }
    
    // Update each record
    $updatedCount = 0;
    foreach ($demoRecords as $record) {
        $updates = [];
        $params = [];
        
        // Generate email_primary if empty
        if (empty($record['email_primary'])) {
            $email = generateEmail($record['first_name'], $record['website_url']);
            $updates[] = "email_primary = ?";
            $params[] = $email;
        }
        
        // Generate phone_number if empty
        if (empty($record['phone_number'])) {
            $phone = generatePhone();
            $updates[] = "phone_number = ?";
            $params[] = $phone;
        }
        
        // Generate address fields if empty
        if (empty($record['street']) || empty($record['city']) || empty($record['state']) || empty($record['zip']) || empty($record['country'])) {
            $address = generateAddress();
            if (empty($record['street'])) {
                $updates[] = "street = ?";
                $params[] = $address['street'];
            }
            if (empty($record['city'])) {
                $updates[] = "city = ?";
                $params[] = $address['city'];
            }
            if (empty($record['state'])) {
                $updates[] = "state = ?";
                $params[] = $address['state'];
            }
            if (empty($record['zip'])) {
                $updates[] = "zip = ?";
                $params[] = $address['zip'];
            }
            if (empty($record['country'])) {
                $updates[] = "country = ?";
                $params[] = $address['country'];
            }
        }
        
        // Generate bio if empty
        if (empty($record['bio'])) {
            $bio = generateBio($record['job_title'], $record['company_name']);
            $updates[] = "bio = ?";
            $params[] = $bio;
        }
        
        // Update record if there are changes
        if (!empty($updates)) {
            $params[] = $record['id']; // Add ID for WHERE clause
            
            $sql = "UPDATE demo_data SET " . implode(', ', $updates) . " WHERE id = ?";
            $db->execute($sql, $params);
            
            echo "<p>Updated {$record['first_name']} {$record['last_name']}: " . implode(', ', $updates) . "</p>";
            $updatedCount++;
        } else {
            echo "<p>{$record['first_name']} {$record['last_name']}: No updates needed</p>";
        }
    }
    
    echo "<p style='color: green;'>🎉 Successfully processed {$updatedCount} demo records</p>";
    
    // Show final results
    echo "<h2>Final Demo Data</h2>";
    $finalRecords = $db->query("
        SELECT 
            first_name, 
            last_name, 
            email_primary, 
            phone_number, 
            company_name, 
            job_title, 
            street, 
            city, 
            state, 
            zip, 
            country, 
            website_url,
            bio
        FROM demo_data 
        ORDER BY first_name
    ");
    
    echo "<table border='1' cellpadding='5' cellspacing='0' style='width: 100%; font-size: 12px;'>";
    echo "<tr>";
    echo "<th>Name</th><th>Email</th><th>Phone</th><th>Company</th><th>Title</th>";
    echo "<th>Address</th><th>Website</th><th>Bio</th>";
    echo "</tr>";
    
    foreach ($finalRecords as $record) {
        $fullName = $record['first_name'] . ' ' . $record['last_name'];
        $address = trim($record['street'] . ', ' . $record['city'] . ', ' . $record['state'] . ' ' . $record['zip'] . ', ' . $record['country']);
        $bio = strlen($record['bio']) > 50 ? substr($record['bio'], 0, 50) . '...' : $record['bio'];
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($fullName) . "</td>";
        echo "<td>" . htmlspecialchars($record['email_primary']) . "</td>";
        echo "<td>" . htmlspecialchars($record['phone_number']) . "</td>";
        echo "<td>" . htmlspecialchars($record['company_name']) . "</td>";
        echo "<td>" . htmlspecialchars($record['job_title']) . "</td>";
        echo "<td>" . htmlspecialchars($address) . "</td>";
        echo "<td>" . htmlspecialchars($record['website_url']) . "</td>";
        echo "<td>" . htmlspecialchars($bio) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Error details: " . $e->getTraceAsString() . "</p>";
}
?>
