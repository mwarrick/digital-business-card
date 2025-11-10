package com.sharemycard.android.di

import android.content.Context
import androidx.room.Room
import com.sharemycard.android.data.local.database.ShareMyCardDatabase
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.android.qualifiers.ApplicationContext
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object DatabaseModule {
    
    @Provides
    @Singleton
    fun provideDatabase(
        @ApplicationContext context: Context
    ): ShareMyCardDatabase {
        return Room.databaseBuilder(
            context,
            ShareMyCardDatabase::class.java,
            "sharemycard_database"
        )
            .fallbackToDestructiveMigration() // For development - remove in production
            .build()
    }
    
    @Provides
    fun provideBusinessCardDao(database: ShareMyCardDatabase) = database.businessCardDao()
    
    @Provides
    fun provideEmailContactDao(database: ShareMyCardDatabase) = database.emailContactDao()
    
    @Provides
    fun providePhoneContactDao(database: ShareMyCardDatabase) = database.phoneContactDao()
    
    @Provides
    fun provideWebsiteLinkDao(database: ShareMyCardDatabase) = database.websiteLinkDao()
    
    @Provides
    fun provideAddressDao(database: ShareMyCardDatabase) = database.addressDao()
    
    @Provides
    fun provideContactDao(database: ShareMyCardDatabase) = database.contactDao()
    
    @Provides
    fun provideLeadDao(database: ShareMyCardDatabase) = database.leadDao()
}

