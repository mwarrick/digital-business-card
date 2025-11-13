<?php
/**
 * Admin script to check and manually delete a business card
 * Usage: Run from browser or command line with card ID parameter
 * Example: ?card_id=abc123c39a6da4
 */

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';

// Get card ID from query parameter
$cardId = $_GET['card_id'] ?? $argv[1] ?? null;

if (!$cardId) {
    die("Error: Card ID required. Usage: ?card_id=YOUR_CARD_ID or php check-and-delete-card.php YOUR_CARD_ID\n");
}

$db = Database::getInstance();

try {
    // First, check if card exists and get its details
    $stmt = $db->prepare("
        SELECT 
            id, 
            user_id, 
            first_name, 
            last_name, 
            is_deleted, 
            is_active,
            created_at,
            updated_at
        FROM business_cards 
        WHERE id LIKE ? OR id = ?
        ORDER BY 
            CASE 
                WHEN id = ? THEN 1
                WHEN id LIKE ? THEN 2
                ELSE 3
            END
        LIMIT 10
    ");
    
    // Search for exact match or ending with the provided ID
    $searchPattern = "%$cardId";
    $stmt->execute([$cardId, $cardId, $cardId, $searchPattern]);
    $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($cards)) {
        echo "❌ No cards found matching ID: $cardId\n";
        exit(1);
    }
    
    echo "📋 Found " . count($cards) . " card(s) matching ID pattern:\n";
    echo str_repeat("=", 80) . "\n";
    
    foreach ($cards as $card) {
        echo "Card ID: " . $card['id'] . "\n";
        echo "User ID: " . $card['user_id'] . "\n";
        echo "Name: " . $card['first_name'] . " " . $card['last_name'] . "\n";
        echo "is_deleted: " . ($card['is_deleted'] ? "YES (1)" : "NO (0)") . "\n";
        echo "is_active: " . ($card['is_active'] ? "YES (1)" : "NO (0)") . "\n";
        echo "Created: " . $card['created_at'] . "\n";
        echo "Updated: " . $card['updated_at'] . "\n";
        echo str_repeat("-", 80) . "\n";
    }
    
    // If we found cards, ask if we should delete the ones that aren't already deleted
    $cardsToDelete = array_filter($cards, function($card) {
        return $card['is_deleted'] == 0;
    });
    
    if (!empty($cardsToDelete)) {
        echo "\n⚠️  Found " . count($cardsToDelete) . " card(s) that are NOT deleted:\n";
        foreach ($cardsToDelete as $card) {
            echo "  - " . $card['id'] . " (" . $card['first_name'] . " " . $card['last_name'] . ")\n";
        }
        
        // If running from command line, delete automatically
        if (php_sapi_name() === 'cli') {
            echo "\n🗑️  Deleting cards...\n";
            foreach ($cardsToDelete as $card) {
                $deleteStmt = $db->prepare("
                    UPDATE business_cards 
                    SET is_deleted = 1, updated_at = NOW() 
                    WHERE id = ?
                ");
                $deleteStmt->execute([$card['id']]);
                echo "✅ Soft deleted card: " . $card['id'] . " (" . $card['first_name'] . " " . $card['last_name'] . ")\n";
            }
            echo "\n✅ Deletion complete!\n";
        } else {
            // If running from browser, show a form
            echo "\n<form method='POST' style='margin-top: 20px;'>";
            echo "<input type='hidden' name='confirm_delete' value='1'>";
            echo "<button type='submit' style='background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;'>";
            echo "Delete " . count($cardsToDelete) . " Card(s)";
            echo "</button>";
            echo "</form>";
            
            if (isset($_POST['confirm_delete'])) {
                echo "\n🗑️  Deleting cards...\n";
                foreach ($cardsToDelete as $card) {
                    $deleteStmt = $db->prepare("
                        UPDATE business_cards 
                        SET is_deleted = 1, updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $deleteStmt->execute([$card['id']]);
                    echo "✅ Soft deleted card: " . $card['id'] . " (" . $card['first_name'] . " " . $card['last_name'] . ")<br>\n";
                }
                echo "\n✅ Deletion complete!<br>\n";
            }
        }
    } else {
        echo "\n✅ All matching cards are already soft-deleted.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

