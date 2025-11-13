package com.sharemycard.android.presentation.viewmodel

import android.util.Log
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.sharemycard.android.domain.models.BusinessCard
import com.sharemycard.android.domain.repository.BusinessCardRepository
import com.sharemycard.android.domain.sync.SyncManager
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.*
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class CardListViewModel @Inject constructor(
    private val businessCardRepository: BusinessCardRepository,
    private val syncManager: SyncManager
) : ViewModel() {
    
    private val _uiState = MutableStateFlow(CardListUiState())
    val uiState: StateFlow<CardListUiState> = _uiState.asStateFlow()
    
    val cards: StateFlow<List<BusinessCard>> = businessCardRepository.getAllCards()
        .stateIn(
            scope = viewModelScope,
            started = SharingStarted.WhileSubscribed(5000),
            initialValue = emptyList()
        )
    
    private val _searchText = MutableStateFlow("")
    val searchText: StateFlow<String> = _searchText.asStateFlow()
    
    val filteredCards: StateFlow<List<BusinessCard>> = combine(
        cards,
        _searchText
    ) { cardsList, search ->
        if (search.isBlank()) {
            cardsList
        } else {
            cardsList.filter {
                it.fullName.contains(search, ignoreCase = true) ||
                it.companyName?.contains(search, ignoreCase = true) == true ||
                it.jobTitle?.contains(search, ignoreCase = true) == true ||
                it.phoneNumber.contains(search, ignoreCase = true) ||
                it.additionalEmails.any { email -> email.email.contains(search, ignoreCase = true) }
            }
        }
    }.stateIn(
        scope = viewModelScope,
        started = SharingStarted.WhileSubscribed(5000),
        initialValue = emptyList()
    )
    
    init {
        observeCards()
    }
    
    private fun observeCards() {
        viewModelScope.launch {
            cards.collect { cardsList ->
                _uiState.update {
                    it.copy(
                        isLoading = false,
                        isEmpty = cardsList.isEmpty()
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
    
    fun deleteCard(card: BusinessCard) {
        Log.d("CardListViewModel", "═══════════════════════════════════════════════════════")
        Log.d("CardListViewModel", "🔴 DELETE CARD BUTTON CLICKED")
        Log.d("CardListViewModel", "   Card: ${card.fullName}")
        Log.d("CardListViewModel", "   Local ID: ${card.id}")
        Log.d("CardListViewModel", "   Server ID: ${card.serverCardId ?: "NONE"}")
        Log.d("CardListViewModel", "═══════════════════════════════════════════════════════")
        viewModelScope.launch {
            Log.d("CardListViewModel", "📱 Inside viewModelScope.launch")
            try {
                Log.d("CardListViewModel", "🔄 Calling businessCardRepository.deleteCard()...")
                businessCardRepository.deleteCard(card)
                Log.d("CardListViewModel", "✅ deleteCard() completed")
                Log.d("CardListViewModel", "🔄 Calling syncManager.pushRecentChanges()...")
                // Trigger sync to delete from server
                syncManager.pushRecentChanges()
                Log.d("CardListViewModel", "✅ pushRecentChanges() completed")
            } catch (e: Exception) {
                Log.e("CardListViewModel", "❌ Exception in deleteCard: ${e.message}", e)
                e.printStackTrace()
                _uiState.update {
                    it.copy(errorMessage = "Failed to delete card: ${e.message}")
                }
            }
        }
    }
}

data class CardListUiState(
    val isLoading: Boolean = true,
    val isEmpty: Boolean = false,
    val isRefreshing: Boolean = false,
    val errorMessage: String? = null
)

