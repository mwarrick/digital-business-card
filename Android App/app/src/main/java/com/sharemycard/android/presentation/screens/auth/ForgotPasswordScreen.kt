package com.sharemycard.android.presentation.screens.auth

import android.util.Log
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.compose.*
import com.sharemycard.android.presentation.viewmodel.ForgotPasswordViewModel
import kotlinx.coroutines.delay

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ForgotPasswordScreen(
    viewModel: ForgotPasswordViewModel = hiltViewModel(),
    onResetComplete: () -> Unit,
    onNavigateBack: () -> Unit
) {
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()
    
    // Auto-dismiss success message and navigate back after reset complete
    LaunchedEffect(uiState.isResetComplete) {
        if (uiState.isResetComplete) {
            delay(1500)
            onResetComplete()
        }
    }
    
    // Auto-clear success message after 3 seconds
    LaunchedEffect(uiState.successMessage) {
        if (uiState.successMessage != null && !uiState.isResetComplete) {
            delay(3000)
            viewModel.clearMessages()
        }
    }
    
    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Reset Password") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(
                            imageVector = Icons.Default.ArrowBack,
                            contentDescription = "Back"
                        )
                    }
                }
            )
        }
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .verticalScroll(rememberScrollState())
                .padding(24.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            // Header
            Spacer(modifier = Modifier.height(16.dp))
            
            Text(
                text = when (uiState.step) {
                    ForgotPasswordViewModel.ResetStep.EMAIL -> "Reset Password"
                    ForgotPasswordViewModel.ResetStep.CODE -> "Check Your Email"
                    ForgotPasswordViewModel.ResetStep.NEW_PASSWORD -> "Create New Password"
                },
                style = MaterialTheme.typography.headlineMedium,
                fontWeight = FontWeight.Bold,
                modifier = Modifier.padding(bottom = 8.dp)
            )
            
            Text(
                text = when (uiState.step) {
                    ForgotPasswordViewModel.ResetStep.EMAIL -> "Enter your email address to receive a password reset code"
                    ForgotPasswordViewModel.ResetStep.CODE -> "A reset code has been sent to:"
                    ForgotPasswordViewModel.ResetStep.NEW_PASSWORD -> "Enter your new password"
                },
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                modifier = Modifier.padding(bottom = 24.dp)
            )
            
            Spacer(modifier = Modifier.height(16.dp))
            
            when (uiState.step) {
                ForgotPasswordViewModel.ResetStep.EMAIL -> {
                    emailStepView(uiState, viewModel)
                }
                ForgotPasswordViewModel.ResetStep.CODE -> {
                    codeStepView(uiState, viewModel)
                }
                ForgotPasswordViewModel.ResetStep.NEW_PASSWORD -> {
                    newPasswordStepView(uiState, viewModel)
                }
            }
            
            // Error message
            uiState.errorMessage?.let { error ->
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(top = 16.dp),
                    colors = CardDefaults.cardColors(
                        containerColor = MaterialTheme.colorScheme.errorContainer
                    )
                ) {
                    Text(
                        text = error,
                        color = MaterialTheme.colorScheme.onErrorContainer,
                        style = MaterialTheme.typography.bodySmall,
                        modifier = Modifier.padding(16.dp)
                    )
                }
            }
            
            // Success message
            uiState.successMessage?.let { success ->
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(top = 16.dp),
                    colors = CardDefaults.cardColors(
                        containerColor = MaterialTheme.colorScheme.primaryContainer
                    )
                ) {
                    Text(
                        text = success,
                        color = MaterialTheme.colorScheme.onPrimaryContainer,
                        style = MaterialTheme.typography.bodySmall,
                        modifier = Modifier.padding(16.dp)
                    )
                }
            }
        }
    }
}

