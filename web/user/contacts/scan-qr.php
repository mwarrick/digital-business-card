<?php
/**
 * QR Code Scanner for Contact Import
 * Allows users to scan vCard QR codes and import contact data
 */

require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';

UserAuth::requireAuth();

$user = UserAuth::getUser();
$db = Database::getInstance();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan QR Code - ShareMyCard</title>
    <link rel="stylesheet" href="/user/includes/user-style.css">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <style>
        .scanner-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .scanner-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .scanner-header h1 {
            margin: 0 0 10px 0;
            font-size: 2.2em;
            font-weight: 600;
        }
        
        .scanner-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 1.1em;
        }
        
        .scanner-content {
            padding: 30px;
        }
        
        .scanner-section {
            margin-bottom: 30px;
        }
        
        .scanner-section h3 {
            color: #333;
            margin: 0 0 20px 0;
            font-size: 1.3em;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
        
        .camera-container {
            position: relative;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        #qr-reader {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            position: relative;
            border-radius: 8px;
            overflow: hidden;
        }
        
        #qr-reader video {
            width: 100%;
            height: auto;
            border-radius: 8px;
        }
        
        .camera-preview {
            position: relative;
            background: #000;
            border-radius: 8px;
            overflow: hidden;
            margin: 0 auto;
            max-width: 500px;
        }
        
        .scanning-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 600;
            border-radius: 8px;
            flex-direction: column;
            gap: 10px;
        }
        
        .scanning-overlay.hidden {
            display: none;
        }
        
        .scanning-frame {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 200px;
            height: 200px;
            border: 2px solid #00ff00;
            border-radius: 8px;
            background: transparent;
            box-shadow: 0 0 0 9999px rgba(0,0,0,0.5);
        }
        
        .scanning-frame::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            border: 2px solid #00ff00;
            border-radius: 8px;
            animation: scanning-pulse 2s infinite;
        }
        
        @keyframes scanning-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        
        .camera-controls {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #17a2b8;
            color: white;
        }
        
        .btn-success:hover {
            background: #138496;
            transform: translateY(-2px);
        }
        
        .camera-selector {
            margin-bottom: 20px;
        }
        
        .camera-selector select {
            padding: 8px 12px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 14px;
            background: white;
        }
        
        .scanning-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 600;
            border-radius: 8px;
        }
        
        .scanning-overlay.hidden {
            display: none;
        }
        
        .form-container {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
        }
        
        .status-message {
            margin: 20px 0;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-weight: 500;
        }
        
        .status-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .status-message.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .hidden {
            display: none;
        }
        
        .vcard-preview {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
            max-height: 200px;
            overflow-y: auto;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .scanner-content {
                padding: 20px;
            }
            
            .camera-controls {
                flex-direction: column;
                align-items: center;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navigation.php'; ?>
    
    <div class="container">
        <div class="scanner-container">
            <div class="scanner-header">
                <h1>📷 Scan QR Code</h1>
                <p>Scan a vCard QR code to import contact information</p>
            </div>
            
            <div class="scanner-content">
                <!-- Camera Section -->
                <div class="scanner-section">
                    <h3>Camera Scanner</h3>
                    <div id="ios-notice" class="status-message info hidden" style="margin-bottom: 20px;">
                        <strong>iOS Users:</strong> For best camera performance, please use Safari instead of Chrome. If you experience issues, try the "Retry Camera" button below.
                    </div>
                    <div class="camera-container">
                        <div class="camera-selector">
                            <label for="camera-select">Select Camera:</label>
                            <select id="camera-select">
                                <option value="">Loading cameras...</option>
                            </select>
                        </div>
                        
                        <div class="camera-preview">
                            <div id="qr-reader"></div>
                            <div id="scanning-overlay" class="scanning-overlay">
                                <div>
                                    <div>📷</div>
                                    <div>Point camera at QR code</div>
                                </div>
                            </div>
                            <div id="scanning-frame" class="scanning-frame hidden"></div>
                        </div>
                        
                        <div class="camera-controls">
                            <button id="preview-camera" class="btn btn-secondary">
                                👁️ Preview Camera
                            </button>
                            <button id="start-scan" class="btn btn-primary">
                                ▶️ Start Scanning
                            </button>
                            <button id="stop-scan" class="btn btn-danger hidden">
                                ⏹️ Stop Scanning
                            </button>
                            <button id="switch-camera" class="btn btn-secondary hidden">
                                🔄 Switch Camera
                            </button>
                        </div>
                        
                        <!-- iOS Fallback - Manual QR Input -->
                        <div id="ios-fallback" class="hidden" style="margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #dee2e6;">
                            <h4 style="margin: 0 0 15px 0; color: #333;">iOS QR Detection Alternative</h4>
                            <p style="margin: 0 0 15px 0; color: #666; font-size: 14px;">
                                If QR scanning isn't working, you can manually enter the QR code data below:
                            </p>
                            <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 15px;">
                                <input type="text" id="manual-qr-input" placeholder="Paste vCard data here..." 
                                       style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: monospace; font-size: 12px;">
                                <button id="process-manual-qr" class="btn btn-primary" style="white-space: nowrap;">
                                    Process QR Data
                                </button>
                            </div>
                            <div style="text-align: center;">
                                <button id="load-test-qr" class="btn btn-secondary" style="font-size: 12px;">
                                    Load Test vCard
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Status Messages -->
                <div id="status-message" class="status-message hidden"></div>
                
                <!-- vCard Preview -->
                <div id="vcard-preview" class="vcard-preview hidden"></div>
                
                <!-- Contact Form -->
                <div id="contact-form-container" class="form-container hidden">
                    <h3>Review & Edit Contact Information</h3>
                    <form id="contact-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name *</label>
                                <input type="text" id="first_name" name="first_name" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email_primary">Email Address *</label>
                            <input type="email" id="email_primary" name="email_primary" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="work_phone">Work Phone</label>
                                <input type="tel" id="work_phone" name="work_phone">
                            </div>
                            <div class="form-group">
                                <label for="mobile_phone">Mobile Phone</label>
                                <input type="tel" id="mobile_phone" name="mobile_phone">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="organization_name">Company/Organization</label>
                                <input type="text" id="organization_name" name="organization_name">
                            </div>
                            <div class="form-group">
                                <label for="job_title">Job Title</label>
                                <input type="text" id="job_title" name="job_title">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="street_address">Street Address</label>
                            <input type="text" id="street_address" name="street_address">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" id="city" name="city">
                            </div>
                            <div class="form-group">
                                <label for="state">State/Province</label>
                                <input type="text" id="state" name="state">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="zip_code">ZIP/Postal Code</label>
                                <input type="text" id="zip_code" name="zip_code">
                            </div>
                            <div class="form-group">
                                <label for="country">Country</label>
                                <input type="text" id="country" name="country">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="website_url">Website</label>
                            <input type="url" id="website_url" name="website_url">
                        </div>
                        
                        <div class="form-group">
                            <label for="birthdate">Birthdate</label>
                            <input type="date" id="birthdate" name="birthdate">
                        </div>
                        
                        <div class="form-group">
                            <label for="comments_from_lead">Notes</label>
                            <textarea id="comments_from_lead" name="comments_from_lead" rows="3"></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" id="cancel-import" class="btn btn-secondary">
                                ❌ Cancel
                            </button>
                            <button type="submit" id="save-contact" class="btn btn-primary">
                                💾 Save Contact
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let html5QrcodeScanner = null;
        let isScanning = false;
        let currentCameraId = null;
        let availableCameras = [];
        
        // Initialize the scanner
        document.addEventListener('DOMContentLoaded', function() {
            initializeCameraSelector();
            setupEventListeners();
        });
        
        function initializeCameraSelector() {
            // Check if we're on iOS
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
            const isChrome = /Chrome/.test(navigator.userAgent);
            
            if (isIOS) {
                document.getElementById('ios-notice').classList.remove('hidden');
                if (isChrome) {
                    showStatus('Note: For best results on iOS, please use Safari instead of Chrome for camera access.', 'info');
                }
            }
            
            // Get available cameras
            Html5Qrcode.getCameras().then(cameras => {
                availableCameras = cameras;
                const select = document.getElementById('camera-select');
                select.innerHTML = '<option value="">Select a camera...</option>';
                
                if (cameras.length === 0) {
                    showStatus('No cameras found. Please ensure your device has a camera and permissions are granted.', 'error');
                    return;
                }
                
                cameras.forEach((camera, index) => {
                    const option = document.createElement('option');
                    option.value = camera.id;
                    option.textContent = camera.label || `Camera ${index + 1}`;
                    select.appendChild(option);
                });
                
                // Auto-select first camera on mobile
                if (cameras.length === 1) {
                    select.value = cameras[0].id;
                }
                
            }).catch(err => {
                console.error('Error getting cameras:', err);
                showStatus('Error: Could not access cameras. Please check permissions and try refreshing the page.', 'error');
            });
        }
        
        function setupEventListeners() {
            document.getElementById('preview-camera').addEventListener('click', previewCamera);
            document.getElementById('start-scan').addEventListener('click', startScanning);
            document.getElementById('stop-scan').addEventListener('click', stopScanning);
            document.getElementById('switch-camera').addEventListener('click', switchCamera);
            document.getElementById('cancel-import').addEventListener('click', cancelImport);
            document.getElementById('contact-form').addEventListener('submit', saveContact);
            document.getElementById('camera-select').addEventListener('change', function() {
                if (isScanning) {
                    stopScanning();
                }
            });
            
            // Add iOS-specific retry button
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
            if (isIOS) {
                addIOSRetryButton();
                // Show manual input fallback for iOS
                document.getElementById('ios-fallback').classList.remove('hidden');
            }
            
            // Manual QR processing
            document.getElementById('process-manual-qr').addEventListener('click', processManualQR);
            document.getElementById('load-test-qr').addEventListener('click', loadTestQR);
        }
        
        function addIOSRetryButton() {
            const cameraControls = document.querySelector('.camera-controls');
            const retryBtn = document.createElement('button');
            retryBtn.id = 'ios-retry';
            retryBtn.className = 'btn btn-secondary hidden';
            retryBtn.innerHTML = '🔄 Retry Camera';
            retryBtn.addEventListener('click', retryCamera);
            cameraControls.appendChild(retryBtn);
        }
        
        function retryCamera() {
            showStatus('Retrying camera access...', 'info');
            stopScanning();
            
            // Clear previous scanner
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear();
                html5QrcodeScanner = null;
            }
            
            // Reinitialize after a short delay
            setTimeout(() => {
                initializeCameraSelector();
                document.getElementById('ios-retry').classList.add('hidden');
            }, 1000);
        }
        
        function previewCamera() {
            const cameraId = document.getElementById('camera-select').value;
            if (!cameraId) {
                showStatus('Please select a camera first.', 'error');
                return;
            }
            
            if (isScanning) {
                stopScanning();
                return;
            }
            
            currentCameraId = cameraId;
            
            // Create scanner instance for preview only
            html5QrcodeScanner = new Html5Qrcode("qr-reader");
            
            // iOS-optimized configuration for preview
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
            const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            
            const config = {
                fps: isIOS ? 5 : 10,
                qrbox: isMobile ? { width: 200, height: 200 } : { width: 250, height: 250 },
                aspectRatio: 1.0,
                videoConstraints: isIOS ? {
                    facingMode: cameraId.includes('back') ? 'environment' : 'user',
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                } : undefined
            };
            
            showStatus('Starting camera preview...', 'info');
            
            html5QrcodeScanner.start(
                cameraId,
                config,
                () => {}, // Empty success callback for preview
                () => {}  // Empty failure callback for preview
            ).then(() => {
                isScanning = true;
                updateUI();
                showStatus('Camera preview active. Click "Start Scanning" to begin QR detection.', 'info');
                
                // Show scanning frame but don't hide overlay completely
                document.getElementById('scanning-frame').classList.remove('hidden');
                document.getElementById('scanning-overlay').style.opacity = '0.3';
            }).catch(err => {
                console.error('Error starting camera preview:', err);
                showStatus('Error starting camera preview: ' + err.message, 'error');
            });
        }
        
        function startScanning() {
            const cameraId = document.getElementById('camera-select').value;
            if (!cameraId) {
                showStatus('Please select a camera first.', 'error');
                return;
            }
            
            // If already scanning (preview mode), just enable QR detection
            if (isScanning && html5QrcodeScanner) {
                // Restart with QR detection enabled
                stopScanning();
                setTimeout(() => {
                    startScanningWithDetection(cameraId);
                }, 500);
                return;
            }
            
            startScanningWithDetection(cameraId);
        }
        
        function startScanningWithDetection(cameraId) {
            currentCameraId = cameraId;
            
            // Create scanner instance
            html5QrcodeScanner = new Html5Qrcode("qr-reader");
            
            // iOS-optimized configuration
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
            const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            
            const config = {
                fps: isIOS ? 3 : 10, // Even lower FPS for iOS QR detection
                qrbox: isMobile ? { width: 200, height: 200 } : { width: 250, height: 250 },
                aspectRatio: 1.0,
                // iOS-specific settings
                videoConstraints: isIOS ? {
                    facingMode: cameraId.includes('back') ? 'environment' : 'user',
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                } : undefined,
                // Additional iOS compatibility settings
                experimentalFeatures: {
                    useBarCodeDetectorIfSupported: false
                }
            };
            
            // Show loading state
            showStatus('Starting QR detection...', 'info');
            
            html5QrcodeScanner.start(
                cameraId,
                config,
                onScanSuccess,
                onScanFailure
            ).then(() => {
                isScanning = true;
                updateUI();
                showStatus('Scanning for QR codes...', 'info');
                
                // Show scanning frame
                document.getElementById('scanning-frame').classList.remove('hidden');
                
                // Hide the initial overlay
                document.getElementById('scanning-overlay').classList.add('hidden');
                
                // Start manual QR detection for iOS if needed
                if (isIOS) {
                    startManualQRDetection();
                }
            }).catch(err => {
                console.error('Error starting scanner:', err);
                let errorMessage = 'Error starting camera: ' + err.message;
                
                // Provide specific guidance for common iOS issues
                if (isIOS) {
                    if (err.message.includes('Permission denied') || err.message.includes('NotAllowedError')) {
                        errorMessage = 'Camera permission denied. Please allow camera access and try again.';
                    } else if (err.message.includes('NotFoundError')) {
                        errorMessage = 'Camera not found. Please try switching cameras or use Safari instead of Chrome.';
                    } else if (err.message.includes('NotReadableError')) {
                        errorMessage = 'Camera is being used by another app. Please close other camera apps and try again.';
                    }
                    
                    // Show retry button for iOS
                    document.getElementById('ios-retry').classList.remove('hidden');
                }
                
                showStatus(errorMessage, 'error');
            });
        }
        
        function stopScanning() {
            if (html5QrcodeScanner && isScanning) {
                html5QrcodeScanner.stop().then(() => {
                    isScanning = false;
                    updateUI();
                    showStatus('Scanning stopped.', 'info');
                    
                    // Hide scanning frame and show overlay
                    document.getElementById('scanning-frame').classList.add('hidden');
                    document.getElementById('scanning-overlay').classList.remove('hidden');
                }).catch(err => {
                    console.error('Error stopping scanner:', err);
                });
            }
        }
        
        function switchCamera() {
            if (!isScanning) return;
            
            const currentIndex = availableCameras.findIndex(cam => cam.id === currentCameraId);
            const nextIndex = (currentIndex + 1) % availableCameras.length;
            const nextCamera = availableCameras[nextIndex];
            
            document.getElementById('camera-select').value = nextCamera.id;
            stopScanning();
            
            setTimeout(() => {
                startScanning();
            }, 500);
        }
        
        function onScanSuccess(decodedText, decodedResult) {
            console.log('QR Code detected:', decodedText);
            console.log('Decoded result:', decodedResult);
            
            // Stop scanning
            stopScanning();
            
            // Check if it's a vCard
            if (decodedText.startsWith('BEGIN:VCARD')) {
                showStatus('vCard detected! Parsing contact information...', 'success');
                parseVCard(decodedText);
            } else {
                showStatus('QR code detected but it\'s not a vCard format. Please scan a contact QR code.', 'error');
            }
        }
        
        function onScanFailure(error) {
            // Don't show every scan failure as it's noisy
            // console.log('Scan failed:', error);
        }
        
        function startManualQRDetection() {
            // For iOS, we'll try a different approach
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
            if (!isIOS) return;
            
            console.log('Starting manual QR detection for iOS...');
            
            // Try to get the video element and use a different detection method
            setTimeout(() => {
                const videoElement = document.querySelector('#qr-reader video');
                if (videoElement) {
                    console.log('Video element found, attempting manual detection...');
                    // The html5-qrcode library should handle this automatically
                    // but we can add some debugging
                    showStatus('QR detection active. Try positioning the QR code within the green frame.', 'info');
                }
            }, 2000);
        }
        
        function processManualQR() {
            const qrData = document.getElementById('manual-qr-input').value.trim();
            
            if (!qrData) {
                showStatus('Please enter QR code data.', 'error');
                return;
            }
            
            // Check if it's a vCard
            if (qrData.startsWith('BEGIN:VCARD')) {
                showStatus('Processing vCard data...', 'info');
                parseVCard(qrData);
            } else {
                showStatus('Please enter valid vCard data (should start with BEGIN:VCARD).', 'error');
            }
        }
        
        function loadTestQR() {
            const testVCard = `BEGIN:VCARD
VERSION:3.0
FN:John Doe
N:Doe;John;;;
ORG:Test Company
TITLE:Software Engineer
TEL;TYPE=WORK:+1-555-123-4567
TEL;TYPE=CELL:+1-555-987-6543
EMAIL;TYPE=WORK:john.doe@testcompany.com
ADR;TYPE=WORK:;;123 Main St;Anytown;CA;12345;USA
URL:https://testcompany.com
NOTE:This is a test contact for QR scanner debugging
END:VCARD`;
            
            document.getElementById('manual-qr-input').value = testVCard;
            showStatus('Test vCard loaded. Click "Process QR Data" to test.', 'info');
        }
        
        function parseVCard(vcardText) {
            try {
                // Show raw vCard for debugging
                document.getElementById('vcard-preview').textContent = vcardText;
                document.getElementById('vcard-preview').classList.remove('hidden');
                
                // Parse vCard
                const contact = parseVCardData(vcardText);
                
                // Populate form
                populateForm(contact);
                
                // Show form
                document.getElementById('contact-form-container').classList.remove('hidden');
                
                showStatus('Contact information parsed successfully. Review and edit as needed.', 'success');
                
            } catch (error) {
                console.error('Error parsing vCard:', error);
                showStatus('Error parsing vCard: ' + error.message, 'error');
            }
        }
        
        function parseVCardData(vcardText) {
            const lines = vcardText.split(/\r?\n/);
            const contact = {};
            
            for (let line of lines) {
                line = line.trim();
                if (!line || line.startsWith('BEGIN:VCARD') || line.startsWith('END:VCARD')) continue;
                
                // Handle line folding
                if (line.startsWith(' ') || line.startsWith('\t')) {
                    // This is a continuation line
                    const lastKey = Object.keys(contact).pop();
                    if (lastKey) {
                        contact[lastKey] += line.substring(1);
                    }
                    continue;
                }
                
                const colonIndex = line.indexOf(':');
                if (colonIndex === -1) continue;
                
                const field = line.substring(0, colonIndex);
                const value = line.substring(colonIndex + 1);
                
                // Parse different vCard fields
                if (field.startsWith('FN:')) {
                    contact.fullName = value;
                } else if (field.startsWith('N:')) {
                    const nameParts = value.split(';');
                    contact.lastName = nameParts[0] || '';
                    contact.firstName = nameParts[1] || '';
                    contact.middleName = nameParts[2] || '';
                    contact.prefix = nameParts[3] || '';
                    contact.suffix = nameParts[4] || '';
                } else if (field.startsWith('TEL')) {
                    if (!contact.phones) contact.phones = [];
                    contact.phones.push({
                        number: value,
                        type: extractType(field)
                    });
                } else if (field.startsWith('EMAIL')) {
                    if (!contact.emails) contact.emails = [];
                    contact.emails.push({
                        email: value,
                        type: extractType(field)
                    });
                } else if (field.startsWith('ORG:')) {
                    contact.organization = value;
                } else if (field.startsWith('TITLE:')) {
                    contact.title = value;
                } else if (field.startsWith('ADR')) {
                    if (!contact.addresses) contact.addresses = [];
                    const addressParts = value.split(';');
                    contact.addresses.push({
                        type: extractType(field),
                        street: addressParts[2] || '',
                        city: addressParts[3] || '',
                        state: addressParts[4] || '',
                        zip: addressParts[5] || '',
                        country: addressParts[6] || ''
                    });
                } else if (field.startsWith('URL:')) {
                    contact.website = value;
                } else if (field.startsWith('BDAY:')) {
                    contact.birthday = value;
                } else if (field.startsWith('NOTE:')) {
                    contact.note = value;
                }
            }
            
            return contact;
        }
        
        function extractType(field) {
            const typeMatch = field.match(/TYPE=([^:;]+)/);
            return typeMatch ? typeMatch[1] : '';
        }
        
        function populateForm(contact) {
            // Set names
            if (contact.firstName) {
                document.getElementById('first_name').value = contact.firstName;
            } else if (contact.fullName) {
                const nameParts = contact.fullName.split(' ');
                document.getElementById('first_name').value = nameParts[0] || '';
                if (nameParts.length > 1) {
                    document.getElementById('last_name').value = nameParts.slice(1).join(' ');
                }
            }
            
            if (contact.lastName) {
                document.getElementById('last_name').value = contact.lastName;
            }
            
            // Set email (use first email found)
            if (contact.emails && contact.emails.length > 0) {
                document.getElementById('email_primary').value = contact.emails[0].email;
            }
            
            // Set phones
            if (contact.phones && contact.phones.length > 0) {
                const workPhone = contact.phones.find(p => p.type.includes('WORK'));
                const mobilePhone = contact.phones.find(p => p.type.includes('CELL') || p.type.includes('MOBILE'));
                
                if (workPhone) {
                    document.getElementById('work_phone').value = workPhone.number;
                }
                if (mobilePhone) {
                    document.getElementById('mobile_phone').value = mobilePhone.number;
                }
                
                // If no specific type, use first phone as mobile
                if (!workPhone && !mobilePhone && contact.phones[0]) {
                    document.getElementById('mobile_phone').value = contact.phones[0].number;
                }
            }
            
            // Set organization and title
            if (contact.organization) {
                document.getElementById('organization_name').value = contact.organization;
            }
            if (contact.title) {
                document.getElementById('job_title').value = contact.title;
            }
            
            // Set address (use first address found)
            if (contact.addresses && contact.addresses.length > 0) {
                const addr = contact.addresses[0];
                document.getElementById('street_address').value = addr.street;
                document.getElementById('city').value = addr.city;
                document.getElementById('state').value = addr.state;
                document.getElementById('zip_code').value = addr.zip;
                document.getElementById('country').value = addr.country;
            }
            
            // Set website
            if (contact.website) {
                document.getElementById('website_url').value = contact.website;
            }
            
            // Set birthday
            if (contact.birthday) {
                // Convert YYYYMMDD to YYYY-MM-DD
                const birthday = contact.birthday.replace(/(\d{4})(\d{2})(\d{2})/, '$1-$2-$3');
                document.getElementById('birthdate').value = birthday;
            }
            
            // Set notes
            if (contact.note) {
                document.getElementById('comments_from_lead').value = contact.note;
            }
        }
        
        function cancelImport() {
            // Hide form and preview
            document.getElementById('contact-form-container').classList.add('hidden');
            document.getElementById('vcard-preview').classList.add('hidden');
            
            // Clear form
            document.getElementById('contact-form').reset();
            
            // Clear status
            document.getElementById('status-message').classList.add('hidden');
        }
        
        function saveContact(e) {
            e.preventDefault();
            
            const form = document.getElementById('contact-form');
            const formData = new FormData(form);
            
            // Add metadata
            formData.append('source', 'qr_scan');
            formData.append('scan_timestamp', new Date().toISOString());
            formData.append('user_agent', navigator.userAgent);
            formData.append('device_type', /Mobile|Android|iPhone|iPad/.test(navigator.userAgent) ? 'mobile' : 'desktop');
            formData.append('camera_used', currentCameraId || 'unknown');
            
            // Show loading state
            const saveBtn = document.getElementById('save-contact');
            const originalText = saveBtn.textContent;
            saveBtn.textContent = '💾 Saving...';
            saveBtn.disabled = true;
            
            // Submit to API
            fetch('/user/api/create-contact-from-qr.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showStatus(`Contact saved successfully: ${data.contact_name}`, 'success');
                    
                    // Hide form after success
                    setTimeout(() => {
                        cancelImport();
                    }, 2000);
                } else {
                    showStatus('Error: ' + (data.message || 'Failed to save contact'), 'error');
                }
            })
            .catch(error => {
                console.error('Error saving contact:', error);
                showStatus('Error saving contact. Please try again.', 'error');
            })
            .finally(() => {
                saveBtn.textContent = originalText;
                saveBtn.disabled = false;
            });
        }
        
        function updateUI() {
            const previewBtn = document.getElementById('preview-camera');
            const startBtn = document.getElementById('start-scan');
            const stopBtn = document.getElementById('stop-scan');
            const switchBtn = document.getElementById('switch-camera');
            const overlay = document.getElementById('scanning-overlay');
            const frame = document.getElementById('scanning-frame');
            
            if (isScanning) {
                previewBtn.textContent = '⏹️ Stop Preview';
                previewBtn.classList.remove('hidden');
                startBtn.classList.add('hidden');
                stopBtn.classList.remove('hidden');
                switchBtn.classList.remove('hidden');
                overlay.classList.add('hidden');
                frame.classList.remove('hidden');
            } else {
                previewBtn.textContent = '👁️ Preview Camera';
                previewBtn.classList.remove('hidden');
                startBtn.classList.remove('hidden');
                stopBtn.classList.add('hidden');
                switchBtn.classList.add('hidden');
                overlay.classList.remove('hidden');
                frame.classList.add('hidden');
            }
        }
        
        function showStatus(message, type) {
            const statusDiv = document.getElementById('status-message');
            statusDiv.textContent = message;
            statusDiv.className = `status-message ${type}`;
            statusDiv.classList.remove('hidden');
            
            // Auto-hide info messages after 5 seconds
            if (type === 'info') {
                setTimeout(() => {
                    statusDiv.classList.add('hidden');
                }, 5000);
            }
        }
    </script>
</body>
</html>
