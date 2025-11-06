# Technical Specification: ShareMyCard Android App (Kotlin)

> **⚠️ NOTE: This plan has been updated with all iOS functionality. See [android-app-plan-updated.md](./android-app-plan-updated.md) for the complete updated specification.**
>
> **This plan has also been split into modular plans for easier development. See the modular plans section below.**

## Overview

This specification outlines the requirements to build an Android version of the ShareMyCard iOS app using Kotlin. The app is a digital business card system with QR code generation/scanning, cloud sync, media management, leads and contacts management, and comprehensive account features.

**Last Updated**: Based on iOS app version 1.8 functionality

## Modular Development Plans

This plan has been split into separate modular plans for easier development:

1. **[Authentication & Security](./android-app-authentication.md)** - Login, registration, password management, JWT handling
2. **[Business Cards](./android-app-business-cards.md)** - Card CRUD, themes, media management
3. **[Contacts Management](./android-app-contacts.md)** - Contact CRUD, QR scanning, export, source tracking
4. **[Leads Management](./android-app-leads.md)** - Lead viewing, conversion, search, sorting
5. **[QR Code Features](./android-app-qr-codes.md)** - Generation, scanning, parsing, URL handling
6. **[Sync & Data Management](./android-app-sync.md)** - Sync logic, conflict resolution, error handling
7. **[UI & Navigation](./android-app-ui-navigation.md)** - Screens, navigation, components, home page
8. **[Settings & Account](./android-app-settings.md)** - Settings, account management, password settings

Each modular plan can be developed independently and integrated together.

---

## 1. Architecture

### 1.1 Application Architecture Pattern

- **MVVM (Model-View-ViewModel)** with Clean Architecture principles
- **Single Activity** architecture with Jetpack Compose for UI
- **Repository Pattern** for data access abstraction
- **Dependency Injection** using Hilt/Dagger

### 1.2 Layer Structure

```
com.sharemycard.android/
├── data/
│   ├── local/
│   │   ├── database/        # Room database entities & DAOs
│   │   └── preferences/     # Encrypted SharedPreferences for tokens
│   ├── remote/
│   │   ├── api/             # Retrofit API interfaces
│   │   └── models/          # API request/response DTOs
│   └── repository/          # Repository implementations
├── domain/
│   ├── models/              # Business logic models
│   ├── repository/          # Repository interfaces
│   └── usecase/             # Use cases for business logic
├── presentation/
│   ├── screens/             # Composable screens
│   ├── components/          # Reusable UI components
│   ├── viewmodels/          # ViewModels
│   └── navigation/          # Navigation graph
└── di/                      # Dependency injection modules
```

---

## 2. Technology Stack

### 2.1 Core Technologies

- **Language**: Kotlin 1.9+
- **Minimum SDK**: Android 8.0 (API 26)
- **Target SDK**: Android 14 (API 34)
- **Build System**: Gradle with Kotlin DSL

### 2.2 Key Libraries

#### UI & Navigation

- **Jetpack Compose**: UI framework (replaces SwiftUI)
- **Material Design 3**: Design system
- **Compose Navigation**: Screen navigation
- **Accompanist**: Permissions, system UI controller

#### Data & Persistence

- **Room**: Local SQLite database (replaces Core Data)
- **DataStore**: Preferences storage
- **EncryptedSharedPreferences**: Secure token storage (replaces Keychain)

#### Networking

- **Retrofit**: HTTP client (replaces URLSession)
- **OkHttp**: HTTP engine with interceptors
- **Gson/Moshi**: JSON serialization (replaces JSONDecoder)

#### Image Handling

- **Coil**: Image loading and caching
- **CameraX**: Camera integration for QR scanning

#### QR Code

- **ZXing (Zebra Crossing)**: QR code generation/scanning (replaces Core Image)

#### Dependency Injection

- **Hilt**: DI framework

#### Concurrency

- **Coroutines + Flow**: Async operations (replaces async/await)
- **StateFlow/SharedFlow**: Reactive state management (replaces @Published)

---

## 3. Data Models

### 3.1 Domain Models (Kotlin)

