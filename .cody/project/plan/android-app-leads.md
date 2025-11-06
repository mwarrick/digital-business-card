# Android App: Leads Management Module

## Overview

This module handles lead viewing, search, sorting, and conversion to contacts. Leads are view-only in the app (captured on web).

## Features

### ✅ Implemented in iOS

1. **Lead Viewing** (View-Only)
   - List all leads
   - View lead details
   - Search leads
   - Sort by most recent first
   - Display capture dates
   - Pull-to-refresh

2. **Lead Data**
   - Full lead information
   - Source information (business card or custom QR)
   - Status (new/converted)
   - Capture date with robust parsing
   - Business card information (if from card)
   - Custom QR information (if from QR)

3. **Lead Conversion**
   - Convert lead to contact
   - One-click conversion
   - Preserve lead data in contact

## Data Models

### Lead Domain Model

```kotlin
data class Lead(
    val id: String,
    val firstName: String,
    val lastName: String,
    val fullName: String? = null,
    val emailPrimary: String? = null,
    val workPhone: String? = null,
    val mobilePhone: String? = null,
    val streetAddress: String? = null,
    val city: String? = null,
    val state: String? = null,
    val zipCode: String? = null,
    val country: String? = null,
    val organizationName: String? = null,
    val jobTitle: String? = null,
    val birthdate: String? = null,
    val websiteUrl: String? = null,
    val photoUrl: String? = null,
    val commentsFromLead: String? = null,
    val createdAt: String? = null,
    val updatedAt: String? = null,
    // Business card information (from join)
    val cardFirstName: String? = null,
    val cardLastName: String? = null,
    val cardCompany: String? = null,
    val cardJobTitle: String? = null,
    // Custom QR code information (from join)
    val qrTitle: String? = null,
    val qrType: String? = null,
    // Status
    val status: String? = null // "new" or "converted"
) {
    val displayName: String
        get() = fullName?.takeIf { it.isNotEmpty() } 
            ?: "$firstName $lastName".trim()
    
    val isConverted: Boolean
        get() = status == "converted"
    
    val cardDisplayName: String
        get() {
            // If from business card, show card owner name
            if (cardFirstName != null && cardLastName != null) {
                return "$cardFirstName $cardLastName"
            }
            
            // If from custom QR code, show QR title/type
            if (!qrTitle.isNullOrEmpty()) {
                val qrTypeLabel = qrType?.capitalize() ?: "Custom"
                return "QR $qrTypeLabel: $qrTitle"
            } else if (qrType != null) {
                return "QR ${qrType.capitalize()}"
            }
            
            return "Unknown Card"
        }
    
    val createdAtDate: Date?
        get() = parseDate(createdAt)
    
    val formattedDate: String
        get() = createdAtDate?.let { formatDate(it) } ?: ""
    
    val relativeDate: String
        get() = createdAtDate?.let { 
            getRelativeTimeSpanString(it.time, System.currentTimeMillis(), 0)
        } ?: ""
    
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
@Entity(tableName = "leads")
data class LeadEntity(
    @PrimaryKey val id: String,
    val firstName: String,
    val lastName: String,
    val fullName: String?,
    val emailPrimary: String?,
    val workPhone: String?,
    val mobilePhone: String?,
    val streetAddress: String?,
    val city: String?,
    val state: String?,
    val zipCode: String?,
    val country: String?,
    val organizationName: String?,
    val jobTitle: String?,
    val birthdate: String?,
    val websiteUrl: String?,
    val photoUrl: String?,
    val commentsFromLead: String?,
    val createdAt: String?,
    val updatedAt: String?,
    val cardFirstName: String?,
    val cardLastName: String?,
    val cardCompany: String?,
    val cardJobTitle: String?,
    val qrTitle: String?,
    val qrType: String?,
    val status: String?,
    val syncStatus: String = "synced",
    val lastSyncAt: Long = System.currentTimeMillis()
)
```

## API Integration

### LeadApi

