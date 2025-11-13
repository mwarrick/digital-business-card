package com.sharemycard.android.data.remote.mapper

import com.sharemycard.android.data.remote.models.ContactDTO
import com.sharemycard.android.domain.models.Contact
import java.text.SimpleDateFormat
import java.util.*

object ContactDtoMapper {
    
    private val dateFormat = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.US).apply {
        timeZone = TimeZone.getTimeZone("UTC")
    }
    
    /**
     * Convert domain model to DTO for pushing to server.
     * userId is set to null as the server will extract it from the JWT token.
     */
    fun toDto(contact: Contact): ContactDTO {
        return ContactDTO(
            id = contact.id, // Use contact ID (may be local UUID for new contacts)
            firstName = contact.firstName,
            lastName = contact.lastName,
            emailPrimary = contact.email,
            phone = contact.phone,
            mobilePhone = contact.mobilePhone,
            workPhone = contact.phone, // Use phone as workPhone if available
            company = contact.company,
            organizationName = contact.company,
            jobTitle = contact.jobTitle,
            address = contact.address,
            streetAddress = contact.address,
            city = contact.city,
            state = contact.state,
            zipCode = contact.zipCode,
            country = contact.country,
            websiteUrl = contact.website,
            notes = contact.notes,
            commentsFromLead = contact.commentsFromLead,
            birthdate = contact.birthdate,
            photoUrl = contact.photoUrl,
            userId = null, // Server will get this from JWT token
            leadId = contact.leadId,
            source = contact.source,
            sourceMetadata = contact.sourceMetadata,
            sourceType = contact.source,
            createdAt = contact.createdAt,
            updatedAt = contact.updatedAt,
            isDeleted = if (contact.isDeleted) 1 else 0
        )
    }
    
    fun toDomain(dto: ContactDTO): Contact {
        val contactId = dto.id ?: UUID.randomUUID().toString()
        
        // Determine source - prioritize explicit source field, then sourceType, then leadId
        // Only mark as "converted" if leadId is a valid non-zero value
        val source = when {
            dto.source != null && dto.source.isNotBlank() -> dto.source
            dto.sourceType == "converted" && dto.leadId != null && dto.leadId != "0" && dto.leadId.isNotBlank() -> "converted"
            dto.leadId != null && dto.leadId != "0" && dto.leadId.isNotBlank() && dto.leadId.toIntOrNull()?.let { it > 0 } == true -> "converted"
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
            leadId = dto.leadId,
            createdAt = dto.createdAt ?: "",
            updatedAt = dto.updatedAt ?: dto.createdAt ?: "",
            isDeleted = dto.isDeleted == 1
        )
    }
}

