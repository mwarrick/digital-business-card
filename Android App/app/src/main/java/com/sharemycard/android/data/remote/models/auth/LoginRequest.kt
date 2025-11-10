package com.sharemycard.android.data.remote.models.auth

import com.google.gson.annotations.SerializedName

data class LoginRequest(
    val email: String,
    @SerializedName("force_email_code") val forceEmailCode: Boolean = false
)

