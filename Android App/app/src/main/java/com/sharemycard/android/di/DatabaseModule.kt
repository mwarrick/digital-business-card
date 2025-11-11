package com.sharemycard.android.di

import android.content.Context
import androidx.room.Room
import com.sharemycard.android.data.local.database.ShareMyCardDatabase
import com.sharemycard.android.data.local.database.dao.*
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
    fun provideBusinessCardDao(database: ShareMyCardDatabase): BusinessCardDao {
        return database.businessCardDao()
    }
    
    @Provides
    fun provideEmailContactDao(database: ShareMyCardDatabase): EmailContactDao {
        return database.emailContactDao()
    }
    
    @Provides
    fun providePhoneContactDao(database: ShareMyCardDatabase): PhoneContactDao {
        return database.phoneContactDao()
    }
    
    @Provides
    fun provideWebsiteLinkDao(database: ShareMyCardDatabase): WebsiteLinkDao {
        return database.websiteLinkDao()
    }
    
    @Provides
    fun provideAddressDao(database: ShareMyCardDatabase): AddressDao {
        return database.addressDao()
    }
    
    @Provides
    fun provideContactDao(database: ShareMyCardDatabase): ContactDao {
        return database.contactDao()
    }
    
    @Provides
    fun provideLeadDao(database: ShareMyCardDatabase): LeadDao {
        return database.leadDao()
    }
}

