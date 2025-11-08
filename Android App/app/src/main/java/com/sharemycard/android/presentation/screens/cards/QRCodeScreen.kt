package com.sharemycard.android.presentation.screens.cards

import android.content.Intent
import android.graphics.Bitmap
import androidx.compose.foundation.Image
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import coil.compose.AsyncImage
import com.sharemycard.android.presentation.viewmodel.CardDetailsViewModel
import com.sharemycard.android.util.QRCodeGenerator

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun QRCodeScreen(
    cardId: String,
    onNavigateBack: () -> Unit = {},
    viewModel: CardDetailsViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()
    val card = uiState.card
    val context = LocalContext.current
    
    LaunchedEffect(cardId) {
        viewModel.loadCard(cardId)
    }
    
    // Generate QR code when card is loaded
    val qrBitmap = remember(card) {
        card?.let { QRCodeGenerator.generateQRCode(it, size = 512) }
    }
    
    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("QR Code") },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    if (qrBitmap != null && card != null) {
                        IconButton(
                            onClick = {
                                // Share QR code as image
                                // Note: For full image sharing, we'd need to save to file first
                                // For now, share the text/URL
                                val shareText = if (!card.serverCardId.isNullOrBlank()) {
                                    "Scan this QR code to add ${card.fullName} to your contacts:\nhttps://sharemycard.app/card.php?id=${card.serverCardId}&src=qr-app"
                                } else {
                                    "Scan this QR code to add ${card.fullName} to your contacts"
                                }
                                val shareIntent = Intent().apply {
                                    action = Intent.ACTION_SEND
                                    putExtra(Intent.EXTRA_TEXT, shareText)
                                    type = "text/plain"
                                }
                                context.startActivity(Intent.createChooser(shareIntent, "Share QR Code"))
                            }
                        ) {
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
                        .verticalScroll(rememberScrollState())
                        .padding(16.dp),
                    horizontalAlignment = Alignment.CenterHorizontally,
                    verticalArrangement = Arrangement.spacedBy(24.dp)
                ) {
                    // Card Preview Section
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = MaterialTheme.shapes.large
                    ) {
                        Column(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(16.dp),
                            horizontalAlignment = Alignment.CenterHorizontally
                        ) {
                            // Profile Photo
                            if (!card.profilePhotoPath.isNullOrBlank()) {
                                val profileUrl = when {
                                    card.profilePhotoPath!!.startsWith("http") -> card.profilePhotoPath
                                    card.profilePhotoPath!!.startsWith("/api/media/view") -> "https://sharemycard.app${card.profilePhotoPath}"
                                    else -> "https://sharemycard.app/api/media/view?file=${card.profilePhotoPath}"
                                }
                                AsyncImage(
                                    model = profileUrl,
                                    contentDescription = "Profile Photo",
                                    modifier = Modifier
                                        .size(80.dp)
                                        .clip(RoundedCornerShape(8.dp)),
                                    contentScale = ContentScale.Crop
                                )
                            }
                            
                            Spacer(modifier = Modifier.height(12.dp))
                            
                            Text(
                                text = card.fullName,
                                style = MaterialTheme.typography.headlineSmall,
                                fontWeight = FontWeight.Bold
                            )
                            
                            if (!card.jobTitle.isNullOrBlank()) {
                                Text(
                                    text = card.jobTitle ?: "",
                                    style = MaterialTheme.typography.titleMedium,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
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
                    }
                    
                    // QR Code Section
                    Column(
                        horizontalAlignment = Alignment.CenterHorizontally,
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        Text(
                            text = "Scan to Add Contact",
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Medium
                        )
                        
                        if (qrBitmap != null) {
                            Card(
                                modifier = Modifier
                                    .size(250.dp)
                                    .padding(16.dp),
                                shape = MaterialTheme.shapes.medium
                            ) {
                                Box(
                                    modifier = Modifier.fillMaxSize(),
                                    contentAlignment = Alignment.Center
                                ) {
                                    Image(
                                        bitmap = qrBitmap.asImageBitmap(),
                                        contentDescription = "QR Code",
                                        modifier = Modifier.fillMaxSize(),
                                        contentScale = ContentScale.Fit
                                    )
                                }
                            }
                            
                            Text(
                                text = "QR Code contains your contact information",
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        } else {
                            Box(
                                modifier = Modifier
                                    .size(250.dp)
                                    .padding(16.dp),
                                contentAlignment = Alignment.Center
                            ) {
                                Column(
                                    horizontalAlignment = Alignment.CenterHorizontally,
                                    verticalArrangement = Arrangement.spacedBy(8.dp)
                                ) {
                                    CircularProgressIndicator()
                                    Text(
                                        text = "Generating QR Code...",
                                        style = MaterialTheme.typography.bodySmall,
                                        color = MaterialTheme.colorScheme.onSurfaceVariant
                                    )
                                }
                            }
                        }
                    }
                    
                    Spacer(modifier = Modifier.height(16.dp))
                }
            }
        }
    }
}

