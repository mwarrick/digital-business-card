package com.sharemycard.android.presentation.screens.home

import androidx.compose.foundation.layout.*
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
import android.content.Intent
import android.net.Uri
import android.util.Log
import androidx.compose.ui.platform.LocalContext
import com.sharemycard.android.presentation.viewmodel.HomeViewModel

@Composable
fun HomeScreen(
    onLogout: () -> Unit = {},
    onNavigateToCards: () -> Unit = {},
    onNavigateToContacts: () -> Unit = {},
    onNavigateToLeads: () -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: HomeViewModel = hiltViewModel()
) {
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()
    
    LaunchedEffect(Unit) {
        Log.d("HomeScreen", "HomeScreen composed")
        viewModel.refreshCounts()
    }
    
    Column(
        modifier = modifier
            .fillMaxSize()
            .padding(24.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.spacedBy(24.dp)
    ) {
        Spacer(modifier = Modifier.weight(0.5f))
        
        // App Logo/Icon
        Icon(
            imageVector = Icons.Default.Layers,
            contentDescription = "ShareMyCard Logo",
            modifier = Modifier.size(80.dp),
            tint = MaterialTheme.colorScheme.primary
        )
        
        // App Title
        Text(
            text = "ShareMyCard",
            style = MaterialTheme.typography.headlineLarge,
            fontWeight = FontWeight.Bold
        )
        
        // Subtitle
        Text(
            text = "Your Digital Business Cards",
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
        
        // Counts Row
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            // Cards Count
            CountCard(
                title = "Cards",
                count = uiState.cardCount,
                icon = Icons.Default.Badge,
                onClick = onNavigateToCards,
                modifier = Modifier.weight(1f)
            )
            
            // Contacts Count
            CountCard(
                title = "Contacts",
                count = uiState.contactCount,
                icon = Icons.Default.Person,
                onClick = onNavigateToContacts,
                modifier = Modifier.weight(1f)
            )
            
            // Leads Count
            CountCard(
                title = "Leads",
                count = uiState.leadCount,
                icon = Icons.Default.PersonAdd,
                onClick = onNavigateToLeads,
                modifier = Modifier.weight(1f)
            )
        }
        
        // Sync Button
        Button(
            onClick = { viewModel.sync() },
            enabled = !uiState.isSyncing,
            modifier = Modifier.fillMaxWidth(),
            colors = ButtonDefaults.buttonColors(
                containerColor = MaterialTheme.colorScheme.primary
            )
        ) {
            if (uiState.isSyncing) {
                CircularProgressIndicator(
                    modifier = Modifier.size(20.dp),
                    color = MaterialTheme.colorScheme.onPrimary
                )
                Spacer(modifier = Modifier.width(8.dp))
                Text("Syncing...")
            } else {
                Icon(
                    imageVector = Icons.Default.Sync,
                    contentDescription = "Sync",
                    modifier = Modifier.size(20.dp)
                )
                Spacer(modifier = Modifier.width(8.dp))
                Text("Sync")
            }
        }
        
        // Sync Status
        if (uiState.syncStatus.isNotBlank()) {
            Text(
                text = uiState.syncStatus,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
        
        Spacer(modifier = Modifier.weight(1f))
        
        // Bottom section with tighter spacing
        Column(
            modifier = Modifier.fillMaxWidth(),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.spacedBy(4.dp)
        ) {
            // User Email (at bottom)
            Text(
                text = "Signed in as ${uiState.userEmail}",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
            
            // Web App Link
            val context = LocalContext.current
            TextButton(
                onClick = {
                    try {
                        val intent = Intent(Intent.ACTION_VIEW).apply {
                            data = Uri.parse("https://sharemycard.app")
                        }
                        context.startActivity(intent)
                    } catch (e: Exception) {
                        Log.e("HomeScreen", "Failed to open web app URL", e)
                    }
                },
                modifier = Modifier.fillMaxWidth()
            ) {
                Text(
                    text = "Use ShareMyCard.app on the Web",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.primary
                )
            }
            
            // Report Issues Link
            TextButton(
                onClick = {
                    try {
                        val intent = Intent(Intent.ACTION_VIEW).apply {
                            data = Uri.parse("https://github.com/mwarrick/digital-business-card/issues")
                        }
                        context.startActivity(intent)
                    } catch (e: Exception) {
                        Log.e("HomeScreen", "Failed to open GitHub issues URL", e)
                    }
                },
                modifier = Modifier.fillMaxWidth()
            ) {
                Text(
                    text = "Report Issues",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.primary
                )
            }
            
            // Version Display
            Text(
                text = "Version 1.0.0",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
    }
}

@Composable
fun CountCard(
    title: String,
    count: Int,
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    onClick: () -> Unit = {},
    modifier: Modifier = Modifier
) {
    Card(
        onClick = onClick,
        modifier = modifier,
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surfaceVariant
        )
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Icon(
                imageVector = icon,
                contentDescription = title,
                modifier = Modifier.size(32.dp),
                tint = MaterialTheme.colorScheme.primary
            )
            Spacer(modifier = Modifier.height(8.dp))
            Text(
                text = count.toString(),
                style = MaterialTheme.typography.headlineMedium,
                fontWeight = FontWeight.Bold
            )
            Text(
                text = title,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
    }
}

