package com.sharemycard.android.data.remote.models.auth

import com.google.gson.annotations.SerializedName

data class PasswordChangeRequest(
    val email: String,
    @SerializedName("current_password") val currentPassword: String,
    @SerializedName("new_password") val newPassword: String
)

