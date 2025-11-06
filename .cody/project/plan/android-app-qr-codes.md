# Android App: QR Code Features Module

## Overview

This module handles QR code generation, scanning, parsing, and URL handling for business cards and contacts.

## Features

### ✅ Implemented in iOS

1. **QR Code Generation**
   - Generate QR codes for business cards
   - Server URL-based (if serverCardId exists)
   - Local vCard-based (fallback)
   - Share QR codes

2. **QR Code Scanning**
   - Camera-based scanning
   - Image upload scanning
   - vCard parsing
   - URL handling (when QR contains URL without vCard)
   - Server-side QR image processing
   - Contact form pre-filling

3. **vCard Parsing**
   - Parse vCard format
   - Extract contact information
   - Handle multiple formats

4. **URL Handling**
   - Fetch content from URLs
   - Check for embedded vCard data
   - Pre-fill contact form with URL if no vCard found

## QR Code Generation

### QRCodeGenerator

```kotlin
object QRCodeGenerator {
    fun generateQRCode(card: BusinessCard): Bitmap? {
        return try {
            val content = if (card.serverCardId != null) {
                "https://sharemycard.app/vcard.php?id=${card.serverCardId}&src=qr-app"
            } else {
                createVCardString(card)
            }
            
            val writer = QRCodeWriter()
            val bitMatrix = writer.encode(
                content,
                BarcodeFormat.QR_CODE,
                512,
                512
            )
            
            val width = bitMatrix.width
            val height = bitMatrix.height
            val pixels = IntArray(width * height)
            
            for (y in 0 until height) {
                for (x in 0 until width) {
                    pixels[y * width + x] = if (bitMatrix[x, y]) 
                        Color.BLACK else Color.WHITE
                }
            }
            
            Bitmap.createBitmap(width, height, Bitmap.Config.RGB_565).apply {
                setPixels(pixels, 0, width, 0, 0, width, height)
            }
        } catch (e: Exception) {
            null
        }
    }
    
    private fun createVCardString(card: BusinessCard): String {
        return buildString {
            appendLine("BEGIN:VCARD")
            appendLine("VERSION:3.0")
            appendLine("FN:${card.fullName}")
            appendLine("N:${card.lastName};${card.firstName};;;")
            appendLine("TEL:${card.phoneNumber}")
            
            card.companyName?.let { appendLine("ORG:$it") }
            card.jobTitle?.let { appendLine("TITLE:$it") }
            
            card.additionalEmails.forEach { email ->
                appendLine("EMAIL;TYPE=${email.type.name}:${email.email}")
            }
            
            card.additionalPhones.forEach { phone ->
                appendLine("TEL;TYPE=${phone.type.name}:${phone.phoneNumber}")
            }
            
            card.websiteLinks.forEach { website ->
                appendLine("URL:${website.url}")
            }
            
            card.address?.let { addr ->
                appendLine("ADR:;;${addr.street ?: ""};${addr.city ?: ""};${addr.state ?: ""};${addr.zipCode ?: ""};${addr.country ?: ""}")
            }
            
            card.bio?.let { appendLine("NOTE:$it") }
            
            appendLine("END:VCARD")
        }
    }
}
```

## QR Code Scanning

### Camera Scanner

```kotlin
@Composable
fun QRCodeScannerScreen(
    onQRCodeScanned: (String) -> Unit,
    onDismiss: () -> Unit
) {
    val context = LocalContext.current
    val lifecycleOwner = LocalLifecycleOwner.current
    
    val cameraPermissionState = rememberPermissionState(
        android.Manifest.permission.CAMERA
    )
    
    LaunchedEffect(Unit) {
        cameraPermissionState.launchPermissionRequest()
    }
    
    if (cameraPermissionState.status.isGranted) {
        AndroidView(
            factory = { ctx ->
                PreviewView(ctx).apply {
                    val cameraProviderFuture = ProcessCameraProvider.getInstance(ctx)
                    cameraProviderFuture.addListener({
                        val cameraProvider = cameraProviderFuture.get()
                        val preview = Preview.Builder().build()
                        val imageAnalysis = ImageAnalysis.Builder()
                            .setBackpressureStrategy(ImageAnalysis.STRATEGY_KEEP_ONLY_LATEST)
                            .build()
                            .apply {
                                setAnalyzer(
                                    ContextCompat.getMainExecutor(ctx),
                                    QRCodeAnalyzer { qrCode ->
                                        onQRCodeScanned(qrCode)
                                    }
                                )
                            }
                        
                        try {
                            cameraProvider.unbindAll()
                            cameraProvider.bindToLifecycle(
                                lifecycleOwner,
                                CameraSelector.DEFAULT_BACK_CAMERA,
                                preview,
                                imageAnalysis
                            )
                            preview.setSurfaceProvider(surfaceProvider)
                        } catch (e: Exception) {
                            // Handle error
                        }
                    }, ContextCompat.getMainExecutor(ctx))
                }
            },
            modifier = Modifier.fillMaxSize()
        )
    } else {
        // Show permission denied UI
    }
}

class QRCodeAnalyzer(
    private val onQRCodeScanned: (String) -> Unit
) : ImageAnalysis.Analyzer {
    private val reader = MultiFormatReader().apply {
        setHints(mapOf(DecodeHintType.POSSIBLE_FORMATS to listOf(BarcodeFormat.QR_CODE)))
    }
    
    @androidx.camera.core.ExperimentalGetImage
    override fun analyze(imageProxy: ImageProxy) {
        val mediaImage = imageProxy.image
        if (mediaImage != null) {
            val image = BinaryBitmap(
                HybridBinarizer(
                    PlanarYUVLuminanceSource(
                        mediaImage.planes[0].buffer.toByteArray(),
                        imageProxy.width,
                        imageProxy.height,
                        0, 0,
                        imageProxy.width,
                        imageProxy.height,
                        false
                    )
                )
            )
            
            try {
                val result = reader.decode(image)
                onQRCodeScanned(result.text)
            } catch (e: NotFoundException) {
                // No QR code found
            }
        }
        imageProxy.close()
    }
}
```

