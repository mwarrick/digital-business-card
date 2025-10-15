//
//  DataManager.swift
//  ShareMyCard
//
//  Created by Mark Warrick on 10/10/25.
//

import Foundation
import CoreData
import SwiftUI
import Combine

// MARK: - Data Manager
class DataManager: ObservableObject {
    static let shared = DataManager()
    
    // MARK: - Core Data Stack
    lazy var persistentContainer: NSPersistentContainer = {
        // Create the managed object model programmatically
        let model = NSManagedObjectModel()
        
        // Create BusinessCardEntity
        let businessCardEntity = NSEntityDescription()
        businessCardEntity.name = "BusinessCardEntity"
        businessCardEntity.managedObjectClassName = "BusinessCardEntity"
        
        // Add attributes to BusinessCardEntity
        let idAttribute = NSAttributeDescription()
        idAttribute.name = "id"
        idAttribute.attributeType = .UUIDAttributeType
        idAttribute.isOptional = true
        
        let firstNameAttribute = NSAttributeDescription()
        firstNameAttribute.name = "firstName"
        firstNameAttribute.attributeType = .stringAttributeType
        firstNameAttribute.isOptional = true
        
        let lastNameAttribute = NSAttributeDescription()
        lastNameAttribute.name = "lastName"
        lastNameAttribute.attributeType = .stringAttributeType
        lastNameAttribute.isOptional = true
        
        let phoneNumberAttribute = NSAttributeDescription()
        phoneNumberAttribute.name = "phoneNumber"
        phoneNumberAttribute.attributeType = .stringAttributeType
        phoneNumberAttribute.isOptional = true
        
        let companyNameAttribute = NSAttributeDescription()
        companyNameAttribute.name = "companyName"
        companyNameAttribute.attributeType = .stringAttributeType
        companyNameAttribute.isOptional = true
        
        let jobTitleAttribute = NSAttributeDescription()
        jobTitleAttribute.name = "jobTitle"
        jobTitleAttribute.attributeType = .stringAttributeType
        jobTitleAttribute.isOptional = true
        
        let bioAttribute = NSAttributeDescription()
        bioAttribute.name = "bio"
        bioAttribute.attributeType = .stringAttributeType
        bioAttribute.isOptional = true
        
        let profilePhotoAttribute = NSAttributeDescription()
        profilePhotoAttribute.name = "profilePhoto"
        profilePhotoAttribute.attributeType = .binaryDataAttributeType
        profilePhotoAttribute.isOptional = true
        
        let companyLogoAttribute = NSAttributeDescription()
        companyLogoAttribute.name = "companyLogo"
        companyLogoAttribute.attributeType = .binaryDataAttributeType
        companyLogoAttribute.isOptional = true
        
        let coverGraphicAttribute = NSAttributeDescription()
        coverGraphicAttribute.name = "coverGraphic"
        coverGraphicAttribute.attributeType = .binaryDataAttributeType
        coverGraphicAttribute.isOptional = true
        
        let profilePhotoPathAttribute = NSAttributeDescription()
        profilePhotoPathAttribute.name = "profilePhotoPath"
        profilePhotoPathAttribute.attributeType = .stringAttributeType
        profilePhotoPathAttribute.isOptional = true
        
        let companyLogoPathAttribute = NSAttributeDescription()
        companyLogoPathAttribute.name = "companyLogoPath"
        companyLogoPathAttribute.attributeType = .stringAttributeType
        companyLogoPathAttribute.isOptional = true
        
        let coverGraphicPathAttribute = NSAttributeDescription()
        coverGraphicPathAttribute.name = "coverGraphicPath"
        coverGraphicPathAttribute.attributeType = .stringAttributeType
        coverGraphicPathAttribute.isOptional = true
        
        let isActiveAttribute = NSAttributeDescription()
        isActiveAttribute.name = "isActive"
        isActiveAttribute.attributeType = .booleanAttributeType
        isActiveAttribute.isOptional = false
        isActiveAttribute.defaultValue = true
        
        let createdAtAttribute = NSAttributeDescription()
        createdAtAttribute.name = "createdAt"
        createdAtAttribute.attributeType = .dateAttributeType
        createdAtAttribute.isOptional = true
        
        let updatedAtAttribute = NSAttributeDescription()
        updatedAtAttribute.name = "updatedAt"
        updatedAtAttribute.attributeType = .dateAttributeType
        updatedAtAttribute.isOptional = true
        
        let serverCardIdAttribute = NSAttributeDescription()
        serverCardIdAttribute.name = "serverCardId"
        serverCardIdAttribute.attributeType = .stringAttributeType
        serverCardIdAttribute.isOptional = true
        
        let themeAttribute = NSAttributeDescription()
        themeAttribute.name = "theme"
        themeAttribute.attributeType = .stringAttributeType
        themeAttribute.isOptional = true
        
        businessCardEntity.properties = [
            idAttribute, firstNameAttribute, lastNameAttribute, phoneNumberAttribute,
            companyNameAttribute, jobTitleAttribute, bioAttribute, profilePhotoAttribute,
            companyLogoAttribute, coverGraphicAttribute, profilePhotoPathAttribute,
            companyLogoPathAttribute, coverGraphicPathAttribute, themeAttribute, isActiveAttribute,
            createdAtAttribute, updatedAtAttribute, serverCardIdAttribute
        ]
        
        // Create EmailContactEntity
        let emailEntity = NSEntityDescription()
        emailEntity.name = "EmailContactEntity"
        emailEntity.managedObjectClassName = "EmailContactEntity"
        
        let emailIdAttribute = NSAttributeDescription()
        emailIdAttribute.name = "id"
        emailIdAttribute.attributeType = .UUIDAttributeType
        emailIdAttribute.isOptional = true
        
        let emailAttribute = NSAttributeDescription()
        emailAttribute.name = "email"
        emailAttribute.attributeType = .stringAttributeType
        emailAttribute.isOptional = true
        
        let emailTypeAttribute = NSAttributeDescription()
        emailTypeAttribute.name = "type"
        emailTypeAttribute.attributeType = .stringAttributeType
        emailTypeAttribute.isOptional = true
        
        let emailLabelAttribute = NSAttributeDescription()
        emailLabelAttribute.name = "label"
        emailLabelAttribute.attributeType = .stringAttributeType
        emailLabelAttribute.isOptional = true
        
        let emailIsPrimaryAttribute = NSAttributeDescription()
        emailIsPrimaryAttribute.name = "isPrimary"
        emailIsPrimaryAttribute.attributeType = .booleanAttributeType
        emailIsPrimaryAttribute.isOptional = false
        emailIsPrimaryAttribute.defaultValue = false
        
        emailEntity.properties = [emailIdAttribute, emailAttribute, emailTypeAttribute, emailLabelAttribute, emailIsPrimaryAttribute]
        
        // Create PhoneContactEntity
        let phoneEntity = NSEntityDescription()
        phoneEntity.name = "PhoneContactEntity"
        phoneEntity.managedObjectClassName = "PhoneContactEntity"
        
        let phoneIdAttribute = NSAttributeDescription()
        phoneIdAttribute.name = "id"
        phoneIdAttribute.attributeType = .UUIDAttributeType
        phoneIdAttribute.isOptional = true
        
        let phoneNumberEntityAttribute = NSAttributeDescription()
        phoneNumberEntityAttribute.name = "phoneNumber"
        phoneNumberEntityAttribute.attributeType = .stringAttributeType
        phoneNumberEntityAttribute.isOptional = true
        
        let phoneTypeAttribute = NSAttributeDescription()
        phoneTypeAttribute.name = "type"
        phoneTypeAttribute.attributeType = .stringAttributeType
        phoneTypeAttribute.isOptional = true
        
        let phoneLabelAttribute = NSAttributeDescription()
        phoneLabelAttribute.name = "label"
        phoneLabelAttribute.attributeType = .stringAttributeType
        phoneLabelAttribute.isOptional = true
        
        phoneEntity.properties = [phoneIdAttribute, phoneNumberEntityAttribute, phoneTypeAttribute, phoneLabelAttribute]
        
        // Create WebsiteLinkEntity
        let websiteEntity = NSEntityDescription()
        websiteEntity.name = "WebsiteLinkEntity"
        websiteEntity.managedObjectClassName = "WebsiteLinkEntity"
        
        let websiteIdAttribute = NSAttributeDescription()
        websiteIdAttribute.name = "id"
        websiteIdAttribute.attributeType = .UUIDAttributeType
        websiteIdAttribute.isOptional = true
        
        let websiteNameAttribute = NSAttributeDescription()
        websiteNameAttribute.name = "name"
        websiteNameAttribute.attributeType = .stringAttributeType
        websiteNameAttribute.isOptional = true
        
        let websiteUrlAttribute = NSAttributeDescription()
        websiteUrlAttribute.name = "url"
        websiteUrlAttribute.attributeType = .stringAttributeType
        websiteUrlAttribute.isOptional = true
        
        let websiteDescriptionAttribute = NSAttributeDescription()
        websiteDescriptionAttribute.name = "websiteDescription"
        websiteDescriptionAttribute.attributeType = .stringAttributeType
        websiteDescriptionAttribute.isOptional = true
        
        let websiteIsPrimaryAttribute = NSAttributeDescription()
        websiteIsPrimaryAttribute.name = "isPrimary"
        websiteIsPrimaryAttribute.attributeType = .booleanAttributeType
        websiteIsPrimaryAttribute.isOptional = false
        websiteIsPrimaryAttribute.defaultValue = false
        
        websiteEntity.properties = [websiteIdAttribute, websiteNameAttribute, websiteUrlAttribute, websiteDescriptionAttribute, websiteIsPrimaryAttribute]
        
        // Create AddressEntity
        let addressEntity = NSEntityDescription()
        addressEntity.name = "AddressEntity"
        addressEntity.managedObjectClassName = "AddressEntity"
        
        let streetAttribute = NSAttributeDescription()
        streetAttribute.name = "street"
        streetAttribute.attributeType = .stringAttributeType
        streetAttribute.isOptional = true
        
        let cityAttribute = NSAttributeDescription()
        cityAttribute.name = "city"
        cityAttribute.attributeType = .stringAttributeType
        cityAttribute.isOptional = true
        
        let stateAttribute = NSAttributeDescription()
        stateAttribute.name = "state"
        stateAttribute.attributeType = .stringAttributeType
        stateAttribute.isOptional = true
        
        let zipCodeAttribute = NSAttributeDescription()
        zipCodeAttribute.name = "zipCode"
        zipCodeAttribute.attributeType = .stringAttributeType
        zipCodeAttribute.isOptional = true
        
        let countryAttribute = NSAttributeDescription()
        countryAttribute.name = "country"
        countryAttribute.attributeType = .stringAttributeType
        countryAttribute.isOptional = true
        
        addressEntity.properties = [streetAttribute, cityAttribute, stateAttribute, zipCodeAttribute, countryAttribute]
        
        // Set up relationships
        
        // BusinessCard -> AdditionalEmails relationship
        let additionalEmailsRelationship = NSRelationshipDescription()
        additionalEmailsRelationship.name = "additionalEmails"
        additionalEmailsRelationship.destinationEntity = emailEntity
        additionalEmailsRelationship.maxCount = 0 // to-many
        additionalEmailsRelationship.deleteRule = .cascadeDeleteRule
        
        // EmailContact -> BusinessCard relationship
        let businessCardEmailRelationship = NSRelationshipDescription()
        businessCardEmailRelationship.name = "businessCard"
        businessCardEmailRelationship.destinationEntity = businessCardEntity
        businessCardEmailRelationship.maxCount = 1 // to-one
        businessCardEmailRelationship.deleteRule = .nullifyDeleteRule
        
        additionalEmailsRelationship.inverseRelationship = businessCardEmailRelationship
        businessCardEmailRelationship.inverseRelationship = additionalEmailsRelationship
        
        emailEntity.properties.append(businessCardEmailRelationship)
        businessCardEntity.properties.append(additionalEmailsRelationship)
        
        // BusinessCard -> AdditionalPhones relationship
        let additionalPhonesRelationship = NSRelationshipDescription()
        additionalPhonesRelationship.name = "additionalPhones"
        additionalPhonesRelationship.destinationEntity = phoneEntity
        additionalPhonesRelationship.maxCount = 0 // to-many
        additionalPhonesRelationship.deleteRule = .cascadeDeleteRule
        
        // PhoneContact -> BusinessCard relationship
        let businessCardPhoneRelationship = NSRelationshipDescription()
        businessCardPhoneRelationship.name = "businessCard"
        businessCardPhoneRelationship.destinationEntity = businessCardEntity
        businessCardPhoneRelationship.maxCount = 1 // to-one
        businessCardPhoneRelationship.deleteRule = .nullifyDeleteRule
        
        additionalPhonesRelationship.inverseRelationship = businessCardPhoneRelationship
        businessCardPhoneRelationship.inverseRelationship = additionalPhonesRelationship
        
        phoneEntity.properties.append(businessCardPhoneRelationship)
        businessCardEntity.properties.append(additionalPhonesRelationship)
        
        // BusinessCard -> WebsiteLinks relationship
        let websiteLinksRelationship = NSRelationshipDescription()
        websiteLinksRelationship.name = "websiteLinks"
        websiteLinksRelationship.destinationEntity = websiteEntity
        websiteLinksRelationship.maxCount = 0 // to-many
        websiteLinksRelationship.deleteRule = .cascadeDeleteRule
        
        // WebsiteLink -> BusinessCard relationship
        let businessCardWebsiteRelationship = NSRelationshipDescription()
        businessCardWebsiteRelationship.name = "businessCard"
        businessCardWebsiteRelationship.destinationEntity = businessCardEntity
        businessCardWebsiteRelationship.maxCount = 1 // to-one
        businessCardWebsiteRelationship.deleteRule = .nullifyDeleteRule
        
        websiteLinksRelationship.inverseRelationship = businessCardWebsiteRelationship
        businessCardWebsiteRelationship.inverseRelationship = websiteLinksRelationship
        
        websiteEntity.properties.append(businessCardWebsiteRelationship)
        businessCardEntity.properties.append(websiteLinksRelationship)
        
        // BusinessCard -> Address relationship
        let addressRelationship = NSRelationshipDescription()
        addressRelationship.name = "address"
        addressRelationship.destinationEntity = addressEntity
        addressRelationship.maxCount = 1 // to-one
        addressRelationship.deleteRule = .cascadeDeleteRule
        
        // Address -> BusinessCard relationship
        let businessCardAddressRelationship = NSRelationshipDescription()
        businessCardAddressRelationship.name = "businessCard"
        businessCardAddressRelationship.destinationEntity = businessCardEntity
        businessCardAddressRelationship.maxCount = 1 // to-one
        businessCardAddressRelationship.deleteRule = .nullifyDeleteRule
        
        addressRelationship.inverseRelationship = businessCardAddressRelationship
        businessCardAddressRelationship.inverseRelationship = addressRelationship
        
        addressEntity.properties.append(businessCardAddressRelationship)
        businessCardEntity.properties.append(addressRelationship)
        
        // Add all entities to the model
        model.entities = [businessCardEntity, emailEntity, phoneEntity, websiteEntity, addressEntity]
        
        // Create container with the programmatic model
        let container = NSPersistentContainer(name: "BusinessCardModel", managedObjectModel: model)
        
        // Use in-memory store for now
        container.persistentStoreDescriptions.first?.type = NSInMemoryStoreType
        
        container.loadPersistentStores { _, error in
            if let error = error {
                print("Core Data error: \(error)")
            } else {
                print("Core Data loaded successfully with programmatic model")
            }
        }
        
        return container
    }()
    
