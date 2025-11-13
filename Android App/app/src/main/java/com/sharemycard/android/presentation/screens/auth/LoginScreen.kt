package com.sharemycard.android.presentation.screens.auth

import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.compose.*
import com.sharemycard.android.presentation.viewmodel.LoginViewModel

@Composable
fun LoginScreen(
    initialEmail: String? = null,
    viewModel: LoginViewModel = hiltViewModel(),
    onLoginSuccess: (email: String, hasPassword: Boolean) -> Unit,
    onNavigateToRegister: () -> Unit
) {
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()
    
    // Pre-populate email if provided
    LaunchedEffect(initialEmail) {
        if (initialEmail != null && initialEmail.isNotBlank() && uiState.email.isBlank()) {
            android.util.Log.d("LoginScreen", "Pre-populating email from navigation: $initialEmail")
            viewModel.updateEmail(initialEmail)
        }
    }
    
    // Navigate immediately when demo login succeeds
    LaunchedEffect(uiState.shouldNavigateToHome) {
        if (uiState.shouldNavigateToHome) {
            android.util.Log.d("LoginScreen", "Demo login successful, navigating to home")
            viewModel.clearNavigation()
            // For demo login, we navigate directly to home (bypassing verify screen)
            // We'll use a special callback or navigate directly
            // For now, we'll use onLoginSuccess with demo email and no password
            onLoginSuccess("demo@sharemycard.app", false)
        }
    }
    
    // Navigate to password screen when login check succeeds
    LaunchedEffect(uiState.shouldNavigateToPassword) {
        if (uiState.shouldNavigateToPassword) {
            val email = uiState.email.trim()
            val loginResponse = uiState.loginResponse
            val hasPassword = loginResponse?.hasPassword ?: false
            
            if (email.isNotBlank()) {
                android.util.Log.d("LoginScreen", "Navigating to password screen for email: $email, hasPassword: $hasPassword")
                // Clear the flag first
                viewModel.clearNavigation()
                // Navigate to password screen
                onLoginSuccess(email, hasPassword)
            } else {
                android.util.Log.e("LoginScreen", "Cannot navigate: email is blank")
                viewModel.clearNavigation()
            }
        }
    }
    
    // Navigate immediately when login succeeds - no state watching needed (for demo/login flows that bypass password)
    LaunchedEffect(uiState.shouldNavigateToVerify) {
        if (uiState.shouldNavigateToVerify) {
            val email = uiState.email.trim()
            val loginResponse = uiState.loginResponse
            val hasPassword = loginResponse?.hasPassword ?: false
            
            if (email.isNotBlank()) {
                android.util.Log.d("LoginScreen", "Immediately navigating to verify screen for email: $email, hasPassword: $hasPassword")
                // Clear the flag first
                viewModel.clearNavigation()
                // Navigate immediately - this happens synchronously in the coroutine
                onLoginSuccess(email, hasPassword)
            } else {
                android.util.Log.e("LoginScreen", "Cannot navigate: email is blank")
                viewModel.clearNavigation()
            }
        }
    }
    
    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(24.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Text(
            text = "ShareMyCard",
            style = MaterialTheme.typography.headlineLarge,
            fontWeight = FontWeight.Bold,
            modifier = Modifier.padding(bottom = 8.dp)
        )
        
        Text(
            text = "Sign in to your account",
            style = MaterialTheme.typography.bodyLarge,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
            modifier = Modifier.padding(bottom = 32.dp)
        )
        
        OutlinedTextField(
            value = uiState.email,
            onValueChange = viewModel::updateEmail,
            label = { Text("Email") },
            keyboardOptions = androidx.compose.foundation.text.KeyboardOptions(
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
                try {
                    android.util.Log.d("LoginScreen", "Login button clicked")
                    viewModel.login()
                } catch (e: Exception) {
                    android.util.Log.e("LoginScreen", "Error in login button onClick", e)
                    // Don't crash - error will be shown in UI state
                }
            },
            enabled = !uiState.isLoading && uiState.email.isNotBlank(),
            modifier = Modifier
                .fillMaxWidth()
                .height(56.dp)
        ) {
            if (uiState.isLoading) {
                // Simplified CircularProgressIndicator to avoid version compatibility issues
                CircularProgressIndicator(
                    modifier = Modifier.size(20.dp)
                )
            } else {
                Text("Login")
            }
        }
        
        Spacer(modifier = Modifier.height(16.dp))
        
        TextButton(onClick = onNavigateToRegister) {
            Text("Don't have an account? Create one")
        }
        
        Spacer(modifier = Modifier.height(24.dp))
        
        // Demo Login Button
        OutlinedButton(
            onClick = {
                try {
                    android.util.Log.d("LoginScreen", "Demo login button clicked")
                    viewModel.loginDemo()
                } catch (e: Exception) {
                    android.util.Log.e("LoginScreen", "Error in demo login button onClick", e)
                }
            },
            enabled = !uiState.isLoading,
            modifier = Modifier.fillMaxWidth()
        ) {
            Text("Try Demo Account")
        }
        
        uiState.errorMessage?.let { error ->
            Column(
                modifier = Modifier.padding(top = 16.dp),
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                Text(
                    text = error,
                    color = MaterialTheme.colorScheme.error,
                    style = MaterialTheme.typography.bodySmall
                )
                // If it's an inactive account error, show helpful actions
                if (error.contains("not active", ignoreCase = true) || 
                    error.contains("complete registration", ignoreCase = true)) {
                    Spacer(modifier = Modifier.height(8.dp))
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        colors = CardDefaults.cardColors(
                            containerColor = MaterialTheme.colorScheme.surfaceVariant
                        )
                    ) {
                        Column(
                            modifier = Modifier.padding(16.dp),
                            horizontalAlignment = Alignment.CenterHorizontally
                        ) {
                            Text(
                                text = "Your account exists but needs verification.",
                                style = MaterialTheme.typography.bodyMedium,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                                modifier = Modifier.padding(bottom = 8.dp)
                            )
                            Text(
                                text = "Please check your email for the verification code sent during registration.",
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                                modifier = Modifier.padding(bottom = 8.dp)
                            )
                            Text(
                                text = "Click below to resend a new verification code.",
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                                modifier = Modifier.padding(bottom = 12.dp)
                            )
                            Button(
                                onClick = { viewModel.resendVerification() },
                                enabled = !uiState.isLoading,
                                modifier = Modifier.fillMaxWidth()
                            ) {
                                if (uiState.isLoading) {
                                    CircularProgressIndicator(
                                        modifier = Modifier.size(20.dp),
                                        color = MaterialTheme.colorScheme.onPrimary
                                    )
                                } else {
                                    Text("Resend Verification Code")
                                }
                            }
                        }
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
        }
    }
}

