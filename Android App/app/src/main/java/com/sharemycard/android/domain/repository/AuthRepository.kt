package com.sharemycard.android.domain.repository

import com.sharemycard.android.data.remote.models.auth.*

interface AuthRepository {
    suspend fun register(email: String): Result<RegisterResponse>
    suspend fun login(email: String, forceEmailCode: Boolean = false): Result<LoginResponse>
    suspend fun verify(email: String, code: String? = null, password: String? = null): Result<VerifyResponse>
    suspend fun resendVerification(email: String): Result<RegisterResponse>
    suspend fun loginDemo(): Result<VerifyResponse>
    suspend fun setPassword(email: String, password: String): Result<Unit>
    suspend fun changePassword(email: String, currentPassword: String, newPassword: String): Result<Unit>
    suspend fun resetPassword(email: String): Result<Unit>
    suspend fun resetPasswordComplete(email: String, code: String, newPassword: String): Result<Unit>
    suspend fun checkPasswordStatus(email: String): Result<Boolean>
    suspend fun clearUserData()
    suspend fun logout()
    fun isAuthenticated(): Boolean
    fun getCurrentEmail(): String?
}

