# Soft Delete Implementation Plan: Cards, Contacts, and Leads

## Overview

Currently, when a card, contact, or lead is deleted from a device (iOS or Android), the record is permanently removed from the local database. However, upon the next sync, the record reappears because it still exists on the server. This creates a poor user experience where deleted items keep coming back.

**Solution**: Implement soft delete functionality using an `is_deleted` field (default 0) for all three entity types. When a record is deleted, set `is_deleted = 1` on the server. During sync, exclude records where `is_deleted = 1`.

## Scope

This change affects:
- **Database Schema**: Three tables (`business_cards`, `contacts`, `leads`)
- **API Endpoints**: Delete operations for all three entities
- **Sync Logic**: iOS and Android sync managers
- **Display Logic**: Website and mobile apps
- **Website**: Dashboard and admin views

---

## 1. Database Schema Changes

### 1.1 Add `is_deleted` Column

Add `is_deleted` field to three tables with default value of 0:

```sql
-- Migration script
ALTER TABLE business_cards ADD COLUMN is_deleted TINYINT(1) DEFAULT 0 NOT NULL;
ALTER TABLE contacts ADD COLUMN is_deleted TINYINT(1) DEFAULT 0 NOT NULL;
ALTER TABLE leads ADD COLUMN is_deleted TINYINT(1) DEFAULT 0 NOT NULL;

-- Add indexes for performance (queries will filter by is_deleted = 0)
CREATE INDEX idx_business_cards_is_deleted ON business_cards(is_deleted);
CREATE INDEX idx_contacts_is_deleted ON contacts(is_deleted);
CREATE INDEX idx_leads_is_deleted ON leads(is_deleted);
```

### 1.2 Migration Considerations

- **Existing Records**: All existing records will have `is_deleted = 0` (default), so they remain visible
- **No Data Loss**: This is a non-destructive change
- **Rollback Plan**: Can remove columns if needed (but would lose soft-delete state)

---

## 2. API Changes

### 2.1 Business Cards API (`/api/cards/index.php`)

**Current Behavior**: Sets `is_active = 0` (confusing - this is for active/inactive status, not deletion)

**New Behavior**: Set `is_deleted = 1` instead of (or in addition to) `is_active = 0`

```php
// Update deleteCard() method
private function deleteCard($cardId) {
    // Verify card belongs to user
    $card = $this->db->querySingle(
        "SELECT id FROM business_cards WHERE id = ? AND user_id = ? AND is_deleted = 0",
        [$cardId, $this->userId]
    );
    
    if (!$card) {
        $this->error('Card not found', 404);
    }
    
    // Soft delete
    $this->db->execute(
        "UPDATE business_cards SET is_deleted = 1, updated_at = NOW() WHERE id = ?",
        [$cardId]
    );
    
    $this->success([], 'Card deleted successfully');
}
```

**Update GET endpoints** to exclude deleted cards:
```php
// In getCards() method
$cards = $this->db->query(
    "SELECT * FROM business_cards WHERE user_id = ? AND is_deleted = 0 ORDER BY created_at DESC",
    [$this->userId]
);
```

### 2.2 Contacts API (`/api/contacts/index.php`)

**Current Behavior**: Hard DELETE statement

**New Behavior**: Set `is_deleted = 1`

```php
// Update DELETE case
case 'DELETE':
    // ... verification code ...
    
    if ($leadId) {
        // Contact came from lead - revert to lead (keep current behavior)
        // But also mark contact as deleted
        $stmt = $db->prepare("UPDATE contacts SET is_deleted = 1 WHERE id = ?");
        $stmt->execute([$actualContactId]);
        
        // Update lead status
        $stmt = $db->prepare("UPDATE leads SET notes = REPLACE(...), updated_at = NOW() WHERE id = ?");
        $stmt->execute([$leadId]);
        
        echo json_encode(['success' => true, 'message' => 'Contact reverted to lead successfully', ...]);
    } else {
        // Contact has no lead - soft delete
        $stmt = $db->prepare("UPDATE contacts SET is_deleted = 1, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$actualContactId]);
        
        echo json_encode(['success' => true, 'message' => 'Contact deleted successfully']);
    }
    break;
```

**Update GET endpoints**:
```php
// In getContacts() method
$contacts = $db->query(
    "SELECT * FROM contacts WHERE id_user = ? AND is_deleted = 0 ORDER BY created_at DESC",
    [$userId]
);
```

### 2.3 Leads API (`/api/leads/index.php`)

**Current Behavior**: Hard DELETE statement

**New Behavior**: Set `is_deleted = 1`

