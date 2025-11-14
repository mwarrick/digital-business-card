package com.sharemycard.android.domain.repository

import com.sharemycard.android.domain.models.Lead
import kotlinx.coroutines.flow.Flow

interface LeadRepository {
    fun getAllLeads(): Flow<List<Lead>>
    suspend fun getLeadById(id: String): Lead?
    suspend fun searchLeads(query: String): List<Lead>
    suspend fun getLeadsByStatus(status: String): List<Lead>
    suspend fun getLeadCount(): Int
    fun getLeadCountFlow(): Flow<Int>
    suspend fun getNewLeadCount(): Int
    fun getNewLeadCountFlow(): Flow<Int>
    suspend fun insertLead(lead: Lead)
    suspend fun insertLeads(leads: List<Lead>)
    suspend fun updateLead(lead: Lead)
    suspend fun deleteLead(lead: Lead)
    suspend fun deleteLeadById(id: String)
    suspend fun deleteAllLeads()
    suspend fun getPendingSyncLeads(): List<Lead>
    suspend fun getAllLeadsSync(): List<Lead>
    suspend fun getAllLeadsIncludingDeleted(): List<Lead>
    suspend fun convertLeadToContact(leadId: String): String // Returns contact ID
}

