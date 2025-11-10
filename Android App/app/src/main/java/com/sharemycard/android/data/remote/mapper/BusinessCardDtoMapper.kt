package com.sharemycard.android.data.remote.mapper

import com.sharemycard.android.data.remote.models.*
import com.sharemycard.android.domain.models.*
import com.sharemycard.android.util.DateParser
import java.text.SimpleDateFormat
import java.util.*

object BusinessCardDtoMapper {
    
    private val dateFormat = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.US).apply {
        timeZone = TimeZone.getTimeZone("UTC")
    }
    
    /**
     * Convert domain model to DTO for pushing to server.
     * userId is set to null as the server will extract it from the JWT token.
     */
    fun toDto(card: BusinessCard): BusinessCardDTO {
        return BusinessCardDTO(
            id = card.serverCardId, // Use serverCardId if available, null for new cards
            userId = null, // Server will get this from JWT token
            firstName = card.firstName,
            lastName = card.lastName,
            phoneNumber = card.phoneNumber,
            companyName = card.companyName,
            jobTitle = card.jobTitle,
            bio = card.bio,
            emails = card.additionalEmails.map { email ->
                EmailContactDTO(
                    id = email.id.takeIf { it.isNotEmpty() },
                    email = email.email,
                    type = when (email.type) {
                        EmailType.WORK -> "work"
                        EmailType.PERSONAL -> "personal"
                        EmailType.OTHER -> "other"
                    },
                    label = email.label,
                    isPrimary = if (email.isPrimary) 1 else 0
                )
            },
            phones = card.additionalPhones.map { phone ->
                PhoneContactDTO(
                    id = phone.id.takeIf { it.isNotEmpty() },
                    phoneNumber = phone.phoneNumber,
                    type = when (phone.type) {
                        PhoneType.MOBILE -> "mobile"
                        PhoneType.WORK -> "work"
                        PhoneType.HOME -> "home"
                        PhoneType.OTHER -> "other"
                    },
                    label = phone.label
                )
            },
            websites = card.websiteLinks.map { website ->
                WebsiteLinkDTO(
                    id = website.id.takeIf { it.isNotEmpty() },
                    url = website.url,
                    name = website.name,
                    description = website.description
                )
            },
            address = card.address?.let { addr ->
                AddressDTO(
                    id = null, // Address IDs are not used in domain model
                    street = addr.street,
                    city = addr.city,
                    state = addr.state,
                    postalCode = addr.zipCode,
                    country = addr.country
                )
            },
            isActive = if (card.isActive) 1 else 0,
            createdAt = DateParser.formatServerDate(card.createdAt),
            updatedAt = DateParser.formatServerDate(card.updatedAt),
            profilePhotoPath = card.profilePhotoPath,
            companyLogoPath = card.companyLogoPath,
            coverGraphicPath = card.coverGraphicPath,
            theme = card.theme
        )
    }
    
    fun toDomain(dto: BusinessCardDTO): BusinessCard {
        val cardId = dto.id ?: UUID.randomUUID().toString()
        
        return BusinessCard(
            id = cardId,
            firstName = dto.firstName ?: "",
            lastName = dto.lastName ?: "",
            phoneNumber = dto.phoneNumber ?: "",
            additionalEmails = dto.emails?.map { emailDto ->
                EmailContact(
                    id = emailDto.id ?: UUID.randomUUID().toString(),
                    email = emailDto.email,
                    type = when (emailDto.type?.lowercase()) {
                        "work" -> EmailType.WORK
                        "personal" -> EmailType.PERSONAL
                        else -> EmailType.OTHER
                    },
                    label = emailDto.label,
                    isPrimary = emailDto.isPrimary == 1
                )
            } ?: emptyList(),
            additionalPhones = dto.phones?.map { phoneDto ->
                PhoneContact(
                    id = phoneDto.id ?: UUID.randomUUID().toString(),
                    phoneNumber = phoneDto.phoneNumber,
                    type = when (phoneDto.type?.lowercase()) {
                        "mobile" -> PhoneType.MOBILE
                        "work" -> PhoneType.WORK
                        "home" -> PhoneType.HOME
                        else -> PhoneType.OTHER
                    },
                    label = phoneDto.label
                )
            } ?: emptyList(),
            websiteLinks = dto.websites?.mapNotNull { websiteDto ->
                websiteDto.url?.let { url ->
                    WebsiteLink(
                        id = websiteDto.id ?: UUID.randomUUID().toString(),
                        url = url,
                        name = websiteDto.name ?: "",
                        description = websiteDto.description
                    )
                }
            } ?: emptyList(),
            address = dto.address?.let { addressDto ->
                Address(
                    street = addressDto.street,
                    city = addressDto.city,
                    state = addressDto.state,
                    zipCode = addressDto.postalCode,
                    country = addressDto.country
                )
            },
            companyName = dto.companyName,
            jobTitle = dto.jobTitle,
            bio = dto.bio,
            profilePhotoPath = dto.profilePhotoPath,
            companyLogoPath = dto.companyLogoPath,
            coverGraphicPath = dto.coverGraphicPath,
            theme = dto.theme,
            createdAt = parseDate(dto.createdAt) ?: System.currentTimeMillis(),
            updatedAt = parseDate(dto.updatedAt) ?: System.currentTimeMillis(),
            isActive = dto.isActive == 1,
            serverCardId = dto.id
        )
    }
    
    private fun parseDate(dateString: String?): Long? {
        // Use DateParser utility for consistent date parsing
        return DateParser.parseServerDate(dateString)
    }
}