    var context: NSManagedObjectContext {
        return persistentContainer.viewContext
    }
    
    // MARK: - Published Properties
    @Published var businessCards: [BusinessCardEntity] = []
    
    private init() {
        print("🔧 DataManager: Initializing...")
        loadBusinessCards()
        print("🔧 DataManager: Loaded \(businessCards.count) cards")
    }
    
    // MARK: - Core Data Operations
    
    func save() {
        do {
            try context.save()
            loadBusinessCards()
        } catch {
            print("Save error: \(error)")
        }
    }
    
    func loadBusinessCards() {
        let request: NSFetchRequest<BusinessCardEntity> = BusinessCardEntity.fetchRequest()
        request.sortDescriptors = [NSSortDescriptor(keyPath: \BusinessCardEntity.updatedAt, ascending: false)]
        
        do {
            businessCards = try context.fetch(request)
        } catch {
            print("Load error: \(error)")
            businessCards = []
        }
    }
    
    /// Clear all local data (used on logout to prevent cross-account data leakage)
    func clearAllData() {
        print("🧹 DataManager: Clearing all local data...")
        // First clear in-memory list to avoid UI referencing deleted objects
        DispatchQueue.main.async { [weak self] in
            self?.businessCards = []
        }
        
        context.performAndWait {
            let entityNames = [
                "EmailContactEntity",
                "PhoneContactEntity",
                "WebsiteLinkEntity",
                "AddressEntity",
                "BusinessCardEntity"
            ]
            entityNames.forEach { name in
                let fetch = NSFetchRequest<NSFetchRequestResult>(entityName: name)
                fetch.includesPropertyValues = false
                do {
                    let results = try context.fetch(fetch) as? [NSManagedObject] ?? []
                    results.forEach { context.delete($0) }
                } catch {
                    print("Clear data fetch error for \(name): \(error)")
                }
            }
            do {
                try context.save()
            } catch {
                print("Clear data save error: \(error)")
            }
        }
        loadBusinessCards()
    }
    
