# Android App: UI & Navigation Module

## Overview

This module handles all UI screens, navigation, and reusable components for the ShareMyCard Android app.

## Features

### ✅ Implemented in iOS

1. **Tab Navigation**
   - Home tab
   - Cards tab
   - Contacts tab
   - Leads tab
   - Settings tab

2. **Home Screen**
   - App icon and title
   - Card count
   - Contact count
   - Lead count
   - User email display
   - Sync button with status
   - Logout button
   - Report Issues link
   - Version display

3. **List Screens**
   - Search functionality
   - Pull-to-refresh
   - Empty states
   - Loading states

4. **Form Screens**
   - Validation
   - Error messages
   - Success messages

## Navigation Graph

### Main Navigation

```kotlin
@Composable
fun ShareMyCardNavGraph(
    navController: NavHostController = rememberNavController(),
    tokenManager: TokenManager
) {
    NavHost(
        navController = navController,
        startDestination = if (tokenManager.isAuthenticated()) "home" else "auth"
    ) {
        // Authentication flow
        navigation(startDestination = "login", route = "auth") {
            composable("login") {
                LoginScreen(
                    onLoginSuccess = {
                        navController.navigate("home") {
                            popUpTo("auth") { inclusive = true }
                        }
                    },
                    onNavigateToRegister = {
                        navController.navigate("register")
                    }
                )
            }
            composable("register") {
                RegisterScreen(
                    onRegistrationSuccess = {
                        navController.navigate("verify")
                    }
                )
            }
            composable("verify") {
                VerifyScreen(
                    onVerificationSuccess = {
                        navController.navigate("home") {
                            popUpTo("auth") { inclusive = true }
                        }
                    }
                )
            }
        }
        
        // Main app flow
        composable("home") {
            HomeScreen(
                onNavigateToCardList = { navController.navigate("card_list") },
                onNavigateToCreateCard = { navController.navigate("card_create") },
                onNavigateToSettings = { navController.navigate("settings") },
                onLogout = {
                    navController.navigate("auth") {
                        popUpTo(0) { inclusive = true }
                    }
                }
            )
        }
        
        composable("card_list") {
            BusinessCardListScreen(
                onNavigateToCardDetail = { cardId ->
                    navController.navigate("card_detail/$cardId")
                },
                onNavigateToCreateCard = {
                    navController.navigate("card_create")
                }
            )
        }
        
        composable("card_create") {
            BusinessCardCreateScreen(
                onCardCreated = { navController.popBackStack() }
            )
        }
        
        composable(
            route = "card_detail/{cardId}",
            arguments = listOf(navArgument("cardId") { type = NavType.StringType })
        ) { backStackEntry ->
            val cardId = backStackEntry.arguments?.getString("cardId")
            BusinessCardDetailScreen(
                cardId = cardId!!,
                onNavigateToEdit = { navController.navigate("card_edit/$cardId") },
                onNavigateToQRCode = { navController.navigate("qr_code/$cardId") }
            )
        }
        
        composable("contacts") {
            ContactsDashboardScreen(
                onNavigateToContactDetail = { contactId ->
                    navController.navigate("contact_detail/$contactId")
                },
                onNavigateToAddContact = {
                    navController.navigate("contact_add")
                }
            )
        }
        
        composable("leads") {
            LeadsDashboardScreen(
                onNavigateToLeadDetail = { leadId ->
                    navController.navigate("lead_detail/$leadId")
                }
            )
        }
        
        composable("settings") {
            SettingsScreen(
                onNavigateToPasswordSettings = {
                    navController.navigate("password_settings")
                },
                onNavigateBack = { navController.popBackStack() }
            )
        }
        
        composable("qr_scanner") {
            QRCodeScannerScreen(
                onQRCodeScanned = { content ->
                    // Handle scanned content
                    navController.popBackStack()
                },
                onDismiss = { navController.popBackStack() }
            )
        }
    }
}
```

## Home Screen

### HomeScreen

