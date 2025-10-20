<?php
/**
 * Debug script to check name tag form elements and JavaScript
 */

// Get a real card ID for testing
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    $card = $pdo->query("SELECT id FROM business_cards WHERE is_active = 1 LIMIT 1")->fetch();
    
    if (!$card) {
        die("No active cards found");
    }
    
    $cardId = $card['id'];
    
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Name Tag Debug</title>
</head>
<body>
    <h1>Name Tag Debug</h1>
    
    <h2>Test Card ID: <?php echo htmlspecialchars($cardId); ?></h2>
    
    <h3>Form Elements Test:</h3>
    <form id="preferences-form" method="POST">
        <div>
            <label for="font_family">Font Family:</label>
            <select name="font_family" id="font_family">
                <option value="helvetica">Helvetica</option>
                <option value="times">Times</option>
                <option value="courier">Courier</option>
            </select>
        </div>
        
        <div>
            <label for="font_size">Font Size:</label>
            <select name="font_size" id="font_size">
                <option value="12">12pt</option>
                <option value="14">14pt</option>
                <option value="16">16pt</option>
            </select>
        </div>
        
        <div>
            <label for="line_spacing">Line Spacing:</label>
            <select name="line_spacing" id="line_spacing">
                <option value="0">Normal</option>
                <option value="1">Loose</option>
                <option value="-1">Tight</option>
            </select>
        </div>
        
        <div>
            <input type="checkbox" name="include_name" id="include_name" checked>
            <label for="include_name">Include Name</label>
        </div>
        
        <div>
            <input type="checkbox" name="include_title" id="include_title" checked>
            <label for="include_title">Include Title</label>
        </div>
        
        <div>
            <input type="checkbox" name="include_phone" id="include_phone" checked>
            <label for="include_phone">Include Phone</label>
        </div>
        
        <div>
            <input type="checkbox" name="include_email" id="include_email" checked>
            <label for="include_email">Include Email</label>
        </div>
        
        <div>
            <input type="checkbox" name="include_website" id="include_website" checked>
            <label for="include_website">Include Website</label>
        </div>
        
        <div>
            <input type="checkbox" name="include_address" id="include_address">
            <label for="include_address">Include Address</label>
        </div>
    </form>
    
    <h3>Preview Test:</h3>
    <div>
        <img id="preview-image" src="/user/cards/preview-name-tag.php?card_id=<?php echo urlencode($cardId); ?>&include_name=1&include_title=1&include_phone=1&include_email=1&include_website=1&include_address=0&font_family=helvetica&font_size=12&line_spacing=0" alt="Preview" style="border: 1px solid #ccc; max-width: 400px;">
    </div>
    
    <h3>Test Buttons:</h3>
    <button onclick="testPreview()">Test Preview Update</button>
    <button onclick="testDownload()">Test PDF Download</button>
    
    <h3>Debug Output:</h3>
    <div id="debug-output"></div>
    
    <script>
        const cardId = '<?php echo addslashes($cardId); ?>';
        const form = document.getElementById('preferences-form');
        
        function log(message) {
            const debug = document.getElementById('debug-output');
            debug.innerHTML += '<p>' + new Date().toLocaleTimeString() + ': ' + message + '</p>';
            console.log(message);
        }
        
        function testPreview() {
            log('Testing preview update...');
            
            try {
                const fontFamily = document.getElementById('font_family');
                const fontSize = document.getElementById('font_size');
                const lineSpacing = document.getElementById('line_spacing');
                
                log('Font family element: ' + (fontFamily ? 'Found' : 'NOT FOUND'));
                log('Font size element: ' + (fontSize ? 'Found' : 'NOT FOUND'));
                log('Line spacing element: ' + (lineSpacing ? 'Found' : 'NOT FOUND'));
                
                if (fontFamily && fontSize && lineSpacing) {
                    log('Font family value: ' + fontFamily.value);
                    log('Font size value: ' + fontSize.value);
                    log('Line spacing value: ' + lineSpacing.value);
                    
                    const params = new URLSearchParams({
                        card_id: cardId,
                        font_family: fontFamily.value,
                        font_size: fontSize.value,
                        line_spacing: lineSpacing.value,
                        include_website: document.getElementById('include_website').checked ? '1' : '0',
                        include_name: document.getElementById('include_name').checked ? '1' : '0',
                        include_title: document.getElementById('include_title').checked ? '1' : '0',
                        include_phone: document.getElementById('include_phone').checked ? '1' : '0',
                        include_email: document.getElementById('include_email').checked ? '1' : '0',
                        include_address: document.getElementById('include_address').checked ? '1' : '0'
                    });
                    
                    const newSrc = `/user/cards/preview-name-tag.php?${params.toString()}&t=${Date.now()}`;
                    log('New preview URL: ' + newSrc);
                    
                    document.getElementById('preview-image').src = newSrc;
                    log('Preview image src updated');
                } else {
                    log('ERROR: Some form elements not found');
                }
            } catch (error) {
                log('ERROR: ' + error.message);
            }
        }
        
        function testDownload() {
            log('Testing PDF download...');
            
            try {
                const params = new URLSearchParams({
                    card_id: cardId,
                    font_family: document.getElementById('font_family').value,
                    font_size: document.getElementById('font_size').value,
                    line_spacing: document.getElementById('line_spacing').value,
                    include_website: document.getElementById('include_website').checked ? '1' : '0',
                    include_name: document.getElementById('include_name').checked ? '1' : '0',
                    include_title: document.getElementById('include_title').checked ? '1' : '0',
                    include_phone: document.getElementById('include_phone').checked ? '1' : '0',
                    include_email: document.getElementById('include_email').checked ? '1' : '0',
                    include_address: document.getElementById('include_address').checked ? '1' : '0'
                });
                
                const downloadUrl = `/user/cards/download-name-tags-html.php?${params.toString()}`;
                log('Download URL: ' + downloadUrl);
                
                // Don't actually download, just log the URL
                log('Would download PDF with URL: ' + downloadUrl);
            } catch (error) {
                log('ERROR: ' + error.message);
            }
        }
        
        // Test on page load
        window.addEventListener('load', function() {
            log('Page loaded, testing form elements...');
            testPreview();
        });
    </script>
</body>
</html>
