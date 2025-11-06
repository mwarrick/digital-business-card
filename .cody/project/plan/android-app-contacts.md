# Android App: Contacts Management Module

## Overview

This module handles contact management including CRUD operations, QR code scanning integration, source tracking, and contact export.

## Features

### ✅ Implemented in iOS

1. **Contact Management**
   - Create contacts (manual or from QR)
   - Edit contacts
   - Delete contacts
   - View contact details
   - List all contacts with search
   - Pull-to-refresh

2. **Contact Data**
   - Required: firstName, lastName
   - Optional: email, phone, mobilePhone, company, jobTitle
   - Address fields (street, city, state, zip, country)
   - Website, notes, birthdate
   - Photo URL
   - Source tracking (manual, converted, qr_scan)
   - Source metadata (JSON string)
   - Created/updated dates with robust parsing

3. **Source Tracking**
   - Track contact origin
   - Store source metadata as JSON
   - Display source in UI
   - Lead conversion tracking

4. **Contact Export**
   - Export to device contacts
   - vCard format generation

## Data Models

### Contact Domain Model

```kotlin
data class Contact(
    val id: String,
    val firstName: String,
    val lastName: String,
    val email: String? = null,
    val phone: String? = null,
    val mobilePhone: String? = null,
    val company: String? = null,
    val jobTitle: String? = null,
    val address: String? = null,
    val city: String? = null,
    val state: String? = null,
    val zipCode: String? = null,
    val country: String? = null,
    val website: String? = null,
    val notes: String? = null,
    val commentsFromLead: String? = null,
    val birthdate: String? = null,
    val photoUrl: String? = null,
    val source: String? = null, // "manual", "converted", "qr_scan"
    val sourceMetadata: String? = null, // JSON string
    val createdAt: String,
    val updatedAt: String
) {
    val fullName: String get() = "$firstName $lastName"
    
    val createdAtDate: Date?
        get() = parseDate(createdAt)
    
    val updatedAtDate: Date?
        get() = parseDate(updatedAt)
    
    val formattedCreatedDate: String
        get() = createdAtDate?.let { formatDate(it) } ?: ""
    
    val formattedUpdatedDate: String
        get() = updatedAtDate?.let { formatDate(it) } ?: ""
    
    val wasUpdated: Boolean
        get() = createdAtDate?.let { created ->
            updatedAtDate?.let { updated ->
                abs(updated.time - created.time) > 60000 // More than 1 minute
            } ?: false
        } ?: false
    
    private fun parseDate(dateString: String?): Date? {
        // Robust date parsing (ISO8601, MySQL DATETIME)
        // Similar to iOS implementation
    }
    
    private fun formatDate(date: Date): String {
        val formatter = SimpleDateFormat("MMM d, yyyy h:mm a", Locale.getDefault())
        return formatter.format(date)
    }
}
```

## Room Database Entity

```kotlin
@Entity(tableName = "contacts")
data class ContactEntity(
    @PrimaryKey val id: String,
    val firstName: String,
    val lastName: String,
    val email: String?,
    val phone: String?,
    val mobilePhone: String?,
    val company: String?,
    val jobTitle: String?,
    val address: String?,
    val city: String?,
    val state: String?,
    val zipCode: String?,
    val country: String?,
    val website: String?,
    val notes: String?,
    val commentsFromLead: String?,
    val birthdate: String?,
    val photoUrl: String?,
    val source: String?,
    val sourceMetadata: String?,
    val createdAt: String,
    val updatedAt: String,
    val syncStatus: String = "synced",
    val lastSyncAt: Long = System.currentTimeMillis()
)
```

## API Integration

### ContactApi

```kotlin
interface ContactApi {
    @GET("contacts/")
    suspend fun getContacts(): ApiResponse<List<ContactDTO>>
    
    @POST("contacts/")
    suspend fun createContact(@Body contact: ContactCreateDTO): ApiResponse<ContactDTO>
    
    @PUT("contacts/")
    suspend fun updateContact(
        @Query("id") id: String,
        @Body contact: ContactUpdateDTO
    ): ApiResponse<ContactDTO>
    
    @DELETE("contacts/")
    suspend fun deleteContact(@Query("id") id: String): ApiResponse<Unit>
    
    @POST("contacts/create-from-qr")
    suspend fun createFromQR(@Body data: QRContactCreateDTO): ApiResponse<ContactDTO>
}
```