```kotlin
// BusinessCard.kt
data class BusinessCard(
    val id: UUID = UUID.randomUUID(),
    var firstName: String,
    var lastName: String,
    var phoneNumber: String,
    var additionalEmails: List<EmailContact> = emptyList(),
    var additionalPhones: List<PhoneContact> = emptyList(),
    var websiteLinks: List<WebsiteLink> = emptyList(),
    var address: Address? = null,
    var companyName: String? = null,
    var jobTitle: String? = null,
    var bio: String? = null,
    var profilePhoto: ByteArray? = null,
    var companyLogo: ByteArray? = null,
    var coverGraphic: ByteArray? = null,
    var profilePhotoPath: String? = null,
    var companyLogoPath: String? = null,
    var coverGraphicPath: String? = null,
    val createdAt: Date = Date(),
    var updatedAt: Date = Date(),
    var isActive: Boolean = true,
    var serverCardId: String? = null
) {
    val fullName: String get() = "$firstName $lastName"
    
    val primaryEmail: EmailContact?
        get() = additionalEmails.firstOrNull { it.isPrimary }
            ?: additionalEmails.firstOrNull { it.type == EmailType.WORK }
            ?: additionalEmails.firstOrNull()
}

data class EmailContact(
    val id: UUID = UUID.randomUUID(),
    var email: String,
    var type: EmailType,
    var label: String? = null,
    var isPrimary: Boolean = false
)

enum class EmailType {
    PERSONAL, WORK, OTHER;
    
    val displayName: String
        get() = when (this) {
            PERSONAL -> "Personal"
            WORK -> "Work"
            OTHER -> "Other"
        }
}

data class PhoneContact(
    val id: UUID = UUID.randomUUID(),
    var phoneNumber: String,
    var type: PhoneType,
    var label: String? = null
)

enum class PhoneType {
    MOBILE, HOME, WORK, OTHER;
    
    val displayName: String
        get() = when (this) {
            MOBILE -> "Mobile"
            HOME -> "Home"
            WORK -> "Work"
            OTHER -> "Other"
        }
}

data class WebsiteLink(
    val id: UUID = UUID.randomUUID(),
    var name: String,
    var url: String,
    var description: String? = null,
    var isPrimary: Boolean = false
)

data class Address(
    var street: String? = null,
    var city: String? = null,
    var state: String? = null,
    var zipCode: String? = null,
    var country: String? = null
) {
    val fullAddress: String
        get() = listOfNotNull(street, city, state, zipCode, country)
            .filter { it.isNotEmpty() }
            .joinToString(", ")
}
```

### 3.2 Room Database Entities

```kotlin
// BusinessCardEntity.kt
@Entity(tableName = "business_cards")
data class BusinessCardEntity(
    @PrimaryKey val id: String = UUID.randomUUID().toString(),
    val firstName: String,
    val lastName: String,
    val phoneNumber: String,
    val companyName: String?,
    val jobTitle: String?,
    val bio: String?,
    @ColumnInfo(typeAffinity = ColumnInfo.BLOB) val profilePhoto: ByteArray?,
    @ColumnInfo(typeAffinity = ColumnInfo.BLOB) val companyLogo: ByteArray?,
    @ColumnInfo(typeAffinity = ColumnInfo.BLOB) val coverGraphic: ByteArray?,
    val profilePhotoPath: String?,
    val companyLogoPath: String?,
    val coverGraphicPath: String?,
    val isActive: Boolean,
    val createdAt: Long,
    val updatedAt: Long,
    val serverCardId: String?,
    val theme: String?
)

@Entity(
    tableName = "email_contacts",
    foreignKeys = [ForeignKey(
        entity = BusinessCardEntity::class,
        parentColumns = ["id"],
        childColumns = ["cardId"],
        onDelete = ForeignKey.CASCADE
    )]
)
data class EmailContactEntity(
    @PrimaryKey val id: String = UUID.randomUUID().toString(),
    val cardId: String,
    val email: String,
    val type: String,
    val label: String?,
    val isPrimary: Boolean
)

// Similar entities for PhoneContactEntity, WebsiteLinkEntity, AddressEntity
```

### 3.3 API DTOs

