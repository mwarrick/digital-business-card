package com.sharemycard.android.data.remote.models.auth

data class PasswordResetCompleteRequest(
    val email: String,
    val code: String,
    val new_password: String
)

