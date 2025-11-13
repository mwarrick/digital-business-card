package com.sharemycard.android.presentation.screens.auth

import android.util.Log
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.*
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.compose.*
import com.sharemycard.android.presentation.viewmodel.VerifyViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun VerifyScreen(
    email: String,
    hasPassword: Boolean,
    viewModel: VerifyViewModel = hiltViewModel(),
    onVerificationSuccess: () -> Unit,
    onForgotPassword: () -> Unit = {}
) {
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()
    
    // VerifyScreen is ONLY for code entry - password entry is handled in PasswordScreen
    // Note: If user doesn't have a password, the login API already sent a verification code,
    // so we should NOT request another one here to avoid duplicate emails
    LaunchedEffect(email, hasPassword) {
        Log.d("VerifyScreen", "VerifyScreen composed - email: $email, hasPassword: $hasPassword, useEmailCode: ${uiState.useEmailCode}")
        // Ensure we're in email code mode (this screen only handles codes)
        if (!uiState.useEmailCode) {
            Log.d("VerifyScreen", "Switching to email code mode")
            viewModel.setUseEmailCode(true)
        }
        
        // If user doesn't have a password, the login API already sent a verification code
        // So we should NOT request another one - just show success message
        if (!hasPassword && uiState.successMessage == null && !uiState.isLoading) {
            Log.d("VerifyScreen", "User doesn't have password - login API already sent verification code, not requesting another")
            // Set success message to indicate code was already sent
            viewModel.setCodeAlreadySent()
        }
    }
    
    LaunchedEffect(uiState.isVerified) {
        if (uiState.isVerified) {
            Log.d("VerifyScreen", "Verification successful, navigating to home")
            viewModel.clearVerification()
            onVerificationSuccess()
        }
    }
    
    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Verify") },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.surface
                )
            )
        }
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .verticalScroll(rememberScrollState())
                .padding(horizontal = 24.dp, vertical = 16.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            // Email display - reduced spacing
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(bottom = 16.dp),
                colors = CardDefaults.cardColors(
                    containerColor = MaterialTheme.colorScheme.surfaceVariant
                )
            ) {
                Column(
                    modifier = Modifier.padding(16.dp),
                    horizontalAlignment = Alignment.CenterHorizontally
                ) {
                    Text(
                        text = "Verifying",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold,
                        modifier = Modifier.padding(bottom = 4.dp)
                    )
                    Text(
                        text = email,
                        style = MaterialTheme.typography.bodyMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
            }
            
            Text(
                text = "Sign In",
                style = MaterialTheme.typography.titleMedium,
                modifier = Modifier.padding(bottom = 8.dp)
            )
            
            Text(
                text = "Enter the verification code sent to your email",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                modifier = Modifier.padding(bottom = 24.dp)
            )
            
            // Email code mode only - password entry is handled in PasswordScreen
            OutlinedTextField(
                value = uiState.code,
                onValueChange = { newValue ->
                    // Only allow digits and limit to 6 characters
                    val filtered = newValue.filter { it.isDigit() }.take(6)
                    viewModel.updateCode(filtered)
                },
                label = { Text("Verification Code") },
                keyboardOptions = androidx.compose.foundation.text.KeyboardOptions(
                    keyboardType = KeyboardType.Number
                ),
                singleLine = true,
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(bottom = 16.dp),
                enabled = !uiState.isLoading,
                placeholder = { Text("Enter 6-digit code") }
            )
            
            // Verify button for code
            Button(
                onClick = {
                    Log.d("VerifyScreen", "Verify button clicked with code")
                    viewModel.verify(email, hasPassword = false) // Always use code mode
                },
                enabled = !uiState.isLoading && uiState.code.isNotBlank(),
                modifier = Modifier
                    .fillMaxWidth()
                    .height(56.dp)
                    .padding(bottom = 8.dp)
            ) {
                if (uiState.isLoading) {
                    CircularProgressIndicator(
                        modifier = Modifier.size(20.dp)
                    )
                } else {
                    Text("Verify")
                }
            }
            
            // Show "Request Code" button if code hasn't been requested yet
            if (!uiState.useEmailCode || uiState.successMessage == null) {
                TextButton(
                    onClick = {
                        Log.d("VerifyScreen", "Request email code clicked")
                        viewModel.requestEmailCode(email)
                    },
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(bottom = 16.dp),
                    enabled = !uiState.isLoading
                ) {
                    Text("Request Verification Code")
                }
            }
            
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
        }
    }
}

