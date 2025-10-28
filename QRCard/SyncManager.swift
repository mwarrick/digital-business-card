//
//  SyncManager.swift
//  ShareMyCard
//
//  Manages synchronization between local Core Data and server
//

import Foundation
import CoreData
import UIKit

class SyncManager {
    static let shared = SyncManager()
    private let dataManager = DataManager.shared
    
    private init() {}
    
    /// Perform full sync: push local changes (respecting timestamps), then pull server changes
    func performFullSync() async throws {
        print("🔄 Starting full sync (with timestamp comparison)...")
        print("🔍 Checking authentication status...")
        print("   🔑 Has token: \(KeychainHelper.isAuthenticated())")
        
        // Step 1: Fetch server cards first
        print("📡 Fetching server cards...")
        let serverCards = try await CardService.fetchCards()
        print("📦 Received \(serverCards.count) cards from server")
        
        // Create a lookup dictionary of server cards by ID
        let serverCardDict: [String: BusinessCardAPI] = Dictionary(uniqueKeysWithValues: serverCards.compactMap { card in
            guard let id = card.id else { return nil }
            return (id, card)
        })
        
        // Step 2: Push local cards to server (comparing timestamps - last write wins)
        try await pushLocalCardsWithComparison(recentOnly: false, serverCards: serverCardDict)
        
        // Step 3: Pull server cards to local (this will overwrite local if server is newer)
        try await pullServerCards()
        
        // Step 4: Sync contacts
        print("📇 Syncing contacts...")
        do {
            try await syncContacts()
        } catch {
            print("⚠️ Contacts sync failed: \(error)")
            // Don't fail the entire sync if contacts fail
        }
        
        print("✅ Full sync complete!")
    }
    
    /// Push only - for auto-sync after local changes
    /// Compares timestamps and only pushes if local is newer
    func pushToServer() async throws {
        print("🔄 Auto-sync: Pushing to server...")
        
        // First fetch server cards to compare timestamps
        let serverCards = try await CardService.fetchCards()
        
        // Create a lookup dictionary of server cards by ID
        let serverCardDict: [String: BusinessCardAPI] = Dictionary(uniqueKeysWithValues: serverCards.compactMap { card in
            guard let id = card.id else { return nil }
            return (id, card)
        })
        
        // Push local cards that are newer
        try await pushLocalCardsWithComparison(recentOnly: true, serverCards: serverCardDict)
        print("✅ Auto-sync push complete!")
    }
    
    /// Push local cards with timestamp comparison
    /// - Parameters:
    ///   - recentOnly: If true, only push cards updated in last 30 seconds
    ///   - serverCards: Dictionary of server cards by ID for comparison
    private func pushLocalCardsWithComparison(recentOnly: Bool, serverCards: [String: BusinessCardAPI]) async throws {
        print("⬆️ Pushing local cards to server...")
        
        let localCards = dataManager.businessCards
        
        for cardEntity in localCards {
            // Convert Core Data entity to BusinessCard model
            let card = dataManager.businessCardEntityToBusinessCard(cardEntity)
            
            do {
                let apiCard = convertToAPIModel(card)
                
                // Check if this card has been synced (has a serverCardId)
                if let serverId = cardEntity.serverCardId, !serverId.isEmpty {
                    // Card exists on server - compare timestamps
                    if let serverCard = serverCards[serverId] {
                        // Compare timestamps - only push if local is newer
                        if shouldPushBasedOnTimestamp(localCard: cardEntity, serverCard: serverCard, recentOnly: recentOnly) {
                            // Update existing card on server
                            var updateCard = apiCard
                            // Ensure the API card has the server ID for update
                            updateCard = BusinessCardAPI(
                                id: serverId,
                                userId: apiCard.userId,
                                firstName: apiCard.firstName,
                                lastName: apiCard.lastName,
                                phoneNumber: apiCard.phoneNumber,
                                companyName: apiCard.companyName,
                                jobTitle: apiCard.jobTitle,
                                bio: apiCard.bio,
                                profilePhotoPath: apiCard.profilePhotoPath,
                                companyLogoPath: apiCard.companyLogoPath,
                                coverGraphicPath: apiCard.coverGraphicPath,
                                theme: apiCard.theme,
                                emails: apiCard.emails,
                                phones: apiCard.phones,
                                websites: apiCard.websites,
                                address: apiCard.address,
                                isActive: apiCard.isActive,
                                createdAt: apiCard.createdAt,
                                updatedAt: apiCard.updatedAt
                            )
                            print("  📤 Pushing update to server:")
                            print("     - Name: \(updateCard.firstName) \(updateCard.lastName)")
                            print("     - Bio: \(updateCard.bio ?? "nil")")
                            print("     - Job Title: \(updateCard.jobTitle ?? "nil")")
                            print("     - Company: \(updateCard.companyName ?? "nil")")
                            _ = try await CardService.updateCard(updateCard)
                            print("  🔄 Updated card on server: \(card.fullName)")
                        } else {
                            print("  ⏭️ Skipping push (server version is newer): \(card.fullName)")
                        }
                    }
                } else {
                    // Create new card on server
                    let createdCard = try await CardService.createCard(apiCard)
                    
                    // Update local card with server ID
                    await MainActor.run {
                        cardEntity.serverCardId = createdCard.id
                        dataManager.save()
                    }
                    
                    print("  ✅ Created card: \(card.fullName)")
                }
            } catch {
                print("  ❌ Failed to sync card \(card.fullName): \(error.localizedDescription)")
            }
        }
    }
    
