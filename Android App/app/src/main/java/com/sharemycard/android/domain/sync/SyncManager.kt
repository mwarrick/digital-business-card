package com.sharemycard.android.domain.sync

import android.util.Log
import com.sharemycard.android.data.local.TokenManager
import com.sharemycard.android.data.remote.api.CardApi
import com.sharemycard.android.data.remote.api.ContactApi
import com.sharemycard.android.data.remote.api.LeadApi
import com.sharemycard.android.data.remote.mapper.*
import com.sharemycard.android.data.remote.models.BusinessCardDTO
import com.sharemycard.android.domain.repository.BusinessCardRepository
import com.sharemycard.android.domain.repository.ContactRepository
import com.sharemycard.android.domain.repository.LeadRepository
import com.sharemycard.android.util.DateParser
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import java.io.IOException
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class SyncManager @Inject constructor(
    private val cardApi: CardApi,
    private val contactApi: ContactApi,
    private val leadApi: LeadApi,
    private val businessCardRepository: BusinessCardRepository,
    private val contactRepository: ContactRepository,
    private val leadRepository: LeadRepository,
    private val tokenManager: TokenManager
) {
    
    private var isSyncing = false
    
    /**
     * Perform full bidirectional sync: push local changes, then pull server changes.
     */
    suspend fun performFullSync(): SyncResult = withContext(Dispatchers.IO) {
        if (isSyncing) {
            Log.d("SyncManager", "Sync already in progress, skipping")
            return@withContext SyncResult(false, "Sync already in progress")
        }
        
        isSyncing = true
        val errors = mutableListOf<String>()
        
        try {
            Log.d("SyncManager", "🔄 Starting full sync...")
            
            // 1. Sync business cards (push then pull)
            try {
                syncBusinessCards()
                Log.d("SyncManager", "✅ Business cards synced")
            } catch (e: CancellationException) {
                Log.w("SyncManager", "Sync cancelled")
                throw e // Re-throw cancellation
            } catch (e: Exception) {
                val error = "Failed to sync business cards: ${e.message}"
                Log.e("SyncManager", error, e)
                errors.add(error)
            }
            
            // 2. Sync contacts
            try {
                syncContacts()
                Log.d("SyncManager", "✅ Contacts synced")
            } catch (e: CancellationException) {
                Log.w("SyncManager", "Sync cancelled")
                throw e
            } catch (e: Exception) {
                val error = "Failed to sync contacts: ${e.message}"
                Log.e("SyncManager", error, e)
                errors.add(error)
            }
            
            // 3. Sync leads
            try {
                syncLeads()
                Log.d("SyncManager", "✅ Leads synced")
            } catch (e: CancellationException) {
                Log.w("SyncManager", "Sync cancelled")
                throw e
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
            
        } catch (e: CancellationException) {
            Log.w("SyncManager", "Sync was cancelled")
            throw e
        } catch (e: IOException) {
            // Handle network errors
            if (e.message?.contains("cancelled", ignoreCase = true) == true) {
                Log.w("SyncManager", "Sync cancelled (network)")
                throw CancellationException("Sync cancelled", e)
            }
            val error = "Network error during sync: ${e.message}"
            Log.e("SyncManager", error, e)
            SyncResult(false, error, listOf(error))
        } finally {
            isSyncing = false
        }
    }
    
    /**
     * Sync business cards: push local changes (with timestamp comparison), then pull server changes.
     */
    private suspend fun syncBusinessCards() {
        Log.d("SyncManager", "═══════════════════════════════════════════════════════")
        Log.d("SyncManager", "📡 Fetching server cards for comparison...")
        try {
            val response = cardApi.getCards()
            
            Log.d("SyncManager", "📥 API Response received:")
            Log.d("SyncManager", "   success field: ${response.success} (type: ${response.success?.javaClass?.simpleName})")
            Log.d("SyncManager", "   isSuccess: ${response.isSuccess}")
            Log.d("SyncManager", "   message: ${response.message}")
            Log.d("SyncManager", "   data is null: ${response.data == null}")
            Log.d("SyncManager", "   data size: ${response.data?.size ?: 0}")
            
            if (!response.isSuccess) {
                Log.e("SyncManager", "❌ API returned unsuccessful response: ${response.message}")
                throw Exception("Failed to fetch cards: ${response.message}")
            }
            
            if (response.data == null) {
                Log.e("SyncManager", "❌ API returned null data")
                throw Exception("Failed to fetch cards: response data is null")
            }
            
            val initialServerCards = response.data
            Log.d("SyncManager", "📦 Received ${initialServerCards.size} cards from server")
            
            if (initialServerCards.isEmpty()) {
                Log.w("SyncManager", "⚠️ Server returned empty cards list")
            } else {
                Log.d("SyncManager", "   First card ID: ${initialServerCards.firstOrNull()?.id}")
                Log.d("SyncManager", "   First card name: ${initialServerCards.firstOrNull()?.let { "${it.firstName} ${it.lastName}" }}")
            }
            
            // Create a lookup map of server cards by ID
            val serverCardMap: Map<String, BusinessCardDTO> = initialServerCards
                .filter { it.id != null }
                .associateBy { it.id!! }
            
            // Step 1: Push local cards to server (with timestamp comparison)
            pushLocalCardsWithComparison(serverCardMap)
            
            // Step 2: Re-fetch server cards to get latest state (in case server was updated by another client)
            Log.d("SyncManager", "📡 Re-fetching server cards to get latest state...")
            try {
                val finalResponse = cardApi.getCards()
                Log.d("SyncManager", "📥 Re-fetch API Response:")
                Log.d("SyncManager", "   isSuccess: ${finalResponse.isSuccess}")
                Log.d("SyncManager", "   data is null: ${finalResponse.data == null}")
                Log.d("SyncManager", "   data size: ${finalResponse.data?.size ?: 0}")
                
                if (finalResponse.isSuccess && finalResponse.data != null) {
                    val finalServerCards = finalResponse.data
                    Log.d("SyncManager", "📦 Received ${finalServerCards.size} cards from server (after push)")
                    
                    // Step 3: Pull server cards to local (this will update local if server is newer)
                    pullServerCards(finalServerCards)
                } else {
                    // Fallback to using initial server cards if re-fetch fails
                    Log.w("SyncManager", "⚠️ Failed to re-fetch server cards, using initial list")
                    Log.w("SyncManager", "   isSuccess: ${finalResponse.isSuccess}, data null: ${finalResponse.data == null}")
                    pullServerCards(initialServerCards)
                }
            } catch (e: Exception) {
                Log.e("SyncManager", "❌ Error during re-fetch: ${e.message}", e)
                Log.w("SyncManager", "⚠️ Using initial server cards list due to re-fetch error")
                pullServerCards(initialServerCards)
            }
            Log.d("SyncManager", "═══════════════════════════════════════════════════════")
        } catch (e: Exception) {
            Log.e("SyncManager", "❌ Error in syncBusinessCards: ${e.message}", e)
            throw e
        }
    }
    
    /**
     * Push local cards to server, comparing timestamps to avoid overwriting newer server data.
     */
    private suspend fun pushLocalCardsWithComparison(serverCardMap: Map<String, BusinessCardDTO>) {
        Log.d("SyncManager", "⬆️ Pushing local cards to server...")
        val localCards = businessCardRepository.getAllCardsSync()
        Log.d("SyncManager", "📋 Found ${localCards.size} local cards to sync")
        
        for (card in localCards) {
            try {
                val dto = BusinessCardDtoMapper.toDto(card)
                
                if (!card.serverCardId.isNullOrBlank()) {
                    // Card exists on server - compare timestamps
                    val serverCard = serverCardMap[card.serverCardId]
                    if (shouldPushBasedOnTimestamp(card, serverCard)) {
                        // Update existing card on server
                        val updateResponse = cardApi.updateCard(card.serverCardId!!, dto)
                        if (updateResponse.isSuccess) {
                            Log.d("SyncManager", "  🔄 Updated card on server: ${card.fullName}")
                        } else {
                            Log.w("SyncManager", "  ⚠️ Failed to update card ${card.fullName}: ${updateResponse.message}")
                        }
                    } else {
                        Log.d("SyncManager", "  ⏭️ Skipping push (server version is newer): ${card.fullName}")
                    }
                } else {
                    // Create new card on server
                    val createResponse = cardApi.createCard(dto)
                    if (createResponse.isSuccess && createResponse.data?.id != null) {
                        val createdId = createResponse.data.id
                        // Update local card with server ID
                        businessCardRepository.updateCardServerId(card.id, createdId)
                        Log.d("SyncManager", "  ✅ Created card on server: ${card.fullName} (server ID: $createdId)")
                    } else {
                        Log.w("SyncManager", "  ⚠️ Failed to create card ${card.fullName}: ${createResponse.message}")
                    }
                }
            } catch (e: CancellationException) {
                throw e
            } catch (e: Exception) {
                Log.e("SyncManager", "  ❌ Failed to sync card ${card.fullName}: ${e.message}", e)
                // Continue with other cards
            }
        }
    }
    
    /**
     * Determine if local card should be pushed to server based on timestamp comparison.
     */
    private fun shouldPushBasedOnTimestamp(
        localCard: com.sharemycard.android.domain.models.BusinessCard,
        serverCard: BusinessCardDTO?
    ): Boolean {
        if (serverCard == null) {
            // No server card exists - push
            return true
        }
        
        val localUpdatedAt = localCard.updatedAt
        val serverUpdatedAt = serverCard.updatedAt?.let { DateParser.parseServerDate(it) }
        
        if (serverUpdatedAt == null) {
            // No server timestamp - push local version
            return true
        }
        
        // Only push if local is newer
        return localUpdatedAt > serverUpdatedAt
    }
    
    /**
     * Pull server cards to local database (with conflict resolution).
     * Also deletes local cards that no longer exist on the server.
     */
    private suspend fun pullServerCards(serverCards: List<BusinessCardDTO>) {
        Log.d("SyncManager", "⬇️ Pulling server cards to local...")
        
        // Map DTOs to domain models
        val domainCards = serverCards.map { dto ->
            val domainCard = BusinessCardDtoMapper.toDomain(dto)
            
            // Log the server timestamp for debugging
            val serverUpdatedAtString = dto.updatedAt
            val serverUpdatedAtParsed = DateParser.parseServerDate(serverUpdatedAtString)
            Log.d("SyncManager", "📋 Server card: ${domainCard.fullName}")
            Log.d("SyncManager", "   Server ID: ${dto.id}")
            Log.d("SyncManager", "   Server updatedAt (raw): $serverUpdatedAtString")
            Log.d("SyncManager", "   Server updatedAt (parsed): $serverUpdatedAtParsed (${serverUpdatedAtParsed?.let { java.util.Date(it) }})")
            
            domainCard
        }
        
        Log.d("SyncManager", "🔄 About to call insertCards with ${domainCards.size} cards...")
        // Save to local database (repository handles conflict resolution)
        businessCardRepository.insertCards(domainCards)
        Log.d("SyncManager", "💾 insertCards completed for ${domainCards.size} cards")
        
        // Delete local cards that no longer exist on the server
        val serverCardIds = serverCards.mapNotNull { it.id }.toSet()
        val localCards = businessCardRepository.getAllCardsSync()
        val cardsToDelete = localCards.filter { 
            // Only delete cards that have a serverCardId and it's not in the server list
            !it.serverCardId.isNullOrBlank() && it.serverCardId !in serverCardIds
        }
        
        if (cardsToDelete.isNotEmpty()) {
            Log.d("SyncManager", "🗑️ Deleting ${cardsToDelete.size} local cards that no longer exist on server...")
            for (card in cardsToDelete) {
                try {
                    businessCardRepository.deleteCard(card)
                    Log.d("SyncManager", "  🗑️ Deleted local card: ${card.fullName} (Server ID: ${card.serverCardId})")
                } catch (e: Exception) {
                    Log.e("SyncManager", "  ❌ Failed to delete local card ${card.fullName}: ${e.message}")
                }
            }
        } else {
            Log.d("SyncManager", "✅ No local cards to delete - all cards exist on server")
        }
    }
    
            /**
             * Sync contacts: push local changes, then pull server changes.
             */
            private suspend fun syncContacts() {
                Log.d("SyncManager", "═══════════════════════════════════════════════════════")
                Log.d("SyncManager", "📡 Fetching server contacts for comparison...")
                
                // Log current user info for debugging
                val userId = tokenManager.getUserIdFromToken()
                val userEmail = tokenManager.getEmail()
                val isDemo = tokenManager.isDemoAccount()
                Log.d("SyncManager", "👤 Current user - ID: $userId, Email: $userEmail, IsDemo: $isDemo")
                
                if (isDemo) {
                    Log.w("SyncManager", "⚠️ WARNING: Syncing with DEMO account! This may pull demo data.")
                }
                
                try {
                    val response = contactApi.getContacts()
                    Log.d("SyncManager", "📥 Contacts API Response - success: ${response.isSuccess}, message: ${response.message}")
                    Log.d("SyncManager", "📥 Contacts API Response - data size: ${response.data?.size ?: 0}")
            
            if (!response.isSuccess) {
                Log.e("SyncManager", "❌ API returned unsuccessful response: ${response.message}")
                throw Exception("Failed to fetch contacts: ${response.message}")
            }
            
            if (response.data == null) {
                Log.w("SyncManager", "⚠️ API returned null data")
                throw Exception("Failed to fetch contacts: null data")
            }
            
            val initialServerContacts = response.data
            Log.d("SyncManager", "📦 Received ${initialServerContacts.size} contacts from server")
            
            // Create a lookup map of server contacts by ID
            val serverContactMap: Map<String, com.sharemycard.android.data.remote.models.ContactDTO> = 
                initialServerContacts
                    .filter { it.id != null }
                    .associateBy { it.id!! }
            
            // Step 1: Push local contacts to server
            pushLocalContactsWithComparison(serverContactMap)
            
            // Step 2: Re-fetch server contacts to get latest state
            Log.d("SyncManager", "📡 Re-fetching server contacts to get latest state...")
            try {
                val finalResponse = contactApi.getContacts()
                
                if (finalResponse.isSuccess && finalResponse.data != null) {
                    val finalServerContacts = finalResponse.data
                    Log.d("SyncManager", "📦 Received ${finalServerContacts.size} contacts from server (after push)")
                    
                    // Step 3: Pull server contacts to local
                    pullServerContacts(finalServerContacts)
                } else {
                    // Fallback to using initial server contacts if re-fetch fails
                    Log.w("SyncManager", "⚠️ Failed to re-fetch server contacts, using initial list")
                    pullServerContacts(initialServerContacts)
                }
            } catch (e: Exception) {
                Log.w("SyncManager", "⚠️ Using initial server contacts list due to re-fetch error")
                pullServerContacts(initialServerContacts)
            }
            Log.d("SyncManager", "═══════════════════════════════════════════════════════")
        } catch (e: Exception) {
            Log.e("SyncManager", "❌ Error in syncContacts: ${e.message}", e)
            throw e
        }
    }
    
    /**
     * Push local contacts to server.
     * Matches contacts by ID first, then by email to avoid duplicates.
     */
    private suspend fun pushLocalContactsWithComparison(
        serverContactMap: Map<String, com.sharemycard.android.data.remote.models.ContactDTO>
    ) {
        Log.d("SyncManager", "⬆️ Pushing local contacts to server...")
        val localContacts = contactRepository.getAllContactsSync()
        Log.d("SyncManager", "📋 Found ${localContacts.size} local contacts to sync")
        
        // Create a map of server contacts by email for duplicate detection
        val serverContactByEmail: Map<String, com.sharemycard.android.data.remote.models.ContactDTO> = 
            serverContactMap.values
                .filter { it.emailPrimary != null && it.emailPrimary.isNotBlank() }
                .associateBy { it.emailPrimary!!.lowercase().trim() }
        
        for (contact in localContacts) {
            try {
                val dto = ContactDtoMapper.toDto(contact)
                
                // Step 1: Check if contact exists on server by ID
                var serverContact = serverContactMap[contact.id]
                
                // Step 2: If not found by ID, check by email to avoid duplicates
                if (serverContact == null && contact.email != null && contact.email.isNotBlank()) {
                    val emailKey = contact.email.lowercase().trim()
                    serverContact = serverContactByEmail[emailKey]
                    
                    if (serverContact != null) {
                        Log.d("SyncManager", "  🔍 Found existing contact on server by email: ${contact.fullName} (${contact.email})")
                        Log.d("SyncManager", "     Local ID: ${contact.id}, Server ID: ${serverContact.id}")
                        
                        // Update local contact with server's ID to avoid duplicates
                        // Delete local contact with old ID, pull will add it back with server ID
                        try {
                            contactRepository.deleteContactById(contact.id)
                            Log.d("SyncManager", "  🗑️ Deleted local contact with old ID, will be re-added with server ID")
                        } catch (e: Exception) {
                            Log.e("SyncManager", "  ❌ Failed to delete local contact: ${e.message}")
                        }
                        
                        // Update the existing server contact instead of creating a new one
                        val updateResponse = contactApi.updateContact(serverContact.id!!, dto)
                        if (updateResponse.isSuccess) {
                            Log.d("SyncManager", "  🔄 Updated existing contact on server: ${contact.fullName}")
                        } else {
                            Log.w("SyncManager", "  ⚠️ Failed to update contact ${contact.fullName}: ${updateResponse.message}")
                        }
                        continue // Skip to next contact
                    }
                }
                
                if (serverContact != null) {
                    // Contact exists on server (matched by ID) - update it
                    val updateResponse = contactApi.updateContact(contact.id, dto)
                    if (updateResponse.isSuccess) {
                        Log.d("SyncManager", "  🔄 Updated contact on server: ${contact.fullName}")
                    } else {
                        Log.w("SyncManager", "  ⚠️ Failed to update contact ${contact.fullName}: ${updateResponse.message}")
                    }
                } else {
                    // No matching contact found - create new contact on server
                    val createResponse = contactApi.createContact(dto)
                    if (createResponse.isSuccess && createResponse.data != null) {
                        val serverContactId = createResponse.data.id
                        if (serverContactId != null && serverContactId != contact.id) {
                            // Server returned a different ID - delete local contact with old ID
                            // The pull step will add it back with the server's ID
                            Log.d("SyncManager", "  🔄 Server returned different ID for contact: ${contact.fullName}")
                            Log.d("SyncManager", "     Local ID: ${contact.id}, Server ID: $serverContactId")
                            try {
                                contactRepository.deleteContactById(contact.id)
                                Log.d("SyncManager", "  🗑️ Deleted local contact with old ID, will be re-added with server ID")
                            } catch (e: Exception) {
                                Log.e("SyncManager", "  ❌ Failed to delete local contact: ${e.message}")
                            }
                        }
                        Log.d("SyncManager", "  ✅ Created contact on server: ${contact.fullName}")
                    } else {
                        Log.w("SyncManager", "  ⚠️ Failed to create contact ${contact.fullName}: ${createResponse.message}")
                    }
                }
            } catch (e: CancellationException) {
                throw e
            } catch (e: Exception) {
                Log.e("SyncManager", "  ❌ Failed to sync contact ${contact.fullName}: ${e.message}", e)
            }
        }
    }
    
    /**
     * Pull server contacts to local database.
     * Also deletes local contacts that no longer exist on the server.
     */
    private suspend fun pullServerContacts(serverContacts: List<com.sharemycard.android.data.remote.models.ContactDTO>) {
        Log.d("SyncManager", "⬇️ Pulling server contacts to local...")
        
        // Map DTOs to domain models
        val domainContacts = serverContacts.map { dto ->
            ContactDtoMapper.toDomain(dto)
        }
        
        // Save to local database (repository handles conflict resolution)
        contactRepository.insertContacts(domainContacts)
        Log.d("SyncManager", "💾 Saved ${domainContacts.size} contacts to local database")
        
        // Delete local contacts that no longer exist on the server
        val serverContactIds = serverContacts.mapNotNull { it.id }.toSet()
        val localContacts = contactRepository.getAllContactsSync()
        val contactsToDelete = localContacts.filter { it.id !in serverContactIds }
        
        if (contactsToDelete.isNotEmpty()) {
            Log.d("SyncManager", "🗑️ Deleting ${contactsToDelete.size} local contacts that no longer exist on server...")
            for (contact in contactsToDelete) {
                try {
                    contactRepository.deleteContactById(contact.id)
                    Log.d("SyncManager", "  🗑️ Deleted local contact: ${contact.fullName} (ID: ${contact.id})")
                } catch (e: Exception) {
                    Log.e("SyncManager", "  ❌ Failed to delete local contact ${contact.fullName}: ${e.message}")
                }
            }
        } else {
            Log.d("SyncManager", "✅ No local contacts to delete - all contacts exist on server")
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
    
    /**
     * Auto-sync: Push only recent local changes (updated in last 30 seconds).
     * Used after local modifications to quickly sync to server.
     */
    suspend fun pushRecentChanges(): SyncResult = withContext(Dispatchers.IO) {
        if (isSyncing) {
            Log.d("SyncManager", "Sync already in progress, skipping auto-sync")
            return@withContext SyncResult(false, "Sync already in progress")
        }
        
        isSyncing = true
        val errors = mutableListOf<String>()
        
        try {
            Log.d("SyncManager", "🔄 Starting auto-sync (recent changes only)...")
            
            val recentThreshold = System.currentTimeMillis() - 30_000
            
            // ========== SYNC CARDS ==========
            // Fetch server cards for comparison
            val cardResponse = cardApi.getCards()
            if (cardResponse.isSuccess && cardResponse.data != null) {
                val serverCardMap: Map<String, BusinessCardDTO> = cardResponse.data
                    .filter { it.id != null }
                    .associateBy { it.id!! }
                
                // Get local cards updated in last 30 seconds
                val localCards = businessCardRepository.getAllCardsSync()
                    .filter { 
                        // BusinessCard.updatedAt is already a Long timestamp
                        it.updatedAt >= recentThreshold
                    }
                
                Log.d("SyncManager", "📋 Found ${localCards.size} recent cards to sync")
                
                // Push only recent cards
                for (card in localCards) {
                    try {
                        val dto = BusinessCardDtoMapper.toDto(card)
                        
                        if (!card.serverCardId.isNullOrBlank()) {
                            val serverCard = serverCardMap[card.serverCardId]
                            if (shouldPushBasedOnTimestamp(card, serverCard)) {
                                val updateResponse = cardApi.updateCard(card.serverCardId!!, dto)
                                if (updateResponse.isSuccess) {
                                    Log.d("SyncManager", "  🔄 Auto-synced card: ${card.fullName}")
                                }
                            }
                        } else {
                            // New card - create on server
                            val createResponse = cardApi.createCard(dto)
                            if (createResponse.isSuccess && createResponse.data?.id != null) {
                                businessCardRepository.updateCardServerId(card.id, createResponse.data.id)
                                Log.d("SyncManager", "  ✅ Auto-created card: ${card.fullName}")
                            }
                        }
                    } catch (e: Exception) {
                        Log.e("SyncManager", "  ❌ Failed to auto-sync card ${card.fullName}: ${e.message}")
                        errors.add("Failed to sync card ${card.fullName}: ${e.message}")
                    }
                }
            }
            
            // ========== SYNC CONTACTS ==========
            // Fetch server contacts for comparison
            val contactResponse = contactApi.getContacts()
            if (contactResponse.isSuccess && contactResponse.data != null) {
                val serverContactMap: Map<String, com.sharemycard.android.data.remote.models.ContactDTO> = 
                    contactResponse.data
                        .filter { it.id != null }
                        .associateBy { it.id!! }
                
                // Get local contacts updated in last 30 seconds
                val localContacts = contactRepository.getAllContactsSync()
                    .filter { 
                        val updatedAt = DateParser.parseServerDate(it.updatedAt) ?: 0L
                        updatedAt >= recentThreshold
                    }
                
                Log.d("SyncManager", "📋 Found ${localContacts.size} recent contacts to sync")
                
                // Create a map of server contacts by email for duplicate detection
                val serverContactByEmail: Map<String, com.sharemycard.android.data.remote.models.ContactDTO> = 
                    serverContactMap.values
                        .filter { it.emailPrimary != null && it.emailPrimary.isNotBlank() }
                        .associateBy { it.emailPrimary!!.lowercase().trim() }
                
                // Push only recent contacts
                for (contact in localContacts) {
                    try {
                        val dto = ContactDtoMapper.toDto(contact)
                        
                        // Step 1: Check if contact exists on server by ID
                        var serverContact = serverContactMap[contact.id]
                        
                        // Step 2: If not found by ID, check by email to avoid duplicates
                        if (serverContact == null && contact.email != null && contact.email.isNotBlank()) {
                            val emailKey = contact.email.lowercase().trim()
                            serverContact = serverContactByEmail[emailKey]
                            
                            if (serverContact != null) {
                                Log.d("SyncManager", "  🔍 Found existing contact on server by email: ${contact.fullName} (${contact.email})")
                                Log.d("SyncManager", "     Local ID: ${contact.id}, Server ID: ${serverContact.id}")
                                
                                // Update local contact with server's ID to avoid duplicates
                                // Delete local contact with old ID, pull will add it back with server ID
                                try {
                                    contactRepository.deleteContactById(contact.id)
                                    Log.d("SyncManager", "  🗑️ Deleted local contact with old ID, will be re-added with server ID")
                                } catch (e: Exception) {
                                    Log.e("SyncManager", "  ❌ Failed to delete local contact: ${e.message}")
                                }
                                
                                // Update the existing server contact instead of creating a new one
                                val updateResponse = contactApi.updateContact(serverContact.id!!, dto)
                                if (updateResponse.isSuccess) {
                                    Log.d("SyncManager", "  🔄 Auto-synced existing contact: ${contact.fullName}")
                                } else {
                                    Log.w("SyncManager", "  ⚠️ Failed to update contact ${contact.fullName}: ${updateResponse.message}")
                                    errors.add("Failed to update contact ${contact.fullName}: ${updateResponse.message}")
                                }
                                continue // Skip to next contact
                            }
                        }
                        
                        if (serverContact != null) {
                            // Contact exists on server (matched by ID) - update it
                            val updateResponse = contactApi.updateContact(contact.id, dto)
                            if (updateResponse.isSuccess) {
                                Log.d("SyncManager", "  🔄 Auto-synced contact: ${contact.fullName}")
                            } else {
                                Log.w("SyncManager", "  ⚠️ Failed to update contact ${contact.fullName}: ${updateResponse.message}")
                                errors.add("Failed to update contact ${contact.fullName}: ${updateResponse.message}")
                            }
                        } else {
                            // New contact - create on server
                            val createResponse = contactApi.createContact(dto)
                            if (createResponse.isSuccess && createResponse.data != null) {
                                val serverContactId = createResponse.data.id
                                if (serverContactId != null && serverContactId != contact.id) {
                                    // Server returned a different ID - delete local contact with old ID
                                    // The pull step will add it back with the server's ID
                                    Log.d("SyncManager", "  🔄 Server returned different ID for contact: ${contact.fullName}")
                                    Log.d("SyncManager", "     Local ID: ${contact.id}, Server ID: $serverContactId")
                                    try {
                                        contactRepository.deleteContactById(contact.id)
                                        Log.d("SyncManager", "  🗑️ Deleted local contact with old ID, will be re-added with server ID")
                                    } catch (e: Exception) {
                                        Log.e("SyncManager", "  ❌ Failed to delete local contact: ${e.message}")
                                    }
                                }
                                Log.d("SyncManager", "  ✅ Auto-created contact: ${contact.fullName}")
                            } else {
                                Log.w("SyncManager", "  ⚠️ Failed to create contact ${contact.fullName}: ${createResponse.message}")
                                errors.add("Failed to create contact ${contact.fullName}: ${createResponse.message}")
                            }
                        }
                    } catch (e: Exception) {
                        Log.e("SyncManager", "  ❌ Failed to auto-sync contact ${contact.fullName}: ${e.message}")
                        errors.add("Failed to sync contact ${contact.fullName}: ${e.message}")
                    }
                }
            } else {
                Log.w("SyncManager", "⚠️ Failed to fetch contacts from server: ${contactResponse.message}")
            }
            
            val success = errors.isEmpty()
            val message = if (success) {
                "Auto-sync completed successfully"
            } else {
                "Auto-sync completed with ${errors.size} error(s)"
            }
            
            SyncResult(success, message, errors)
            
        } catch (e: CancellationException) {
            throw e
        } catch (e: Exception) {
            val error = "Auto-sync failed: ${e.message}"
            Log.e("SyncManager", error, e)
            SyncResult(false, error, listOf(error))
        } finally {
            isSyncing = false
        }
    }
}

data class SyncResult(
    val success: Boolean,
    val message: String,
    val errors: List<String> = emptyList()
)