```kotlin
// BusinessCardDTO.kt
data class BusinessCardDTO(
    val id: String?,
    @SerializedName("user_id") val userId: String?,
    @SerializedName("first_name") val firstName: String,
    @SerializedName("last_name") val lastName: String,
    @SerializedName("phone_number") val phoneNumber: String,
    @SerializedName("company_name") val companyName: String?,
    @SerializedName("job_title") val jobTitle: String?,
    val bio: String?,
    @SerializedName("profile_photo_path") val profilePhotoPath: String?,
    @SerializedName("company_logo_path") val companyLogoPath: String?,
    @SerializedName("cover_graphic_path") val coverGraphicPath: String?,
    val theme: String?,
    val emails: List<EmailContactDTO>,
    val phones: List<PhoneContactDTO>,
    val websites: List<WebsiteLinkDTO>,
    val address: AddressDTO?,
    @SerializedName("is_active") val isActive: Boolean?,
    @SerializedName("created_at") val createdAt: String?,
    @SerializedName("updated_at") val updatedAt: String?
)
```

---

## 4. API Integration

### 4.1 Retrofit API Service

```kotlin
interface ShareMyCardApi {
    // Authentication
    @POST("auth/register")
    suspend fun register(@Body request: RegisterRequest): ApiResponse<RegisterResponse>
    
    @POST("auth/login")
    suspend fun login(@Body request: LoginRequest): ApiResponse<LoginResponse>
    
    @POST("auth/verify")
    suspend fun verify(@Body request: VerifyRequest): ApiResponse<VerifyResponse>
    
    // Business Cards
    @GET("cards/")
    suspend fun getCards(): ApiResponse<List<BusinessCardDTO>>
    
    @POST("cards/")
    suspend fun createCard(@Body card: BusinessCardDTO): ApiResponse<BusinessCardDTO>
    
    @PUT("cards/")
    suspend fun updateCard(
        @Query("id") id: String,
        @Body card: BusinessCardDTO
    ): ApiResponse<BusinessCardDTO>
    
    @DELETE("cards/")
    suspend fun deleteCard(@Query("id") id: String): ApiResponse<Unit>
    
    // Media Upload
    @Multipart
    @POST("media/upload")
    suspend fun uploadImage(
        @Part("business_card_id") cardId: RequestBody,
        @Part("media_type") mediaType: RequestBody,
        @Part file: MultipartBody.Part
    ): ApiResponse<MediaUploadResponse>
    
    @GET("media/view")
    suspend fun downloadImage(@Query("filename") filename: String): ResponseBody
    
    @HTTP(method = "DELETE", path = "media/delete", hasBody = true)
    suspend fun deleteImage(@Body request: DeleteMediaRequest): ApiResponse<Unit>
}
```

### 4.2 API Configuration

```kotlin
object ApiConfig {
    const val BASE_URL = "https://sharemycard.app/api/"
    const val TIMEOUT = 30L // seconds
    
    object Endpoints {
        const val REGISTER = "auth/register"
        const val LOGIN = "auth/login"
        const val VERIFY = "auth/verify"
        const val CARDS = "cards/"
        const val MEDIA_UPLOAD = "media/upload"
        const val MEDIA_VIEW = "media/view"
    }
    
    object MediaType {
        const val PROFILE_PHOTO = "profile_photo"
        const val COMPANY_LOGO = "company_logo"
        const val COVER_GRAPHIC = "cover_graphic"
    }
}
```

### 4.3 Authentication Interceptor

```kotlin
class AuthInterceptor @Inject constructor(
    private val tokenManager: TokenManager
) : Interceptor {
    override fun intercept(chain: Interceptor.Chain): Response {
        val originalRequest = chain.request()
        val requestBuilder = originalRequest.newBuilder()
            .addHeader("Content-Type", "application/json")
            .addHeader("User-Agent", "ShareMyCard-Android/1.0")
            .addHeader("X-App-Platform", "android-app")
        
        tokenManager.getToken()?.let { token ->
            requestBuilder.addHeader("Authorization", "Bearer $token")
        }
        
        return chain.proceed(requestBuilder.build())
    }
}
```

---

## 5. Local Storage

### 5.1 Room Database

