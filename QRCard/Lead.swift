//
//  Lead.swift
//  ShareMyCard
//
//  Lead data model matching the API structure
//

import Foundation

// MARK: - Lead Data Model
struct Lead: Codable, Identifiable {
    let id: String
    let firstName: String
    let lastName: String
    let fullName: String?
    let emailPrimary: String?
    let workPhone: String?
    let mobilePhone: String?
    let streetAddress: String?
    let city: String?
    let state: String?
    let zipCode: String?
    let country: String?
    let organizationName: String?
    let jobTitle: String?
    let birthdate: String?
    let websiteUrl: String?
    let photoUrl: String?
    let commentsFromLead: String?
    let createdAt: String?
    let updatedAt: String?
    
    // Business card information (from join)
    let cardFirstName: String?
    let cardLastName: String?
    let cardCompany: String?
    let cardJobTitle: String?
    
    // Custom QR code information (from join)
    let qrTitle: String?
    let qrType: String?
    
    // Status
    let status: String? // "new" or "converted"
    
    enum CodingKeys: String, CodingKey {
        case id
        case firstName = "first_name"
        case lastName = "last_name"
        case fullName = "full_name"
        case emailPrimary = "email_primary"
        case workPhone = "work_phone"
        case mobilePhone = "mobile_phone"
        case streetAddress = "street_address"
        case city
        case state
        case zipCode = "zip_code"
        case country
        case organizationName = "organization_name"
        case jobTitle = "job_title"
        case birthdate
        case websiteUrl = "website_url"
        case photoUrl = "photo_url"
        case commentsFromLead = "comments_from_lead"
        case createdAt = "created_at"
        case updatedAt = "updated_at"
        case cardFirstName = "card_first_name"
        case cardLastName = "card_last_name"
        case cardCompany = "card_company"
        case cardJobTitle = "card_job_title"
        case qrTitle = "qr_title"
        case qrType = "qr_type"
        case status
    }
    
    // Custom decoder to handle integer IDs from server
    init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)
        
        // Handle id as either String or Int
        if let idString = try? container.decode(String.self, forKey: .id) {
            id = idString
        } else if let idInt = try? container.decode(Int.self, forKey: .id) {
            id = String(idInt)
        } else {
            throw DecodingError.typeMismatch(String.self, DecodingError.Context(codingPath: [CodingKeys.id], debugDescription: "Expected String or Int for id"))
        }
        
        firstName = try container.decode(String.self, forKey: .firstName)
        lastName = try container.decode(String.self, forKey: .lastName)
        fullName = try container.decodeIfPresent(String.self, forKey: .fullName)
        emailPrimary = try container.decodeIfPresent(String.self, forKey: .emailPrimary)
        workPhone = try container.decodeIfPresent(String.self, forKey: .workPhone)
        mobilePhone = try container.decodeIfPresent(String.self, forKey: .mobilePhone)
        streetAddress = try container.decodeIfPresent(String.self, forKey: .streetAddress)
        city = try container.decodeIfPresent(String.self, forKey: .city)
        state = try container.decodeIfPresent(String.self, forKey: .state)
        zipCode = try container.decodeIfPresent(String.self, forKey: .zipCode)
        country = try container.decodeIfPresent(String.self, forKey: .country)
        organizationName = try container.decodeIfPresent(String.self, forKey: .organizationName)
        jobTitle = try container.decodeIfPresent(String.self, forKey: .jobTitle)
        birthdate = try container.decodeIfPresent(String.self, forKey: .birthdate)
        websiteUrl = try container.decodeIfPresent(String.self, forKey: .websiteUrl)
        photoUrl = try container.decodeIfPresent(String.self, forKey: .photoUrl)
        commentsFromLead = try container.decodeIfPresent(String.self, forKey: .commentsFromLead)
        createdAt = try container.decodeIfPresent(String.self, forKey: .createdAt)
        updatedAt = try container.decodeIfPresent(String.self, forKey: .updatedAt)
        cardFirstName = try container.decodeIfPresent(String.self, forKey: .cardFirstName)
        cardLastName = try container.decodeIfPresent(String.self, forKey: .cardLastName)
        cardCompany = try container.decodeIfPresent(String.self, forKey: .cardCompany)
        cardJobTitle = try container.decodeIfPresent(String.self, forKey: .cardJobTitle)
        qrTitle = try container.decodeIfPresent(String.self, forKey: .qrTitle)
        qrType = try container.decodeIfPresent(String.self, forKey: .qrType)
        status = try container.decodeIfPresent(String.self, forKey: .status)
    }
    
    // Manual initializer for preview/testing
    init(
        id: String,
        firstName: String,
        lastName: String,
        fullName: String?,
        emailPrimary: String?,
        workPhone: String?,
        mobilePhone: String?,
        streetAddress: String?,
        city: String?,
        state: String?,
        zipCode: String?,
        country: String?,
        organizationName: String?,
        jobTitle: String?,
        birthdate: String?,
        websiteUrl: String?,
        photoUrl: String?,
        commentsFromLead: String?,
        createdAt: String?,
        updatedAt: String?,
        cardFirstName: String?,
        cardLastName: String?,
        cardCompany: String?,
        cardJobTitle: String?,
        qrTitle: String?,
        qrType: String?,
        status: String?
    ) {
        self.id = id
        self.firstName = firstName
        self.lastName = lastName
        self.fullName = fullName
        self.emailPrimary = emailPrimary
        self.workPhone = workPhone
        self.mobilePhone = mobilePhone
        self.streetAddress = streetAddress
        self.city = city
        self.state = state
        self.zipCode = zipCode
        self.country = country
        self.organizationName = organizationName
        self.jobTitle = jobTitle
        self.birthdate = birthdate
        self.websiteUrl = websiteUrl
        self.photoUrl = photoUrl
        self.commentsFromLead = commentsFromLead
        self.createdAt = createdAt
        self.updatedAt = updatedAt
        self.cardFirstName = cardFirstName
        self.cardLastName = cardLastName
        self.cardCompany = cardCompany
        self.cardJobTitle = cardJobTitle
        self.qrTitle = qrTitle
        self.qrType = qrType
        self.status = status
    }
}

// MARK: - Lead Extensions
extension Lead {
    var displayName: String {
        if let fullName = fullName, !fullName.isEmpty {
            return fullName
        }
        return "\(firstName) \(lastName)".trimmingCharacters(in: .whitespaces)
    }
    
    var isConverted: Bool {
        return status == "converted"
    }
    
    var cardDisplayName: String {
        // If from business card, show card owner name
        if let cardFirstName = cardFirstName, let cardLastName = cardLastName {
            return "\(cardFirstName) \(cardLastName)"
        }
        
        // If from custom QR code, show QR title/type
        if let qrTitle = qrTitle, !qrTitle.isEmpty {
            let qrTypeLabel = qrType?.capitalized ?? "Custom"
            return "QR \(qrTypeLabel): \(qrTitle)"
        } else if let qrType = qrType {
            return "QR \(qrType.capitalized)"
        }
        
        return "Unknown Card"
    }
}