    /// Pull all cards from server and add to local database
    private func pullServerCards() async throws {
        print("⬇️ Pulling cards from server...")
        
        do {
            let serverCards = try await CardService.fetchCards()
            print("  📦 Received \(serverCards.count) cards from server")
            
            for serverCard in serverCards {
                // Check if we already have this card locally
                if let localCard = findLocalCard(withServerId: serverCard.id ?? "") {
                    // Card exists - check if server version is newer
                    if shouldUpdateLocalCard(localCard, with: serverCard) {
                        await MainActor.run {
                            updateLocalCard(localCard, from: serverCard)
                        }
                        print("  🔄 Updated card: \(serverCard.firstName) \(serverCard.lastName)")
                    } else {
                        print("  ⏭️ Card already up to date: \(serverCard.firstName) \(serverCard.lastName)")
                    }
                } else {
                    // Create new local card from server data
                    await MainActor.run {
                        createLocalCard(from: serverCard)
                    }
                    print("  ✅ Pulled card: \(serverCard.firstName) \(serverCard.lastName)")
                }
            }
        } catch {
            print("  ❌ Failed to pull cards from server: \(error.localizedDescription)")
            throw error
        }
    }
    
    /// Find local card by server ID
    private func findLocalCard(withServerId serverId: String) -> BusinessCardEntity? {
        return dataManager.businessCards.first { $0.serverCardId == serverId }
    }
    
    /// Check if local card should be updated with server version
    /// Returns true if server version is newer based on updatedAt timestamp
    private func shouldUpdateLocalCard(_ localCard: BusinessCardEntity, with serverCard: BusinessCardAPI) -> Bool {
        // Compare timestamps - newer wins
        guard let serverUpdatedAt = serverCard.updatedAt,
              let serverDate = parseServerDate(serverUpdatedAt),
              let localUpdatedAt = localCard.updatedAt else {
            // If we can't determine, update from server
            return true
        }
        
        // Update if server is newer
        let shouldUpdate = serverDate > localUpdatedAt
        if shouldUpdate {
            print("    📅 Server version is newer: \(serverDate) > \(localUpdatedAt)")
        } else {
            print("    📅 Local version is newer or equal: \(localUpdatedAt) >= \(serverDate)")
        }
        return shouldUpdate
    }
    
    /// Parse server date string to Date
    private func parseServerDate(_ dateString: String) -> Date? {
        let formatter = DateFormatter()
        formatter.dateFormat = "yyyy-MM-dd HH:mm:ss"
        formatter.timeZone = TimeZone(secondsFromGMT: 0)
        return formatter.date(from: dateString)
    }
    