```php
// Update DELETE case
case 'DELETE':
    // ... verification code ...
    
    // Check if lead has been converted to contact
    $stmt = $db->prepare("SELECT id FROM contacts WHERE id_lead = ? AND is_deleted = 0");
    $stmt->execute([$leadId]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($contact) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot delete lead that has been converted to contact']);
        exit;
    }
    
    // Soft delete
    $stmt = $db->prepare("UPDATE leads SET is_deleted = 1, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$leadId]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Lead deleted successfully']);
    } else {
        throw new Exception('Failed to delete lead');
    }
    break;
```

**Update GET endpoints**:
```php
// In getLeads() method
$leads = $db->query(
    "SELECT * FROM leads WHERE ... AND is_deleted = 0 ORDER BY created_at DESC",
    [...]
);
```

### 2.4 DTO Changes

Update API response DTOs to include `is_deleted` field:

- `BusinessCardDTO`: Add `is_deleted` field
- `ContactDTO`: Add `is_deleted` field  
- `LeadDTO`: Add `is_deleted` field

---

## 3. iOS App Changes

### 3.1 Core Data Models

Add `isDeleted` property to:
- `BusinessCard` entity
- `Contact` entity
- `Lead` entity

```swift
@NSManaged public var isDeleted: Bool
```

Default value: `false` (0)

### 3.2 DataManager Changes

**Update delete methods** to set `isDeleted = true` locally and sync to server:

```swift
// BusinessCard deletion
func deleteCard(_ card: BusinessCard) async throws {
    // Mark as deleted locally
    card.isDeleted = true
    card.updatedAt = Date()
    try context.save()
    
    // Sync to server
    if let serverId = card.serverCardId {
        try await CardService.deleteCard(serverId)
    }
}

// Similar for Contact and Lead
```

**Update fetch queries** to exclude deleted records:

```swift
// In getAllCards()
let request: NSFetchRequest<BusinessCard> = BusinessCard.fetchRequest()
request.predicate = NSPredicate(format: "isDeleted == NO")
// ... rest of query
```

### 3.3 SyncManager Changes

**Update sync logic** to:
1. Filter out deleted records when pushing to server
2. Filter out deleted records when pulling from server
3. Handle server-side deletions (mark local records as deleted)

```swift
// In pushLocalCardsWithComparison()
let localCards = try dataManager.getAllCards()
    .filter { !$0.isDeleted } // Only push non-deleted cards

// In pullServerCards()
let serverCards = try await CardService.fetchCards()
    .filter { !($0.isDeleted ?? false) } // Only pull non-deleted cards

// Mark local cards as deleted if they're deleted on server
for localCard in localCards {
    if let serverId = localCard.serverCardId,
       let serverCard = serverCardMap[serverId],
       serverCard.isDeleted == true {
        localCard.isDeleted = true
        try context.save()
    }
}
```

**Similar updates needed for**:
- `syncContacts()`
- `syncLeads()`

### 3.4 Service Layer Changes

Update API service methods to handle `is_deleted` field in responses:

```swift
// CardService.swift
struct BusinessCardAPI: Codable {
    var isDeleted: Bool?
    // ... other fields
}
```

---

## 4. Android App Changes

### 4.1 Room Database Entities

Add `isDeleted` field to:
- `BusinessCardEntity`
- `ContactEntity`
- `LeadEntity`

```kotlin
@Entity(tableName = "business_cards")
data class BusinessCardEntity(
    // ... existing fields ...
    @ColumnInfo(name = "is_deleted")
    val isDeleted: Boolean = false
)
```

### 4.2 Domain Models

Add `isDeleted` property to domain models:

```kotlin
data class BusinessCard(
    // ... existing fields ...
    var isDeleted: Boolean = false
)
```

### 4.3 Repository Changes

**Update delete methods**:

```kotlin
// BusinessCardRepositoryImpl
override suspend fun deleteCard(card: BusinessCard) {
    // Mark as deleted locally
    val updatedCard = card.copy(isDeleted = true, updatedAt = System.currentTimeMillis())
    updateCard(updatedCard)
    
    // Sync to server
    if (!card.serverCardId.isNullOrBlank()) {
        try {
            val response = cardApi.deleteCard(card.serverCardId!!)
            if (response.isSuccess) {
                Log.d("BusinessCardRepository", "✅ Deleted card on server")
            }
        } catch (e: Exception) {
            Log.e("BusinessCardRepository", "❌ Error deleting card on server", e)
        }
    }
}
```

**Update query methods** to filter deleted records:

```kotlin
// In BusinessCardDao
@Query("SELECT * FROM business_cards WHERE is_deleted = 0 ORDER BY created_at DESC")
fun getAllCards(): Flow<List<BusinessCardEntity>>

@Query("SELECT * FROM business_cards WHERE id = :id AND is_deleted = 0")
suspend fun getCardById(id: String): BusinessCardEntity?

// Similar for ContactDao and LeadDao
```

### 4.4 SyncManager Changes

