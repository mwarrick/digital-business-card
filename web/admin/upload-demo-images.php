<?php
/**
 * Demo Images Upload Helper
 * This page helps you upload custom demo images
 */

require_once __DIR__ . '/../config/database.php';

$message = '';
$error = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['demo_images'])) {
    $mediaDir = __DIR__ . '/../storage/media';
    
    // Ensure media directory exists
    if (!is_dir($mediaDir)) {
        mkdir($mediaDir, 0755, true);
    }
    
    $uploadedFiles = [];
    $errors = [];
    
    foreach ($_FILES['demo_images']['name'] as $key => $filename) {
        if ($_FILES['demo_images']['error'][$key] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['demo_images']['tmp_name'][$key];
            $targetPath = $mediaDir . '/' . $filename;
            
            // Validate filename (must be a demo image)
            $validDemoImages = [
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
            
            if (in_array($filename, $validDemoImages)) {
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $uploadedFiles[] = $filename;
                } else {
                    $errors[] = "Failed to upload $filename";
                }
            } else {
                $errors[] = "Invalid filename: $filename (must be one of the demo image names)";
            }
        }
    }
    
    if (!empty($uploadedFiles)) {
        $message = "Successfully uploaded: " . implode(', ', $uploadedFiles);
    }
    
    if (!empty($errors)) {
        $error = "Errors: " . implode(', ', $errors);
    }
}

// Check current demo images
$mediaDir = __DIR__ . '/../storage/media';
$demoImages = [
    'demo-alex-profile.jpg' => 'Alex Chen Profile Photo',
    'demo-techcorp-logo.jpg' => 'TechCorp Company Logo',
    'demo-techcorp-cover.jpg' => 'TechCorp Cover Graphic',
    'demo-sarah-profile.jpg' => 'Sarah Martinez Profile Photo',
    'demo-designstudio-logo.jpg' => 'Design Studio Company Logo',
    'demo-designstudio-cover.jpg' => 'Design Studio Cover Graphic',
    'demo-michael-profile.jpg' => 'Michael Thompson Profile Photo',
    'demo-innovation-logo.jpg' => 'Innovation Ventures Company Logo',
    'demo-innovation-cover.jpg' => 'Innovation Ventures Cover Graphic'
];

$existingImages = [];
foreach ($demoImages as $filename => $description) {
    $filePath = $mediaDir . '/' . $filename;
    $existingImages[$filename] = [
        'description' => $description,
        'exists' => file_exists($filePath),
        'size' => file_exists($filePath) ? filesize($filePath) : 0,
        'modified' => file_exists($filePath) ? date('Y-m-d H:i:s', filemtime($filePath)) : null
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Demo Images - ShareMyCard Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { border-bottom: 2px solid #667eea; padding-bottom: 10px; margin-bottom: 20px; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .upload-section { margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 4px; }
        .image-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin: 20px 0; }
        .image-card { border: 1px solid #ddd; padding: 15px; border-radius: 4px; background: white; }
        .image-card.exists { border-color: #28a745; background: #f8fff9; }
        .image-card.missing { border-color: #dc3545; background: #fff8f8; }
        .status { font-weight: bold; padding: 4px 8px; border-radius: 3px; font-size: 12px; }
        .status.exists { background: #28a745; color: white; }
        .status.missing { background: #dc3545; color: white; }
        .specs { font-size: 12px; color: #666; margin-top: 5px; }
        .btn { background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #5a6fd8; }
        .file-input { margin: 10px 0; }
        .instructions { background: #e3f2fd; padding: 15px; border-radius: 4px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📸 Upload Demo Images</h1>
            <p>Upload custom images for the demo business cards. These will be preserved during demo account regeneration.</p>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="instructions">
            <h3>📋 Image Specifications</h3>
            <ul>
                <li><strong>Profile Photos:</strong> 400x400px (square, 1:1 ratio)</li>
                <li><strong>Company Logos:</strong> 400x400px (square, 1:1 ratio)</li>
                <li><strong>Cover Graphics:</strong> 1200x400px (3:1 ratio, landscape)</li>
                <li><strong>Format:</strong> JPG or PNG</li>
                <li><strong>File Names:</strong> Must match exactly (see list below)</li>
            </ul>
        </div>

        <div class="upload-section">
            <h3>📤 Upload Images</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="file-input">
                    <input type="file" name="demo_images[]" multiple accept="image/*" required>
                    <p><small>Select multiple images at once. File names must match exactly.</small></p>
                </div>
                <button type="submit" class="btn">Upload Images</button>
            </form>
        </div>

        <h3>📊 Current Demo Images Status</h3>
        <div class="image-grid">
            <?php foreach ($existingImages as $filename => $info): ?>
                <div class="image-card <?php echo $info['exists'] ? 'exists' : 'missing'; ?>">
                    <h4><?php echo htmlspecialchars($info['description']); ?></h4>
                    <p><strong>File:</strong> <?php echo htmlspecialchars($filename); ?></p>
                    <span class="status <?php echo $info['exists'] ? 'exists' : 'missing'; ?>">
                        <?php echo $info['exists'] ? '✅ EXISTS' : '❌ MISSING'; ?>
                    </span>
                    <?php if ($info['exists']): ?>
                        <div class="specs">
                            <strong>Size:</strong> <?php echo number_format($info['size']); ?> bytes<br>
                            <strong>Modified:</strong> <?php echo $info['modified']; ?>
                        </div>
                    <?php else: ?>
                        <div class="specs">
                            <strong>Status:</strong> Missing - no placeholder will be shown
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="instructions">
            <h3>ℹ️ How It Works</h3>
            <ul>
                <li><strong>Custom Images:</strong> Upload images with the exact filenames above to use them for demo cards</li>
                <li><strong>Missing Images:</strong> If images are missing, demo cards will not display profile photos, logos, or covers</li>
                <li><strong>Preservation:</strong> Your custom images are preserved during demo account regeneration</li>
                <li><strong>Demo Reset:</strong> Demo cards are reset on each login, and images are copied from /storage/media/demo/</li>
            </ul>
        </div>

        <p><a href="/admin/dashboard.php" class="btn">← Back to Admin Dashboard</a></p>
    </div>
</body>
</html>
