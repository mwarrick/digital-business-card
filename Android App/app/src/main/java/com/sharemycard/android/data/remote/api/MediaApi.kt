package com.sharemycard.android.data.remote.api

import com.sharemycard.android.data.remote.models.ApiResponse
import com.sharemycard.android.data.remote.models.DeleteMediaRequest
import com.sharemycard.android.data.remote.models.MediaUploadResponse
import okhttp3.MultipartBody
import okhttp3.RequestBody
import okhttp3.ResponseBody
import retrofit2.http.*

interface MediaApi {
    
    @Multipart
    @POST("media/upload")
    suspend fun uploadImage(
        @Part("business_card_id") cardId: RequestBody,
        @Part("media_type") mediaType: RequestBody,
        @Part file: MultipartBody.Part
    ): ApiResponse<MediaUploadResponse>
    
    @GET("media/view")
    suspend fun downloadImage(@Query("file") filename: String): ResponseBody
    
    @HTTP(method = "DELETE", path = "media/delete", hasBody = true)
    suspend fun deleteImage(@Body request: DeleteMediaRequest): ApiResponse<Unit>
}

