package com.sharemycard.android.data.remote.api

object ApiConfig {
    const val BASE_URL = "https://sharemycard.app/api/"
    const val TIMEOUT_SECONDS = 30L
    
    object Endpoints {
        // Authentication
        const val REGISTER = "auth/register"
        const val LOGIN = "auth/login"
        const val VERIFY = "auth/verify"
        const val RESEND_VERIFICATION = "auth/resend-verification"
        
        // Password Management
        const val SET_PASSWORD = "auth/set-password"
        const val CHANGE_PASSWORD = "auth/change-password"
        const val RESET_PASSWORD_REQUEST = "auth/reset-password-request"
        const val RESET_PASSWORD_COMPLETE = "auth/reset-password-complete"
        const val CHECK_PASSWORD_STATUS = "auth/check-password-status"
        
        // Business Cards
        const val CARDS = "cards/"
        const val QR_CODE = "cards/qrcode"
        
        // Contacts
        const val CONTACTS = "contacts/"
        
        // Leads
        const val LEADS = "leads/"
        const val CONVERT_LEAD = "leads/convert"
        
        // Media
        const val MEDIA_UPLOAD = "media/upload"
        const val MEDIA_VIEW = "media/view"
        const val MEDIA_DELETE = "media/delete"
    }
    
    object MediaType {
        const val PROFILE_PHOTO = "profile_photo"
        const val COMPANY_LOGO = "company_logo"
        const val COVER_GRAPHIC = "cover_graphic"
    }
}

