package com.sharemycard.android.data.repository

import android.util.Log
import com.sharemycard.android.data.local.TokenManager
import com.sharemycard.android.data.remote.api.AuthApi
import com.sharemycard.android.data.remote.models.ApiResponse
import com.sharemycard.android.data.remote.models.auth.*
import com.sharemycard.android.domain.repository.AuthRepository
import com.sharemycard.android.domain.repository.BusinessCardRepository
import com.sharemycard.android.domain.repository.ContactRepository
import com.sharemycard.android.domain.repository.LeadRepository
import retrofit2.HttpException
import javax.inject.Inject

class AuthRepositoryImpl @Inject constructor(
    private val authApi: AuthApi,
    private val tokenManager: TokenManager,
    private val businessCardRepository: BusinessCardRepository,
    private val contactRepository: ContactRepository,
    private val leadRepository: LeadRepository
) : AuthRepository {
    
    override suspend fun register(email: String): Result<RegisterResponse> {
        return try {
            android.util.Log.d("AuthRepository", "Attempting registration for email: $email")
            val response = authApi.register(RegisterRequest(email))
            android.util.Log.d("AuthRepository", "Registration response - success: ${response.isSuccess}, message: ${response.message}")
            
            if (response.isSuccess && response.data != null) {
                Result.success(response.data)
            } else {
                val errorMsg = response.message ?: "Registration failed"
                android.util.Log.e("AuthRepository", "Registration failed: $errorMsg")
                Result.failure(Exception(errorMsg))
            }
        } catch (e: HttpException) {
            android.util.Log.e("AuthRepository", "HTTP error during registration: ${e.code()}", e)
            // Try to parse error response body
            val errorBody = e.response()?.errorBody()?.string()
            android.util.Log.e("AuthRepository", "Error body: $errorBody")
            
            // Try to extract error message and account status from response
            val errorMsg = try {
                if (errorBody != null) {
                    val gson = com.google.gson.Gson()
                    val errorResponse = gson.fromJson(errorBody, ApiResponse::class.java)
                    val message = errorResponse.message ?: when (e.code()) {
                        403 -> "Access denied. Please try again later or contact support."
                        409 -> "This email is already registered. Please sign in instead."
                        429 -> "Too many requests. Please wait a moment and try again."
                        else -> "Registration failed (${e.code()})"
                    }
                    
                    // Include account status in error message for 409 errors
                    if (e.code() == 409 && errorResponse.accountStatus != null) {
                        // Create a custom exception with account status info
                        val exception = RegistrationErrorException(
                            message,
                            errorResponse.accountStatus,
                            errorResponse.hasPassword ?: false
                        )
                        return Result.failure(exception)
                    }
                    
                    message
                } else {
                    when (e.code()) {
                        403 -> "Access denied. Please try again later or contact support."
                        409 -> "This email is already registered. Please sign in instead."
                        429 -> "Too many requests. Please wait a moment and try again."
                        else -> "Registration failed (${e.code()})"
                    }
                }
            } catch (parseError: Exception) {
                android.util.Log.e("AuthRepository", "Failed to parse error response", parseError)
                when (e.code()) {
                    403 -> "Access denied. Please try again later or contact support."
                    409 -> "This email is already registered. Please sign in instead."
                    429 -> "Too many requests. Please wait a moment and try again."
                    else -> "Registration failed: ${e.message ?: "HTTP ${e.code()}"}"
                }
            }
            
            Result.failure(Exception(errorMsg))
        } catch (e: java.net.UnknownHostException) {
            android.util.Log.e("AuthRepository", "Network error: Cannot reach server", e)
            Result.failure(Exception("Cannot connect to server. Please check your internet connection."))
        } catch (e: java.net.SocketTimeoutException) {
            android.util.Log.e("AuthRepository", "Network error: Request timeout", e)
            Result.failure(Exception("Request timed out. Please try again."))
        } catch (e: Exception) {
            android.util.Log.e("AuthRepository", "Registration exception", e)
            Result.failure(Exception("Registration failed: ${e.message ?: "Unknown error"}"))
        }
    }
    
    override suspend fun login(email: String, forceEmailCode: Boolean): Result<LoginResponse> {
        return try {
            val trimmedEmail = email.trim()
            android.util.Log.d("AuthRepository", "Attempting login for email: '$trimmedEmail' (original: '$email'), forceEmailCode: $forceEmailCode")
            val response = authApi.login(LoginRequest(trimmedEmail, forceEmailCode))
            android.util.Log.d("AuthRepository", "Login response - success: ${response.isSuccess}, message: ${response.message}")
            
            if (response.isSuccess && response.data != null) {
                Result.success(response.data)
            } else {
                val errorMsg = response.message ?: "Login failed"
                android.util.Log.e("AuthRepository", "Login failed: $errorMsg")
                Result.failure(Exception(errorMsg))
            }
        } catch (e: java.net.UnknownHostException) {
            android.util.Log.e("AuthRepository", "Network error: Cannot reach server", e)
            Result.failure(Exception("Cannot connect to server. Please check your internet connection."))
        } catch (e: java.net.SocketTimeoutException) {
            android.util.Log.e("AuthRepository", "Network error: Request timeout", e)
            Result.failure(Exception("Request timed out. Please try again."))
        } catch (e: HttpException) {
            android.util.Log.e("AuthRepository", "HTTP error during login: ${e.code()}", e)
            // Try to parse error response body
            val errorBody = e.response()?.errorBody()?.string()
            android.util.Log.e("AuthRepository", "Error body: $errorBody")
            
            // Try to extract error message from response
            val errorMsg = try {
                if (errorBody != null) {
                    val gson = com.google.gson.Gson()
                    val errorResponse = gson.fromJson(errorBody, ApiResponse::class.java)
                    errorResponse.message ?: when (e.code()) {
                        403 -> "Account is not active. Please complete registration or contact support."
                        404 -> "User not found. Please check your email address."
                        429 -> "Too many login attempts. Please wait a moment and try again."
                        else -> "Login failed (${e.code()})"
                    }
                } else {
                    when (e.code()) {
                        403 -> "Account is not active. Please complete registration or contact support."
                        404 -> "User not found. Please check your email address."
                        429 -> "Too many login attempts. Please wait a moment and try again."
                        else -> "Login failed (${e.code()})"
                    }
                }
            } catch (parseError: Exception) {
                android.util.Log.e("AuthRepository", "Failed to parse error response", parseError)
                when (e.code()) {
                    403 -> "Account is not active. Please complete registration or contact support."
                    404 -> "User not found. Please check your email address."
                    429 -> "Too many login attempts. Please wait a moment and try again."
                    else -> "Login failed: ${e.message ?: "HTTP ${e.code()}"}"
                }
            }
            
            Result.failure(Exception(errorMsg))
        } catch (e: Exception) {
            android.util.Log.e("AuthRepository", "Unexpected error during login", e)
            Result.failure(Exception("Login failed: ${e.message ?: "Unknown error"}"))
        }
    }
    
    override suspend fun resendVerification(email: String): Result<RegisterResponse> {
        return try {
            val trimmedEmail = email.trim()
            android.util.Log.d("AuthRepository", "Resending verification code for email: '$trimmedEmail' (original: '$email')")
            val request = RegisterRequest(trimmedEmail)
            android.util.Log.d("AuthRepository", "Resend verification request - email: '${request.email}'")
            
            val response = authApi.resendVerification(request)
            android.util.Log.d("AuthRepository", "Resend verification response - success: ${response.isSuccess}, message: ${response.message}, data: ${response.data != null}")
            
            if (response.isSuccess) {
                if (response.data != null) {
                    android.util.Log.d("AuthRepository", "Resend verification successful - code should be sent to email")
                    Result.success(response.data)
                } else {
                    // API returned success but no data - this shouldn't happen, but handle it
                    android.util.Log.w("AuthRepository", "Resend verification returned success but no data")
                    Result.success(RegisterResponse(userId = "", email = email, message = "Verification code sent"))
                }
            } else {
                val errorMsg = response.message ?: "Failed to resend verification code"
                android.util.Log.e("AuthRepository", "Resend verification failed: $errorMsg")
                Result.failure(Exception(errorMsg))
            }
        } catch (e: HttpException) {
            android.util.Log.e("AuthRepository", "HTTP error during resend verification: ${e.code()}", e)
            val errorBody = e.response()?.errorBody()?.string()
            android.util.Log.e("AuthRepository", "Error body: $errorBody")
            
            val errorMsg = try {
                if (errorBody != null) {
                    val gson = com.google.gson.Gson()
                    val errorResponse = gson.fromJson(errorBody, ApiResponse::class.java)
                    errorResponse.message ?: when (e.code()) {
                        404 -> "User not found. Please check your email address."
                        400 -> "Account is already active. Please login instead."
                        429 -> "Too many requests. Please wait a moment and try again."
                        else -> "Failed to resend verification code (${e.code()})"
                    }
                } else {
                    when (e.code()) {
                        404 -> "User not found. Please check your email address."
                        400 -> "Account is already active. Please login instead."
                        429 -> "Too many requests. Please wait a moment and try again."
                        else -> "Failed to resend verification code (${e.code()})"
                    }
                }
            } catch (parseError: Exception) {
                android.util.Log.e("AuthRepository", "Failed to parse error response", parseError)
                when (e.code()) {
                    404 -> "User not found. Please check your email address."
                    400 -> "Account is already active. Please login instead."
                    429 -> "Too many requests. Please wait a moment and try again."
                    else -> "Failed to resend verification code: ${e.message ?: "HTTP ${e.code()}"}"
                }
            }
            
            Result.failure(Exception(errorMsg))
        } catch (e: java.net.UnknownHostException) {
            android.util.Log.e("AuthRepository", "Network error: Cannot reach server", e)
            Result.failure(Exception("Cannot connect to server. Please check your internet connection."))
        } catch (e: java.net.SocketTimeoutException) {
            android.util.Log.e("AuthRepository", "Network error: Request timeout", e)
            Result.failure(Exception("Request timed out. Please try again."))
        } catch (e: Exception) {
            android.util.Log.e("AuthRepository", "Resend verification exception", e)
            Result.failure(Exception("Failed to resend verification code: ${e.message ?: "Unknown error"}"))
        }
    }
    
    override suspend fun verify(
        email: String,
        code: String?,
        password: String?
    ): Result<VerifyResponse> {
        return try {
            val trimmedEmail = email.trim()
            android.util.Log.d("AuthRepository", "Verifying - email: '$trimmedEmail', code: ${code?.take(2)}**, hasPassword: ${password != null}")
            val request = VerifyRequest(trimmedEmail, code?.trim(), password)
            android.util.Log.d("AuthRepository", "VerifyRequest - email: ${request.email}, code length: ${request.code?.length}, hasPassword: ${request.password != null}")
            
            val response = authApi.verify(request)
            android.util.Log.d("AuthRepository", "Verify response - success: ${response.isSuccess}, message: ${response.message}")
            
            if (response.isSuccess && response.data != null) {
                val verifyResponse = response.data
                
                // Clear any existing user data before saving new token
                // This prevents data from a previous user from being synced to the new account
                clearUserData()
                
                // Save token and email
                tokenManager.saveToken(verifyResponse.token)
                tokenManager.saveEmail(verifyResponse.email)
                android.util.Log.d("AuthRepository", "Verification successful - token saved, user data cleared")
                Result.success(verifyResponse)
            } else {
                val errorMsg = response.message ?: "Verification failed"
                android.util.Log.e("AuthRepository", "Verification failed: $errorMsg")
                Result.failure(Exception(errorMsg))
            }
        } catch (e: HttpException) {
            android.util.Log.e("AuthRepository", "HTTP error during verification: ${e.code()}", e)
            // Try to parse error response body
            val errorBody = e.response()?.errorBody()?.string()
            android.util.Log.e("AuthRepository", "Error body: $errorBody")
            
            // Try to extract error message from response
            val errorMsg = try {
                if (errorBody != null) {
                    val gson = com.google.gson.Gson()
                    val errorResponse = gson.fromJson(errorBody, ApiResponse::class.java)
                    errorResponse.message ?: when (e.code()) {
                        403 -> "Access denied. Please complete registration or contact support."
                        404 -> "User not found. Please check your email address."
                        429 -> "Too many verification attempts. Please wait a moment and try again."
                        else -> "Verification failed (${e.code()})"
                    }
                } else {
                    when (e.code()) {
                        403 -> "Access denied. Please complete registration or contact support."
                        404 -> "User not found. Please check your email address."
                        429 -> "Too many verification attempts. Please wait a moment and try again."
                        else -> "Verification failed (${e.code()})"
                    }
                }
            } catch (parseError: Exception) {
                android.util.Log.e("AuthRepository", "Failed to parse error response", parseError)
                when (e.code()) {
                    403 -> "Access denied. Please complete registration or contact support."
                    404 -> "User not found. Please check your email address."
                    429 -> "Too many verification attempts. Please wait a moment and try again."
                    else -> "Verification failed: ${e.message ?: "HTTP ${e.code()}"}"
                }
            }
            
            Result.failure(Exception(errorMsg))
        } catch (e: java.net.UnknownHostException) {
            android.util.Log.e("AuthRepository", "Network error: Cannot reach server", e)
            Result.failure(Exception("Cannot connect to server. Please check your internet connection."))
        } catch (e: java.net.SocketTimeoutException) {
            android.util.Log.e("AuthRepository", "Network error: Request timeout", e)
            Result.failure(Exception("Request timed out. Please try again."))
        } catch (e: Exception) {
            android.util.Log.e("AuthRepository", "Verification exception", e)
            Result.failure(Exception("Verification failed: ${e.message ?: "Unknown error"}"))
        }
    }
    
    override suspend fun loginDemo(): Result<VerifyResponse> {
        return try {
            val demoEmail = "demo@sharemycard.app"
            android.util.Log.d("AuthRepository", "Demo login - calling login API with: $demoEmail")
            
            // First, call login with demo email
            val loginResponse = authApi.login(LoginRequest(demoEmail))
            android.util.Log.d("AuthRepository", "Demo login response - success: ${loginResponse.isSuccess}, isDemo: ${loginResponse.data?.isDemo}")
            
            if (!loginResponse.isSuccess || loginResponse.data == null) {
                val errorMsg = loginResponse.message ?: "Demo login failed"
                android.util.Log.e("AuthRepository", "Demo login failed: $errorMsg")
                return Result.failure(Exception(errorMsg))
            }
            
            val loginData = loginResponse.data
            
            // Check if this is a demo account
            if (loginData.isDemo == true) {
                android.util.Log.d("AuthRepository", "Demo account detected - calling verify without code/password")
                // Call verify with just the email (no code or password needed for demo)
                val verifyResponse = authApi.verify(VerifyRequest(demoEmail, null, null))
                android.util.Log.d("AuthRepository", "Demo verify response - success: ${verifyResponse.isSuccess}")
                
                if (verifyResponse.isSuccess && verifyResponse.data != null) {
                    val verifyData = verifyResponse.data
                    
                    // Clear any existing user data before saving new token
                    // This prevents data from a previous user from being synced to the demo account
                    clearUserData()
                    
                    // Save token and email
                    // For demo accounts, always use the known demo email instead of relying on API response
                    tokenManager.saveToken(verifyData.token)
                    tokenManager.saveEmail(demoEmail)
                    android.util.Log.d("AuthRepository", "Demo login successful - token saved, email: $demoEmail, user data cleared")
                    Result.success(verifyData)
                } else {
                    val errorMsg = verifyResponse.message ?: "Demo verification failed"
                    android.util.Log.e("AuthRepository", "Demo verification failed: $errorMsg")
                    Result.failure(Exception(errorMsg))
                }
            } else {
                android.util.Log.e("AuthRepository", "Email is not recognized as demo account")
                Result.failure(Exception("Demo login failed - user not recognized as demo"))
            }
        } catch (e: Exception) {
            android.util.Log.e("AuthRepository", "Demo login exception", e)
            Result.failure(Exception("Demo login failed: ${e.message ?: "Unknown error"}"))
        }
    }
    
    override suspend fun setPassword(email: String, password: String): Result<Unit> {
        return try {
            android.util.Log.d("AuthRepository", "═══════════════════════════════════════════════════════")
            android.util.Log.d("AuthRepository", "🔐 SET PASSWORD REQUEST")
            android.util.Log.d("AuthRepository", "   Email: $email")
            android.util.Log.d("AuthRepository", "   Password length: ${password.length}")
            android.util.Log.d("AuthRepository", "   Request body: {email: $email, password: ***}")
            
            val request = PasswordSetRequest(email, password)
            android.util.Log.d("AuthRepository", "📤 Sending set password request...")
            
            val response = authApi.setPassword(request)
            
            android.util.Log.d("AuthRepository", "📥 Set password response received")
            android.util.Log.d("AuthRepository", "   Success: ${response.isSuccess}")
            android.util.Log.d("AuthRepository", "   Message: ${response.message}")
            
            if (response.isSuccess) {
                android.util.Log.d("AuthRepository", "✅ Password set successfully")
                Result.success(Unit)
            } else {
                val errorMsg = response.message ?: "Failed to set password"
                android.util.Log.e("AuthRepository", "❌ Set password failed: $errorMsg")
                Result.failure(Exception(errorMsg))
            }
        } catch (e: retrofit2.HttpException) {
            val errorBody = e.response()?.errorBody()?.string()
            android.util.Log.e("AuthRepository", "❌ HTTP Error setting password: ${e.code()} - ${e.message()}")
            android.util.Log.e("AuthRepository", "❌ Error body: $errorBody")
            android.util.Log.e("AuthRepository", "❌ Response headers: ${e.response()?.headers()}")
            Result.failure(Exception("Server error (${e.code()}): ${errorBody ?: e.message()}"))
        } catch (e: java.io.IOException) {
            android.util.Log.e("AuthRepository", "❌ Network error setting password: ${e.message}", e)
            Result.failure(Exception("Network error: ${e.message}"))
        } catch (e: Exception) {
            android.util.Log.e("AuthRepository", "❌ Unexpected error setting password: ${e.message}", e)
            e.printStackTrace()
            Result.failure(e)
        }
    }
    
    override suspend fun changePassword(
        email: String,
        currentPassword: String,
        newPassword: String
    ): Result<Unit> {
        return try {
            android.util.Log.d("AuthRepository", "═══════════════════════════════════════════════════════")
            android.util.Log.d("AuthRepository", "🔐 CHANGE PASSWORD REQUEST")
            android.util.Log.d("AuthRepository", "   Email: $email")
            android.util.Log.d("AuthRepository", "   Current password length: ${currentPassword.length}")
            android.util.Log.d("AuthRepository", "   New password length: ${newPassword.length}")
            
            val request = PasswordChangeRequest(email, currentPassword, newPassword)
            android.util.Log.d("AuthRepository", "📤 Sending change password request...")
            
            val response = authApi.changePassword(request)
            
            android.util.Log.d("AuthRepository", "📥 Change password response received")
            android.util.Log.d("AuthRepository", "   Success: ${response.isSuccess}")
            android.util.Log.d("AuthRepository", "   Message: ${response.message}")
            
            if (response.isSuccess) {
                android.util.Log.d("AuthRepository", "✅ Password changed successfully")
                Result.success(Unit)
            } else {
                val errorMsg = response.message ?: "Failed to change password"
                android.util.Log.e("AuthRepository", "❌ Change password failed: $errorMsg")
                Result.failure(Exception(errorMsg))
            }
        } catch (e: retrofit2.HttpException) {
            val errorBody = e.response()?.errorBody()?.string()
            android.util.Log.e("AuthRepository", "❌ HTTP Error changing password: ${e.code()} - ${e.message()}")
            android.util.Log.e("AuthRepository", "❌ Error body: $errorBody")
            android.util.Log.e("AuthRepository", "❌ Response headers: ${e.response()?.headers()}")
            Result.failure(Exception("Server error (${e.code()}): ${errorBody ?: e.message()}"))
        } catch (e: java.io.IOException) {
            android.util.Log.e("AuthRepository", "❌ Network error changing password: ${e.message}", e)
            Result.failure(Exception("Network error: ${e.message}"))
        } catch (e: Exception) {
            android.util.Log.e("AuthRepository", "❌ Unexpected error changing password: ${e.message}", e)
            e.printStackTrace()
            Result.failure(e)
        }
    }
    
    override suspend fun resetPassword(email: String): Result<Unit> {
        return try {
            val response = authApi.resetPasswordRequest(PasswordResetRequest(email))
            if (response.isSuccess) {
                Result.success(Unit)
            } else {
                Result.failure(Exception(response.message ?: "Failed to reset password"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    override suspend fun resetPasswordComplete(email: String, code: String, newPassword: String): Result<Unit> {
        return try {
            val response = authApi.resetPasswordComplete(
                PasswordResetCompleteRequest(email, code, newPassword)
            )
            if (response.isSuccess) {
                Result.success(Unit)
            } else {
                Result.failure(Exception(response.message ?: "Failed to complete password reset"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    override suspend fun checkPasswordStatus(email: String): Result<Boolean> {
        return try {
            val response = authApi.checkPasswordStatus(email)
            if (response.isSuccess && response.data != null) {
                Result.success(response.data.hasPassword)
            } else {
                Result.failure(Exception(response.message ?: "Failed to check password status"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    override suspend fun clearUserData() {
        try {
            Log.d("AuthRepository", "🧹 Clearing all user data from local database...")
            businessCardRepository.deleteAllCards()
            contactRepository.deleteAllContacts()
            leadRepository.deleteAllLeads()
            Log.d("AuthRepository", "✅ All user data cleared successfully")
        } catch (e: Exception) {
            Log.e("AuthRepository", "❌ Error clearing user data: ${e.message}", e)
            // Don't throw - we still want to clear the token even if data clearing fails
        }
    }
    
    override suspend fun logout() {
        // Clear all user data before logging out
        clearUserData()
        
        // Clear authentication tokens
        tokenManager.deleteToken()
        tokenManager.deleteEmail()
        Log.d("AuthRepository", "✅ Logout complete - user data and tokens cleared")
    }
    
    override fun isAuthenticated(): Boolean {
        return tokenManager.isAuthenticated()
    }
    
    override fun getCurrentEmail(): String? {
        return tokenManager.getEmail()
    }
}

