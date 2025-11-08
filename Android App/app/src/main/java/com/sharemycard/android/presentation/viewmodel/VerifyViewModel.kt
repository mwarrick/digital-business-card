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
class VerifyViewModel @Inject constructor(
    private val authRepository: AuthRepository
) : ViewModel() {
    
    private val _uiState = MutableStateFlow(VerifyUiState())
    val uiState: StateFlow<VerifyUiState> = _uiState.asStateFlow()
    
    fun updateCode(code: String) {
        _uiState.update { it.copy(code = code, errorMessage = null, successMessage = null) }
    }
    
    fun updatePassword(password: String) {
        _uiState.update { it.copy(password = password, errorMessage = null, successMessage = null) }
    }
    
    fun verify(email: String, hasPassword: Boolean) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            
            // Trim and clean email to avoid validation issues
            val cleanedEmail = email.trim()
            android.util.Log.d("VerifyViewModel", "Verifying - cleaned email: '$cleanedEmail' (original length: ${email.length}, cleaned length: ${cleanedEmail.length})")
            
            // If user requested email code, use code instead of password
            val useCode = !hasPassword || _uiState.value.useEmailCode
            
            val result = if (useCode) {
                val code = _uiState.value.code.trim()
                if (code.isBlank()) {
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            errorMessage = "Please enter the verification code"
                        )
                    }
                    return@launch
                }
                // Validate code format (must be 6 digits)
                if (!code.matches(Regex("^\\d{6}$"))) {
                    android.util.Log.e("VerifyViewModel", "Invalid code format: '$code' (length: ${code.length})")
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            errorMessage = "Verification code must be 6 digits"
                        )
                    }
                    return@launch
                }
                android.util.Log.d("VerifyViewModel", "Verifying with code: $code for email: '$cleanedEmail'")
                authRepository.verify(cleanedEmail, code = code)
            } else {
                val password = _uiState.value.password
                if (password.isBlank()) {
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            errorMessage = "Please enter your password"
                        )
                    }
                    return@launch
                }
                android.util.Log.d("VerifyViewModel", "Verifying with password for email: '$cleanedEmail'")
                authRepository.verify(cleanedEmail, password = password)
            }
            
            result.fold(
                onSuccess = {
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            isVerified = true
                        )
                    }
                },
                onFailure = { error ->
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            errorMessage = error.message ?: "Verification failed"
                        )
                    }
                }
            )
        }
    }
    
    fun clearVerification() {
        _uiState.update { it.copy(isVerified = false) }
    }
    
    fun requestEmailCode(email: String) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null, password = "") }
            
            authRepository.login(email, forceEmailCode = true).fold(
                onSuccess = { response ->
                    android.util.Log.d("VerifyViewModel", "Email code requested successfully")
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            useEmailCode = true, // Switch to email code mode
                            successMessage = "Verification code sent to your email",
                            errorMessage = null
                        )
                    }
                },
                onFailure = { error ->
                    android.util.Log.e("VerifyViewModel", "Failed to request email code", error)
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            errorMessage = error.message ?: "Failed to send verification code"
                        )
                    }
                }
            )
        }
    }
}

data class VerifyUiState(
    val code: String = "",
    val password: String = "",
    val isLoading: Boolean = false,
    val errorMessage: String? = null,
    val successMessage: String? = null,
    val isVerified: Boolean = false,
    val useEmailCode: Boolean = false // Track if user chose to use email code instead of password
)

