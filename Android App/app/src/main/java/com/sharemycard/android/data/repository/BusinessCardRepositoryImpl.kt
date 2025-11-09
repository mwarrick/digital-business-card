package com.sharemycard.android.data.repository

import android.util.Log
import com.sharemycard.android.data.local.database.dao.*
import com.sharemycard.android.data.local.mapper.*
import com.sharemycard.android.data.remote.api.CardApi
import com.sharemycard.android.domain.models.BusinessCard
import com.sharemycard.android.domain.repository.BusinessCardRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flow
import kotlinx.coroutines.flow.map
import javax.inject.Inject

class BusinessCardRepositoryImpl @Inject constructor(
    private val businessCardDao: BusinessCardDao,
    private val emailContactDao: EmailContactDao,
    private val phoneContactDao: PhoneContactDao,
    private val websiteLinkDao: WebsiteLinkDao,
    private val addressDao: AddressDao,
    private val cardApi: CardApi
) : BusinessCardRepository {
    
    override fun getAllCards(): Flow<List<BusinessCard>> {
        return flow {
            // Collect from the Flow and emit mapped results
            businessCardDao.getAllCardsFlow().collect { entities ->
                val cards = entities.map { entity ->
                    val cardId = entity.id
                    BusinessCardMapper.toDomain(
                        entity = entity,
                        emails = emailContactDao.getEmailsByCardId(cardId),
                        phones = phoneContactDao.getPhonesByCardId(cardId),
                        websites = websiteLinkDao.getWebsitesByCardId(cardId),
                        address = addressDao.getAddressByCardId(cardId)
                    )
                }
                emit(cards)
            }
        }
    }
    
    override suspend fun getCardById(id: String): BusinessCard? {
        val entity = businessCardDao.getCardById(id) ?: return null
        return BusinessCardMapper.toDomain(
            entity = entity,
            emails = emailContactDao.getEmailsByCardId(id),
            phones = phoneContactDao.getPhonesByCardId(id),
            websites = websiteLinkDao.getWebsitesByCardId(id),
            address = addressDao.getAddressByCardId(id)
        )
    }
    
    override suspend fun getCardByServerId(serverCardId: String): BusinessCard? {
        val entity = businessCardDao.getCardByServerId(serverCardId) ?: return null
        val cardId = entity.id
        return BusinessCardMapper.toDomain(
            entity = entity,
            emails = emailContactDao.getEmailsByCardId(cardId),
            phones = phoneContactDao.getPhonesByCardId(cardId),
            websites = websiteLinkDao.getWebsitesByCardId(cardId),
            address = addressDao.getAddressByCardId(cardId)
        )
    }
    
    override suspend fun getActiveCards(): List<BusinessCard> {
        val entities = businessCardDao.getActiveCards()
        return entities.map { entity ->
            val cardId = entity.id
            BusinessCardMapper.toDomain(
                entity = entity,
                emails = emailContactDao.getEmailsByCardId(cardId),
                phones = phoneContactDao.getPhonesByCardId(cardId),
                websites = websiteLinkDao.getWebsitesByCardId(cardId),
                address = addressDao.getAddressByCardId(cardId)
            )
        }
    }
    
    override fun getActiveCardsFlow(): Flow<List<BusinessCard>> {
        return flow {
            val entities = businessCardDao.getActiveCards()
            val cards = entities.map { entity ->
                val cardId = entity.id
                BusinessCardMapper.toDomain(
                    entity = entity,
                    emails = emailContactDao.getEmailsByCardId(cardId),
                    phones = phoneContactDao.getPhonesByCardId(cardId),
                    websites = websiteLinkDao.getWebsitesByCardId(cardId),
                    address = addressDao.getAddressByCardId(cardId)
                )
            }
            emit(cards)
        }
    }
    
    override suspend fun getCardCount(): Int {
        return businessCardDao.getCardCount()
    }
    
    override fun getCardCountFlow(): Flow<Int> {
        return businessCardDao.getCardCountFlow()
    }
    
    override suspend fun insertCard(card: BusinessCard) {
        val cardId = card.id
        Log.d("BusinessCardRepository", "💾 insertCard called for: ${card.fullName}")
        Log.d("BusinessCardRepository", "   Card updatedAt: ${card.updatedAt} (${java.util.Date(card.updatedAt)})")
        val entity = BusinessCardMapper.toEntity(card)
        Log.d("BusinessCardRepository", "   Entity updatedAt: ${entity.updatedAt} (${java.util.Date(entity.updatedAt)})")
        
        businessCardDao.insertCard(entity)
        Log.d("BusinessCardRepository", "   ✅ Card saved to database with updatedAt: ${entity.updatedAt}")
        
        // Insert related entities
        emailContactDao.deleteEmailsByCardId(cardId)
        emailContactDao.insertEmails(BusinessCardMapper.toEmailEntities(cardId, card.additionalEmails))
        
        phoneContactDao.deletePhonesByCardId(cardId)
        phoneContactDao.insertPhones(BusinessCardMapper.toPhoneEntities(cardId, card.additionalPhones))
        
        websiteLinkDao.deleteWebsitesByCardId(cardId)
        websiteLinkDao.insertWebsites(BusinessCardMapper.toWebsiteEntities(cardId, card.websiteLinks))
        
        addressDao.deleteAddressByCardId(cardId)
        BusinessCardMapper.toAddressEntity(cardId, card.address)?.let {
            addressDao.insertAddress(it)
        }
    }
    
    override suspend fun insertCards(cards: List<BusinessCard>) {
        Log.d("BusinessCardRepository", "═══════════════════════════════════════════════════════")
        Log.d("BusinessCardRepository", "📥 insertCards called with ${cards.size} cards")
        Log.d("BusinessCardRepository", "═══════════════════════════════════════════════════════")
        cards.forEach { card ->
            Log.d("BusinessCardRepository", "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━")
            Log.d("BusinessCardRepository", "🔄 Processing card: ${card.fullName}")
            Log.d("BusinessCardRepository", "   Server Card ID: ${card.serverCardId}")
            Log.d("BusinessCardRepository", "   Server updatedAt: ${card.updatedAt} (${java.util.Date(card.updatedAt)})")
            
            // When syncing, we need to match by serverCardId to update existing cards
            // instead of creating duplicates
            if (!card.serverCardId.isNullOrBlank()) {
                // Check if a card with this serverCardId already exists
                val existingCard = getCardByServerId(card.serverCardId!!)
                if (existingCard != null) {
                    // Compare timestamps - only update if server is newer
                    val serverUpdatedAt = card.updatedAt
                    val localUpdatedAt = existingCard.updatedAt
                    val timeDiff = serverUpdatedAt - localUpdatedAt
                    
                    Log.d("BusinessCardRepository", "✅ FOUND existing card by serverCardId: ${card.serverCardId}")
                    Log.d("BusinessCardRepository", "   Local Card ID: ${existingCard.id}")
                    Log.d("BusinessCardRepository", "   ┌─────────────────────────────────────────┐")
                    Log.d("BusinessCardRepository", "   │ TIMESTAMP COMPARISON                     │")
                    Log.d("BusinessCardRepository", "   ├─────────────────────────────────────────┤")
                    Log.d("BusinessCardRepository", "   │ Local updatedAt:                         │")
                    Log.d("BusinessCardRepository", "   │   $localUpdatedAt                        │")
                    Log.d("BusinessCardRepository", "   │   ${java.util.Date(localUpdatedAt)}      │")
                    Log.d("BusinessCardRepository", "   ├─────────────────────────────────────────┤")
                    Log.d("BusinessCardRepository", "   │ Server updatedAt:                        │")
                    Log.d("BusinessCardRepository", "   │   $serverUpdatedAt                       │")
                    Log.d("BusinessCardRepository", "   │   ${java.util.Date(serverUpdatedAt)}     │")
                    Log.d("BusinessCardRepository", "   ├─────────────────────────────────────────┤")
                    Log.d("BusinessCardRepository", "   │ Difference: ${timeDiff}ms (${timeDiff / 1000}s)      │")
                    Log.d("BusinessCardRepository", "   │ Server > Local? ${serverUpdatedAt > localUpdatedAt}                          │")
                    Log.d("BusinessCardRepository", "   │ Server >= Local? ${serverUpdatedAt >= localUpdatedAt}                          │")
                    Log.d("BusinessCardRepository", "   └─────────────────────────────────────────┘")
                    
                    // Debug: Check if timestamps are actually equal
                    if (serverUpdatedAt == localUpdatedAt) {
                        Log.w("BusinessCardRepository", "⚠️ WARNING: Timestamps are EXACTLY equal!")
                        Log.w("BusinessCardRepository", "   This might indicate the local card was already updated")
                        Log.w("BusinessCardRepository", "   OR the server timestamp parsing is incorrect")
                        Log.w("BusinessCardRepository", "   Local card data: ${existingCard.companyName}, updated: ${java.util.Date(localUpdatedAt)}")
                        Log.w("BusinessCardRepository", "   Server card data: ${card.companyName}, updated: ${java.util.Date(serverUpdatedAt)}")
                        
                        // Check if data actually differs (ignoring ByteArray fields and timestamps)
                        val dataDiffers = existingCard.firstName != card.firstName ||
                                existingCard.lastName != card.lastName ||
                                existingCard.phoneNumber != card.phoneNumber ||
                                existingCard.companyName != card.companyName ||
                                existingCard.jobTitle != card.jobTitle ||
                                existingCard.bio != card.bio ||
                                existingCard.profilePhotoPath != card.profilePhotoPath ||
                                existingCard.companyLogoPath != card.companyLogoPath ||
                                existingCard.coverGraphicPath != card.coverGraphicPath ||
                                existingCard.theme != card.theme ||
                                existingCard.isActive != card.isActive
                        
                        if (dataDiffers) {
                            Log.w("BusinessCardRepository", "   ⚠️ Data DIFFERS even though timestamps are equal!")
                            Log.w("BusinessCardRepository", "   Will update to sync data changes")
                        } else {
                            Log.d("BusinessCardRepository", "   ✓ Data matches - no update needed")
                        }
                    }
                    
                    // Use >= instead of > to allow updates when timestamps are equal but data might differ
                    // This handles edge cases where timestamps match but data was updated
                    if (serverUpdatedAt >= localUpdatedAt) {
                        // Server version is newer or equal - update local card
                        val reason = if (serverUpdatedAt > localUpdatedAt) {
                            "Server is newer (by ${timeDiff}ms)"
                        } else {
                            "Timestamps equal but updating to ensure data sync"
                        }
                        Log.d("BusinessCardRepository", "✅ DECISION: UPDATE - $reason")
                        Log.d("BusinessCardRepository", "   Old: ${existingCard.companyName}, ${existingCard.jobTitle}")
                        Log.d("BusinessCardRepository", "   New: ${card.companyName}, ${card.jobTitle}")
                        
                        // CRITICAL: Preserve the server's updatedAt timestamp when updating
                        // The card from server already has the correct updatedAt, we just need to preserve it
                        val updatedCard = card.copy(
                            id = existingCard.id,
                            updatedAt = card.updatedAt  // Explicitly preserve server's updatedAt
                        )
                        Log.d("BusinessCardRepository", "   📝 Updating card with server updatedAt: ${updatedCard.updatedAt} (${java.util.Date(updatedCard.updatedAt)})")
                        Log.d("BusinessCardRepository", "   📝 Previous local updatedAt was: ${existingCard.updatedAt} (${java.util.Date(existingCard.updatedAt)})")
                        insertCard(updatedCard)
                        Log.d("BusinessCardRepository", "✅ Card updated successfully")
                    } else {
                        // Local version is newer - skip update
                        Log.d("BusinessCardRepository", "⏭️ DECISION: SKIP - Local is newer (diff: ${-timeDiff}ms)")
                        Log.d("BusinessCardRepository", "   Keeping local version: ${existingCard.companyName}")
                    }
                } else {
                    Log.d("BusinessCardRepository", "⚠️ No card found by serverCardId: ${card.serverCardId}")
                    Log.d("BusinessCardRepository", "   Searching all local cards for matching serverCardId...")
                    
                    // Debug: Check all local cards to see their serverCardIds
                    val allLocalCards = getAllCardsSync()
                    Log.d("BusinessCardRepository", "   Found ${allLocalCards.size} local cards")
                    allLocalCards.forEach { localCard ->
                        if (localCard.serverCardId == card.serverCardId) {
                            Log.d("BusinessCardRepository", "   ⚠️ Found local card with matching serverCardId but getCardByServerId returned null!")
                            Log.d("BusinessCardRepository", "     Local card ID: ${localCard.id}, serverCardId: ${localCard.serverCardId}")
                        }
                    }
                    
                    // No card found by serverCardId - check if a card with the same local ID exists
                    // (This can happen if the server ID was used as the local ID)
                    val existingById = getCardById(card.id)
                    if (existingById != null) {
                        // Compare timestamps
                        val serverUpdatedAt = card.updatedAt
                        val localUpdatedAt = existingById.updatedAt
                        val timeDiff = serverUpdatedAt - localUpdatedAt
                        
                        Log.d("BusinessCardRepository", "🔍 Found existing card by ID: ${card.id}")
                        Log.d("BusinessCardRepository", "   Local updatedAt: $localUpdatedAt (${java.util.Date(localUpdatedAt)})")
                        Log.d("BusinessCardRepository", "   Server updatedAt: $serverUpdatedAt (${java.util.Date(serverUpdatedAt)})")
                        Log.d("BusinessCardRepository", "   Time difference: ${timeDiff}ms (${timeDiff / 1000}s)")
                        
                        if (serverUpdatedAt > localUpdatedAt) {
                            // Server version is newer - update
                            Log.d("BusinessCardRepository", "🔄 Server is newer (by ${timeDiff}ms) - Updating card by ID: ${card.id}")
                            Log.d("BusinessCardRepository", "   Old: ${existingById.companyName}, ${existingById.jobTitle}")
                            Log.d("BusinessCardRepository", "   New: ${card.companyName}, ${card.jobTitle}")
                            // Preserve the existing local ID but update serverCardId if needed
                            // CRITICAL: Preserve the server's updatedAt timestamp
                            val updatedCard = card.copy(
                                id = existingById.id,
                                updatedAt = card.updatedAt  // Explicitly preserve server's updatedAt
                            )
                            Log.d("BusinessCardRepository", "   📝 Updating card with server updatedAt: ${updatedCard.updatedAt} (${java.util.Date(updatedCard.updatedAt)})")
                            insertCard(updatedCard)
                            Log.d("BusinessCardRepository", "✅ Card updated successfully by ID")
                        } else {
                            // Local version is newer or equal - skip update
                            Log.d("BusinessCardRepository", "⏭️ Local is newer or equal (diff: ${timeDiff}ms) - Skipping update for card: ${card.id} (${card.companyName})")
                        }
                    } else {
                        // Check all local cards to see if any match by name/phone (fuzzy matching)
                        // This handles cases where serverCardId might not be set or doesn't match
                        val allLocalCardsForMatching = getAllCardsSync()
                        val matchingCard = allLocalCardsForMatching.firstOrNull { localCard ->
                            // Match by name and phone number, and either:
                            // 1. serverCardId is blank (never synced), OR
                            // 2. serverCardId matches (should have been found above, but double-check)
                            val nameMatches = localCard.firstName.equals(card.firstName, ignoreCase = true) &&
                                    localCard.lastName.equals(card.lastName, ignoreCase = true)
                            val phoneMatches = localCard.phoneNumber == card.phoneNumber
                            val serverIdMatches = localCard.serverCardId == card.serverCardId
                            
                            nameMatches && phoneMatches && (localCard.serverCardId.isNullOrBlank() || serverIdMatches)
                        }
                        
                        if (matchingCard != null) {
                            // Found a matching local card - update it with server data
                            Log.d("BusinessCardRepository", "🔍 Found matching local card: ${matchingCard.id}")
                            Log.d("BusinessCardRepository", "   Matching by: ${card.fullName}, ${card.phoneNumber}")
                            Log.d("BusinessCardRepository", "   Local serverCardId: ${matchingCard.serverCardId}, Server serverCardId: ${card.serverCardId}")
                            val serverUpdatedAt = card.updatedAt
                            val localUpdatedAt = matchingCard.updatedAt
                            val timeDiff = serverUpdatedAt - localUpdatedAt
                            
                            Log.d("BusinessCardRepository", "   Local updatedAt: $localUpdatedAt (${java.util.Date(localUpdatedAt)})")
                            Log.d("BusinessCardRepository", "   Server updatedAt: $serverUpdatedAt (${java.util.Date(serverUpdatedAt)})")
                            Log.d("BusinessCardRepository", "   Time difference: ${timeDiff}ms (${timeDiff / 1000}s)")
                            
                            if (serverUpdatedAt >= localUpdatedAt) {
                                // Server version is newer or equal - update and set serverCardId
                                Log.d("BusinessCardRepository", "🔄 Server is newer or equal (by ${timeDiff}ms) - Updating local card with server data")
                                Log.d("BusinessCardRepository", "   Old: ${matchingCard.companyName}, ${matchingCard.jobTitle}")
                                Log.d("BusinessCardRepository", "   New: ${card.companyName}, ${card.jobTitle}")
                                // CRITICAL: Preserve the server's updatedAt timestamp
                                val updatedCard = card.copy(
                                    id = matchingCard.id,
                                    updatedAt = card.updatedAt  // Explicitly preserve server's updatedAt
                                )
                                Log.d("BusinessCardRepository", "   📝 Updating card with server updatedAt: ${updatedCard.updatedAt} (${java.util.Date(updatedCard.updatedAt)})")
                                insertCard(updatedCard)
                                Log.d("BusinessCardRepository", "✅ Card updated successfully via fuzzy match")
                            } else {
                                // Local is newer - just set the serverCardId without updating data
                                Log.d("BusinessCardRepository", "⏭️ Local is newer (by ${-timeDiff}ms) - Just setting serverCardId")
                                if (!card.serverCardId.isNullOrBlank()) {
                                    updateCardServerId(matchingCard.id, card.serverCardId!!)
                                }
                            }
                        } else {
                            // New card from server - insert it
                            Log.d("BusinessCardRepository", "➕ No matching local card found - Inserting new card from server")
                            Log.d("BusinessCardRepository", "   Card: ${card.fullName}, ${card.phoneNumber}, serverCardId: ${card.serverCardId}")
                            insertCard(card)
                        }
                    }
                }
            } else {
                // Card without server ID - just insert normally
                Log.d("BusinessCardRepository", "➕ Inserting card without server ID: ${card.id}")
                insertCard(card)
            }
        }
    }
    
    override suspend fun updateCard(card: BusinessCard) {
        card.updatedAt = System.currentTimeMillis()
        insertCard(card) // Insert handles update via REPLACE strategy
    }
    
    override suspend fun deleteCard(card: BusinessCard) {
        // Delete on server first (if card exists on server)
        if (!card.serverCardId.isNullOrBlank()) {
            try {
                val response = cardApi.deleteCard(card.serverCardId!!)
                if (response.isSuccess) {
                    Log.d("BusinessCardRepository", "✅ Deleted card on server: ${card.fullName}")
                } else {
                    Log.w("BusinessCardRepository", "⚠️ Failed to delete card on server: ${response.message}")
                    // Continue with local deletion even if server deletion fails
                }
            } catch (e: Exception) {
                Log.e("BusinessCardRepository", "❌ Error deleting card on server: ${e.message}", e)
                // Continue with local deletion even if server deletion fails
            }
        } else {
            Log.d("BusinessCardRepository", "ℹ️ Card has no server ID, skipping server deletion: ${card.fullName}")
        }
        
        // Delete locally
        businessCardDao.deleteCardById(card.id)
        // Related entities are deleted via CASCADE
        Log.d("BusinessCardRepository", "🗑️ Deleted card locally: ${card.fullName}")
    }
    
    override suspend fun deleteCardById(id: String) {
        // Get the card to check if it has a serverCardId
        val card = getCardById(id)
        if (card != null && !card.serverCardId.isNullOrBlank()) {
            try {
                val response = cardApi.deleteCard(card.serverCardId!!)
                if (response.isSuccess) {
                    Log.d("BusinessCardRepository", "✅ Deleted card on server (ID: $id, Server ID: ${card.serverCardId})")
                } else {
                    Log.w("BusinessCardRepository", "⚠️ Failed to delete card on server: ${response.message}")
                }
            } catch (e: Exception) {
                Log.e("BusinessCardRepository", "❌ Error deleting card on server: ${e.message}", e)
                // Continue with local deletion even if server deletion fails
            }
        }
        
        // Delete locally
        businessCardDao.deleteCardById(id)
        Log.d("BusinessCardRepository", "🗑️ Deleted card locally (ID: $id)")
    }
    
    override suspend fun deleteAllCards() {
        businessCardDao.deleteAllCards()
    }
    
    override suspend fun getPendingSyncCards(): List<BusinessCard> {
        val entities = businessCardDao.getPendingSyncCards()
        return entities.map { entity ->
            val cardId = entity.id
            BusinessCardMapper.toDomain(
                entity = entity,
                emails = emailContactDao.getEmailsByCardId(cardId),
                phones = phoneContactDao.getPhonesByCardId(cardId),
                websites = websiteLinkDao.getWebsitesByCardId(cardId),
                address = addressDao.getAddressByCardId(cardId)
            )
        }
    }
    
    override suspend fun updateCardServerId(cardId: String, serverCardId: String) {
        val entity = businessCardDao.getCardById(cardId)
        if (entity != null) {
            val updatedEntity = entity.copy(serverCardId = serverCardId)
            businessCardDao.updateCard(updatedEntity)
        }
    }
    
    override suspend fun getAllCardsSync(): List<BusinessCard> {
        val entities = businessCardDao.getAllCards()
        return entities.map { entity ->
            val cardId = entity.id
            BusinessCardMapper.toDomain(
                entity = entity,
                emails = emailContactDao.getEmailsByCardId(cardId),
                phones = phoneContactDao.getPhonesByCardId(cardId),
                websites = websiteLinkDao.getWebsitesByCardId(cardId),
                address = addressDao.getAddressByCardId(cardId)
            )
        }
    }
}

