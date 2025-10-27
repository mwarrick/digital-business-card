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
}

// Note: ContactEntity extensions are defined in CoreDataEntities.swift