```kotlin
@Database(
    entities = [
        BusinessCardEntity::class,
        EmailContactEntity::class,
        PhoneContactEntity::class,
        WebsiteLinkEntity::class,
        AddressEntity::class
    ],
    version = 1,
    exportSchema = true
)
abstract class ShareMyCardDatabase : RoomDatabase() {
    abstract fun businessCardDao(): BusinessCardDao
    abstract fun emailContactDao(): EmailContactDao
    abstract fun phoneContactDao(): PhoneContactDao
    abstract fun websiteLinkDao(): WebsiteLinkDao
    abstract fun addressDao(): AddressDao
}

@Dao
interface BusinessCardDao {
    @Query("SELECT * FROM business_cards ORDER BY updatedAt DESC")
    fun getAllCards(): Flow<List<BusinessCardEntity>>
    
    @Query("SELECT * FROM business_cards WHERE id = :id")
    suspend fun getCardById(id: String): BusinessCardEntity?
    
    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertCard(card: BusinessCardEntity)
    
    @Update
    suspend fun updateCard(card: BusinessCardEntity)
    
    @Delete
    suspend fun deleteCard(card: BusinessCardEntity)
    
    @Query("DELETE FROM business_cards")
    suspend fun deleteAllCards()
}
```

### 5.2 Secure Token Storage

```kotlin
class TokenManager @Inject constructor(
    @ApplicationContext private val context: Context
) {
    private val sharedPreferences = EncryptedSharedPreferences.create(
        "secure_prefs",
        MasterKeys.getOrCreate(MasterKeys.AES256_GCM_SPEC),
        context,
        EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
        EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM
    )
    
    fun saveToken(token: String) {
        sharedPreferences.edit().putString(KEY_JWT_TOKEN, token).apply()
    }
    
    fun getToken(): String? {
        return sharedPreferences.getString(KEY_JWT_TOKEN, null)
    }
    
    fun deleteToken() {
        sharedPreferences.edit().remove(KEY_JWT_TOKEN).apply()
    }
    
    fun isAuthenticated(): Boolean = getToken() != null
    
    companion object {
        private const val KEY_JWT_TOKEN = "jwt_token"
    }
}
```

---

## 6. UI Implementation (Jetpack Compose)

### 6.1 Main Screen

```kotlin
@Composable
fun ContentScreen(
    viewModel: ContentViewModel = hiltViewModel(),
    onNavigateToCardList: () -> Unit,
    onNavigateToCreateCard: () -> Unit,
    onNavigateToSettings: () -> Unit,
    onLogout: () -> Unit
) {
    val cardsCount by viewModel.cardsCount.collectAsState()
    val isSyncing by viewModel.isSyncing.collectAsState()
    val syncMessage by viewModel.syncMessage.collectAsState()
    
    LaunchedEffect(Unit) {
        viewModel.performSync()
    }
    
    Scaffold(
        topBar = {
            TopAppBar(title = { Text("ShareMyCard") })
        }
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .padding(24.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center
        ) {
            Icon(
                imageVector = Icons.Default.CardGiftcard,
                contentDescription = null,
                modifier = Modifier.size(80.dp),
                tint = MaterialTheme.colorScheme.primary
            )
            
            Spacer(modifier = Modifier.height(16.dp))
            
            Text(
                text = "ShareMyCard",
                style = MaterialTheme.typography.headlineLarge
            )
            
            Text(
                text = "Your Digital Business Cards",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
            
            if (cardsCount > 0) {
                Spacer(modifier = Modifier.height(8.dp))
                Text(
                    text = "$cardsCount ${if (cardsCount == 1) "Card" else "Cards"}",
                    style = MaterialTheme.typography.titleLarge,
                    color = MaterialTheme.colorScheme.primary
                )
            }
            
            Spacer(modifier = Modifier.height(32.dp))
            
            Button(
                onClick = onNavigateToCardList,
                modifier = Modifier.fillMaxWidth()
            ) {
                Text("View All Business Cards")
            }
            
            OutlinedButton(
                onClick = onNavigateToCreateCard,
                modifier = Modifier.fillMaxWidth()
            ) {
                Text("Create New Business Card")
            }
            
            HorizontalDivider(modifier = Modifier.padding(vertical = 16.dp))
            
            Button(
                onClick = { viewModel.performSync() },
                enabled = !isSyncing,
                modifier = Modifier.fillMaxWidth()
            ) {
                if (isSyncing) {
                    CircularProgressIndicator(modifier = Modifier.size(20.dp))
                } else {
                    Icon(Icons.Default.Sync, contentDescription = null)
                }
                Spacer(modifier = Modifier.width(8.dp))
                Text(if (isSyncing) "Syncing..." else "Sync with Server")
            }
            
            if (syncMessage.isNotEmpty()) {
                Text(
                    text = syncMessage,
                    style = MaterialTheme.typography.bodySmall,
                    color = if (syncMessage.contains("✅")) 
                        Color.Green else MaterialTheme.colorScheme.error
                )
            }
            
            HorizontalDivider(modifier = Modifier.padding(vertical = 16.dp))
            
            OutlinedButton(
                onClick = onNavigateToSettings,
                modifier = Modifier.fillMaxWidth()
            ) {
                Text("Account Security")
            }
            
            TextButton(
                onClick = onLogout,
                modifier = Modifier.fillMaxWidth()
            ) {
                Text("Logout", color = MaterialTheme.colorScheme.error)
            }
        }
    }
}
```

