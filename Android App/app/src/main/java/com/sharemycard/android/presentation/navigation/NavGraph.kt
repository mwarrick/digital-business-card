package com.sharemycard.android.presentation.navigation

import android.net.Uri
import android.util.Log
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.navigation.NavHostController
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import androidx.navigation.navArgument
import com.sharemycard.android.domain.repository.AuthRepository
import java.util.regex.Pattern
import com.sharemycard.android.presentation.screens.auth.LoginScreen
import com.sharemycard.android.presentation.screens.auth.PasswordScreen
import com.sharemycard.android.presentation.screens.auth.ForgotPasswordScreen
import com.sharemycard.android.presentation.screens.auth.RegisterScreen
import com.sharemycard.android.presentation.screens.auth.VerifyScreen
import com.sharemycard.android.presentation.screens.MainTabScreen
import com.sharemycard.android.presentation.screens.cards.CardDetailsScreen
import com.sharemycard.android.presentation.screens.cards.CardEditScreen
import com.sharemycard.android.presentation.screens.cards.QRCodeScreen
import com.sharemycard.android.presentation.screens.contacts.ContactDetailsScreen
import com.sharemycard.android.presentation.screens.contacts.ContactEditScreen
import com.sharemycard.android.presentation.screens.contacts.QRScannerScreen
import com.sharemycard.android.presentation.screens.leads.LeadDetailsScreen
import com.sharemycard.android.presentation.screens.settings.PasswordSettingsScreen

