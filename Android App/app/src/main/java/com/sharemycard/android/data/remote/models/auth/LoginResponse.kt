package com.sharemycard.android.data.remote.models.auth

import com.google.gson.annotations.SerializedName

data class LoginResponse(
    @SerializedName("user_id") val userId: String,
    val email: String,
    @SerializedName("is_admin") val isAdmin: Boolean,
    @SerializedName("has_password") val hasPassword: Boolean,
    @SerializedName("verification_code_sent") val verificationCodeSent: Boolean,
    @SerializedName("is_demo") val isDemo: Boolean? = null
)

