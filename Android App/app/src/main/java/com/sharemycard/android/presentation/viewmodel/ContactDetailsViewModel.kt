package com.sharemycard.android.presentation.viewmodel

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.sharemycard.android.domain.models.Contact
import com.sharemycard.android.domain.repository.ContactRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class ContactDetailsViewModel @Inject constructor(
    private val contactRepository: ContactRepository
) : ViewModel() {
    
    private val _uiState = MutableStateFlow(ContactDetailsUiState())
    val uiState: StateFlow<ContactDetailsUiState> = _uiState.asStateFlow()
    
    fun loadContact(contactId: String) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            try {
                val contact = contactRepository.getContactById(contactId)
                _uiState.update {
                    it.copy(
                        isLoading = false,
                        contact = contact,
                        errorMessage = null
                    )
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
    
    fun deleteContact() {
        val contact = _uiState.value.contact ?: return
        viewModelScope.launch {
            try {
                contactRepository.deleteContact(contact)
                _uiState.update { it.copy(shouldNavigateBack = true) }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(errorMessage = "Failed to delete contact: ${e.message}")
                }
            }
        }
    }
}

data class ContactDetailsUiState(
    val isLoading: Boolean = false,
    val contact: Contact? = null,
    val errorMessage: String? = null,
    val shouldNavigateBack: Boolean = false
)