```kotlin
@Composable
fun HomeScreen(
    viewModel: HomeViewModel = hiltViewModel(),
    onNavigateToCardList: () -> Unit,
    onNavigateToCreateCard: () -> Unit,
    onNavigateToSettings: () -> Unit,
    onLogout: () -> Unit
) {
    val uiState by viewModel.uiState.collectAsState()
    val userEmail = viewModel.userEmail.collectAsState().value
    
    Scaffold(
        topBar = {
            TopAppBar(title = { Text("ShareMyCard") })
        }
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .padding(24.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center
        ) {
            // App Icon
            Icon(
                imageVector = Icons.Default.CardGiftcard,
                contentDescription = null,
                modifier = Modifier.size(80.dp),
                tint = MaterialTheme.colorScheme.primary
            )
            
            Spacer(modifier = Modifier.height(16.dp))
            
            Text(
                text = "ShareMyCard",
                style = MaterialTheme.typography.headlineLarge
            )
            
            Text(
                text = "Your Digital Business Cards",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
            
            Spacer(modifier = Modifier.height(24.dp))
            
            // User Email
            userEmail?.let { email ->
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier.padding(bottom = 8.dp)
                ) {
                    Icon(
                        imageVector = Icons.Default.Person,
                        contentDescription = null,
                        tint = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                    Spacer(modifier = Modifier.width(8.dp))
                    Text(
                        text = email,
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
            }
            
            // Card Count
            if (uiState.cardsCount > 0) {
                Text(
                    text = "${uiState.cardsCount} ${if (uiState.cardsCount == 1) "Card" else "Cards"}",
                    style = MaterialTheme.typography.titleLarge,
                    color = MaterialTheme.colorScheme.primary
                )
            }
            
            // Contact Count
            if (uiState.contactsCount > 0) {
                Text(
                    text = "${uiState.contactsCount} ${if (uiState.contactsCount == 1) "Contact" else "Contacts"}",
                    style = MaterialTheme.typography.titleLarge,
                    color = MaterialTheme.colorScheme.secondary
                )
            }
            
            // Lead Count
            if (uiState.leadsCount > 0) {
                Text(
                    text = "${uiState.leadsCount} ${if (uiState.leadsCount == 1) "Lead" else "Leads"}",
                    style = MaterialTheme.typography.titleLarge,
                    color = Color(0xFFFF9500) // Orange
                )
            }
            
            Spacer(modifier = Modifier.height(32.dp))
            
            // Sync Button
            Button(
                onClick = { viewModel.performSync() },
                enabled = !uiState.isSyncing,
                modifier = Modifier.fillMaxWidth()
            ) {
                if (uiState.isSyncing) {
                    CircularProgressIndicator(modifier = Modifier.size(20.dp))
                } else {
                    Icon(Icons.Default.Sync, contentDescription = null)
                }
                Spacer(modifier = Modifier.width(8.dp))
                Text(if (uiState.isSyncing) "Syncing..." else "Sync with Server")
            }
            
            if (uiState.syncMessage.isNotEmpty()) {
                Text(
                    text = uiState.syncMessage,
                    style = MaterialTheme.typography.bodySmall,
                    color = if (uiState.syncMessage.contains("✅")) 
                        Color.Green else MaterialTheme.colorScheme.error
                )
            }
            
            HorizontalDivider(modifier = Modifier.padding(vertical = 16.dp))
            
            // Web App Link
            TextButton(
                onClick = {
                    val intent = Intent(Intent.ACTION_VIEW, Uri.parse("https://sharemycard.app"))
                    context.startActivity(intent)
                }
            ) {
                Text("Use ShareMyCard.app on the Web")
            }
            
            // Report Issues Link
            TextButton(
                onClick = {
                    val intent = Intent(Intent.ACTION_VIEW, Uri.parse("https://github.com/mwarrick/digital-business-card/issues"))
                    context.startActivity(intent)
                }
            ) {
                Text("Report Issues")
            }
            
            // Version
            Text(
                text = "Version 1.8",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
            
            HorizontalDivider(modifier = Modifier.padding(vertical = 16.dp))
            
            // Logout Button
            TextButton(
                onClick = onLogout,
                modifier = Modifier.fillMaxWidth()
            ) {
                Text("Logout", color = MaterialTheme.colorScheme.error)
            }
        }
    }
}
```

