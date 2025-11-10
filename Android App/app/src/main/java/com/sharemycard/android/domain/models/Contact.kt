package com.sharemycard.android.domain.models

import java.text.SimpleDateFormat
import java.util.*

data class Contact(
    val id: String,
    val firstName: String,
    val lastName: String,
    val email: String? = null,
    val phone: String? = null,
    val mobilePhone: String? = null,
    val company: String? = null,
    val jobTitle: String? = null,
    val address: String? = null,
    val city: String? = null,
    val state: String? = null,
    val zipCode: String? = null,
    val country: String? = null,
    val website: String? = null,
    val notes: String? = null,
    val commentsFromLead: String? = null,
    val birthdate: String? = null,
    val photoUrl: String? = null,
    val source: String? = null, // "manual", "converted", "qr_scan"
    val sourceMetadata: String? = null, // JSON string
    val createdAt: String,
    val updatedAt: String
) {
    val fullName: String get() = "$firstName $lastName"
    
    val createdAtDate: Date?
        get() = parseDate(createdAt)
    
    val updatedAtDate: Date?
        get() = parseDate(updatedAt)
    
    val formattedCreatedDate: String
        get() = createdAtDate?.let { formatDate(it) } ?: ""
    
    val formattedUpdatedDate: String
        get() = updatedAtDate?.let { formatDate(it) } ?: ""
    
    val wasUpdated: Boolean
        get() = createdAtDate?.let { created ->
            updatedAtDate?.let { updated ->
                kotlin.math.abs(updated.time - created.time) > 60000 // More than 1 minute
            } ?: false
        } ?: false
    
    private fun parseDate(dateString: String?): Date? {
        if (dateString.isNullOrEmpty()) return null
        
        val formats = listOf(
            "yyyy-MM-dd'T'HH:mm:ss.SSS'Z'",
            "yyyy-MM-dd'T'HH:mm:ss'Z'",
            "yyyy-MM-dd HH:mm:ss",
            "yyyy-MM-dd",
            "MMM d, yyyy h:mm a"
        )
        
        for (format in formats) {
            try {
                val sdf = SimpleDateFormat(format, Locale.getDefault())
                sdf.timeZone = TimeZone.getTimeZone("UTC")
                return sdf.parse(dateString)
            } catch (e: Exception) {
                // Try next format
            }
        }
        
        return null
    }
    
    private fun formatDate(date: Date): String {
        val formatter = SimpleDateFormat("MMM d, yyyy h:mm a", Locale.getDefault())
        return formatter.format(date)
    }
}

