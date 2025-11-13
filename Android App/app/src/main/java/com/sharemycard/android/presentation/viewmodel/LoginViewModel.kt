package com.sharemycard.android.presentation.viewmodel

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.sharemycard.android.data.remote.models.auth.LoginResponse
import com.sharemycard.android.domain.repository.AuthRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class LoginViewModel @Inject constructor(
    private val authRepository: AuthRepository
) : ViewModel() {
    
    private val _uiState = MutableStateFlow(LoginUiState())
    val uiState: StateFlow<LoginUiState> = _uiState.asStateFlow()
    
    fun updateEmail(email: String) {
        _uiState.update { it.copy(email = email, errorMessage = null, successMessage = null) }
    }
    
    fun login() {
        android.util.Log.d("LoginViewModel", "login() function called")
        val email = _uiState.value.email.trim()
        android.util.Log.d("LoginViewModel", "Email from state: $email")
        if (email.isBlank()) {
            android.util.Log.d("LoginViewModel", "Email is blank, showing error")
            _uiState.update { it.copy(errorMessage = "Please enter your email") }
            return
        }
        
        android.util.Log.d("LoginViewModel", "Starting login coroutine for email: $email")
        viewModelScope.launch {
            try {
                _uiState.update { it.copy(isLoading = true, errorMessage = null, successMessage = null) }
                
                // Check if user has password - do NOT force email code
                // This will check the account and return whether user has password
                authRepository.login(email, forceEmailCode = false).fold(
                    onSuccess = { response ->
                        android.util.Log.d("LoginViewModel", "Login check successful, hasPassword: ${response.hasPassword}")
                        val hasPassword = response.hasPassword ?: false
                        
                        if (hasPassword) {
                            // User has password - navigate to password screen
                            android.util.Log.d("LoginViewModel", "User has password - navigating to password screen")
                            _uiState.update {
                                it.copy(
                                    isLoading = false,
                                    loginResponse = response,
                                    shouldNavigateToPassword = true
                                )
                            }
                        } else {
                            // User doesn't have password - navigate directly to verify screen
                            android.util.Log.d("LoginViewModel", "User doesn't have password - navigating directly to verify screen")
                            _uiState.update {
                                it.copy(
                                    isLoading = false,
                                    loginResponse = response,
                                    shouldNavigateToVerify = true
                                )
                            }
                        }
                    },
                    onFailure = { error ->
                        android.util.Log.e("LoginViewModel", "Login error: ${error.message}", error)
                        val errorMsg = error.message ?: "Login failed"
                        
                        // If account is not active, automatically resend verification code
                        if (errorMsg.contains("not active", ignoreCase = true) || 
                            errorMsg.contains("complete registration", ignoreCase = true)) {
                            android.util.Log.d("LoginViewModel", "Account not active - automatically resending verification code")
                            // Automatically resend verification code
                            authRepository.resendVerification(email).fold(
                                onSuccess = { resendResponse ->
                                    android.util.Log.d("LoginViewModel", "Verification code resent automatically")
                                    _uiState.update {
                                        it.copy(
                                            isLoading = false,
                                            errorMessage = null,
                                            successMessage = "Verification code sent to your email. Please check your inbox.",
                                            shouldNavigateToVerify = true
                                        )
                                    }
                                },
                                onFailure = { resendError ->
                                    android.util.Log.e("LoginViewModel", "Failed to resend verification: ${resendError.message}")
                                    _uiState.update {
                                        it.copy(
                                            isLoading = false,
                                            errorMessage = resendError.message ?: "Failed to send verification code. Please try again."
                                        )
                                    }
                                }
                            )
                        } else {
                            _uiState.update {
                                it.copy(
                                    isLoading = false,
                                    errorMessage = errorMsg
                                )
                            }
                        }
                    }
                )
            } catch (e: Exception) {
                android.util.Log.e("LoginViewModel", "Unexpected error during login", e)
                _uiState.update {
                    it.copy(
                        isLoading = false,
                        errorMessage = "An unexpected error occurred: ${e.message ?: "Unknown error"}"
                    )
                }
            }
        }
    }
    
    fun clearNavigation() {
        _uiState.update { 
            it.copy(
                shouldNavigateToPassword = false,
                shouldNavigateToVerify = false,
                shouldNavigateToHome = false
            ) 
        }
    }
    
    fun loginDemo() {
        android.util.Log.d("LoginViewModel", "loginDemo() function called")
        viewModelScope.launch {
            try {
                _uiState.update { it.copy(isLoading = true, errorMessage = null, successMessage = null) }
                
                authRepository.loginDemo().fold(
                    onSuccess = { verifyResponse ->
                        android.util.Log.d("LoginViewModel", "Demo login successful, navigating to home")
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                shouldNavigateToHome = true,
                                successMessage = "Demo account logged in"
                            )
                        }
                    },
                    onFailure = { error ->
                        android.util.Log.e("LoginViewModel", "Demo login error: ${error.message}", error)
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                errorMessage = error.message ?: "Demo login failed"
                            )
                        }
                    }
                )
            } catch (e: Exception) {
                android.util.Log.e("LoginViewModel", "Unexpected error during demo login", e)
                _uiState.update {
                    it.copy(
                        isLoading = false,
                        errorMessage = "An unexpected error occurred: ${e.message ?: "Unknown error"}"
                    )
                }
            }
        }
    }
    
    fun resendVerification() {
        val email = _uiState.value.email.trim()
        if (email.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Please enter your email") }
            return
        }
        
        viewModelScope.launch {
            try {
                _uiState.update { it.copy(isLoading = true, errorMessage = null) }
                
                authRepository.resendVerification(email).fold(
                    onSuccess = { response ->
                        android.util.Log.d("LoginViewModel", "Verification code resent successfully")
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                errorMessage = null,
                                successMessage = "Verification code sent to your email. Please check your inbox.",
                                shouldNavigateToVerify = true // Navigate to verify screen
                            )
                        }
                    },
                    onFailure = { error ->
                        android.util.Log.e("LoginViewModel", "Resend verification error: ${error.message}", error)
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                errorMessage = error.message ?: "Failed to resend verification code"
                            )
                        }
                    }
                )
            } catch (e: Exception) {
                android.util.Log.e("LoginViewModel", "Unexpected error during resend verification", e)
                _uiState.update {
                    it.copy(
                        isLoading = false,
                        errorMessage = "An unexpected error occurred: ${e.message ?: "Unknown error"}"
                    )
                }
            }
        }
    }
}

data class LoginUiState(
    val email: String = "",
    val isLoading: Boolean = false,
    val errorMessage: String? = null,
    val successMessage: String? = null,
    val loginResponse: LoginResponse? = null,
    val shouldNavigateToPassword: Boolean = false, // Navigate to password screen
    val shouldNavigateToVerify: Boolean = false,
    val shouldNavigateToHome: Boolean = false
)