## Tab Navigation

### MainTabScreen

```kotlin
@Composable
fun MainTabScreen() {
    var selectedTab by remember { mutableIntStateOf(0) }
    
    Scaffold(
        bottomBar = {
            NavigationBar {
                NavigationBarItem(
                    icon = { Icon(Icons.Default.Home, contentDescription = "Home") },
                    label = { Text("Home") },
                    selected = selectedTab == 0,
                    onClick = { selectedTab = 0 }
                )
                NavigationBarItem(
                    icon = { Icon(Icons.Default.CardGiftcard, contentDescription = "Cards") },
                    label = { Text("Cards") },
                    selected = selectedTab == 1,
                    onClick = { selectedTab = 1 }
                )
                NavigationBarItem(
                    icon = { Icon(Icons.Default.Person, contentDescription = "Contacts") },
                    label = { Text("Contacts") },
                    selected = selectedTab == 2,
                    onClick = { selectedTab = 2 }
                )
                NavigationBarItem(
                    icon = { Icon(Icons.Default.PersonAdd, contentDescription = "Leads") },
                    label = { Text("Leads") },
                    selected = selectedTab == 3,
                    onClick = { selectedTab = 3 }
                )
                NavigationBarItem(
                    icon = { Icon(Icons.Default.Settings, contentDescription = "Settings") },
                    label = { Text("Settings") },
                    selected = selectedTab == 4,
                    onClick = { selectedTab = 4 }
                )
            }
        }
    ) { paddingValues ->
        when (selectedTab) {
            0 -> HomeScreen(modifier = Modifier.padding(paddingValues))
            1 -> BusinessCardListScreen(modifier = Modifier.padding(paddingValues))
            2 -> ContactsDashboardScreen(modifier = Modifier.padding(paddingValues))
            3 -> LeadsDashboardScreen(modifier = Modifier.padding(paddingValues))
            4 -> SettingsScreen(modifier = Modifier.padding(paddingValues))
        }
    }
}
```

## Reusable Components

### SearchBar

```kotlin
@Composable
fun SearchBar(
    searchText: String,
    onSearchTextChange: (String) -> Unit,
    modifier: Modifier = Modifier
) {
    OutlinedTextField(
        value = searchText,
        onValueChange = onSearchTextChange,
        leadingIcon = {
            Icon(Icons.Default.Search, contentDescription = "Search")
        },
        trailingIcon = {
            if (searchText.isNotEmpty()) {
                IconButton(onClick = { onSearchTextChange("") }) {
                    Icon(Icons.Default.Clear, contentDescription = "Clear")
                }
            }
        },
        placeholder = { Text("Search...") },
        singleLine = true,
        modifier = modifier.fillMaxWidth()
    )
}
```

### PullToRefresh

```kotlin
@Composable
fun PullToRefreshList(
    onRefresh: suspend () -> Unit,
    content: @Composable () -> Unit
) {
    val pullRefreshState = rememberPullRefreshState(
        refreshing = false,
        onRefresh = onRefresh
    )
    
    Box(modifier = Modifier.pullRefresh(pullRefreshState)) {
        content()
        PullRefreshIndicator(
            refreshing = pullRefreshState.isRefreshing,
            state = pullRefreshState,
            modifier = Modifier.align(Alignment.TopCenter)
        )
    }
}
```

## Dependencies

```kotlin
dependencies {
    // Compose
    implementation(platform("androidx.compose:compose-bom:2024.01.00"))
    implementation("androidx.compose.ui:ui")
    implementation("androidx.compose.material3:material3")
    implementation("androidx.compose.material:material-icons-extended")
    
    // Navigation
    implementation("androidx.navigation:navigation-compose:2.7.6")
    
    // Pull to refresh
    implementation("androidx.compose.material:material:1.5.4")
}
```

## Integration Points

- **All Modules**: Provides UI for all features
- **Navigation**: Connects all screens
- **ViewModels**: Provides data to UI