### Image Upload Scanner

```kotlin
@Composable
fun QRImagePickerScreen(
    onQRCodeProcessed: (ContactCreateData) -> Unit,
    onDismiss: () -> Unit
) {
    val context = LocalContext.current
    var selectedImage by remember { mutableStateOf<Uri?>(null) }
    var isProcessing by remember { mutableStateOf(false) }
    
    // Image picker
    val launcher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.PickVisualMedia()
    ) { uri ->
        uri?.let {
            selectedImage = it
            processQRImage(context, it, onQRCodeProcessed, onDismiss)
        }
    }
    
    Button(onClick = { launcher.launch(PickVisualMediaRequest()) }) {
        Text("Pick QR Image")
    }
}

suspend fun processQRImage(
    context: Context,
    imageUri: Uri,
    onSuccess: (ContactCreateData) -> Unit,
    onError: (String) -> Unit
) {
    try {
        val imageBytes = context.contentResolver.openInputStream(imageUri)?.readBytes()
            ?: throw Exception("Could not read image")
        
        val response = qrApi.processImage(
            RequestBody.create(MediaType.parse("image/*"), imageBytes)
        )
        
        if (response.success) {
            val contactData = parseQRResponse(response.data)
            onSuccess(contactData)
        } else {
            onError(response.message ?: "Processing failed")
        }
    } catch (e: Exception) {
        onError(e.message ?: "Error processing image")
    }
}
```

## vCard Parsing

### VCardParser

```kotlin
object VCardParser {
    fun parseVCard(vCardString: String): ContactCreateData? {
        return try {
            val lines = vCardString.lines()
            var firstName = ""
            var lastName = ""
            var email: String? = null
            var phone: String? = null
            var company: String? = null
            var jobTitle: String? = null
            var address: String? = null
            var city: String? = null
            var state: String? = null
            var zipCode: String? = null
            var country: String? = null
            var website: String? = null
            var notes: String? = null
            
            for (line in lines) {
                when {
                    line.startsWith("N:") -> {
                        val parts = line.substring(2).split(";")
                        lastName = parts.getOrNull(0) ?: ""
                        firstName = parts.getOrNull(1) ?: ""
                    }
                    line.startsWith("FN:") -> {
                        val fullName = line.substring(3)
                        val nameParts = fullName.split(" ", limit = 2)
                        if (firstName.isEmpty()) firstName = nameParts.firstOrNull() ?: ""
                        if (lastName.isEmpty()) lastName = nameParts.getOrNull(1) ?: ""
                    }
                    line.startsWith("EMAIL") -> {
                        email = line.substringAfter(":").trim()
                    }
                    line.startsWith("TEL") -> {
                        phone = line.substringAfter(":").trim()
                    }
                    line.startsWith("ORG:") -> {
                        company = line.substring(4).trim()
                    }
                    line.startsWith("TITLE:") -> {
                        jobTitle = line.substring(6).trim()
                    }
                    line.startsWith("ADR:") -> {
                        val parts = line.substring(4).split(";")
                        address = parts.getOrNull(2)?.trim()
                        city = parts.getOrNull(3)?.trim()
                        state = parts.getOrNull(4)?.trim()
                        zipCode = parts.getOrNull(5)?.trim()
                        country = parts.getOrNull(6)?.trim()
                    }
                    line.startsWith("URL:") -> {
                        website = line.substring(4).trim()
                    }
                    line.startsWith("NOTE:") -> {
                        notes = line.substring(5).trim()
                    }
                }
            }
            
            ContactCreateData(
                firstName = firstName,
                lastName = lastName,
                email = email,
                phone = phone,
                company = company,
                jobTitle = jobTitle,
                address = address,
                city = city,
                state = state,
                zipCode = zipCode,
                country = country,
                website = website,
                notes = notes,
                source = "qr_scan"
            )
        } catch (e: Exception) {
            null
        }
    }
}
```

