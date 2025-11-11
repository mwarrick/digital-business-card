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
import androidx.compose.ui.text.input.PasswordVisualTransformation
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
    
    // When screen loads, ensure we're in email code mode if coming from login
    // LoginScreen always sends code, so VerifyScreen should show code entry
    LaunchedEffect(email) {
        Log.d("VerifyScreen", "VerifyScreen composed - email: $email, hasPassword: $hasPassword, useEmailCode: ${uiState.useEmailCode}")
        // If not already in email code mode and user doesn't have password, switch to email code
        // This handles the case where user comes from login (which always sends code)
        if (!uiState.useEmailCode && !hasPassword) {
            Log.d("VerifyScreen", "Switching to email code mode (no password available)")
            viewModel.requestEmailCode(email)
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
                text = "Enter your password or request a verification code",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                modifier = Modifier.padding(bottom = 24.dp)
            )
            
            if (hasPassword && !uiState.useEmailCode) {
                // Password mode - show password field with Login button
                OutlinedTextField(
                    value = uiState.password,
                    onValueChange = viewModel::updatePassword,
                    label = { Text("Password") },
                    visualTransformation = PasswordVisualTransformation(),
                    keyboardOptions = androidx.compose.foundation.text.KeyboardOptions(
                        keyboardType = KeyboardType.Password
                    ),
                    singleLine = true,
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(bottom = 16.dp),
                    enabled = !uiState.isLoading
                )
                
                // Login button
                Button(
                    onClick = {
                        Log.d("VerifyScreen", "Login button clicked with password")
                        viewModel.verify(email, hasPassword)
                    },
                    enabled = !uiState.isLoading && uiState.password.isNotBlank(),
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
                        Text("Login")
                    }
                }
                
                // Request verification code option
                TextButton(
                    onClick = {
                        Log.d("VerifyScreen", "Request email code clicked")
                        viewModel.requestEmailCode(email)
                    },
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(bottom = 8.dp),
                    enabled = !uiState.isLoading
                ) {
                    Text("Request Verification Code")
                }
                
                // Forgot Password link
                TextButton(
                    onClick = {
                        Log.d("VerifyScreen", "Forgot password clicked")
                        onForgotPassword()
                    },
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(bottom = 16.dp),
                    enabled = !uiState.isLoading
                ) {
                    Text("Forgot Password?")
                }
            } else {
                // Email code mode (either no password or user chose email code)
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
                        viewModel.verify(email, hasPassword)
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
                if (!uiState.useEmailCode) {
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

