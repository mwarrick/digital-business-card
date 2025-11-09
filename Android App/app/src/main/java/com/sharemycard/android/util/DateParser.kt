package com.sharemycard.android.util

import java.text.SimpleDateFormat
import java.util.*

/**
 * Utility for parsing server date strings in various formats.
 * Handles ISO8601, MySQL DATETIME, and MySQL DATETIME with microseconds.
 */
object DateParser {
    
    /**
     * Parse a server date string to a timestamp (milliseconds since epoch).
     * Tries multiple formats and timezones:
     * 1. ISO8601: "2024-01-01T12:00:00.000Z" or "2024-01-01T12:00:00Z" (UTC)
     * 2. MySQL DATETIME: "2024-01-01 12:00:00" (tries UTC first, then EST/EDT)
     * 3. MySQL DATETIME with microseconds: "2024-01-01 12:00:00.123456" (tries UTC first, then EST/EDT)
     * 
     * Note: Server may be sending timestamps in EST/EDT timezone. We try UTC first (best practice),
     * then fall back to EST/EDT if UTC parsing doesn't make sense.
     */
    fun parseServerDate(dateString: String?): Long? {
        if (dateString.isNullOrBlank()) return null
        
        // Try ISO8601 format with fractional seconds (always UTC)
        try {
            val isoFormatter = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss.SSS'Z'", Locale.US).apply {
                timeZone = TimeZone.getTimeZone("UTC")
            }
            return isoFormatter.parse(dateString)?.time
        } catch (e: Exception) {
            // Continue to next format
        }
        
        // Try ISO8601 format without fractional seconds (always UTC)
        try {
            val isoFormatter = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss'Z'", Locale.US).apply {
                timeZone = TimeZone.getTimeZone("UTC")
            }
            return isoFormatter.parse(dateString)?.time
        } catch (e: Exception) {
            // Continue to next format
        }
        
        // Try MySQL DATETIME format: "YYYY-MM-DD HH:MM:SS"
        // Server is in EST/EDT, so parse as EST/EDT first (America/New_York handles DST automatically)
        try {
            val mysqlFormatter = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.US).apply {
                timeZone = TimeZone.getTimeZone("America/New_York") // EST/EDT
            }
            val parsed = mysqlFormatter.parse(dateString)
            if (parsed != null) {
                return parsed.time
            }
        } catch (e: Exception) {
            // Continue to UTC fallback
        }
        
        // Fallback: Try parsing as UTC (in case server sends UTC timestamps)
        try {
            val mysqlFormatter = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.US).apply {
                timeZone = TimeZone.getTimeZone("UTC")
            }
            val parsed = mysqlFormatter.parse(dateString)
            if (parsed != null) {
                return parsed.time
            }
        } catch (e: Exception) {
            // Continue to next format
        }
        
        // Try MySQL DATETIME with microseconds: "YYYY-MM-DD HH:MM:SS.ffffff"
        // Server is in EST/EDT, so parse as EST/EDT first
        try {
            val mysqlMicroFormatter = SimpleDateFormat("yyyy-MM-dd HH:mm:ss.SSSSSS", Locale.US).apply {
                timeZone = TimeZone.getTimeZone("America/New_York") // EST/EDT
            }
            val parsed = mysqlMicroFormatter.parse(dateString)
            if (parsed != null) {
                return parsed.time
            }
        } catch (e: Exception) {
            // Continue to UTC fallback
        }
        
        // Fallback: Try parsing as UTC with microseconds
        try {
            val mysqlMicroFormatter = SimpleDateFormat("yyyy-MM-dd HH:mm:ss.SSSSSS", Locale.US).apply {
                timeZone = TimeZone.getTimeZone("UTC")
            }
            val parsed = mysqlMicroFormatter.parse(dateString)
            if (parsed != null) {
                return parsed.time
            }
        } catch (e: Exception) {
            // Return null if all formats fail
            return null
        }
        
        return null
    }
    
    /**
     * Format a timestamp to MySQL DATETIME format for sending to server.
     */
    fun formatServerDate(timestamp: Long): String {
        val formatter = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.US).apply {
            timeZone = TimeZone.getTimeZone("UTC")
        }
        return formatter.format(Date(timestamp))
    }
}

