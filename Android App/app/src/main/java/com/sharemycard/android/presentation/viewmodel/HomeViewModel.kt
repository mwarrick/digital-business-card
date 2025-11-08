package com.sharemycard.android.presentation.viewmodel

import android.util.Log
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.sharemycard.android.domain.repository.AuthRepository
import com.sharemycard.android.domain.repository.BusinessCardRepository
import com.sharemycard.android.domain.repository.ContactRepository
import com.sharemycard.android.domain.repository.LeadRepository
import com.sharemycard.android.domain.sync.SyncManager
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class HomeViewModel @Inject constructor(
    private val authRepository: AuthRepository,
    private val businessCardRepository: BusinessCardRepository,
    private val contactRepository: ContactRepository,
    private val leadRepository: LeadRepository,
    private val syncManager: SyncManager
) : ViewModel() {
    
    private val _uiState = MutableStateFlow(HomeUiState())
    val uiState: StateFlow<HomeUiState> = _uiState.asStateFlow()
    
    init {
        loadUserEmail()
        observeCounts()
    }
    
    private fun loadUserEmail() {
        val email = authRepository.getCurrentEmail()
        _uiState.update { it.copy(userEmail = email ?: "Unknown") }
    }
    
    private fun observeCounts() {
        viewModelScope.launch {
            combine(
                businessCardRepository.getCardCountFlow(),
                contactRepository.getContactCountFlow(),
                leadRepository.getLeadCountFlow()
            ) { cardCount, contactCount, leadCount ->
                Triple(cardCount, contactCount, leadCount)
            }.collect { (cardCount, contactCount, leadCount) ->
                _uiState.update {
                    it.copy(
                        cardCount = cardCount,
                        contactCount = contactCount,
                        leadCount = leadCount
                    )
                }
            }
        }
    }
    
    fun sync() {
        viewModelScope.launch {
            _uiState.update { it.copy(isSyncing = true, syncStatus = "Syncing...") }

            try {
                Log.d("HomeViewModel", "Starting sync...")
                val result = syncManager.performFullSync()
                
                if (result.success) {
                    // Refresh counts after successful sync
                    refreshCounts()
                    
                    _uiState.update {
                        it.copy(
                            isSyncing = false,
                            syncStatus = "Last synced: ${java.text.SimpleDateFormat("HH:mm:ss", java.util.Locale.getDefault()).format(java.util.Date())}",
                            lastSyncTime = System.currentTimeMillis()
                        )
                    }
                    Log.d("HomeViewModel", "Sync completed successfully")
                } else {
                    _uiState.update {
                        it.copy(
                            isSyncing = false,
                            syncStatus = "Sync failed: ${result.message}",
                            lastSyncTime = null
                        )
                    }
                    Log.e("HomeViewModel", "Sync failed: ${result.message}")
                }
            } catch (e: Exception) {
                Log.e("HomeViewModel", "Sync error", e)
                _uiState.update {
                    it.copy(
                        isSyncing = false,
                        syncStatus = "Sync error: ${e.message}",
                        lastSyncTime = null
                    )
                }
            }
        }
    }
    
    fun refreshCounts() {
        viewModelScope.launch {
            // Force refresh by collecting current values
            val cardCount = businessCardRepository.getCardCount()
            val contactCount = contactRepository.getContactCount()
            val leadCount = leadRepository.getLeadCount()
            
            _uiState.update {
                it.copy(
                    cardCount = cardCount,
                    contactCount = contactCount,
                    leadCount = leadCount
                )
            }
        }
    }
}

data class HomeUiState(
    val userEmail: String = "",
    val cardCount: Int = 0,
    val contactCount: Int = 0,
    val leadCount: Int = 0,
    val isSyncing: Boolean = false,
    val syncStatus: String = "",
    val lastSyncTime: Long? = null
)

