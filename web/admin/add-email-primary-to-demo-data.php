<?php
/**
 * Add email_primary field to demo_data table and populate it
 * Combines first_name with website_url to create email addresses
 */

require_once __DIR__ . '/../api/includes/Database.php';

$db = Database::getInstance();

echo "<h1>Add Email Primary to Demo Data</h1>";

try {
    // Check if email_primary column already exists
    $columns = $db->query("SHOW COLUMNS FROM demo_data LIKE 'email_primary'");
    if (count($columns) > 0) {
        echo "<p style='color: orange;'>⚠️ email_primary column already exists</p>";
    } else {
        echo "<p>Adding email_primary column to demo_data table...</p>";
        
        // Add email_primary column
        $db->execute("ALTER TABLE demo_data ADD COLUMN email_primary VARCHAR(255) AFTER last_name");
        echo "<p>✅ Added email_primary column</p>";
        
        // Add index
        $db->execute("CREATE INDEX idx_demo_data_email_primary ON demo_data (email_primary)");
        echo "<p>✅ Added index for email_primary</p>";
    }
    
    // Get all demo data records
    $demoRecords = $db->query("SELECT id, first_name, last_name, website_url FROM demo_data");
    echo "<p><strong>Found " . count($demoRecords) . " demo records to update</strong></p>";
    
    if (count($demoRecords) === 0) {
        echo "<p style='color: red;'>❌ No demo data found in database</p>";
        exit;
    }
    
    // Function to generate email from first name and website URL
    function generateEmail($firstName, $websiteUrl) {
        // Clean the first name (lowercase, remove spaces)
        $cleanFirstName = strtolower(trim($firstName));
        
        // Extract domain from website URL
        $domain = '';
        if (!empty($websiteUrl)) {
            $parsedUrl = parse_url($websiteUrl);
            if (isset($parsedUrl['host'])) {
                $domain = $parsedUrl['host'];
            }
        }
        
        // If no domain found, use a default
        if (empty($domain)) {
            $domain = 'example.com';
        }
        
        return $cleanFirstName . '@' . $domain;
    }
    
    // Update each record with generated email
    $updatedCount = 0;
    foreach ($demoRecords as $record) {
        $email = generateEmail($record['first_name'], $record['website_url']);
        
        $db->execute(
            "UPDATE demo_data SET email_primary = ? WHERE id = ?",
            [$email, $record['id']]
        );
        
        echo "<p>Updated {$record['first_name']} {$record['last_name']}: {$email}</p>";
        $updatedCount++;
    }
    
    echo "<p style='color: green;'>🎉 Successfully updated {$updatedCount} demo records with email_primary</p>";
    
    // Show final results
    echo "<h2>Final Demo Data</h2>";
    $finalRecords = $db->query("SELECT first_name, last_name, email_primary, website_url FROM demo_data ORDER BY first_name");
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>First Name</th><th>Last Name</th><th>Email Primary</th><th>Website URL</th></tr>";
    
    foreach ($finalRecords as $record) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($record['first_name']) . "</td>";
        echo "<td>" . htmlspecialchars($record['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($record['email_primary']) . "</td>";
        echo "<td>" . htmlspecialchars($record['website_url']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Error details: " . $e->getTraceAsString() . "</p>";
}
?>
