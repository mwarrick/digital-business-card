# Android App: Settings & Account Management Module

## Overview

This module handles settings screens, account management, and password settings.

## Features

### ✅ Implemented in iOS

1. **Settings Screen**
   - Account security access
   - Logout functionality
   - User information display

2. **Password Settings**
   - Set password (for new users)
   - Change password (for existing users)
   - Check password status
   - Form validation
   - Success/error messages

3. **Account Management**
   - User email display
   - Account information

## Settings Screen

### SettingsScreen

```kotlin
@Composable
fun SettingsScreen(
    viewModel: SettingsViewModel = hiltViewModel(),
    onNavigateToPasswordSettings: () -> Unit,
    onNavigateBack: () -> Unit
) {
    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Settings") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                }
            )
        }
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            // Account Security Section
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(16.dp)
            ) {
                Column {
                    ListItem(
                        headlineContent = { Text("Account Security") },
                        supportingContent = { Text("Manage your password settings") },
                        leadingContent = {
                            Icon(
                                Icons.Default.Lock,
                                contentDescription = null,
                                tint = MaterialTheme.colorScheme.primary
                            )
                        },
                        trailingContent = {
                            IconButton(onClick = onNavigateToPasswordSettings) {
                                Icon(Icons.Default.ChevronRight, contentDescription = "Open")
                            }
                        }
                    )
                }
            }
            
            // User Information Section
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(16.dp)
            ) {
                Column(
                    modifier = Modifier.padding(16.dp)
                ) {
                    Text(
                        "Account Information",
                        style = MaterialTheme.typography.titleMedium
                    )
                    Spacer(modifier = Modifier.height(8.dp))
                    
                    viewModel.userEmail.collectAsState().value?.let { email ->
                        Row(
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Icon(
                                Icons.Default.Email,
                                contentDescription = null,
                                tint = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                            Spacer(modifier = Modifier.width(8.dp))
                            Text(
                                email,
                                style = MaterialTheme.typography.bodyLarge
                            )
                        }
                    }
                }
            }
        }
    }
}
```

## Password Settings Screen

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
    
    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Account Security") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                }
            )
        }
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .padding(24.dp)
        ) {
            // Header
            Column(
                horizontalAlignment = Alignment.CenterHorizontally,
                modifier = Modifier.fillMaxWidth()
            ) {
                Icon(
                    imageVector = Icons.Default.Lock,
                    contentDescription = null,
                    modifier = Modifier.size(64.dp),
                    tint = MaterialTheme.colorScheme.primary
                )
                Spacer(modifier = Modifier.height(16.dp))
                Text(
                    "Account Security",
                    style = MaterialTheme.typography.headlineMedium
                )
                Text(
                    "Manage your password settings",
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
            
            Spacer(modifier = Modifier.height(32.dp))
            
            if (uiState.checkingStatus) {
                CircularProgressIndicator(
                    modifier = Modifier.align(Alignment.CenterHorizontally)
                )
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
                    enabled = !uiState.isLoading && 
                        uiState.currentPassword.isNotBlank() &&
                        uiState.newPassword.isNotBlank() &&
                        uiState.newPassword == uiState.confirmPassword,
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
                    enabled = !uiState.isLoading &&
                        uiState.newPassword.isNotBlank() &&
                        uiState.newPassword == uiState.confirmPassword,
                    modifier = Modifier.fillMaxWidth()
                ) {
                    if (uiState.isLoading) {
                        CircularProgressIndicator(modifier = Modifier.size(20.dp))
                    } else {
                        Text("Set Password")
                    }
                }
            }
            
            Spacer(modifier = Modifier.height(16.dp))
            
            // Error message
            uiState.errorMessage?.let { error ->
                Text(
                    text = error,
                    color = MaterialTheme.colorScheme.error,
                    style = MaterialTheme.typography.bodySmall
                )
            }
            
            // Success message
            uiState.successMessage?.let { success ->
                Text(
                    text = success,
                    color = MaterialTheme.colorScheme.primary,
                    style = MaterialTheme.typography.bodySmall
                )
            }
        }
    }
    
    // Navigate back on success
    LaunchedEffect(uiState.passwordSet) {
        if (uiState.passwordSet) {
            onNavigateBack()
        }
    }
}
```

## ViewModels

### SettingsViewModel

```kotlin
@HiltViewModel
class SettingsViewModel @Inject constructor(
    private val tokenManager: TokenManager
) : ViewModel() {
    val userEmail: StateFlow<String?> = flow {
        emit(tokenManager.getEmail())
    }.stateIn(
        scope = viewModelScope,
        started = SharingStarted.WhileSubscribed(5000),
        initialValue = null
    )
}
```

### PasswordSettingsViewModel

```kotlin
@HiltViewModel
class PasswordSettingsViewModel @Inject constructor(
    private val authService: AuthService,
    private val tokenManager: TokenManager
) : ViewModel() {
    private val _uiState = MutableStateFlow(PasswordSettingsUiState())
    val uiState: StateFlow<PasswordSettingsUiState> = _uiState.asStateFlow()
    
    fun checkPasswordStatus() {
        viewModelScope.launch {
            val email = tokenManager.getEmail() ?: return@launch
            _uiState.update { it.copy(checkingStatus = true) }
            
            val result = authService.checkPasswordStatus(email)
            result.fold(
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
                            errorMessage = error.message
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
        viewModelScope.launch {
            val email = tokenManager.getEmail() ?: return@launch
            val password = _uiState.value.newPassword
            
            if (password != _uiState.value.confirmPassword) {
                _uiState.update { it.copy(errorMessage = "Passwords do not match") }
                return@launch
            }
            
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            
            val result = authService.setPassword(email, password)
            result.fold(
                onSuccess = {
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            successMessage = "Password set successfully",
                            passwordSet = true
                        )
                    }
                },
                onFailure = { error ->
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
        viewModelScope.launch {
            val email = tokenManager.getEmail() ?: return@launch
            val currentPassword = _uiState.value.currentPassword
            val newPassword = _uiState.value.newPassword
            
            if (newPassword != _uiState.value.confirmPassword) {
                _uiState.update { it.copy(errorMessage = "Passwords do not match") }
                return@launch
            }
            
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            
            val result = authService.changePassword(email, currentPassword, newPassword)
            result.fold(
                onSuccess = {
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            successMessage = "Password changed successfully",
                            passwordSet = true
                        )
                    }
                },
                onFailure = { error ->
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
}

data class PasswordSettingsUiState(
    val checkingStatus: Boolean = true,
    val hasPassword: Boolean = false,
    val currentPassword: String = "",
    val newPassword: String = "",
    val confirmPassword: String = "",
    val isLoading: Boolean = false,
    val errorMessage: String? = null,
    val successMessage: String? = null,
    val passwordSet: Boolean = false
)
```

## Dependencies

```kotlin
dependencies {
    // Compose
    implementation("androidx.compose.material3:material3")
    implementation("androidx.compose.material:material-icons-extended")
}
```

## Integration Points

- **Authentication Module**: Uses AuthService for password operations
- **TokenManager**: Gets user email
- **Navigation**: Integrates with navigation graph


