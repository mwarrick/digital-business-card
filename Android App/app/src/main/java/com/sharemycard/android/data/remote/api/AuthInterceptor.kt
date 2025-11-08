package com.sharemycard.android.data.remote.api

import com.sharemycard.android.data.local.TokenManager
import okhttp3.Interceptor
import okhttp3.Response
import javax.inject.Inject

class AuthInterceptor @Inject constructor(
    private val tokenManager: TokenManager
) : Interceptor {
    
    override fun intercept(chain: Interceptor.Chain): Response {
        val originalRequest = chain.request()
        val requestBuilder = originalRequest.newBuilder()
            .addHeader("Content-Type", "application/json")
            .addHeader("User-Agent", "ShareMyCard-Android/1.0")
            .addHeader("X-App-Platform", "android-app")
        
        // Add authorization header if token exists
        tokenManager.getToken()?.let { token ->
            requestBuilder.addHeader("Authorization", "Bearer $token")
        }
        
        return chain.proceed(requestBuilder.build())
    }
}

