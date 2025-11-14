package com.sharemycard.android.presentation.screens.contacts

import android.Manifest
import androidx.camera.core.*
import androidx.camera.lifecycle.ProcessCameraProvider
import androidx.camera.view.PreviewView
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalLifecycleOwner
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.core.content.ContextCompat
import com.google.accompanist.permissions.ExperimentalPermissionsApi
import com.google.accompanist.permissions.PermissionStatus
import com.google.accompanist.permissions.rememberPermissionState
import com.google.mlkit.vision.barcode.BarcodeScanning
import com.google.mlkit.vision.barcode.common.Barcode
import com.google.mlkit.vision.common.InputImage
import java.util.concurrent.Executors

@OptIn(ExperimentalMaterial3Api::class, ExperimentalPermissionsApi::class)
@Composable
fun QRScannerScreen(
    onScanResult: (String) -> Unit,
    onNavigateBack: () -> Unit
) {
    val context = LocalContext.current
    val lifecycleOwner = LocalLifecycleOwner.current
    
    // Camera permission
    val cameraPermissionState = rememberPermissionState(
        permission = Manifest.permission.CAMERA
    )
    
    // QR code scanning state
    var scannedCode by remember { mutableStateOf<String?>(null) }
    var isScanning by remember { mutableStateOf(true) }
    
    // Handle scan result - only process once
    LaunchedEffect(scannedCode) {
        scannedCode?.let { code ->
            if (isScanning) {
                android.util.Log.d("QRScanner", "Processing scanned code: $code")
                isScanning = false
                try {
                    onScanResult(code)
                    android.util.Log.d("QRScanner", "Successfully called onScanResult")
                } catch (e: Exception) {
                    android.util.Log.e("QRScanner", "Error in onScanResult callback", e)
                    e.printStackTrace()
                }
            }
        }
    }
    
    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        Icon(
                            imageVector = Icons.Default.Layers,
                            contentDescription = "ShareMyCard Logo",
                            modifier = Modifier.size(24.dp),
                            tint = MaterialTheme.colorScheme.primary
                        )
                        Text("Scan QR Code")
                    }
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                }
            )
        }
    ) { paddingValues ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            when {
                cameraPermissionState.status !is PermissionStatus.Granted -> {
                    // Permission not granted
                    PermissionRequestScreen(
                        onRequestPermission = { cameraPermissionState.launchPermissionRequest() },
                        modifier = Modifier.fillMaxSize()
                    )
                }
                isScanning -> {
                    // Camera preview with QR scanning
                    QRScannerPreview(
                        onQRCodeScanned = { code ->
                            if (isScanning) {
                                scannedCode = code
                            }
                        },
                        modifier = Modifier.fillMaxSize()
                    )
                }
            }
        }
    }
}

