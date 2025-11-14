package com.sharemycard.android.presentation.screens.cards

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.verticalScroll
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
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.core.content.FileProvider
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import coil.compose.AsyncImage
import com.sharemycard.android.domain.models.BusinessCard
import com.sharemycard.android.presentation.viewmodel.CardDetailsViewModel
import com.sharemycard.android.util.DateParser
import com.sharemycard.android.util.QRCodeGenerator
import java.io.File
import java.text.SimpleDateFormat
import java.util.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun CardDetailsScreen(
    cardId: String,
    onNavigateBack: () -> Unit = {},
    onNavigateToEdit: (String) -> Unit = {},
    viewModel: CardDetailsViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()
    val card = uiState.card
    val context = LocalContext.current
    
    LaunchedEffect(cardId) {
        viewModel.loadCard(cardId)
    }
    
    LaunchedEffect(uiState.shouldNavigateBack) {
        if (uiState.shouldNavigateBack) {
            onNavigateBack()
        }
    }
    
    LaunchedEffect(uiState.duplicatedCardId) {
        uiState.duplicatedCardId?.let { duplicatedId ->
            onNavigateToEdit(duplicatedId)
        }
    }
    
    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        Icon(
                            imageVector = Icons.Default.Layers,
                            contentDescription = "ShareMyCard Logo",
                            modifier = Modifier.size(24.dp),
                            tint = MaterialTheme.colorScheme.primary
                        )
                    }
                },
                navigationIcon = {
                    TextButton(onClick = onNavigateBack) {
                        Text("Done", style = MaterialTheme.typography.bodyLarge)
                    }
                },
                actions = {
                    if (card != null) {
                        IconButton(onClick = { viewModel.duplicateCard() }) {
                            Icon(Icons.Default.ContentCopy, contentDescription = "Duplicate")
                        }
                        IconButton(onClick = { 
                            shareCard(context, card)
                        }) {
                            Icon(Icons.Default.Share, contentDescription = "Share")
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
            card == null -> {
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
                            text = uiState.errorMessage ?: "Card not found",
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
                        .verticalScroll(rememberScrollState()),
                    verticalArrangement = Arrangement.spacedBy(0.dp)
                ) {
                    // Cover Image
                    if (!card.coverGraphicPath.isNullOrBlank()) {
                        val coverUrl = when {
                            card.coverGraphicPath!!.startsWith("http") -> card.coverGraphicPath
                            card.coverGraphicPath!!.startsWith("/api/media/view") -> "https://sharemycard.app${card.coverGraphicPath}"
                            else -> "https://sharemycard.app/api/media/view?file=${card.coverGraphicPath}"
                        }
                        AsyncImage(
                            model = coverUrl,
                            contentDescription = "Cover Image",
                            modifier = Modifier
                                .fillMaxWidth()
                                .height(200.dp),
                            contentScale = ContentScale.Crop
                        )
                    } else {
                        // Placeholder gradient or solid color based on theme
                        val theme = com.sharemycard.android.util.CardThemes.getThemeById(card.theme)
                        val coverColor = if (theme != null) {
                            androidx.compose.ui.graphics.Color(android.graphics.Color.parseColor(theme.primaryColor))
                        } else {
                            MaterialTheme.colorScheme.primaryContainer
                        }
                        Surface(
                            modifier = Modifier
                                .fillMaxWidth()
                                .height(200.dp),
                            color = coverColor
                        ) {}
                    }
                    
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
                            // Profile Photo, Name, Company, Title, Logo Row
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.spacedBy(16.dp),
                                verticalAlignment = Alignment.Top
                            ) {
                                // Profile Photo
                                Box(
                                    modifier = Modifier
                                        .size(80.dp)
                                        .clip(CircleShape)
                                ) {
                                    if (!card.profilePhotoPath.isNullOrBlank()) {
                                        val profileUrl = when {
                                            card.profilePhotoPath!!.startsWith("http") -> card.profilePhotoPath
                                            card.profilePhotoPath!!.startsWith("/api/media/view") -> "https://sharemycard.app${card.profilePhotoPath}"
                                            else -> "https://sharemycard.app/api/media/view?file=${card.profilePhotoPath}"
                                        }
                                        AsyncImage(
                                            model = profileUrl,
                                            contentDescription = "Profile Photo",
                                            modifier = Modifier.fillMaxSize(),
                                            contentScale = ContentScale.Crop
                                        )
                                    } else {
                                        Surface(
                                            modifier = Modifier.fillMaxSize(),
                                            color = MaterialTheme.colorScheme.surfaceVariant,
                                            shape = CircleShape
                                        ) {
                                            Icon(
                                                imageVector = Icons.Default.Person,
                                                contentDescription = "No Photo",
                                                modifier = Modifier
                                                    .fillMaxSize()
                                                    .padding(20.dp),
                                                tint = MaterialTheme.colorScheme.onSurfaceVariant
                                            )
                                        }
                                    }
                                }
                                
                                // Name, Title, Company
                                Column(
                                    modifier = Modifier.weight(1f)
                                ) {
                                    Row(
                                        verticalAlignment = Alignment.CenterVertically,
                                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                                    ) {
                                        Text(
                                            text = card.fullName,
                                            style = MaterialTheme.typography.headlineSmall,
                                            fontWeight = FontWeight.Bold
                                        )
                                        // Inactive indicator
                                        if (!card.isActive) {
                                            Surface(
                                                color = MaterialTheme.colorScheme.surfaceVariant,
                                                shape = MaterialTheme.shapes.small
                                            ) {
                                                Text(
                                                    text = "Inactive",
                                                    style = MaterialTheme.typography.labelSmall,
                                                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                                                    modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                                                )
                                            }
                                        }
                                    }
                                    
                                    if (!card.jobTitle.isNullOrBlank()) {
                                        Text(
                                            text = card.jobTitle ?: "",
                                            style = MaterialTheme.typography.titleMedium,
                                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                                            modifier = Modifier.padding(top = 4.dp)
                                        )
                                    }
                                    
                                    if (!card.companyName.isNullOrBlank()) {
                                        Text(
                                            text = card.companyName ?: "",
                                            style = MaterialTheme.typography.bodyLarge,
                                            color = MaterialTheme.colorScheme.onSurfaceVariant
                                        )
                                    }
                                }
                                
                                // Company Logo
                                if (!card.companyLogoPath.isNullOrBlank()) {
                                    val logoUrl = when {
                                        card.companyLogoPath!!.startsWith("http") -> card.companyLogoPath
                                        card.companyLogoPath!!.startsWith("/api/media/view") -> "https://sharemycard.app${card.companyLogoPath}"
                                        else -> "https://sharemycard.app/api/media/view?file=${card.companyLogoPath}"
                                    }
                                    Box(
                                        modifier = Modifier
                                            .size(60.dp)
                                            .clip(MaterialTheme.shapes.medium)
                                    ) {
                                        AsyncImage(
                                            model = logoUrl,
                                            contentDescription = "Company Logo",
                                            modifier = Modifier.fillMaxSize(),
                                            contentScale = ContentScale.Crop
                                        )
                                    }
                                }
                            }
                            
                            Spacer(modifier = Modifier.height(24.dp))
                            
                            // Calculate primary website once for reuse
                            val primaryWebsite = card.websiteLinks.firstOrNull { it.isPrimary } 
                                ?: card.websiteLinks.firstOrNull()
                            
                            // Contact Information Section
                            Text(
                                text = "Contact Information",
                                style = MaterialTheme.typography.titleLarge,
                                fontWeight = FontWeight.Bold,
                                modifier = Modifier.padding(bottom = 12.dp)
                            )
                            
                            // Primary Phone
                            if (card.phoneNumber.isNotBlank()) {
                                ContactActionCard(
                                    icon = Icons.Default.Phone,
                                    iconColor = androidx.compose.ui.graphics.Color(0xFF4CAF50), // Green
                                    title = "Call ${card.firstName}",
                                    value = card.phoneNumber,
                                    onClick = {
                                        val intent = Intent(Intent.ACTION_DIAL).apply {
                                            data = Uri.parse("tel:${card.phoneNumber}")
                                        }
                                        context.startActivity(intent)
                                    }
                                )
                            }
                            
                            // Additional Phones
                            card.additionalPhones.forEach { phone ->
                                ContactActionCard(
                                    icon = Icons.Default.Phone,
                                    iconColor = androidx.compose.ui.graphics.Color(0xFF4CAF50), // Green
                                    title = "Call ${phone.label ?: phone.type.name}",
                                    value = phone.phoneNumber,
                                    onClick = {
                                        val intent = Intent(Intent.ACTION_DIAL).apply {
                                            data = Uri.parse("tel:${phone.phoneNumber}")
                                        }
                                        context.startActivity(intent)
                                    }
                                )
                            }
                            
                            // Primary Email
                            card.primaryEmail?.let { email ->
                                ContactActionCard(
                                    icon = Icons.Default.Email,
                                    iconColor = androidx.compose.ui.graphics.Color(0xFF2196F3), // Blue
                                    title = "Email ${card.firstName}",
                                    value = email.email,
                                    onClick = {
                                        val intent = Intent(Intent.ACTION_SENDTO).apply {
                                            data = Uri.parse("mailto:${email.email}")
                                        }
                                        context.startActivity(intent)
                                    }
                                )
                            }
                            
                            // Primary Website
                            primaryWebsite?.let { website ->
                                ContactActionCard(
                                    icon = Icons.Default.Language,
                                    iconColor = androidx.compose.ui.graphics.Color(0xFF9C27B0), // Purple
                                    title = "Visit ${website.name.ifBlank { card.companyName ?: "Website" }}",
                                    value = website.url,
                                    onClick = {
                                        val url = if (!website.url.startsWith("http://") && !website.url.startsWith("https://")) {
                                            "https://${website.url}"
                                        } else {
                                            website.url
                                        }
                                        val intent = Intent(Intent.ACTION_VIEW).apply {
                                            data = Uri.parse(url)
                                        }
                                        context.startActivity(intent)
                                    }
                                )
                            }
                            
                            // Address
                            card.address?.let { address ->
                                if (!address.fullAddress.isBlank()) {
                                    ContactActionCard(
                                        icon = Icons.Default.LocationOn,
                                        iconColor = androidx.compose.ui.graphics.Color(0xFFFF9800), // Orange
                                        title = "Get Directions",
                                        value = address.fullAddress,
                                        onClick = {
                                            val query = Uri.encode(address.fullAddress)
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
                            
                            Spacer(modifier = Modifier.height(24.dp))
                            
                            // Additional Information Section
                            val otherEmails = card.additionalEmails.filter { it != card.primaryEmail }
                            val otherWebsites = card.websiteLinks.filter { it != primaryWebsite }
                            
                            if (otherEmails.isNotEmpty() || otherWebsites.isNotEmpty()) {
                                Text(
                                    text = "Additional Information",
                                    style = MaterialTheme.typography.titleLarge,
                                    fontWeight = FontWeight.Bold,
                                    modifier = Modifier.padding(bottom = 12.dp)
                                )
                                
                                // Other Emails
                                otherEmails.forEach { email ->
                                    ContactActionCard(
                                        icon = Icons.Default.Email,
                                        iconColor = androidx.compose.ui.graphics.Color(0xFF2196F3), // Blue
                                        title = email.label ?: email.type.name,
                                        value = email.email,
                                        onClick = {
                                            val intent = Intent(Intent.ACTION_SENDTO).apply {
                                                data = Uri.parse("mailto:${email.email}")
                                            }
                                            context.startActivity(intent)
                                        }
                                    )
                                }
                                
                                // Other Websites
                                otherWebsites.forEach { website ->
                                    ContactActionCard(
                                        icon = Icons.Default.Language,
                                        iconColor = androidx.compose.ui.graphics.Color(0xFF9C27B0), // Purple
                                        title = website.name.ifBlank { "Website" },
                                        value = website.url,
                                        onClick = {
                                            val url = if (!website.url.startsWith("http://") && !website.url.startsWith("https://")) {
                                                "https://${website.url}"
                                            } else {
                                                website.url
                                            }
                                            val intent = Intent(Intent.ACTION_VIEW).apply {
                                                data = Uri.parse(url)
                                            }
                                            context.startActivity(intent)
                                        }
                                    )
                                }
                                
                                Spacer(modifier = Modifier.height(24.dp))
                            }
                            
                            // About Section
                            if (!card.bio.isNullOrBlank()) {
                                Text(
                                    text = "About ${card.firstName}",
                                    style = MaterialTheme.typography.titleLarge,
                                    fontWeight = FontWeight.Bold,
                                    modifier = Modifier.padding(bottom = 12.dp)
                                )
                                
                                Text(
                                    text = card.bio ?: "",
                                    style = MaterialTheme.typography.bodyLarge,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                            }
                            
                            // Card Information Section
                            Spacer(modifier = Modifier.height(24.dp))
                            
                            Text(
                                text = "Card Information",
                                style = MaterialTheme.typography.titleLarge,
                                fontWeight = FontWeight.Bold,
                                modifier = Modifier.padding(bottom = 12.dp)
                            )
                            
                            // Theme
                            if (!card.theme.isNullOrBlank()) {
                                val theme = com.sharemycard.android.util.CardThemes.getThemeById(card.theme)
                                if (theme != null) {
                                    Row(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .padding(vertical = 4.dp),
                                        verticalAlignment = Alignment.CenterVertically,
                                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                                    ) {
                                        Text(
                                            text = "Theme:",
                                            style = MaterialTheme.typography.bodyMedium,
                                            fontWeight = FontWeight.Medium
                                        )
                                        Text(
                                            text = theme.name,
                                            style = MaterialTheme.typography.bodyMedium,
                                            color = MaterialTheme.colorScheme.onSurfaceVariant
                                        )
                                    }
                                }
                            }
                            
                            // Active Status
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(vertical = 4.dp),
                                verticalAlignment = Alignment.CenterVertically,
                                horizontalArrangement = Arrangement.spacedBy(8.dp)
                            ) {
                                Text(
                                    text = "Status:",
                                    style = MaterialTheme.typography.bodyMedium,
                                    fontWeight = FontWeight.Medium
                                )
                                Text(
                                    text = if (card.isActive) "Active" else "Inactive",
                                    style = MaterialTheme.typography.bodyMedium,
                                    color = if (card.isActive) {
                                        MaterialTheme.colorScheme.primary
                                    } else {
                                        MaterialTheme.colorScheme.onSurfaceVariant
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
fun formatTimestamp(timestamp: Long): String {
    val dateFormat = SimpleDateFormat("yyyy-MM-dd HH:mm:ss zzz", Locale.US)
    dateFormat.timeZone = TimeZone.getDefault()
    return dateFormat.format(Date(timestamp))
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
                    color = MaterialTheme.colorScheme.onSurfaceVariant
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

/**
 * Shares a business card via Android's share sheet.
 * Shares as vCard file (.vcf) and optionally includes URL if card has serverCardId.
 */
private fun shareCard(context: android.content.Context, card: BusinessCard) {
    try {
        // Create vCard content
        val vCardContent = QRCodeGenerator.createVCardString(card)
        
        // Create temporary file for vCard
        val cacheDir = context.cacheDir
        val vCardFile = File(cacheDir, "${card.fullName.replace(" ", "_")}_${System.currentTimeMillis()}.vcf")
        vCardFile.writeText(vCardContent)
        
        // Get FileProvider URI
        val vCardUri = FileProvider.getUriForFile(
            context,
            "${context.packageName}.fileprovider",
            vCardFile
        )
        
        // Create share intent
        val shareIntent = Intent(Intent.ACTION_SEND).apply {
            type = "text/x-vcard"
            putExtra(Intent.EXTRA_STREAM, vCardUri)
            putExtra(Intent.EXTRA_SUBJECT, "${card.fullName} - Business Card")
            
            // Add URL if available
            val shareText = if (!card.serverCardId.isNullOrBlank()) {
                "View ${card.fullName}'s digital business card:\n" +
                "https://sharemycard.app/card.php?id=${card.serverCardId}&src=share-android"
            } else {
                "${card.fullName}'s contact information"
            }
            putExtra(Intent.EXTRA_TEXT, shareText)
            
            // Grant read permission to the receiving app
            addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
        }
        
        // Start share chooser
        context.startActivity(Intent.createChooser(shareIntent, "Share ${card.fullName}'s Business Card"))
    } catch (e: Exception) {
        android.util.Log.e("CardDetailsScreen", "Failed to share card: ${e.message}", e)
        // Fallback: share as plain text
        try {
            val shareText = if (!card.serverCardId.isNullOrBlank()) {
                "${card.fullName}\n" +
                "View digital business card: https://sharemycard.app/card.php?id=${card.serverCardId}&src=share-android\n\n" +
                "Phone: ${card.phoneNumber}\n" +
                (card.primaryEmail?.let { "Email: ${it.email}\n" } ?: "") +
                (card.companyName?.let { "Company: $it\n" } ?: "") +
                (card.jobTitle?.let { "Title: $it\n" } ?: "")
            } else {
                "${card.fullName}\n" +
                "Phone: ${card.phoneNumber}\n" +
                (card.primaryEmail?.let { "Email: ${it.email}\n" } ?: "") +
                (card.companyName?.let { "Company: $it\n" } ?: "") +
                (card.jobTitle?.let { "Title: $it\n" } ?: "")
            }
            
            val shareIntent = Intent(Intent.ACTION_SEND).apply {
                type = "text/plain"
                putExtra(Intent.EXTRA_TEXT, shareText)
                putExtra(Intent.EXTRA_SUBJECT, "${card.fullName} - Business Card")
            }
            
            context.startActivity(Intent.createChooser(shareIntent, "Share ${card.fullName}'s Business Card"))
        } catch (e2: Exception) {
            android.util.Log.e("CardDetailsScreen", "Failed to share as text: ${e2.message}", e2)
        }
    }
}

