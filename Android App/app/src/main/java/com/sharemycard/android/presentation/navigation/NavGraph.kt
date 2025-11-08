package com.sharemycard.android.presentation.navigation

import android.net.Uri
import android.util.Log
import androidx.compose.runtime.Composable
import androidx.navigation.NavHostController
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import androidx.navigation.navArgument
import com.sharemycard.android.domain.repository.AuthRepository
import com.sharemycard.android.presentation.screens.auth.LoginScreen
import com.sharemycard.android.presentation.screens.auth.RegisterScreen
import com.sharemycard.android.presentation.screens.auth.VerifyScreen
import com.sharemycard.android.presentation.screens.MainTabScreen
import com.sharemycard.android.presentation.screens.cards.CardDetailsScreen
import com.sharemycard.android.presentation.screens.cards.QRCodeScreen
import com.sharemycard.android.presentation.screens.contacts.ContactDetailsScreen
import com.sharemycard.android.presentation.screens.leads.LeadDetailsScreen

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
                        val encodedLoginEmail = Uri.encode(loginEmail)
                        Log.d("NavGraph", "Navigating to verify with email: $loginEmail (encoded: $encodedLoginEmail), hasPassword: $hasPassword")
                        navController.navigate("verify/$encodedLoginEmail/$hasPassword") {
                            popUpTo("login") { inclusive = false }
                        }
                    } catch (e: Exception) {
                        Log.e("NavGraph", "Error navigating to verify screen", e)
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
                        val encodedEmail = Uri.encode(email)
                        Log.d("NavGraph", "Navigating to verify with email: $email (encoded: $encodedEmail), hasPassword: $hasPassword")
                        navController.navigate("verify/$encodedEmail/$hasPassword") {
                            popUpTo("login") { inclusive = false }
                        }
                    } catch (e: Exception) {
                        Log.e("NavGraph", "Error navigating to verify screen", e)
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
                    // TODO: Implement forgot password flow
                    // For now, navigate back to login
                    Log.d("NavGraph", "Forgot password - navigating back to login")
                    navController.popBackStack("login", false)
                }
            )
        }
        
        // Main app with tab navigation
        composable("home") {
                    MainTabScreen(
                        navController = navController,
                        onLogout = {
                            Log.d("NavGraph", "Logout clicked - clearing auth and navigating to login")
                            authRepository.logout()
                            navController.navigate("login") {
                                popUpTo(0) { inclusive = true }
                            }
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
                        onNavigateToEdit = { /* TODO: Navigate to edit screen */ }
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
                onNavigateToEdit = { /* TODO: Navigate to edit screen */ }
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
    }
}