@Composable
fun ShareMyCardNavGraph(
    navController: NavHostController = rememberNavController(),
    authRepository: AuthRepository
) {
    val startDestination = if (authRepository.isAuthenticated()) "home" else "login"
    NavHost(
        navController = navController,
        startDestination = startDestination
    ) {
        // Authentication flow - login with email parameter (must come before plain "login")
        composable(
            route = "login/{email}",
            arguments = listOf(
                navArgument("email") {
                    type = NavType.StringType
                }
            )
        ) { backStackEntry ->
            // Decode URL-encoded email
            val encodedEmail = backStackEntry.arguments?.getString("email") ?: ""
            val email = try {
                Uri.decode(encodedEmail)
            } catch (e: Exception) {
                Log.e("NavGraph", "Error decoding email", e)
                encodedEmail // Fallback to original if decoding fails
            }
            
            LoginScreen(
                initialEmail = email,
                onLoginSuccess = { loginEmail, hasPassword ->
                    try {
                        // Check if this is a demo login - demo accounts bypass verification
                        if (loginEmail.lowercase() == "demo@sharemycard.app") {
                            Log.d("NavGraph", "Demo login detected - navigating directly to home")
                            navController.navigate("home") {
                                popUpTo(0) { inclusive = true }
                            }
                        } else {
                            // Check if user has password
                            if (hasPassword) {
                                // User has password - navigate to password screen
                                val encodedLoginEmail = Uri.encode(loginEmail)
                                Log.d("NavGraph", "Navigating to password screen with email: $loginEmail (encoded: $encodedLoginEmail), hasPassword: $hasPassword")
                                navController.navigate("password/$encodedLoginEmail") {
                                    popUpTo("login") { inclusive = false } // Keep login in back stack
                                }
                            } else {
                                // User doesn't have password - navigate directly to verify screen
                                val encodedLoginEmail = Uri.encode(loginEmail)
                                Log.d("NavGraph", "User doesn't have password - navigating directly to verify screen for email: $loginEmail")
                                navController.navigate("verify/$encodedLoginEmail/false") {
                                    popUpTo("login") { inclusive = false } // Keep login in back stack
                                }
                            }
                        }
                    } catch (e: Exception) {
                        Log.e("NavGraph", "Error navigating after login", e)
                    }
                },
                onNavigateToRegister = {
                    try {
                        navController.navigate("register")
                    } catch (e: Exception) {
                        Log.e("NavGraph", "Error navigating to register", e)
                    }
                }
            )
        }
        
        // Login without email parameter
        composable("login") {
            LoginScreen(
                initialEmail = null,
                onLoginSuccess = { email, hasPassword ->
                    try {
                        // Check if this is a demo login - demo accounts bypass verification
                        if (email.lowercase() == "demo@sharemycard.app") {
                            Log.d("NavGraph", "Demo login detected - navigating directly to home")
                            navController.navigate("home") {
                                popUpTo(0) { inclusive = true }
                            }
                        } else {
                            // Check if user has password
                            if (hasPassword) {
                                // User has password - navigate to password screen
                                val encodedEmail = Uri.encode(email)
                                Log.d("NavGraph", "Navigating to password screen with email: $email (encoded: $encodedEmail), hasPassword: $hasPassword")
                                navController.navigate("password/$encodedEmail") {
                                    popUpTo("login") { inclusive = false } // Keep login in back stack
                                }
                            } else {
                                // User doesn't have password - navigate directly to verify screen
                                val encodedEmail = Uri.encode(email)
                                Log.d("NavGraph", "User doesn't have password - navigating directly to verify screen for email: $email")
                                navController.navigate("verify/$encodedEmail/false") {
                                    popUpTo("login") { inclusive = false } // Keep login in back stack
                                }
                            }
                        }
                    } catch (e: Exception) {
                        Log.e("NavGraph", "Error navigating after login", e)
                    }
                },
                onNavigateToRegister = {
                    try {
                        navController.navigate("register")
                    } catch (e: Exception) {
                        Log.e("NavGraph", "Error navigating to register", e)
                    }
                }
            )
        }
        
        // Password screen - shows password field + Request Verification Code button
        composable(
            route = "password/{email}",
            arguments = listOf(
                navArgument("email") { type = NavType.StringType }
            )
        ) { backStackEntry ->
            val encodedEmail = backStackEntry.arguments?.getString("email") ?: ""
            val email = try {
                Uri.decode(encodedEmail)
            } catch (e: Exception) {
                Log.e("NavGraph", "Error decoding email", e)
                encodedEmail
            }
            Log.d("NavGraph", "Password screen - decoded email: '$email' (encoded was: '$encodedEmail')")
            
            PasswordScreen(
                email = email,
                onPasswordLoginSuccess = {
                    // Password login successful - navigate to home
                    Log.d("NavGraph", "Password login successful, navigating to home")
                    navController.navigate("home") {
                        popUpTo(0) { inclusive = true }
                    }
                },
                onRequestVerificationCode = {
                    // User requested verification code - navigate to verify screen
                    val encodedEmailForVerify = Uri.encode(email)
                    Log.d("NavGraph", "Requesting verification code, navigating to verify screen for email: $email")
                    navController.navigate("verify/$encodedEmailForVerify/false") {
                        popUpTo("password") { inclusive = false } // Keep password screen in back stack
                    }
                },
                onForgotPassword = {
                    // Navigate to forgot password screen
                    Log.d("NavGraph", "Forgot password - navigating to forgot password screen from password screen")
                    navController.navigate("forgot_password") {
                        popUpTo("password") { inclusive = false } // Keep password screen in back stack
                    }
                }
            )
        }
        
        composable("register") {
            RegisterScreen(
                onRegistrationSuccess = { email ->
                    // Navigate to verify screen (registration always uses email code)
                    // Use Uri.encode() which properly handles + and other special characters in URL paths
                    // This will encode + as %2B, which Uri.decode() will correctly decode back to +
                    val encodedEmail = Uri.encode(email)
                    Log.d("NavGraph", "Navigating to verify after registration with email: $email (encoded: $encodedEmail)")
                    navController.navigate("verify/$encodedEmail/false") {
                        popUpTo("register") { inclusive = false }
                    }
                },
                onNavigateToLogin = { email, hasPassword ->
                    // If hasPassword is provided (verified account), navigate directly to verify screen
                    // Otherwise, navigate to login screen
                    if (email.isNotBlank() && hasPassword != null) {
                        // Verified account - go directly to verify screen
                        val encodedEmail = Uri.encode(email)
                        val hasPasswordValue = hasPassword ?: false
                        Log.d("NavGraph", "Navigating directly to verify screen for verified account: $email, hasPassword: $hasPasswordValue (original: $hasPassword)")
                        navController.navigate("verify/$encodedEmail/$hasPasswordValue") {
                            popUpTo("register") { inclusive = false }
                        }
                    } else if (email.isNotBlank()) {
                        // Email provided but no hasPassword info - go to login screen with email pre-filled
                        val encodedEmail = Uri.encode(email)
                        navController.navigate("login/$encodedEmail") {
                            popUpTo("register") { inclusive = false }
                        }
                    } else {
                        // No email - go to login screen
                        navController.navigate("login") {
                            popUpTo("register") { inclusive = false }
                        }
                    }
                }
            )
        }
        
        composable(
            route = "verify/{email}/{hasPassword}",
            arguments = listOf(
                navArgument("email") { type = NavType.StringType },
                navArgument("hasPassword") { type = NavType.BoolType }
            )
        ) { backStackEntry ->
            // Decode URL-encoded email using Uri.decode() which properly handles + signs
            val encodedEmail = backStackEntry.arguments?.getString("email") ?: ""
            val email = try {
                Uri.decode(encodedEmail)
            } catch (e: Exception) {
                Log.e("NavGraph", "Error decoding email", e)
                encodedEmail // Fallback to original if decoding fails
            }
            // Parse hasPassword - handle both boolean and string representations
            val hasPasswordArg = backStackEntry.arguments?.get("hasPassword")
            val hasPassword = when {
                hasPasswordArg is Boolean -> hasPasswordArg
                hasPasswordArg is String -> hasPasswordArg.toBoolean()
                else -> false
            }
            Log.d("NavGraph", "Verify screen - decoded email: '$email' (encoded was: '$encodedEmail'), hasPassword: $hasPassword (raw: $hasPasswordArg, type: ${hasPasswordArg?.javaClass?.simpleName})")
            
            VerifyScreen(
                email = email,
                hasPassword = hasPassword,
                onVerificationSuccess = {
                    navController.navigate("home") {
                        popUpTo(0) { inclusive = true }
                    }
                },
                onForgotPassword = {
                    // Navigate to forgot password screen
                    Log.d("NavGraph", "Forgot password - navigating to forgot password screen")
                    navController.navigate("forgot_password") {
                        popUpTo("verify") { inclusive = false } // Keep verify screen in back stack
                    }
                }
            )
        }
        
        // Main app with tab navigation
        composable("home") {
            var shouldLogout by remember { mutableStateOf(false) }
            
            LaunchedEffect(shouldLogout) {
                if (shouldLogout) {
                    Log.d("NavGraph", "Logout clicked - clearing auth and navigating to login")
                    authRepository.logout()
                    navController.navigate("login") {
                        popUpTo(0) { inclusive = true }
                    }
                    shouldLogout = false
                }
            }
            
            MainTabScreen(
                navController = navController,
                onLogout = {
                    shouldLogout = true
                },
                        onNavigateToCardDetails = { cardId ->
                            navController.navigate("card_details/$cardId")
                        },
                        onNavigateToContactDetails = { contactId ->
                            navController.navigate("contact_details/$contactId")
                        },
                        onNavigateToLeadDetails = { leadId ->
                            navController.navigate("lead_details/$leadId")
                        }
                    )
        }
        
                // Detail screens
                composable(
                    route = "card_details/{cardId}",
                    arguments = listOf(navArgument("cardId") { type = NavType.StringType })
                ) { backStackEntry ->
                    val cardId = backStackEntry.arguments?.getString("cardId") ?: ""
                    CardDetailsScreen(
                        cardId = cardId,
                        onNavigateBack = { navController.popBackStack() },
                        onNavigateToEdit = { editCardId ->
                            navController.navigate("card_edit/$editCardId")
                        }
                    )
                }
                
                // Card create/edit screens
                composable(
                    route = "card_create"
                ) {
                    CardEditScreen(
                        cardId = null,
                        onNavigateBack = { navController.popBackStack() }
                    )
                }
                
                composable(
                    route = "card_edit/{cardId}",
                    arguments = listOf(navArgument("cardId") { type = NavType.StringType })
                ) { backStackEntry ->
                    val cardId = backStackEntry.arguments?.getString("cardId") ?: ""
                    CardEditScreen(
                        cardId = cardId,
                        onNavigateBack = { navController.popBackStack() }
                    )
                }
                
                composable(
                    route = "card_qr/{cardId}",
                    arguments = listOf(navArgument("cardId") { type = NavType.StringType })
                ) { backStackEntry ->
                    val cardId = backStackEntry.arguments?.getString("cardId") ?: ""
                    QRCodeScreen(
                        cardId = cardId,
                        onNavigateBack = { navController.popBackStack() }
                    )
                }
        
        composable(
            route = "contact_details/{contactId}",
            arguments = listOf(navArgument("contactId") { type = NavType.StringType })
        ) { backStackEntry ->
            val contactId = backStackEntry.arguments?.getString("contactId") ?: ""
            ContactDetailsScreen(
                contactId = contactId,
                onNavigateBack = { navController.popBackStack() },
                onNavigateToEdit = { editContactId ->
                    navController.navigate("contact_edit/$editContactId")
                }
            )
        }
        
        // Contact create/edit screens
        composable(
            route = "contact_create"
        ) {
            ContactEditScreen(
                contactId = null,
                onNavigateBack = { navController.popBackStack() }
            )
        }
        
        composable(
            route = "contact_edit/{contactId}",
            arguments = listOf(navArgument("contactId") { type = NavType.StringType })
        ) { backStackEntry ->
            val contactId = backStackEntry.arguments?.getString("contactId") ?: ""
            ContactEditScreen(
                contactId = contactId,
                onNavigateBack = { navController.popBackStack() }
            )
        }
        
        // Contact create from QR code
        composable(
            route = "contact_create_from_qr/{cardId}",
            arguments = listOf(navArgument("cardId") { type = NavType.StringType })
        ) { backStackEntry ->
            val cardId = backStackEntry.arguments?.getString("cardId") ?: ""
            ContactEditScreen(
                contactId = null,
                cardIdFromQR = cardId,
                onNavigateBack = { navController.popBackStack() }
            )
        }
        
        // QR Scanner for contacts
        composable(
            route = "contact_qr_scan"
        ) {
            QRScannerScreen(
                onScanResult = { qrCode ->
                    try {
                        Log.d("NavGraph", "QR code scanned: $qrCode")
                        // Parse QR code and extract card ID
                        // The QR code should be a URL like: https://sharemycard.app/card.php?id=...
                        val cardId = extractCardIdFromQRCode(qrCode)
                        Log.d("NavGraph", "Extracted card ID: $cardId")
                        if (cardId != null) {
                            // Navigate to contact creation with card ID - ContactEditScreen will fetch and populate data
                            Log.d("NavGraph", "Navigating to contact_create_from_qr/$cardId")
                            navController.navigate("contact_create_from_qr/$cardId") {
                                popUpTo("contact_qr_scan") { inclusive = true }
                            }
                            Log.d("NavGraph", "Navigation completed")
                        } else {
                            // Invalid QR code - show error and go back
                            Log.w("NavGraph", "Invalid QR code format, going back")
                            navController.popBackStack()
                        }
                    } catch (e: Exception) {
                        Log.e("NavGraph", "Error processing QR scan result", e)
                        e.printStackTrace()
                        // Try to go back on error
                        try {
                            navController.popBackStack()
                        } catch (navException: Exception) {
                            Log.e("NavGraph", "Error navigating back", navException)
                        }
                    }
                },
                onNavigateBack = { 
                    try {
                        navController.popBackStack()
                    } catch (e: Exception) {
                        Log.e("NavGraph", "Error navigating back from QR scanner", e)
                    }
                }
            )
        }
        
        composable(
            route = "lead_details/{leadId}",
            arguments = listOf(navArgument("leadId") { type = NavType.StringType })
        ) { backStackEntry ->
            val leadId = backStackEntry.arguments?.getString("leadId") ?: ""
            LeadDetailsScreen(
                leadId = leadId,
                onNavigateBack = { navController.popBackStack() }
            )
        }
        
        // Forgot password screen
        composable("forgot_password") {
            ForgotPasswordScreen(
                onResetComplete = {
                    // After successful password reset, navigate back to login
                    Log.d("NavGraph", "Password reset complete - navigating to login")
                    navController.navigate("login") {
                        popUpTo(0) { inclusive = true }
                    }
                },
                onNavigateBack = {
                    navController.popBackStack()
                }
            )
        }
        
        // Settings screens
        composable("password_settings") {
            PasswordSettingsScreen(
                onNavigateBack = { navController.popBackStack() }
            )
        }
    }
}

// Helper function to extract card ID from QR code URL
private fun extractCardIdFromQRCode(qrCode: String): String? {
    Log.d("NavGraph", "🔍 Extracting card ID from QR code: $qrCode")
    // Pattern to match: https://sharemycard.app/card.php?id=XXXXX
    val pattern = Pattern.compile(".*card\\.php\\?id=([^&\\s]+)")
    val matcher = pattern.matcher(qrCode)
    return if (matcher.find()) {
        val cardId = matcher.group(1)
        Log.d("NavGraph", "✅ Extracted card ID: $cardId")
        cardId
    } else {
        Log.w("NavGraph", "⚠️ Could not extract card ID from QR code")
        null
    }
}

