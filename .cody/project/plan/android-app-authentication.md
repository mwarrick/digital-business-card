# Android App: Authentication & Security Module

## Overview

This module handles all authentication and security features for the ShareMyCard Android app, including user registration, login, password management, and secure token storage.

## Features

### ✅ Implemented in iOS

1. **User Registration**
   - Email-based registration
   - Email verification code sent
   - User ID returned

2. **Dual Authentication**
   - Password-based login (if password is set)
   - Email verification code login (fallback)
   - Demo account login support

3. **Password Management**
   - Set password (for new users)
   - Change password (for existing users with password)
   - Reset password (forgot password flow)
   - Check password status

4. **Secure Storage**
   - JWT token storage in EncryptedSharedPreferences
   - User email storage in EncryptedSharedPreferences
   - JWT token decoding for email extraction (fallback)
   - Token deletion on logout

5. **Session Management**
   - Check authentication status
   - Token expiration handling
   - Auto-logout on token expiry

## Data Models

### Request/Response Models

```kotlin
// RegisterRequest.kt
data class RegisterRequest(
    val email: String
)

// RegisterResponse.kt
data class RegisterResponse(
    @SerializedName("user_id") val userId: String,
    val email: String,
    val message: String
)

// LoginRequest.kt
data class LoginRequest(
    val email: String
)

// LoginResponse.kt
data class LoginResponse(
    @SerializedName("user_id") val userId: String,
    val email: String,
    @SerializedName("is_admin") val isAdmin: Boolean,
    @SerializedName("has_password") val hasPassword: Boolean,
    @SerializedName("verification_code_sent") val verificationCodeSent: Boolean,
    @SerializedName("is_demo") val isDemo: Boolean? = null
)

// VerifyRequest.kt
data class VerifyRequest(
    val email: String,
    val code: String? = null,
    val password: String? = null
)

// VerifyResponse.kt
data class VerifyResponse(
    val token: String,
    @SerializedName("user_id") val userId: String,
    val email: String,
    @SerializedName("is_admin") val isAdmin: Boolean,
    @SerializedName("is_active") val isActive: Boolean,
    @SerializedName("verification_type") val verificationType: String?,
    @SerializedName("token_expires_in") val tokenExpiresIn: Int,
    val message: String?,
    @SerializedName("is_demo") val isDemo: Boolean? = null,
    val user: UserInfo? = null  // Nested for demo login
)

data class UserInfo(
    val id: String,
    val email: String,
    @SerializedName("is_admin") val isAdmin: Boolean,
    @SerializedName("is_demo") val isDemo: Boolean? = null
)

// PasswordSetRequest.kt
data class PasswordSetRequest(
    val email: String,
    val password: String
)

// PasswordChangeRequest.kt
data class PasswordChangeRequest(
    val email: String,
    val currentPassword: String,
    val newPassword: String
)

// PasswordResetRequest.kt
data class PasswordResetRequest(
    val email: String
)
```

## API Integration

### Retrofit API Service

```kotlin
interface AuthApi {
    @POST("auth/register")
    suspend fun register(@Body request: RegisterRequest): ApiResponse<RegisterResponse>
    
    @POST("auth/login")
    suspend fun login(@Body request: LoginRequest): ApiResponse<LoginResponse>
    
    @POST("auth/verify")
    suspend fun verify(@Body request: VerifyRequest): ApiResponse<VerifyResponse>
    
    @POST("auth/password/set")
    suspend fun setPassword(@Body request: PasswordSetRequest): ApiResponse<ApiResponse<Unit>>
    
    @POST("auth/password/change")
    suspend fun changePassword(@Body request: PasswordChangeRequest): ApiResponse<ApiResponse<Unit>>
    
    @POST("auth/password/reset")
    suspend fun resetPassword(@Body request: PasswordResetRequest): ApiResponse<ApiResponse<Unit>>
    
    @GET("auth/password/status")
    suspend fun checkPasswordStatus(@Query("email") email: String): ApiResponse<PasswordStatusResponse>
}

data class PasswordStatusResponse(
    @SerializedName("has_password") val hasPassword: Boolean
)
```

## Secure Storage

### TokenManager

