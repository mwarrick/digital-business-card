package com.sharemycard.android.domain.models

import java.text.SimpleDateFormat
import java.util.*

data class Lead(
    val id: String,
    val firstName: String,
    val lastName: String,
    val fullName: String? = null,
    val emailPrimary: String? = null,
    val workPhone: String? = null,
    val mobilePhone: String? = null,
    val streetAddress: String? = null,
    val city: String? = null,
    val state: String? = null,
    val zipCode: String? = null,
    val country: String? = null,
    val organizationName: String? = null,
    val jobTitle: String? = null,
    val birthdate: String? = null,
    val websiteUrl: String? = null,
    val photoUrl: String? = null,
    val commentsFromLead: String? = null,
    val createdAt: String? = null,
    val updatedAt: String? = null,
    // Business card information (from join)
    val cardFirstName: String? = null,
    val cardLastName: String? = null,
    val cardCompany: String? = null,
    val cardJobTitle: String? = null,
    // Custom QR code information (from join)
    val qrTitle: String? = null,
    val qrType: String? = null,
    // Status
    val status: String? = null // "new" or "converted"
) {
    val displayName: String
        get() = fullName?.takeIf { it.isNotEmpty() } 
            ?: "$firstName $lastName".trim()
    
    val isConverted: Boolean
        get() = status == "converted"
    
    val cardDisplayName: String
        get() {
            // If from business card, show card owner name
            if (cardFirstName != null && cardLastName != null) {
                return "$cardFirstName $cardLastName"
            }
            
            // If from custom QR code, show QR title/type
            if (!qrTitle.isNullOrEmpty()) {
                val qrTypeLabel = qrType?.replaceFirstChar { it.uppercaseChar() } ?: "Custom"
                return "QR $qrTypeLabel: $qrTitle"
            } else if (qrType != null) {
                return "QR ${qrType.replaceFirstChar { it.uppercaseChar() }}"
            }
            
            return "Unknown Card"
        }
    
    val createdAtDate: Date?
        get() = parseDate(createdAt)
    
    val formattedDate: String
        get() = createdAtDate?.let { formatDate(it) } ?: ""
    
    val relativeDate: String
        get() = createdAtDate?.let { getRelativeTimeString(it) } ?: ""
    
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
    
    private fun getRelativeTimeString(date: Date): String {
        val now = Date()
        val diff = now.time - date.time
        val seconds = diff / 1000
        val minutes = seconds / 60
        val hours = minutes / 60
        val days = hours / 24
        
        return when {
            days > 365 -> "${days / 365} year${if (days / 365 > 1) "s" else ""} ago"
            days > 30 -> "${days / 30} month${if (days / 30 > 1) "s" else ""} ago"
            days > 0 -> "${days} day${if (days > 1) "s" else ""} ago"
            hours > 0 -> "${hours} hour${if (hours > 1) "s" else ""} ago"
            minutes > 0 -> "${minutes} minute${if (minutes > 1) "s" else ""} ago"
            else -> "Just now"
        }
    }
}

