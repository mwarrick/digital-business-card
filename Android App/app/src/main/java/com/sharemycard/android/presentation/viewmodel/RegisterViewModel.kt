package com.sharemycard.android.presentation.viewmodel

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.sharemycard.android.data.remote.models.auth.RegisterResponse
import com.sharemycard.android.data.repository.RegistrationErrorException
import com.sharemycard.android.domain.repository.AuthRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class RegisterViewModel @Inject constructor(
    private val authRepository: AuthRepository
) : ViewModel() {
    
    private val _uiState = MutableStateFlow(RegisterUiState())
    val uiState: StateFlow<RegisterUiState> = _uiState.asStateFlow()
    
    fun updateEmail(email: String) {
        _uiState.update { it.copy(email = email, errorMessage = null, successMessage = null) }
    }
    
    fun register() {
        val email = _uiState.value.email.trim()
        if (email.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Please enter your email") }
            return
        }
        
        if (!android.util.Patterns.EMAIL_ADDRESS.matcher(email).matches()) {
            _uiState.update { it.copy(errorMessage = "Please enter a valid email address") }
            return
        }
        
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            
            authRepository.register(email).fold(
                onSuccess = { response ->
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            registerResponse = response,
                            shouldNavigateToVerify = true
                        )
                    }
                },
                onFailure = { error ->
                    // Check if it's a RegistrationErrorException with account status
                    if (error is RegistrationErrorException) {
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                errorMessage = error.message,
                                accountStatus = error.accountStatus,
                                hasPassword = error.hasPassword
                            )
                        }
                    } else {
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                errorMessage = error.message ?: "Registration failed",
                                accountStatus = null,
                                hasPassword = null
                            )
                        }
                    }
                }
            )
        }
    }
    
    fun clearNavigation() {
        _uiState.update { it.copy(shouldNavigateToVerify = false) }
    }
    
    fun resendVerification() {
        val email = _uiState.value.email.trim()
        if (email.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Please enter your email") }
            return
        }
        
        if (!android.util.Patterns.EMAIL_ADDRESS.matcher(email).matches()) {
            _uiState.update { it.copy(errorMessage = "Please enter a valid email address") }
            return
        }
        
        viewModelScope.launch {
            try {
                _uiState.update { it.copy(isLoading = true, errorMessage = null, successMessage = null) }
                
                authRepository.resendVerification(email).fold(
                    onSuccess = { response ->
                        android.util.Log.d("RegisterViewModel", "Verification code resent successfully")
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                errorMessage = null,
                                successMessage = "Verification code sent to your email. Please check your inbox.",
                                shouldNavigateToVerify = true
                            )
                        }
                    },
                    onFailure = { error ->
                        android.util.Log.e("RegisterViewModel", "Resend verification error: ${error.message}", error)
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                errorMessage = error.message ?: "Failed to resend verification code"
                            )
                        }
                    }
                )
            } catch (e: Exception) {
                android.util.Log.e("RegisterViewModel", "Unexpected error during resend verification", e)
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

data class RegisterUiState(
    val email: String = "",
    val isLoading: Boolean = false,
    val errorMessage: String? = null,
    val successMessage: String? = null,
    val registerResponse: RegisterResponse? = null,
    val shouldNavigateToVerify: Boolean = false,
    val accountStatus: String? = null, // "verified" or "unverified"
    val hasPassword: Boolean? = null
)

