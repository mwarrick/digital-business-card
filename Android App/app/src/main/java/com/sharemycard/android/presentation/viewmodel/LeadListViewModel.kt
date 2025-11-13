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
                leadRepository.deleteLead(lead)
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(errorMessage = "Failed to delete lead: ${e.message}")
                }
            }
        }
    }
}

data class LeadListUiState(
    val isLoading: Boolean = true,
    val isEmpty: Boolean = false,
    val isRefreshing: Boolean = false,
    val errorMessage: String? = null
)

