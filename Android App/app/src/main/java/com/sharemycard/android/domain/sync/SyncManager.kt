package com.sharemycard.android.domain.sync

import android.util.Log
import com.sharemycard.android.data.remote.api.CardApi
import com.sharemycard.android.data.remote.api.ContactApi
import com.sharemycard.android.data.remote.api.LeadApi
import com.sharemycard.android.data.remote.mapper.*
import com.sharemycard.android.domain.repository.BusinessCardRepository
import com.sharemycard.android.domain.repository.ContactRepository
import com.sharemycard.android.domain.repository.LeadRepository
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class SyncManager @Inject constructor(
    private val cardApi: CardApi,
    private val contactApi: ContactApi,
    private val leadApi: LeadApi,
    private val businessCardRepository: BusinessCardRepository,
    private val contactRepository: ContactRepository,
    private val leadRepository: LeadRepository
) {
    
    private var isSyncing = false
    
    suspend fun performFullSync(): SyncResult = withContext(Dispatchers.IO) {
        if (isSyncing) {
            Log.d("SyncManager", "Sync already in progress, skipping")
            return@withContext SyncResult(false, "Sync already in progress")
        }
        
        isSyncing = true
        val errors = mutableListOf<String>()
        
        try {
            Log.d("SyncManager", "🔄 Starting full sync...")
            
            // 1. Sync business cards
            try {
                syncBusinessCards()
                Log.d("SyncManager", "✅ Business cards synced")
            } catch (e: Exception) {
                val error = "Failed to sync business cards: ${e.message}"
                Log.e("SyncManager", error, e)
                errors.add(error)
            }
            
            // 2. Sync contacts
            try {
                syncContacts()
                Log.d("SyncManager", "✅ Contacts synced")
            } catch (e: Exception) {
                val error = "Failed to sync contacts: ${e.message}"
                Log.e("SyncManager", error, e)
                errors.add(error)
            }
            
            // 3. Sync leads
            try {
                syncLeads()
                Log.d("SyncManager", "✅ Leads synced")
            } catch (e: Exception) {
                val error = "Failed to sync leads: ${e.message}"
                Log.e("SyncManager", error, e)
                errors.add(error)
            }
            
            val success = errors.isEmpty()
            val message = if (success) {
                "Sync completed successfully"
            } else {
                "Sync completed with ${errors.size} error(s): ${errors.joinToString(", ")}"
            }
            
            Log.d("SyncManager", "🔄 Full sync complete: $message")
            SyncResult(success, message, errors)
            
        } finally {
            isSyncing = false
        }
    }
    
    private suspend fun syncBusinessCards() {
        Log.d("SyncManager", "📡 Fetching business cards from server...")
        val response = cardApi.getCards()
        
        if (response.isSuccess && response.data != null) {
            val serverCards = response.data
            Log.d("SyncManager", "📦 Received ${serverCards.size} cards from server")
            
            // Log server card IDs for debugging
            serverCards.forEach { dto ->
                Log.d("SyncManager", "   Server card: id=${dto.id}, name=${dto.firstName} ${dto.lastName}, company=${dto.companyName}, jobTitle=${dto.jobTitle}")
            }
            
            // Map DTOs to domain models
            val domainCards = serverCards.map { dto ->
                BusinessCardDtoMapper.toDomain(dto)
            }
            
            // Log mapped cards
            domainCards.forEach { card ->
                Log.d("SyncManager", "   Mapped card: id=${card.id}, serverCardId=${card.serverCardId}, company=${card.companyName}, jobTitle=${card.jobTitle}")
            }
            
            // Save to local database
            businessCardRepository.insertCards(domainCards)
            Log.d("SyncManager", "💾 Saved ${domainCards.size} cards to local database")
        } else {
            throw Exception("Failed to fetch cards: ${response.message}")
        }
    }
    
    private suspend fun syncContacts() {
        Log.d("SyncManager", "📡 Fetching contacts from server...")
        val response = contactApi.getContacts()
        
        if (response.isSuccess && response.data != null) {
            val serverContacts = response.data
            Log.d("SyncManager", "📦 Received ${serverContacts.size} contacts from server")
            
            // Map DTOs to domain models
            val domainContacts = serverContacts.map { dto ->
                ContactDtoMapper.toDomain(dto)
            }
            
            // Save to local database
            contactRepository.insertContacts(domainContacts)
            Log.d("SyncManager", "💾 Saved ${domainContacts.size} contacts to local database")
        } else {
            throw Exception("Failed to fetch contacts: ${response.message}")
        }
    }
    
    private suspend fun syncLeads() {
        Log.d("SyncManager", "📡 Fetching leads from server...")
        val response = leadApi.getLeads()
        
        if (response.isSuccess && response.data != null) {
            val serverLeads = response.data
            Log.d("SyncManager", "📦 Received ${serverLeads.size} leads from server")
            
            // Map DTOs to domain models
            val domainLeads = serverLeads.map { dto ->
                LeadDtoMapper.toDomain(dto)
            }
            
            // Save to local database
            leadRepository.insertLeads(domainLeads)
            Log.d("SyncManager", "💾 Saved ${domainLeads.size} leads to local database")
        } else {
            throw Exception("Failed to fetch leads: ${response.message}")
        }
    }
}

data class SyncResult(
    val success: Boolean,
    val message: String,
    val errors: List<String> = emptyList()
)