```kotlin
class TokenManager @Inject constructor(
    @ApplicationContext private val context: Context
) {
    private val sharedPreferences = EncryptedSharedPreferences.create(
        "secure_prefs",
        MasterKeys.getOrCreate(MasterKeys.AES256_GCM_SPEC),
        context,
        EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
        EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM
    )
    
    fun saveToken(token: String) {
        sharedPreferences.edit().putString(KEY_JWT_TOKEN, token).apply()
    }
    
    fun getToken(): String? {
        return sharedPreferences.getString(KEY_JWT_TOKEN, null)
    }
    
    fun deleteToken() {
        sharedPreferences.edit().remove(KEY_JWT_TOKEN).apply()
    }
    
    fun isAuthenticated(): Boolean = getToken() != null
    
    fun saveEmail(email: String) {
        sharedPreferences.edit().putString(KEY_USER_EMAIL, email).apply()
    }
    
    fun getEmail(): String? {
        // First try to get from storage
        val storedEmail = sharedPreferences.getString(KEY_USER_EMAIL, null)
        if (!storedEmail.isNullOrEmpty()) {
            return storedEmail
        }
        
        // Fallback: decode from JWT token
        val token = getToken()
        if (token != null) {
            val decodedEmail = decodeEmailFromToken(token)
            if (decodedEmail != null) {
                // Save for future use
                saveEmail(decodedEmail)
                return decodedEmail
            }
        }
        
        return null
    }
    
    fun deleteEmail() {
        sharedPreferences.edit().remove(KEY_USER_EMAIL).apply()
    }
    
    private fun decodeEmailFromToken(token: String): String? {
        return try {
            val parts = token.split(".")
            if (parts.size != 3) return null
            
            val payload = parts[1]
            val decoded = Base64.decode(payload, Base64.URL_SAFE)
            val json = String(decoded, Charsets.UTF_8)
            val jsonObject = JSONObject(json)
            jsonObject.getString("email")
        } catch (e: Exception) {
            null
        }
    }
    
    companion object {
        private const val KEY_JWT_TOKEN = "jwt_token"
        private const val KEY_USER_EMAIL = "user_email"
    }
}
```

## Authentication Service

### AuthService

```kotlin
class AuthService @Inject constructor(
    private val authApi: AuthApi,
    private val tokenManager: TokenManager
) {
    suspend fun register(email: String): Result<RegisterResponse> {
        return try {
            val response = authApi.register(RegisterRequest(email))
            if (response.success) {
                Result.success(response.data)
            } else {
                Result.failure(Exception(response.message ?: "Registration failed"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    suspend fun login(email: String): Result<LoginResponse> {
        return try {
            val response = authApi.login(LoginRequest(email))
            if (response.success) {
                Result.success(response.data)
            } else {
                Result.failure(Exception(response.message ?: "Login failed"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    suspend fun verify(
        email: String,
        code: String? = null,
        password: String? = null
    ): Result<VerifyResponse> {
        return try {
            val response = authApi.verify(VerifyRequest(email, code, password))
            if (response.success) {
                val verifyResponse = response.data
                // Save token and email
                tokenManager.saveToken(verifyResponse.token)
                tokenManager.saveEmail(verifyResponse.email)
                Result.success(verifyResponse)
            } else {
                Result.failure(Exception(response.message ?: "Verification failed"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    suspend fun setPassword(email: String, password: String): Result<Unit> {
        return try {
            val response = authApi.setPassword(PasswordSetRequest(email, password))
            if (response.success) {
                Result.success(Unit)
            } else {
                Result.failure(Exception(response.message ?: "Failed to set password"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    suspend fun changePassword(
        email: String,
        currentPassword: String,
        newPassword: String
    ): Result<Unit> {
        return try {
            val response = authApi.changePassword(
                PasswordChangeRequest(email, currentPassword, newPassword)
            )
            if (response.success) {
                Result.success(Unit)
            } else {
                Result.failure(Exception(response.message ?: "Failed to change password"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    suspend fun resetPassword(email: String): Result<Unit> {
        return try {
            val response = authApi.resetPassword(PasswordResetRequest(email))
            if (response.success) {
                Result.success(Unit)
            } else {
                Result.failure(Exception(response.message ?: "Failed to reset password"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    suspend fun checkPasswordStatus(email: String): Result<Boolean> {
        return try {
            val response = authApi.checkPasswordStatus(email)
            if (response.success) {
                Result.success(response.data.hasPassword)
            } else {
                Result.failure(Exception(response.message ?: "Failed to check password status"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    fun logout() {
        tokenManager.deleteToken()
        tokenManager.deleteEmail()
    }
    
    fun isAuthenticated(): Boolean = tokenManager.isAuthenticated()
    
    fun getCurrentEmail(): String? = tokenManager.getEmail()
}
```

