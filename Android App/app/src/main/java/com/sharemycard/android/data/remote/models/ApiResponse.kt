package com.sharemycard.android.data.remote.models

import com.google.gson.annotations.SerializedName

/**
 * Generic API response wrapper
 * Handles both boolean and integer success fields
 */
data class ApiResponse<T>(
    @SerializedName("success") 
    val success: Any?, // Can be Boolean or Int (1/0)
    
    @SerializedName("message") 
    val message: String? = null,
    
    @SerializedName("data") 
    val data: T? = null,
    
    @SerializedName("errors") 
    val errors: List<String>? = null,
    
    // Additional error data (used for registration errors with account status)
    @SerializedName("account_status")
    val accountStatus: String? = null,
    
    @SerializedName("has_password")
    val hasPassword: Boolean? = null
) {
    /**
     * Check if the response is successful
     * Handles both boolean (true) and integer (1) success values
     */
    val isSuccess: Boolean
        get() = when (success) {
            is Boolean -> success
            is Int -> success == 1
            is Number -> success.toInt() == 1
            else -> false
        }
}

