package com.sharemycard.android.data.remote.api

import com.sharemycard.android.data.remote.models.ApiResponse
import com.sharemycard.android.data.remote.models.LeadConvertResponse
import com.sharemycard.android.data.remote.models.LeadDTO
import retrofit2.http.*

interface LeadApi {
    
    @GET("leads/")
    suspend fun getLeads(): ApiResponse<List<LeadDTO>>
    
    @POST("leads/convert")
    suspend fun convertLeadToContact(
        @Body body: Map<String, String>
    ): ApiResponse<LeadConvertResponse>
    
    @DELETE("leads/")
    suspend fun deleteLead(@Query("id") id: String): ApiResponse<Unit>
}