    /// Check if local card should be pushed based on timestamp comparison
    /// - Parameters:
    ///   - localCard: The local card entity
    ///   - serverCard: The server card API model
    ///   - recentOnly: If true, only consider cards updated in last 30 seconds
    private func shouldPushBasedOnTimestamp(localCard: BusinessCardEntity, serverCard: BusinessCardAPI, recentOnly: Bool) -> Bool {
        // Get local timestamp
        guard let localUpdatedAt = localCard.updatedAt else {
            print("    ⚠️ No local timestamp, skipping push")
            return false
        }
        
        // If recentOnly, check if card was updated recently
        if recentOnly {
            let timeSinceUpdate = Date().timeIntervalSince(localUpdatedAt)
            if timeSinceUpdate >= 30.0 {
                print("    📅 Local card not recently updated (\(String(format: "%.1f", timeSinceUpdate))s ago), skipping")
                return false
            }
        }
        
        // Get server timestamp
        guard let serverUpdatedAt = serverCard.updatedAt,
              let serverDate = parseServerDate(serverUpdatedAt) else {
            print("    ⚠️ No server timestamp, pushing local version")
            return true
        }
        
        // Compare timestamps - local must be newer to push
        if localUpdatedAt > serverDate {
            print("    📅 Local is newer: \(localUpdatedAt) > \(serverDate) - PUSHING")
            return true
        } else {
            print("    📅 Server is newer or equal: \(serverDate) >= \(localUpdatedAt) - SKIPPING")
            return false
        }
    }
    
    /// Push all local cards to server (for manual sync - no timestamp comparison)
    /// - Parameter recentOnly: If true, only push cards updated in last 30 seconds
    private func pushLocalCards(recentOnly: Bool) async throws {
        print("⬆️ Pushing local cards to server...")
        
        let localCards = dataManager.businessCards
        
        for cardEntity in localCards {
            // Convert Core Data entity to BusinessCard model
            let card = dataManager.businessCardEntityToBusinessCard(cardEntity)
            
            do {
                let apiCard = convertToAPIModel(card)
                
                // Check if this card has been synced (has a serverCardId)
                if let serverId = cardEntity.serverCardId, !serverId.isEmpty {
                    // Update existing card on server (for manual sync)
                    var updateCard = apiCard
                    updateCard = BusinessCardAPI(
                        id: serverId,
                        userId: apiCard.userId,
                        firstName: apiCard.firstName,
                        lastName: apiCard.lastName,
                        phoneNumber: apiCard.phoneNumber,
                        companyName: apiCard.companyName,
                        jobTitle: apiCard.jobTitle,
                        bio: apiCard.bio,
                        profilePhotoPath: apiCard.profilePhotoPath,
                        companyLogoPath: apiCard.companyLogoPath,
                        coverGraphicPath: apiCard.coverGraphicPath,
                        theme: apiCard.theme,
                        emails: apiCard.emails,
                        phones: apiCard.phones,
                        websites: apiCard.websites,
                        address: apiCard.address,
                        isActive: apiCard.isActive,
                        createdAt: apiCard.createdAt,
                        updatedAt: apiCard.updatedAt
                    )
                    _ = try await CardService.updateCard(updateCard)
                    print("  🔄 Updated card on server: \(card.fullName)")
                } else {
                    // Create new card on server
                    let createdCard = try await CardService.createCard(apiCard)
                    
                    // Update local card with server ID
                    await MainActor.run {
                        cardEntity.serverCardId = createdCard.id
                        dataManager.save()
                    }
                    
                    print("  ✅ Created card: \(card.fullName)")
                }
            } catch {
                print("  ❌ Failed to sync card \(card.fullName): \(error.localizedDescription)")
            }
        }
    }
    
