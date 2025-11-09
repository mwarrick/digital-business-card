package com.sharemycard.android.presentation.viewmodel

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.sharemycard.android.domain.models.Contact
import com.sharemycard.android.domain.repository.ContactRepository
import com.sharemycard.android.domain.sync.SyncManager
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
class ContactEditViewModel @Inject constructor(
    private val contactRepository: ContactRepository,
    private val syncManager: SyncManager
) : ViewModel() {
    
    private val _uiState = MutableStateFlow(ContactEditUiState())
    val uiState: StateFlow<ContactEditUiState> = _uiState.asStateFlow()
    
    fun initialize(contactId: String?) {
        if (contactId != null) {
            loadContact(contactId)
        } else {
            // New contact - initialize with defaults
            _uiState.update { 
                it.copy(
                    isNewContact = true,
                    contactId = null,
                    firstName = "",
                    lastName = "",
                    email = "",
                    phone = "",
                    mobilePhone = "",
                    company = "",
                    jobTitle = "",
                    address = "",
                    city = "",
                    state = "",
                    zipCode = "",
                    country = "",
                    website = "",
                    notes = "",
                    birthdate = "",
                    source = "manual"
                )
            }
        }
    }
    
    private fun loadContact(contactId: String) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            try {
                val contact = contactRepository.getContactById(contactId)
                if (contact != null) {
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            isNewContact = false,
                            contactId = contactId,
                            firstName = contact.firstName,
                            lastName = contact.lastName,
                            email = contact.email ?: "",
                            phone = contact.phone ?: "",
                            mobilePhone = contact.mobilePhone ?: "",
                            company = contact.company ?: "",
                            jobTitle = contact.jobTitle ?: "",
                            address = contact.address ?: "",
                            city = contact.city ?: "",
                            state = contact.state ?: "",
                            zipCode = contact.zipCode ?: "",
                            country = contact.country ?: "",
                            website = contact.website ?: "",
                            notes = contact.notes ?: "",
                            birthdate = contact.birthdate ?: "",
                            source = contact.source ?: "manual",
                            errorMessage = null
                        )
                    }
                } else {
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            errorMessage = "Contact not found"
                        )
                    }
                }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(
                        isLoading = false,
                        errorMessage = "Failed to load contact: ${e.message}"
                    )
                }
            }
        }
    }
    
    // Field updates
    fun updateFirstName(value: String) = _uiState.update { it.copy(firstName = value) }
    fun updateLastName(value: String) = _uiState.update { it.copy(lastName = value) }
    fun updateEmail(value: String) = _uiState.update { it.copy(email = value) }
    fun updatePhone(value: String) = _uiState.update { it.copy(phone = value) }
    fun updateMobilePhone(value: String) = _uiState.update { it.copy(mobilePhone = value) }
    fun updateCompany(value: String) = _uiState.update { it.copy(company = value) }
    fun updateJobTitle(value: String) = _uiState.update { it.copy(jobTitle = value) }
    fun updateAddress(value: String) = _uiState.update { it.copy(address = value) }
    fun updateCity(value: String) = _uiState.update { it.copy(city = value) }
    fun updateState(value: String) = _uiState.update { it.copy(state = value) }
    fun updateZipCode(value: String) = _uiState.update { it.copy(zipCode = value) }
    fun updateCountry(value: String) = _uiState.update { it.copy(country = value) }
    fun updateWebsite(value: String) = _uiState.update { it.copy(website = value) }
    fun updateNotes(value: String) = _uiState.update { it.copy(notes = value) }
    fun updateBirthdate(value: String) = _uiState.update { it.copy(birthdate = value) }
    
    fun saveContact() {
        viewModelScope.launch {
            _uiState.update { it.copy(isSaving = true, errorMessage = null) }
            
            val state = _uiState.value
            
            // Validation
            if (state.firstName.isBlank() || state.lastName.isBlank()) {
                _uiState.update {
                    it.copy(
                        isSaving = false,
                        errorMessage = "First Name and Last Name are required."
                    )
                }
                return@launch
            }
            
            try {
                val contactId = state.contactId ?: UUID.randomUUID().toString()
                val now = System.currentTimeMillis()
                val nowString = DateParser.formatServerDate(now)
                
                // Get existing contact to preserve dates if editing
                val existingContact = if (!state.isNewContact && state.contactId != null) {
                    contactRepository.getContactById(state.contactId)
                } else {
                    null
                }
                
                // Format dates as MySQL DATETIME strings
                val createdAt = existingContact?.createdAt ?: nowString
                val updatedAt = nowString
                
                val contact = Contact(
                    id = contactId,
                    firstName = state.firstName,
                    lastName = state.lastName,
                    email = state.email.takeIf { it.isNotBlank() },
                    phone = state.phone.takeIf { it.isNotBlank() },
                    mobilePhone = state.mobilePhone.takeIf { it.isNotBlank() },
                    company = state.company.takeIf { it.isNotBlank() },
                    jobTitle = state.jobTitle.takeIf { it.isNotBlank() },
                    address = state.address.takeIf { it.isNotBlank() },
                    city = state.city.takeIf { it.isNotBlank() },
                    state = state.state.takeIf { it.isNotBlank() },
                    zipCode = state.zipCode.takeIf { it.isNotBlank() },
                    country = state.country.takeIf { it.isNotBlank() },
                    website = state.website.takeIf { it.isNotBlank() },
                    notes = state.notes.takeIf { it.isNotBlank() },
                    birthdate = state.birthdate.takeIf { it.isNotBlank() },
                    source = state.source,
                    createdAt = createdAt,
                    updatedAt = updatedAt
                )
                
                // Save to local database
                if (state.isNewContact) {
                    contactRepository.insertContact(contact)
                } else {
                    contactRepository.updateContact(contact)
                }
                
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
                        errorMessage = "Failed to save contact: ${e.message}"
                    )
                }
            }
        }
    }
    
    fun clearError() {
        _uiState.update { it.copy(errorMessage = null) }
    }
}

data class ContactEditUiState(
    val isLoading: Boolean = false,
    val isSaving: Boolean = false,
    val isNewContact: Boolean = true,
    val contactId: String? = null,
    
    val firstName: String = "",
    val lastName: String = "",
    val email: String = "",
    val phone: String = "",
    val mobilePhone: String = "",
    val company: String = "",
    val jobTitle: String = "",
    val address: String = "",
    val city: String = "",
    val state: String = "",
    val zipCode: String = "",
    val country: String = "",
    val website: String = "",
    val notes: String = "",
    val birthdate: String = "",
    val source: String = "manual",
    
    val errorMessage: String? = null,
    val shouldNavigateBack: Boolean = false
)