### 6.2 Business Card List

```kotlin
@Composable
fun BusinessCardListScreen(
    viewModel: CardListViewModel = hiltViewModel(),
    onNavigateToCardDetail: (String) -> Unit,
    onNavigateToCreateCard: () -> Unit
) {
    val cards by viewModel.cards.collectAsState()
    
    Scaffold(
        topBar = {
            TopAppBar(title = { Text("My Business Cards") })
        },
        floatingActionButton = {
            FloatingActionButton(onClick = onNavigateToCreateCard) {
                Icon(Icons.Default.Add, contentDescription = "Add Card")
            }
        }
    ) { paddingValues ->
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            items(cards, key = { it.id }) { card ->
                BusinessCardItem(
                    card = card,
                    onClick = { onNavigateToCardDetail(card.id) }
                )
            }
        }
    }
}
```

### 6.3 QR Code Generation

```kotlin
@Composable
fun QRCodeScreen(
    card: BusinessCard,
    onShare: () -> Unit
) {
    val qrBitmap = remember(card) {
        QRCodeGenerator.generateQRCode(card)
    }
    
    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(16.dp),
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        // Card preview
        BusinessCardPreview(card)
        
        Spacer(modifier = Modifier.height(24.dp))
        
        Text(
            text = "Scan to Add Contact",
            style = MaterialTheme.typography.titleMedium
        )
        
        qrBitmap?.let { bitmap ->
            Image(
                bitmap = bitmap.asImageBitmap(),
                contentDescription = "QR Code",
                modifier = Modifier
                    .size(250.dp)
                    .padding(16.dp)
            )
        } ?: CircularProgressIndicator()
        
        Button(
            onClick = onShare,
            modifier = Modifier.fillMaxWidth()
        ) {
            Icon(Icons.Default.Share, contentDescription = null)
            Spacer(modifier = Modifier.width(8.dp))
            Text("Share QR Code")
        }
    }
}
```

---

## 7. QR Code Implementation

### 7.1 QR Code Generator

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

### 7.2 QR Code Scanner

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

---

## 8. Sync Manager

### 8.1 Sync Implementation

