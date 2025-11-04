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
        
        // Create ContactEntity
        let contactEntity = NSEntityDescription()
        contactEntity.name = "ContactEntity"
        contactEntity.managedObjectClassName = "ContactEntity"
        
        let contactIdAttribute = NSAttributeDescription()
        contactIdAttribute.name = "id"
        contactIdAttribute.attributeType = .stringAttributeType
        contactIdAttribute.isOptional = false
        
        let contactFirstNameAttribute = NSAttributeDescription()
        contactFirstNameAttribute.name = "firstName"
        contactFirstNameAttribute.attributeType = .stringAttributeType
        contactFirstNameAttribute.isOptional = false
        
        let contactLastNameAttribute = NSAttributeDescription()
        contactLastNameAttribute.name = "lastName"
        contactLastNameAttribute.attributeType = .stringAttributeType
        contactLastNameAttribute.isOptional = false
        
        let contactEmailAttribute = NSAttributeDescription()
        contactEmailAttribute.name = "email"
        contactEmailAttribute.attributeType = .stringAttributeType
        contactEmailAttribute.isOptional = true
        
        let contactPhoneAttribute = NSAttributeDescription()
        contactPhoneAttribute.name = "phone"
        contactPhoneAttribute.attributeType = .stringAttributeType
        contactPhoneAttribute.isOptional = true
        
        let contactMobilePhoneAttribute = NSAttributeDescription()
        contactMobilePhoneAttribute.name = "mobilePhone"
        contactMobilePhoneAttribute.attributeType = .stringAttributeType
        contactMobilePhoneAttribute.isOptional = true
        
        let contactCompanyAttribute = NSAttributeDescription()
        contactCompanyAttribute.name = "company"
        contactCompanyAttribute.attributeType = .stringAttributeType
        contactCompanyAttribute.isOptional = true
        
        let contactJobTitleAttribute = NSAttributeDescription()
        contactJobTitleAttribute.name = "jobTitle"
        contactJobTitleAttribute.attributeType = .stringAttributeType
        contactJobTitleAttribute.isOptional = true
        
        let contactAddressAttribute = NSAttributeDescription()
        contactAddressAttribute.name = "address"
        contactAddressAttribute.attributeType = .stringAttributeType
        contactAddressAttribute.isOptional = true
        
        let contactCityAttribute = NSAttributeDescription()
        contactCityAttribute.name = "city"
        contactCityAttribute.attributeType = .stringAttributeType
        contactCityAttribute.isOptional = true
        
        let contactStateAttribute = NSAttributeDescription()
        contactStateAttribute.name = "state"
        contactStateAttribute.attributeType = .stringAttributeType
        contactStateAttribute.isOptional = true
        
        let contactZipCodeAttribute = NSAttributeDescription()
        contactZipCodeAttribute.name = "zipCode"
        contactZipCodeAttribute.attributeType = .stringAttributeType
        contactZipCodeAttribute.isOptional = true
        
        let contactCountryAttribute = NSAttributeDescription()
        contactCountryAttribute.name = "country"
        contactCountryAttribute.attributeType = .stringAttributeType
        contactCountryAttribute.isOptional = true
        
        let contactWebsiteAttribute = NSAttributeDescription()
        contactWebsiteAttribute.name = "website"
        contactWebsiteAttribute.attributeType = .stringAttributeType
        contactWebsiteAttribute.isOptional = true
        
        let contactNotesAttribute = NSAttributeDescription()
        contactNotesAttribute.name = "notes"
        contactNotesAttribute.attributeType = .stringAttributeType
        contactNotesAttribute.isOptional = true
        
        let contactCommentsFromLeadAttribute = NSAttributeDescription()
        contactCommentsFromLeadAttribute.name = "commentsFromLead"
        contactCommentsFromLeadAttribute.attributeType = .stringAttributeType
        contactCommentsFromLeadAttribute.isOptional = true
        
        let contactBirthdateAttribute = NSAttributeDescription()
        contactBirthdateAttribute.name = "birthdate"
        contactBirthdateAttribute.attributeType = .stringAttributeType
        contactBirthdateAttribute.isOptional = true
        
        let contactPhotoUrlAttribute = NSAttributeDescription()
        contactPhotoUrlAttribute.name = "photoUrl"
        contactPhotoUrlAttribute.attributeType = .stringAttributeType
        contactPhotoUrlAttribute.isOptional = true
        
        let contactSourceAttribute = NSAttributeDescription()
        contactSourceAttribute.name = "source"
        contactSourceAttribute.attributeType = .stringAttributeType
        contactSourceAttribute.isOptional = true
        
        let contactSourceMetadataAttribute = NSAttributeDescription()
        contactSourceMetadataAttribute.name = "sourceMetadata"
        contactSourceMetadataAttribute.attributeType = .stringAttributeType
        contactSourceMetadataAttribute.isOptional = true
        
        let contactCreatedAtAttribute = NSAttributeDescription()
        contactCreatedAtAttribute.name = "createdAt"
        contactCreatedAtAttribute.attributeType = .dateAttributeType
        contactCreatedAtAttribute.isOptional = false
        
        let contactUpdatedAtAttribute = NSAttributeDescription()
        contactUpdatedAtAttribute.name = "updatedAt"
        contactUpdatedAtAttribute.attributeType = .dateAttributeType
        contactUpdatedAtAttribute.isOptional = false
        
        let contactSyncStatusAttribute = NSAttributeDescription()
        contactSyncStatusAttribute.name = "syncStatus"
        contactSyncStatusAttribute.attributeType = .stringAttributeType
        contactSyncStatusAttribute.isOptional = false
        contactSyncStatusAttribute.defaultValue = "pending"
        
        let contactLastSyncAtAttribute = NSAttributeDescription()
        contactLastSyncAtAttribute.name = "lastSyncAt"
        contactLastSyncAtAttribute.attributeType = .dateAttributeType
        contactLastSyncAtAttribute.isOptional = true
        
        contactEntity.properties = [
            contactIdAttribute, contactFirstNameAttribute, contactLastNameAttribute,
            contactEmailAttribute, contactPhoneAttribute, contactMobilePhoneAttribute,
            contactCompanyAttribute, contactJobTitleAttribute, contactAddressAttribute,
            contactCityAttribute, contactStateAttribute, contactZipCodeAttribute,
            contactCountryAttribute, contactWebsiteAttribute, contactNotesAttribute,
            contactCommentsFromLeadAttribute, contactBirthdateAttribute, contactPhotoUrlAttribute, contactSourceAttribute,
            contactSourceMetadataAttribute, contactCreatedAtAttribute, contactUpdatedAtAttribute,
            contactSyncStatusAttribute, contactLastSyncAtAttribute
        ]
        
        // MARK: - LeadEntity
        let leadEntity = NSEntityDescription()
        leadEntity.name = "LeadEntity"
        leadEntity.managedObjectClassName = "LeadEntity"
        
        let leadIdAttribute = NSAttributeDescription()
        leadIdAttribute.name = "id"
        leadIdAttribute.attributeType = .stringAttributeType
        leadIdAttribute.isOptional = false
        
        let leadFirstNameAttribute = NSAttributeDescription()
        leadFirstNameAttribute.name = "firstName"
        leadFirstNameAttribute.attributeType = .stringAttributeType
        leadFirstNameAttribute.isOptional = false
        
        let leadLastNameAttribute = NSAttributeDescription()
        leadLastNameAttribute.name = "lastName"
        leadLastNameAttribute.attributeType = .stringAttributeType
        leadLastNameAttribute.isOptional = false
        
        let leadFullNameAttribute = NSAttributeDescription()
        leadFullNameAttribute.name = "fullName"
        leadFullNameAttribute.attributeType = .stringAttributeType
        leadFullNameAttribute.isOptional = true
        
        let leadEmailPrimaryAttribute = NSAttributeDescription()
        leadEmailPrimaryAttribute.name = "emailPrimary"
        leadEmailPrimaryAttribute.attributeType = .stringAttributeType
        leadEmailPrimaryAttribute.isOptional = true
        
        let leadWorkPhoneAttribute = NSAttributeDescription()
        leadWorkPhoneAttribute.name = "workPhone"
        leadWorkPhoneAttribute.attributeType = .stringAttributeType
        leadWorkPhoneAttribute.isOptional = true
        
        let leadMobilePhoneAttribute = NSAttributeDescription()
        leadMobilePhoneAttribute.name = "mobilePhone"
        leadMobilePhoneAttribute.attributeType = .stringAttributeType
        leadMobilePhoneAttribute.isOptional = true
        
        let leadStreetAddressAttribute = NSAttributeDescription()
        leadStreetAddressAttribute.name = "streetAddress"
        leadStreetAddressAttribute.attributeType = .stringAttributeType
        leadStreetAddressAttribute.isOptional = true
        
        let leadCityAttribute = NSAttributeDescription()
        leadCityAttribute.name = "city"
        leadCityAttribute.attributeType = .stringAttributeType
        leadCityAttribute.isOptional = true
        
        let leadStateAttribute = NSAttributeDescription()
        leadStateAttribute.name = "state"
        leadStateAttribute.attributeType = .stringAttributeType
        leadStateAttribute.isOptional = true
        
        let leadZipCodeAttribute = NSAttributeDescription()
        leadZipCodeAttribute.name = "zipCode"
        leadZipCodeAttribute.attributeType = .stringAttributeType
        leadZipCodeAttribute.isOptional = true
        
        let leadCountryAttribute = NSAttributeDescription()
        leadCountryAttribute.name = "country"
        leadCountryAttribute.attributeType = .stringAttributeType
        leadCountryAttribute.isOptional = true
        
        let leadOrganizationNameAttribute = NSAttributeDescription()
        leadOrganizationNameAttribute.name = "organizationName"
        leadOrganizationNameAttribute.attributeType = .stringAttributeType
        leadOrganizationNameAttribute.isOptional = true
        
        let leadJobTitleAttribute = NSAttributeDescription()
        leadJobTitleAttribute.name = "jobTitle"
        leadJobTitleAttribute.attributeType = .stringAttributeType
        leadJobTitleAttribute.isOptional = true
        
        let leadBirthdateAttribute = NSAttributeDescription()
        leadBirthdateAttribute.name = "birthdate"
        leadBirthdateAttribute.attributeType = .stringAttributeType
        leadBirthdateAttribute.isOptional = true
        
        let leadWebsiteUrlAttribute = NSAttributeDescription()
        leadWebsiteUrlAttribute.name = "websiteUrl"
        leadWebsiteUrlAttribute.attributeType = .stringAttributeType
        leadWebsiteUrlAttribute.isOptional = true
        
        let leadPhotoUrlAttribute = NSAttributeDescription()
        leadPhotoUrlAttribute.name = "photoUrl"
        leadPhotoUrlAttribute.attributeType = .stringAttributeType
        leadPhotoUrlAttribute.isOptional = true
        
        let leadCommentsFromLeadAttribute = NSAttributeDescription()
        leadCommentsFromLeadAttribute.name = "commentsFromLead"
        leadCommentsFromLeadAttribute.attributeType = .stringAttributeType
        leadCommentsFromLeadAttribute.isOptional = true
        
        let leadCreatedAtAttribute = NSAttributeDescription()
        leadCreatedAtAttribute.name = "createdAt"
        leadCreatedAtAttribute.attributeType = .dateAttributeType
        leadCreatedAtAttribute.isOptional = false
        
        let leadUpdatedAtAttribute = NSAttributeDescription()
        leadUpdatedAtAttribute.name = "updatedAt"
        leadUpdatedAtAttribute.attributeType = .dateAttributeType
        leadUpdatedAtAttribute.isOptional = false
        
        let leadCardFirstNameAttribute = NSAttributeDescription()
        leadCardFirstNameAttribute.name = "cardFirstName"
        leadCardFirstNameAttribute.attributeType = .stringAttributeType
        leadCardFirstNameAttribute.isOptional = true
        
        let leadCardLastNameAttribute = NSAttributeDescription()
        leadCardLastNameAttribute.name = "cardLastName"
        leadCardLastNameAttribute.attributeType = .stringAttributeType
        leadCardLastNameAttribute.isOptional = true
        
        let leadCardCompanyAttribute = NSAttributeDescription()
        leadCardCompanyAttribute.name = "cardCompany"
        leadCardCompanyAttribute.attributeType = .stringAttributeType
        leadCardCompanyAttribute.isOptional = true
        
        let leadCardJobTitleAttribute = NSAttributeDescription()
        leadCardJobTitleAttribute.name = "cardJobTitle"
        leadCardJobTitleAttribute.attributeType = .stringAttributeType
        leadCardJobTitleAttribute.isOptional = true
        
        let leadQrTitleAttribute = NSAttributeDescription()
        leadQrTitleAttribute.name = "qrTitle"
        leadQrTitleAttribute.attributeType = .stringAttributeType
        leadQrTitleAttribute.isOptional = true
        
        let leadQrTypeAttribute = NSAttributeDescription()
        leadQrTypeAttribute.name = "qrType"
        leadQrTypeAttribute.attributeType = .stringAttributeType
        leadQrTypeAttribute.isOptional = true
        
        let leadStatusAttribute = NSAttributeDescription()
        leadStatusAttribute.name = "status"
        leadStatusAttribute.attributeType = .stringAttributeType
        leadStatusAttribute.isOptional = true
        
        let leadSyncStatusAttribute = NSAttributeDescription()
        leadSyncStatusAttribute.name = "syncStatus"
        leadSyncStatusAttribute.attributeType = .stringAttributeType
        leadSyncStatusAttribute.isOptional = false
        leadSyncStatusAttribute.defaultValue = "pending"
        
        let leadLastSyncAtAttribute = NSAttributeDescription()
        leadLastSyncAtAttribute.name = "lastSyncAt"
        leadLastSyncAtAttribute.attributeType = .dateAttributeType
        leadLastSyncAtAttribute.isOptional = true
        
        leadEntity.properties = [
            leadIdAttribute, leadFirstNameAttribute, leadLastNameAttribute,
            leadFullNameAttribute, leadEmailPrimaryAttribute, leadWorkPhoneAttribute,
            leadMobilePhoneAttribute, leadStreetAddressAttribute, leadCityAttribute,
            leadStateAttribute, leadZipCodeAttribute, leadCountryAttribute,
            leadOrganizationNameAttribute, leadJobTitleAttribute, leadBirthdateAttribute,
            leadWebsiteUrlAttribute, leadPhotoUrlAttribute, leadCommentsFromLeadAttribute,
            leadCreatedAtAttribute, leadUpdatedAtAttribute, leadCardFirstNameAttribute,
            leadCardLastNameAttribute, leadCardCompanyAttribute, leadCardJobTitleAttribute,
            leadQrTitleAttribute, leadQrTypeAttribute, leadStatusAttribute,
            leadSyncStatusAttribute, leadLastSyncAtAttribute
        ]
        
        // Add all entities to the model
        model.entities = [businessCardEntity, emailEntity, phoneEntity, websiteEntity, addressEntity, contactEntity, leadEntity]
        
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
        
        // Check for and clean up duplicates on initialization
        checkAndRemoveDuplicates()
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
    
    /// Find business card by server ID (queries CoreData directly)
    func findBusinessCard(byServerId serverId: String) -> BusinessCardEntity? {
        guard !serverId.isEmpty else { return nil }
        
        let request: NSFetchRequest<BusinessCardEntity> = BusinessCardEntity.fetchRequest()
        request.predicate = NSPredicate(format: "serverCardId == %@", serverId)
        request.fetchLimit = 1
        
        do {
            return try context.fetch(request).first
        } catch {
            print("Error finding business card by server ID: \(error)")
            return nil
        }
    }
    
    /// Find business card by local UUID
    func findBusinessCard(byId id: UUID) -> BusinessCardEntity? {
        let request: NSFetchRequest<BusinessCardEntity> = BusinessCardEntity.fetchRequest()
        request.predicate = NSPredicate(format: "id == %@", id as CVarArg)
        request.fetchLimit = 1
        
        do {
            return try context.fetch(request).first
        } catch {
            print("Error finding business card by ID: \(error)")
            return nil
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
                "BusinessCardEntity",
                "ContactEntity",
                "LeadEntity"
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
        
        // Check for duplicates: if serverCardId exists, check if we already have this card
        if let serverCardId = businessCard.serverCardId, !serverCardId.isEmpty {
            if let existingEntity = findBusinessCard(byServerId: serverCardId) {
                print("⚠️ Duplicate detected: Card with serverCardId \(serverCardId) already exists. Updating instead of creating duplicate.")
                // Update existing card instead of creating duplicate
                updateBusinessCard(existingEntity, with: businessCard)
                return existingEntity
            }
        }
        
        // Also check by local UUID to prevent duplicates
        if let existingEntity = findBusinessCard(byId: businessCard.id) {
            print("⚠️ Duplicate detected: Card with local id \(businessCard.id) already exists. Updating instead of creating duplicate.")
            updateBusinessCard(existingEntity, with: businessCard)
            return existingEntity
        }
        
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
    
    // MARK: - Contact Management
    
    func createContact(from contact: Contact) -> ContactEntity {
        let entity = ContactEntity(context: context)
        entity.updateFromContact(contact)
        save()
        return entity
    }
    
    func updateContact(_ entity: ContactEntity, with contact: Contact) {
        entity.updateFromContact(contact)
        save()
    }
    
    func deleteContact(_ entity: ContactEntity) {
        context.delete(entity)
        save()
    }
    
    func fetchContacts() -> [ContactEntity] {
        let request: NSFetchRequest<ContactEntity> = ContactEntity.fetchRequest()
        request.sortDescriptors = [NSSortDescriptor(keyPath: \ContactEntity.lastName, ascending: true)]
        
        do {
            return try context.fetch(request)
        } catch {
            print("Error fetching contacts: \(error)")
            return []
        }
    }
    
    func fetchContact(by id: String) -> ContactEntity? {
        let request: NSFetchRequest<ContactEntity> = ContactEntity.fetchRequest()
        request.predicate = NSPredicate(format: "id == %@", id)
        request.fetchLimit = 1

        do {
            return try context.fetch(request).first
        } catch {
            print("Error fetching contact: \(error)")
            return nil
        }
    }
    
    // MARK: - Lead Management
    
    func createLead(from lead: Lead) -> LeadEntity {
        let entity = LeadEntity(context: context)
        entity.updateFromLead(lead)
        save()
        return entity
    }
    
    func updateLead(_ entity: LeadEntity, with lead: Lead) {
        entity.updateFromLead(lead)
        save()
    }
    
    func deleteLead(_ entity: LeadEntity) {
        context.delete(entity)
        save()
    }
    
    func fetchLeads() -> [LeadEntity] {
        let request: NSFetchRequest<LeadEntity> = LeadEntity.fetchRequest()
        request.sortDescriptors = [NSSortDescriptor(keyPath: \LeadEntity.createdAt, ascending: false)]
        
        do {
            return try context.fetch(request)
        } catch {
            print("Error fetching leads: \(error)")
            return []
        }
    }
    
    func fetchLead(by id: String) -> LeadEntity? {
        let request: NSFetchRequest<LeadEntity> = LeadEntity.fetchRequest()
        request.predicate = NSPredicate(format: "id == %@", id)
        request.fetchLimit = 1

        do {
            return try context.fetch(request).first
        } catch {
            print("Error fetching lead: \(error)")
            return nil
        }
    }
    
    // MARK: - Duplicate Detection and Cleanup
    
    /// Check for and remove duplicate business cards
    /// Duplicates are identified by having the same serverCardId
    func checkAndRemoveDuplicates() {
        print("🔍 Checking for duplicate business cards...")
        
        // Reload to ensure we have the latest data
        loadBusinessCards()
        
        // Group cards by serverCardId
        var serverIdGroups: [String: [BusinessCardEntity]] = [:]
        var cardsWithoutServerId: [BusinessCardEntity] = []
        
        for card in businessCards {
            if let serverId = card.serverCardId, !serverId.isEmpty {
                if serverIdGroups[serverId] == nil {
                    serverIdGroups[serverId] = []
                }
                serverIdGroups[serverId]?.append(card)
            } else {
                cardsWithoutServerId.append(card)
            }
        }
        
        var duplicatesRemoved = 0
        
        // For each group with duplicates, keep the most recently updated one
        for (serverId, cards) in serverIdGroups {
            if cards.count > 1 {
                print("⚠️ Found \(cards.count) duplicates for serverCardId: \(serverId)")
                
                // Sort by updatedAt (most recent first), then by createdAt
                let sortedCards = cards.sorted { card1, card2 in
                    let date1 = card1.updatedAt ?? card1.createdAt ?? Date.distantPast
                    let date2 = card2.updatedAt ?? card2.createdAt ?? Date.distantPast
                    return date1 > date2
                }
                
                // Keep the first (most recent), delete the rest
                for duplicateCard in sortedCards.dropFirst() {
                    print("  🗑️ Removing duplicate card: \(duplicateCard.firstName ?? "") \(duplicateCard.lastName ?? "")")
                    context.delete(duplicateCard)
                    duplicatesRemoved += 1
                }
            }
        }
        
        // Also check for duplicates by local UUID (shouldn't happen, but safety check)
        var uuidGroups: [UUID: [BusinessCardEntity]] = [:]
        for card in businessCards {
            if let cardId = card.id {
                if uuidGroups[cardId] == nil {
                    uuidGroups[cardId] = []
                }
                uuidGroups[cardId]?.append(card)
            }
        }
        
        for (uuid, cards) in uuidGroups {
            if cards.count > 1 {
                print("⚠️ Found \(cards.count) duplicates for local UUID: \(uuid)")
                
                // Keep the most recently updated one
                let sortedCards = cards.sorted { card1, card2 in
                    let date1 = card1.updatedAt ?? card1.createdAt ?? Date.distantPast
                    let date2 = card2.updatedAt ?? card2.createdAt ?? Date.distantPast
                    return date1 > date2
                }
                
                for duplicateCard in sortedCards.dropFirst() {
                    print("  🗑️ Removing duplicate card by UUID: \(duplicateCard.firstName ?? "") \(duplicateCard.lastName ?? "")")
                    context.delete(duplicateCard)
                    duplicatesRemoved += 1
                }
            }
        }
        
        if duplicatesRemoved > 0 {
            print("✅ Removed \(duplicatesRemoved) duplicate business card(s)")
            save()
            loadBusinessCards()
        } else {
            print("✅ No duplicates found")
        }
    }
    
    // MARK: - Sample Data Setup
    
    func createSampleData() {
        // Only create sample data if no business cards exist
        guard businessCards.isEmpty else { return }
        
        let sampleCard = BusinessCard.sampleData.first!
        _ = createBusinessCard(from: sampleCard)
    }
}
