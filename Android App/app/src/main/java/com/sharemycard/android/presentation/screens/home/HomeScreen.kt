package com.sharemycard.android.presentation.screens.home

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
import android.content.Intent
import android.net.Uri
import android.util.Log
import androidx.compose.ui.platform.LocalContext
import com.sharemycard.android.presentation.viewmodel.HomeViewModel

@Composable
@Suppress("UNUSED_PARAMETER")
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
        Log.d("HomeScreen", "HomeScreen composed - performing initial sync if needed")
        viewModel.refreshCounts()
        // Perform automatic sync on first load after login
        viewModel.performInitialSyncIfNeeded()
    }
    
    Column(
        modifier = modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(16.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        // Logo and Title in a Row
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.Center,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Icon(
                imageVector = Icons.Default.Layers,
                contentDescription = "ShareMyCard Logo",
                modifier = Modifier.size(48.dp),
                tint = MaterialTheme.colorScheme.primary
            )
            Spacer(modifier = Modifier.width(12.dp))
            Text(
                text = "ShareMyCard",
                style = MaterialTheme.typography.headlineMedium,
                fontWeight = FontWeight.Bold
            )
        }
        
        // Counts Row
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(8.dp)
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
        
        // User Email (moved above Sync)
        Text(
            text = "Signed in as ${uiState.userEmail}",
            style = MaterialTheme.typography.bodySmall,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
        
        // Sync Button
        Button(
            onClick = { 
                Log.d("HomeScreen", "🔵 SYNC BUTTON CLICKED IN UI")
                viewModel.sync() 
            },
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
        
        // Web App Link (below sync status)
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
        
        // Bottom section with tighter spacing
        Column(
            modifier = Modifier.fillMaxWidth(),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.spacedBy(4.dp)
        ) {
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
                .padding(12.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Icon(
                imageVector = icon,
                contentDescription = title,
                modifier = Modifier.size(24.dp),
                tint = MaterialTheme.colorScheme.primary
            )
            Spacer(modifier = Modifier.height(4.dp))
            Text(
                text = count.toString(),
                style = MaterialTheme.typography.titleLarge,
                fontWeight = FontWeight.Bold
            )
            Text(
                text = title,
                style = MaterialTheme.typography.labelSmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
    }
}

