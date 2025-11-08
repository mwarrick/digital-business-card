package com.sharemycard.android.presentation.screens.contacts

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.sharemycard.android.domain.models.Contact
import com.sharemycard.android.presentation.viewmodel.ContactListViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ContactsScreen(
    modifier: Modifier = Modifier,
    viewModel: ContactListViewModel = hiltViewModel(),
    onContactClick: (String) -> Unit = {}
) {
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()
    val contacts by viewModel.filteredContacts.collectAsStateWithLifecycle()
    val searchText by viewModel.searchText.collectAsStateWithLifecycle()
    
    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Contacts") },
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
                    if (contacts.isNotEmpty()) {
                        IconButton(onClick = { /* TODO: Filter/Sort options */ }) {
                            Icon(Icons.Default.MoreVert, contentDescription = "More options")
                        }
                    }
                }
            )
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
                if (contacts.isNotEmpty() || searchText.isNotEmpty()) {
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
                        placeholder = { Text("Search contacts...") },
                        singleLine = true
                    )
                }
                
                // Content
                when {
                    uiState.isLoading && contacts.isEmpty() -> {
                        Box(
                            modifier = Modifier.fillMaxSize(),
                            contentAlignment = Alignment.Center
                        ) {
                            CircularProgressIndicator()
                        }
                    }
                    contacts.isEmpty() -> {
                        EmptyState(hasSearchText = searchText.isNotEmpty())
                    }
                    else -> {
                        LazyColumn(
                            modifier = Modifier.fillMaxSize(),
                            contentPadding = PaddingValues(horizontal = 16.dp, vertical = 8.dp),
                            verticalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            items(
                                items = contacts,
                                key = { it.id }
                            ) { contact ->
                                ContactItem(
                                    contact = contact,
                                    onClick = { onContactClick(contact.id) },
                                    onDelete = { viewModel.deleteContact(contact) }
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
fun ContactItem(
    contact: Contact,
    onClick: () -> Unit,
    onDelete: () -> Unit
) {
    Card(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth()
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Column(
                modifier = Modifier.weight(1f)
            ) {
                Text(
                    text = contact.fullName,
                    style = MaterialTheme.typography.titleLarge,
                    fontWeight = FontWeight.Bold
                )
                
                if (!contact.company.isNullOrBlank()) {
                    Text(
                        text = contact.company ?: "",
                        style = MaterialTheme.typography.bodyMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                        modifier = Modifier.padding(top = 4.dp)
                    )
                }
                
                if (!contact.jobTitle.isNullOrBlank()) {
                    Text(
                        text = contact.jobTitle ?: "",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
                
                if (!contact.email.isNullOrBlank()) {
                    Row(
                        modifier = Modifier.padding(top = 8.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Icon(
                            imageVector = Icons.Default.Email,
                            contentDescription = "Email",
                            modifier = Modifier.size(16.dp),
                            tint = MaterialTheme.colorScheme.primary
                        )
                        Spacer(modifier = Modifier.width(4.dp))
                        Text(
                            text = contact.email ?: "",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                }
                
                if (!contact.phone.isNullOrBlank() || !contact.mobilePhone.isNullOrBlank()) {
                    Row(
                        modifier = Modifier.padding(top = 4.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Icon(
                            imageVector = Icons.Default.Phone,
                            contentDescription = "Phone",
                            modifier = Modifier.size(16.dp),
                            tint = MaterialTheme.colorScheme.primary
                        )
                        Spacer(modifier = Modifier.width(4.dp))
                        Text(
                            text = contact.mobilePhone ?: contact.phone ?: "",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                }
            }
            
            // Source indicator
            Column(
                horizontalAlignment = Alignment.End
            ) {
                contact.source?.let { source ->
                    Surface(
                        color = when (source) {
                            "converted" -> MaterialTheme.colorScheme.secondaryContainer
                            "qr_scan" -> MaterialTheme.colorScheme.tertiaryContainer
                            else -> MaterialTheme.colorScheme.surfaceVariant
                        },
                        shape = MaterialTheme.shapes.small
                    ) {
                        Text(
                            text = source.replaceFirstChar { it.uppercaseChar() },
                            style = MaterialTheme.typography.labelSmall,
                            color = when (source) {
                                "converted" -> MaterialTheme.colorScheme.onSecondaryContainer
                                "qr_scan" -> MaterialTheme.colorScheme.onTertiaryContainer
                                else -> MaterialTheme.colorScheme.onSurfaceVariant
                            },
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                        )
                    }
                }
            }
        }
    }
}

@Composable
fun EmptyState(
    hasSearchText: Boolean
) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Icon(
            imageVector = Icons.Default.Person,
            contentDescription = "No Contacts",
            modifier = Modifier.size(64.dp),
            tint = MaterialTheme.colorScheme.onSurfaceVariant
        )
        
        Spacer(modifier = Modifier.height(16.dp))
        
        Text(
            text = if (hasSearchText) "No contacts found" else "No Contacts",
            style = MaterialTheme.typography.titleLarge,
            fontWeight = FontWeight.Bold
        )
        
        Spacer(modifier = Modifier.height(8.dp))
        
        Text(
            text = if (hasSearchText) {
                "Try adjusting your search"
            } else {
                "Your contacts will appear here after syncing"
            },
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
    }
}
