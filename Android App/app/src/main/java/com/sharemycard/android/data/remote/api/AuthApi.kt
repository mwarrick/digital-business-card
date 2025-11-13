package com.sharemycard.android.data.remote.api

import com.sharemycard.android.data.remote.models.ApiResponse
import com.sharemycard.android.data.remote.models.auth.*
import retrofit2.http.*

interface AuthApi {
    @POST(ApiConfig.Endpoints.REGISTER)
    suspend fun register(
        @Body request: RegisterRequest
    ): ApiResponse<RegisterResponse>
    
    @POST(ApiConfig.Endpoints.LOGIN)
    suspend fun login(
        @Body request: LoginRequest
    ): ApiResponse<LoginResponse>
    
    @POST(ApiConfig.Endpoints.VERIFY)
    suspend fun verify(
        @Body request: VerifyRequest
    ): ApiResponse<VerifyResponse>
    
    @POST(ApiConfig.Endpoints.SET_PASSWORD)
    suspend fun setPassword(
        @Body request: PasswordSetRequest
    ): ApiResponse<Unit>
    
    @POST(ApiConfig.Endpoints.CHANGE_PASSWORD)
    suspend fun changePassword(
        @Body request: PasswordChangeRequest
    ): ApiResponse<Unit>
    
    @POST(ApiConfig.Endpoints.RESET_PASSWORD_REQUEST)
    suspend fun resetPasswordRequest(
        @Body request: PasswordResetRequest
    ): ApiResponse<Unit>
    
    @POST(ApiConfig.Endpoints.RESET_PASSWORD_COMPLETE)
    suspend fun resetPasswordComplete(
        @Body request: PasswordResetCompleteRequest
    ): ApiResponse<Unit>
    
    @POST(ApiConfig.Endpoints.RESEND_VERIFICATION)
    suspend fun resendVerification(
        @Body request: RegisterRequest
    ): ApiResponse<RegisterResponse>
    
    @GET(ApiConfig.Endpoints.CHECK_PASSWORD_STATUS)
    suspend fun checkPasswordStatus(
        @Query("email") email: String
    ): ApiResponse<PasswordStatusResponse>
}