**Update sync logic**:

```kotlin
// In pushRecentChanges()
val localCards = businessCardRepository.getAllCardsSync()
    .filter { !it.isDeleted } // Only push non-deleted cards

// In pullServerCards()
val serverCards = cardApi.getCards().data
    ?.filter { !(it.isDeleted ?: false) } // Only pull non-deleted cards
    ?: emptyList()

// Mark local cards as deleted if deleted on server
for (localCard in localCards) {
    if (!localCard.serverCardId.isNullOrBlank()) {
        val serverCard = serverCardMap[localCard.serverCardId]
        if (serverCard?.isDeleted == true) {
            val deletedCard = localCard.copy(isDeleted = true, updatedAt = System.currentTimeMillis())
            businessCardRepository.updateCard(deletedCard)
        }
    }
}
```

**Similar updates needed for**:
- `syncContacts()`
- `syncLeads()`

### 4.5 DTO Mapper Changes

Update mappers to include `isDeleted`:

```kotlin
// BusinessCardDtoMapper
fun BusinessCardDTO.toDomain(): BusinessCard {
    return BusinessCard(
        // ... existing mappings ...
        isDeleted = this.isDeleted ?: false
    )
}

fun BusinessCard.toDto(): BusinessCardDTO {
    return BusinessCardDTO(
        // ... existing mappings ...
        isDeleted = if (this.isDeleted) 1 else 0
    )
}
```

---

## 5. Website Changes

### 5.1 Dashboard Queries

Update all queries to exclude deleted records:

```php
// In dashboard.php or similar
$cards = $db->query(
    "SELECT * FROM business_cards WHERE user_id = ? AND is_deleted = 0 ORDER BY created_at DESC",
    [$userId]
);

$contacts = $db->query(
    "SELECT * FROM contacts WHERE id_user = ? AND is_deleted = 0 ORDER BY created_at DESC",
    [$userId]
);

$leads = $db->query(
    "SELECT * FROM leads WHERE ... AND is_deleted = 0 ORDER BY created_at DESC",
    [...]
);
```

### 5.2 Admin Views

If admin views exist, they may want to:
- Show deleted records separately (e.g., "Deleted Items" section)
- Allow permanent deletion (hard delete)
- Allow restoration of soft-deleted items

### 5.3 Delete Operations

Update web-based delete operations to use soft delete:

```php
// In delete.php files
$db->execute(
    "UPDATE business_cards SET is_deleted = 1, updated_at = NOW() WHERE id = ? AND user_id = ?",
    [$cardId, $userId]
);
```

---

## 6. Migration Strategy

### 6.1 Database Migration

1. **Create migration script** (`migrations/add_is_deleted_fields.sql`):
   ```sql
   ALTER TABLE business_cards ADD COLUMN is_deleted TINYINT(1) DEFAULT 0 NOT NULL;
   ALTER TABLE contacts ADD COLUMN is_deleted TINYINT(1) DEFAULT 0 NOT NULL;
   ALTER TABLE leads ADD COLUMN is_deleted TINYINT(1) DEFAULT 0 NOT NULL;
   
   CREATE INDEX idx_business_cards_is_deleted ON business_cards(is_deleted);
   CREATE INDEX idx_contacts_is_deleted ON contacts(is_deleted);
   CREATE INDEX idx_leads_is_deleted ON leads(is_deleted);
   ```

2. **Test migration** on staging database first

3. **Run migration** on production during low-traffic period

### 6.2 App Updates

1. **Deploy API changes first** (backward compatible - existing apps will continue to work)
2. **Deploy iOS app update** (with new Core Data model version)
3. **Deploy Android app update** (with Room database migration)
4. **Deploy website changes**

### 6.3 Core Data Migration (iOS)

Add new Core Data model version with `isDeleted` field. Migration should:
- Set `isDeleted = false` for all existing records
- Use lightweight migration if possible

### 6.4 Room Migration (Android)

Create Room migration:

```kotlin
val MIGRATION_1_2 = object : Migration(1, 2) {
    override fun migrate(database: SupportSQLiteDatabase) {
        database.execSQL("ALTER TABLE business_cards ADD COLUMN is_deleted INTEGER NOT NULL DEFAULT 0")
        database.execSQL("ALTER TABLE contacts ADD COLUMN is_deleted INTEGER NOT NULL DEFAULT 0")
        database.execSQL("ALTER TABLE leads ADD COLUMN is_deleted INTEGER NOT NULL DEFAULT 0")
    }
}
```

---

## 7. Testing Checklist

### 7.1 API Testing

- [ ] Delete card via API - verify `is_deleted = 1`
- [ ] Get cards - verify deleted cards are excluded
- [ ] Delete contact via API - verify `is_deleted = 1`
- [ ] Get contacts - verify deleted contacts are excluded
- [ ] Delete lead via API - verify `is_deleted = 1`
- [ ] Get leads - verify deleted leads are excluded
- [ ] Verify deleted records don't appear in sync responses

