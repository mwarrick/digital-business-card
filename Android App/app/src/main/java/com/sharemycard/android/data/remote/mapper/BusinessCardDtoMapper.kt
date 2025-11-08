package com.sharemycard.android.data.remote.mapper

import com.sharemycard.android.data.remote.models.*
import com.sharemycard.android.domain.models.*
import java.text.SimpleDateFormat
import java.util.*

object BusinessCardDtoMapper {
    
    private val dateFormat = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.US).apply {
        timeZone = TimeZone.getTimeZone("UTC")
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
        if (dateString.isNullOrBlank()) return null
        
        return try {
            dateFormat.parse(dateString)?.time
        } catch (e: Exception) {
            // Try ISO format
            try {
                val isoFormat = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss.SSS'Z'", Locale.US).apply {
                    timeZone = TimeZone.getTimeZone("UTC")
                }
                isoFormat.parse(dateString)?.time
            } catch (e2: Exception) {
                null
            }
        }
    }
}

