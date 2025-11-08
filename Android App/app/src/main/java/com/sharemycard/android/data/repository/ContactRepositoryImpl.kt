package com.sharemycard.android.data.repository

import com.sharemycard.android.data.local.database.dao.ContactDao
import com.sharemycard.android.data.local.mapper.ContactMapper
import com.sharemycard.android.domain.models.Contact
import com.sharemycard.android.domain.repository.ContactRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import javax.inject.Inject

class ContactRepositoryImpl @Inject constructor(
    private val contactDao: ContactDao
) : ContactRepository {
    
    override fun getAllContacts(): Flow<List<Contact>> {
        return contactDao.getAllContactsFlow().map { entities ->
            entities.map { entity -> ContactMapper.toDomain(entity) }
        }
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
        contactDao.deleteContactById(contact.id)
    }
    
    override suspend fun deleteContactById(id: String) {
        contactDao.deleteContactById(id)
    }
    
    override suspend fun deleteAllContacts() {
        contactDao.deleteAllContacts()
    }
    
    override suspend fun getPendingSyncContacts(): List<Contact> {
        val entities = contactDao.getPendingSyncContacts()
        return entities.map { ContactMapper.toDomain(it) }
    }
}