### 7.2 iOS App Testing

- [ ] Delete card - verify local `isDeleted = true`
- [ ] Sync after delete - verify card doesn't reappear
- [ ] Delete contact - verify local `isDeleted = true`
- [ ] Sync after delete - verify contact doesn't reappear
- [ ] Delete lead - verify local `isDeleted = true`
- [ ] Sync after delete - verify lead doesn't reappear
- [ ] Test Core Data migration
- [ ] Test sync with server-side deletions

### 7.3 Android App Testing

- [ ] Delete card - verify local `isDeleted = true`
- [ ] Sync after delete - verify card doesn't reappear
- [ ] Delete contact - verify local `isDeleted = true`
- [ ] Sync after delete - verify contact doesn't reappear
- [ ] Delete lead - verify local `isDeleted = true`
- [ ] Sync after delete - verify lead doesn't reappear
- [ ] Test Room database migration
- [ ] Test sync with server-side deletions

### 7.4 Website Testing

- [ ] Dashboard shows only non-deleted records
- [ ] Delete operation sets `is_deleted = 1`
- [ ] Deleted records don't appear in lists
- [ ] Admin views (if applicable)

### 7.5 Cross-Platform Sync Testing

- [ ] Delete on iOS, sync on Android - verify deleted on Android
- [ ] Delete on Android, sync on iOS - verify deleted on iOS
- [ ] Delete on website, sync on mobile - verify deleted on mobile
- [ ] Delete on mobile, verify deleted on website after sync

---

## 8. Edge Cases and Considerations

### 8.1 Conflict Resolution

When syncing, if:
- **Local is deleted, server is not**: Push deletion to server
- **Server is deleted, local is not**: Mark local as deleted
- **Both are deleted**: No action needed

### 8.2 Converted Leads

When a lead is converted to a contact:
- The lead should remain (not be deleted)
- If the contact is deleted, it reverts to lead (existing behavior)
- Both lead and contact should have `is_deleted = 0` when active

### 8.3 Permanent Deletion

Consider adding a "permanent delete" feature for:
- Admin users
- Records deleted for X days (e.g., 30 days)
- User-initiated permanent deletion (with confirmation)

### 8.4 Data Retention

Consider:
- How long to keep soft-deleted records
- Whether to implement automatic permanent deletion after X days
- Whether to allow users to restore deleted items

### 8.5 Performance

- Indexes on `is_deleted` columns are critical for query performance
- Monitor query performance after migration
- Consider partitioning if tables become very large

---

## 9. Implementation Order

1. **Phase 1: Database & API** (Week 1)
   - Add `is_deleted` columns to database
   - Update API delete endpoints
   - Update API GET endpoints to filter deleted records
   - Test API changes

2. **Phase 2: iOS App** (Week 2)
   - Update Core Data models
   - Update DataManager delete methods
   - Update SyncManager
   - Test iOS changes

3. **Phase 3: Android App** (Week 3)
   - Update Room entities
   - Update repository delete methods
   - Update SyncManager
   - Test Android changes

4. **Phase 4: Website** (Week 4)
   - Update dashboard queries
   - Update delete operations
   - Test website changes

5. **Phase 5: Cross-Platform Testing** (Week 5)
   - Test sync across all platforms
   - Test edge cases
   - Performance testing

---

## 10. Rollback Plan

If issues arise:

1. **API Rollback**: Revert delete endpoints to hard delete (but keep `is_deleted` column)
2. **App Rollback**: Revert to previous app versions (but keep database schema)
3. **Database Rollback**: Can remove `is_deleted` columns if absolutely necessary (data loss for soft-deleted items)

---

## 11. Documentation Updates

Update the following documentation:
- API documentation (delete endpoints)
- iOS app documentation (sync behavior)
- Android app documentation (sync behavior)
- Database schema documentation
- User-facing documentation (if deletion behavior changes)

---

## 12. Success Criteria

- [ ] Deleted cards/contacts/leads don't reappear after sync
- [ ] All platforms (iOS, Android, Web) show consistent data
- [ ] Sync performance is not significantly impacted
- [ ] No data loss during migration
- [ ] All existing functionality continues to work
- [ ] Cross-platform sync works correctly

---

## Notes

- **Backward Compatibility**: API changes should be backward compatible initially (old apps will continue to work)
- **Gradual Rollout**: Consider feature flags to enable/disable soft delete per platform
- **Monitoring**: Add logging to track soft delete operations and sync behavior
- **User Communication**: If deletion behavior changes for users, communicate the change

---

**Last Updated**: [Date]
**Status**: Planning
**Priority**: High (affects core functionality)