```kotlin
class SyncManager @Inject constructor(
    private val cardRepository: CardRepository,
    private val cardService: CardService,
    private val mediaService: MediaService
) {
    suspend fun performFullSync() = withContext(Dispatchers.IO) {
        try {
            // 1. Fetch server cards
            val serverCards = cardService.fetchCards()
            val serverCardMap = serverCards.associateBy { it.id }
            
            // 2. Push local changes with timestamp comparison
            pushLocalCardsWithComparison(serverCardMap)
            
            // 3. Pull server cards to local
            pullServerCards(serverCards)
        } catch (e: Exception) {
            throw SyncException("Sync failed: ${e.message}", e)
        }
    }
    
    private suspend fun pushLocalCardsWithComparison(
        serverCards: Map<String?, BusinessCardDTO>
    ) {
        val localCards = cardRepository.getAllCardsSync()
        
        for (card in localCards) {
            try {
                val apiCard = card.toDTO()
                
                if (card.serverCardId != null) {
                    val serverCard = serverCards[card.serverCardId]
                    if (shouldPushBasedOnTimestamp(card, serverCard)) {
                        cardService.updateCard(card.serverCardId!!, apiCard)
                    }
                } else {
                    val createdCard = cardService.createCard(apiCard)
                    cardRepository.updateCardServerId(card.id, createdCard.id!!)
                }
            } catch (e: Exception) {
                // Log and continue with other cards
            }
        }
    }
    
    private suspend fun pullServerCards(serverCards: List<BusinessCardDTO>) {
        for (serverCard in serverCards) {
            val localCard = serverCard.id?.let { 
                cardRepository.findCardByServerId(it) 
            }
            
            if (localCard != null) {
                if (shouldUpdateLocalCard(localCard, serverCard)) {
                    cardRepository.updateCard(serverCard.toDomain())
                    downloadImagesForCard(serverCard)
                }
            } else {
                cardRepository.insertCard(serverCard.toDomain())
                downloadImagesForCard(serverCard)
            }
        }
    }
    
    private fun shouldPushBasedOnTimestamp(
        localCard: BusinessCard,
        serverCard: BusinessCardDTO?
    ): Boolean {
        if (serverCard == null) return true
        
        val serverDate = serverCard.updatedAt?.parseServerDate() ?: return true
        return localCard.updatedAt.time > serverDate.time
    }
    
    private suspend fun downloadImagesForCard(card: BusinessCardDTO) {
        card.profilePhotoPath?.let { path ->
            try {
                val imageData = mediaService.downloadImage(path)
                // Save to local card
            } catch (e: Exception) {
                // Log error
            }
        }
        // Similar for logo and cover graphic
    }
}
```

---

## 9. Navigation

### 9.1 Navigation Graph

```kotlin
@Composable
fun ShareMyCardNavGraph(
    navController: NavHostController = rememberNavController()
) {
    NavHost(
        navController = navController,
        startDestination = if (isAuthenticated()) "home" else "auth"
    ) {
        // Authentication flow
        navigation(startDestination = "login", route = "auth") {
            composable("login") {
                LoginScreen(
                    onLoginSuccess = {
                        navController.navigate("home") {
                            popUpTo("auth") { inclusive = true }
                        }
                    },
                    onNavigateToRegister = {
                        navController.navigate("register")
                    }
                )
            }
            composable("register") {
                RegisterScreen(
                    onRegistrationSuccess = {
                        navController.navigate("verify")
                    }
                )
            }
            composable("verify") {
                VerifyScreen(
                    onVerificationSuccess = {
                        navController.navigate("home") {
                            popUpTo("auth") { inclusive = true }
                        }
                    }
                )
            }
        }
        
        // Main app flow
        composable("home") {
            ContentScreen(
                onNavigateToCardList = { navController.navigate("card_list") },
                onNavigateToCreateCard = { navController.navigate("card_create") },
                onNavigateToSettings = { navController.navigate("settings") },
                onLogout = {
                    navController.navigate("auth") {
                        popUpTo(0) { inclusive = true }
                    }
                }
            )
        }
        
        composable("card_list") {
            BusinessCardListScreen(
                onNavigateToCardDetail = { cardId ->
                    navController.navigate("card_detail/$cardId")
                },
                onNavigateToCreateCard = {
                    navController.navigate("card_create")
                }
            )
        }
        
        composable("card_create") {
            BusinessCardCreationScreen(
                onCardCreated = { navController.popBackStack() }
            )
        }
        
        composable(
            route = "card_detail/{cardId}",
            arguments = listOf(navArgument("cardId") { type = NavType.StringType })
        ) { backStackEntry ->
            val cardId = backStackEntry.arguments?.getString("cardId")
            BusinessCardDetailScreen(
                cardId = cardId!!,
                onNavigateToEdit = { navController.navigate("card_edit/$cardId") },
                onNavigateToQRCode = { navController.navigate("qr_code/$cardId") }
            )
        }
        
        composable(
            route = "card_edit/{cardId}",
            arguments = listOf(navArgument("cardId") { type = NavType.StringType })
        ) { backStackEntry ->
            val cardId = backStackEntry.arguments?.getString("cardId")
            BusinessCardEditScreen(
                cardId = cardId!!,
                onCardUpdated = { navController.popBackStack() }
            )
        }
        
        composable(
            route = "qr_code/{cardId}",
            arguments = listOf(navArgument("cardId") { type = NavType.StringType })
        ) { backStackEntry ->
            val cardId = backStackEntry.arguments?.getString("cardId")
            QRCodeScreen(
                cardId = cardId!!,
                onDismiss = { navController.popBackStack() }
            )
        }
        
        composable("qr_scanner") {
            QRCodeScannerScreen(
                onQRCodeScanned = { content ->
                    // Handle scanned content
                    navController.popBackStack()
                },
                onDismiss = { navController.popBackStack() }
            )
        }
        
        composable("settings") {
            PasswordSettingsScreen(
                onNavigateBack = { navController.popBackStack() }
            )
        }
    }
}
```

