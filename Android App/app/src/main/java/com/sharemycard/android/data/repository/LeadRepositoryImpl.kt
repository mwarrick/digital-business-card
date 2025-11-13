package com.sharemycard.android.data.repository

import android.util.Log
import com.sharemycard.android.data.local.database.dao.LeadDao
import com.sharemycard.android.data.local.mapper.LeadMapper
import com.sharemycard.android.data.remote.api.LeadApi
import com.sharemycard.android.domain.models.Lead
import com.sharemycard.android.domain.repository.LeadRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import javax.inject.Inject

class LeadRepositoryImpl @Inject constructor(
    private val leadDao: LeadDao,
    private val leadApi: LeadApi
) : LeadRepository {
    
    override fun getAllLeads(): Flow<List<Lead>> {
        return leadDao.getAllLeadsFlow().map { entities ->
            entities.map { entity -> LeadMapper.toDomain(entity) }
        }
    }
    
    override suspend fun getLeadById(id: String): Lead? {
        val entity = leadDao.getLeadById(id) ?: return null
        return LeadMapper.toDomain(entity)
    }
    
    override suspend fun searchLeads(query: String): List<Lead> {
        val searchQuery = "%$query%"
        val entities = leadDao.searchLeads(searchQuery)
        return entities.map { LeadMapper.toDomain(it) }
    }
    
    override suspend fun getLeadsByStatus(status: String): List<Lead> {
        val entities = leadDao.getLeadsByStatus(status)
        return entities.map { LeadMapper.toDomain(it) }
    }
    
    override suspend fun getLeadCount(): Int {
        return leadDao.getLeadCount()
    }
    
    override fun getLeadCountFlow(): Flow<Int> {
        return leadDao.getLeadCountFlow()
    }
    
    override suspend fun getNewLeadCount(): Int {
        return leadDao.getNewLeadCount()
    }
    
    override fun getNewLeadCountFlow(): Flow<Int> {
        return leadDao.getNewLeadCountFlow()
    }
    
    override suspend fun insertLead(lead: Lead) {
        val entity = LeadMapper.toEntity(lead)
        leadDao.insertLead(entity)
    }
    
    override suspend fun insertLeads(leads: List<Lead>) {
        val entities = leads.map { LeadMapper.toEntity(it) }
        leadDao.insertLeads(entities)
    }
    
    override suspend fun updateLead(lead: Lead) {
        val entity = LeadMapper.toEntity(lead)
        leadDao.updateLead(entity)
    }
    
    override suspend fun deleteLead(lead: Lead) {
        // Mark as deleted locally
        val updatedLead = lead.copy(isDeleted = true, updatedAt = System.currentTimeMillis().toString())
        updateLead(updatedLead)
    }
    
    override suspend fun deleteLeadById(id: String) {
        // Get the lead to update
        val lead = getLeadById(id)
        if (lead != null) {
            // Mark as deleted locally
            val updatedLead = lead.copy(isDeleted = true, updatedAt = System.currentTimeMillis().toString())
            updateLead(updatedLead)
        }
    }
    
    override suspend fun deleteAllLeads() {
        leadDao.deleteAllLeads()
    }
    
    override suspend fun getPendingSyncLeads(): List<Lead> {
        val entities = leadDao.getPendingSyncLeads()
        return entities.map { LeadMapper.toDomain(it) }
    }
    
    override suspend fun getAllLeadsSync(): List<Lead> {
        val entities = leadDao.getAllLeads()
        return entities.map { LeadMapper.toDomain(it) }
    }
    
    override suspend fun convertLeadToContact(leadId: String): String {
        Log.d("LeadRepository", "🔄 Converting lead to contact: $leadId")
        
        try {
            Log.d("LeadRepository", "📤 Sending conversion request with body: {lead_id: $leadId}")
            val requestBody = mapOf("lead_id" to leadId)
            
            val response = try {
                leadApi.convertLeadToContact(requestBody)
            } catch (e: retrofit2.HttpException) {
                val errorBody = e.response()?.errorBody()?.string()
                Log.e("LeadRepository", "❌ HTTP Error: ${e.code()} - ${e.message()}")
                Log.e("LeadRepository", "❌ Error body: $errorBody")
                throw Exception("Server error (${e.code()}): ${errorBody ?: e.message()}")
            } catch (e: java.io.IOException) {
                Log.e("LeadRepository", "❌ Network error: ${e.message}", e)
                throw Exception("Network error: ${e.message}")
            } catch (e: Exception) {
                Log.e("LeadRepository", "❌ Unexpected error: ${e.message}", e)
                throw e
            }
            
            Log.d("LeadRepository", "📥 Received response - success: ${response.isSuccess}, message: ${response.message}")
            Log.d("LeadRepository", "📥 Response data: ${response.data}")
            
            if (!response.isSuccess) {
                val errorMsg = response.message ?: "Unknown error"
                Log.e("LeadRepository", "❌ Server returned error: $errorMsg")
                throw Exception("Failed to convert lead: $errorMsg")
            }
            
            // The server returns { contact_id: "123" } in the data field
            val convertResponse = response.data
            if (convertResponse == null) {
                Log.e("LeadRepository", "❌ Response data is null")
                throw Exception("No contact ID returned from server")
            }
            
            val contactId = convertResponse.contactId
            if (contactId.isBlank()) {
                Log.e("LeadRepository", "❌ Contact ID is blank")
                throw Exception("Contact ID is empty")
            }
            
            Log.d("LeadRepository", "✅ Lead converted to contact: $contactId")
            
            // Update the lead status to "converted" locally
            val lead = getLeadById(leadId)
            if (lead != null) {
                val updatedLead = lead.copy(
                    status = "converted",
                    updatedAt = System.currentTimeMillis().toString()
                )
                updateLead(updatedLead)
                Log.d("LeadRepository", "✅ Updated lead status to 'converted'")
            } else {
                Log.w("LeadRepository", "⚠️ Lead $leadId not found locally")
            }
            
            // Note: The contact will be synced from the server on the next sync
            // We don't create it locally here because we don't have the full contact data
            
            return contactId
        } catch (e: Exception) {
            Log.e("LeadRepository", "❌ Error converting lead to contact: ${e.message}", e)
            Log.e("LeadRepository", "❌ Exception type: ${e.javaClass.simpleName}")
            e.printStackTrace()
            throw e
        }
    }
}