    /// Update existing local card with server data
    private func updateLocalCard(_ cardEntity: BusinessCardEntity, from apiCard: BusinessCardAPI) {
        let context = dataManager.persistentContainer.viewContext
        
        // Update basic info
        cardEntity.firstName = apiCard.firstName
        cardEntity.lastName = apiCard.lastName
        cardEntity.phoneNumber = apiCard.phoneNumber
        cardEntity.companyName = apiCard.companyName
        cardEntity.jobTitle = apiCard.jobTitle
        cardEntity.bio = apiCard.bio
        cardEntity.profilePhotoPath = apiCard.profilePhotoPath
        cardEntity.companyLogoPath = apiCard.companyLogoPath
        cardEntity.coverGraphicPath = apiCard.coverGraphicPath
        cardEntity.theme = apiCard.theme
        cardEntity.updatedAt = Date()
        
        // Download images from server if paths are available
        Task {
            await downloadImagesForCard(cardEntity, apiCard: apiCard)
        }
        
        // Delete existing related entities
        if let emails = cardEntity.additionalEmails as? Set<EmailContactEntity> {
            emails.forEach { context.delete($0) }
        }
        if let phones = cardEntity.additionalPhones as? Set<PhoneContactEntity> {
            phones.forEach { context.delete($0) }
        }
        if let websites = cardEntity.websiteLinks as? Set<WebsiteLinkEntity> {
            websites.forEach { context.delete($0) }
        }
        if let address = cardEntity.address {
            context.delete(address)
        }
        
        // Create new emails
        for emailAPI in apiCard.emails {
            let emailEntity = EmailContactEntity(context: context)
            emailEntity.id = UUID()
            emailEntity.email = emailAPI.email
            emailEntity.type = emailAPI.type
            emailEntity.label = emailAPI.label
            emailEntity.isPrimary = emailAPI.is_primary ?? false
            emailEntity.businessCard = cardEntity
        }
        
        // Create new phones
        for phoneAPI in apiCard.phones {
            let phoneEntity = PhoneContactEntity(context: context)
            phoneEntity.id = UUID()
            phoneEntity.phoneNumber = phoneAPI.phoneNumber
            phoneEntity.type = phoneAPI.type
            phoneEntity.label = phoneAPI.label
            phoneEntity.businessCard = cardEntity
        }
        
        // Create new websites
        for websiteAPI in apiCard.websites {
            let websiteEntity = WebsiteLinkEntity(context: context)
            websiteEntity.id = UUID()
            websiteEntity.url = websiteAPI.url
            websiteEntity.name = websiteAPI.name
            websiteEntity.websiteDescription = websiteAPI.description
            websiteEntity.isPrimary = websiteAPI.is_primary ?? false
            websiteEntity.businessCard = cardEntity
        }
        
        // Create new address
        if let addressAPI = apiCard.address {
            let addressEntity = AddressEntity(context: context)
            addressEntity.street = addressAPI.street
            addressEntity.city = addressAPI.city
            addressEntity.state = addressAPI.state
            addressEntity.zipCode = addressAPI.zipCode
            addressEntity.country = addressAPI.country
            addressEntity.businessCard = cardEntity
        }
        
        dataManager.save()
    }
    
    /// Create local Core Data card from server API model
    private func createLocalCard(from apiCard: BusinessCardAPI) {
        let context = dataManager.persistentContainer.viewContext
        let cardEntity = BusinessCardEntity(context: context)
        
        // Basic info
        cardEntity.id = UUID()
        cardEntity.serverCardId = apiCard.id
        cardEntity.firstName = apiCard.firstName
        cardEntity.lastName = apiCard.lastName
        cardEntity.phoneNumber = apiCard.phoneNumber
        cardEntity.companyName = apiCard.companyName
        cardEntity.jobTitle = apiCard.jobTitle
        cardEntity.bio = apiCard.bio
        cardEntity.profilePhotoPath = apiCard.profilePhotoPath
        cardEntity.companyLogoPath = apiCard.companyLogoPath
        cardEntity.coverGraphicPath = apiCard.coverGraphicPath
        cardEntity.theme = apiCard.theme
        
        // Emails
        for emailAPI in apiCard.emails {
            let emailEntity = EmailContactEntity(context: context)
            emailEntity.id = UUID()
            emailEntity.email = emailAPI.email
            emailEntity.type = emailAPI.type
            emailEntity.label = emailAPI.label
            emailEntity.isPrimary = emailAPI.is_primary ?? false
            emailEntity.businessCard = cardEntity
        }
        
        // Phones
        for phoneAPI in apiCard.phones {
            let phoneEntity = PhoneContactEntity(context: context)
            phoneEntity.id = UUID()
            phoneEntity.phoneNumber = phoneAPI.phoneNumber
            phoneEntity.type = phoneAPI.type
            phoneEntity.label = phoneAPI.label
            phoneEntity.businessCard = cardEntity
        }
        
        // Websites
        for websiteAPI in apiCard.websites {
            let websiteEntity = WebsiteLinkEntity(context: context)
            websiteEntity.id = UUID()
            websiteEntity.url = websiteAPI.url
            websiteEntity.name = websiteAPI.name
            websiteEntity.websiteDescription = websiteAPI.description
            websiteEntity.isPrimary = websiteAPI.is_primary ?? false
            websiteEntity.businessCard = cardEntity
        }
        
        // Address
        if let addressAPI = apiCard.address {
            let addressEntity = AddressEntity(context: context)
            addressEntity.street = addressAPI.street
            addressEntity.city = addressAPI.city
            addressEntity.state = addressAPI.state
            addressEntity.zipCode = addressAPI.zipCode
            addressEntity.country = addressAPI.country
            addressEntity.businessCard = cardEntity
        }
        
        dataManager.save()
        
        // Download images from server if paths are available
        Task {
            await downloadImagesForCard(cardEntity, apiCard: apiCard)
        }
    }
    
