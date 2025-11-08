package com.sharemycard.android.presentation.screens.auth

import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.compose.*
import com.sharemycard.android.presentation.viewmodel.RegisterViewModel

@Composable
fun RegisterScreen(
    viewModel: RegisterViewModel = hiltViewModel(),
    onRegistrationSuccess: (email: String) -> Unit,
    onNavigateToLogin: (email: String, hasPassword: Boolean?) -> Unit
) {
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()
    
    LaunchedEffect(uiState.shouldNavigateToVerify) {
        if (uiState.shouldNavigateToVerify) {
            val email = uiState.email
            if (email.isNotBlank()) {
                viewModel.clearNavigation()
                onRegistrationSuccess(email)
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
            text = "Create Account",
            style = MaterialTheme.typography.headlineLarge,
            modifier = Modifier.padding(bottom = 8.dp)
        )
        
        Text(
            text = "Enter your email to get started",
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
            onClick = { viewModel.register() },
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
                Text("Create Account")
            }
        }
        
        Spacer(modifier = Modifier.height(16.dp))
        
        TextButton(onClick = { onNavigateToLogin("", null) }) {
            Text("Already have an account? Sign in")
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
            Column(
                modifier = Modifier.padding(top = 16.dp),
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                Text(
                    text = error,
                    color = MaterialTheme.colorScheme.error,
                    style = MaterialTheme.typography.bodySmall
                )
                
                // Show different UI based on account status
                when (uiState.accountStatus) {
                    "verified" -> {
                        // Account exists and is verified - navigate directly to verify screen
                        Spacer(modifier = Modifier.height(8.dp))
                        Text(
                            text = "This email is already registered and verified. Please sign in to continue.",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            modifier = Modifier.padding(bottom = 8.dp)
                        )
                        Button(
                            onClick = { 
                                android.util.Log.d("RegisterScreen", "Sign In clicked - email: ${uiState.email}, hasPassword: ${uiState.hasPassword}")
                                onNavigateToLogin(uiState.email, uiState.hasPassword) 
                            },
                            enabled = !uiState.isLoading,
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Text("Sign In")
                        }
                    }
                    "unverified" -> {
                        // Account exists but not verified - allow resend verification
                        Spacer(modifier = Modifier.height(8.dp))
                        Text(
                            text = "This email is already registered but not verified. Please verify your account.",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            modifier = Modifier.padding(bottom = 8.dp)
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
                        Spacer(modifier = Modifier.height(8.dp))
                        TextButton(onClick = { onNavigateToLogin(uiState.email, null) }) {
                            Text("Sign in instead")
                        }
                    }
                    else -> {
                        // Generic duplicate email error (fallback)
                        if (error.contains("already registered", ignoreCase = true)) {
                            Spacer(modifier = Modifier.height(8.dp))
                            TextButton(onClick = { onNavigateToLogin(uiState.email, null) }) {
                                Text("Sign in instead")
                            }
                        }
                    }
                }
            }
        }
    }
}

