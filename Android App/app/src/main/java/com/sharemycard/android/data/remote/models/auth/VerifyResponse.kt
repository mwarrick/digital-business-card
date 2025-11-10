package com.sharemycard.android.data.remote.models.auth

import com.google.gson.annotations.SerializedName

data class VerifyResponse(
    val token: String,
    @SerializedName("user_id") val userId: String,
    val email: String,
    @SerializedName("is_admin") val isAdmin: Boolean,
    @SerializedName("is_active") val isActive: Boolean,
    @SerializedName("verification_type") val verificationType: String?,
    @SerializedName("token_expires_in") val tokenExpiresIn: Int,
    val message: String?,
    @SerializedName("is_demo") val isDemo: Boolean? = null,
    val user: UserInfo? = null
)

data class UserInfo(
    val id: String,
    val email: String,
    @SerializedName("is_admin") val isAdmin: Boolean,
    @SerializedName("is_demo") val isDemo: Boolean? = null
)

