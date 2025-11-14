package com.sharemycard.android.presentation.viewmodel

import android.util.Log
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.sharemycard.android.data.remote.api.CardApi
import com.sharemycard.android.domain.models.BusinessCard
import com.sharemycard.android.domain.models.EmailContact
import com.sharemycard.android.domain.models.PhoneContact
import com.sharemycard.android.domain.models.WebsiteLink
import com.sharemycard.android.domain.models.Address
import com.sharemycard.android.domain.repository.BusinessCardRepository
import com.sharemycard.android.util.DateParser
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import java.util.UUID
import javax.inject.Inject

@HiltViewModel
class CardDetailsViewModel @Inject constructor(
    private val businessCardRepository: BusinessCardRepository,
    private val cardApi: CardApi
) : ViewModel() {
    
    // Method to get raw updatedAt from database
    private suspend fun getLocalUpdatedAtFromDatabase(cardId: String): Long? {
        return businessCardRepository.getCardById(cardId)?.updatedAt
    }
    
    private val _uiState = MutableStateFlow(CardDetailsUiState())
    val uiState: StateFlow<CardDetailsUiState> = _uiState.asStateFlow()
    
    fun loadCard(cardId: String) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            try {
                val card = businessCardRepository.getCardById(cardId)
                
                // Fetch server data if serverCardId exists
                var serverCreatedAt: String? = null
                var serverUpdatedAt: String? = null
                var syncEvaluation: SyncEvaluation? = null
                if (card != null && !card.serverCardId.isNullOrBlank()) {
                    try {
                        val response = cardApi.getCard(card.serverCardId!!)
                        if (response.isSuccess && response.data != null) {
                            serverCreatedAt = response.data.createdAt
                            serverUpdatedAt = response.data.updatedAt
                            Log.d("CardDetailsViewModel", "Fetched server timestamps for card ${card.serverCardId}")
                            Log.d("CardDetailsViewModel", "  Server createdAt: $serverCreatedAt")
                            Log.d("CardDetailsViewModel", "  Server updatedAt: $serverUpdatedAt")
                            
                            // CRITICAL: Get the LOCAL updatedAt directly from the database
                            // Do NOT use card.updatedAt as it may have been modified during sync
                            val localUpdatedAt = getLocalUpdatedAtFromDatabase(cardId)
                            
                            Log.d("CardDetailsViewModel", "  Local updatedAt from database: $localUpdatedAt (${localUpdatedAt?.let { java.util.Date(it) }})")
                            Log.d("CardDetailsViewModel", "  Card object updatedAt (may be stale): ${card.updatedAt} (${java.util.Date(card.updatedAt)})")
                            
                            // Evaluate sync decision using the raw database value and server value
                            syncEvaluation = evaluateSync(localUpdatedAt, serverUpdatedAt)
                        }
                    } catch (e: Exception) {
                        Log.w("CardDetailsViewModel", "Failed to fetch server data: ${e.message}")
                    }
                }
                
                _uiState.update {
                    it.copy(
                        isLoading = false,
                        card = card,
                        serverCreatedAt = serverCreatedAt,
                        serverUpdatedAt = serverUpdatedAt,
                        syncEvaluation = syncEvaluation,
                        errorMessage = null
                    )
                }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(
                        isLoading = false,
                        errorMessage = "Failed to load card: ${e.message}"
                    )
                }
            }
        }
    }
    
    fun deleteCard() {
        val card = _uiState.value.card ?: return
        viewModelScope.launch {
            try {
                businessCardRepository.deleteCard(card)
                _uiState.update { it.copy(shouldNavigateBack = true) }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(errorMessage = "Failed to delete card: ${e.message}")
                }
            }
        }
    }
    
    fun duplicateCard() {
        val originalCard = _uiState.value.card ?: return
        viewModelScope.launch {
            try {
                val currentTime = System.currentTimeMillis()
                
                // Create new IDs for all contact items
                val duplicatedEmails = originalCard.additionalEmails.map { email ->
                    EmailContact(
                        id = UUID.randomUUID().toString(),
                        email = email.email,
                        type = email.type,
                        label = email.label,
                        isPrimary = email.isPrimary
                    )
                }
                
                val duplicatedPhones = originalCard.additionalPhones.map { phone ->
                    PhoneContact(
                        id = UUID.randomUUID().toString(),
                        phoneNumber = phone.phoneNumber,
                        type = phone.type,
                        label = phone.label
                    )
                }
                
                val duplicatedWebsites = originalCard.websiteLinks.map { website ->
                    WebsiteLink(
                        id = UUID.randomUUID().toString(),
                        name = website.name,
                        url = website.url,
                        description = website.description,
                        isPrimary = website.isPrimary
                    )
                }
                
                // Copy address if it exists
                val duplicatedAddress = originalCard.address?.let { address ->
                    Address(
                        street = address.street,
                        city = address.city,
                        state = address.state,
                        zipCode = address.zipCode,
                        country = address.country
                    )
                }
                
                // Create the duplicated card with new ID and timestamps
                val duplicatedCard = BusinessCard(
                    id = UUID.randomUUID().toString(),
                    firstName = originalCard.firstName,
                    lastName = originalCard.lastName,
                    phoneNumber = originalCard.phoneNumber,
                    additionalEmails = duplicatedEmails,
                    additionalPhones = duplicatedPhones,
                    websiteLinks = duplicatedWebsites,
                    address = duplicatedAddress,
                    companyName = originalCard.companyName,
                    jobTitle = originalCard.jobTitle,
                    bio = originalCard.bio,
                    profilePhoto = originalCard.profilePhoto?.copyOf(), // Deep copy byte array
                    companyLogo = originalCard.companyLogo?.copyOf(), // Deep copy byte array
                    coverGraphic = originalCard.coverGraphic?.copyOf(), // Deep copy byte array
                    profilePhotoPath = originalCard.profilePhotoPath,
                    companyLogoPath = originalCard.companyLogoPath,
                    coverGraphicPath = originalCard.coverGraphicPath,
                    theme = originalCard.theme,
                    createdAt = currentTime,
                    updatedAt = currentTime,
                    isActive = originalCard.isActive,
                    serverCardId = null, // New card, no server ID yet
                    isDeleted = false
                )
                
                // Insert the duplicated card
                businessCardRepository.insertCard(duplicatedCard)
                
                Log.d("CardDetailsViewModel", "✅ Card duplicated: ${originalCard.fullName} -> ${duplicatedCard.fullName}")
                Log.d("CardDetailsViewModel", "   Original ID: ${originalCard.id}")
                Log.d("CardDetailsViewModel", "   Duplicated ID: ${duplicatedCard.id}")
                
                // Update UI state to navigate to edit screen
                _uiState.update { it.copy(duplicatedCardId = duplicatedCard.id) }
                
                // Clear the duplicated card ID after a short delay to allow navigation
                kotlinx.coroutines.delay(100)
                _uiState.update { it.copy(duplicatedCardId = null) }
            } catch (e: Exception) {
                Log.e("CardDetailsViewModel", "❌ Failed to duplicate card: ${e.message}", e)
                _uiState.update {
                    it.copy(errorMessage = "Failed to duplicate card: ${e.message}")
                }
            }
        }
    }
    
    /**
     * Evaluate whether a sync should happen based on local and server timestamps
     * 
     * @param localUpdatedAt The raw updatedAt timestamp from the local database (Long timestamp in milliseconds)
     * @param serverUpdatedAtString The raw updatedAt string from the server API
     */
    private fun evaluateSync(localUpdatedAt: Long?, serverUpdatedAtString: String?): SyncEvaluation {
        if (localUpdatedAt == null) {
            return SyncEvaluation(
                shouldSync = false,
                reason = "No local timestamp available",
                localTimestamp = 0L,
                serverTimestamp = null,
                difference = null
            )
        }
        
        if (serverUpdatedAtString.isNullOrBlank()) {
            return SyncEvaluation(
                shouldSync = false,
                reason = "No server timestamp available",
                localTimestamp = localUpdatedAt,
                serverTimestamp = null,
                difference = null
            )
        }
        
        // CRITICAL: Use the raw local updatedAt from database and parse the server timestamp
        val serverUpdatedAt = DateParser.parseServerDate(serverUpdatedAtString)
        
        if (serverUpdatedAt == null) {
            return SyncEvaluation(
                shouldSync = false,
                reason = "Failed to parse server timestamp",
                localTimestamp = localUpdatedAt,
                serverTimestamp = null,
                difference = null
            )
        }
        
        // Format both timestamps for comparison logging
        val localDate = java.util.Date(localUpdatedAt)
        val serverDate = java.util.Date(serverUpdatedAt)
        val dateFormat = java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss.SSS zzz", java.util.Locale.US)
        dateFormat.timeZone = java.util.TimeZone.getDefault()
        
        // Also format in UTC to see the actual timestamp values
        val utcDateFormat = java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss.SSS 'UTC'", java.util.Locale.US)
        utcDateFormat.timeZone = java.util.TimeZone.getTimeZone("UTC")
        
        // Extract time components for detailed comparison
        val localCalendar = java.util.Calendar.getInstance().apply { time = localDate }
        val serverCalendar = java.util.Calendar.getInstance().apply { time = serverDate }
        
        Log.d("CardDetailsViewModel", "🔍 DETAILED COMPARISON:")
        Log.d("CardDetailsViewModel", "  ┌─────────────────────────────────────────┐")
        Log.d("CardDetailsViewModel", "  │ LOCAL TIMESTAMP                         │")
        Log.d("CardDetailsViewModel", "  ├─────────────────────────────────────────┤")
        Log.d("CardDetailsViewModel", "  │ Long value: $localUpdatedAt")
        Log.d("CardDetailsViewModel", "  │ Local timezone: ${dateFormat.format(localDate)}")
        Log.d("CardDetailsViewModel", "  │ UTC timezone: ${utcDateFormat.format(localDate)}")
        Log.d("CardDetailsViewModel", "  │ Year: ${localCalendar.get(java.util.Calendar.YEAR)}")
        Log.d("CardDetailsViewModel", "  │ Month: ${localCalendar.get(java.util.Calendar.MONTH) + 1}")
        Log.d("CardDetailsViewModel", "  │ Day: ${localCalendar.get(java.util.Calendar.DAY_OF_MONTH)}")
        Log.d("CardDetailsViewModel", "  │ Hour: ${localCalendar.get(java.util.Calendar.HOUR_OF_DAY)}")
        Log.d("CardDetailsViewModel", "  │ Minute: ${localCalendar.get(java.util.Calendar.MINUTE)}")
        Log.d("CardDetailsViewModel", "  │ Second: ${localCalendar.get(java.util.Calendar.SECOND)}")
        Log.d("CardDetailsViewModel", "  │ Millisecond: ${localCalendar.get(java.util.Calendar.MILLISECOND)}")
        Log.d("CardDetailsViewModel", "  └─────────────────────────────────────────┘")
        Log.d("CardDetailsViewModel", "  ┌─────────────────────────────────────────┐")
        Log.d("CardDetailsViewModel", "  │ SERVER TIMESTAMP                        │")
        Log.d("CardDetailsViewModel", "  ├─────────────────────────────────────────┤")
        Log.d("CardDetailsViewModel", "  │ Raw string: $serverUpdatedAtString")
        Log.d("CardDetailsViewModel", "  │ Long value: $serverUpdatedAt")
        Log.d("CardDetailsViewModel", "  │ Local timezone: ${dateFormat.format(serverDate)}")
        Log.d("CardDetailsViewModel", "  │ UTC timezone: ${utcDateFormat.format(serverDate)}")
        Log.d("CardDetailsViewModel", "  │ Year: ${serverCalendar.get(java.util.Calendar.YEAR)}")
        Log.d("CardDetailsViewModel", "  │ Month: ${serverCalendar.get(java.util.Calendar.MONTH) + 1}")
        Log.d("CardDetailsViewModel", "  │ Day: ${serverCalendar.get(java.util.Calendar.DAY_OF_MONTH)}")
        Log.d("CardDetailsViewModel", "  │ Hour: ${serverCalendar.get(java.util.Calendar.HOUR_OF_DAY)}")
        Log.d("CardDetailsViewModel", "  │ Minute: ${serverCalendar.get(java.util.Calendar.MINUTE)}")
        Log.d("CardDetailsViewModel", "  │ Second: ${serverCalendar.get(java.util.Calendar.SECOND)}")
        Log.d("CardDetailsViewModel", "  │ Millisecond: ${serverCalendar.get(java.util.Calendar.MILLISECOND)}")
        Log.d("CardDetailsViewModel", "  └─────────────────────────────────────────┘")
        
        val difference = serverUpdatedAt - localUpdatedAt
        Log.d("CardDetailsViewModel", "  ┌─────────────────────────────────────────┐")
        Log.d("CardDetailsViewModel", "  │ COMPARISON RESULT                       │")
        Log.d("CardDetailsViewModel", "  ├─────────────────────────────────────────┤")
        Log.d("CardDetailsViewModel", "  │ Difference (ms): $difference")
        Log.d("CardDetailsViewModel", "  │ Difference (seconds): ${difference / 1000}")
        Log.d("CardDetailsViewModel", "  │ Difference (minutes): ${difference / 60000}")
        Log.d("CardDetailsViewModel", "  │ Difference (hours): ${difference / 3600000}")
        Log.d("CardDetailsViewModel", "  │ Server > Local? ${serverUpdatedAt > localUpdatedAt}")
        Log.d("CardDetailsViewModel", "  │ Server == Local? ${serverUpdatedAt == localUpdatedAt}")
        Log.d("CardDetailsViewModel", "  │ Server < Local? ${serverUpdatedAt < localUpdatedAt}")
        Log.d("CardDetailsViewModel", "  └─────────────────────────────────────────┘")
        
        // For display purposes, only show "SYNC NEEDED" if server is actually newer (not equal)
        // The actual sync logic uses >= to handle edge cases, but for user feedback, we're more strict
        val shouldSync = serverUpdatedAt > localUpdatedAt
        
        val reason = when {
            difference > 0 -> "Server is newer by ${difference / 1000}s - SYNC NEEDED"
            difference == 0L -> "Timestamps are equal - NO SYNC NEEDED (data is in sync)"
            else -> "Local is newer by ${-difference / 1000}s - NO SYNC"
        }
        
        Log.d("CardDetailsViewModel", "═══════════════════════════════════════════════════════")
        Log.d("CardDetailsViewModel", "Sync Evaluation")
        Log.d("CardDetailsViewModel", "  Local updatedAt: $localUpdatedAt")
        Log.d("CardDetailsViewModel", "  Local date: ${java.util.Date(localUpdatedAt)}")
        Log.d("CardDetailsViewModel", "  Server updatedAt string: $serverUpdatedAtString")
        Log.d("CardDetailsViewModel", "  Server updatedAt parsed: $serverUpdatedAt")
        Log.d("CardDetailsViewModel", "  Server date: ${java.util.Date(serverUpdatedAt)}")
        Log.d("CardDetailsViewModel", "  Difference: ${difference}ms (${difference / 1000}s)")
        Log.d("CardDetailsViewModel", "  Should sync: $shouldSync")
        Log.d("CardDetailsViewModel", "  Reason: $reason")
        Log.d("CardDetailsViewModel", "═══════════════════════════════════════════════════════")
        
        return SyncEvaluation(
            shouldSync = shouldSync,
            reason = reason,
            localTimestamp = localUpdatedAt,
            serverTimestamp = serverUpdatedAt,
            difference = difference
        )
    }
}

data class SyncEvaluation(
    val shouldSync: Boolean,
    val reason: String,
    val localTimestamp: Long,
    val serverTimestamp: Long?,
    val difference: Long?
)

data class CardDetailsUiState(
    val isLoading: Boolean = false,
    val card: BusinessCard? = null,
    val serverCreatedAt: String? = null,
    val serverUpdatedAt: String? = null,
    val syncEvaluation: SyncEvaluation? = null,
    val errorMessage: String? = null,
    val shouldNavigateBack: Boolean = false,
    val duplicatedCardId: String? = null // ID of duplicated card to navigate to edit
)

