package com.sharemycard.android.presentation.screens.cards

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import coil.compose.AsyncImage
import com.sharemycard.android.domain.models.BusinessCard
import com.sharemycard.android.presentation.viewmodel.CardListViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun CardsScreen(
    modifier: Modifier = Modifier,
    viewModel: CardListViewModel = hiltViewModel(),
    onCreateCard: () -> Unit = {},
    onCardClick: (String) -> Unit = {}, // Used for View button
    onQRClick: (String) -> Unit = {} // Used for QR button
) {
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()
    val cards by viewModel.filteredCards.collectAsStateWithLifecycle()
    val searchText by viewModel.searchText.collectAsStateWithLifecycle()
    
    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Business Cards") },
                actions = {
                    IconButton(
                        onClick = { viewModel.refresh() },
                        enabled = !uiState.isRefreshing
                    ) {
                        Icon(
                            imageVector = Icons.Default.Refresh,
                            contentDescription = "Refresh"
                        )
                    }
                    // Removed three dots menu - not needed for now
                }
            )
        },
        floatingActionButton = {
            FloatingActionButton(
                onClick = onCreateCard,
                containerColor = MaterialTheme.colorScheme.primary
            ) {
                Icon(
                    imageVector = Icons.Default.Add,
                    contentDescription = "Create Card",
                    tint = MaterialTheme.colorScheme.onPrimary
                )
            }
        }
    ) { paddingValues ->
        Box(
            modifier = modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            Column(
                modifier = Modifier.fillMaxSize()
            ) {
                // Search Bar
                if (cards.isNotEmpty() || searchText.isNotEmpty()) {
                    OutlinedTextField(
                        value = searchText,
                        onValueChange = { viewModel.updateSearchText(it) },
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(16.dp),
                        leadingIcon = {
                            Icon(Icons.Default.Search, contentDescription = "Search")
                        },
                        trailingIcon = {
                            if (searchText.isNotEmpty()) {
                                IconButton(onClick = { viewModel.updateSearchText("") }) {
                                    Icon(Icons.Default.Clear, contentDescription = "Clear")
                                }
                            }
                        },
                        placeholder = { Text("Search cards...") },
                        singleLine = true
                    )
                }
                
                // Content
                when {
                    uiState.isLoading && cards.isEmpty() -> {
                        Box(
                            modifier = Modifier.fillMaxSize(),
                            contentAlignment = Alignment.Center
                        ) {
                            CircularProgressIndicator()
                        }
                    }
                    cards.isEmpty() -> {
                        EmptyState(
                            hasSearchText = searchText.isNotEmpty(),
                            onCreateCard = onCreateCard
                        )
                    }
                    else -> {
                        LazyColumn(
                            modifier = Modifier.fillMaxSize(),
                            contentPadding = PaddingValues(horizontal = 16.dp, vertical = 8.dp),
                            verticalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            items(
                                items = cards,
                                key = { it.id }
                            ) { card ->
                                CardItem(
                                    card = card,
                                    onClick = { onCardClick(card.id) },
                                    onDelete = { viewModel.deleteCard(card) },
                                    onView = { onCardClick(card.id) },
                                    onQR = { onQRClick(card.id) },
                                    onEdit = { /* TODO: Navigate to edit screen */ }
                                )
                            }
                        }
                    }
                }
            }
            
            // Refresh indicator overlay
            if (uiState.isRefreshing) {
                CircularProgressIndicator(
                    modifier = Modifier
                        .align(Alignment.TopCenter)
                        .padding(16.dp)
                )
            }
        }
    }
}

