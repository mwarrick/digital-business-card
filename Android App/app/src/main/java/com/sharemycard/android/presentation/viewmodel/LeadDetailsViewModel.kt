package com.sharemycard.android.presentation.viewmodel

import android.util.Log
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.sharemycard.android.domain.models.Lead
import com.sharemycard.android.domain.repository.LeadRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class LeadDetailsViewModel @Inject constructor(
    private val leadRepository: LeadRepository
) : ViewModel() {
    
    private val _uiState = MutableStateFlow(LeadDetailsUiState())
    val uiState: StateFlow<LeadDetailsUiState> = _uiState.asStateFlow()
    
    fun loadLead(leadId: String) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            try {
                val lead = leadRepository.getLeadById(leadId)
                _uiState.update {
                    it.copy(
                        isLoading = false,
                        lead = lead,
                        errorMessage = null
                    )
                }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(
                        isLoading = false,
                        errorMessage = "Failed to load lead: ${e.message}"
                    )
                }
            }
        }
    }
    
    fun deleteLead() {
        val lead = _uiState.value.lead ?: return
        viewModelScope.launch {
            try {
                leadRepository.deleteLead(lead)
                _uiState.update { it.copy(shouldNavigateBack = true) }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(errorMessage = "Failed to delete lead: ${e.message}")
                }
            }
        }
    }
    
    fun convertToContact() {
        val lead = _uiState.value.lead ?: return
        Log.d("LeadDetailsViewModel", "🔄 Starting conversion for lead: ${lead.id}")
        viewModelScope.launch {
            _uiState.update { it.copy(isConverting = true, errorMessage = null) }
            try {
                Log.d("LeadDetailsViewModel", "📞 Calling repository to convert lead")
                val contactId = leadRepository.convertLeadToContact(lead.id)
                Log.d("LeadDetailsViewModel", "✅ Conversion successful, contact ID: $contactId")
                // Reload the lead to get updated status
                loadLead(lead.id)
                _uiState.update { 
                    it.copy(
                        isConverting = false,
                        conversionSuccess = true,
                        convertedContactId = contactId
                    ) 
                }
            } catch (e: Exception) {
                Log.e("LeadDetailsViewModel", "❌ Conversion failed: ${e.message}", e)
                val errorMessage = e.message ?: "Unknown error occurred"
                _uiState.update {
                    it.copy(
                        isConverting = false,
                        errorMessage = "Failed to convert lead: $errorMessage"
                    )
                }
            }
        }
    }
}

data class LeadDetailsUiState(
    val isLoading: Boolean = false,
    val lead: Lead? = null,
    val errorMessage: String? = null,
    val shouldNavigateBack: Boolean = false,
    val isConverting: Boolean = false,
    val conversionSuccess: Boolean = false,
    val convertedContactId: String? = null
)

