package com.sharemycard.android.presentation.viewmodel

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.sharemycard.android.domain.models.BusinessCard
import com.sharemycard.android.domain.repository.BusinessCardRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class CardDetailsViewModel @Inject constructor(
    private val businessCardRepository: BusinessCardRepository
) : ViewModel() {
    
    private val _uiState = MutableStateFlow(CardDetailsUiState())
    val uiState: StateFlow<CardDetailsUiState> = _uiState.asStateFlow()
    
    fun loadCard(cardId: String) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            try {
                val card = businessCardRepository.getCardById(cardId)
                _uiState.update {
                    it.copy(
                        isLoading = false,
                        card = card,
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
}

data class CardDetailsUiState(
    val isLoading: Boolean = false,
    val card: BusinessCard? = null,
    val errorMessage: String? = null,
    val shouldNavigateBack: Boolean = false
)

