package com.sharemycard.android.data.remote.api

import com.sharemycard.android.data.remote.models.ApiResponse
import com.sharemycard.android.data.remote.models.ContactDTO
import retrofit2.http.*

interface ContactApi {
    
    @GET("contacts/")
    suspend fun getContacts(): ApiResponse<List<ContactDTO>>
    
    @GET("contacts/")
    suspend fun getContact(@Query("id") id: String): ApiResponse<ContactDTO>
    
    @POST("contacts/")
    suspend fun createContact(@Body contact: ContactDTO): ApiResponse<ContactDTO>
    
    @PUT("contacts/")
    suspend fun updateContact(
        @Query("id") id: String,
        @Body contact: ContactDTO
    ): ApiResponse<ContactDTO>
    
    @DELETE("contacts/")
    suspend fun deleteContact(@Query("id") id: String): ApiResponse<Unit>
}

