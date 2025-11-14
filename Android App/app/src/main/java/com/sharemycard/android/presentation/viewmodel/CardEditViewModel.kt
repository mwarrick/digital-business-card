package com.sharemycard.android.presentation.viewmodel

import android.graphics.Bitmap
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.sharemycard.android.data.remote.api.ApiConfig
import com.sharemycard.android.data.remote.api.CardApi
import com.sharemycard.android.domain.models.*
import com.sharemycard.android.domain.repository.BusinessCardRepository
import com.sharemycard.android.domain.repository.MediaRepository
import com.sharemycard.android.domain.sync.SyncManager
import com.sharemycard.android.util.CardThemes
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import java.util.UUID
import javax.inject.Inject

@HiltViewModel
class CardEditViewModel @Inject constructor(
    private val businessCardRepository: BusinessCardRepository,
    private val mediaRepository: MediaRepository,
    private val syncManager: SyncManager,
    private val cardApi: CardApi
) : ViewModel() {
    
    private val _uiState = MutableStateFlow(CardEditUiState())
    val uiState: StateFlow<CardEditUiState> = _uiState.asStateFlow()
    
    fun initialize(cardId: String?) {
        if (cardId != null) {
            loadCard(cardId)
        } else {
            // New card - initialize with defaults
            _uiState.update { it.copy(isNewCard = true) }
        }
    }
    
    private fun loadCard(cardId: String) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            try {
                val card = businessCardRepository.getCardById(cardId)
                if (card != null) {
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            cardId = cardId,
                            firstName = card.firstName,
                            lastName = card.lastName,
                            phoneNumber = card.phoneNumber,
                            companyName = card.companyName ?: "",
                            jobTitle = card.jobTitle ?: "",
                            bio = card.bio ?: "",
                            additionalEmails = card.additionalEmails.toMutableList(),
                            additionalPhones = card.additionalPhones.toMutableList(),
                            websiteLinks = card.websiteLinks.toMutableList(),
                            address = card.address,
                            profilePhotoPath = card.profilePhotoPath,
                            companyLogoPath = card.companyLogoPath,
                            coverGraphicPath = card.coverGraphicPath,
                            theme = card.theme ?: CardThemes.defaultTheme.id,
                            isActive = card.isActive,
                            isNewCard = false
                        )
                    }
                } else {
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            errorMessage = "Card not found"
                        )
                    }
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
    
    // Field updates
    fun updateFirstName(value: String) {
        _uiState.update { it.copy(firstName = value) }
    }
    
    fun updateLastName(value: String) {
        _uiState.update { it.copy(lastName = value) }
    }
    
    fun updatePhoneNumber(value: String) {
        _uiState.update { it.copy(phoneNumber = value) }
    }
    
    fun updateCompanyName(value: String) {
        _uiState.update { it.copy(companyName = value) }
    }
    
    fun updateJobTitle(value: String) {
        _uiState.update { it.copy(jobTitle = value) }
    }
    
    fun updateBio(value: String) {
        _uiState.update { it.copy(bio = value) }
    }
    
    fun updateTheme(themeId: String) {
        _uiState.update { it.copy(theme = themeId) }
        // Auto-save theme change
        saveThemeChange(themeId)
    }
    
    // Save theme change automatically
    private fun saveThemeChange(themeId: String) {
        viewModelScope.launch {
            try {
                val state = _uiState.value
                
                // Validate required fields before saving
                if (state.firstName.isBlank() || state.lastName.isBlank() || state.phoneNumber.isBlank()) {
                    android.util.Log.w("CardEditViewModel", "Cannot save theme - card data incomplete")
                    return@launch
                }
                
                // Get or create card ID
                val cardId = state.cardId ?: UUID.randomUUID().toString()
                
                // Get existing card to preserve serverCardId if editing
                val existingCard = if (!state.isNewCard && state.cardId != null) {
                    businessCardRepository.getCardById(state.cardId)
                } else {
                    null
                }
                
                // Save card data with new theme
                val currentTime = System.currentTimeMillis()
                val card = BusinessCard(
                    id = cardId,
                    firstName = state.firstName,
                    lastName = state.lastName,
                    phoneNumber = state.phoneNumber,
                    companyName = state.companyName.takeIf { it.isNotBlank() },
                    jobTitle = state.jobTitle.takeIf { it.isNotBlank() },
                    bio = state.bio.takeIf { it.isNotBlank() },
                    additionalEmails = state.additionalEmails,
                    additionalPhones = state.additionalPhones,
                    websiteLinks = state.websiteLinks,
                    address = state.address,
                    profilePhotoPath = state.profilePhotoPath,
                    companyLogoPath = state.companyLogoPath,
                    coverGraphicPath = state.coverGraphicPath,
                    theme = themeId, // Use the new theme
                    isActive = state.isActive,
                    createdAt = existingCard?.createdAt ?: currentTime,
                    updatedAt = currentTime,
                    serverCardId = existingCard?.serverCardId
                )
                
                // Save to local database
                if (state.isNewCard) {
                    businessCardRepository.insertCard(card)
                    _uiState.update { it.copy(cardId = cardId, isNewCard = false) }
                    
                    // For new cards, sync first to get serverCardId, then sync again with theme
                    kotlinx.coroutines.delay(200)
                    val syncResult = syncManager.pushRecentChanges()
                    
                    // Get the synced card to check if it has serverCardId now
                    val syncedCard = businessCardRepository.getCardById(cardId)
                    if (syncedCard?.serverCardId != null) {
                        // Update the card with theme again (in case server returned different data)
                        val cardWithTheme = syncedCard.copy(theme = themeId, updatedAt = System.currentTimeMillis())
                        businessCardRepository.updateCard(cardWithTheme)
                        // Sync again with the theme
                        kotlinx.coroutines.delay(200)
                        syncManager.pushRecentChanges()
                    }
                } else {
                    // Ensure updatedAt is set to current time to trigger sync
                    val currentTime = System.currentTimeMillis()
                    val cardWithTimestamp = card.copy(updatedAt = currentTime)
                    
                    businessCardRepository.updateCard(cardWithTimestamp)
                    
                    // Sync the change - delay to ensure DB write completes
                    kotlinx.coroutines.delay(500)
                    val syncResult = syncManager.pushRecentChanges()
                    
                    // If sync didn't work, try direct API call as fallback
                    if (!syncResult.success) {
                        val cardForFallback = businessCardRepository.getCardById(cardId)
                        if (cardForFallback?.serverCardId != null && cardForFallback.theme == themeId) {
                            try {
                                val dto = com.sharemycard.android.data.remote.mapper.BusinessCardDtoMapper.toDto(cardForFallback)
                                val updateResponse = cardApi.updateCard(cardForFallback.serverCardId!!, dto)
                                if (!updateResponse.isSuccess) {
                                    android.util.Log.w("CardEditViewModel", "Direct API call failed: ${updateResponse.message}")
                                }
                            } catch (e: Exception) {
                                android.util.Log.e("CardEditViewModel", "Direct API call exception: ${e.message}", e)
                            }
                        }
                    }
                }
            } catch (e: Exception) {
                android.util.Log.e("CardEditViewModel", "Error saving theme: ${e.message}", e)
            }
        }
    }
    
    fun updateIsActive(isActive: Boolean) {
        _uiState.update { it.copy(isActive = isActive) }
    }
    
    // Address updates
    fun updateStreet(value: String) {
        val currentAddress = _uiState.value.address ?: Address()
        _uiState.update {
            it.copy(address = currentAddress.copy(street = value.takeIf { it.isNotBlank() }))
        }
    }
    
    fun updateCity(value: String) {
        val currentAddress = _uiState.value.address ?: Address()
        _uiState.update {
            it.copy(address = currentAddress.copy(city = value.takeIf { it.isNotBlank() }))
        }
    }
    
    fun updateState(value: String) {
        val currentAddress = _uiState.value.address ?: Address()
        _uiState.update {
            it.copy(address = currentAddress.copy(state = value.takeIf { it.isNotBlank() }))
        }
    }
    
    fun updateZipCode(value: String) {
        val currentAddress = _uiState.value.address ?: Address()
        _uiState.update {
            it.copy(address = currentAddress.copy(zipCode = value.takeIf { it.isNotBlank() }))
        }
    }
    
    fun updateCountry(value: String) {
        val currentAddress = _uiState.value.address ?: Address()
        _uiState.update {
            it.copy(address = currentAddress.copy(country = value.takeIf { it.isNotBlank() }))
        }
    }
    
    // Image updates - automatically save and upload when image is selected
    fun setProfilePhoto(bitmap: Bitmap) {
        _uiState.update { it.copy(profilePhotoBitmap = bitmap) }
        uploadImageAndSave(ApiConfig.MediaType.PROFILE_PHOTO, bitmap)
    }
    
    fun setCompanyLogo(bitmap: Bitmap) {
        _uiState.update { it.copy(companyLogoBitmap = bitmap) }
        uploadImageAndSave(ApiConfig.MediaType.COMPANY_LOGO, bitmap)
    }
    
    fun setCoverGraphic(bitmap: Bitmap) {
        _uiState.update { it.copy(coverGraphicBitmap = bitmap) }
        uploadImageAndSave(ApiConfig.MediaType.COVER_GRAPHIC, bitmap)
    }
    
    // Upload a single image and save the card automatically
    private fun uploadImageAndSave(mediaType: String, bitmap: Bitmap) {
        viewModelScope.launch {
            try {
                val state = _uiState.value
                
                // Validate required fields before saving
                if (state.firstName.isBlank() || state.lastName.isBlank() || state.phoneNumber.isBlank()) {
                    android.util.Log.w("CardEditViewModel", "⚠️ Cannot upload image - card data incomplete")
                    _uiState.update { 
                        it.copy(errorMessage = "Please fill in required fields (First Name, Last Name, Phone) before uploading images")
                    }
                    return@launch
                }
                
                android.util.Log.d("CardEditViewModel", "📤 Auto-uploading $mediaType...")
                
                // Get or create card ID
                val cardId = state.cardId ?: UUID.randomUUID().toString()
                
                // Get existing card to preserve serverCardId if editing
                val existingCard = if (!state.isNewCard && state.cardId != null) {
                    businessCardRepository.getCardById(state.cardId)
                } else {
                    null
                }
                
                // Save card data first (to get serverCardId for new cards)
                val currentTime = System.currentTimeMillis()
                val card = BusinessCard(
                    id = cardId,
                    firstName = state.firstName,
                    lastName = state.lastName,
                    phoneNumber = state.phoneNumber,
                    companyName = state.companyName.takeIf { it.isNotBlank() },
                    jobTitle = state.jobTitle.takeIf { it.isNotBlank() },
                    bio = state.bio.takeIf { it.isNotBlank() },
                    additionalEmails = state.additionalEmails,
                    additionalPhones = state.additionalPhones,
                    websiteLinks = state.websiteLinks,
                    address = state.address,
                    profilePhotoPath = state.profilePhotoPath,
                    companyLogoPath = state.companyLogoPath,
                    coverGraphicPath = state.coverGraphicPath,
                    theme = state.theme,
                    isActive = state.isActive,
                    createdAt = existingCard?.createdAt ?: currentTime,
                    updatedAt = currentTime,
                    serverCardId = existingCard?.serverCardId
                )
                
                // Save to local database
                if (state.isNewCard) {
                    businessCardRepository.insertCard(card)
                    _uiState.update { it.copy(cardId = cardId, isNewCard = false) }
                    android.util.Log.d("CardEditViewModel", "✅ Card saved: ${card.fullName}")
                } else {
                    businessCardRepository.updateCard(card)
                    android.util.Log.d("CardEditViewModel", "✅ Card updated: ${card.fullName}")
                }
                
                // For new cards, sync first to get serverCardId
                var uploadCardId = existingCard?.serverCardId ?: cardId
                if (state.isNewCard && existingCard?.serverCardId == null) {
                    android.util.Log.d("CardEditViewModel", "🔄 Syncing new card to get serverCardId...")
                    kotlinx.coroutines.delay(200)
                    val syncResult = syncManager.pushRecentChanges()
                    val syncedCard = businessCardRepository.getCardById(cardId)
                    uploadCardId = syncedCard?.serverCardId ?: cardId
                    android.util.Log.d("CardEditViewModel", "🔄 Sync complete, using card ID: $uploadCardId")
                }
                
                // Upload the image
                android.util.Log.d("CardEditViewModel", "📤 Uploading $mediaType for card $uploadCardId...")
                mediaRepository.uploadImage(bitmap, uploadCardId, mediaType)
                    .fold(
                        onSuccess = { response ->
                            android.util.Log.d("CardEditViewModel", "✅ $mediaType uploaded: ${response.filename}")
                            
                            // Update card with new image path
                            val latestCard = businessCardRepository.getCardById(cardId)
                            if (latestCard != null) {
                                val updatedCard = latestCard.copy(
                                    profilePhotoPath = if (mediaType == ApiConfig.MediaType.PROFILE_PHOTO) response.filename else latestCard.profilePhotoPath,
                                    companyLogoPath = if (mediaType == ApiConfig.MediaType.COMPANY_LOGO) response.filename else latestCard.companyLogoPath,
                                    coverGraphicPath = if (mediaType == ApiConfig.MediaType.COVER_GRAPHIC) response.filename else latestCard.coverGraphicPath,
                                    updatedAt = System.currentTimeMillis()
                                )
                                businessCardRepository.updateCard(updatedCard)
                                
                                // Update UI state with path and clear bitmap
                                _uiState.update { currentState ->
                                    currentState.copy(
                                        profilePhotoPath = updatedCard.profilePhotoPath,
                                        companyLogoPath = updatedCard.companyLogoPath,
                                        coverGraphicPath = updatedCard.coverGraphicPath,
                                        profilePhotoBitmap = if (mediaType == ApiConfig.MediaType.PROFILE_PHOTO) null else currentState.profilePhotoBitmap,
                                        companyLogoBitmap = if (mediaType == ApiConfig.MediaType.COMPANY_LOGO) null else currentState.companyLogoBitmap,
                                        coverGraphicBitmap = if (mediaType == ApiConfig.MediaType.COVER_GRAPHIC) null else currentState.coverGraphicBitmap
                                    )
                                }
                                
                                // Sync the updated card
                                kotlinx.coroutines.delay(200)
                                syncManager.pushRecentChanges()
                                android.util.Log.d("CardEditViewModel", "✅ Card synced with $mediaType")
                            }
                        },
                        onFailure = { error ->
                            android.util.Log.e("CardEditViewModel", "❌ $mediaType upload failed: ${error.message}", error)
                            _uiState.update { 
                                it.copy(errorMessage = "Failed to upload $mediaType: ${error.message}")
                            }
                        }
                    )
            } catch (e: Exception) {
                android.util.Log.e("CardEditViewModel", "❌ Error uploading $mediaType: ${e.message}", e)
                _uiState.update { 
                    it.copy(errorMessage = "Error uploading $mediaType: ${e.message}")
                }
            }
        }
    }
    
    // Email/Phone/Website management
    fun addEmail(email: EmailContact) {
        _uiState.update {
            it.copy(additionalEmails = (it.additionalEmails + email).toMutableList())
        }
    }
    
    fun removeEmail(emailId: String) {
        _uiState.update {
            it.copy(additionalEmails = it.additionalEmails.filter { it.id != emailId }.toMutableList())
        }
    }
    
    fun addPhone(phone: PhoneContact) {
        _uiState.update {
            it.copy(additionalPhones = (it.additionalPhones + phone).toMutableList())
        }
    }
    
    fun removePhone(phoneId: String) {
        _uiState.update {
            it.copy(additionalPhones = it.additionalPhones.filter { it.id != phoneId }.toMutableList())
        }
    }
    
    fun addWebsite(website: WebsiteLink) {
        _uiState.update {
            it.copy(websiteLinks = (it.websiteLinks + website).toMutableList())
        }
    }
    
    fun removeWebsite(websiteId: String) {
        _uiState.update {
            it.copy(websiteLinks = it.websiteLinks.filter { it.id != websiteId }.toMutableList())
        }
    }
    
    // Validation
    private fun validate(): String? {
        val state = _uiState.value
        if (state.firstName.isBlank()) {
            return "First name is required"
        }
        if (state.lastName.isBlank()) {
            return "Last name is required"
        }
        if (state.phoneNumber.isBlank()) {
            return "Phone number is required"
        }
        return null
    }
    
    // Save card
    fun saveCard() {
        val validationError = validate()
        if (validationError != null) {
            _uiState.update { it.copy(errorMessage = validationError) }
            return
        }
        
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null) }
            
            try {
                val state = _uiState.value
                val cardId = state.cardId ?: UUID.randomUUID().toString()
                
                // Log image state before saving
                android.util.Log.d("CardEditViewModel", "💾 Starting saveCard()")
                android.util.Log.d("CardEditViewModel", "   Card ID: $cardId")
                android.util.Log.d("CardEditViewModel", "   Profile photo bitmap: ${if (state.profilePhotoBitmap != null) "EXISTS" else "null"}")
                android.util.Log.d("CardEditViewModel", "   Company logo bitmap: ${if (state.companyLogoBitmap != null) "EXISTS" else "null"}")
                android.util.Log.d("CardEditViewModel", "   Cover graphic bitmap: ${if (state.coverGraphicBitmap != null) "EXISTS" else "null"}")
                android.util.Log.d("CardEditViewModel", "   Current profile photo path: ${state.profilePhotoPath}")
                android.util.Log.d("CardEditViewModel", "   Current company logo path: ${state.companyLogoPath}")
                android.util.Log.d("CardEditViewModel", "   Current cover graphic path: ${state.coverGraphicPath}")
                
                // Get existing card to preserve serverCardId if editing
                val existingCard = if (!state.isNewCard && state.cardId != null) {
                    businessCardRepository.getCardById(state.cardId)
                } else {
                    null
                }
                
                // Create/update card (save first to get card ID for new cards)
                // Set updatedAt to current time to ensure sync picks it up
                val currentTime = System.currentTimeMillis()
                val card = BusinessCard(
                    id = cardId,
                    firstName = state.firstName,
                    lastName = state.lastName,
                    phoneNumber = state.phoneNumber,
                    companyName = state.companyName.takeIf { it.isNotBlank() },
                    jobTitle = state.jobTitle.takeIf { it.isNotBlank() },
                    bio = state.bio.takeIf { it.isNotBlank() },
                    additionalEmails = state.additionalEmails,
                    additionalPhones = state.additionalPhones,
                    websiteLinks = state.websiteLinks,
                    address = state.address,
                    profilePhotoPath = state.profilePhotoPath,
                    companyLogoPath = state.companyLogoPath,
                    coverGraphicPath = state.coverGraphicPath,
                    theme = state.theme,
                    isActive = state.isActive,
                    createdAt = existingCard?.createdAt ?: currentTime, // Preserve createdAt for existing cards
                    updatedAt = currentTime, // Explicitly set to current time for sync
                    serverCardId = existingCard?.serverCardId
                )
                
                // Save to local database first (needed for new cards to get ID)
                if (state.isNewCard) {
                    businessCardRepository.insertCard(card)
                    android.util.Log.d("CardEditViewModel", "✅ New card saved: ${card.fullName}, updatedAt: ${card.updatedAt}")
                } else {
                    businessCardRepository.updateCard(card)
                    android.util.Log.d("CardEditViewModel", "✅ Card updated: ${card.fullName}, updatedAt: ${card.updatedAt}")
                }
                
                // Note: Images are uploaded automatically when selected via uploadImageAndSave()
                // No need to upload images here - they're already handled
                
                // Get the final card state from DB to ensure we have the latest updatedAt
                val finalCard = businessCardRepository.getCardById(cardId)
                if (finalCard != null) {
                    android.util.Log.d("CardEditViewModel", "📤 Triggering sync for card: ${finalCard.fullName}, updatedAt: ${finalCard.updatedAt} (${java.util.Date(finalCard.updatedAt)})")
                }
                
                // Small delay to ensure database write completes before sync
                kotlinx.coroutines.delay(200)
                
                // Trigger sync
                val syncResult = syncManager.pushRecentChanges()
                android.util.Log.d("CardEditViewModel", "🔄 Sync result: success=${syncResult.success}, message=${syncResult.message}")
                
                _uiState.update {
                    it.copy(
                        isSaving = false,
                        shouldNavigateBack = true
                    )
                }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(
                        isSaving = false,
                        errorMessage = "Failed to save card: ${e.message}"
                    )
                }
            }
        }
    }
    
    private data class ImageUploadResults(
        val profilePhotoPath: String? = null,
        val companyLogoPath: String? = null,
        val coverGraphicPath: String? = null
    )
    
    private suspend fun uploadPendingImages(cardId: String): ImageUploadResults {
        val state = _uiState.value
        var updatedProfilePhotoPath: String? = null
        var updatedCompanyLogoPath: String? = null
        var updatedCoverGraphicPath: String? = null
        
        // Upload profile photo
        state.profilePhotoBitmap?.let { bitmap ->
            android.util.Log.d("CardEditViewModel", "📤 Uploading profile photo for card $cardId...")
            mediaRepository.uploadImage(bitmap, cardId, ApiConfig.MediaType.PROFILE_PHOTO)
                .fold(
                    onSuccess = { response ->
                        updatedProfilePhotoPath = response.filename
                        _uiState.update { it.copy(profilePhotoPath = response.filename) }
                        android.util.Log.d("CardEditViewModel", "✅ Profile photo uploaded: ${response.filename}")
                    },
                    onFailure = { error ->
                        android.util.Log.e("CardEditViewModel", "❌ Profile photo upload failed: ${error.message}", error)
                    }
                )
        }
        
        // Upload company logo
        state.companyLogoBitmap?.let { bitmap ->
            android.util.Log.d("CardEditViewModel", "📤 Uploading company logo for card $cardId...")
            mediaRepository.uploadImage(bitmap, cardId, ApiConfig.MediaType.COMPANY_LOGO)
                .fold(
                    onSuccess = { response ->
                        updatedCompanyLogoPath = response.filename
                        _uiState.update { it.copy(companyLogoPath = response.filename) }
                        android.util.Log.d("CardEditViewModel", "✅ Company logo uploaded: ${response.filename}")
                    },
                    onFailure = { error ->
                        android.util.Log.e("CardEditViewModel", "❌ Company logo upload failed: ${error.message}", error)
                    }
                )
        }
        
        // Upload cover graphic
        state.coverGraphicBitmap?.let { bitmap ->
            android.util.Log.d("CardEditViewModel", "📤 Uploading cover graphic for card $cardId...")
            mediaRepository.uploadImage(bitmap, cardId, ApiConfig.MediaType.COVER_GRAPHIC)
                .fold(
                    onSuccess = { response ->
                        updatedCoverGraphicPath = response.filename
                        _uiState.update { it.copy(coverGraphicPath = response.filename) }
                        android.util.Log.d("CardEditViewModel", "✅ Cover graphic uploaded: ${response.filename}")
                    },
                    onFailure = { error ->
                        android.util.Log.e("CardEditViewModel", "❌ Cover graphic upload failed: ${error.message}", error)
                    }
                )
        }
        
        return ImageUploadResults(
            profilePhotoPath = updatedProfilePhotoPath,
            companyLogoPath = updatedCompanyLogoPath,
            coverGraphicPath = updatedCoverGraphicPath
        )
    }
    
    fun clearError() {
        _uiState.update { it.copy(errorMessage = null) }
    }
}

data class CardEditUiState(
    val isLoading: Boolean = false,
    val isSaving: Boolean = false,
    val cardId: String? = null,
    val card: BusinessCard? = null,
    val isNewCard: Boolean = true,
    
    // Form fields
    val firstName: String = "",
    val lastName: String = "",
    val phoneNumber: String = "",
    val companyName: String = "",
    val jobTitle: String = "",
    val bio: String = "",
    
    // Additional contacts
    val additionalEmails: MutableList<EmailContact> = mutableListOf(),
    val additionalPhones: MutableList<PhoneContact> = mutableListOf(),
    val websiteLinks: MutableList<WebsiteLink> = mutableListOf(),
    
    // Address
    val address: Address? = null,
    
    // Media
    val profilePhotoPath: String? = null,
    val companyLogoPath: String? = null,
    val coverGraphicPath: String? = null,
    val profilePhotoBitmap: Bitmap? = null,
    val companyLogoBitmap: Bitmap? = null,
    val coverGraphicBitmap: Bitmap? = null,
    
    // Theme
    val theme: String = CardThemes.defaultTheme.id,
    
    // Status
    val isActive: Boolean = true,
    
    // UI state
    val errorMessage: String? = null,
    val shouldNavigateBack: Boolean = false
)

