package com.sharemycard.android.data.remote.models.auth

import com.google.gson.annotations.SerializedName

data class RegisterResponse(
    @SerializedName("user_id") val userId: String,
    val email: String,
    val message: String
)

