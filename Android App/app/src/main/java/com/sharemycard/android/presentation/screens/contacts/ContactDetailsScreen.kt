package com.sharemycard.android.presentation.screens.contacts

import android.Manifest
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
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.google.accompanist.permissions.ExperimentalPermissionsApi
import com.google.accompanist.permissions.rememberMultiplePermissionsState
import com.sharemycard.android.presentation.viewmodel.ContactDetailsViewModel
import com.sharemycard.android.util.ContactExporter
import kotlinx.coroutines.launch

@OptIn(ExperimentalMaterial3Api::class, ExperimentalPermissionsApi::class)
@Composable
fun ContactDetailsScreen(
    contactId: String,
    onNavigateBack: () -> Unit = {},
    onNavigateToEdit: (String) -> Unit = {},
    viewModel: ContactDetailsViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()
    val contact = uiState.contact
    val context = LocalContext.current
    val coroutineScope = rememberCoroutineScope()
    
    // Permission state for exporting contacts
    val contactsPermissionsState = rememberMultiplePermissionsState(
        permissions = listOf(
            Manifest.permission.WRITE_CONTACTS,
            Manifest.permission.READ_CONTACTS
        )
    )
    
    // Snackbar host state for showing export messages
    val snackbarHostState = remember { SnackbarHostState() }
    
    // Track if user clicked export button (to auto-export after permissions granted)
    var shouldAutoExport by remember { mutableStateOf(false) }
    
    LaunchedEffect(contactId) {
        viewModel.loadContact(contactId)
        shouldAutoExport = false // Reset when contact changes
    }
    
    LaunchedEffect(uiState.shouldNavigateBack) {
        if (uiState.shouldNavigateBack) {
            onNavigateBack()
        }
    }
    
    // Auto-export when permissions are granted after user clicked export button
    LaunchedEffect(contactsPermissionsState.allPermissionsGranted) {
        if (contactsPermissionsState.allPermissionsGranted && contact != null && shouldAutoExport) {
            // User just granted permissions after clicking export
            val success = ContactExporter.exportContact(context, contact)
            val message = if (success) {
                "${contact.fullName} exported to contacts"
            } else {
                "Failed to export contact"
            }
            coroutineScope.launch {
                snackbarHostState.showSnackbar(message)
            }
            shouldAutoExport = false // Reset flag
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
                        IconButton(
                            onClick = {
                                if (contactsPermissionsState.allPermissionsGranted) {
                                    // Permissions already granted, export immediately
                                    val success = ContactExporter.exportContact(context, contact)
                                    val message = if (success) {
                                        "${contact.fullName} exported to contacts"
                                    } else {
                                        "Failed to export contact"
                                    }
                                    coroutineScope.launch {
                                        snackbarHostState.showSnackbar(message)
                                    }
                                } else {
                                    // Request permissions - export will happen after permissions are granted
                                    shouldAutoExport = true
                                    contactsPermissionsState.launchMultiplePermissionRequest()
                                }
                            }
                        ) {
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
        },
        snackbarHost = {
            SnackbarHost(hostState = snackbarHostState)
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
                            // "Converted from Lead" Banner
                            if (contact.source == "converted") {
                                Surface(
                                    color = MaterialTheme.colorScheme.primaryContainer,
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
                            
                            // Name and Title
                            Text(
                                text = "${contact.firstName} ${contact.lastName}".trim(),
                                style = MaterialTheme.typography.headlineSmall,
                                fontWeight = FontWeight.Bold
                            )
                            
                            if (!contact.jobTitle.isNullOrBlank()) {
                                Text(
                                    text = contact.jobTitle,
                                    style = MaterialTheme.typography.titleMedium,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                                    modifier = Modifier.padding(top = 4.dp)
                                )
                            }
                            
                            if (!contact.company.isNullOrBlank()) {
                                Text(
                                    text = contact.company,
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
                                value = contact.firstName.ifBlank { "Not provided" }
                            )
                            
                            InfoCard(
                                icon = Icons.Default.Person,
                                iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                title = "Last Name",
                                value = contact.lastName.ifBlank { "Not provided" }
                            )
                            
                            // Email
                            if (!contact.email.isNullOrBlank()) {
                                ContactActionCard(
                                    icon = Icons.Default.Email,
                                    iconColor = androidx.compose.ui.graphics.Color(0xFF2196F3), // Blue
                                    title = "Email",
                                    value = contact.email,
                                    onClick = {
                                        val intent = Intent(Intent.ACTION_SENDTO).apply {
                                            data = Uri.parse("mailto:${contact.email}")
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
                            if (!contact.phone.isNullOrBlank()) {
                                ContactActionCard(
                                    icon = Icons.Default.Phone,
                                    iconColor = androidx.compose.ui.graphics.Color(0xFF4CAF50), // Green
                                    title = "Work Phone",
                                    value = contact.phone,
                                    onClick = {
                                        val intent = Intent(Intent.ACTION_DIAL).apply {
                                            data = Uri.parse("tel:${contact.phone}")
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
                            if (!contact.mobilePhone.isNullOrBlank()) {
                                ContactActionCard(
                                    icon = Icons.Default.Phone,
                                    iconColor = androidx.compose.ui.graphics.Color(0xFF4CAF50), // Green
                                    title = "Mobile Phone",
                                    value = contact.mobilePhone,
                                    onClick = {
                                        val intent = Intent(Intent.ACTION_DIAL).apply {
                                            data = Uri.parse("tel:${contact.mobilePhone}")
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
                                value = contact.company?.ifBlank { "Not provided" } ?: "Not provided"
                            )
                            
                            InfoCard(
                                icon = Icons.Default.Info,
                                iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                title = "Job Title",
                                value = contact.jobTitle?.ifBlank { "Not provided" } ?: "Not provided"
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
                                value = contact.address?.ifBlank { "Not provided" } ?: "Not provided"
                            )
                            
                            InfoCard(
                                icon = Icons.Default.LocationOn,
                                iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                title = "City",
                                value = contact.city?.ifBlank { "Not provided" } ?: "Not provided"
                            )
                            
                            InfoCard(
                                icon = Icons.Default.LocationOn,
                                iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                title = "State",
                                value = contact.state?.ifBlank { "Not provided" } ?: "Not provided"
                            )
                            
                            InfoCard(
                                icon = Icons.Default.LocationOn,
                                iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                title = "ZIP Code",
                                value = contact.zipCode?.ifBlank { "Not provided" } ?: "Not provided"
                            )
                            
                            InfoCard(
                                icon = Icons.Default.Public,
                                iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                title = "Country",
                                value = contact.country?.ifBlank { "Not provided" } ?: "Not provided"
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
                            val website = contact.website
                            if (!website.isNullOrBlank()) {
                                ContactActionCard(
                                    icon = Icons.Default.Language,
                                    iconColor = androidx.compose.ui.graphics.Color(0xFF9C27B0), // Purple
                                    title = "Website",
                                    value = website,
                                    onClick = {
                                        val url = if (!website.startsWith("http://") && !website.startsWith("https://")) {
                                            "https://$website"
                                        } else {
                                            website
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
                            
                            // Notes
                            InfoCard(
                                icon = Icons.Default.Description,
                                iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                title = "Notes",
                                value = contact.notes?.ifBlank { "Not provided" } ?: "Not provided"
                            )
                            
                            // Birthdate
                            InfoCard(
                                icon = Icons.Default.CalendarToday,
                                iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                title = "Birthdate",
                                value = if (!contact.birthdate.isNullOrBlank() && contact.birthdate != "0000-00-00") {
                                    contact.birthdate
                                } else {
                                    "Not provided"
                                }
                            )
                            
                            // Source (always show, but hide qr_scan on API 25)
                            if (contact.source != "qr_scan") {
                                InfoCard(
                                    icon = Icons.Default.Info,
                                    iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                    title = "Source",
                                    value = contact.source?.ifBlank { "Not provided" } ?: "Not provided"
                                )
                            }
                            
                            Spacer(modifier = Modifier.height(24.dp))
                            
                            // Date Information Section
                            Text(
                                text = "Date Information",
                                style = MaterialTheme.typography.titleLarge,
                                fontWeight = FontWeight.Bold,
                                modifier = Modifier.padding(bottom = 12.dp)
                            )
                            
                            // Created Date
                            InfoCard(
                                icon = Icons.Default.CalendarToday,
                                iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                title = "Created",
                                value = if (!contact.formattedCreatedDate.isNullOrBlank()) {
                                    contact.formattedCreatedDate
                                } else {
                                    "Not provided"
                                }
                            )
                            
                            // Updated Date (only show if contact was updated)
                            if (contact.wasUpdated) {
                                InfoCard(
                                    icon = Icons.Default.CalendarToday,
                                    iconColor = MaterialTheme.colorScheme.onSurfaceVariant,
                                    title = "Updated",
                                    value = if (!contact.formattedUpdatedDate.isNullOrBlank()) {
                                        contact.formattedUpdatedDate
                                    } else {
                                        "Not provided"
                                    }
                                )
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

