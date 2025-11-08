package com.sharemycard.android.domain.repository

import com.sharemycard.android.domain.models.BusinessCard
import kotlinx.coroutines.flow.Flow

interface BusinessCardRepository {
    fun getAllCards(): Flow<List<BusinessCard>>
    suspend fun getCardById(id: String): BusinessCard?
    suspend fun getCardByServerId(serverCardId: String): BusinessCard?
    suspend fun getActiveCards(): List<BusinessCard>
    fun getActiveCardsFlow(): Flow<List<BusinessCard>>
    suspend fun getCardCount(): Int
    fun getCardCountFlow(): Flow<Int>
    suspend fun insertCard(card: BusinessCard)
    suspend fun insertCards(cards: List<BusinessCard>)
    suspend fun updateCard(card: BusinessCard)
    suspend fun deleteCard(card: BusinessCard)
    suspend fun deleteCardById(id: String)
    suspend fun deleteAllCards()
    suspend fun getPendingSyncCards(): List<BusinessCard>
}

