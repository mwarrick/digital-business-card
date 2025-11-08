package com.sharemycard.android.data.repository

import android.util.Log
import com.sharemycard.android.data.local.database.dao.*
import com.sharemycard.android.data.local.mapper.*
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
    private val addressDao: AddressDao
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
        val entity = BusinessCardMapper.toEntity(card)
        
        businessCardDao.insertCard(entity)
        
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
        cards.forEach { card ->
            // When syncing, we need to match by serverCardId to update existing cards
            // instead of creating duplicates
            if (!card.serverCardId.isNullOrBlank()) {
                // Check if a card with this serverCardId already exists
                val existingCard = getCardByServerId(card.serverCardId!!)
                if (existingCard != null) {
                    // Compare timestamps - only update if server is newer
                    val serverUpdatedAt = card.updatedAt
                    val localUpdatedAt = existingCard.updatedAt
                    
                    if (serverUpdatedAt > localUpdatedAt) {
                        // Server version is newer - update local card
                        Log.d("BusinessCardRepository", "🔄 Server is newer (${serverUpdatedAt} > ${localUpdatedAt}) - Updating card: ${existingCard.id} (serverCardId: ${card.serverCardId}) with new data: ${card.companyName}, ${card.jobTitle}")
                        val updatedCard = card.copy(id = existingCard.id)
                        insertCard(updatedCard)
                    } else {
                        // Local version is newer or equal - skip update
                        Log.d("BusinessCardRepository", "⏭️ Local is newer or equal (${localUpdatedAt} >= ${serverUpdatedAt}) - Skipping update for card: ${existingCard.id} (${card.companyName})")
                    }
                } else {
                    // Check if a card with the same local ID exists (in case server ID matches local ID)
                    val existingById = getCardById(card.id)
                    if (existingById != null) {
                        // Compare timestamps
                        val serverUpdatedAt = card.updatedAt
                        val localUpdatedAt = existingById.updatedAt
                        
                        if (serverUpdatedAt > localUpdatedAt) {
                            // Server version is newer - update
                            Log.d("BusinessCardRepository", "🔄 Server is newer (${serverUpdatedAt} > ${localUpdatedAt}) - Updating card by ID: ${card.id} with new data: ${card.companyName}, ${card.jobTitle}")
                            insertCard(card)
                        } else {
                            // Local version is newer or equal - skip update
                            Log.d("BusinessCardRepository", "⏭️ Local is newer or equal (${localUpdatedAt} >= ${serverUpdatedAt}) - Skipping update for card: ${card.id} (${card.companyName})")
                        }
                    } else {
                        // New card from server - use server ID as local ID
                        Log.d("BusinessCardRepository", "➕ Inserting new card from server: ${card.id} (${card.companyName})")
                        insertCard(card)
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
        businessCardDao.deleteCardById(card.id)
        // Related entities are deleted via CASCADE
    }
    
    override suspend fun deleteCardById(id: String) {
        businessCardDao.deleteCardById(id)
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
}

