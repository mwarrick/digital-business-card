package com.sharemycard.android.data.remote.models

import com.google.gson.annotations.SerializedName

data class BusinessCardDTO(
    val id: String?,
    @SerializedName("user_id")
    val userId: String?,
    @SerializedName("first_name")
    val firstName: String?,
    @SerializedName("last_name")
    val lastName: String?,
    @SerializedName("phone_number")
    val phoneNumber: String?,
    @SerializedName("company_name")
    val companyName: String?,
    @SerializedName("job_title")
    val jobTitle: String?,
    val bio: String?,
    val emails: List<EmailContactDTO>? = null,
    val phones: List<PhoneContactDTO>? = null,
    val websites: List<WebsiteLinkDTO>? = null,
    val address: AddressDTO? = null,
    @SerializedName("is_active")
    val isActive: Int? = 1, // 1 = active, 0 = inactive
    @SerializedName("created_at")
    val createdAt: String?,
    @SerializedName("updated_at")
    val updatedAt: String?,
    @SerializedName("profile_photo_path")
    val profilePhotoPath: String? = null,
    @SerializedName("company_logo_path")
    val companyLogoPath: String? = null,
    @SerializedName("cover_graphic_path")
    val coverGraphicPath: String? = null,
    val theme: String? = null
)

data class EmailContactDTO(
    val id: String? = null,
    val email: String,
    val type: String? = "work", // work, personal, other
    val label: String? = null,
    @SerializedName("is_primary")
    val isPrimary: Int? = 0 // 1 = primary, 0 = not primary
)

data class PhoneContactDTO(
    val id: String? = null,
    @SerializedName("phone_number")
    val phoneNumber: String,
    val type: String? = "mobile", // mobile, work, home, other
    val label: String? = null
)

data class WebsiteLinkDTO(
    val id: String? = null,
    val url: String,
    val name: String? = null,
    val description: String? = null
)

data class AddressDTO(
    val id: String? = null,
    val street: String? = null,
    val city: String? = null,
    val state: String? = null,
    @SerializedName("postal_code")
    val postalCode: String? = null,
    val country: String? = null
)

