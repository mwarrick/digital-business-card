package com.sharemycard.android.data.remote.models

import com.google.gson.annotations.SerializedName

data class LeadDTO(
    val id: String?,
    @SerializedName("first_name")
    val firstName: String?,
    @SerializedName("last_name")
    val lastName: String?,
    @SerializedName("full_name")
    val fullName: String? = null,
    val email: String? = null,
    @SerializedName("email_primary")
    val emailPrimary: String? = null,
    val phone: String? = null,
    @SerializedName("work_phone")
    val workPhone: String? = null,
    @SerializedName("mobile_phone")
    val mobilePhone: String? = null,
    val company: String? = null,
    @SerializedName("organization_name")
    val organizationName: String? = null,
    val title: String? = null,
    @SerializedName("job_title")
    val jobTitle: String? = null,
    val address: String? = null,
    @SerializedName("street_address")
    val streetAddress: String? = null,
    val city: String? = null,
    val state: String? = null,
    @SerializedName("zip_code")
    val zipCode: String? = null,
    val country: String? = null,
    @SerializedName("website_url")
    val websiteUrl: String? = null,
    val notes: String? = null,
    @SerializedName("comments_from_lead")
    val commentsFromLead: String? = null,
    val birthdate: String? = null,
    @SerializedName("photo_url")
    val photoUrl: String? = null,
    @SerializedName("id_business_card")
    val businessCardId: String? = null,
    @SerializedName("id_user")
    val userId: String? = null,
    @SerializedName("id_lead")
    val leadId: String? = null,
    // Business card information (from join)
    @SerializedName("card_first_name")
    val cardFirstName: String? = null,
    @SerializedName("card_last_name")
    val cardLastName: String? = null,
    @SerializedName("card_company")
    val cardCompany: String? = null,
    @SerializedName("card_job_title")
    val cardJobTitle: String? = null,
    // Custom QR code information (from join)
    @SerializedName("qr_title")
    val qrTitle: String? = null,
    @SerializedName("qr_type")
    val qrType: String? = null,
    // Status
    val status: String? = null, // "new" or "converted"
    @SerializedName("created_at")
    val createdAt: String?,
    @SerializedName("updated_at")
    val updatedAt: String? = null,
    @SerializedName("is_deleted")
    val isDeleted: Int? = 0 // 1 = deleted, 0 = not deleted
)

