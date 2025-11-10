package com.sharemycard.android.presentation.viewmodel

import android.graphics.Bitmap
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.sharemycard.android.data.remote.api.ApiConfig
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
    private val syncManager: SyncManager
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
    
    // Image updates
    fun setProfilePhoto(bitmap: Bitmap) {
        _uiState.update { it.copy(profilePhotoBitmap = bitmap) }
    }
    
    fun setCompanyLogo(bitmap: Bitmap) {
        _uiState.update { it.copy(companyLogoBitmap = bitmap) }
    }
    
    fun setCoverGraphic(bitmap: Bitmap) {
        _uiState.update { it.copy(coverGraphicBitmap = bitmap) }
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
                
                // Get existing card to preserve serverCardId if editing
                val existingCard = if (!state.isNewCard && state.cardId != null) {
                    businessCardRepository.getCardById(state.cardId)
                } else {
                    null
                }
                
                // Create/update card
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
                    serverCardId = existingCard?.serverCardId
                )
                
                // Save to local database first
                if (state.isNewCard) {
                    businessCardRepository.insertCard(card)
                } else {
                    businessCardRepository.updateCard(card)
                }
                
                // Upload images if needed
                val finalCardId = if (state.isNewCard) cardId else state.cardId!!
                uploadPendingImages(finalCardId)
                
                // Trigger sync
                syncManager.pushRecentChanges()
                
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
    
    private suspend fun uploadPendingImages(cardId: String) {
        val state = _uiState.value
        
        // Upload profile photo
        state.profilePhotoBitmap?.let { bitmap ->
            mediaRepository.uploadImage(bitmap, cardId, ApiConfig.MediaType.PROFILE_PHOTO)
                .getOrNull()?.let { response ->
                    _uiState.update { it.copy(profilePhotoPath = response.filename) }
                }
        }
        
        // Upload company logo
        state.companyLogoBitmap?.let { bitmap ->
            mediaRepository.uploadImage(bitmap, cardId, ApiConfig.MediaType.COMPANY_LOGO)
                .getOrNull()?.let { response ->
                    _uiState.update { it.copy(companyLogoPath = response.filename) }
                }
        }
        
        // Upload cover graphic
        state.coverGraphicBitmap?.let { bitmap ->
            mediaRepository.uploadImage(bitmap, cardId, ApiConfig.MediaType.COVER_GRAPHIC)
                .getOrNull()?.let { response ->
                    _uiState.update { it.copy(coverGraphicPath = response.filename) }
                }
        }
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

