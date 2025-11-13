<?php
/**
 * Cleanup Duplicate Contacts Script
 * 
 * This script removes duplicate contacts that were created from the same lead.
 * It keeps the most recent contact for each leadId and soft-deletes the older duplicates.
 * 
 * Usage: Run this script via web browser or command line
 * 
 * Safety: This script uses soft delete (sets is_deleted = 1) so data can be recovered if needed
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/UserAuth.php';

// Check if user is admin (for web access)
$isWebRequest = php_sapi_name() !== 'cli';
if ($isWebRequest) {
    if (!UserAuth::isLoggedIn() || !UserAuth::isAdmin()) {
        http_response_code(403);
        die("Access denied. Admin privileges required.");
    }
}

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();

try {
    $db->beginTransaction();
    
    // Step 1: Find duplicates
    $findDuplicatesQuery = "
        SELECT 
            id_lead,
            COUNT(*) as duplicate_count,
            GROUP_CONCAT(id ORDER BY updated_at DESC) as contact_ids,
            GROUP_CONCAT(CONCAT(id, ':', updated_at) ORDER BY updated_at DESC SEPARATOR '|') as contact_details
        FROM contacts
        WHERE id_lead IS NOT NULL 
          AND id_lead != '' 
          AND id_lead != '0'
          AND is_deleted = 0
        GROUP BY id_lead
        HAVING COUNT(*) > 1
    ";
    
    $stmt = $db->query($findDuplicatesQuery);
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalDuplicates = 0;
    $contactsToDelete = [];
    $duplicateDetails = [];
    
    foreach ($duplicates as $duplicate) {
        $leadId = $duplicate['id_lead'];
        $count = (int)$duplicate['duplicate_count'];
        $totalDuplicates += ($count - 1); // Subtract 1 because we keep one
        
        // Get all contacts for this leadId
        $contactsQuery = "
            SELECT id, first_name, last_name, created_at, updated_at
            FROM contacts
            WHERE id_lead = ? AND is_deleted = 0
            ORDER BY updated_at DESC, created_at DESC, id DESC
        ";
        $contactsStmt = $db->prepare($contactsQuery);
        $contactsStmt->execute([$leadId]);
        $contacts = $contactsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Keep the first (most recent) one, mark others for deletion
        $keepContact = array_shift($contacts);
        $duplicateDetails[] = [
            'leadId' => $leadId,
            'keepContact' => [
                'id' => $keepContact['id'],
                'name' => trim($keepContact['first_name'] . ' ' . $keepContact['last_name']),
                'updated_at' => $keepContact['updated_at']
            ],
            'duplicates' => array_map(function($c) {
                return [
                    'id' => $c['id'],
                    'name' => trim($c['first_name'] . ' ' . $c['last_name']),
                    'updated_at' => $c['updated_at']
                ];
            }, $contacts)
        ];
        
        // Mark duplicates for deletion
        foreach ($contacts as $contact) {
            $contactsToDelete[] = $contact['id'];
        }
    }
    
    // Step 2: Soft delete duplicates
    $deletedCount = 0;
    if (!empty($contactsToDelete)) {
        $placeholders = implode(',', array_fill(0, count($contactsToDelete), '?'));
        $deleteQuery = "
            UPDATE contacts 
            SET is_deleted = 1, updated_at = NOW()
            WHERE id IN ($placeholders) AND is_deleted = 0
        ";
        $deleteStmt = $db->prepare($deleteQuery);
        $deleteStmt->execute($contactsToDelete);
        $deletedCount = $deleteStmt->rowCount();
    }
    
    // Step 3: Verify cleanup
    $verifyQuery = "
        SELECT 
            id_lead,
            COUNT(*) as remaining_count
        FROM contacts
        WHERE id_lead IS NOT NULL 
          AND id_lead != '' 
          AND id_lead != '0'
          AND is_deleted = 0
        GROUP BY id_lead
        HAVING COUNT(*) > 1
    ";
    $verifyStmt = $db->query($verifyQuery);
    $remainingDuplicates = $verifyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $db->commit();
    
    $result = [
        'success' => true,
        'message' => 'Duplicate contacts cleanup completed',
        'summary' => [
            'duplicate_groups_found' => count($duplicates),
            'total_duplicate_contacts' => $totalDuplicates,
            'contacts_deleted' => $deletedCount,
            'remaining_duplicates' => count($remainingDuplicates)
        ],
        'details' => $duplicateDetails
    ];
    
    if ($isWebRequest) {
        echo json_encode($result, JSON_PRETTY_PRINT);
    } else {
        // Command line output
        echo "=== Duplicate Contacts Cleanup ===\n";
        echo "Duplicate groups found: " . count($duplicates) . "\n";
        echo "Total duplicate contacts: $totalDuplicates\n";
        echo "Contacts deleted: $deletedCount\n";
        echo "Remaining duplicates: " . count($remainingDuplicates) . "\n\n";
        
        if (!empty($duplicateDetails)) {
            echo "Details:\n";
            foreach ($duplicateDetails as $detail) {
                echo "Lead ID: {$detail['leadId']}\n";
                echo "  Kept: {$detail['keepContact']['name']} (ID: {$detail['keepContact']['id']})\n";
                foreach ($detail['duplicates'] as $dup) {
                    echo "  Deleted: {$dup['name']} (ID: {$dup['id']})\n";
                }
                echo "\n";
            }
        }
    }
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Duplicate contacts cleanup error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    if ($isWebRequest) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error cleaning up duplicates: ' . $e->getMessage()
        ], JSON_PRETTY_PRINT);
    } else {
        echo "ERROR: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>

