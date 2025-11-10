package com.sharemycard.android.data.remote.api

import com.sharemycard.android.data.remote.models.ApiResponse
import com.sharemycard.android.data.remote.models.BusinessCardDTO
import retrofit2.http.*

interface CardApi {
    
    @GET("cards/")
    suspend fun getCards(): ApiResponse<List<BusinessCardDTO>>
    
    @GET("cards/")
    suspend fun getCard(@Query("id") id: String): ApiResponse<BusinessCardDTO>
    
    @POST("cards/")
    suspend fun createCard(@Body card: BusinessCardDTO): ApiResponse<BusinessCardDTO>
    
    @PUT("cards/")
    suspend fun updateCard(
        @Query("id") id: String,
        @Body card: BusinessCardDTO
    ): ApiResponse<BusinessCardDTO>
    
    @DELETE("cards/")
    suspend fun deleteCard(@Query("id") id: String): ApiResponse<Unit>
}

