package com.sharemycard.android.domain.models

enum class EmailType {
    PERSONAL, WORK, OTHER
}

data class EmailContact(
    val id: String,
    var email: String,
    var type: EmailType,
    var label: String? = null,
    var isPrimary: Boolean = false
)

