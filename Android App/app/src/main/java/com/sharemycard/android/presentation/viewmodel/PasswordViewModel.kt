package com.sharemycard.android.presentation.viewmodel

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.sharemycard.android.domain.repository.AuthRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class PasswordViewModel @Inject constructor(
    private val authRepository: AuthRepository
) : ViewModel() {
    
    private val _uiState = MutableStateFlow(PasswordUiState())
    val uiState: StateFlow<PasswordUiState> = _uiState.asStateFlow()
    
    fun setEmail(email: String) {
        _uiState.update { it.copy(email = email.trim()) }
    }
    
    fun updatePassword(password: String) {
        _uiState.update { it.copy(password = password, errorMessage = null) }
    }
    
    fun loginWithPassword() {
        val email = _uiState.value.email
        val password = _uiState.value.password
        
        if (email.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Email is required") }
            return
        }
        
        if (password.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Please enter your password") }
            return
        }
        
        android.util.Log.d("PasswordViewModel", "Attempting password login for email: $email")
        viewModelScope.launch {
            try {
                _uiState.update { it.copy(isLoading = true, errorMessage = null) }
                
                // Verify with password (not code)
                authRepository.verify(email, password = password).fold(
                    onSuccess = { response ->
                        android.util.Log.d("PasswordViewModel", "Password login successful")
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                isLoggedIn = true,
                                successMessage = "Login successful"
                            )
                        }
                    },
                    onFailure = { error ->
                        android.util.Log.e("PasswordViewModel", "Password login failed: ${error.message}", error)
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                errorMessage = error.message ?: "Login failed. Please check your password."
                            )
                        }
                    }
                )
            } catch (e: Exception) {
                android.util.Log.e("PasswordViewModel", "Unexpected error during password login", e)
                _uiState.update {
                    it.copy(
                        isLoading = false,
                        errorMessage = "An unexpected error occurred: ${e.message ?: "Unknown error"}"
                    )
                }
            }
        }
    }
    
    fun clearLoginState() {
        _uiState.update { it.copy(isLoggedIn = false) }
    }
    
    fun requestVerificationCode() {
        val email = _uiState.value.email
        if (email.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Email is required") }
            return
        }
        
        android.util.Log.d("PasswordViewModel", "Requesting verification code for email: $email")
        viewModelScope.launch {
            try {
                _uiState.update { it.copy(isLoading = true, errorMessage = null) }
                
                // Request verification code by calling login with forceEmailCode = true
                authRepository.login(email, forceEmailCode = true).fold(
                    onSuccess = { response ->
                        android.util.Log.d("PasswordViewModel", "Verification code requested successfully")
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                successMessage = "Verification code sent to your email",
                                codeRequested = true // Flag to indicate code was requested
                            )
                        }
                    },
                    onFailure = { error ->
                        android.util.Log.e("PasswordViewModel", "Failed to request verification code: ${error.message}", error)
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                errorMessage = error.message ?: "Failed to send verification code"
                            )
                        }
                    }
                )
            } catch (e: Exception) {
                android.util.Log.e("PasswordViewModel", "Unexpected error during code request", e)
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

data class PasswordUiState(
    val email: String = "",
    val password: String = "",
    val isLoading: Boolean = false,
    val errorMessage: String? = null,
    val successMessage: String? = null,
    val isLoggedIn: Boolean = false,
    val codeRequested: Boolean = false // Flag to track if verification code was requested
)

