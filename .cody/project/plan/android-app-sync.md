# Android App: Sync & Data Management Module

## Overview

This module handles synchronization between local Room database and the server, including conflict resolution, error handling, and retry logic.

## Features

### ✅ Implemented in iOS

1. **Full Sync**
   - Fetch server data
   - Push local changes (timestamp comparison)
   - Pull server changes
   - Sync business cards
   - Sync contacts
   - Sync leads

2. **Auto Sync**
   - Push only recent changes
   - Timestamp comparison
   - Triggered after local changes

3. **Conflict Resolution**
   - Last-write-wins based on timestamps
   - Server timestamp comparison
   - Local timestamp comparison

4. **Error Handling**
   - Cancellation error handling
   - Retry logic for transient failures
   - Graceful degradation

## SyncManager

### Implementation

```kotlin
class SyncManager @Inject constructor(
    private val cardRepository: CardRepository,
    private val contactRepository: ContactRepository,
    private val leadRepository: LeadRepository,
    private val cardService: CardService,
    private val contactService: ContactService,
    private val leadService: LeadService,
    private val mediaService: MediaService
) {
    suspend fun performFullSync() = withContext(Dispatchers.IO) {
        try {
            // 1. Sync business cards
            syncBusinessCards()
            
            // 2. Sync contacts
            syncContacts()
            
            // 3. Sync leads
            syncLeads()
        } catch (e: Exception) {
            throw SyncException("Sync failed: ${e.message}", e)
        }
    }
    
    private suspend fun syncBusinessCards() {
        // 1. Fetch server cards
        val serverCards = cardService.fetchCards()
        val serverCardMap = serverCards.associateBy { it.id }
        
        // 2. Push local changes with timestamp comparison
        pushLocalCardsWithComparison(serverCardMap)
        
        // 3. Pull server cards to local
        pullServerCards(serverCards)
    }
    
    private suspend fun pushLocalCardsWithComparison(
        serverCards: Map<String?, BusinessCardDTO>
    ) {
        val localCards = cardRepository.getAllCardsSync()
        
        for (card in localCards) {
            try {
                val apiCard = card.toDTO()
                
                if (card.serverCardId != null) {
                    val serverCard = serverCards[card.serverCardId]
                    if (shouldPushBasedOnTimestamp(card, serverCard)) {
                        cardService.updateCard(card.serverCardId!!, apiCard)
                    }
                } else {
                    val createdCard = cardService.createCard(apiCard)
                    cardRepository.updateCardServerId(card.id, createdCard.id!!)
                }
            } catch (e: Exception) {
                // Log and continue with other cards
            }
        }
    }
    
    private suspend fun pullServerCards(serverCards: List<BusinessCardDTO>) {
        for (serverCard in serverCards) {
            val localCard = serverCard.id?.let { 
                cardRepository.findCardByServerId(it) 
            }
            
            if (localCard != null) {
                if (shouldUpdateLocalCard(localCard, serverCard)) {
                    cardRepository.updateCard(serverCard.toDomain())
                    downloadImagesForCard(serverCard)
                }
            } else {
                cardRepository.insertCard(serverCard.toDomain())
                downloadImagesForCard(serverCard)
            }
        }
    }
    
    private fun shouldPushBasedOnTimestamp(
        localCard: BusinessCard,
        serverCard: BusinessCardDTO?
    ): Boolean {
        if (serverCard == null) return true
        
        val serverDate = serverCard.updatedAt?.parseServerDate() ?: return true
        return localCard.updatedAt > serverDate.time
    }
    
    private fun shouldUpdateLocalCard(
        localCard: BusinessCard,
        serverCard: BusinessCardDTO
    ): Boolean {
        val serverDate = serverCard.updatedAt?.parseServerDate() ?: return true
        return serverDate.time > localCard.updatedAt
    }
    
    private suspend fun downloadImagesForCard(card: BusinessCardDTO) {
        card.profilePhotoPath?.let { path ->
            try {
                val imageData = mediaService.downloadImage(path)
                // Save to local card
            } catch (e: Exception) {
                // Log error
            }
        }
        // Similar for logo and cover graphic
    }
    
    private suspend fun syncContacts() {
        try {
            val serverContacts = contactService.fetchContacts()
            val serverContactMap = serverContacts.associateBy { it.id }
            
            // Push local contacts
            pushLocalContacts(serverContactMap)
            
            // Pull server contacts
            pullServerContacts(serverContacts)
        } catch (e: Exception) {
            // Log error but don't fail entire sync
        }
    }
    
    private suspend fun syncLeads() {
        try {
            val serverLeads = leadService.fetchLeads()
            // Leads are view-only, just pull
            pullServerLeads(serverLeads)
        } catch (e: Exception) {
            // Log error but don't fail entire sync
        }
    }
    
    suspend fun pushToServer() {
        // Auto-sync: push only recent changes
        try {
            val serverCards = cardService.fetchCards()
            val serverCardMap = serverCards.associateBy { it.id }
            pushLocalCardsWithComparison(serverCardMap)
        } catch (e: Exception) {
            // Handle error
        }
    }
}
```

## Date Parsing

### DateParser

```kotlin
object DateParser {
    fun parseServerDate(dateString: String?): Date? {
        if (dateString == null || dateString.isEmpty()) return null
        
        // Try ISO8601 format first (with and without fractional seconds)
        val isoFormatter = ISO8601DateFormat()
        try {
            return isoFormatter.parse(dateString)
        } catch (e: Exception) {
            // Continue to next format
        }
        
        // Try MySQL DATETIME format: "YYYY-MM-DD HH:MM:SS"
        val mysqlFormatter = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.US)
        mysqlFormatter.timeZone = TimeZone.getTimeZone("UTC")
        try {
            return mysqlFormatter.parse(dateString)
        } catch (e: Exception) {
            // Continue to next format
        }
        
        // Try MySQL DATETIME with microseconds: "YYYY-MM-DD HH:MM:SS.ffffff"
        val mysqlMicroFormatter = SimpleDateFormat("yyyy-MM-dd HH:mm:ss.SSSSSS", Locale.US)
        mysqlMicroFormatter.timeZone = TimeZone.getTimeZone("UTC")
        try {
            return mysqlMicroFormatter.parse(dateString)
        } catch (e: Exception) {
            // Return null if all formats fail
            return null
        }
    }
}
```

## Error Handling

### Cancellation Handling

```kotlin
suspend fun syncWithRetry() {
    try {
        performFullSync()
    } catch (e: CancellationException) {
        // Retry once after delay
        delay(500)
        performFullSync()
    } catch (e: IOException) {
        if (e.message?.contains("cancelled") == true) {
            // Handle cancellation silently
            return
        }
        throw e
    }
}
```

### Concurrent Refresh Prevention

```kotlin
class SyncManager {
    private var isSyncing = false
    
    suspend fun performFullSync() {
        if (isSyncing) {
            return // Already syncing
        }
        
        isSyncing = true
        try {
            // Perform sync
        } finally {
            isSyncing = false
        }
    }
}
```

## Dependencies

```kotlin
dependencies {
    // Coroutines
    implementation("org.jetbrains.kotlinx:kotlinx-coroutines-android:1.7.3")
    
    // Room
    implementation("androidx.room:room-runtime:2.6.1")
    implementation("androidx.room:room-ktx:2.6.1")
}
```

## Integration Points

- **All Modules**: Syncs data for cards, contacts, leads
- **MediaService**: Downloads images during sync
- **Repositories**: Updates local database