---

## 10. Dependency Injection (Hilt)

```kotlin
@Module
@InstallIn(SingletonComponent::class)
object AppModule {
    
    @Provides
    @Singleton
    fun provideShareMyCardDatabase(
        @ApplicationContext context: Context
    ): ShareMyCardDatabase {
        return Room.databaseBuilder(
            context,
            ShareMyCardDatabase::class.java,
            "sharemycard_db"
        ).build()
    }
    
    @Provides
    @Singleton
    fun provideOkHttpClient(
        authInterceptor: AuthInterceptor
    ): OkHttpClient {
        return OkHttpClient.Builder()
            .addInterceptor(authInterceptor)
            .connectTimeout(ApiConfig.TIMEOUT, TimeUnit.SECONDS)
            .readTimeout(ApiConfig.TIMEOUT, TimeUnit.SECONDS)
            .writeTimeout(ApiConfig.TIMEOUT, TimeUnit.SECONDS)
            .build()
    }
    
    @Provides
    @Singleton
    fun provideRetrofit(okHttpClient: OkHttpClient): Retrofit {
        return Retrofit.Builder()
            .baseUrl(ApiConfig.BASE_URL)
            .client(okHttpClient)
            .addConverterFactory(GsonConverterFactory.create())
            .build()
    }
    
    @Provides
    @Singleton
    fun provideShareMyCardApi(retrofit: Retrofit): ShareMyCardApi {
        return retrofit.create(ShareMyCardApi::class.java)
    }
    
    @Provides
    @Singleton
    fun provideTokenManager(
        @ApplicationContext context: Context
    ): TokenManager {
        return TokenManager(context)
    }
}

@Module
@InstallIn(SingletonComponent::class)
abstract class RepositoryModule {
    
    @Binds
    @Singleton
    abstract fun bindCardRepository(
        impl: CardRepositoryImpl
    ): CardRepository
}
```

---

## 11. Key Differences from iOS

### 11.1 Architecture Patterns

- **iOS**: SwiftUI + Combine + Core Data
- **Android**: Jetpack Compose + Flow + Room

### 11.2 Async Programming

- **iOS**: `async/await` with Swift Concurrency
- **Android**: Coroutines with `suspend` functions

### 11.3 Reactive State

- **iOS**: `@Published` properties with Combine
- **Android**: `StateFlow`/`SharedFlow` with Coroutines

### 11.4 Secure Storage

- **iOS**: Keychain Services
- **Android**: EncryptedSharedPreferences

### 11.5 Image Handling

- **iOS**: UIImage with Core Image
- **Android**: Bitmap with BitmapFactory

### 11.6 QR Code

- **iOS**: Core Image CIFilter
- **Android**: ZXing library

### 11.7 Camera Access

- **iOS**: AVFoundation
- **Android**: CameraX

---

## 12. Build Configuration

### 12.1 build.gradle.kts (Module level)

