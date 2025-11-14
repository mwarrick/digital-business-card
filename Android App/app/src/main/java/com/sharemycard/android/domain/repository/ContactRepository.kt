package com.sharemycard.android.domain.repository

import com.sharemycard.android.domain.models.Contact
import kotlinx.coroutines.flow.Flow

interface ContactRepository {
    fun getAllContacts(): Flow<List<Contact>>
    suspend fun getAllContactsSync(): List<Contact>
    suspend fun getContactById(id: String): Contact?
    suspend fun getContactByLeadId(leadId: String): Contact?
    suspend fun searchContacts(query: String): List<Contact>
    suspend fun getContactsBySource(source: String): List<Contact>
    suspend fun getContactCount(): Int
    fun getContactCountFlow(): Flow<Int>
    suspend fun insertContact(contact: Contact)
    suspend fun insertContacts(contacts: List<Contact>)
    suspend fun updateContact(contact: Contact)
    suspend fun deleteContact(contact: Contact)
    suspend fun deleteContactById(id: String)
    suspend fun hardDeleteContactById(id: String) // Hard delete for ID updates
    suspend fun deleteAllContacts()
    suspend fun getPendingSyncContacts(): List<Contact>
}

