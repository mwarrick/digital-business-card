package com.sharemycard.android.data.remote.models.auth

data class VerifyRequest(
    val email: String,
    val code: String? = null,
    val password: String? = null
)

