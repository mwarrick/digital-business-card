<?php
// Temporary PHP info page to check server settings
// This will be deleted after checking upload limits

echo "<h2>PHP Upload Settings</h2>";
echo "<p><strong>upload_max_filesize:</strong> " . ini_get('upload_max_filesize') . "</p>";
echo "<p><strong>post_max_size:</strong> " . ini_get('post_max_size') . "</p>";
echo "<p><strong>max_execution_time:</strong> " . ini_get('max_execution_time') . "</p>";
echo "<p><strong>max_input_time:</strong> " . ini_get('max_input_time') . "</p>";
echo "<p><strong>memory_limit:</strong> " . ini_get('memory_limit') . "</p>";
echo "<p><strong>max_file_uploads:</strong> " . ini_get('max_file_uploads') . "</p>";

echo "<h2>File Upload Test</h2>";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    $file = $_FILES['test_file'];
    echo "<p><strong>File name:</strong> " . $file['name'] . "</p>";
    echo "<p><strong>File size:</strong> " . number_format($file['size']) . " bytes (" . number_format($file['size'] / 1024 / 1024, 2) . " MB)</p>";
    echo "<p><strong>File type:</strong> " . $file['type'] . "</p>";
    echo "<p><strong>Upload error:</strong> " . $file['error'] . "</p>";
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        echo "<p style='color: green;'><strong>Upload successful!</strong></p>";
    } else {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        echo "<p style='color: red;'><strong>Upload failed:</strong> " . ($error_messages[$file['error']] ?? 'Unknown error') . "</p>";
    }
}
?>

<form method="post" enctype="multipart/form-data">
    <p>Test file upload (try a file larger than 5MB):</p>
    <input type="file" name="test_file" required>
    <button type="submit">Upload Test</button>
</form>
