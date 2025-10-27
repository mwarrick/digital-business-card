//
//  CoreDataEntities.swift
//  ShareMyCard
//
//  Created by Mark Warrick on 10/10/25.
//

import Foundation
import CoreData

// MARK: - BusinessCardEntity
@objc(BusinessCardEntity)
public class BusinessCardEntity: NSManagedObject {
    
}

extension BusinessCardEntity {
    @nonobjc public class func fetchRequest() -> NSFetchRequest<BusinessCardEntity> {
        return NSFetchRequest<BusinessCardEntity>(entityName: "BusinessCardEntity")
    }
    
    @NSManaged public var bio: String?
    @NSManaged public var companyLogo: Data?
    @NSManaged public var companyName: String?
    @NSManaged public var coverGraphic: Data?
    @NSManaged public var createdAt: Date?
    @NSManaged public var firstName: String?
    @NSManaged public var id: UUID?
    @NSManaged public var isActive: Bool
    @NSManaged public var jobTitle: String?
    @NSManaged public var lastName: String?
    @NSManaged public var phoneNumber: String?
    @NSManaged public var profilePhoto: Data?
    @NSManaged public var profilePhotoPath: String? // Server filename
    @NSManaged public var companyLogoPath: String? // Server filename
    @NSManaged public var coverGraphicPath: String? // Server filename
    @NSManaged public var theme: String? // Visual theme (e.g., "professional-blue")
    @NSManaged public var serverCardId: String? // ID from the API server
    @NSManaged public var updatedAt: Date?
    @NSManaged public var additionalEmails: NSSet?
    @NSManaged public var additionalPhones: NSSet?
    @NSManaged public var address: AddressEntity?
    @NSManaged public var websiteLinks: NSSet?
}

// MARK: Generated accessors for additionalEmails
extension BusinessCardEntity {
    @objc(addAdditionalEmailsObject:)
    @NSManaged public func addToAdditionalEmails(_ value: EmailContactEntity)
    
    @objc(removeAdditionalEmailsObject:)
    @NSManaged public func removeFromAdditionalEmails(_ value: EmailContactEntity)
    
    @objc(addAdditionalEmails:)
    @NSManaged public func addToAdditionalEmails(_ values: NSSet)
    
    @objc(removeAdditionalEmails:)
    @NSManaged public func removeFromAdditionalEmails(_ values: NSSet)
}

// MARK: Generated accessors for additionalPhones
extension BusinessCardEntity {
    @objc(addAdditionalPhonesObject:)
    @NSManaged public func addToAdditionalPhones(_ value: PhoneContactEntity)
    
    @objc(removeAdditionalPhonesObject:)
    @NSManaged public func removeFromAdditionalPhones(_ value: PhoneContactEntity)
    
    @objc(addAdditionalPhones:)
    @NSManaged public func addToAdditionalPhones(_ values: NSSet)
    
    @objc(removeAdditionalPhones:)
    @NSManaged public func removeFromAdditionalPhones(_ values: NSSet)
}

// MARK: Generated accessors for websiteLinks
extension BusinessCardEntity {
    @objc(addWebsiteLinksObject:)
    @NSManaged public func addToWebsiteLinks(_ value: WebsiteLinkEntity)
    
    @objc(removeWebsiteLinksObject:)
    @NSManaged public func removeFromWebsiteLinks(_ value: WebsiteLinkEntity)
    
    @objc(addWebsiteLinks:)
    @NSManaged public func addToWebsiteLinks(_ values: NSSet)
    
    @objc(removeWebsiteLinks:)
    @NSManaged public func removeFromWebsiteLinks(_ values: NSSet)
}

// MARK: - EmailContactEntity
@objc(EmailContactEntity)
public class EmailContactEntity: NSManagedObject {
    
}

extension EmailContactEntity {
    @nonobjc public class func fetchRequest() -> NSFetchRequest<EmailContactEntity> {
        return NSFetchRequest<EmailContactEntity>(entityName: "EmailContactEntity")
    }
    
    @NSManaged public var email: String?
    @NSManaged public var id: UUID?
    @NSManaged public var isPrimary: Bool
    @NSManaged public var label: String?
    @NSManaged public var type: String?
    @NSManaged public var businessCard: BusinessCardEntity?
}

// MARK: - PhoneContactEntity
@objc(PhoneContactEntity)
public class PhoneContactEntity: NSManagedObject {
    
}

extension PhoneContactEntity {
    @nonobjc public class func fetchRequest() -> NSFetchRequest<PhoneContactEntity> {
        return NSFetchRequest<PhoneContactEntity>(entityName: "PhoneContactEntity")
    }
    
