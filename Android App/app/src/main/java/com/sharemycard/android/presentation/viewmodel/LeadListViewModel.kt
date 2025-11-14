package com.sharemycard.android.presentation.viewmodel

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.sharemycard.android.domain.models.Lead
import com.sharemycard.android.domain.repository.LeadRepository
import com.sharemycard.android.domain.sync.SyncManager
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.*
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class LeadListViewModel @Inject constructor(
    private val leadRepository: LeadRepository,
    private val contactRepository: com.sharemycard.android.domain.repository.ContactRepository,
    private val syncManager: SyncManager
) : ViewModel() {
    
    private val _uiState = MutableStateFlow(LeadListUiState())
    val uiState: StateFlow<LeadListUiState> = _uiState.asStateFlow()
    
    val leads: StateFlow<List<Lead>> = leadRepository.getAllLeads()
        .stateIn(
            scope = viewModelScope,
            started = SharingStarted.WhileSubscribed(5000),
            initialValue = emptyList()
        )
    
    private val _searchText = MutableStateFlow("")
    val searchText: StateFlow<String> = _searchText.asStateFlow()
    
    val filteredLeads: StateFlow<List<Lead>> = combine(
        leads,
        _searchText
    ) { leadsList, search ->
        if (search.isBlank()) {
            leadsList
        } else {
            leadsList.filter {
                it.displayName.contains(search, ignoreCase = true) ||
                it.organizationName?.contains(search, ignoreCase = true) == true ||
                it.jobTitle?.contains(search, ignoreCase = true) == true ||
                it.emailPrimary?.contains(search, ignoreCase = true) == true ||
                it.workPhone?.contains(search, ignoreCase = true) == true ||
                it.mobilePhone?.contains(search, ignoreCase = true) == true ||
                it.cardDisplayName.contains(search, ignoreCase = true)
            }
        }
    }.stateIn(
        scope = viewModelScope,
        started = SharingStarted.WhileSubscribed(5000),
        initialValue = emptyList()
    )
    
    init {
        observeLeads()
    }
    
    private fun observeLeads() {
        viewModelScope.launch {
            leads.collect { leadsList ->
                _uiState.update {
                    it.copy(
                        isLoading = false,
                        isEmpty = leadsList.isEmpty()
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
            _uiState.update { it.copy(isRefreshing = true, errorMessage = null) }
            try {
                // Trigger a sync to get latest data from server
                val result = syncManager.performFullSync()
                if (!result.success) {
                    _uiState.update {
                        it.copy(
                            isRefreshing = false,
                            errorMessage = result.message
                        )
                    }
                } else {
                    _uiState.update { it.copy(isRefreshing = false) }
                }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(
                        isRefreshing = false,
                        errorMessage = "Refresh failed: ${e.message}"
                    )
                }
            }
        }
    }
    
    fun deleteLead(lead: Lead) {
        viewModelScope.launch {
            try {
                // Check if lead is converted to a contact
                if (lead.isConverted) {
                    // Find the associated contact (if available, for showing link)
                    val contact = contactRepository.getContactByLeadId(lead.id)
                    
                    // Always prevent deletion of converted leads, regardless of whether contact is found
                    _uiState.update {
                        it.copy(
                            showDeleteWarning = true,
                            warningLead = lead,
                            warningContactId = contact?.id // May be null, but still show warning
                        )
                    }
                    return@launch
                }
                
                // Lead is not converted, proceed with deletion
                performDeleteLead(lead)
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(errorMessage = "Failed to delete lead: ${e.message}")
                }
            }
        }
    }
    
    fun dismissDeleteWarning() {
        _uiState.update {
            it.copy(
                showDeleteWarning = false,
                warningLead = null,
                warningContactId = null
            )
        }
    }
    
    fun confirmDeleteLead() {
        val lead = _uiState.value.warningLead ?: return
        dismissDeleteWarning()
        viewModelScope.launch {
            performDeleteLead(lead)
        }
    }
    
    private suspend fun performDeleteLead(lead: Lead) {
        try {
            // Mark as deleted locally
            val updatedLead = lead.copy(
                isDeleted = true,
                updatedAt = System.currentTimeMillis().toString()
            )
            leadRepository.updateLead(updatedLead)
            
            // Sync immediately with server (pushRecentChanges now handles lead deletions)
            val syncResult = syncManager.pushRecentChanges()
            if (!syncResult.success) {
                _uiState.update {
                    it.copy(errorMessage = "Lead deleted locally, but sync failed: ${syncResult.message}")
                }
            }
        } catch (e: Exception) {
            _uiState.update {
                it.copy(errorMessage = "Failed to delete lead: ${e.message}")
            }
        }
    }
}

data class LeadListUiState(
    val isLoading: Boolean = true,
    val isEmpty: Boolean = false,
    val isRefreshing: Boolean = false,
    val errorMessage: String? = null,
    val showDeleteWarning: Boolean = false,
    val warningLead: Lead? = null,
    val warningContactId: String? = null
)

