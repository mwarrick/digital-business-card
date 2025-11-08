package com.sharemycard.android.data.remote.mapper

import com.sharemycard.android.data.remote.models.ContactDTO
import com.sharemycard.android.domain.models.Contact
import java.text.SimpleDateFormat
import java.util.*

object ContactDtoMapper {
    
    private val dateFormat = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.US).apply {
        timeZone = TimeZone.getTimeZone("UTC")
    }
    
    fun toDomain(dto: ContactDTO): Contact {
        val contactId = dto.id ?: UUID.randomUUID().toString()
        
        // Determine source
        val source = when {
            dto.sourceType == "converted" -> "converted"
            dto.leadId != null -> "converted"
            dto.source != null -> dto.source
            else -> "manual"
        }
        
        return Contact(
            id = contactId,
            firstName = dto.firstName ?: "",
            lastName = dto.lastName ?: "",
            email = dto.emailPrimary,
            phone = dto.workPhone ?: dto.phone,
            mobilePhone = dto.mobilePhone,
            company = dto.organizationName ?: dto.company,
            jobTitle = dto.jobTitle,
            address = dto.streetAddress ?: dto.address,
            city = dto.city,
            state = dto.state,
            zipCode = dto.zipCode,
            country = dto.country,
            website = dto.websiteUrl,
            notes = dto.notes,
            commentsFromLead = dto.commentsFromLead,
            birthdate = dto.birthdate,
            photoUrl = dto.photoUrl,
            source = source,
            sourceMetadata = dto.sourceMetadata,
            createdAt = dto.createdAt ?: "",
            updatedAt = dto.updatedAt ?: dto.createdAt ?: ""
        )
    }
}

