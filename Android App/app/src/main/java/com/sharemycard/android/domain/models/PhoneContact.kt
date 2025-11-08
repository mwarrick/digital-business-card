package com.sharemycard.android.domain.models

enum class PhoneType {
    MOBILE, HOME, WORK, OTHER
}

data class PhoneContact(
    val id: String,
    var phoneNumber: String,
    var type: PhoneType,
    var label: String? = null
)

