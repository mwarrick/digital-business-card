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
    
    LaunchedEffect(uiState.shouldNavigateToVerify) {
        if (uiState.shouldNavigateToVerify) {
            try {
                val loginResponse = uiState.loginResponse
                val email = uiState.email
                if (email.isNotBlank()) {
                    viewModel.clearNavigation()
                    android.util.Log.d("LoginScreen", "Navigating to verify screen for email: $email")
                    // If loginResponse exists, use its hasPassword, otherwise false (for resend verification)
                    val hasPassword = loginResponse?.hasPassword ?: false
                    onLoginSuccess(email, hasPassword)
                } else {
                    android.util.Log.e("LoginScreen", "Cannot navigate: email is blank")
                }
            } catch (e: Exception) {
                android.util.Log.e("LoginScreen", "Error during navigation", e)
                // Don't crash - just log the error
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
        // DEBUG BANNER
        Card(
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 16.dp),
            colors = CardDefaults.cardColors(
                containerColor = Color(0xFFFF6B6B)
            )
        ) {
            Column(
                modifier = Modifier.padding(16.dp),
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                Text(
                    text = "🔴 LOGIN SCREEN - CODE UPDATED! 🔴",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                    color = Color.White
                )
            }
        }
        
        Text(
            text = "🟢 UPDATED ShareMyCard 🟢",
            style = MaterialTheme.typography.headlineLarge,
            fontWeight = FontWeight.Bold,
            color = Color(0xFF00AA00),
            modifier = Modifier.padding(bottom = 8.dp)
        )
        
        Text(
            text = "✅ CODE CHANGED - THIS IS NEW! ✅",
            style = MaterialTheme.typography.bodyLarge,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
            fontWeight = FontWeight.Bold,
            modifier = Modifier.padding(bottom = 16.dp)
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

