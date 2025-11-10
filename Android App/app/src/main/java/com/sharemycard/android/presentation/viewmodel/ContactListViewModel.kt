package com.sharemycard.android.presentation.viewmodel

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.sharemycard.android.domain.models.Contact
import com.sharemycard.android.domain.repository.ContactRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.*
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class ContactListViewModel @Inject constructor(
    private val contactRepository: ContactRepository
) : ViewModel() {
    
    private val _uiState = MutableStateFlow(ContactListUiState())
    val uiState: StateFlow<ContactListUiState> = _uiState.asStateFlow()
    
    val contacts: StateFlow<List<Contact>> = contactRepository.getAllContacts()
        .stateIn(
            scope = viewModelScope,
            started = SharingStarted.WhileSubscribed(5000),
            initialValue = emptyList()
        )
    
    private val _searchText = MutableStateFlow("")
    val searchText: StateFlow<String> = _searchText.asStateFlow()
    
    val filteredContacts: StateFlow<List<Contact>> = combine(
        contacts,
        _searchText
    ) { contactsList, search ->
        if (search.isBlank()) {
            contactsList
        } else {
            contactsList.filter {
                it.fullName.contains(search, ignoreCase = true) ||
                it.company?.contains(search, ignoreCase = true) == true ||
                it.jobTitle?.contains(search, ignoreCase = true) == true ||
                it.email?.contains(search, ignoreCase = true) == true ||
                it.phone?.contains(search, ignoreCase = true) == true ||
                it.mobilePhone?.contains(search, ignoreCase = true) == true
            }
        }
    }.stateIn(
        scope = viewModelScope,
        started = SharingStarted.WhileSubscribed(5000),
        initialValue = emptyList()
    )
    
    init {
        observeContacts()
    }
    
    private fun observeContacts() {
        viewModelScope.launch {
            contacts.collect { contactsList ->
                _uiState.update {
                    it.copy(
                        isLoading = false,
                        isEmpty = contactsList.isEmpty()
                    )
                }
            }
        }
    }
    
    fun updateSearchText(text: String) {
        _searchText.value = text
    }
    
    fun refresh() {
        viewModelScope.launch {
            _uiState.update { it.copy(isRefreshing = true) }
            // Force refresh by getting current count
            contactRepository.getContactCount()
            _uiState.update { it.copy(isRefreshing = false) }
        }
    }
    
    fun deleteContact(contact: Contact) {
        viewModelScope.launch {
            try {
                contactRepository.deleteContact(contact)
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(errorMessage = "Failed to delete contact: ${e.message}")
                }
            }
        }
    }
}

data class ContactListUiState(
    val isLoading: Boolean = true,
    val isEmpty: Boolean = false,
    val isRefreshing: Boolean = false,
    val errorMessage: String? = null
)

