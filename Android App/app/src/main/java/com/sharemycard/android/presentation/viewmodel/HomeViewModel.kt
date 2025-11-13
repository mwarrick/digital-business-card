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
    
    private var hasPerformedInitialSync = false
    
    init {
        loadUserEmail()
        observeCounts()
    }
    
    fun performInitialSyncIfNeeded() {
        if (hasPerformedInitialSync) {
            Log.d("HomeViewModel", "Initial sync already performed, skipping")
            return
        }
        
        Log.d("HomeViewModel", "═══════════════════════════════════════════════════════")
        Log.d("HomeViewModel", "🔄 PERFORMING INITIAL SYNC AFTER LOGIN")
        Log.d("HomeViewModel", "═══════════════════════════════════════════════════════")
        hasPerformedInitialSync = true
        
        viewModelScope.launch {
            _uiState.update { it.copy(isSyncing = true, syncStatus = "Syncing...") }
            
            try {
                val result = syncManager.performFullSync()
                Log.d("HomeViewModel", "Initial sync completed - Success: ${result.success}, Message: ${result.message}")
                
                if (result.success) {
                    refreshCounts()
                    _uiState.update {
                        it.copy(
                            isSyncing = false,
                            syncStatus = "Last synced: ${java.text.SimpleDateFormat("HH:mm:ss", java.util.Locale.getDefault()).format(java.util.Date())}",
                            lastSyncTime = System.currentTimeMillis()
                        )
                    }
                } else {
                    _uiState.update {
                        it.copy(
                            isSyncing = false,
                            syncStatus = "Sync failed: ${result.message}",
                            lastSyncTime = null
                        )
                    }
                }
            } catch (e: Exception) {
                Log.e("HomeViewModel", "Initial sync error", e)
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
        Log.d("HomeViewModel", "═══════════════════════════════════════════════════════")
        Log.d("HomeViewModel", "🔵 SYNC BUTTON CLICKED")
        Log.d("HomeViewModel", "═══════════════════════════════════════════════════════")
        viewModelScope.launch {
            Log.d("HomeViewModel", "📱 Inside viewModelScope.launch")
            _uiState.update { it.copy(isSyncing = true, syncStatus = "Syncing...") }
            Log.d("HomeViewModel", "✅ UI state updated to syncing")

            try {
                Log.d("HomeViewModel", "🔄 About to call syncManager.performFullSync()...")
                val result = syncManager.performFullSync()
                Log.d("HomeViewModel", "📥 performFullSync() returned")
                Log.d("HomeViewModel", "   Success: ${result.success}")
                Log.d("HomeViewModel", "   Message: ${result.message}")
                
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

