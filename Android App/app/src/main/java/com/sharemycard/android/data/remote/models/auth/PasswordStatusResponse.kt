package com.sharemycard.android.data.remote.models.auth

import com.google.gson.annotations.SerializedName

data class PasswordStatusResponse(
    @SerializedName("has_password") val hasPassword: Boolean
)

