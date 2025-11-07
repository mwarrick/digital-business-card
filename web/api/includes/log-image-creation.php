<?php
/**
 * Helper function to log image creation events
 * Include this file and call logImageCreation() from anywhere
 */

require_once __DIR__ . '/ImageCreationLogger.php';

/**
 * Log an image creation event
 * 
 * @param string $imageName - Name of the image file
 * @param string $imagePath - Full path to the image file
 * @param string $imageType - Type: profile_photo, company_logo, cover_graphic, virtual_background, name_tag, other
 * @param string $creationMethod - Method: upload, generated, placeholder, demo_copy
 * @param int $fileSize - File size in bytes (optional)
 * @param string $dimensions - Image dimensions like "800x600" (optional)
 */
function logImageCreation($imageName, $imagePath, $imageType, $creationMethod, $fileSize = null, $dimensions = null) {
    try {
        $logger = new ImageCreationLogger();
        $logger->logImageCreation($imageName, $imagePath, $imageType, $creationMethod, $fileSize, $dimensions);
    } catch (Exception $e) {
        error_log("Failed to log image creation: " . $e->getMessage());
    }
}
?>







