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
    
    Scaffold(
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
                            
                            // Contact Information Section
                            Text(
                                text = "Contact Information",
                                style = MaterialTheme.typography.titleLarge,
                                fontWeight = FontWeight.Bold,
                                modifier = Modifier.padding(bottom = 12.dp)
                            )
                            
                            // Email
                            if (!lead.emailPrimary.isNullOrBlank()) {
                                ContactActionCard(
                                    icon = Icons.Default.Email,
                                    iconColor = androidx.compose.ui.graphics.Color(0xFF2196F3), // Blue
                                    title = "Email ${lead.firstName}",
                                    value = lead.emailPrimary ?: "",
                                    onClick = {
                                        val intent = Intent(Intent.ACTION_SENDTO).apply {
                                            data = Uri.parse("mailto:${lead.emailPrimary}")
                                        }
                                        context.startActivity(intent)
                                    }
                                )
                            }
                            
                            // Work Phone
                            if (!lead.workPhone.isNullOrBlank()) {
                                ContactActionCard(
                                    icon = Icons.Default.Phone,
                                    iconColor = androidx.compose.ui.graphics.Color(0xFF4CAF50), // Green
                                    title = "Call Work",
                                    value = lead.workPhone ?: "",
                                    onClick = {
                                        val intent = Intent(Intent.ACTION_DIAL).apply {
                                            data = Uri.parse("tel:${lead.workPhone}")
                                        }
                                        context.startActivity(intent)
                                    }
                                )
                            }
                            
                            // Mobile Phone
                            if (!lead.mobilePhone.isNullOrBlank()) {
                                ContactActionCard(
                                    icon = Icons.Default.Phone,
                                    iconColor = androidx.compose.ui.graphics.Color(0xFF4CAF50), // Green
                                    title = "Call Mobile",
                                    value = lead.mobilePhone ?: "",
                                    onClick = {
                                        val intent = Intent(Intent.ACTION_DIAL).apply {
                                            data = Uri.parse("tel:${lead.mobilePhone}")
                                        }
                                        context.startActivity(intent)
                                    }
                                )
                            }
                            
                            // Website
                            if (!lead.websiteUrl.isNullOrBlank()) {
                                ContactActionCard(
                                    icon = Icons.Default.Language,
                                    iconColor = androidx.compose.ui.graphics.Color(0xFF9C27B0), // Purple
                                    title = "Visit Website",
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
                            }
                            
                            // Address
                            if (fullAddress.isNotEmpty()) {
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
                            
                            // Additional Information Section
                            val hasAdditionalInfo = !lead.commentsFromLead.isNullOrBlank() ||
                                !lead.formattedDate.isNullOrBlank() ||
                                (!lead.cardDisplayName.isNullOrBlank() && lead.cardDisplayName != "Unknown Card")
                            
                            if (hasAdditionalInfo) {
                                Spacer(modifier = Modifier.height(24.dp))
                                
                                Text(
                                    text = "Additional Information",
                                    style = MaterialTheme.typography.titleLarge,
                                    fontWeight = FontWeight.Bold,
                                    modifier = Modifier.padding(bottom = 12.dp)
                                )
                                
                                // Source Information
                                if (!lead.cardDisplayName.isNullOrBlank() && lead.cardDisplayName != "Unknown Card") {
                                    InfoCard(
                                        icon = Icons.Default.Info,
                                        iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                        title = "Source",
                                        value = lead.cardDisplayName
                                    )
                                    
                                    if (!lead.qrTitle.isNullOrBlank()) {
                                        InfoCard(
                                            icon = Icons.Default.QrCodeScanner,
                                            iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                            title = "QR Code",
                                            value = lead.qrTitle ?: ""
                                        )
                                    }
                                }
                                
                                // Message/Comments
                                if (!lead.commentsFromLead.isNullOrBlank()) {
                                    InfoCard(
                                        icon = Icons.Default.Description,
                                        iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                        title = "Message",
                                        value = lead.commentsFromLead ?: ""
                                    )
                                }
                                
                                // Received Date
                                if (!lead.formattedDate.isNullOrBlank()) {
                                    InfoCard(
                                        icon = Icons.Default.CalendarToday,
                                        iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                        title = "Received",
                                        value = lead.formattedDate
                                    )
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

