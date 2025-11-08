package com.sharemycard.android.data.remote.mapper

import com.sharemycard.android.data.remote.models.LeadDTO
import com.sharemycard.android.domain.models.Lead

object LeadDtoMapper {
    
    fun toDomain(dto: LeadDTO): Lead {
        val leadId = dto.id ?: ""
        
        return Lead(
            id = leadId,
            firstName = dto.firstName ?: "",
            lastName = dto.lastName ?: "",
            fullName = dto.fullName,
            emailPrimary = dto.emailPrimary ?: dto.email,
            workPhone = dto.workPhone,
            mobilePhone = dto.mobilePhone ?: dto.phone,
            streetAddress = dto.streetAddress ?: dto.address,
            city = dto.city,
            state = dto.state,
            zipCode = dto.zipCode,
            country = dto.country,
            organizationName = dto.organizationName ?: dto.company,
            jobTitle = dto.jobTitle ?: dto.title,
            birthdate = dto.birthdate,
            websiteUrl = dto.websiteUrl,
            photoUrl = dto.photoUrl,
            commentsFromLead = dto.commentsFromLead,
            createdAt = dto.createdAt,
            updatedAt = dto.updatedAt,
            cardFirstName = dto.cardFirstName,
            cardLastName = dto.cardLastName,
            cardCompany = dto.cardCompany,
            cardJobTitle = dto.cardJobTitle,
            qrTitle = dto.qrTitle,
            qrType = dto.qrType,
            status = dto.status
        )
    }
}

