# Android App: Business Cards Module

## Overview

This module handles all business card functionality including CRUD operations, theme support, media management, and server synchronization.

## Features

### ✅ Implemented in iOS

1. **Card Management**
   - Create new business cards
   - Edit existing cards
   - Delete cards
   - View card details
   - List all cards
   - Active/inactive status

2. **Card Data**
   - Required: firstName, lastName, phoneNumber
   - Optional: companyName, jobTitle, bio
   - Multiple emails (personal, work, other)
   - Multiple phones (mobile, home, work, other)
   - Multiple websites with descriptions
   - Full address (street, city, state, zip, country)
   - Theme selection (12 professional themes)

3. **Media Management**
   - Profile photo upload/display
   - Company logo upload/display
   - Cover graphic upload/display
   - Image cropping and editing
   - Server path storage
   - Local image caching

4. **QR Code Generation**
   - Generate QR codes for cards
   - Server URL-based QR (if serverCardId exists)
   - Local vCard-based QR (fallback)

## Data Models

### BusinessCard Domain Model

```kotlin
data class BusinessCard(
    val id: String = UUID.randomUUID().toString(),
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
    var theme: String? = null,
    val createdAt: Long = System.currentTimeMillis(),
    var updatedAt: Long = System.currentTimeMillis(),
    var isActive: Boolean = true,
    var serverCardId: String? = null
) {
    val fullName: String get() = "$firstName $lastName"
    
    val primaryEmail: EmailContact?
        get() = additionalEmails.firstOrNull { it.isPrimary }
            ?: additionalEmails.firstOrNull { it.type == EmailType.WORK }
            ?: additionalEmails.firstOrNull()
}
```

### Supporting Models

```kotlin
data class EmailContact(
    val id: String = UUID.randomUUID().toString(),
    var email: String,
    var type: EmailType,
    var label: String? = null,
    var isPrimary: Boolean = false
)

enum class EmailType {
    PERSONAL, WORK, OTHER
}

data class PhoneContact(
    val id: String = UUID.randomUUID().toString(),
    var phoneNumber: String,
    var type: PhoneType,
    var label: String? = null
)

enum class PhoneType {
    MOBILE, HOME, WORK, OTHER
}

data class WebsiteLink(
    val id: String = UUID.randomUUID().toString(),
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

## Room Database Entities

```kotlin
@Entity(tableName = "business_cards")
data class BusinessCardEntity(
    @PrimaryKey val id: String,
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
    val theme: String?,
    val isActive: Boolean,
    val createdAt: Long,
    val updatedAt: Long,
    val serverCardId: String?
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
    @PrimaryKey val id: String,
    val cardId: String,
    val email: String,
    val type: String,
    val label: String?,
    val isPrimary: Boolean
)

// Similar entities for PhoneContactEntity, WebsiteLinkEntity, AddressEntity
```

## API Integration

### CardApi

```kotlin
interface CardApi {
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
}
```

### MediaApi

```kotlin
interface MediaApi {
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

## Repository

```kotlin
interface CardRepository {
    fun getAllCards(): Flow<List<BusinessCard>>
    suspend fun getCardById(id: String): BusinessCard?
    suspend fun insertCard(card: BusinessCard)
    suspend fun updateCard(card: BusinessCard)
    suspend fun deleteCard(card: BusinessCard)
    suspend fun getAllCardsSync(): List<BusinessCard>
}

class CardRepositoryImpl @Inject constructor(
    private val cardDao: BusinessCardDao,
    private val emailDao: EmailContactDao,
    private val phoneDao: PhoneContactDao,
    private val websiteDao: WebsiteLinkDao,
    private val addressDao: AddressDao
) : CardRepository {
    // Implementation
}
```

## ViewModels

### CardListViewModel

```kotlin
@HiltViewModel
class CardListViewModel @Inject constructor(
    private val cardRepository: CardRepository
) : ViewModel() {
    val cards: StateFlow<List<BusinessCard>> = cardRepository.getAllCards()
        .map { it.map { entity -> entity.toDomain() } }
        .stateIn(
            scope = viewModelScope,
            started = SharingStarted.WhileSubscribed(5000),
            initialValue = emptyList()
        )
}
```

### CardEditViewModel

```kotlin
@HiltViewModel
class CardEditViewModel @Inject constructor(
    private val cardRepository: CardRepository,
    private val cardService: CardService,
    private val mediaService: MediaService
) : ViewModel() {
    // Card editing logic
    // Media upload logic
    // Validation logic
}
```

## UI Screens

### BusinessCardListScreen

- List of all cards
- Search functionality
- Create new card button
- Card item click navigation

### BusinessCardCreateScreen

- Form with all fields
- Image pickers for media
- Theme selector
- Validation

### BusinessCardEditScreen

- Pre-filled form
- Update functionality
- Media management

### BusinessCardDisplayScreen

- Card preview
- QR code generation
- Share functionality

## Dependencies

```kotlin
dependencies {
    // Room
    implementation("androidx.room:room-runtime:2.6.1")
    implementation("androidx.room:room-ktx:2.6.1")
    kapt("androidx.room:room-compiler:2.6.1")
    
    // Image loading
    implementation("io.coil-kt:coil-compose:2.5.0")
    
    // Image cropping
    implementation("com.vanniktech:android-image-cropper:4.5.0")
}
```

## Integration Points

- **SyncManager**: Syncs cards with server
- **MediaService**: Handles image upload/download
- **QRCodeGenerator**: Generates QR codes for cards

