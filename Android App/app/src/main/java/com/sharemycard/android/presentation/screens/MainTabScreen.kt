package com.sharemycard.android.presentation.screens

import androidx.compose.foundation.layout.*
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.ui.Modifier
import androidx.navigation.NavController
import com.sharemycard.android.presentation.screens.cards.CardsScreen
import com.sharemycard.android.presentation.screens.contacts.ContactsScreen
import com.sharemycard.android.presentation.screens.home.HomeScreen
import com.sharemycard.android.presentation.screens.leads.LeadsScreen
import com.sharemycard.android.presentation.screens.settings.SettingsScreen

@Composable
fun MainTabScreen(
    navController: NavController,
    onLogout: () -> Unit = {},
    onNavigateToCardDetails: (String) -> Unit = {},
    onNavigateToContactDetails: (String) -> Unit = {},
    onNavigateToLeadDetails: (String) -> Unit = {}
) {
    var selectedTab by rememberSaveable { mutableIntStateOf(0) }
    
    Scaffold(
        bottomBar = {
            NavigationBar {
                NavigationBarItem(
                    icon = { Icon(Icons.Default.Home, contentDescription = "Home") },
                    label = { Text("Home") },
                    selected = selectedTab == 0,
                    onClick = { selectedTab = 0 }
                )
                NavigationBarItem(
                    icon = { Icon(Icons.Default.Badge, contentDescription = "Cards") },
                    label = { Text("Cards") },
                    selected = selectedTab == 1,
                    onClick = { selectedTab = 1 }
                )
                NavigationBarItem(
                    icon = { Icon(Icons.Default.Person, contentDescription = "Contacts") },
                    label = { Text("Contacts") },
                    selected = selectedTab == 2,
                    onClick = { selectedTab = 2 }
                )
                NavigationBarItem(
                    icon = { Icon(Icons.Default.PersonAdd, contentDescription = "Leads") },
                    label = { Text("Leads") },
                    selected = selectedTab == 3,
                    onClick = { selectedTab = 3 }
                )
                NavigationBarItem(
                    icon = { Icon(Icons.Default.Settings, contentDescription = "Settings") },
                    label = { Text("Settings") },
                    selected = selectedTab == 4,
                    onClick = { selectedTab = 4 }
                )
            }
        }
    ) { paddingValues ->
        when (selectedTab) {
            0 -> HomeScreen(
                onLogout = onLogout,
                onNavigateToCards = { selectedTab = 1 },
                onNavigateToContacts = { selectedTab = 2 },
                onNavigateToLeads = { selectedTab = 3 },
                modifier = Modifier.padding(paddingValues)
            )
            1 -> CardsScreen(
                modifier = Modifier.padding(paddingValues),
                onCardClick = onNavigateToCardDetails,
                onQRClick = { cardId ->
                    navController.navigate("card_qr/$cardId")
                }
            )
            2 -> ContactsScreen(
                modifier = Modifier.padding(paddingValues),
                onContactClick = onNavigateToContactDetails
            )
            3 -> LeadsScreen(
                modifier = Modifier.padding(paddingValues),
                onLeadClick = onNavigateToLeadDetails
            )
            4 -> SettingsScreen(
                onLogout = onLogout,
                modifier = Modifier.padding(paddingValues)
            )
        }
    }
}

