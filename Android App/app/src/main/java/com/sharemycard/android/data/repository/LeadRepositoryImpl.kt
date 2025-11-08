package com.sharemycard.android.data.repository

import com.sharemycard.android.data.local.database.dao.LeadDao
import com.sharemycard.android.data.local.mapper.LeadMapper
import com.sharemycard.android.domain.models.Lead
import com.sharemycard.android.domain.repository.LeadRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import javax.inject.Inject

class LeadRepositoryImpl @Inject constructor(
    private val leadDao: LeadDao
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
        leadDao.deleteLeadById(lead.id)
    }
    
    override suspend fun deleteLeadById(id: String) {
        leadDao.deleteLeadById(id)
    }
    
    override suspend fun deleteAllLeads() {
        leadDao.deleteAllLeads()
    }
    
    override suspend fun getPendingSyncLeads(): List<Lead> {
        val entities = leadDao.getPendingSyncLeads()
        return entities.map { LeadMapper.toDomain(it) }
    }
}