    /// Download images from server for a card entity
    private func downloadImagesForCard(_ cardEntity: BusinessCardEntity, apiCard: BusinessCardAPI) async {
        // Download profile photo
        if let profilePhotoPath = apiCard.profilePhotoPath, !profilePhotoPath.isEmpty {
            do {
                let image = try await MediaService.downloadImage(filename: profilePhotoPath)
                if let imageData = image.jpegData(compressionQuality: 0.8) {
                    await MainActor.run {
                        cardEntity.profilePhoto = imageData
                        dataManager.save()
                    }
                    print("  📷 Downloaded profile photo: \(profilePhotoPath)")
                }
            } catch {
                print("  ⚠️ Failed to download profile photo: \(error.localizedDescription)")
            }
        }
        
        // Download company logo
        if let companyLogoPath = apiCard.companyLogoPath, !companyLogoPath.isEmpty {
            do {
                let image = try await MediaService.downloadImage(filename: companyLogoPath)
                if let imageData = image.jpegData(compressionQuality: 0.8) {
                    await MainActor.run {
                        cardEntity.companyLogo = imageData
                        dataManager.save()
                    }
                    print("  🏢 Downloaded company logo: \(companyLogoPath)")
                }
            } catch {
                print("  ⚠️ Failed to download company logo: \(error.localizedDescription)")
            }
        }
        
        // Download cover graphic
        if let coverGraphicPath = apiCard.coverGraphicPath, !coverGraphicPath.isEmpty {
            do {
                let image = try await MediaService.downloadImage(filename: coverGraphicPath)
                if let imageData = image.jpegData(compressionQuality: 0.8) {
                    await MainActor.run {
                        cardEntity.coverGraphic = imageData
                        dataManager.save()
                    }
                    print("  🎨 Downloaded cover graphic: \(coverGraphicPath)")
                }
            } catch {
                print("  ⚠️ Failed to download cover graphic: \(error.localizedDescription)")
            }
        }
    }
    
    /// Convert BusinessCard model to API model
    private func convertToAPIModel(_ card: BusinessCard) -> BusinessCardAPI {
        return BusinessCardAPI(
            id: nil, // Will be assigned by server
            userId: nil, // Will be assigned by server
            firstName: card.firstName,
            lastName: card.lastName,
            phoneNumber: card.primaryPhone,
            companyName: card.companyName,
            jobTitle: card.jobTitle,
            bio: card.bio,
            profilePhotoPath: card.profilePhotoPath,
            companyLogoPath: card.companyLogoPath,
            coverGraphicPath: card.coverGraphicPath,
            theme: nil, // iOS app doesn't set theme, web only
            emails: card.additionalEmails.map { email in
                EmailContactAPI(
                    id: nil,
                    email: email.email,
                    type: email.type.rawValue,
                    label: email.label,
                    is_primary: email.isPrimary
                )
            },
            phones: card.additionalPhones.map { phone in
                PhoneContactAPI(
                    id: nil,
                    phoneNumber: phone.phoneNumber,
                    type: phone.type.rawValue,
                    label: phone.label  // Fix: was nil, should be phone.label
                )
            },
            websites: card.websiteLinks.map { website in
                WebsiteLinkAPI(
                    id: nil,
                    url: website.url,
                    name: website.name,
                    description: website.description,  // Fix: was nil, should be website.description
                    is_primary: website.isPrimary
                )
            },
            address: card.address.map { addr in
                AddressAPI(
                    id: nil,
                    street: addr.street,
                    city: addr.city,
                    state: addr.state,
                    zipCode: addr.zipCode,
                    country: addr.country
                )
            },
            isActive: true,
            createdAt: nil,
            updatedAt: nil
        )
    }
    