    // MARK: - Business Card CRUD Operations
    
    func createBusinessCard(from businessCard: BusinessCard) -> BusinessCardEntity {
        print("Creating BusinessCardEntity...")
        print("Context: \(context)")
        print("Model: \(context.persistentStoreCoordinator?.managedObjectModel.description ?? "nil")")
        
        let entity = BusinessCardEntity(context: context)
        entity.id = businessCard.id
        entity.firstName = businessCard.firstName
        entity.lastName = businessCard.lastName
        entity.phoneNumber = businessCard.phoneNumber
        entity.companyName = businessCard.companyName
        entity.jobTitle = businessCard.jobTitle
        entity.bio = businessCard.bio
        entity.profilePhoto = businessCard.profilePhoto
        entity.companyLogo = businessCard.companyLogo
        entity.coverGraphic = businessCard.coverGraphic
        entity.profilePhotoPath = businessCard.profilePhotoPath
        entity.companyLogoPath = businessCard.companyLogoPath
        entity.coverGraphicPath = businessCard.coverGraphicPath
        entity.isActive = businessCard.isActive
        entity.createdAt = businessCard.createdAt
        entity.updatedAt = businessCard.updatedAt
        
        // Save additional emails
        for email in businessCard.additionalEmails {
            let emailEntity = EmailContactEntity(context: context)
            emailEntity.id = email.id
            emailEntity.email = email.email
            emailEntity.type = email.type.rawValue
            emailEntity.label = email.label
            emailEntity.isPrimary = email.isPrimary
            emailEntity.businessCard = entity
        }
        
        // Save additional phones
        for phone in businessCard.additionalPhones {
            let phoneEntity = PhoneContactEntity(context: context)
            phoneEntity.id = phone.id
            phoneEntity.phoneNumber = phone.phoneNumber
            phoneEntity.type = phone.type.rawValue
            phoneEntity.label = phone.label
            phoneEntity.businessCard = entity
        }
        
        // Save website links
        for website in businessCard.websiteLinks {
            let websiteEntity = WebsiteLinkEntity(context: context)
            websiteEntity.id = website.id
            websiteEntity.name = website.name
            websiteEntity.url = website.url
            websiteEntity.websiteDescription = website.description
            websiteEntity.isPrimary = website.isPrimary
            websiteEntity.businessCard = entity
        }
        
        // Save address
        if let address = businessCard.address {
            let addressEntity = AddressEntity(context: context)
            addressEntity.street = address.street
            addressEntity.city = address.city
            addressEntity.state = address.state
            addressEntity.zipCode = address.zipCode
            addressEntity.country = address.country
            addressEntity.businessCard = entity
        }
        
        save()
        return entity
    }
    
