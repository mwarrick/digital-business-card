package com.sharemycard.android.domain.models

import java.util.UUID

data class BusinessCard(
    val id: String = UUID.randomUUID().toString(),
    var firstName: String,
    var lastName: String,
    var phoneNumber: String,
    var additionalEmails: List<EmailContact> = emptyList(),
    var additionalPhones: List<PhoneContact> = emptyList(),
    var websiteLinks: List<WebsiteLink> = emptyList(),
    var address: Address? = null,
    var companyName: String? = null,
    var jobTitle: String? = null,
    var bio: String? = null,
    var profilePhoto: ByteArray? = null,
    var companyLogo: ByteArray? = null,
    var coverGraphic: ByteArray? = null,
    var profilePhotoPath: String? = null,
    var companyLogoPath: String? = null,
    var coverGraphicPath: String? = null,
    var theme: String? = null,
    val createdAt: Long = System.currentTimeMillis(),
    var updatedAt: Long = System.currentTimeMillis(),
    var isActive: Boolean = true,
    var serverCardId: String? = null,
    var isDeleted: Boolean = false
) {
    val fullName: String get() = "$firstName $lastName"
    
    val primaryEmail: EmailContact?
        get() = additionalEmails.firstOrNull { it.isPrimary }
            ?: additionalEmails.firstOrNull { it.type == EmailType.WORK }
            ?: additionalEmails.firstOrNull()
    
    override fun equals(other: Any?): Boolean {
        if (this === other) return true
        if (javaClass != other?.javaClass) return false

        other as BusinessCard

        if (id != other.id) return false
        if (firstName != other.firstName) return false
        if (lastName != other.lastName) return false
        if (phoneNumber != other.phoneNumber) return false
        if (additionalEmails != other.additionalEmails) return false
        if (additionalPhones != other.additionalPhones) return false
        if (websiteLinks != other.websiteLinks) return false
        if (address != other.address) return false
        if (companyName != other.companyName) return false
        if (jobTitle != other.jobTitle) return false
        if (bio != other.bio) return false
        if (!profilePhoto.contentEquals(other.profilePhoto)) return false
        if (!companyLogo.contentEquals(other.companyLogo)) return false
        if (!coverGraphic.contentEquals(other.coverGraphic)) return false
        if (profilePhotoPath != other.profilePhotoPath) return false
        if (companyLogoPath != other.companyLogoPath) return false
        if (coverGraphicPath != other.coverGraphicPath) return false
        if (theme != other.theme) return false
        if (createdAt != other.createdAt) return false
        if (updatedAt != other.updatedAt) return false
        if (isActive != other.isActive) return false
        if (serverCardId != other.serverCardId) return false

        return true
    }

    override fun hashCode(): Int {
        var result = id.hashCode()
        result = 31 * result + firstName.hashCode()
        result = 31 * result + lastName.hashCode()
        result = 31 * result + phoneNumber.hashCode()
        result = 31 * result + additionalEmails.hashCode()
        result = 31 * result + additionalPhones.hashCode()
        result = 31 * result + websiteLinks.hashCode()
        result = 31 * result + (address?.hashCode() ?: 0)
        result = 31 * result + (companyName?.hashCode() ?: 0)
        result = 31 * result + (jobTitle?.hashCode() ?: 0)
        result = 31 * result + (bio?.hashCode() ?: 0)
        result = 31 * result + (profilePhoto?.contentHashCode() ?: 0)
        result = 31 * result + (companyLogo?.contentHashCode() ?: 0)
        result = 31 * result + (coverGraphic?.contentHashCode() ?: 0)
        result = 31 * result + (profilePhotoPath?.hashCode() ?: 0)
        result = 31 * result + (companyLogoPath?.hashCode() ?: 0)
        result = 31 * result + (coverGraphicPath?.hashCode() ?: 0)
        result = 31 * result + (theme?.hashCode() ?: 0)
        result = 31 * result + createdAt.hashCode()
        result = 31 * result + updatedAt.hashCode()
        result = 31 * result + isActive.hashCode()
        result = 31 * result + (serverCardId?.hashCode() ?: 0)
        return result
    }
}