    @NSManaged public var id: UUID?
    @NSManaged public var label: String?
    @NSManaged public var phoneNumber: String?
    @NSManaged public var type: String?
    @NSManaged public var businessCard: BusinessCardEntity?
}

// MARK: - WebsiteLinkEntity
@objc(WebsiteLinkEntity)
public class WebsiteLinkEntity: NSManagedObject {
    
}

extension WebsiteLinkEntity {
    @nonobjc public class func fetchRequest() -> NSFetchRequest<WebsiteLinkEntity> {
        return NSFetchRequest<WebsiteLinkEntity>(entityName: "WebsiteLinkEntity")
    }
    
    @NSManaged public var websiteDescription: String?
    @NSManaged public var id: UUID?
    @NSManaged public var isPrimary: Bool
    @NSManaged public var name: String?
    @NSManaged public var url: String?
    @NSManaged public var businessCard: BusinessCardEntity?
}

// MARK: - AddressEntity
@objc(AddressEntity)
public class AddressEntity: NSManagedObject {
    
}

extension AddressEntity {
    @nonobjc public class func fetchRequest() -> NSFetchRequest<AddressEntity> {
        return NSFetchRequest<AddressEntity>(entityName: "AddressEntity")
    }
    
    @NSManaged public var city: String?
    @NSManaged public var country: String?
    @NSManaged public var state: String?
    @NSManaged public var street: String?
    @NSManaged public var zipCode: String?
    @NSManaged public var businessCard: BusinessCardEntity?
}

// MARK: - ContactEntity
@objc(ContactEntity)
public class ContactEntity: NSManagedObject {
    
}

extension ContactEntity {
    @nonobjc public class func fetchRequest() -> NSFetchRequest<ContactEntity> {
        return NSFetchRequest<ContactEntity>(entityName: "ContactEntity")
    }
    
    @NSManaged public var id: String
    @NSManaged public var firstName: String
    @NSManaged public var lastName: String
    @NSManaged public var email: String?
    @NSManaged public var phone: String?
    @NSManaged public var mobilePhone: String?
    @NSManaged public var company: String?
    @NSManaged public var jobTitle: String?
    @NSManaged public var address: String?
    @NSManaged public var city: String?
    @NSManaged public var state: String?
    @NSManaged public var zipCode: String?
    @NSManaged public var country: String?
    @NSManaged public var website: String?
    @NSManaged public var notes: String?
    @NSManaged public var commentsFromLead: String?
    @NSManaged public var birthdate: String?
    @NSManaged public var photoUrl: String?
    @NSManaged public var source: String?
    @NSManaged public var sourceMetadata: String?
    @NSManaged public var createdAt: Date
    @NSManaged public var updatedAt: Date
    @NSManaged public var syncStatus: String
    @NSManaged public var lastSyncAt: Date?
}

// MARK: - ContactEntity Extensions
extension ContactEntity {
    func updateFromContact(_ contact: Contact) {
        self.id = contact.id
        self.firstName = contact.firstName
        self.lastName = contact.lastName
        self.email = contact.email
        self.phone = contact.phone
        self.mobilePhone = contact.mobilePhone
        self.company = contact.company
        self.jobTitle = contact.jobTitle
        self.address = contact.address
        self.city = contact.city
        self.state = contact.state
        self.zipCode = contact.zipCode
        self.country = contact.country
        self.website = contact.website
        self.notes = contact.notes
        self.commentsFromLead = contact.commentsFromLead
        self.birthdate = contact.birthdate
        self.photoUrl = contact.photoUrl
        self.source = contact.source
        self.sourceMetadata = contact.sourceMetadata
        
        let formatter = ISO8601DateFormatter()
        self.createdAt = formatter.date(from: contact.createdAt) ?? Date()
        self.updatedAt = formatter.date(from: contact.updatedAt) ?? Date()
        self.syncStatus = "synced"
        self.lastSyncAt = Date()
    }
    
    func toContact() -> Contact {
        let formatter = ISO8601DateFormatter()
        
        return Contact(
            id: self.id,
            firstName: self.firstName,
            lastName: self.lastName,
            email: self.email,
            phone: self.phone,
            mobilePhone: self.mobilePhone,
            company: self.company,
            jobTitle: self.jobTitle,
            address: self.address,
            city: self.city,
            state: self.state,
            zipCode: self.zipCode,
            country: self.country,
            website: self.website,
            notes: self.notes,
            commentsFromLead: self.commentsFromLead,
            birthdate: self.birthdate,
            photoUrl: self.photoUrl,
            source: self.source,
            sourceMetadata: self.sourceMetadata,
            createdAt: formatter.string(from: self.createdAt),
            updatedAt: formatter.string(from: self.updatedAt)
        )
    }
}