@Composable
fun PermissionRequestScreen(
    onRequestPermission: () -> Unit,
    modifier: Modifier = Modifier
) {
    Column(
        modifier = modifier
            .fillMaxSize()
            .padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Icon(
            imageVector = Icons.Default.CameraAlt,
            contentDescription = "Camera",
            modifier = Modifier.size(64.dp),
            tint = MaterialTheme.colorScheme.primary
        )
        
        Spacer(modifier = Modifier.height(24.dp))
        
        Text(
            text = "Camera Permission Required",
            style = MaterialTheme.typography.titleLarge,
            fontWeight = FontWeight.Bold
        )
        
        Spacer(modifier = Modifier.height(16.dp))
        
        Text(
            text = "We need camera access to scan QR codes and add contacts.",
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
        
        Spacer(modifier = Modifier.height(32.dp))
        
        Button(onClick = onRequestPermission) {
            Text("Grant Permission")
        }
    }
}

@Composable
fun QRScannerPreview(
    onQRCodeScanned: (String) -> Unit,
    modifier: Modifier = Modifier
) {
    val context = LocalContext.current
    val lifecycleOwner = LocalLifecycleOwner.current
    
    val previewView = remember { PreviewView(context) }
    var cameraProvider by remember { mutableStateOf<ProcessCameraProvider?>(null) }
    
    // Initialize camera provider
    LaunchedEffect(Unit) {
        val providerFuture = ProcessCameraProvider.getInstance(context)
        providerFuture.addListener({
            cameraProvider = providerFuture.get()
        }, ContextCompat.getMainExecutor(context))
    }
    
    // Set up camera when provider is ready
    LaunchedEffect(cameraProvider) {
        val provider = cameraProvider ?: return@LaunchedEffect
        
        // Image analysis for QR code scanning
        val analysis = ImageAnalysis.Builder()
            .setBackpressureStrategy(ImageAnalysis.STRATEGY_KEEP_ONLY_LATEST)
            .build()
        
        val scanner = BarcodeScanning.getClient()
        
        // Use a shared flag to prevent multiple scans
        val scanLock = java.util.concurrent.atomic.AtomicBoolean(false)
        
        analysis.setAnalyzer(
            Executors.newSingleThreadExecutor()
        ) { imageProxy ->
            // Only process if we haven't scanned yet
            if (!scanLock.get()) {
                processImageProxy(imageProxy, scanner) { code ->
                    // Mark as scanned and stop processing
                    if (scanLock.compareAndSet(false, true)) {
                        onQRCodeScanned(code)
                    }
                }
            } else {
                // Already scanned, just close the image
                imageProxy.close()
            }
        }
        
        // Preview
        val preview = Preview.Builder().build()
        preview.setSurfaceProvider(previewView.surfaceProvider)
        
        // Select back camera
        val cameraSelector = CameraSelector.DEFAULT_BACK_CAMERA
        
        try {
            provider.unbindAll()
            provider.bindToLifecycle(
                lifecycleOwner,
                cameraSelector,
                preview,
                analysis
            )
        } catch (e: Exception) {
            android.util.Log.e("QRScanner", "Error binding camera", e)
        }
    }
    
    Box(modifier = modifier) {
        // Camera preview
        AndroidView(
            factory = { previewView },
            modifier = Modifier.fillMaxSize()
        )
        
        // Overlay with scanning frame
        Box(
            modifier = Modifier
                .fillMaxSize()
                .background(Color.Black.copy(alpha = 0.5f)),
            contentAlignment = Alignment.Center
        ) {
            // Scanning frame
            Column(
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                // Frame with corner indicators
                Box(
                    modifier = Modifier.size(250.dp)
                ) {
                    // Transparent center
                    Box(
                        modifier = Modifier
                            .fillMaxSize()
                            .background(Color.Transparent)
                    )
                    
                    // Corner indicators using Canvas or simple boxes
                    // Top-left corner
                    Box(
                        modifier = Modifier
                            .align(Alignment.TopStart)
                            .size(30.dp)
                            .background(Color.Transparent)
                    ) {
                        Box(
                            modifier = Modifier
                                .width(30.dp)
                                .height(3.dp)
                                .background(Color.White)
                        )
                        Box(
                            modifier = Modifier
                                .width(3.dp)
                                .height(30.dp)
                                .background(Color.White)
                        )
                    }
                    
                    // Top-right corner
                    Box(
                        modifier = Modifier
                            .align(Alignment.TopEnd)
                            .size(30.dp)
                            .background(Color.Transparent)
                    ) {
                        Box(
                            modifier = Modifier
                                .align(Alignment.TopEnd)
                                .width(30.dp)
                                .height(3.dp)
                                .background(Color.White)
                        )
                        Box(
                            modifier = Modifier
                                .align(Alignment.TopEnd)
                                .width(3.dp)
                                .height(30.dp)
                                .background(Color.White)
                        )
                    }
                    
                    // Bottom-left corner
                    Box(
                        modifier = Modifier
                            .align(Alignment.BottomStart)
                            .size(30.dp)
                            .background(Color.Transparent)
                    ) {
                        Box(
                            modifier = Modifier
                                .align(Alignment.BottomStart)
                                .width(30.dp)
                                .height(3.dp)
                                .background(Color.White)
                        )
                        Box(
                            modifier = Modifier
                                .align(Alignment.BottomStart)
                                .width(3.dp)
                                .height(30.dp)
                                .background(Color.White)
                        )
                    }
                    
                    // Bottom-right corner
                    Box(
                        modifier = Modifier
                            .align(Alignment.BottomEnd)
                            .size(30.dp)
                            .background(Color.Transparent)
                    ) {
                        Box(
                            modifier = Modifier
                                .align(Alignment.BottomEnd)
                                .width(30.dp)
                                .height(3.dp)
                                .background(Color.White)
                        )
                        Box(
                            modifier = Modifier
                                .align(Alignment.BottomEnd)
                                .width(3.dp)
                                .height(30.dp)
                                .background(Color.White)
                        )
                    }
                }
                
                // Instructions
                Text(
                    text = "Position QR code within the frame",
                    style = MaterialTheme.typography.bodyLarge,
                    color = Color.White,
                    fontWeight = FontWeight.Medium
                )
            }
        }
    }
}

private fun processImageProxy(
    imageProxy: ImageProxy,
    scanner: com.google.mlkit.vision.barcode.BarcodeScanner,
    onQRCodeFound: (String) -> Unit
) {
    val mediaImage = imageProxy.image
    if (mediaImage != null) {
        val image = InputImage.fromMediaImage(
            mediaImage,
            imageProxy.imageInfo.rotationDegrees
        )
        
        scanner.process(image)
            .addOnSuccessListener { barcodes ->
                // Post to main thread to safely update Compose state
                android.os.Handler(android.os.Looper.getMainLooper()).post {
                    try {
                        for (barcode in barcodes) {
                            when (barcode.valueType) {
                                Barcode.TYPE_URL, Barcode.TYPE_TEXT -> {
                                    barcode.rawValue?.let { code ->
                                        android.util.Log.d("QRScanner", "QR code detected: $code")
                                        onQRCodeFound(code)
                                        return@post // Only process first valid QR code
                                    }
                                }
                            }
                        }
                    } catch (e: Exception) {
                        android.util.Log.e("QRScanner", "Error processing QR code", e)
                    }
                }
            }
            .addOnFailureListener { e ->
                android.util.Log.e("QRScanner", "Error scanning QR code", e)
            }
            .addOnCompleteListener {
                imageProxy.close()
            }
    } else {
        imageProxy.close()
    }
}

