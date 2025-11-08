package com.sharemycard.android.data.remote.api

import com.sharemycard.android.data.remote.models.ApiResponse
import com.sharemycard.android.data.remote.models.ContactDTO
import com.sharemycard.android.data.remote.models.LeadDTO
import retrofit2.http.*

interface LeadApi {
    
    @GET("leads/")
    suspend fun getLeads(): ApiResponse<List<LeadDTO>>
    
    @POST("leads/convert")
    suspend fun convertLeadToContact(
        @Query("id") leadId: String
    ): ApiResponse<ContactDTO>
}

