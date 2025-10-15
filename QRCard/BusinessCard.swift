//
//  BusinessCard.swift
//  ShareMyCard
//
//  Created by Mark Warrick on 10/10/25.
//

import Foundation
import SwiftUI

// MARK: - BusinessCard Data Model
struct BusinessCard: Identifiable, Codable {
    let id: UUID
    
    // MARK: - Required Fields
    var firstName: String
    var lastName: String
    var phoneNumber: String
    
    // MARK: - Optional Personal Information
    var additionalEmails: [EmailContact] = []
    var additionalPhones: [PhoneContact] = []
    var websiteLinks: [WebsiteLink] = []
    var address: Address?
    var companyName: String?
    var jobTitle: String?
    var bio: String?
    
    // MARK: - Media Assets
    var profilePhoto: Data?
    var companyLogo: Data?
    var coverGraphic: Data?
    
    // Server image paths (filenames)
    var profilePhotoPath: String?
    var companyLogoPath: String?
    var coverGraphicPath: String?
    
    // MARK: - Metadata
    var createdAt: Date
    var updatedAt: Date
    var isActive: Bool
    // Server ID for tracking-enabled URLs
    var serverCardId: String?
    
    // MARK: - Computed Properties
    var fullName: String {
        return "\(firstName) \(lastName)"
    }
    
    var primaryEmail: EmailContact? {
        return additionalEmails.first { $0.isPrimary } ?? additionalEmails.first { $0.type == .work } ?? additionalEmails.first
    }
    
    var primaryPhone: String {
        return phoneNumber
    }
    
    var primaryWebsite: WebsiteLink? {
        return websiteLinks.first { $0.isPrimary } ?? websiteLinks.first
    }
    
    // MARK: - Initializer
    init(firstName: String, lastName: String, phoneNumber: String) {
        self.id = UUID()
        self.firstName = firstName
        self.lastName = lastName
        self.phoneNumber = phoneNumber
        self.createdAt = Date()
        self.updatedAt = Date()
        self.isActive = true
    }
}

// MARK: - Supporting Data Models

struct EmailContact: Identifiable, Codable {
    let id: UUID
    var email: String
    var type: EmailType
    var label: String?
    var isPrimary: Bool
    
    init(email: String, type: EmailType, label: String? = nil, isPrimary: Bool = false) {
        self.id = UUID()
        self.email = email
        self.type = type
        self.label = label
        self.isPrimary = isPrimary
    }
    
    init(id: UUID, email: String, type: EmailType, label: String? = nil, isPrimary: Bool = false) {
        self.id = id
        self.email = email
        self.type = type
        self.label = label
        self.isPrimary = isPrimary
    }
}

enum EmailType: String, CaseIterable, Codable {
    case personal = "personal"
    case work = "work"
    case other = "other"
    
    var displayName: String {
        switch self {
        case .personal: return "Personal"
        case .work: return "Work"
        case .other: return "Other"
        }
    }
}

struct PhoneContact: Identifiable, Codable {
    let id: UUID
    var phoneNumber: String
    var type: PhoneType
    var label: String?
    
    init(phoneNumber: String, type: PhoneType, label: String? = nil) {
        self.id = UUID()
        self.phoneNumber = phoneNumber
        self.type = type
        self.label = label
    }
    
    init(id: UUID, phoneNumber: String, type: PhoneType, label: String? = nil) {
        self.id = id
        self.phoneNumber = phoneNumber
        self.type = type
        self.label = label
    }
}

enum PhoneType: String, CaseIterable, Codable {
    case mobile = "mobile"
    case home = "home"
    case work = "work"
    case other = "other"
    
    var displayName: String {
        switch self {
        case .mobile: return "Mobile"
        case .home: return "Home"
        case .work: return "Work"
        case .other: return "Other"
        }
    }
}

struct WebsiteLink: Identifiable, Codable {
    let id: UUID
    var name: String
    var url: String
    var description: String?
    var isPrimary: Bool
    
    init(name: String, url: String, description: String? = nil, isPrimary: Bool = false) {
        self.id = UUID()
        self.name = name
        self.url = url
        self.description = description
        self.isPrimary = isPrimary
    }
    
    init(id: UUID, name: String, url: String, description: String? = nil, isPrimary: Bool = false) {
        self.id = id
        self.name = name
        self.url = url
        self.description = description
        self.isPrimary = isPrimary
    }
}

struct Address: Codable {
    var street: String?
    var city: String?
    var state: String?
    var zipCode: String?
    var country: String?
    
    var fullAddress: String {
        var components: [String] = []
        
        if let street = street, !street.isEmpty { components.append(street) }
        if let city = city, !city.isEmpty { components.append(city) }
        if let state = state, !state.isEmpty { components.append(state) }
        if let zipCode = zipCode, !zipCode.isEmpty { components.append(zipCode) }
        if let country = country, !country.isEmpty { components.append(country) }
        
        return components.joined(separator: ", ")
    }
}

// MARK: - Sample Data for Testing
extension BusinessCard {
    static let sampleData: [BusinessCard] = [
        BusinessCard(firstName: "Mark", lastName: "Warrick", phoneNumber: "+1 (555) 123-4567")
            .with {
                $0.additionalEmails = [
                    EmailContact(email: "mark@warrick.net", type: .work),
                    EmailContact(email: "mark.personal@email.com", type: .personal)
                ]
                $0.additionalPhones = [
                    PhoneContact(phoneNumber: "+1 (555) 987-6543", type: .work)
                ]
                $0.websiteLinks = [
                    WebsiteLink(name: "Portfolio", url: "https://markwarrick.com"),
                    WebsiteLink(name: "LinkedIn", url: "https://linkedin.com/in/markwarrick")
                ]
                $0.companyName = "ShareMyCard Development"
                $0.jobTitle = "iOS Developer"
                $0.bio = "Passionate about creating innovative digital solutions. Check out my work at markwarrick.com"
                $0.address = Address(
                    street: "123 Developer Lane",
                    city: "Tech City",
                    state: "CA",
                    zipCode: "90210",
                    country: "USA"
                )
            }
    ]
}

// MARK: - Helper Extension for Sample Data
private extension BusinessCard {
    func with(_ configure: (inout BusinessCard) -> Void) -> BusinessCard {
        var copy = self
        configure(&copy)
        return copy
    }
}