## UI Screens

### LoginScreen

```kotlin
@Composable
fun LoginScreen(
    viewModel: LoginViewModel = hiltViewModel(),
    onLoginSuccess: () -> Unit,
    onNavigateToRegister: () -> Unit
) {
    val uiState by viewModel.uiState.collectAsState()
    
    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(24.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        // Email input
        OutlinedTextField(
            value = uiState.email,
            onValueChange = viewModel::updateEmail,
            label = { Text("Email") },
            modifier = Modifier.fillMaxWidth()
        )
        
        Spacer(modifier = Modifier.height(16.dp))
        
        // Login button
        Button(
            onClick = { viewModel.login() },
            enabled = !uiState.isLoading && uiState.email.isNotBlank(),
            modifier = Modifier.fillMaxWidth()
        ) {
            if (uiState.isLoading) {
                CircularProgressIndicator(modifier = Modifier.size(20.dp))
            } else {
                Text("Login")
            }
        }
        
        // Register link
        TextButton(onClick = onNavigateToRegister) {
            Text("Create Account")
        }
        
        // Error message
        uiState.errorMessage?.let { error ->
            Text(
                text = error,
                color = MaterialTheme.colorScheme.error,
                modifier = Modifier.padding(top = 16.dp)
            )
        }
    }
    
    // Navigate on success
    LaunchedEffect(uiState.isLoggedIn) {
        if (uiState.isLoggedIn) {
            onLoginSuccess()
        }
    }
}
```

### VerifyScreen

```kotlin
@Composable
fun VerifyScreen(
    viewModel: VerifyViewModel = hiltViewModel(),
    email: String,
    hasPassword: Boolean,
    onVerificationSuccess: () -> Unit
) {
    val uiState by viewModel.uiState.collectAsState()
    
    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(24.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Text("Verify Your Email", style = MaterialTheme.typography.headlineMedium)
        
        Spacer(modifier = Modifier.height(16.dp))
        
        if (hasPassword) {
            // Password input
            OutlinedTextField(
                value = uiState.password,
                onValueChange = viewModel::updatePassword,
                label = { Text("Password") },
                visualTransformation = PasswordVisualTransformation(),
                modifier = Modifier.fillMaxWidth()
            )
            
            TextButton(onClick = { /* Forgot password */ }) {
                Text("Forgot Password?")
            }
        } else {
            // Verification code input
            OutlinedTextField(
                value = uiState.code,
                onValueChange = viewModel::updateCode,
                label = { Text("Verification Code") },
                modifier = Modifier.fillMaxWidth()
            )
        }
        
        Spacer(modifier = Modifier.height(16.dp))
        
        Button(
            onClick = { viewModel.verify(email, hasPassword) },
            enabled = !uiState.isLoading && 
                (if (hasPassword) uiState.password.isNotBlank() else uiState.code.isNotBlank()),
            modifier = Modifier.fillMaxWidth()
        ) {
            if (uiState.isLoading) {
                CircularProgressIndicator(modifier = Modifier.size(20.dp))
            } else {
                Text("Verify")
            }
        }
        
        uiState.errorMessage?.let { error ->
            Text(
                text = error,
                color = MaterialTheme.colorScheme.error,
                modifier = Modifier.padding(top = 16.dp)
            )
        }
    }
    
    LaunchedEffect(uiState.isVerified) {
        if (uiState.isVerified) {
            onVerificationSuccess()
        }
    }
}
```

### PasswordSettingsScreen

