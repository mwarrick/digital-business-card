package com.sharemycard.android.data.repository

import android.util.Log
import com.sharemycard.android.data.local.database.dao.ContactDao
import com.sharemycard.android.data.local.mapper.ContactMapper
import com.sharemycard.android.data.remote.api.ContactApi
import com.sharemycard.android.domain.models.Contact
import com.sharemycard.android.domain.repository.ContactRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import javax.inject.Inject

class ContactRepositoryImpl @Inject constructor(
    private val contactDao: ContactDao,
    private val contactApi: ContactApi
) : ContactRepository {
    
    override fun getAllContacts(): Flow<List<Contact>> {
        return contactDao.getAllContactsFlow().map { entities ->
            entities.map { entity -> ContactMapper.toDomain(entity) }
        }
    }
    
    override suspend fun getAllContactsSync(): List<Contact> {
        val entities = contactDao.getAllContacts()
        return entities.map { entity -> ContactMapper.toDomain(entity) }
    }
    
    override suspend fun getContactById(id: String): Contact? {
        val entity = contactDao.getContactById(id) ?: return null
        return ContactMapper.toDomain(entity)
    }
    
    override suspend fun searchContacts(query: String): List<Contact> {
        val searchQuery = "%$query%"
        val entities = contactDao.searchContacts(searchQuery)
        return entities.map { ContactMapper.toDomain(it) }
    }
    
    override suspend fun getContactsBySource(source: String): List<Contact> {
        val entities = contactDao.getContactsBySource(source)
        return entities.map { ContactMapper.toDomain(it) }
    }
    
    override suspend fun getContactCount(): Int {
        return contactDao.getContactCount()
    }
    
    override fun getContactCountFlow(): Flow<Int> {
        return contactDao.getContactCountFlow()
    }
    
    override suspend fun insertContact(contact: Contact) {
        val entity = ContactMapper.toEntity(contact)
        contactDao.insertContact(entity)
    }
    
    override suspend fun insertContacts(contacts: List<Contact>) {
        val entities = contacts.map { ContactMapper.toEntity(it) }
        contactDao.insertContacts(entities)
    }
    
    override suspend fun updateContact(contact: Contact) {
        val entity = ContactMapper.toEntity(contact)
        contactDao.updateContact(entity)
    }
    
    override suspend fun deleteContact(contact: Contact) {
        // Delete on server first (if contact exists on server)
        try {
            val response = contactApi.deleteContact(contact.id)
            if (response.isSuccess) {
                Log.d("ContactRepository", "✅ Deleted contact on server: ${contact.fullName}")
            } else {
                Log.w("ContactRepository", "⚠️ Failed to delete contact on server: ${response.message}")
                // Continue with local deletion even if server deletion fails
            }
        } catch (e: Exception) {
            Log.e("ContactRepository", "❌ Error deleting contact on server: ${e.message}", e)
            // Continue with local deletion even if server deletion fails
        }
        
        // Delete locally
        contactDao.deleteContactById(contact.id)
        Log.d("ContactRepository", "🗑️ Deleted contact locally: ${contact.fullName}")
    }
    
    override suspend fun deleteContactById(id: String) {
        // Try to delete on server first
        try {
            val response = contactApi.deleteContact(id)
            if (response.isSuccess) {
                Log.d("ContactRepository", "✅ Deleted contact on server (ID: $id)")
            } else {
                Log.w("ContactRepository", "⚠️ Failed to delete contact on server: ${response.message}")
            }
        } catch (e: Exception) {
            Log.e("ContactRepository", "❌ Error deleting contact on server: ${e.message}", e)
            // Continue with local deletion even if server deletion fails
        }
        
        // Delete locally
        contactDao.deleteContactById(id)
        Log.d("ContactRepository", "🗑️ Deleted contact locally (ID: $id)")
    }
    
    override suspend fun deleteAllContacts() {
        contactDao.deleteAllContacts()
    }
    
    override suspend fun getPendingSyncContacts(): List<Contact> {
        val entities = contactDao.getPendingSyncContacts()
        return entities.map { ContactMapper.toDomain(it) }
    }
}

