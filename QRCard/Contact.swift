//
//  Contact.swift
//  ShareMyCard
//
//  Contact data model and Core Data entity
//

import Foundation
import CoreData

// MARK: - Contact Data Model
struct Contact: Codable, Identifiable {
    let id: String
    let firstName: String
    let lastName: String
    let email: String?
    let phone: String?
    let mobilePhone: String?
    let company: String?
    let jobTitle: String?
    let address: String?
    let city: String?
    let state: String?
    let zipCode: String?
    let country: String?
    let website: String?
    let notes: String?
    let commentsFromLead: String?
    let birthdate: String?
    let photoUrl: String?
    let source: String?
    let sourceMetadata: String?
    let createdAt: String
    let updatedAt: String
    
    // Explicit memberwise initializer so other code (e.g., CoreData mapping)
    // can construct Contact instances without relying on synthesized init
    init(
        id: String,
        firstName: String,
        lastName: String,
        email: String?,
        phone: String?,
        mobilePhone: String?,
        company: String?,
        jobTitle: String?,
        address: String?,
        city: String?,
        state: String?,
        zipCode: String?,
        country: String?,
        website: String?,
        notes: String?,
        commentsFromLead: String?,
        birthdate: String?,
        photoUrl: String?,
        source: String?,
        sourceMetadata: String?,
        createdAt: String,
        updatedAt: String
    ) {
        self.id = id
        self.firstName = firstName
        self.lastName = lastName
        self.email = email
        self.phone = phone
        self.mobilePhone = mobilePhone
        self.company = company
        self.jobTitle = jobTitle
        self.address = address
        self.city = city
        self.state = state
        self.zipCode = zipCode
        self.country = country
        self.website = website
        self.notes = notes
        self.commentsFromLead = commentsFromLead
        self.birthdate = birthdate
        self.photoUrl = photoUrl
        self.source = source
        self.sourceMetadata = sourceMetadata
        self.createdAt = createdAt
        self.updatedAt = updatedAt
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
        email = try container.decodeIfPresent(String.self, forKey: .email)
        phone = try container.decodeIfPresent(String.self, forKey: .phone)
        mobilePhone = try container.decodeIfPresent(String.self, forKey: .mobilePhone)
        company = try container.decodeIfPresent(String.self, forKey: .company)
        jobTitle = try container.decodeIfPresent(String.self, forKey: .jobTitle)
        address = try container.decodeIfPresent(String.self, forKey: .address)
        city = try container.decodeIfPresent(String.self, forKey: .city)
        state = try container.decodeIfPresent(String.self, forKey: .state)
        zipCode = try container.decodeIfPresent(String.self, forKey: .zipCode)
        country = try container.decodeIfPresent(String.self, forKey: .country)
        website = try container.decodeIfPresent(String.self, forKey: .website)
        notes = try container.decodeIfPresent(String.self, forKey: .notes)
        commentsFromLead = try container.decodeIfPresent(String.self, forKey: .commentsFromLead)
        birthdate = try container.decodeIfPresent(String.self, forKey: .birthdate)
        photoUrl = try container.decodeIfPresent(String.self, forKey: .photoUrl)
        source = try container.decodeIfPresent(String.self, forKey: .source)
        sourceMetadata = try container.decodeIfPresent(String.self, forKey: .sourceMetadata)
        createdAt = try container.decode(String.self, forKey: .createdAt)
        updatedAt = try container.decode(String.self, forKey: .updatedAt)
    }
    
    enum CodingKeys: String, CodingKey {
        case id
        case firstName = "first_name"
        case lastName = "last_name"
        case email = "email_primary"
        case phone = "work_phone"
        case mobilePhone = "mobile_phone"
        case company = "organization_name"
        case jobTitle = "job_title"
        case address = "street_address"  // Database uses street_address
        case city = "city"
        case state = "state"  // Database uses state, not state_province
        case zipCode = "zip_code"  // Database uses zip_code, not postal_code
        case country = "country"
        case website = "website_url"
        case notes = "notes"
        case commentsFromLead = "comments_from_lead"
        case birthdate = "birthdate"
        case photoUrl = "photo_url"
        case source = "source"
        case sourceMetadata = "source_metadata"
        case createdAt = "created_at"
        case updatedAt = "updated_at"
    }
}

// MARK: - Contact Creation Data
struct ContactCreateData: Codable {
    let firstName: String
    let lastName: String
    let email: String?
    let phone: String?
    let mobilePhone: String?
    let company: String?
    let jobTitle: String?
    let address: String?
    let city: String?
    let state: String?
    let zipCode: String?
    let country: String?
    let website: String?
    let notes: String?
    let commentsFromLead: String?
    let birthdate: String?
    let photoUrl: String?
    let source: String?
    let sourceMetadata: String?
    
    enum CodingKeys: String, CodingKey {
        case firstName = "first_name"
        case lastName = "last_name"
        case email = "email_primary"
        case phone = "work_phone"  // Database uses work_phone
        case mobilePhone = "mobile_phone"
        case company = "organization_name"  // Database uses organization_name
        case jobTitle = "job_title"
        case address = "street_address"  // Database uses street_address
        case city = "city"
        case state = "state"  // Database uses state
        case zipCode = "zip_code"  // Database uses zip_code
        case country = "country"
        case website = "website_url"
        case notes = "notes"
        case commentsFromLead = "comments_from_lead"
        case birthdate = "birthdate"
        case photoUrl = "photo_url"
        case source = "source"
        case sourceMetadata = "source_metadata"
    }
}