## ViewModels

### ContactsViewModel

```kotlin
@HiltViewModel
class ContactsViewModel @Inject constructor(
    private val contactRepository: ContactRepository,
    private val contactService: ContactService
) : ViewModel() {
    private val _contacts = MutableStateFlow<List<Contact>>(emptyList())
    val contacts: StateFlow<List<Contact>> = _contacts.asStateFlow()
    
    private val _searchText = MutableStateFlow("")
    val searchText: StateFlow<String> = _searchText.asStateFlow()
    
    val filteredContacts: StateFlow<List<Contact>> = combine(
        _contacts,
        _searchText
    ) { contacts, search ->
        if (search.isBlank()) {
            contacts
        } else {
            contacts.filter {
                it.fullName.contains(search, ignoreCase = true) ||
                it.email?.contains(search, ignoreCase = true) == true ||
                it.company?.contains(search, ignoreCase = true) == true
            }
        }
    }.stateIn(
        scope = viewModelScope,
        started = SharingStarted.WhileSubscribed(5000),
        initialValue = emptyList()
    )
    
    fun updateSearchText(text: String) {
        _searchText.value = text
    }
    
    fun loadContacts() {
        viewModelScope.launch {
            try {
                syncFromServer()
                loadLocalContacts()
            } catch (e: Exception) {
                // Handle error
            }
        }
    }
    
    suspend fun refreshFromServer() {
        // Pull-to-refresh logic
        // Prevent concurrent refreshes
    }
    
    private suspend fun syncFromServer() {
        // Sync logic
    }
    
    private fun loadLocalContacts() {
        viewModelScope.launch {
            val entities = contactRepository.getAllContactsSync()
            _contacts.value = entities.map { it.toDomain() }
        }
    }
}
```

## UI Screens

### ContactsDashboardScreen

- List of contacts
- Search bar
- Pull-to-refresh
- Create contact button
- Contact item click navigation

### ContactDetailsScreen

- Full contact information
- Created date display
- Updated date display (if applicable)
- Edit button
- Delete button
- Export to device contacts

### AddContactScreen / EditContactScreen

- Form with all fields
- Validation
- Save functionality

## Contact Export

```kotlin
class ContactExportHelper {
    fun exportToDeviceContacts(context: Context, contact: Contact): Boolean {
        val values = ContentValues().apply {
            put(ContactsContract.Data.RAW_CONTACT_ID, contact.id)
            put(ContactsContract.Data.MIMETYPE, ContactsContract.CommonDataKinds.StructuredName.CONTENT_ITEM_TYPE)
            put(ContactsContract.CommonDataKinds.StructuredName.GIVEN_NAME, contact.firstName)
            put(ContactsContract.CommonDataKinds.StructuredName.FAMILY_NAME, contact.lastName)
            
            contact.email?.let {
                put(ContactsContract.Data.MIMETYPE, ContactsContract.CommonDataKinds.Email.CONTENT_ITEM_TYPE)
                put(ContactsContract.CommonDataKinds.Email.DATA, it)
            }
            
            // Add more fields...
        }
        
        return try {
            context.contentResolver.insert(ContactsContract.Data.CONTENT_URI, values) != null
        } catch (e: Exception) {
            false
        }
    }
}
```

## Dependencies

```kotlin
dependencies {
    // Room
    implementation("androidx.room:room-runtime:2.6.1")
    implementation("androidx.room:room-ktx:2.6.1")
    
    // Contacts
    implementation("androidx.compose.material:material-icons-extended:1.5.4")
}
```

## Integration Points

- **QR Scanner**: Creates contacts from QR scans
- **Leads Module**: Converts leads to contacts
- **SyncManager**: Syncs contacts with server