```kotlin
interface LeadApi {
    @GET("leads/")
    suspend fun getLeads(): ApiResponse<List<LeadDTO>>
    
    @POST("leads/convert.php")
    suspend fun convertLeadToContact(
        @Body request: LeadConversionRequest
    ): ApiResponse<ContactDTO>
}

data class LeadConversionRequest(
    @SerializedName("lead_id") val leadId: String
)
```

## ViewModels

### LeadsViewModel

```kotlin
@HiltViewModel
class LeadsViewModel @Inject constructor(
    private val leadRepository: LeadRepository,
    private val leadService: LeadService
) : ViewModel() {
    private val _leads = MutableStateFlow<List<Lead>>(emptyList())
    val leads: StateFlow<List<Lead>> = _leads.asStateFlow()
    
    private val _searchText = MutableStateFlow("")
    val searchText: StateFlow<String> = _searchText.asStateFlow()
    
    private val _isLoading = MutableStateFlow(false)
    val isLoading: StateFlow<Boolean> = _isLoading.asStateFlow()
    
    private val _errorMessage = MutableStateFlow<String?>(null)
    val errorMessage: StateFlow<String?> = _errorMessage.asStateFlow()
    
    private var isRefreshing = false
    
    val filteredLeads: StateFlow<List<Lead>> = combine(
        _leads,
        _searchText
    ) { leads, search ->
        val filtered = if (search.isBlank()) {
            leads
        } else {
            leads.filter {
                it.displayName.contains(search, ignoreCase = true) ||
                it.emailPrimary?.contains(search, ignoreCase = true) == true ||
                it.organizationName?.contains(search, ignoreCase = true) == true
            }
        }
        // Sort by most recent first
        filtered.sortedByDescending { it.createdAtDate?.time ?: 0L }
    }.stateIn(
        scope = viewModelScope,
        started = SharingStarted.WhileSubscribed(5000),
        initialValue = emptyList()
    )
    
    fun updateSearchText(text: String) {
        _searchText.value = text
    }
    
    fun loadLeads() {
        viewModelScope.launch {
            try {
                syncWithRetry()
                loadLocalLeads()
            } catch (e: Exception) {
                // Handle error
            }
        }
    }
    
    suspend fun refreshFromServer() {
        // Prevent concurrent refreshes
        if (isRefreshing) return
        
        isRefreshing = true
        _isLoading.value = true
        _errorMessage.value = null
        
        try {
            syncFromServer()
            loadLocalLeads()
            _isLoading.value = false
            isRefreshing = false
        } catch (e: Exception) {
            isRefreshing = false
            // Handle cancellation errors silently
            if (e is CancellationException || 
                (e is IOException && e.message?.contains("cancelled") == true)) {
                _isLoading.value = false
                return
            }
            _errorMessage.value = e.message
            _isLoading.value = false
        }
    }
    
    suspend fun convertToContact(leadId: String): Result<Contact> {
        return try {
            val contact = leadService.convertToContact(leadId)
            // Refresh leads after conversion
            refreshFromServer()
            Result.success(contact)
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    private suspend fun syncWithRetry() {
        try {
            syncFromServer()
        } catch (e: CancellationException) {
            // Retry once after delay
            delay(500)
            syncFromServer()
        }
    }
    
    private suspend fun syncFromServer() {
        // Sync logic
    }
    
    private fun loadLocalLeads() {
        viewModelScope.launch {
            val entities = leadRepository.getAllLeadsSync()
            val loaded = entities.map { it.toDomain() }
            // Sort by most recent first
            _leads.value = loaded.sortedByDescending { 
                it.createdAtDate?.time ?: 0L 
            }
        }
    }
}
```

## UI Screens

### LeadsDashboardScreen

- List of leads
- Search bar
- Pull-to-refresh
- Lead item click navigation
- Sort by most recent first
- Display formatted dates

### LeadDetailsScreen

- Full lead information
- Source information section
- Captured date display
- Convert to contact button
- Status display

## Dependencies

```kotlin
dependencies {
    // Room
    implementation("androidx.room:room-runtime:2.6.1")
    implementation("androidx.room:room-ktx:2.6.1")
}
```

## Integration Points

- **Contacts Module**: Converts leads to contacts
- **SyncManager**: Syncs leads with server