## URL Handling

### URLFetcher

```kotlin
object URLFetcher {
    suspend fun fetchVCardFromURL(urlString: String): ContactCreateData? {
        return try {
            val url = URL(urlString)
            val connection = url.openConnection() as HttpURLConnection
            connection.requestMethod = "GET"
            connection.connectTimeout = 10000
            connection.readTimeout = 10000
            
            val responseCode = connection.responseCode
            if (responseCode == HttpURLConnection.HTTP_OK) {
                val content = connection.inputStream.bufferedReader().use { it.readText() }
                
                if (isVCardData(content)) {
                    VCardParser.parseVCard(content)
                } else {
                    // No vCard data - return contact data with URL pre-filled
                    ContactCreateData(
                        firstName = "",
                        lastName = "",
                        website = urlString,
                        source = "qr_scan",
                        sourceMetadata = "{\"qr_data\":\"$urlString\",\"type\":\"url\",\"no_vcard\":true}"
                    )
                }
            } else {
                // Even if fetch fails, return contact data with URL
                ContactCreateData(
                    firstName = "",
                    lastName = "",
                    website = urlString,
                    source = "qr_scan",
                    sourceMetadata = "{\"qr_data\":\"$urlString\",\"type\":\"url\",\"fetch_failed\":true}"
                )
            }
        } catch (e: Exception) {
            // On error, still return contact data with URL
            ContactCreateData(
                firstName = "",
                lastName = "",
                website = urlString,
                source = "qr_scan",
                sourceMetadata = "{\"qr_data\":\"$urlString\",\"type\":\"url\",\"fetch_error\":\"${e.message}\"}"
            )
        }
    }
    
    private fun isVCardData(content: String): Boolean {
        return content.trim().startsWith("BEGIN:VCARD") && 
               content.contains("END:VCARD")
    }
}
```

## Server QR Processing

### QRApi

```kotlin
interface QRApi {
    @Multipart
    @POST("qr/process-image")
    suspend fun processImage(
        @Part image: RequestBody
    ): ApiResponse<QRProcessResponse>
}

data class QRProcessResponse(
    val success: Boolean, // Or Int (1/0)
    val type: String? = null, // "text", "vcard", etc.
    val data: String? = null, // URL or text
    @SerializedName("contact_data") val contactData: Map<String, Any>? = null,
    val message: String? = null,
    val debug: String? = null
)

fun parseQRResponse(response: QRProcessResponse): ContactCreateData {
    // Handle success as either Bool or Int
    val success = if (response.success is Boolean) {
        response.success
    } else if (response.success is Int) {
        response.success == 1
    } else {
        false
    }
    
    if (!success) {
        throw Exception(response.message ?: "Processing failed")
    }
    
    // Check if we have contact_data (vCard was parsed)
    if (response.contactData != null) {
        return ContactCreateData(
            firstName = response.contactData["first_name"] as? String ?: "",
            lastName = response.contactData["last_name"] as? String ?: "",
            email = response.contactData["email_primary"] as? String,
            phone = response.contactData["work_phone"] as? String,
            mobilePhone = response.contactData["mobile_phone"] as? String,
            company = response.contactData["organization_name"] as? String,
            jobTitle = response.contactData["job_title"] as? String,
            address = response.contactData["street_address"] as? String,
            city = response.contactData["city"] as? String,
            state = response.contactData["state"] as? String,
            zipCode = response.contactData["zip_code"] as? String,
            country = response.contactData["country"] as? String,
            website = response.contactData["website_url"] as? String,
            notes = response.contactData["notes"] as? String,
            source = "qr_scan",
            sourceMetadata = "{\"qr_image_upload\":true}"
        )
    }
    
    // No contact_data - check if it's a URL
    if (response.type == "text" && response.data != null) {
        val urlString = response.data
        if (urlString.startsWith("http://") || urlString.startsWith("https://")) {
            return ContactCreateData(
                firstName = "",
                lastName = "",
                website = urlString,
                source = "qr_scan",
                sourceMetadata = "{\"qr_data\":\"$urlString\",\"type\":\"url\",\"from_image\":true}"
            )
        }
    }
    
    throw Exception("Could not parse QR code data")
}
```

## Dependencies

```kotlin
dependencies {
    // QR Code
    implementation("com.google.zxing:core:3.5.2")
    implementation("com.journeyapps:zxing-android-embedded:4.3.0")
    
    // CameraX
    implementation("androidx.camera:camera-camera2:1.3.1")
    implementation("androidx.camera:camera-lifecycle:1.3.1")
    implementation("androidx.camera:camera-view:1.3.1")
    
    // Image Picker
    implementation("androidx.activity:activity-compose:1.8.2")
}
```

## Integration Points

- **Contacts Module**: Creates contacts from QR scans
- **Business Cards Module**: Generates QR codes for cards