```kotlin
plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.android")
    id("kotlin-kapt")
    id("com.google.dagger.hilt.android")
}

android {
    namespace = "com.sharemycard.android"
    compileSdk = 34
    
    defaultConfig {
        applicationId = "com.sharemycard.android"
        minSdk = 26
        targetSdk = 34
        versionCode = 1
        versionName = "1.0.0"
    }
    
    buildFeatures {
        compose = true
    }
    
    composeOptions {
        kotlinCompilerExtensionVersion = "1.5.3"
    }
}

dependencies {
    // Compose
    implementation(platform("androidx.compose:compose-bom:2024.01.00"))
    implementation("androidx.compose.ui:ui")
    implementation("androidx.compose.material3:material3")
    implementation("androidx.compose.ui:ui-tooling-preview")
    implementation("androidx.activity:activity-compose:1.8.2")
    implementation("androidx.navigation:navigation-compose:2.7.6")
    
    // Room
    implementation("androidx.room:room-runtime:2.6.1")
    implementation("androidx.room:room-ktx:2.6.1")
    kapt("androidx.room:room-compiler:2.6.1")
    
    // Retrofit
    implementation("com.squareup.retrofit2:retrofit:2.9.0")
    implementation("com.squareup.retrofit2:converter-gson:2.9.0")
    implementation("com.squareup.okhttp3:logging-interceptor:4.12.0")
    
    // Hilt
    implementation("com.google.dagger:hilt-android:2.50")
    kapt("com.google.dagger:hilt-compiler:2.50")
    implementation("androidx.hilt:hilt-navigation-compose:1.1.0")
    
    // Coroutines
    implementation("org.jetbrains.kotlinx:kotlinx-coroutines-android:1.7.3")
    
    // Security
    implementation("androidx.security:security-crypto:1.1.0-alpha06")
    
    // Image Loading
    implementation("io.coil-kt:coil-compose:2.5.0")
    
    // QR Code
    implementation("com.google.zxing:core:3.5.2")
    
    // CameraX
    implementation("androidx.camera:camera-camera2:1.3.1")
    implementation("androidx.camera:camera-lifecycle:1.3.1")
    implementation("androidx.camera:camera-view:1.3.1")
    
    // Accompanist (Permissions)
    implementation("com.google.accompanist:accompanist-permissions:0.32.0")
}
```

---

## 13. Testing Strategy

### 13.1 Unit Tests

- **ViewModel tests** using JUnit and MockK
- **Repository tests** with fake implementations
- **Use case tests** for business logic

### 13.2 UI Tests

- **Compose UI tests** using `@Composable` test APIs
- **Navigation tests**
- **Integration tests** with Hilt test modules

### 13.3 Example Test

```kotlin
@RunWith(AndroidJUnit4::class)
class CardRepositoryTest {
    
    @get:Rule
    val instantTaskExecutorRule = InstantTaskExecutorRule()
    
    private lateinit var database: ShareMyCardDatabase
    private lateinit var repository: CardRepositoryImpl
    
    @Before
    fun setup() {
        database = Room.inMemoryDatabaseBuilder(
            ApplicationProvider.getApplicationContext(),
            ShareMyCardDatabase::class.java
        ).allowMainThreadQueries().build()
        
        repository = CardRepositoryImpl(database.businessCardDao())
    }
    
    @Test
    fun insertCard_retrievesCard() = runTest {
        val card = BusinessCard(
            firstName = "John",
            lastName = "Doe",
            phoneNumber = "+1234567890"
        )
        
        repository.insertCard(card)
        
        val cards = repository.getAllCards().first()
        assertEquals(1, cards.size)
        assertEquals("John", cards[0].firstName)
    }
}
```

---

## 14. Platform-Specific Features

### 14.1 Android-Specific Enhancements

1. **Material You (Dynamic Color)**: Support system theming
2. **Widgets**: Home screen widget for quick QR access
3. **Share Target**: Allow sharing business cards via Android's share sheet
4. **Shortcuts**: Deep links for common actions
5. **Biometric Authentication**: Fingerprint/Face unlock for app access

### 14.2 Permissions Required

```xml
<!-- AndroidManifest.xml -->
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.CAMERA" />
<uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE" />
<uses-permission android:name="android.permission.WRITE_EXTERNAL_STORAGE" />
```

---

## 15. Performance Considerations

1. **Image Compression**: Use Coil's built-in compression
2. **Lazy Loading**: Implement pagination for large card lists
3. **Database Optimization**: Add proper indexes
4. **Memory Management**: Use weak references for large bitmaps
5. **Background Work**: Use WorkManager for sync operations

---

## Conclusion

This specification provides a comprehensive blueprint for building the ShareMyCard Android app in Kotlin, maintaining feature parity with the iOS version while following Android best practices and leveraging modern Android development tools.