package com.sharemycard.android.data.repository

import android.util.Log
import com.sharemycard.android.data.local.database.dao.ContactDao
import com.sharemycard.android.data.local.mapper.ContactMapper
import com.sharemycard.android.data.remote.api.ContactApi
import com.sharemycard.android.domain.models.Contact
import com.sharemycard.android.domain.repository.ContactRepository
import com.sharemycard.android.domain.repository.LeadRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import javax.inject.Inject

class ContactRepositoryImpl @Inject constructor(
    private val contactDao: ContactDao,
    private val contactApi: ContactApi,
    private val leadRepository: LeadRepository
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
    
    override suspend fun getContactByLeadId(leadId: String): Contact? {
        val entity = contactDao.getContactByLeadId(leadId) ?: return null
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
        Log.d("ContactRepository", "═══════════════════════════════════════════════════════")
        Log.d("ContactRepository", "💾 INSERT CONTACT CALLED")
        Log.d("ContactRepository", "   Contact ID: ${contact.id}")
        Log.d("ContactRepository", "   Name: ${contact.firstName} ${contact.lastName}")
        Log.d("ContactRepository", "   Email: ${contact.email ?: "N/A"}")
        Log.d("ContactRepository", "   Created At: ${contact.createdAt}")
        Log.d("ContactRepository", "   Updated At: ${contact.updatedAt}")
        Log.d("ContactRepository", "   Source: ${contact.source}")
        Log.d("ContactRepository", "   isDeleted: ${contact.isDeleted}")
        
        val entity = ContactMapper.toEntity(contact)
        Log.d("ContactRepository", "📦 Converted to entity - calling contactDao.insertContact()...")
        
        contactDao.insertContact(entity)
        
        Log.d("ContactRepository", "✅ contactDao.insertContact() completed")
        
        // Verify immediately after insert - check both with and without isDeleted filter
        val verifyEntity = contactDao.getContactById(contact.id)
        if (verifyEntity != null) {
            Log.d("ContactRepository", "✅✅✅ CONTACT ENTITY FOUND IN DAO AFTER INSERT (with isDeleted filter) ✅✅✅")
            Log.d("ContactRepository", "   Entity ID: ${verifyEntity.id}")
            Log.d("ContactRepository", "   Entity Name: ${verifyEntity.firstName} ${verifyEntity.lastName}")
            Log.d("ContactRepository", "   Entity isDeleted: ${verifyEntity.isDeleted}")
        } else {
            Log.e("ContactRepository", "❌ CONTACT NOT FOUND with isDeleted filter - checking without filter...")
            
            // Check without isDeleted filter
            val verifyEntityIncludingDeleted = contactDao.getContactByIdIncludingDeleted(contact.id)
            if (verifyEntityIncludingDeleted != null) {
                Log.e("ContactRepository", "⚠️⚠️⚠️ CONTACT FOUND BUT MARKED AS DELETED ⚠️⚠️⚠️")
                Log.e("ContactRepository", "   Entity ID: ${verifyEntityIncludingDeleted.id}")
                Log.e("ContactRepository", "   Entity Name: ${verifyEntityIncludingDeleted.firstName} ${verifyEntityIncludingDeleted.lastName}")
                Log.e("ContactRepository", "   Entity isDeleted: ${verifyEntityIncludingDeleted.isDeleted}")
                Log.e("ContactRepository", "   This is a CRITICAL ERROR - new contact was marked as deleted!")
            } else {
                // Check all contacts to see if it exists at all
                val allEntities = contactDao.getAllContacts()
                Log.d("ContactRepository", "📋 Total contacts in DAO (non-deleted): ${allEntities.size}")
                val foundInAll = allEntities.find { it.id == contact.id }
                if (foundInAll != null) {
                    Log.e("ContactRepository", "⚠️ Contact found in getAllContacts() but NOT in getContactById()")
                    Log.e("ContactRepository", "   This suggests a query issue")
                    Log.e("ContactRepository", "   Found contact - ID: ${foundInAll.id}, isDeleted: ${foundInAll.isDeleted}")
                } else {
                    Log.e("ContactRepository", "❌❌❌ CONTACT NOT FOUND IN DAO AT ALL AFTER INSERT ❌❌❌")
                    Log.e("ContactRepository", "   Searched for ID: ${contact.id}")
                    Log.e("ContactRepository", "   Contact was NOT saved to database!")
                }
            }
        }
        
        Log.d("ContactRepository", "═══════════════════════════════════════════════════════")
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
        // Mark as deleted locally first
        val updatedContact = contact.copy(isDeleted = true, updatedAt = System.currentTimeMillis().toString())
        updateContact(updatedContact)
        Log.d("ContactRepository", "🗑️ Marked contact as deleted locally: ${contact.fullName}")
        
        // Delete on server (if contact exists on server)
        try {
            val response = contactApi.deleteContact(contact.id)
            if (response.isSuccess) {
                Log.d("ContactRepository", "✅ Deleted contact on server: ${contact.fullName}")
                
                // Check if contact was reverted to lead
                if (response.revertedToLead == true && !response.leadId.isNullOrBlank()) {
                    Log.d("ContactRepository", "🔄 Contact was reverted to lead: ${response.leadId}")
                    
                    // Update the lead status locally to "new"
                    try {
                        val lead = leadRepository.getLeadById(response.leadId)
                        if (lead != null) {
                            // Update lead status to "new" (remove "converted" status)
                            val updatedLead = lead.copy(
                                status = "new",
                                updatedAt = System.currentTimeMillis().toString()
                            )
                            leadRepository.updateLead(updatedLead)
                            Log.d("ContactRepository", "✅ Updated lead ${response.leadId} status to 'new'")
                        } else {
                            Log.w("ContactRepository", "⚠️ Lead ${response.leadId} not found locally")
                        }
                    } catch (e: Exception) {
                        Log.e("ContactRepository", "❌ Error updating lead status: ${e.message}", e)
                    }
                }
            } else {
                Log.w("ContactRepository", "⚠️ Failed to delete contact on server: ${response.message}")
            }
        } catch (e: Exception) {
            Log.e("ContactRepository", "❌ Error deleting contact on server: ${e.message}", e)
        }
    }
    
    override suspend fun deleteContactById(id: String) {
        // Get the contact to update
        val contact = getContactById(id)
        if (contact != null) {
            // Mark as deleted locally
            val updatedContact = contact.copy(isDeleted = true, updatedAt = System.currentTimeMillis().toString())
            updateContact(updatedContact)
            Log.d("ContactRepository", "🗑️ Marked contact as deleted locally (ID: $id)")
            
            // Delete on server
            try {
                val response = contactApi.deleteContact(id)
                if (response.isSuccess) {
                    Log.d("ContactRepository", "✅ Deleted contact on server (ID: $id)")
                    
                    // Check if contact was reverted to lead
                    if (response.revertedToLead == true && !response.leadId.isNullOrBlank()) {
                        Log.d("ContactRepository", "🔄 Contact was reverted to lead: ${response.leadId}")
                        
                        // Update the lead status locally to "new"
                        try {
                            val lead = leadRepository.getLeadById(response.leadId)
                            if (lead != null) {
                                // Update lead status to "new" (remove "converted" status)
                                val updatedLead = lead.copy(
                                    status = "new",
                                    updatedAt = System.currentTimeMillis().toString()
                                )
                                leadRepository.updateLead(updatedLead)
                                Log.d("ContactRepository", "✅ Updated lead ${response.leadId} status to 'new'")
                            } else {
                                Log.w("ContactRepository", "⚠️ Lead ${response.leadId} not found locally")
                            }
                        } catch (e: Exception) {
                            Log.e("ContactRepository", "❌ Error updating lead status: ${e.message}", e)
                        }
                    }
                } else {
                    Log.w("ContactRepository", "⚠️ Failed to delete contact on server: ${response.message}")
                }
            } catch (e: Exception) {
                Log.e("ContactRepository", "❌ Error deleting contact on server: ${e.message}", e)
            }
        } else {
            Log.w("ContactRepository", "⚠️ Contact not found (ID: $id)")
        }
    }
    
    override suspend fun hardDeleteContactById(id: String) {
        // Hard delete from database (for ID updates during sync)
        contactDao.hardDeleteContactById(id)
        Log.d("ContactRepository", "🗑️ Hard deleted contact (ID: $id)")
    }
    
    override suspend fun deleteAllContacts() {
        contactDao.deleteAllContacts()
    }
    
    override suspend fun getPendingSyncContacts(): List<Contact> {
        val entities = contactDao.getPendingSyncContacts()
        return entities.map { ContactMapper.toDomain(it) }
    }
}