// MARK: - Core Data Contact Entity
// Note: ContactEntity is defined in CoreDataEntities.swift

// MARK: - Contact Extensions
extension Contact {
    var fullName: String {
        return "\(firstName) \(lastName)".trimmingCharacters(in: .whitespaces)
    }
    
    var displayName: String {
        if fullName.isEmpty {
            return email ?? "Unknown Contact"
        }
        return fullName
    }
    
    /// Parse the createdAt date from ISO8601 string or MySQL DATETIME format
    var createdAtDate: Date? {
        guard !createdAt.isEmpty else { return nil }
        
        // Try ISO8601 format first (with and without fractional seconds)
        let isoFormatter = ISO8601DateFormatter()
        isoFormatter.formatOptions = [.withInternetDateTime, .withFractionalSeconds]
        if let date = isoFormatter.date(from: createdAt) {
            return date
        }
        
        isoFormatter.formatOptions = [.withInternetDateTime]
        if let date = isoFormatter.date(from: createdAt) {
            return date
        }
        
        // Try MySQL DATETIME format: "YYYY-MM-DD HH:MM:SS"
        let mysqlFormatter = DateFormatter()
        mysqlFormatter.dateFormat = "yyyy-MM-dd HH:mm:ss"
        mysqlFormatter.timeZone = TimeZone(secondsFromGMT: 0)
        if let date = mysqlFormatter.date(from: createdAt) {
            return date
        }
        
        // Try MySQL DATETIME with microseconds: "YYYY-MM-DD HH:MM:SS.ffffff"
        mysqlFormatter.dateFormat = "yyyy-MM-dd HH:mm:ss.SSSSSS"
        if let date = mysqlFormatter.date(from: createdAt) {
            return date
        }
        
        // Try ISO8601-like formats as fallback
        let flexibleFormatter = DateFormatter()
        flexibleFormatter.dateFormat = "yyyy-MM-dd'T'HH:mm:ss.SSSSSS'Z'"
        flexibleFormatter.timeZone = TimeZone(secondsFromGMT: 0)
        if let date = flexibleFormatter.date(from: createdAt) {
            return date
        }
        
        flexibleFormatter.dateFormat = "yyyy-MM-dd'T'HH:mm:ss'Z'"
        if let date = flexibleFormatter.date(from: createdAt) {
            return date
        }
        
        return nil
    }
    
    /// Parse the updatedAt date from ISO8601 string or MySQL DATETIME format
    var updatedAtDate: Date? {
        guard !updatedAt.isEmpty else { return nil }
        
        // Try ISO8601 format first (with and without fractional seconds)
        let isoFormatter = ISO8601DateFormatter()
        isoFormatter.formatOptions = [.withInternetDateTime, .withFractionalSeconds]
        if let date = isoFormatter.date(from: updatedAt) {
            return date
        }
        
        isoFormatter.formatOptions = [.withInternetDateTime]
        if let date = isoFormatter.date(from: updatedAt) {
            return date
        }
        
        // Try MySQL DATETIME format: "YYYY-MM-DD HH:MM:SS"
        let mysqlFormatter = DateFormatter()
        mysqlFormatter.dateFormat = "yyyy-MM-dd HH:mm:ss"
        mysqlFormatter.timeZone = TimeZone(secondsFromGMT: 0)
        if let date = mysqlFormatter.date(from: updatedAt) {
            return date
        }
        
        // Try MySQL DATETIME with microseconds: "YYYY-MM-DD HH:MM:SS.ffffff"
        mysqlFormatter.dateFormat = "yyyy-MM-dd HH:mm:ss.SSSSSS"
        if let date = mysqlFormatter.date(from: updatedAt) {
            return date
        }
        
        // Try ISO8601-like formats as fallback
        let flexibleFormatter = DateFormatter()
        flexibleFormatter.dateFormat = "yyyy-MM-dd'T'HH:mm:ss.SSSSSS'Z'"
        flexibleFormatter.timeZone = TimeZone(secondsFromGMT: 0)
        if let date = flexibleFormatter.date(from: updatedAt) {
            return date
        }
        
        flexibleFormatter.dateFormat = "yyyy-MM-dd'T'HH:mm:ss'Z'"
        if let date = flexibleFormatter.date(from: updatedAt) {
            return date
        }
        
        return nil
    }
    
    /// Formatted created date string for display
    var formattedCreatedDate: String {
        guard let date = createdAtDate else { return "" }
        let formatter = DateFormatter()
        formatter.dateStyle = .medium
        formatter.timeStyle = .short
        return formatter.string(from: date)
    }
    
    /// Formatted updated date string for display
    var formattedUpdatedDate: String {
        guard let date = updatedAtDate else { return "" }
        let formatter = DateFormatter()
        formatter.dateStyle = .medium
        formatter.timeStyle = .short
        return formatter.string(from: date)
    }
    
    /// Check if contact was updated (updatedAt is different from createdAt)
    var wasUpdated: Bool {
        guard let created = createdAtDate, let updated = updatedAtDate else { return false }
        return abs(updated.timeIntervalSince(created)) > 60 // More than 1 minute difference
    }
}

// Note: ContactEntity extensions are defined in CoreDataEntities.swift