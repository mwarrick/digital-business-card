package com.sharemycard.android.domain.models

data class WebsiteLink(
    val id: String,
    var name: String,
    var url: String,
    var description: String? = null,
    var isPrimary: Boolean = false
)

