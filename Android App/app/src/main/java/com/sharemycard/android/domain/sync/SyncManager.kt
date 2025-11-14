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
        Log.d("SyncManager", "═══════════════════════════════════════════════════════")
        Log.d("SyncManager", "🚀 performFullSync() CALLED")
        Log.d("SyncManager", "═══════════════════════════════════════════════════════")
        
        if (isSyncing) {
            Log.w("SyncManager", "⚠️ Sync already in progress, skipping")
            return@withContext SyncResult(false, "Sync already in progress")
        }
        
        isSyncing = true
        val errors = mutableListOf<String>()
        
        try {
            Log.d("SyncManager", "🔄 Starting full sync...")
            Log.d("SyncManager", "   Thread: ${Thread.currentThread().name}")
            Log.d("SyncManager", "   Timestamp: ${System.currentTimeMillis()} (${java.util.Date()})")
            
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
        Log.d("SyncManager", "═══════════════════════════════════════════════════════")
        Log.d("SyncManager", "⬆️ PUSHING LOCAL CARDS TO SERVER")
        Log.d("SyncManager", "═══════════════════════════════════════════════════════")
        Log.d("SyncManager", "📞 Calling businessCardRepository.getAllCardsSync()...")
        val allLocalCards = businessCardRepository.getAllCardsSync()
        Log.d("SyncManager", "📥 Received ${allLocalCards.size} cards from repository")
        
        // Separate deleted and non-deleted cards
        val deletedCards = allLocalCards.filter { it.isDeleted }
        val localCards = allLocalCards.filter { !it.isDeleted }
        
        Log.d("SyncManager", "📊 Card separation complete:")
        Log.d("SyncManager", "   Total cards: ${allLocalCards.size}")
        Log.d("SyncManager", "   Deleted cards: ${deletedCards.size}")
        Log.d("SyncManager", "   Non-deleted cards: ${localCards.size}")
        Log.d("SyncManager", "📋 Found ${localCards.size} local cards to sync (filtered from ${allLocalCards.size} total, excluding ${deletedCards.size} deleted)")
        
        // First, push deleted cards to server
        Log.d("SyncManager", "🗑️ Processing ${deletedCards.size} deleted card(s) for sync...")
        if (deletedCards.isEmpty()) {
            Log.d("SyncManager", "  ℹ️ No deleted cards found - nothing to delete on server")
        } else {
            Log.d("SyncManager", "  📋 Deleted cards list:")
            deletedCards.forEachIndexed { index, card ->
                Log.d("SyncManager", "     ${index + 1}. ${card.fullName} (Local: ${card.id}, Server: ${card.serverCardId ?: "NONE"})")
            }
        }
        
        for (card in deletedCards) {
            Log.d("SyncManager", "  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━")
            Log.d("SyncManager", "  🗑️ Processing deleted card: ${card.fullName}")
            Log.d("SyncManager", "     Local ID: ${card.id}")
            Log.d("SyncManager", "     Server ID: ${card.serverCardId ?: "NULL/EMPTY"}")
            Log.d("SyncManager", "     isDeleted: ${card.isDeleted}")
            
            if (!card.serverCardId.isNullOrBlank()) {
                try {
                    // Check if card is already deleted on server
                    val serverCard = serverCardMap[card.serverCardId]
                    Log.d("SyncManager", "  Server card found in map: ${serverCard != null}")
                    Log.d("SyncManager", "  Server card is_deleted: ${serverCard?.isDeleted}")
                    
                    if (serverCard?.isDeleted != 1) {
                        // Card exists on server but is not deleted - delete it
                        Log.d("SyncManager", "  📤 Sending delete request to server...")
                        Log.d("SyncManager", "     API Endpoint: DELETE /api/cards/?id=${card.serverCardId}")
                        Log.d("SyncManager", "     Full URL: https://sharemycard.app/api/cards/?id=${card.serverCardId}")
                        Log.d("SyncManager", "     🔄 Calling cardApi.deleteCard(${card.serverCardId})...")
                        
                        try {
                            val deleteResponse = cardApi.deleteCard(card.serverCardId!!)
                            
                            Log.d("SyncManager", "  📥 Delete response received")
                            Log.d("SyncManager", "     Response object: $deleteResponse")
                            Log.d("SyncManager", "     Response success field: ${deleteResponse.success}")
                            Log.d("SyncManager", "     Response isSuccess: ${deleteResponse.isSuccess}")
                            Log.d("SyncManager", "     Response message: ${deleteResponse.message}")
                            Log.d("SyncManager", "     Response data: ${deleteResponse.data}")
                            
                            if (deleteResponse.isSuccess) {
                                Log.d("SyncManager", "  ✅ Deleted card on server: ${card.fullName} (ID: ${card.serverCardId})")
                            } else {
                                Log.w("SyncManager", "  ⚠️ Failed to delete card ${card.fullName} on server")
                                Log.w("SyncManager", "     Server ID: ${card.serverCardId}")
                                Log.w("SyncManager", "     Error: ${deleteResponse.message}")
                                Log.w("SyncManager", "     Full response: $deleteResponse")
                            }
                        } catch (e: retrofit2.HttpException) {
                            Log.e("SyncManager", "  ❌ HTTP Exception deleting card ${card.fullName} on server")
                            Log.e("SyncManager", "     Server ID: ${card.serverCardId}")
                            Log.e("SyncManager", "     HTTP Code: ${e.code()}")
                            Log.e("SyncManager", "     HTTP Message: ${e.message()}")
                            try {
                                val errorBody = e.response()?.errorBody()?.string()
                                Log.e("SyncManager", "     Error Body: $errorBody")
                            } catch (bodyEx: Exception) {
                                Log.e("SyncManager", "     Could not read error body: ${bodyEx.message}")
                            }
                            e.printStackTrace()
                        } catch (e: java.io.IOException) {
                            Log.e("SyncManager", "  ❌ Network/IO Exception deleting card ${card.fullName} on server")
                            Log.e("SyncManager", "     Server ID: ${card.serverCardId}")
                            Log.e("SyncManager", "     Exception type: ${e.javaClass.simpleName}")
                            Log.e("SyncManager", "     Exception message: ${e.message}")
                            e.printStackTrace()
                        }
                    } else {
                        Log.d("SyncManager", "  ✓ Card already deleted on server: ${card.fullName}")
                    }
                } catch (e: CancellationException) {
                    throw e
                } catch (e: Exception) {
                    Log.e("SyncManager", "  ❌ Exception deleting card ${card.fullName} on server")
                    Log.e("SyncManager", "     Server ID: ${card.serverCardId}")
                    Log.e("SyncManager", "     Exception type: ${e.javaClass.simpleName}")
                    Log.e("SyncManager", "     Exception message: ${e.message}")
                    e.printStackTrace()
                    // Continue with other cards
                }
            } else {
                Log.w("SyncManager", "  ⚠️ Card has no server ID, skipping server deletion")
                Log.w("SyncManager", "     Card: ${card.fullName}")
                Log.w("SyncManager", "     Local ID: ${card.id}")
                Log.w("SyncManager", "     This card may have never been synced to the server")
            }
        }
        Log.d("SyncManager", "  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━")
        
        // Then, push non-deleted cards
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
        
        // Filter out deleted cards from server
        val nonDeletedServerCards = serverCards.filter { !(it.isDeleted == 1) }
        Log.d("SyncManager", "📦 Filtered from ${serverCards.size} to ${nonDeletedServerCards.size} non-deleted server cards")
        
        // Map DTOs to domain models
        val domainCards = nonDeletedServerCards.map { dto ->
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
        
        // Mark local cards as deleted if they're deleted on server
        val serverCardMap = serverCards.associateBy { it.id ?: "" }
        val localCards = businessCardRepository.getAllCardsSync()
        val cardsToMarkDeleted = localCards.filter { 
            // Mark as deleted if card has serverCardId and server says it's deleted
            !it.serverCardId.isNullOrBlank() && 
            serverCardMap[it.serverCardId]?.isDeleted == 1 &&
            !it.isDeleted
        }
        
        if (cardsToMarkDeleted.isNotEmpty()) {
            Log.d("SyncManager", "🗑️ Marking ${cardsToMarkDeleted.size} local cards as deleted (deleted on server)...")
            for (card in cardsToMarkDeleted) {
                try {
                    val deletedCard = card.copy(isDeleted = true, updatedAt = System.currentTimeMillis())
                    businessCardRepository.updateCard(deletedCard)
                    Log.d("SyncManager", "  🗑️ Marked local card as deleted: ${card.fullName} (Server ID: ${card.serverCardId})")
                } catch (e: Exception) {
                    Log.e("SyncManager", "  ❌ Failed to mark local card as deleted ${card.fullName}: ${e.message}")
                }
            }
        } else {
            Log.d("SyncManager", "✅ No local cards to mark as deleted")
        }
        
        // Delete local cards that no longer exist on the server (not in server list at all)
        val serverCardIds = nonDeletedServerCards.mapNotNull { it.id }.toSet()
        val cardsToDelete = localCards.filter { 
            // Only delete cards that have a serverCardId and it's not in the server list
            !it.serverCardId.isNullOrBlank() && 
            it.serverCardId !in serverCardIds &&
            !it.isDeleted
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
                
                // Log token details for debugging email with special characters
                val token = tokenManager.getToken()
                if (token != null && userEmail != null && userEmail.contains("+")) {
                    Log.d("SyncManager", "⚠️ Email contains '+' symbol: $userEmail")
                    Log.d("SyncManager", "   Token user ID: $userId")
                    // Try to decode token payload to verify user_id
                    try {
                        val parts = token.split(".")
                        if (parts.size == 3) {
                            val payload = String(android.util.Base64.decode(parts[1], android.util.Base64.URL_SAFE))
                            Log.d("SyncManager", "   Token payload: $payload")
                        }
                    } catch (e: Exception) {
                        Log.e("SyncManager", "   Error decoding token: ${e.message}")
                    }
                }
                
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
            
            // Log each contact received from server
            initialServerContacts.forEachIndexed { index, dto ->
                Log.d("SyncManager", "   Server contact ${index + 1}: ${dto.firstName} ${dto.lastName} (ID: ${dto.id}, user_id: ${dto.userId})")
            }
            
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
        val allLocalContacts = contactRepository.getAllContactsSync()
        // Only push non-deleted contacts
        val localContacts = allLocalContacts.filter { !it.isDeleted }
        Log.d("SyncManager", "📋 Found ${localContacts.size} local contacts to sync (filtered from ${allLocalContacts.size} total, excluding ${allLocalContacts.size - localContacts.size} deleted)")
        
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
                        val createdServerContact = createResponse.data
                        val serverContactId = createdServerContact.id
                        if (serverContactId != null && serverContactId != contact.id) {
                            // Server returned a different ID - update local contact with server ID and data
                            Log.d("SyncManager", "  🔄 Server returned different ID for contact: ${contact.fullName}")
                            Log.d("SyncManager", "     Local ID: ${contact.id}, Server ID: $serverContactId")
                            try {
                                // Hard delete local contact with old ID (for ID update)
                                contactRepository.hardDeleteContactById(contact.id)
                                Log.d("SyncManager", "  🗑️ Hard deleted local contact with old ID: ${contact.id}")
                                
                                // Insert the server contact with the server ID
                                val serverContactDomain = ContactDtoMapper.toDomain(createdServerContact)
                                contactRepository.insertContact(serverContactDomain)
                                Log.d("SyncManager", "  ✅ Inserted server contact with server ID: ${serverContactDomain.fullName} (ID: ${serverContactDomain.id})")
                                
                                // Verify the contact was inserted
                                val verifyContact = contactRepository.getContactById(serverContactDomain.id)
                                if (verifyContact != null) {
                                    Log.d("SyncManager", "  ✅ Verified contact exists locally: ${verifyContact.fullName}")
                                } else {
                                    Log.e("SyncManager", "  ❌ Contact was NOT found after insert! ID: ${serverContactDomain.id}")
                                }
                            } catch (e: Exception) {
                                Log.e("SyncManager", "  ❌ Failed to update local contact ID: ${e.message}", e)
                                e.printStackTrace()
                            }
                        } else if (serverContactId == contact.id) {
                            // Server returned same ID - just update local contact with server data
                            try {
                                val serverContactDomain = ContactDtoMapper.toDomain(createdServerContact)
                                contactRepository.updateContact(serverContactDomain)
                                Log.d("SyncManager", "  ✅ Updated local contact with server data: ${serverContactDomain.fullName}")
                            } catch (e: Exception) {
                                Log.e("SyncManager", "  ❌ Failed to update local contact: ${e.message}")
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
        
        // Filter out deleted contacts from server first
        val nonDeletedServerContacts = serverContacts.filter { !(it.isDeleted == 1) }
        Log.d("SyncManager", "📦 Filtered from ${serverContacts.size} to ${nonDeletedServerContacts.size} non-deleted server contacts")
        
        // Get current user info for filtering
        val currentUserId = tokenManager.getUserIdFromToken()
        val isDemo = tokenManager.isDemoAccount()
        val demoUserId = "demo-user-uuid-fixed"
        
        Log.d("SyncManager", "🔍 Filtering contacts - Current User ID: $currentUserId, IsDemo: $isDemo")
        
        // Filter out demo user contacts if current user is not demo
        val filteredContacts = if (!isDemo) {
            val beforeCount = nonDeletedServerContacts.size
            val filtered = nonDeletedServerContacts.filter { dto ->
                // Check if contact has id_user field and it's not the demo user ID
                val contactUserId = dto.userId
                val isDemoContact = contactUserId == demoUserId
                
                if (isDemoContact) {
                    Log.w("SyncManager", "⚠️ Filtering out demo contact: ${dto.firstName} ${dto.lastName} (id_user: $contactUserId)")
                }
                
                !isDemoContact
            }
            val afterCount = filtered.size
            if (beforeCount != afterCount) {
                Log.w("SyncManager", "⚠️ Filtered out ${beforeCount - afterCount} demo user contacts from sync")
            }
            filtered
        } else {
            nonDeletedServerContacts
        }
        
        // Additional safety: filter by current user ID if available
        // STRICT: Only sync contacts that belong to the current user
        val userFilteredContacts = if (currentUserId != null && !isDemo) {
            filteredContacts.filter { dto ->
                val contactUserId = dto.userId
                val matches = contactUserId == currentUserId
                if (!matches) {
                    if (contactUserId == null) {
                        Log.w("SyncManager", "⚠️ Filtering out contact with NULL user_id: ${dto.firstName} ${dto.lastName} (expected: $currentUserId)")
                    } else {
                        Log.w("SyncManager", "⚠️ Filtering out contact with wrong user_id: ${dto.firstName} ${dto.lastName} (contact user_id: $contactUserId, expected: $currentUserId)")
                    }
                }
                matches // STRICT: Only allow contacts that match the current user ID exactly
            }
        } else {
            filteredContacts
        }
        
        Log.d("SyncManager", "📦 Filtered from ${serverContacts.size} to ${userFilteredContacts.size} contacts")
        
        // Check for existing contacts by ID and leadId before inserting
        val localContacts = contactRepository.getAllContactsSync()
        val localContactsById = localContacts.associateBy { it.id }
        val localContactsByLeadId = localContacts
            .filter { !it.leadId.isNullOrBlank() }
            .associateBy { it.leadId!! }
        
        // Filter out contacts that would be duplicates based on leadId or update existing by ID
        val contactsToInsert = mutableListOf<com.sharemycard.android.domain.models.Contact>()
        val contactsToUpdate = mutableListOf<com.sharemycard.android.domain.models.Contact>()
        
        Log.d("SyncManager", "📋 Processing ${userFilteredContacts.size} contacts for sync")
        for ((index, dto) in userFilteredContacts.withIndex()) {
            val domainContact = ContactDtoMapper.toDomain(dto)
            Log.d("SyncManager", "📝 Processing contact ${index + 1}/${userFilteredContacts.size}: ${domainContact.fullName} (ID: ${domainContact.id}, user_id: ${dto.userId})")
            
            // First check if contact already exists locally by ID
            val existingContactById = localContactsById[domainContact.id]
            if (existingContactById != null) {
                // Contact exists locally - update it with server data
                Log.d("SyncManager", "🔄 Contact exists locally by ID: ${domainContact.fullName} (ID: ${domainContact.id})")
                contactsToUpdate.add(domainContact)
                continue
            }
            
            // Check if this contact has a leadId and if we already have a contact with that leadId (but different ID)
            // IMPORTANT: Only check for duplicates if leadId is not null, not blank, and not "0" (which means no lead)
            val leadId = domainContact.leadId
            if (!leadId.isNullOrBlank() && leadId != "0") {
                val existingContact = localContactsByLeadId[leadId]
                if (existingContact != null && existingContact.id != domainContact.id) {
                    // Duplicate found - update existing contact instead of creating new one
                    Log.w("SyncManager", "⚠️ Duplicate contact detected by leadId: $leadId")
                    Log.w("SyncManager", "   Existing contact ID: ${existingContact.id}, New contact ID: ${domainContact.id}")
                    Log.w("SyncManager", "   Updating existing contact instead of creating duplicate")
                    
                    // Update the existing contact with the server's data, but keep the existing ID
                    val updatedContact = existingContact.copy(
                        firstName = domainContact.firstName,
                        lastName = domainContact.lastName,
                        email = domainContact.email,
                        phone = domainContact.phone,
                        mobilePhone = domainContact.mobilePhone,
                        company = domainContact.company,
                        jobTitle = domainContact.jobTitle,
                        address = domainContact.address,
                        city = domainContact.city,
                        state = domainContact.state,
                        zipCode = domainContact.zipCode,
                        country = domainContact.country,
                        website = domainContact.website,
                        notes = domainContact.notes,
                        commentsFromLead = domainContact.commentsFromLead,
                        birthdate = domainContact.birthdate,
                        photoUrl = domainContact.photoUrl,
                        source = domainContact.source,
                        sourceMetadata = domainContact.sourceMetadata,
                        updatedAt = domainContact.updatedAt,
                        isDeleted = domainContact.isDeleted
                    )
                    contactsToUpdate.add(updatedContact)
                    continue
                }
            } else if (leadId == "0") {
                Log.d("SyncManager", "   ℹ️ Contact has leadId='0' (no lead), skipping duplicate check by leadId")
            }
            
            // New contact - insert it
            Log.d("SyncManager", "➕ New contact to insert: ${domainContact.fullName} (ID: ${domainContact.id})")
            contactsToInsert.add(domainContact)
        }
        
        // Update existing contacts that were duplicates
        if (contactsToUpdate.isNotEmpty()) {
            Log.d("SyncManager", "🔄 Updating ${contactsToUpdate.size} existing contacts to prevent duplicates")
            for (contact in contactsToUpdate) {
                try {
                    contactRepository.updateContact(contact)
                } catch (e: Exception) {
                    Log.e("SyncManager", "❌ Failed to update contact ${contact.fullName}: ${e.message}")
                }
            }
        }
        
        // Save new contacts to local database
        if (contactsToInsert.isNotEmpty()) {
            contactRepository.insertContacts(contactsToInsert)
            Log.d("SyncManager", "💾 Saved ${contactsToInsert.size} new contacts to local database")
        } else {
            Log.d("SyncManager", "💾 No new contacts to save (all were duplicates)")
        }
        
        val totalProcessed = contactsToInsert.size + contactsToUpdate.size
        Log.d("SyncManager", "═══════════════════════════════════════════════════════")
        Log.d("SyncManager", "📊 Contact Sync Summary:")
        Log.d("SyncManager", "   Total server contacts received: ${serverContacts.size}")
        Log.d("SyncManager", "   After filtering: ${userFilteredContacts.size}")
        Log.d("SyncManager", "   Contacts to insert: ${contactsToInsert.size}")
        Log.d("SyncManager", "   Contacts to update: ${contactsToUpdate.size}")
        Log.d("SyncManager", "   Total processed: $totalProcessed")
        Log.d("SyncManager", "═══════════════════════════════════════════════════════")
        Log.d("SyncManager", "✅ Processed $totalProcessed contacts (${contactsToInsert.size} new, ${contactsToUpdate.size} updated)")
        
        // Mark local contacts as deleted if they're deleted on server
        val serverContactMap = serverContacts.associateBy { it.id ?: "" }
        val allLocalContacts = contactRepository.getAllContactsSync()
        val contactsToMarkDeleted = allLocalContacts.filter { 
            // Mark as deleted if contact exists on server and server says it's deleted
            serverContactMap[it.id]?.isDeleted == 1 &&
            !it.isDeleted
        }
        
        if (contactsToMarkDeleted.isNotEmpty()) {
            Log.d("SyncManager", "🗑️ Marking ${contactsToMarkDeleted.size} local contacts as deleted (deleted on server)...")
            for (contact in contactsToMarkDeleted) {
                try {
                    val deletedContact = contact.copy(isDeleted = true, updatedAt = System.currentTimeMillis().toString())
                    contactRepository.updateContact(deletedContact)
                    Log.d("SyncManager", "  🗑️ Marked local contact as deleted: ${contact.fullName} (ID: ${contact.id})")
                } catch (e: Exception) {
                    Log.e("SyncManager", "  ❌ Failed to mark local contact as deleted ${contact.fullName}: ${e.message}")
                }
            }
        } else {
            Log.d("SyncManager", "✅ No local contacts to mark as deleted")
        }
        
        // Delete local contacts that no longer exist on the server (not in server list at all)
        val serverContactIds = userFilteredContacts.mapNotNull { it.id }.toSet()
        Log.d("SyncManager", "🔍 Checking for contacts to delete - Server has ${serverContactIds.size} contact IDs")
        Log.d("SyncManager", "🔍 Local has ${allLocalContacts.size} contacts (including deleted)")
        
        val contactsToDelete = allLocalContacts.filter { 
            val notOnServer = it.id !in serverContactIds
            val notDeletedLocally = !it.isDeleted
            val shouldDelete = notOnServer && notDeletedLocally
            
            if (shouldDelete) {
                Log.d("SyncManager", "  🎯 Contact marked for deletion: ${it.fullName} (ID: ${it.id})")
                Log.d("SyncManager", "     Reason: Not in server list (server has ${serverContactIds.size} contacts)")
            }
            
            shouldDelete
        }
        
        if (contactsToDelete.isNotEmpty()) {
            Log.d("SyncManager", "🗑️ Deleting ${contactsToDelete.size} local contacts that no longer exist on server...")
            for (contact in contactsToDelete) {
                try {
                    Log.d("SyncManager", "  🗑️ Deleting local contact: ${contact.fullName} (ID: ${contact.id})")
                    contactRepository.deleteContactById(contact.id)
                    Log.d("SyncManager", "  ✅ Successfully deleted local contact: ${contact.fullName}")
                } catch (e: Exception) {
                    Log.e("SyncManager", "  ❌ Failed to delete local contact ${contact.fullName}: ${e.message}", e)
                }
            }
        } else {
            Log.d("SyncManager", "✅ No local contacts to delete - all contacts exist on server")
            // Log all local contacts for debugging
            if (allLocalContacts.isNotEmpty()) {
                Log.d("SyncManager", "📋 Local contacts:")
                allLocalContacts.forEach { contact ->
                    val onServer = contact.id in serverContactIds
                    Log.d("SyncManager", "   - ${contact.fullName} (ID: ${contact.id}, isDeleted: ${contact.isDeleted}, onServer: $onServer)")
                }
            }
        }
    }
    
    private suspend fun syncLeads() {
        Log.d("SyncManager", "═══════════════════════════════════════════════════════")
        Log.d("SyncManager", "📡 Fetching leads from server...")
        
        // Log current user info for debugging
        val userId = tokenManager.getUserIdFromToken()
        val userEmail = tokenManager.getEmail()
        val isDemo = tokenManager.isDemoAccount()
        Log.d("SyncManager", "👤 Current user - ID: $userId, Email: $userEmail, IsDemo: $isDemo")
        
        if (isDemo) {
            Log.w("SyncManager", "⚠️ WARNING: Syncing with DEMO account! This may pull demo data.")
        }
        
        // Step 1: Fetch server leads first to compare
        val response = leadApi.getLeads()
        
        // Step 2: Push local lead deletions to server
        if (response.isSuccess && response.data != null) {
            val serverLeads = response.data
            val serverLeadMap = serverLeads.associateBy { it.id ?: "" }
            pushLocalLeadDeletions(serverLeadMap)
        }
        
        // Step 3: Re-fetch leads to get latest state after deletions
        val finalResponse = leadApi.getLeads()
        
        if (finalResponse.isSuccess && finalResponse.data != null) {
            val allServerLeads = finalResponse.data
            Log.d("SyncManager", "📦 Received ${allServerLeads.size} leads from server")
            
            // Log all leads received from server for debugging
            allServerLeads.forEach { dto ->
                Log.d("SyncManager", "  📥 Server Lead: ${dto.firstName} ${dto.lastName} (ID: ${dto.id}, userId: ${dto.userId}, businessCardId: ${dto.businessCardId}, isDeleted: ${dto.isDeleted}, createdAt: ${dto.createdAt})")
            }
            
            // Filter out deleted leads from server first
            val nonDeletedServerLeads = allServerLeads.filter { !(it.isDeleted == 1) }
            Log.d("SyncManager", "📦 Filtered from ${allServerLeads.size} to ${nonDeletedServerLeads.size} non-deleted server leads")
            
            // Log which leads were filtered out as deleted
            val deletedLeads = allServerLeads.filter { it.isDeleted == 1 }
            if (deletedLeads.isNotEmpty()) {
                Log.d("SyncManager", "🗑️ Filtered out ${deletedLeads.size} deleted leads:")
                deletedLeads.forEach { dto ->
                    Log.d("SyncManager", "  🗑️ Deleted: ${dto.firstName} ${dto.lastName} (ID: ${dto.id}, createdAt: ${dto.createdAt})")
                }
            }
            
            // Get current user info for filtering
            val currentUserId = tokenManager.getUserIdFromToken()
            val demoUserId = "demo-user-uuid-fixed"
            
            Log.d("SyncManager", "🔍 Filtering leads - Current User ID: $currentUserId, IsDemo: $isDemo")
            
            // Filter out demo user leads if current user is not demo
            val filteredLeads = if (!isDemo) {
                val beforeCount = nonDeletedServerLeads.size
                val filtered = nonDeletedServerLeads.filter { dto ->
                    // Check if lead has id_user field and it's not the demo user ID
                    val leadUserId = dto.userId
                    val isDemoLead = leadUserId == demoUserId
                    
                    if (isDemoLead) {
                        Log.w("SyncManager", "⚠️ Filtering out demo lead: ${dto.firstName} ${dto.lastName} (id_user: $leadUserId)")
                    }
                    
                    !isDemoLead
                }
                val afterCount = filtered.size
                if (beforeCount != afterCount) {
                    Log.w("SyncManager", "⚠️ Filtered out ${beforeCount - afterCount} demo user leads from sync")
                }
                filtered
            } else {
                nonDeletedServerLeads
            }
            
            // Note: Server already filters leads by (bc.user_id = ? OR l.id_user = ?)
            // So we trust the server's filtering and don't filter by userId here.
            // A lead might have wrong id_user but still belong to us via the business card.
            // Only filter out demo user leads (already done above).
            val userFilteredLeads = filteredLeads
            
            Log.d("SyncManager", "📦 Filtered from ${allServerLeads.size} to ${userFilteredLeads.size} user-filtered leads")
            
            // Log details about each lead being synced for debugging
            userFilteredLeads.forEach { dto ->
                Log.d("SyncManager", "  📋 Lead: ${dto.firstName} ${dto.lastName} (ID: ${dto.id}, userId: ${dto.userId}, businessCardId: ${dto.businessCardId}, createdAt: ${dto.createdAt})")
            }
            
            // Filter out leads with null IDs (they would cause issues)
            val validLeads = userFilteredLeads.filter { dto ->
                val hasValidId = !dto.id.isNullOrBlank()
                if (!hasValidId) {
                    Log.w("SyncManager", "⚠️ Skipping lead with null/empty ID: ${dto.firstName} ${dto.lastName} (createdAt: ${dto.createdAt})")
                }
                hasValidId
            }
            
            if (validLeads.size != userFilteredLeads.size) {
                Log.w("SyncManager", "⚠️ Filtered out ${userFilteredLeads.size - validLeads.size} leads with invalid IDs")
            }
            
            // Map DTOs to domain models
            val domainLeads = validLeads.map { dto ->
                try {
                    LeadDtoMapper.toDomain(dto)
                } catch (e: Exception) {
                    Log.e("SyncManager", "❌ Error mapping lead ${dto.firstName} ${dto.lastName} (ID: ${dto.id}): ${e.message}", e)
                    null
                }
            }.filterNotNull()
            
            if (domainLeads.size != validLeads.size) {
                Log.w("SyncManager", "⚠️ ${validLeads.size - domainLeads.size} leads failed to map to domain models")
            }
            
            // Save to local database (repository handles conflict resolution)
            leadRepository.insertLeads(domainLeads)
            Log.d("SyncManager", "💾 Saved ${domainLeads.size} leads to local database")
            
            // Mark local leads as deleted if they're deleted on server
            val serverLeadMap = allServerLeads.associateBy { it.id ?: "" }
            val localLeads = leadRepository.getAllLeadsSync()
            val leadsToMarkDeleted = localLeads.filter { 
                // Mark as deleted if lead exists on server and server says it's deleted
                serverLeadMap[it.id]?.isDeleted == 1 &&
                !it.isDeleted
            }
            
            if (leadsToMarkDeleted.isNotEmpty()) {
                Log.d("SyncManager", "🗑️ Marking ${leadsToMarkDeleted.size} local leads as deleted (deleted on server)...")
                for (lead in leadsToMarkDeleted) {
                    try {
                        val deletedLead = lead.copy(isDeleted = true, updatedAt = System.currentTimeMillis().toString())
                        leadRepository.updateLead(deletedLead)
                        Log.d("SyncManager", "  🗑️ Marked local lead as deleted: ${lead.displayName} (ID: ${lead.id})")
                    } catch (e: Exception) {
                        Log.e("SyncManager", "  ❌ Failed to mark local lead as deleted ${lead.displayName}: ${e.message}")
                    }
                }
            } else {
                Log.d("SyncManager", "✅ No local leads to mark as deleted")
            }
            
            // Delete local leads that no longer exist on the server (not in server list at all)
            val serverLeadIds = userFilteredLeads.mapNotNull { it.id }.toSet()
            val leadsToDelete = localLeads.filter { 
                it.id !in serverLeadIds &&
                !it.isDeleted
            }
            
            if (leadsToDelete.isNotEmpty()) {
                Log.d("SyncManager", "🗑️ Deleting ${leadsToDelete.size} local leads that no longer exist on server...")
                for (lead in leadsToDelete) {
                    try {
                        leadRepository.deleteLeadById(lead.id)
                        Log.d("SyncManager", "  🗑️ Deleted local lead: ${lead.displayName} (ID: ${lead.id})")
                    } catch (e: Exception) {
                        Log.e("SyncManager", "  ❌ Failed to delete local lead ${lead.displayName}: ${e.message}")
                    }
                }
            } else {
                Log.d("SyncManager", "✅ No local leads to delete - all leads exist on server")
            }
            
            Log.d("SyncManager", "═══════════════════════════════════════════════════════")
        } else {
            Log.e("SyncManager", "❌ Failed to fetch leads: ${finalResponse.message}")
            throw Exception("Failed to fetch leads: ${finalResponse.message}")
        }
    }
    
    /**
     * Push local lead deletions to server.
     * Deletes leads on server that are marked as deleted locally.
     */
    private suspend fun pushLocalLeadDeletions(
        serverLeadMap: Map<String, com.sharemycard.android.data.remote.models.LeadDTO>
    ) {
        Log.d("SyncManager", "⬆️ Pushing local lead deletions to server...")
        val localLeads = leadRepository.getAllLeadsIncludingDeleted()
        val deletedLeads = localLeads.filter { it.isDeleted }
        
        if (deletedLeads.isEmpty()) {
            Log.d("SyncManager", "✅ No deleted leads to push")
            return
        }
        
        Log.d("SyncManager", "📋 Found ${deletedLeads.size} deleted leads to push")
        
        for (lead in deletedLeads) {
            try {
                // Check if lead exists on server and is not already deleted
                val serverLead = serverLeadMap[lead.id]
                
                if (serverLead != null && serverLead.isDeleted != 1) {
                    // Lead exists on server and is not deleted - delete it
                    Log.d("SyncManager", "  📤 Deleting lead on server: ${lead.displayName} (ID: ${lead.id})")
                    
                    try {
                        val deleteResponse = leadApi.deleteLead(lead.id)
                        
                        if (deleteResponse.isSuccess) {
                            Log.d("SyncManager", "  ✅ Deleted lead on server: ${lead.displayName} (ID: ${lead.id})")
                        } else {
                            Log.w("SyncManager", "  ⚠️ Failed to delete lead ${lead.displayName} on server: ${deleteResponse.message}")
                        }
                    } catch (e: retrofit2.HttpException) {
                        if (e.code() == 400) {
                            // 400 might mean lead is converted to contact - this is expected
                            Log.w("SyncManager", "  ⚠️ Cannot delete lead ${lead.displayName} on server (may be converted to contact): ${e.message}")
                        } else {
                            Log.e("SyncManager", "  ❌ HTTP error deleting lead ${lead.displayName} on server: ${e.code()} - ${e.message}")
                        }
                    } catch (e: Exception) {
                        Log.e("SyncManager", "  ❌ Exception deleting lead ${lead.displayName} on server: ${e.message}", e)
                    }
                } else if (serverLead == null) {
                    Log.d("SyncManager", "  ⏭️ Lead ${lead.displayName} (ID: ${lead.id}) not found on server, skipping deletion")
                } else {
                    Log.d("SyncManager", "  ⏭️ Lead ${lead.displayName} (ID: ${lead.id}) already deleted on server")
                }
            } catch (e: Exception) {
                Log.e("SyncManager", "  ❌ Error processing lead deletion ${lead.displayName}: ${e.message}", e)
            }
        }
        
        Log.d("SyncManager", "✅ Finished pushing lead deletions")
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
            
            val currentTime = System.currentTimeMillis()
            val recentThreshold = currentTime - 30_000 // 30 seconds ago
            
            Log.d("SyncManager", "🕐 Current time: $currentTime (${java.util.Date(currentTime)})")
            Log.d("SyncManager", "🕐 Recent threshold: $recentThreshold (${java.util.Date(recentThreshold)})")
            
            // ========== SYNC CARDS ==========
            // Fetch server cards for comparison
            val cardResponse = cardApi.getCards()
            if (cardResponse.isSuccess && cardResponse.data != null) {
                val serverCardMap: Map<String, BusinessCardDTO> = cardResponse.data
                    .filter { it.id != null }
                    .associateBy { it.id!! }
                
                // Get local cards updated in last 30 seconds (excluding deleted)
                val allLocalCards = businessCardRepository.getAllCardsSync()
                
                val localCards = allLocalCards.filter { 
                    !it.isDeleted && 
                    // BusinessCard.updatedAt is already a Long timestamp
                    it.updatedAt >= recentThreshold
                }
                
                Log.d("SyncManager", "Found ${localCards.size} recent cards to sync (out of ${allLocalCards.size} total)")
                
                // Push only recent cards
                for (card in localCards) {
                    try {
                        val dto = BusinessCardDtoMapper.toDto(card)
                        
                        if (!card.serverCardId.isNullOrBlank()) {
                            val serverCard = serverCardMap[card.serverCardId]
                            if (shouldPushBasedOnTimestamp(card, serverCard)) {
                                val updateResponse = cardApi.updateCard(card.serverCardId!!, dto)
                                if (updateResponse.isSuccess) {
                                    Log.d("SyncManager", "Auto-synced card: ${card.fullName}")
                                } else {
                                    Log.e("SyncManager", "Failed to sync card ${card.fullName}: ${updateResponse.message}")
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
                                val createdServerContact = createResponse.data
                                val serverContactId = createdServerContact.id
                                if (serverContactId != null && serverContactId != contact.id) {
                                    // Server returned a different ID - update local contact with server ID and data
                                    Log.d("SyncManager", "  🔄 Server returned different ID for contact: ${contact.fullName}")
                                    Log.d("SyncManager", "     Local ID: ${contact.id}, Server ID: $serverContactId")
                                    try {
                                        // Hard delete local contact with old ID (for ID update)
                                        contactRepository.hardDeleteContactById(contact.id)
                                        Log.d("SyncManager", "  🗑️ Hard deleted local contact with old ID: ${contact.id}")
                                        
                                        // Insert the server contact with the server ID
                                        val serverContactDomain = ContactDtoMapper.toDomain(createdServerContact)
                                        contactRepository.insertContact(serverContactDomain)
                                        Log.d("SyncManager", "  ✅ Inserted server contact with server ID: ${serverContactDomain.fullName} (ID: ${serverContactDomain.id})")
                                        
                                        // Verify the contact was inserted
                                        val verifyContact = contactRepository.getContactById(serverContactDomain.id)
                                        if (verifyContact != null) {
                                            Log.d("SyncManager", "  ✅ Verified contact exists locally: ${verifyContact.fullName}")
                                        } else {
                                            Log.e("SyncManager", "  ❌ Contact was NOT found after insert! ID: ${serverContactDomain.id}")
                                        }
                                    } catch (e: Exception) {
                                        Log.e("SyncManager", "  ❌ Failed to update local contact ID: ${e.message}", e)
                                        e.printStackTrace()
                                    }
                                } else if (serverContactId == contact.id) {
                                    // Server returned same ID - just update local contact with server data
                                    try {
                                        val serverContactDomain = ContactDtoMapper.toDomain(createdServerContact)
                                        contactRepository.updateContact(serverContactDomain)
                                        Log.d("SyncManager", "  ✅ Updated local contact with server data: ${serverContactDomain.fullName}")
                                    } catch (e: Exception) {
                                        Log.e("SyncManager", "  ❌ Failed to update local contact: ${e.message}")
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
            
            // After pushing, pull to ensure local database has server data (especially for ID updates)
            Log.d("SyncManager", "🔄 Pulling server contacts after push to sync IDs...")
            try {
                val pullResponse = contactApi.getContacts()
                if (pullResponse.isSuccess && pullResponse.data != null) {
                    pullServerContacts(pullResponse.data)
                    Log.d("SyncManager", "✅ Pulled server contacts after push")
                } else {
                    Log.w("SyncManager", "⚠️ Failed to pull contacts after push: ${pullResponse.message}")
                }
            } catch (e: Exception) {
                Log.e("SyncManager", "❌ Error pulling contacts after push: ${e.message}", e)
                // Don't fail the whole sync if pull fails
            }
            
            // ========== SYNC LEAD DELETIONS ==========
            // Fetch server leads for comparison
            val leadResponse = leadApi.getLeads()
            if (leadResponse.isSuccess && leadResponse.data != null) {
                val serverLeadMap = leadResponse.data
                    .filter { it.id != null }
                    .associateBy { it.id!! }
                
                // Get local leads marked as deleted (including deleted ones)
                val allLocalLeads = leadRepository.getAllLeadsIncludingDeleted()
                val deletedLeads = allLocalLeads.filter { 
                    it.isDeleted &&
                    // Check if updated recently (within last 30 seconds) or if it's a new deletion
                    (it.updatedAt?.let { updatedAtStr ->
                        val updatedAt = DateParser.parseServerDate(updatedAtStr) ?: 0L
                        updatedAt >= recentThreshold
                    } ?: true) // If updatedAt is null, assume it's recent
                }
                
                Log.d("SyncManager", "📋 Found ${deletedLeads.size} recently deleted leads to sync")
                
                // Push lead deletions to server
                for (lead in deletedLeads) {
                    try {
                        val serverLead = serverLeadMap[lead.id]
                        
                        if (serverLead != null && serverLead.isDeleted != 1) {
                            // Lead exists on server and is not deleted - delete it
                            Log.d("SyncManager", "  📤 Deleting lead on server: ${lead.displayName} (ID: ${lead.id})")
                            
                            try {
                                val deleteResponse = leadApi.deleteLead(lead.id)
                                
                                if (deleteResponse.isSuccess) {
                                    Log.d("SyncManager", "  ✅ Deleted lead on server: ${lead.displayName} (ID: ${lead.id})")
                                } else {
                                    Log.w("SyncManager", "  ⚠️ Failed to delete lead ${lead.displayName} on server: ${deleteResponse.message}")
                                    errors.add("Failed to delete lead ${lead.displayName}: ${deleteResponse.message}")
                                }
                            } catch (e: retrofit2.HttpException) {
                                if (e.code() == 400) {
                                    // 400 might mean lead is converted to contact - this is expected
                                    Log.w("SyncManager", "  ⚠️ Cannot delete lead ${lead.displayName} on server (may be converted to contact): ${e.message}")
                                } else {
                                    Log.e("SyncManager", "  ❌ HTTP error deleting lead ${lead.displayName} on server: ${e.code()} - ${e.message}")
                                    errors.add("Failed to delete lead ${lead.displayName}: HTTP ${e.code()}")
                                }
                            } catch (e: Exception) {
                                Log.e("SyncManager", "  ❌ Exception deleting lead ${lead.displayName} on server: ${e.message}", e)
                                errors.add("Failed to delete lead ${lead.displayName}: ${e.message}")
                            }
                        } else if (serverLead == null) {
                            Log.d("SyncManager", "  ⏭️ Lead ${lead.displayName} (ID: ${lead.id}) not found on server, skipping deletion")
                        } else {
                            Log.d("SyncManager", "  ⏭️ Lead ${lead.displayName} (ID: ${lead.id}) already deleted on server")
                        }
                    } catch (e: Exception) {
                        Log.e("SyncManager", "  ❌ Error processing lead deletion ${lead.displayName}: ${e.message}", e)
                        errors.add("Failed to process lead deletion ${lead.displayName}: ${e.message}")
                    }
                }
            } else {
                Log.w("SyncManager", "⚠️ Failed to fetch leads from server: ${leadResponse.message}")
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

