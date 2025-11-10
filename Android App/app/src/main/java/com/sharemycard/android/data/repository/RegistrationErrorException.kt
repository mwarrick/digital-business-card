package com.sharemycard.android.data.repository

/**
 * Custom exception for registration errors that includes account status information
 */
class RegistrationErrorException(
    message: String,
    val accountStatus: String, // "verified" or "unverified"
    val hasPassword: Boolean
) : Exception(message)

