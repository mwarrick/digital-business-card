package com.sharemycard.android.di

import com.sharemycard.android.data.repository.*
import com.sharemycard.android.domain.repository.*
import dagger.Binds
import dagger.Module
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
abstract class RepositoryModule {
    
    @Binds
    @Singleton
    abstract fun bindAuthRepository(
        impl: AuthRepositoryImpl
    ): AuthRepository
    
    @Binds
    @Singleton
    abstract fun bindBusinessCardRepository(
        impl: BusinessCardRepositoryImpl
    ): BusinessCardRepository
    
    @Binds
    @Singleton
    abstract fun bindContactRepository(
        impl: ContactRepositoryImpl
    ): ContactRepository
    
    @Binds
    @Singleton
    abstract fun bindLeadRepository(
        impl: LeadRepositoryImpl
    ): LeadRepository
    
    @Binds
    @Singleton
    abstract fun bindMediaRepository(
        impl: MediaRepositoryImpl
    ): MediaRepository
}