    func updateBusinessCard(_ entity: BusinessCardEntity, with businessCard: BusinessCard) {
        entity.firstName = businessCard.firstName
        entity.lastName = businessCard.lastName
        entity.phoneNumber = businessCard.phoneNumber
        entity.companyName = businessCard.companyName
        entity.jobTitle = businessCard.jobTitle
        entity.bio = businessCard.bio
        entity.profilePhoto = businessCard.profilePhoto
        entity.companyLogo = businessCard.companyLogo
        entity.coverGraphic = businessCard.coverGraphic
        entity.profilePhotoPath = businessCard.profilePhotoPath
        entity.companyLogoPath = businessCard.companyLogoPath
        entity.coverGraphicPath = businessCard.coverGraphicPath
        entity.isActive = businessCard.isActive
        entity.updatedAt = Date()
        
        // Clear existing relationships
        if let emails = entity.additionalEmails as? Set<EmailContactEntity> {
            emails.forEach { context.delete($0) }
        }
        if let phones = entity.additionalPhones as? Set<PhoneContactEntity> {
            phones.forEach { context.delete($0) }
        }
        if let websites = entity.websiteLinks as? Set<WebsiteLinkEntity> {
            websites.forEach { context.delete($0) }
        }
        if let address = entity.address {
            context.delete(address)
        }
        
        // Add new relationships (same as create)
        for email in businessCard.additionalEmails {
            let emailEntity = EmailContactEntity(context: context)
            emailEntity.id = email.id
            emailEntity.email = email.email
            emailEntity.type = email.type.rawValue
            emailEntity.label = email.label
            emailEntity.isPrimary = email.isPrimary
            emailEntity.businessCard = entity
        }
        
        for phone in businessCard.additionalPhones {
            let phoneEntity = PhoneContactEntity(context: context)
            phoneEntity.id = phone.id
            phoneEntity.phoneNumber = phone.phoneNumber
            phoneEntity.type = phone.type.rawValue
            phoneEntity.label = phone.label
            phoneEntity.businessCard = entity
        }
        
        for website in businessCard.websiteLinks {
            let websiteEntity = WebsiteLinkEntity(context: context)
            websiteEntity.id = website.id
            websiteEntity.name = website.name
            websiteEntity.url = website.url
            websiteEntity.websiteDescription = website.description
            websiteEntity.isPrimary = website.isPrimary
            websiteEntity.businessCard = entity
        }
        
        if let address = businessCard.address {
            let addressEntity = AddressEntity(context: context)
            addressEntity.street = address.street
            addressEntity.city = address.city
            addressEntity.state = address.state
            addressEntity.zipCode = address.zipCode
            addressEntity.country = address.country
            addressEntity.businessCard = entity
        }
        
        save()
    }
    
