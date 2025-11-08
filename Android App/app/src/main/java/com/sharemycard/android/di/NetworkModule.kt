package com.sharemycard.android.di

import com.sharemycard.android.data.local.TokenManager
import com.sharemycard.android.data.remote.api.ApiConfig
import com.sharemycard.android.data.remote.api.AuthApi
import com.sharemycard.android.data.remote.api.AuthInterceptor
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object NetworkModule {
    
    @Provides
    @Singleton
    fun provideTokenManager(
        @dagger.hilt.android.qualifiers.ApplicationContext context: android.content.Context
    ): TokenManager {
        return TokenManager(context)
    }
    
    @Provides
    @Singleton
    fun provideAuthInterceptor(tokenManager: TokenManager): AuthInterceptor {
        return AuthInterceptor(tokenManager)
    }
    
    @Provides
    @Singleton
    fun provideOkHttpClient(authInterceptor: AuthInterceptor): OkHttpClient {
        val loggingInterceptor = HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BODY
        }
        
        return OkHttpClient.Builder()
            .addInterceptor(authInterceptor)
            .addInterceptor(loggingInterceptor)
            .connectTimeout(ApiConfig.TIMEOUT_SECONDS, TimeUnit.SECONDS)
            .readTimeout(ApiConfig.TIMEOUT_SECONDS, TimeUnit.SECONDS)
            .writeTimeout(ApiConfig.TIMEOUT_SECONDS, TimeUnit.SECONDS)
            .build()
    }
    
    @Provides
    @Singleton
    fun provideRetrofit(okHttpClient: OkHttpClient): Retrofit {
        return Retrofit.Builder()
            .baseUrl(ApiConfig.BASE_URL)
            .client(okHttpClient)
            .addConverterFactory(GsonConverterFactory.create())
            .build()
    }
    
    @Provides
    @Singleton
    fun provideAuthApi(retrofit: Retrofit): AuthApi {
        return retrofit.create(AuthApi::class.java)
    }
    
    @Provides
    @Singleton
    fun provideCardApi(retrofit: Retrofit): com.sharemycard.android.data.remote.api.CardApi {
        return retrofit.create(com.sharemycard.android.data.remote.api.CardApi::class.java)
    }
    
    @Provides
    @Singleton
    fun provideContactApi(retrofit: Retrofit): com.sharemycard.android.data.remote.api.ContactApi {
        return retrofit.create(com.sharemycard.android.data.remote.api.ContactApi::class.java)
    }
    
    @Provides
    @Singleton
    fun provideLeadApi(retrofit: Retrofit): com.sharemycard.android.data.remote.api.LeadApi {
        return retrofit.create(com.sharemycard.android.data.remote.api.LeadApi::class.java)
    }
}

