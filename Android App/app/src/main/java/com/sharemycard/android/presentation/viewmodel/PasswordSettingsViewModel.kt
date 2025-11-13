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
class PasswordSettingsViewModel @Inject constructor(
    private val authRepository: AuthRepository
) : ViewModel() {
    
    private val _uiState = MutableStateFlow(PasswordSettingsUiState())
    val uiState: StateFlow<PasswordSettingsUiState> = _uiState.asStateFlow()
    
    init {
        checkPasswordStatus()
    }
    
    fun checkPasswordStatus() {
        val email = authRepository.getCurrentEmail()
        if (email == null) {
            _uiState.update { it.copy(errorMessage = "Not logged in") }
            return
        }
        
        viewModelScope.launch {
            _uiState.update { it.copy(checkingStatus = true) }
            
            authRepository.checkPasswordStatus(email).fold(
                onSuccess = { hasPassword ->
                    _uiState.update {
                        it.copy(
                            checkingStatus = false,
                            hasPassword = hasPassword
                        )
                    }
                },
                onFailure = { error ->
                    _uiState.update {
                        it.copy(
                            checkingStatus = false,
                            errorMessage = error.message ?: "Failed to check password status"
                        )
                    }
                }
            )
        }
    }
    
    fun updateCurrentPassword(password: String) {
        _uiState.update { it.copy(currentPassword = password, errorMessage = null) }
    }
    
    fun updateNewPassword(password: String) {
        _uiState.update { it.copy(newPassword = password, errorMessage = null) }
    }
    
    fun updateConfirmPassword(password: String) {
        _uiState.update { it.copy(confirmPassword = password, errorMessage = null) }
    }
    
    fun setPassword() {
        val email = authRepository.getCurrentEmail()
        if (email == null) {
            _uiState.update { it.copy(errorMessage = "Not logged in") }
            return
        }
        
        val newPassword = _uiState.value.newPassword
        val confirmPassword = _uiState.value.confirmPassword
        
        if (newPassword.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Please enter a password") }
            return
        }
        
        if (newPassword != confirmPassword) {
            _uiState.update { it.copy(errorMessage = "Passwords do not match") }
            return
        }
        
        viewModelScope.launch {
            Log.d("PasswordSettingsViewModel", "═══════════════════════════════════════════════════════")
            Log.d("PasswordSettingsViewModel", "🔐 SET PASSWORD CALLED")
            Log.d("PasswordSettingsViewModel", "   Email: $email")
            Log.d("PasswordSettingsViewModel", "   Password length: ${newPassword.length}")
            
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            
            authRepository.setPassword(email, newPassword).fold(
                onSuccess = {
                    Log.d("PasswordSettingsViewModel", "✅ Password set successfully")
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            hasPassword = true,
                            successMessage = "Password set successfully",
                            newPassword = "",
                            confirmPassword = ""
                        )
                    }
                },
                onFailure = { error ->
                    Log.e("PasswordSettingsViewModel", "❌ Failed to set password: ${error.message}", error)
                    Log.e("PasswordSettingsViewModel", "   Error type: ${error.javaClass.simpleName}")
                    error.printStackTrace()
                    
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            errorMessage = error.message ?: "Failed to set password"
                        )
                    }
                }
            )
        }
    }
    
    fun changePassword() {
        val email = authRepository.getCurrentEmail()
        if (email == null) {
            _uiState.update { it.copy(errorMessage = "Not logged in") }
            return
        }
        
        val currentPassword = _uiState.value.currentPassword
        val newPassword = _uiState.value.newPassword
        val confirmPassword = _uiState.value.confirmPassword
        
        if (currentPassword.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Please enter your current password") }
            return
        }
        
        if (newPassword.isBlank()) {
            _uiState.update { it.copy(errorMessage = "Please enter a new password") }
            return
        }
        
        if (newPassword != confirmPassword) {
            _uiState.update { it.copy(errorMessage = "Passwords do not match") }
            return
        }
        
        viewModelScope.launch {
            Log.d("PasswordSettingsViewModel", "═══════════════════════════════════════════════════════")
            Log.d("PasswordSettingsViewModel", "🔐 CHANGE PASSWORD CALLED")
            Log.d("PasswordSettingsViewModel", "   Email: $email")
            Log.d("PasswordSettingsViewModel", "   Current password length: ${currentPassword.length}")
            Log.d("PasswordSettingsViewModel", "   New password length: ${newPassword.length}")
            
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            
            authRepository.changePassword(email, currentPassword, newPassword).fold(
                onSuccess = {
                    Log.d("PasswordSettingsViewModel", "✅ Password changed successfully")
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            successMessage = "Password changed successfully",
                            currentPassword = "",
                            newPassword = "",
                            confirmPassword = ""
                        )
                    }
                },
                onFailure = { error ->
                    Log.e("PasswordSettingsViewModel", "❌ Failed to change password: ${error.message}", error)
                    Log.e("PasswordSettingsViewModel", "   Error type: ${error.javaClass.simpleName}")
                    error.printStackTrace()
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            errorMessage = error.message ?: "Failed to change password"
                        )
                    }
                }
            )
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
}

data class PasswordSettingsUiState(
    val checkingStatus: Boolean = false,
    val hasPassword: Boolean = false,
    val currentPassword: String = "",
    val newPassword: String = "",
    val confirmPassword: String = "",
    val isLoading: Boolean = false,
    val errorMessage: String? = null,
    val successMessage: String? = null
)