@Composable
private fun emailStepView(
    uiState: com.sharemycard.android.presentation.viewmodel.ForgotPasswordUiState,
    viewModel: ForgotPasswordViewModel
) {
    Column(
        modifier = Modifier.fillMaxWidth(),
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        OutlinedTextField(
            value = uiState.email,
            onValueChange = viewModel::updateEmail,
            label = { Text("Email Address") },
            keyboardOptions = KeyboardOptions(
                keyboardType = KeyboardType.Email
            ),
            singleLine = true,
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 16.dp),
            enabled = !uiState.isLoading
        )
        
        Button(
            onClick = {
                Log.d("ForgotPasswordScreen", "Send reset code button clicked")
                viewModel.requestResetCode()
            },
            enabled = !uiState.isLoading && uiState.email.isNotBlank(),
            modifier = Modifier
                .fillMaxWidth()
                .height(56.dp)
        ) {
            if (uiState.isLoading) {
                CircularProgressIndicator(
                    modifier = Modifier.size(20.dp),
                    color = MaterialTheme.colorScheme.onPrimary
                )
            } else {
                Text("Send Reset Code")
            }
        }
    }
}

@Composable
private fun codeStepView(
    uiState: com.sharemycard.android.presentation.viewmodel.ForgotPasswordUiState,
    viewModel: ForgotPasswordViewModel
) {
    Column(
        modifier = Modifier.fillMaxWidth(),
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Text(
            text = uiState.email,
            style = MaterialTheme.typography.bodyLarge,
            fontWeight = FontWeight.SemiBold,
            modifier = Modifier.padding(bottom = 24.dp)
        )
        
        OutlinedTextField(
            value = uiState.code,
            onValueChange = viewModel::updateCode,
            label = { Text("6-Digit Code") },
            keyboardOptions = KeyboardOptions(
                keyboardType = KeyboardType.Number
            ),
            singleLine = true,
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 16.dp),
            enabled = !uiState.isLoading,
            placeholder = { Text("Enter 6-digit code") }
        )
        
        Button(
            onClick = {
                Log.d("ForgotPasswordScreen", "Verify code button clicked")
                viewModel.verifyCode()
            },
            enabled = !uiState.isLoading && uiState.code.length == 6,
            modifier = Modifier
                .fillMaxWidth()
                .height(56.dp)
                .padding(bottom = 8.dp)
        ) {
            if (uiState.isLoading) {
                CircularProgressIndicator(
                    modifier = Modifier.size(20.dp),
                    color = MaterialTheme.colorScheme.onPrimary
                )
            } else {
                Text("Verify Code")
            }
        }
        
        TextButton(
            onClick = {
                Log.d("ForgotPasswordScreen", "Use different email clicked")
                viewModel.goBackToEmail()
            },
            modifier = Modifier.fillMaxWidth()
        ) {
            Text("Use Different Email")
        }
    }
}

@Composable
private fun newPasswordStepView(
    uiState: com.sharemycard.android.presentation.viewmodel.ForgotPasswordUiState,
    viewModel: ForgotPasswordViewModel
) {
    Column(
        modifier = Modifier.fillMaxWidth(),
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        OutlinedTextField(
            value = uiState.newPassword,
            onValueChange = viewModel::updateNewPassword,
            label = { Text("New Password") },
            visualTransformation = PasswordVisualTransformation(),
            keyboardOptions = KeyboardOptions(
                keyboardType = KeyboardType.Password
            ),
            singleLine = true,
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 16.dp),
            enabled = !uiState.isLoading
        )
        
        OutlinedTextField(
            value = uiState.confirmPassword,
            onValueChange = viewModel::updateConfirmPassword,
            label = { Text("Confirm New Password") },
            visualTransformation = PasswordVisualTransformation(),
            keyboardOptions = KeyboardOptions(
                keyboardType = KeyboardType.Password
            ),
            singleLine = true,
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 24.dp),
            enabled = !uiState.isLoading
        )
        
        Button(
            onClick = {
                Log.d("ForgotPasswordScreen", "Reset password button clicked")
                viewModel.completeReset()
            },
            enabled = !uiState.isLoading &&
                     uiState.newPassword.isNotBlank() &&
                     uiState.confirmPassword.isNotBlank() &&
                     uiState.newPassword.length >= 6 &&
                     uiState.newPassword == uiState.confirmPassword,
            modifier = Modifier
                .fillMaxWidth()
                .height(56.dp)
        ) {
            if (uiState.isLoading) {
                CircularProgressIndicator(
                    modifier = Modifier.size(20.dp),
                    color = MaterialTheme.colorScheme.onPrimary
                )
            } else {
                Text("Reset Password")
            }
        }
    }
}

