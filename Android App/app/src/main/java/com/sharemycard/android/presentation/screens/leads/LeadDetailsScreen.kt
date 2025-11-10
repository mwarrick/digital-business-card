package com.sharemycard.android.presentation.screens.leads

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
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.sharemycard.android.presentation.viewmodel.LeadDetailsViewModel

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun LeadDetailsScreen(
    leadId: String,
    onNavigateBack: () -> Unit = {},
    viewModel: LeadDetailsViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()
    val lead = uiState.lead
    
    LaunchedEffect(leadId) {
        viewModel.loadLead(leadId)
    }
    
    LaunchedEffect(uiState.shouldNavigateBack) {
        if (uiState.shouldNavigateBack) {
            onNavigateBack()
        }
    }
    
    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Lead Details") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
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
            lead == null -> {
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
                            text = uiState.errorMessage ?: "Lead not found",
                            style = MaterialTheme.typography.bodyLarge,
                            color = MaterialTheme.colorScheme.error
                        )
                    }
                }
            }
            else -> {
                Column(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(paddingValues)
                        .verticalScroll(rememberScrollState())
                        .padding(16.dp),
                    verticalArrangement = Arrangement.spacedBy(16.dp)
                ) {
                    // Header Card
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        colors = CardDefaults.cardColors(
                            containerColor = MaterialTheme.colorScheme.primaryContainer
                        )
                    ) {
                        Column(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(16.dp),
                            horizontalAlignment = Alignment.CenterHorizontally
                        ) {
                            Text(
                                text = lead.displayName,
                                style = MaterialTheme.typography.headlineMedium,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.onPrimaryContainer
                            )
                            
                            if (!lead.organizationName.isNullOrBlank()) {
                                Text(
                                    text = lead.organizationName ?: "",
                                    style = MaterialTheme.typography.titleMedium,
                                    color = MaterialTheme.colorScheme.onPrimaryContainer,
                                    modifier = Modifier.padding(top = 4.dp)
                                )
                            }
                            
                            if (!lead.jobTitle.isNullOrBlank()) {
                                Text(
                                    text = lead.jobTitle ?: "",
                                    style = MaterialTheme.typography.bodyLarge,
                                    color = MaterialTheme.colorScheme.onPrimaryContainer
                                )
                            }
                        }
                    }
                    
                    // Status Badge
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        if (lead.isConverted) {
                            Surface(
                                color = MaterialTheme.colorScheme.secondaryContainer,
                                shape = MaterialTheme.shapes.small,
                                modifier = Modifier.weight(1f)
                            ) {
                                Row(
                                    modifier = Modifier.padding(12.dp),
                                    verticalAlignment = Alignment.CenterVertically,
                                    horizontalArrangement = Arrangement.Center
                                ) {
                                    Icon(
                                        imageVector = Icons.Default.CheckCircle,
                                        contentDescription = "Converted",
                                        modifier = Modifier.size(20.dp),
                                        tint = MaterialTheme.colorScheme.onSecondaryContainer
                                    )
                                    Spacer(modifier = Modifier.width(8.dp))
                                    Text(
                                        text = "Converted to Contact",
                                        style = MaterialTheme.typography.bodyMedium,
                                        color = MaterialTheme.colorScheme.onSecondaryContainer
                                    )
                                }
                            }
                        } else {
                            Surface(
                                color = MaterialTheme.colorScheme.primaryContainer,
                                shape = MaterialTheme.shapes.small,
                                modifier = Modifier.weight(1f)
                            ) {
                                Text(
                                    text = "New Lead",
                                    style = MaterialTheme.typography.bodyMedium,
                                    color = MaterialTheme.colorScheme.onPrimaryContainer,
                                    modifier = Modifier.padding(12.dp),
                                    textAlign = androidx.compose.ui.text.style.TextAlign.Center
                                )
                            }
                        }
                    }
                    
                    // Source Information
                    if (!lead.cardDisplayName.isNullOrBlank() && lead.cardDisplayName != "Unknown Card") {
                        SectionCard(title = "Source") {
                            InfoRow(label = "From", value = lead.cardDisplayName)
                            if (!lead.qrTitle.isNullOrBlank()) {
                                InfoRow(label = "QR Code", value = lead.qrTitle ?: "")
                            }
                        }
                    }
                    
                    // Contact Information Section
                    SectionCard(title = "Contact Information") {
                        if (!lead.emailPrimary.isNullOrBlank()) {
                            ContactRow(
                                icon = Icons.Default.Email,
                                label = "Email",
                                value = lead.emailPrimary ?: "",
                                onClick = { /* TODO: Open email */ }
                            )
                        }
                        
                        if (!lead.workPhone.isNullOrBlank()) {
                            ContactRow(
                                icon = Icons.Default.Phone,
                                label = "Work Phone",
                                value = lead.workPhone ?: "",
                                onClick = { /* TODO: Make phone call */ }
                            )
                        }
                        
                        if (!lead.mobilePhone.isNullOrBlank()) {
                            ContactRow(
                                icon = Icons.Default.Phone,
                                label = "Mobile Phone",
                                value = lead.mobilePhone ?: "",
                                onClick = { /* TODO: Make phone call */ }
                            )
                        }
                    }
                    
                    // Professional Information Section
                    if (!lead.organizationName.isNullOrBlank() || !lead.jobTitle.isNullOrBlank()) {
                        SectionCard(title = "Professional Information") {
                            if (!lead.organizationName.isNullOrBlank()) {
                                InfoRow(label = "Company", value = lead.organizationName ?: "")
                            }
                            if (!lead.jobTitle.isNullOrBlank()) {
                                InfoRow(label = "Job Title", value = lead.jobTitle ?: "")
                            }
                        }
                    }
                    
                    // Address Section
                    if (!lead.streetAddress.isNullOrBlank() || 
                        !lead.city.isNullOrBlank() || 
                        !lead.state.isNullOrBlank() ||
                        !lead.zipCode.isNullOrBlank() ||
                        !lead.country.isNullOrBlank()) {
                        SectionCard(title = "Address") {
                            if (!lead.streetAddress.isNullOrBlank()) {
                                InfoRow(label = "Street", value = lead.streetAddress ?: "")
                            }
                            if (!lead.city.isNullOrBlank()) {
                                InfoRow(label = "City", value = lead.city ?: "")
                            }
                            if (!lead.state.isNullOrBlank()) {
                                InfoRow(label = "State", value = lead.state ?: "")
                            }
                            if (!lead.zipCode.isNullOrBlank()) {
                                InfoRow(label = "ZIP Code", value = lead.zipCode ?: "")
                            }
                            if (!lead.country.isNullOrBlank()) {
                                InfoRow(label = "Country", value = lead.country ?: "")
                            }
                        }
                    }
                    
                    // Additional Information Section
                    if (!lead.websiteUrl.isNullOrBlank() || 
                        !lead.commentsFromLead.isNullOrBlank()) {
                        SectionCard(title = "Additional Information") {
                            if (!lead.websiteUrl.isNullOrBlank()) {
                                ContactRow(
                                    icon = Icons.Default.Language,
                                    label = "Website",
                                    value = lead.websiteUrl ?: "",
                                    onClick = { /* TODO: Open website */ }
                                )
                            }
                            if (!lead.commentsFromLead.isNullOrBlank()) {
                                InfoRow(label = "Message", value = lead.commentsFromLead ?: "")
                            }
                            if (!lead.formattedDate.isNullOrBlank()) {
                                InfoRow(label = "Received", value = lead.formattedDate)
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
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Text(
                text = title,
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.primary
            )
            content()
        }
    }
}

@Composable
fun ContactRow(
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    label: String,
    value: String,
    onClick: () -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 4.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Icon(
            imageVector = icon,
            contentDescription = label,
            modifier = Modifier.size(20.dp),
            tint = MaterialTheme.colorScheme.primary
        )
        Spacer(modifier = Modifier.width(12.dp))
        Column(modifier = Modifier.weight(1f)) {
            Text(
                text = label,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
            TextButton(
                onClick = onClick,
                contentPadding = PaddingValues(0.dp),
                modifier = Modifier.padding(0.dp)
            ) {
                Text(
                    text = value,
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.primary
                )
            }
        }
    }
}

@Composable
fun InfoRow(
    label: String,
    value: String
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 4.dp),
        horizontalArrangement = Arrangement.SpaceBetween
    ) {
        Text(
            text = label,
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
        Text(
            text = value,
            style = MaterialTheme.typography.bodyMedium,
            fontWeight = FontWeight.Medium,
            modifier = Modifier
                .weight(1f)
                .padding(start = 16.dp)
        )
    }
}

