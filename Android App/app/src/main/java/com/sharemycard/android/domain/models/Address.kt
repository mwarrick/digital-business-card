package com.sharemycard.android.domain.models

data class Address(
    var street: String? = null,
    var city: String? = null,
    var state: String? = null,
    var zipCode: String? = null,
    var country: String? = null
) {
    val fullAddress: String
        get() = listOfNotNull(street, city, state, zipCode, country)
            .filter { it.isNotEmpty() }
            .joinToString(", ")
}

