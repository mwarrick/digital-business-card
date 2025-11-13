package com.sharemycard.android.presentation.screens.leads

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
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
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
    
    // Show success message when conversion completes
    LaunchedEffect(uiState.conversionSuccess) {
        if (uiState.conversionSuccess) {
            // The lead will be reloaded and show "Converted to Contact" banner
            // Reset the success flag after a delay
            kotlinx.coroutines.delay(2000)
            // Note: The state will be reset when the lead is reloaded
        }
    }
    
    // Show error message
    val snackbarHostState = remember { SnackbarHostState() }
    LaunchedEffect(uiState.errorMessage) {
        if (uiState.errorMessage != null && !uiState.isConverting) {
            snackbarHostState.showSnackbar(
                message = uiState.errorMessage ?: "An error occurred",
                duration = SnackbarDuration.Long
            )
        }
    }
    
    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) },
        topBar = {
            TopAppBar(
                title = { }, // Empty title to match iOS
                navigationIcon = {
                    TextButton(onClick = onNavigateBack) {
                        Text("Close", style = MaterialTheme.typography.bodyLarge)
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
                val context = LocalContext.current
                // Build full address string
                val addressParts = listOfNotNull(
                    lead.streetAddress,
                    lead.city,
                    lead.state,
                    lead.zipCode,
                    lead.country
                ).filter { !it.isNullOrBlank() }
                val fullAddress = addressParts.joinToString(", ")
                
                Column(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(paddingValues)
                        .verticalScroll(rememberScrollState()),
                    verticalArrangement = Arrangement.spacedBy(0.dp)
                ) {
                    // Main Card Section
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = MaterialTheme.shapes.large
                    ) {
                        Column(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(16.dp)
                        ) {
                            // "Converted to Contact" Banner
                            if (lead.isConverted) {
                                Surface(
                                    color = MaterialTheme.colorScheme.secondaryContainer,
                                    shape = MaterialTheme.shapes.medium,
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .padding(bottom = 16.dp)
                                ) {
                                    Row(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .padding(16.dp),
                                        horizontalArrangement = Arrangement.spacedBy(8.dp),
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        Icon(
                                            imageVector = Icons.Default.CheckCircle,
                                            contentDescription = "Converted",
                                            tint = MaterialTheme.colorScheme.onSecondaryContainer,
                                            modifier = Modifier.size(20.dp)
                                        )
                                        Text(
                                            text = "Converted to Contact",
                                            style = MaterialTheme.typography.bodyMedium,
                                            fontWeight = FontWeight.Medium,
                                            color = MaterialTheme.colorScheme.onSecondaryContainer
                                        )
                                    }
                                }
                            }
                            
                            // Name and Title
                            Text(
                                text = lead.displayName,
                                style = MaterialTheme.typography.headlineSmall,
                                fontWeight = FontWeight.Bold
                            )
                            
                            if (!lead.jobTitle.isNullOrBlank()) {
                                Text(
                                    text = lead.jobTitle ?: "",
                                    style = MaterialTheme.typography.titleMedium,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                                    modifier = Modifier.padding(top = 4.dp)
                                )
                            }
                            
                            if (!lead.organizationName.isNullOrBlank()) {
                                Text(
                                    text = lead.organizationName ?: "",
                                    style = MaterialTheme.typography.bodyLarge,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                            }
                            
                            Spacer(modifier = Modifier.height(24.dp))
                            
                            // Basic Information Section
                            Text(
                                text = "Basic Information",
                                style = MaterialTheme.typography.titleLarge,
                                fontWeight = FontWeight.Bold,
                                modifier = Modifier.padding(bottom = 12.dp)
                            )
                            
                            InfoCard(
                                icon = Icons.Default.Person,
                                iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                title = "First Name",
                                value = lead.firstName.ifBlank { "Not provided" }
                            )
                            
                            InfoCard(
                                icon = Icons.Default.Person,
                                iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                title = "Last Name",
                                value = lead.lastName.ifBlank { "Not provided" }
                            )
                            
                            // Email
                            if (!lead.emailPrimary.isNullOrBlank()) {
                                ContactActionCard(
                                    icon = Icons.Default.Email,
                                    iconColor = androidx.compose.ui.graphics.Color(0xFF2196F3), // Blue
                                    title = "Email",
                                    value = lead.emailPrimary ?: "",
                                    onClick = {
                                        val intent = Intent(Intent.ACTION_SENDTO).apply {
                                            data = Uri.parse("mailto:${lead.emailPrimary}")
                                        }
                                        context.startActivity(intent)
                                    }
                                )
                            } else {
                                InfoCard(
                                    icon = Icons.Default.Email,
                                    iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                    title = "Email",
                                    value = "Not provided"
                                )
                            }
                            
                            // Work Phone
                            if (!lead.workPhone.isNullOrBlank()) {
                                ContactActionCard(
                                    icon = Icons.Default.Phone,
                                    iconColor = androidx.compose.ui.graphics.Color(0xFF4CAF50), // Green
                                    title = "Work Phone",
                                    value = lead.workPhone ?: "",
                                    onClick = {
                                        val intent = Intent(Intent.ACTION_DIAL).apply {
                                            data = Uri.parse("tel:${lead.workPhone}")
                                        }
                                        context.startActivity(intent)
                                    }
                                )
                            } else {
                                InfoCard(
                                    icon = Icons.Default.Phone,
                                    iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                    title = "Work Phone",
                                    value = "Not provided"
                                )
                            }
                            
                            // Mobile Phone
                            if (!lead.mobilePhone.isNullOrBlank()) {
                                ContactActionCard(
                                    icon = Icons.Default.Phone,
                                    iconColor = androidx.compose.ui.graphics.Color(0xFF4CAF50), // Green
                                    title = "Mobile Phone",
                                    value = lead.mobilePhone ?: "",
                                    onClick = {
                                        val intent = Intent(Intent.ACTION_DIAL).apply {
                                            data = Uri.parse("tel:${lead.mobilePhone}")
                                        }
                                        context.startActivity(intent)
                                    }
                                )
                            } else {
                                InfoCard(
                                    icon = Icons.Default.Phone,
                                    iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                    title = "Mobile Phone",
                                    value = "Not provided"
                                )
                            }
                            
                            Spacer(modifier = Modifier.height(24.dp))
                            
                            // Professional Information Section
                            Text(
                                text = "Professional Information",
                                style = MaterialTheme.typography.titleLarge,
                                fontWeight = FontWeight.Bold,
                                modifier = Modifier.padding(bottom = 12.dp)
                            )
                            
                            InfoCard(
                                icon = Icons.Default.Badge,
                                iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                title = "Company",
                                value = lead.organizationName?.ifBlank { "Not provided" } ?: "Not provided"
                            )
                            
                            InfoCard(
                                icon = Icons.Default.Info,
                                iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                title = "Job Title",
                                value = lead.jobTitle?.ifBlank { "Not provided" } ?: "Not provided"
                            )
                            
                            Spacer(modifier = Modifier.height(24.dp))
                            
                            // Address Section
                            Text(
                                text = "Address",
                                style = MaterialTheme.typography.titleLarge,
                                fontWeight = FontWeight.Bold,
                                modifier = Modifier.padding(bottom = 12.dp)
                            )
                            
                            InfoCard(
                                icon = Icons.Default.LocationOn,
                                iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                title = "Street Address",
                                value = lead.streetAddress?.ifBlank { "Not provided" } ?: "Not provided"
                            )
                            
                            InfoCard(
                                icon = Icons.Default.LocationOn,
                                iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                title = "City",
                                value = lead.city?.ifBlank { "Not provided" } ?: "Not provided"
                            )
                            
                            InfoCard(
                                icon = Icons.Default.LocationOn,
                                iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                title = "State",
                                value = lead.state?.ifBlank { "Not provided" } ?: "Not provided"
                            )
                            
                            InfoCard(
                                icon = Icons.Default.LocationOn,
                                iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                title = "ZIP Code",
                                value = lead.zipCode?.ifBlank { "Not provided" } ?: "Not provided"
                            )
                            
                            InfoCard(
                                icon = Icons.Default.Public,
                                iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                title = "Country",
                                value = lead.country?.ifBlank { "Not provided" } ?: "Not provided"
                            )
                            
                            // Address action card if address exists
                            if (fullAddress.isNotEmpty()) {
                                Spacer(modifier = Modifier.height(8.dp))
                                ContactActionCard(
                                    icon = Icons.Default.LocationOn,
                                    iconColor = androidx.compose.ui.graphics.Color(0xFFFF9800), // Orange
                                    title = "Get Directions",
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
                            
                            Spacer(modifier = Modifier.height(24.dp))
                            
                            // Additional Information Section
                            Text(
                                text = "Additional Information",
                                style = MaterialTheme.typography.titleLarge,
                                fontWeight = FontWeight.Bold,
                                modifier = Modifier.padding(bottom = 12.dp)
                            )
                            
                            // Website
                            if (!lead.websiteUrl.isNullOrBlank()) {
                                ContactActionCard(
                                    icon = Icons.Default.Language,
                                    iconColor = androidx.compose.ui.graphics.Color(0xFF9C27B0), // Purple
                                    title = "Website",
                                    value = lead.websiteUrl ?: "",
                                    onClick = {
                                        val url = if (!lead.websiteUrl!!.startsWith("http://") && !lead.websiteUrl!!.startsWith("https://")) {
                                            "https://${lead.websiteUrl}"
                                        } else {
                                            lead.websiteUrl
                                        }
                                        val intent = Intent(Intent.ACTION_VIEW).apply {
                                            data = Uri.parse(url)
                                        }
                                        context.startActivity(intent)
                                    }
                                )
                            } else {
                                InfoCard(
                                    icon = Icons.Default.Language,
                                    iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                    title = "Website",
                                    value = "Not provided"
                                )
                            }
                            
                            // Comments/Message
                            InfoCard(
                                icon = Icons.Default.Description,
                                iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                title = "Comments",
                                value = lead.commentsFromLead?.ifBlank { "Not provided" } ?: "Not provided"
                            )
                            
                            // Birthdate
                            InfoCard(
                                icon = Icons.Default.CalendarToday,
                                iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                title = "Birthdate",
                                value = lead.birthdate?.ifBlank { "Not provided" } ?: "Not provided"
                            )
                            
                            Spacer(modifier = Modifier.height(24.dp))
                            
                            // Source Information Section
                            Text(
                                text = "Source Information",
                                style = MaterialTheme.typography.titleLarge,
                                fontWeight = FontWeight.Bold,
                                modifier = Modifier.padding(bottom = 12.dp)
                            )
                            
                            InfoCard(
                                icon = Icons.Default.Info,
                                iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                title = "From Business Card",
                                value = lead.cardDisplayName.ifBlank { "Not provided" }
                            )
                            
                            if (!lead.qrTitle.isNullOrBlank()) {
                                InfoCard(
                                    icon = Icons.Default.QrCodeScanner,
                                    iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                    title = "QR Code",
                                    value = lead.qrTitle ?: "Not provided"
                                )
                            }
                            
                            // Received Date
                            InfoCard(
                                icon = Icons.Default.CalendarToday,
                                iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                title = "Captured Date",
                                value = if (!lead.formattedDate.isNullOrBlank()) {
                                    lead.formattedDate
                                } else if (!lead.relativeDate.isNullOrBlank()) {
                                    lead.relativeDate
                                } else {
                                    "Not provided"
                                }
                            )
                            
                            // "Convert to Contact" Button at the bottom (only show if not already converted)
                            if (!lead.isConverted) {
                                Spacer(modifier = Modifier.height(32.dp))
                                Button(
                                    onClick = { viewModel.convertToContact() },
                                    enabled = !uiState.isConverting,
                                    modifier = Modifier.fillMaxWidth()
                                ) {
                                    if (uiState.isConverting) {
                                        CircularProgressIndicator(
                                            modifier = Modifier.size(20.dp),
                                            color = MaterialTheme.colorScheme.onPrimary
                                        )
                                        Spacer(modifier = Modifier.width(8.dp))
                                        Text("Converting...")
                                    } else {
                                        Icon(
                                            imageVector = Icons.Default.PersonAdd,
                                            contentDescription = "Convert to Contact",
                                            modifier = Modifier.size(20.dp)
                                        )
                                        Spacer(modifier = Modifier.width(8.dp))
                                        Text("Convert to Contact")
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun ContactActionCard(
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    iconColor: androidx.compose.ui.graphics.Color,
    title: String,
    value: String,
    onClick: () -> Unit
) {
    Card(
        onClick = onClick,
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 4.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            horizontalArrangement = Arrangement.spacedBy(12.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Icon(
                imageVector = icon,
                contentDescription = title,
                modifier = Modifier.size(24.dp),
                tint = iconColor
            )
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = title,
                    style = MaterialTheme.typography.titleSmall,
                    fontWeight = FontWeight.Medium
                )
                Text(
                    text = value,
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    maxLines = 2
                )
            }
            Icon(
                imageVector = Icons.Default.ChevronRight,
                contentDescription = "Action",
                tint = MaterialTheme.colorScheme.onSurfaceVariant,
                modifier = Modifier.size(20.dp)
            )
        }
    }
}

@Composable
fun InfoCard(
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    iconColor: androidx.compose.ui.graphics.Color,
    title: String,
    value: String
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 4.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            horizontalArrangement = Arrangement.spacedBy(12.dp),
            verticalAlignment = Alignment.Top
        ) {
            Icon(
                imageVector = icon,
                contentDescription = title,
                modifier = Modifier.size(24.dp),
                tint = iconColor
            )
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = title,
                    style = MaterialTheme.typography.titleSmall,
                    fontWeight = FontWeight.Medium
                )
                Text(
                    text = value,
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    maxLines = 2
                )
            }
        }
    }
}

