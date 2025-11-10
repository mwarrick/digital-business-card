package com.sharemycard.android

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.ui.Modifier
import androidx.hilt.navigation.compose.hiltViewModel
import com.sharemycard.android.domain.repository.AuthRepository
import com.sharemycard.android.presentation.navigation.ShareMyCardNavGraph
import com.sharemycard.android.ui.theme.ShareMyCardTheme
import android.util.Log
import dagger.hilt.android.AndroidEntryPoint
import javax.inject.Inject

@AndroidEntryPoint
class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        Log.d("MainActivity", "🟢 MainActivity onCreate - APP UPDATED VERSION 2.0 🟢")
        
        // Set up global exception handler to catch uncaught exceptions
        val defaultHandler = Thread.getDefaultUncaughtExceptionHandler()
        Thread.setDefaultUncaughtExceptionHandler { thread, exception ->
            Log.e("MainActivity", "❌ UNCAUGHT EXCEPTION in thread ${thread.name}", exception)
            Log.e("MainActivity", "Exception message: ${exception.message}")
            exception.printStackTrace()
            // Call default handler to show crash dialog
            defaultHandler?.uncaughtException(thread, exception)
        }
        
        setContent {
            ShareMyCardTheme {
                Surface(
                    modifier = Modifier.fillMaxSize(),
                    color = MaterialTheme.colorScheme.background
                ) {
                    val authRepository: AuthRepository = hiltViewModel<MainActivityViewModel>().authRepository
                    ShareMyCardNavGraph(authRepository = authRepository)
                }
            }
        }
    }
}

// Temporary ViewModel to inject AuthRepository in Composable
@dagger.hilt.android.lifecycle.HiltViewModel
class MainActivityViewModel @Inject constructor(
    val authRepository: AuthRepository
) : androidx.lifecycle.ViewModel()