    func deleteBusinessCard(_ entity: BusinessCardEntity) {
        context.delete(entity)
        save()
    }
    
    // MARK: - Conversion Methods
    
    func businessCardEntityToBusinessCard(_ entity: BusinessCardEntity) -> BusinessCard {
        var businessCard = BusinessCard(
            firstName: entity.firstName ?? "",
            lastName: entity.lastName ?? "",
            phoneNumber: entity.phoneNumber ?? ""
        )
        
        businessCard.companyName = entity.companyName
        businessCard.jobTitle = entity.jobTitle
        businessCard.bio = entity.bio
        businessCard.profilePhoto = entity.profilePhoto
        businessCard.companyLogo = entity.companyLogo
        businessCard.coverGraphic = entity.coverGraphic
        businessCard.profilePhotoPath = entity.profilePhotoPath
        businessCard.companyLogoPath = entity.companyLogoPath
        businessCard.coverGraphicPath = entity.coverGraphicPath
        businessCard.isActive = entity.isActive
        businessCard.createdAt = entity.createdAt ?? Date()
        businessCard.updatedAt = entity.updatedAt ?? Date()
        businessCard.serverCardId = entity.serverCardId
        
        // Convert additional emails
        if let emailEntities = entity.additionalEmails as? Set<EmailContactEntity> {
            businessCard.additionalEmails = emailEntities.compactMap { emailEntity in
                guard let email = emailEntity.email,
                      let typeString = emailEntity.type,
                      let type = EmailType(rawValue: typeString) else { return nil }
                
                return EmailContact(
                    id: emailEntity.id ?? UUID(),
                    email: email,
                    type: type,
                    label: emailEntity.label,
                    isPrimary: emailEntity.isPrimary
                )
            }
        }
        
        // Convert additional phones
        if let phoneEntities = entity.additionalPhones as? Set<PhoneContactEntity> {
            businessCard.additionalPhones = phoneEntities.compactMap { phoneEntity in
                guard let phoneNumber = phoneEntity.phoneNumber,
                      let typeString = phoneEntity.type,
                      let type = PhoneType(rawValue: typeString) else { return nil }
                
                return PhoneContact(
                    phoneNumber: phoneNumber,
                    type: type,
                    label: phoneEntity.label
                )
            }
        }
        
        // Convert website links
        if let websiteEntities = entity.websiteLinks as? Set<WebsiteLinkEntity> {
            businessCard.websiteLinks = websiteEntities.compactMap { websiteEntity in
                guard let name = websiteEntity.name,
                      let url = websiteEntity.url else { return nil }
                
                return WebsiteLink(
                    id: websiteEntity.id ?? UUID(),
                    name: name,
                    url: url,
                    description: websiteEntity.websiteDescription,
                    isPrimary: websiteEntity.isPrimary
                )
            }
        }
        
        // Convert address
        if let addressEntity = entity.address {
            businessCard.address = Address(
                street: addressEntity.street,
                city: addressEntity.city,
                state: addressEntity.state,
                zipCode: addressEntity.zipCode,
                country: addressEntity.country
            )
        }
        
        return businessCard
    }
    
    // MARK: - Sample Data Setup
    
    func createSampleData() {
        // Only create sample data if no business cards exist
        guard businessCards.isEmpty else { return }
        
        let sampleCard = BusinessCard.sampleData.first!
        _ = createBusinessCard(from: sampleCard)
    }
}
