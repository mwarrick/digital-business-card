package com.sharemycard.android.di

import android.content.Context
import androidx.room.Room
import androidx.room.migration.Migration
import androidx.sqlite.db.SupportSQLiteDatabase
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
    
    // Migration from version 1 to 2: Add isDeleted columns
    val MIGRATION_1_2 = object : Migration(1, 2) {
        override fun migrate(database: SupportSQLiteDatabase) {
            // Add isDeleted column to business_cards table
            database.execSQL("ALTER TABLE business_cards ADD COLUMN isDeleted INTEGER NOT NULL DEFAULT 0")
            
            // Add isDeleted column to contacts table
            database.execSQL("ALTER TABLE contacts ADD COLUMN isDeleted INTEGER NOT NULL DEFAULT 0")
            
            // Add isDeleted column to leads table
            database.execSQL("ALTER TABLE leads ADD COLUMN isDeleted INTEGER NOT NULL DEFAULT 0")
        }
    }
    
    // Migration from version 2 to 3: Add leadId column to contacts table
    val MIGRATION_2_3 = object : Migration(2, 3) {
        override fun migrate(database: SupportSQLiteDatabase) {
            // Add leadId column to contacts table (nullable TEXT)
            database.execSQL("ALTER TABLE contacts ADD COLUMN leadId TEXT")
        }
    }
    
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
            .addMigrations(MIGRATION_1_2, MIGRATION_2_3)
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