    // MARK: - Contacts Sync
    
    /// Sync contacts with server (pull-only to avoid duplication)
    private func syncContacts() async throws {
        print("📇 Starting contacts sync (pull-only)...")
        
        let contactsAPIClient = ContactsAPIClient()
        
        // Pull server contacts to local (source of truth)
        try await pullServerContactsToLocal(contactsAPIClient: contactsAPIClient)
        
        print("✅ Contacts sync complete!")
    }
    
    /// Push local contacts to server
    private func pushLocalContactsToServer(contactsAPIClient: ContactsAPIClient) async throws {
        print("⬆️ Pushing local contacts to server...")
        
        let localContacts = dataManager.fetchContacts()
        print("📱 Found \(localContacts.count) local contacts")
        
        for contactEntity in localContacts {
            let contact = contactEntity.toContact()
            
            do {
                // Check if contact exists on server by trying to fetch it
                do {
                    let _ = try await contactsAPIClient.getContact(id: contact.id)
                    // Contact exists on server - no need to push
                    print("  ⏭️ Contact already exists on server: \(contact.displayName)")
                } catch {
                    // Contact doesn't exist on server - create it
                    let contactData = ContactCreateData(
                        firstName: contact.firstName,
                        lastName: contact.lastName,
                        email: contact.email,
                        phone: contact.phone,
                        mobilePhone: contact.mobilePhone,
                        company: contact.company,
                        jobTitle: contact.jobTitle,
                        address: contact.address,
                        city: contact.city,
                        state: contact.state,
                        zipCode: contact.zipCode,
                        country: contact.country,
                        website: contact.website,
                        notes: contact.notes,
                        commentsFromLead: contact.commentsFromLead,
                        birthdate: contact.birthdate,
                        photoUrl: contact.photoUrl,
                        source: contact.source ?? "manual",
                        sourceMetadata: contact.sourceMetadata
                    )
                    
                    let _ = try await contactsAPIClient.createContact(contactData)
                    print("  ✅ Created contact on server: \(contact.displayName)")
                }
            } catch {
                print("  ❌ Failed to sync contact \(contact.displayName): \(error.localizedDescription)")
            }
        }
    }
    
    /// Pull server contacts to local
    private func pullServerContactsToLocal(contactsAPIClient: ContactsAPIClient) async throws {
        print("⬇️ Pulling server contacts to local...")
        
        let serverContacts = try await contactsAPIClient.fetchContacts()
        print("📦 Received \(serverContacts.count) contacts from server")
        
        // Clear existing local contacts
        let existingEntities = dataManager.fetchContacts()
        for entity in existingEntities {
            dataManager.deleteContact(entity)
        }
        
        // Add server contacts to local storage
        for contact in serverContacts {
            _ = dataManager.createContact(from: contact)
        }
        
        print("💾 Updated local storage with \(serverContacts.count) contacts")
    }
    
    /// Update local contacts with server data (legacy method - kept for compatibility)
    private func updateLocalContacts(_ serverContacts: [Contact]) async {
        print("💾 Updating local contacts with server data...")
        
        // Clear existing contacts
        let existingEntities = dataManager.fetchContacts()
        for entity in existingEntities {
            dataManager.deleteContact(entity)
        }
        
        // Add server contacts
        for contact in serverContacts {
            _ = dataManager.createContact(from: contact)
        }
        
        print("💾 Updated local storage with \(serverContacts.count) contacts")
    }
}

