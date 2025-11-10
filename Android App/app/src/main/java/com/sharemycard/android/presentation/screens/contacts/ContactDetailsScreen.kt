package com.sharemycard.android.presentation.screens.contacts

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.sharemycard.android.presentation.viewmodel.ContactDetailsViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ContactDetailsScreen(
    contactId: String,
    onNavigateBack: () -> Unit = {},
    onNavigateToEdit: (String) -> Unit = {},
    viewModel: ContactDetailsViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()
    val contact = uiState.contact
    
    LaunchedEffect(contactId) {
        viewModel.loadContact(contactId)
    }
    
    LaunchedEffect(uiState.shouldNavigateBack) {
        if (uiState.shouldNavigateBack) {
            onNavigateBack()
        }
    }
    
    Scaffold(
        topBar = {
            TopAppBar(
                title = { }, // Empty title to match iOS
                navigationIcon = {
                    TextButton(onClick = onNavigateBack) {
                        Text("Close", style = MaterialTheme.typography.bodyLarge)
                    }
                },
                actions = {
                    if (contact != null) {
                        IconButton(onClick = { /* TODO: Export contact */ }) {
                            Icon(Icons.Default.FileDownload, contentDescription = "Export")
                        }
                        IconButton(onClick = { onNavigateToEdit(contactId) }) {
                            Icon(Icons.Default.Edit, contentDescription = "Edit")
                        }
                        IconButton(onClick = { viewModel.deleteContact() }) {
                            Icon(Icons.Default.Delete, contentDescription = "Delete")
                        }
                    }
                }
            )
        }
    ) { paddingValues ->
        when {
            uiState.isLoading -> {
                Box(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(paddingValues),
                    contentAlignment = Alignment.Center
                ) {
                    CircularProgressIndicator()
                }
            }
            contact == null -> {
                Box(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(paddingValues),
                    contentAlignment = Alignment.Center
                ) {
                    Column(
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Icon(
                            imageVector = Icons.Default.Error,
                            contentDescription = "Error",
                            modifier = Modifier.size(64.dp),
                            tint = MaterialTheme.colorScheme.error
                        )
                        Spacer(modifier = Modifier.height(16.dp))
                        Text(
                            text = uiState.errorMessage ?: "Contact not found",
                            style = MaterialTheme.typography.bodyLarge,
                            color = MaterialTheme.colorScheme.error
                        )
                    }
                }
            }
            else -> {
                val context = LocalContext.current
                // Build full address string
                val addressParts = listOfNotNull(
                    contact.address,
                    contact.city,
                    contact.state,
                    contact.zipCode,
                    contact.country
                ).filter { !it.isNullOrBlank() }
                val fullAddress = addressParts.joinToString(", ")
                
                Column(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(paddingValues)
                        .verticalScroll(rememberScrollState())
                        .padding(16.dp),
                    verticalArrangement = Arrangement.spacedBy(16.dp)
                ) {
                    // "Converted from Lead" Banner
                    if (contact.source == "converted") {
                        Surface(
                            color = MaterialTheme.colorScheme.primaryContainer,
                            shape = MaterialTheme.shapes.medium,
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(16.dp),
                                horizontalArrangement = Arrangement.spacedBy(8.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Icon(
                                    imageVector = Icons.Default.PersonAdd,
                                    contentDescription = "Converted",
                                    tint = MaterialTheme.colorScheme.primary,
                                    modifier = Modifier.size(20.dp)
                                )
                                Text(
                                    text = "Converted from Lead",
                                    style = MaterialTheme.typography.bodyMedium,
                                    fontWeight = FontWeight.Medium,
                                    color = MaterialTheme.colorScheme.onPrimaryContainer
                                )
                            }
                        }
                    }
                    
                    // Basic Information Section
                    SectionCard(title = "Basic Information") {
                        InfoRow(label = "First Name", value = contact.firstName)
                        InfoRow(label = "Last Name", value = contact.lastName)
                        
                        if (!contact.email.isNullOrBlank()) {
                            ClickableInfoRow(
                                label = "Email",
                                value = contact.email ?: "",
                                onClick = {
                                    val intent = Intent(Intent.ACTION_SENDTO).apply {
                                        data = Uri.parse("mailto:${contact.email}")
                                    }
                                    context.startActivity(intent)
                                }
                            )
                        }
                        
                        if (!contact.phone.isNullOrBlank()) {
                            ClickableInfoRow(
                                label = "Work Phone",
                                value = contact.phone ?: "",
                                onClick = {
                                    val intent = Intent(Intent.ACTION_DIAL).apply {
                                        data = Uri.parse("tel:${contact.phone}")
                                    }
                                    context.startActivity(intent)
                                }
                            )
                        }
                        
                        if (!contact.mobilePhone.isNullOrBlank()) {
                            ClickableInfoRow(
                                label = "Mobile Phone",
                                value = contact.mobilePhone ?: "",
                                onClick = {
                                    val intent = Intent(Intent.ACTION_DIAL).apply {
                                        data = Uri.parse("tel:${contact.mobilePhone}")
                                    }
                                    context.startActivity(intent)
                                }
                            )
                        } else {
                            InfoRow(label = "Mobile Phone", value = "Not provided")
                        }
                    }
                    
                    // Professional Information Section
                    if (!contact.company.isNullOrBlank() || !contact.jobTitle.isNullOrBlank()) {
                        SectionCard(title = "Professional Information") {
                            if (!contact.company.isNullOrBlank()) {
                                InfoRow(label = "Company", value = contact.company ?: "")
                            }
                            if (!contact.jobTitle.isNullOrBlank()) {
                                InfoRow(label = "Job Title", value = contact.jobTitle ?: "")
                            }
                        }
                    }
                    
                    // Address Section
                    if (fullAddress.isNotEmpty()) {
                        SectionCard(title = "Address") {
                            // Combined clickable address that links to Google Maps
                            ClickableInfoRow(
                                label = "Address",
                                value = fullAddress,
                                onClick = {
                                    val query = Uri.encode(fullAddress)
                                    val intent = Intent(Intent.ACTION_VIEW).apply {
                                        data = Uri.parse("geo:0,0?q=$query")
                                    }
                                    try {
                                        context.startActivity(intent)
                                    } catch (e: Exception) {
                                        // Fallback to web maps
                                        val webIntent = Intent(Intent.ACTION_VIEW).apply {
                                            data = Uri.parse("https://www.google.com/maps/search/?api=1&query=$query")
                                        }
                                        context.startActivity(webIntent)
                                    }
                                }
                            )
                        }
                    }
                    
                    // Additional Information Section
                    val hasAdditionalInfo = !contact.website.isNullOrBlank() || 
                        !contact.notes.isNullOrBlank() ||
                        !contact.birthdate.isNullOrBlank() ||
                        !contact.source.isNullOrBlank()
                    
                    if (hasAdditionalInfo) {
                        SectionCard(title = "Additional Information") {
                            if (!contact.website.isNullOrBlank()) {
                                ClickableInfoRow(
                                    label = "Website",
                                    value = contact.website ?: "",
                                    onClick = {
                                        val url = if (!contact.website!!.startsWith("http://") && !contact.website!!.startsWith("https://")) {
                                            "https://${contact.website}"
                                        } else {
                                            contact.website
                                        }
                                        val intent = Intent(Intent.ACTION_VIEW).apply {
                                            data = Uri.parse(url)
                                        }
                                        context.startActivity(intent)
                                    }
                                )
                            }
                            
                            if (!contact.notes.isNullOrBlank()) {
                                InfoRow(label = "Notes", value = contact.notes ?: "")
                            } else {
                                InfoRow(label = "Notes", value = "Not provided")
                            }
                            
                            if (!contact.birthdate.isNullOrBlank() && contact.birthdate != "0000-00-00") {
                                InfoRow(label = "Birthdate", value = contact.birthdate ?: "")
                            } else {
                                InfoRow(label = "Birthdate", value = "0000-00-00")
                            }
                            
                            if (!contact.source.isNullOrBlank()) {
                                InfoRow(label = "Source", value = contact.source ?: "")
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun SectionCard(
    title: String,
    content: @Composable ColumnScope.() -> Unit
) {
    Card(
        modifier = Modifier.fillMaxWidth()
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp)
        ) {
            Text(
                text = title,
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.primary,
                modifier = Modifier.padding(bottom = 8.dp)
            )
            Column(
                verticalArrangement = Arrangement.spacedBy(4.dp)
            ) {
                content()
            }
        }
    }
}

@Composable
fun ClickableInfoRow(
    label: String,
    value: String,
    onClick: () -> Unit
) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Text(
            text = label,
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
            modifier = Modifier.weight(1f, fill = false)
        )
        Spacer(modifier = Modifier.width(8.dp))
        TextButton(
            onClick = onClick,
            contentPadding = PaddingValues(0.dp),
            modifier = Modifier.padding(0.dp)
        ) {
            Text(
                text = value,
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.primary,
                fontWeight = FontWeight.Medium
            )
        }
    }
}

@Composable
fun InfoRow(
    label: String,
    value: String
) {
    Row(
        modifier = Modifier.fillMaxWidth()
    ) {
        Text(
            text = label,
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
            modifier = Modifier.weight(1f, fill = false)
        )
        Spacer(modifier = Modifier.width(8.dp))
        Text(
            text = value,
            style = MaterialTheme.typography.bodyMedium,
            fontWeight = FontWeight.Medium
        )
    }
}