```kotlin
@Composable
fun PasswordSettingsScreen(
    viewModel: PasswordSettingsViewModel = hiltViewModel(),
    onNavigateBack: () -> Unit
) {
    val uiState by viewModel.uiState.collectAsState()
    
    LaunchedEffect(Unit) {
        viewModel.checkPasswordStatus()
    }
    
    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(24.dp)
    ) {
        Text(
            "Account Security",
            style = MaterialTheme.typography.headlineMedium
        )
        
        Spacer(modifier = Modifier.height(24.dp))
        
        if (uiState.checkingStatus) {
            CircularProgressIndicator()
        } else if (uiState.hasPassword) {
            // Change password form
            OutlinedTextField(
                value = uiState.currentPassword,
                onValueChange = viewModel::updateCurrentPassword,
                label = { Text("Current Password") },
                visualTransformation = PasswordVisualTransformation(),
                modifier = Modifier.fillMaxWidth()
            )
            
            Spacer(modifier = Modifier.height(16.dp))
            
            OutlinedTextField(
                value = uiState.newPassword,
                onValueChange = viewModel::updateNewPassword,
                label = { Text("New Password") },
                visualTransformation = PasswordVisualTransformation(),
                modifier = Modifier.fillMaxWidth()
            )
            
            Spacer(modifier = Modifier.height(16.dp))
            
            OutlinedTextField(
                value = uiState.confirmPassword,
                onValueChange = viewModel::updateConfirmPassword,
                label = { Text("Confirm Password") },
                visualTransformation = PasswordVisualTransformation(),
                modifier = Modifier.fillMaxWidth()
            )
            
            Spacer(modifier = Modifier.height(24.dp))
            
            Button(
                onClick = { viewModel.changePassword() },
                enabled = !uiState.isLoading,
                modifier = Modifier.fillMaxWidth()
            ) {
                if (uiState.isLoading) {
                    CircularProgressIndicator(modifier = Modifier.size(20.dp))
                } else {
                    Text("Change Password")
                }
            }
        } else {
            // Set password form
            OutlinedTextField(
                value = uiState.newPassword,
                onValueChange = viewModel::updateNewPassword,
                label = { Text("Password") },
                visualTransformation = PasswordVisualTransformation(),
                modifier = Modifier.fillMaxWidth()
            )
            
            Spacer(modifier = Modifier.height(16.dp))
            
            OutlinedTextField(
                value = uiState.confirmPassword,
                onValueChange = viewModel::updateConfirmPassword,
                label = { Text("Confirm Password") },
                visualTransformation = PasswordVisualTransformation(),
                modifier = Modifier.fillMaxWidth()
            )
            
            Spacer(modifier = Modifier.height(24.dp))
            
            Button(
                onClick = { viewModel.setPassword() },
                enabled = !uiState.isLoading,
                modifier = Modifier.fillMaxWidth()
            ) {
                if (uiState.isLoading) {
                    CircularProgressIndicator(modifier = Modifier.size(20.dp))
                } else {
                    Text("Set Password")
                }
            }
        }
        
        uiState.errorMessage?.let { error ->
            Text(
                text = error,
                color = MaterialTheme.colorScheme.error,
                modifier = Modifier.padding(top = 16.dp)
            )
        }
        
        uiState.successMessage?.let { success ->
            Text(
                text = success,
                color = MaterialTheme.colorScheme.primary,
                modifier = Modifier.padding(top = 16.dp)
            )
        }
    }
}
```

## ViewModels

### LoginViewModel

```kotlin
@HiltViewModel
class LoginViewModel @Inject constructor(
    private val authService: AuthService
) : ViewModel() {
    private val _uiState = MutableStateFlow(LoginUiState())
    val uiState: StateFlow<LoginUiState> = _uiState.asStateFlow()
    
    fun updateEmail(email: String) {
        _uiState.update { it.copy(email = email, errorMessage = null) }
    }
    
    fun login() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            
            val result = authService.login(_uiState.value.email)
            result.fold(
                onSuccess = { response ->
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            loginResponse = response,
                            shouldNavigateToVerify = true
                        )
                    }
                },
                onFailure = { error ->
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            errorMessage = error.message ?: "Login failed"
                        )
                    }
                }
            )
        }
    }
}

data class LoginUiState(
    val email: String = "",
    val isLoading: Boolean = false,
    val errorMessage: String? = null,
    val loginResponse: LoginResponse? = null,
    val shouldNavigateToVerify: Boolean = false,
    val isLoggedIn: Boolean = false
)
```

## Dependencies

```kotlin
// build.gradle.kts
dependencies {
    // Security
    implementation("androidx.security:security-crypto:1.1.0-alpha06")
    
    // JSON
    implementation("com.google.code.gson:gson:2.10.1")
    
    // Coroutines
    implementation("org.jetbrains.kotlinx:kotlinx-coroutines-android:1.7.3")
}
```

## Testing

### Unit Tests

```kotlin
@RunWith(AndroidJUnit4::class)
class AuthServiceTest {
    @Test
    fun login_success_savesToken() = runTest {
        // Test implementation
    }
    
    @Test
    fun verify_withPassword_savesToken() = runTest {
        // Test implementation
    }
}
```

## Integration Points

- **TokenManager**: Used by API interceptor for authenticated requests
- **AuthService**: Used by ViewModels for authentication flows
- **Navigation**: Controls app navigation based on auth status


