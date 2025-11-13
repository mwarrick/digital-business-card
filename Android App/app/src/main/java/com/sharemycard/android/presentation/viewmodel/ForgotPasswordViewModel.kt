package com.sharemycard.android.presentation.viewmodel

import android.util.Log
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
class ForgotPasswordViewModel @Inject constructor(
    private val authRepository: AuthRepository
) : ViewModel() {
    
    private val _uiState = MutableStateFlow(ForgotPasswordUiState())
    val uiState: StateFlow<ForgotPasswordUiState> = _uiState.asStateFlow()
    
    enum class ResetStep {
        EMAIL,
        CODE,
        NEW_PASSWORD
    }
    
    fun updateEmail(email: String) {
        _uiState.update { it.copy(email = email.trim(), errorMessage = null) }
    }
    
    fun updateCode(code: String) {
        // Only allow digits and limit to 6 characters
        val filtered = code.filter { it.isDigit() }.take(6)
        _uiState.update { it.copy(code = filtered, errorMessage = null) }
    }
    
    fun updateNewPassword(password: String) {
        _uiState.update { it.copy(newPassword = password, errorMessage = null) }
    }
    
    fun updateConfirmPassword(password: String) {
        _uiState.update { it.copy(confirmPassword = password, errorMessage = null) }
    }
    
    fun requestResetCode() {
        val email = _uiState.value.email
        
        if (email.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Email is required") }
            return
        }
        
        if (!android.util.Patterns.EMAIL_ADDRESS.matcher(email).matches()) {
            _uiState.update { it.copy(errorMessage = "Invalid email format") }
            return
        }
        
        Log.d("ForgotPasswordViewModel", "Requesting password reset code for email: $email")
        viewModelScope.launch {
            try {
                _uiState.update { it.copy(isLoading = true, errorMessage = null) }
                
                authRepository.resetPassword(email).fold(
                    onSuccess = {
                        Log.d("ForgotPasswordViewModel", "Reset code requested successfully")
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                step = ResetStep.CODE,
                                successMessage = "Reset code sent to your email"
                            )
                        }
                    },
                    onFailure = { error ->
                        Log.e("ForgotPasswordViewModel", "Failed to request reset code: ${error.message}", error)
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                errorMessage = error.message ?: "Failed to send reset code"
                            )
                        }
                    }
                )
            } catch (e: Exception) {
                Log.e("ForgotPasswordViewModel", "Unexpected error during reset code request", e)
                _uiState.update {
                    it.copy(
                        isLoading = false,
                        errorMessage = "An unexpected error occurred: ${e.message ?: "Unknown error"}"
                    )
                }
            }
        }
    }
    
    fun verifyCode() {
        val email = _uiState.value.email
        val code = _uiState.value.code
        
        if (code.length != 6) {
            _uiState.update { it.copy(errorMessage = "Please enter the 6-digit code") }
            return
        }
        
        Log.d("ForgotPasswordViewModel", "Verifying reset code for email: $email")
        // For now, just proceed to password step
        // In a real implementation, you might verify the code first
        _uiState.update {
            it.copy(
                step = ResetStep.NEW_PASSWORD,
                errorMessage = null
            )
        }
    }
    
    fun completeReset() {
        val email = _uiState.value.email
        val code = _uiState.value.code
        val newPassword = _uiState.value.newPassword
        val confirmPassword = _uiState.value.confirmPassword
        
        if (newPassword.isBlank() || confirmPassword.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Please enter and confirm your new password") }
            return
        }
        
        if (newPassword.length < 6) {
            _uiState.update { it.copy(errorMessage = "Password must be at least 6 characters") }
            return
        }
        
        if (newPassword != confirmPassword) {
            _uiState.update { it.copy(errorMessage = "Passwords do not match") }
            return
        }
        
        Log.d("ForgotPasswordViewModel", "Completing password reset for email: $email")
        viewModelScope.launch {
            try {
                _uiState.update { it.copy(isLoading = true, errorMessage = null) }
                
                authRepository.resetPasswordComplete(email, code, newPassword).fold(
                    onSuccess = {
                        Log.d("ForgotPasswordViewModel", "Password reset completed successfully")
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                successMessage = "Password reset successfully!",
                                isResetComplete = true
                            )
                        }
                    },
                    onFailure = { error ->
                        Log.e("ForgotPasswordViewModel", "Failed to complete password reset: ${error.message}", error)
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                errorMessage = error.message ?: "Failed to reset password"
                            )
                        }
                    }
                )
            } catch (e: Exception) {
                Log.e("ForgotPasswordViewModel", "Unexpected error during password reset", e)
                _uiState.update {
                    it.copy(
                        isLoading = false,
                        errorMessage = "An unexpected error occurred: ${e.message ?: "Unknown error"}"
                    )
                }
            }
        }
    }
    
    fun clearMessages() {
        _uiState.update {
            it.copy(
                errorMessage = null,
                successMessage = null
            )
        }
    }
    
    fun goBackToEmail() {
        _uiState.update {
            it.copy(
                step = ResetStep.EMAIL,
                code = "",
                errorMessage = null,
                successMessage = null
            )
        }
    }
}

data class ForgotPasswordUiState(
    val email: String = "",
    val code: String = "",
    val newPassword: String = "",
    val confirmPassword: String = "",
    val step: ForgotPasswordViewModel.ResetStep = ForgotPasswordViewModel.ResetStep.EMAIL,
    val isLoading: Boolean = false,
    val errorMessage: String? = null,
    val successMessage: String? = null,
    val isResetComplete: Boolean = false
)