@Composable
fun CardItem(
    card: BusinessCard,
    onClick: () -> Unit,
    onDelete: () -> Unit,
    onView: () -> Unit = onClick,
    onQR: () -> Unit = {},
    onEdit: () -> Unit = {}
) {
    Card(
        modifier = Modifier.fillMaxWidth()
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp)
        ) {
            // Top section: Logo, Name, Company
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(16.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                // Company Logo
                Box(
                    modifier = Modifier
                        .size(60.dp)
                        .clip(RoundedCornerShape(8.dp)),
                    contentAlignment = Alignment.Center
                ) {
                    if (!card.companyLogoPath.isNullOrBlank()) {
                        // Build full URL for the logo
                        val logoUrl = when {
                            card.companyLogoPath!!.startsWith("http") -> {
                                // Already a full URL
                                card.companyLogoPath
                            }
                            card.companyLogoPath!!.startsWith("/api/media/view") -> {
                                // Already has the API path, just add base URL
                                "https://sharemycard.app${card.companyLogoPath}"
                            }
                            else -> {
                                // Just a filename, construct the full URL
                                "https://sharemycard.app/api/media/view?file=${card.companyLogoPath}"
                            }
                        }
                        
                        AsyncImage(
                            model = logoUrl,
                            contentDescription = "Company Logo",
                            modifier = Modifier.fillMaxSize(),
                            contentScale = ContentScale.Crop
                        )
                    } else {
                        // Placeholder when no logo
                        Surface(
                            color = MaterialTheme.colorScheme.surfaceVariant,
                            modifier = Modifier.fillMaxSize()
                        ) {
                            Box(
                                modifier = Modifier.fillMaxSize(),
                                contentAlignment = Alignment.Center
                            ) {
                                Icon(
                                    imageVector = Icons.Default.Business,
                                    contentDescription = "No Logo",
                                    modifier = Modifier.size(32.dp),
                                    tint = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                            }
                        }
                    }
                }
                
                // Name, Title, and Company
                Column(
                    modifier = Modifier.weight(1f)
                ) {
                    Text(
                        text = card.fullName,
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Medium
                    )
                    
                    if (!card.jobTitle.isNullOrBlank()) {
                        Text(
                            text = card.jobTitle ?: "",
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            modifier = Modifier.padding(top = 4.dp)
                        )
                    }
                    
                    if (!card.companyName.isNullOrBlank()) {
                        Text(
                            text = card.companyName ?: "",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                }
            }
            
            Spacer(modifier = Modifier.height(12.dp))
            
            // Action buttons: View, QR, Edit
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                // View button (Green)
                Button(
                    onClick = onView,
                    modifier = Modifier.weight(1f),
                    colors = ButtonDefaults.buttonColors(
                        containerColor = androidx.compose.ui.graphics.Color(0xFF4CAF50) // Green
                    )
                ) {
                    Icon(
                        imageVector = Icons.Default.Visibility,
                        contentDescription = "View",
                        modifier = Modifier.size(18.dp)
                    )
                    Spacer(modifier = Modifier.width(4.dp))
                    Text("View", style = MaterialTheme.typography.labelMedium)
                }
                
                // QR button (Blue)
                Button(
                    onClick = onQR,
                    modifier = Modifier.weight(1f),
                    colors = ButtonDefaults.buttonColors(
                        containerColor = androidx.compose.ui.graphics.Color(0xFF2196F3) // Blue
                    )
                ) {
                    Icon(
                        imageVector = Icons.Default.QrCodeScanner,
                        contentDescription = "QR Code",
                        modifier = Modifier.size(18.dp)
                    )
                    Spacer(modifier = Modifier.width(4.dp))
                    Text("QR", style = MaterialTheme.typography.labelMedium)
                }
                
                // Edit button (Orange)
                Button(
                    onClick = onEdit,
                    modifier = Modifier.weight(1f),
                    colors = ButtonDefaults.buttonColors(
                        containerColor = androidx.compose.ui.graphics.Color(0xFFFF9800) // Orange
                    )
                ) {
                    Icon(
                        imageVector = Icons.Default.Edit,
                        contentDescription = "Edit",
                        modifier = Modifier.size(18.dp)
                    )
                    Spacer(modifier = Modifier.width(4.dp))
                    Text("Edit", style = MaterialTheme.typography.labelMedium)
                }
            }
        }
    }
}

@Composable
fun EmptyState(
    hasSearchText: Boolean,
    onCreateCard: () -> Unit
) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Icon(
            imageVector = Icons.Default.Badge,
            contentDescription = "No Cards",
            modifier = Modifier.size(64.dp),
            tint = MaterialTheme.colorScheme.onSurfaceVariant
        )
        
        Spacer(modifier = Modifier.height(16.dp))
        
        Text(
            text = if (hasSearchText) "No cards found" else "No Business Cards",
            style = MaterialTheme.typography.titleLarge,
            fontWeight = FontWeight.Bold
        )
        
        Spacer(modifier = Modifier.height(8.dp))
        
        Text(
            text = if (hasSearchText) {
                "Try adjusting your search"
            } else {
                "Create your first business card to get started"
            },
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
        
        if (!hasSearchText) {
            Spacer(modifier = Modifier.height(24.dp))
            
            Button(onClick = onCreateCard) {
                Icon(
                    imageVector = Icons.Default.Add,
                    contentDescription = "Create",
                    modifier = Modifier.size(20.dp)
                )
                Spacer(modifier = Modifier.width(8.dp))
                Text("Create Card")
            }
        }
    }
}
