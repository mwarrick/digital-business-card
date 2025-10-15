//
//  BusinessCardEditView.swift
//  ShareMyCard
//
//  Created by Mark Warrick on 10/10/25.
//

import SwiftUI

struct BusinessCardEditView: View {
    @Environment(\.dismiss) private var dismiss
    @StateObject private var dataManager = DataManager.shared
    
    let businessCardEntity: BusinessCardEntity
    @State private var businessCard: BusinessCard
    
    // MARK: - Form State
    @State private var firstName: String
    @State private var lastName: String
    @State private var phoneNumber: String
    @State private var companyName: String
    @State private var jobTitle: String
    @State private var bio: String
    
    // MARK: - Additional Contacts
    @State private var additionalEmails: [EmailContact]
    @State private var additionalPhones: [PhoneContact]
    @State private var websiteLinks: [WebsiteLink]
    
    // MARK: - Address
    @State private var street: String
    @State private var city: String
    @State private var state: String
    @State private var zipCode: String
    @State private var country: String
    
    // MARK: - Image State
    @State private var profilePhoto: UIImage?
    @State private var companyLogo: UIImage?
    @State private var coverGraphic: UIImage?
    @State private var profilePhotoPath: String?
    @State private var companyLogoPath: String?
    @State private var coverGraphicPath: String?
    
    // MARK: - UI State
    @State private var showingAddressSheet = false
    @State private var showingDeleteConfirmation = false
    @State private var editingEmail: EmailContact?
    @State private var editingPhone: PhoneContact?
    @State private var editingWebsite: WebsiteLink?
    
    // MARK: - Initializer
    init(businessCardEntity: BusinessCardEntity) {
        print("BusinessCardEditView: Initializing with entity: \(businessCardEntity)")
        self.businessCardEntity = businessCardEntity
        
        // Force load relationships to avoid lazy loading issues
        let _ = businessCardEntity.additionalEmails
        let _ = businessCardEntity.additionalPhones
        let _ = businessCardEntity.websiteLinks
        let _ = businessCardEntity.address
        
        self._businessCard = State(initialValue: DataManager.shared.businessCardEntityToBusinessCard(businessCardEntity))
        
        // Initialize form state with existing data
        self._firstName = State(initialValue: businessCardEntity.firstName ?? "")
        self._lastName = State(initialValue: businessCardEntity.lastName ?? "")
        self._phoneNumber = State(initialValue: businessCardEntity.phoneNumber ?? "")
        self._companyName = State(initialValue: businessCardEntity.companyName ?? "")
        self._jobTitle = State(initialValue: businessCardEntity.jobTitle ?? "")
        self._bio = State(initialValue: businessCardEntity.bio ?? "")
        
        // Initialize additional contacts
        let emails: [EmailContact] = (businessCardEntity.additionalEmails as? Set<EmailContactEntity>)?.compactMap { emailEntity in
            guard let email = emailEntity.email,
                  let typeString = emailEntity.type,
                  let type = EmailType(rawValue: typeString) else { return nil }
            return EmailContact(id: emailEntity.id ?? UUID(), email: email, type: type, label: emailEntity.label, isPrimary: emailEntity.isPrimary)
        } ?? []
        self._additionalEmails = State(initialValue: emails)
        
        let phones: [PhoneContact] = (businessCardEntity.additionalPhones as? Set<PhoneContactEntity>)?.compactMap { phoneEntity in
            guard let phoneNumber = phoneEntity.phoneNumber,
                  let typeString = phoneEntity.type,
                  let type = PhoneType(rawValue: typeString) else { return nil }
            return PhoneContact(id: phoneEntity.id ?? UUID(), phoneNumber: phoneNumber, type: type, label: phoneEntity.label)
        } ?? []
        self._additionalPhones = State(initialValue: phones)
        
        let websites: [WebsiteLink] = (businessCardEntity.websiteLinks as? Set<WebsiteLinkEntity>)?.compactMap { websiteEntity in
            guard let name = websiteEntity.name,
                  let url = websiteEntity.url else { return nil }
            return WebsiteLink(id: websiteEntity.id ?? UUID(), name: name, url: url, description: websiteEntity.websiteDescription, isPrimary: websiteEntity.isPrimary)
        } ?? []
        self._websiteLinks = State(initialValue: websites)
        
        // Initialize address
        let address = businessCardEntity.address
        self._street = State(initialValue: address?.street ?? "")
        self._city = State(initialValue: address?.city ?? "")
        self._state = State(initialValue: address?.state ?? "")
        self._zipCode = State(initialValue: address?.zipCode ?? "")
        self._country = State(initialValue: address?.country ?? "")
        
        // Initialize images
        self._profilePhoto = State(initialValue: businessCardEntity.profilePhoto.flatMap { UIImage(data: $0) })
        self._companyLogo = State(initialValue: businessCardEntity.companyLogo.flatMap { UIImage(data: $0) })
        self._coverGraphic = State(initialValue: businessCardEntity.coverGraphic.flatMap { UIImage(data: $0) })
        
        // Initialize server paths
        self._profilePhotoPath = State(initialValue: businessCardEntity.profilePhotoPath)
        self._companyLogoPath = State(initialValue: businessCardEntity.companyLogoPath)
        self._coverGraphicPath = State(initialValue: businessCardEntity.coverGraphicPath)
    }
    
    var body: some View {
        NavigationView {
            Form {
                // MARK: - Required Information Section
                Section("Required Information") {
                    HStack {
                        TextField("First Name", text: $firstName)
                        TextField("Last Name", text: $lastName)
                    }
                    
                    TextField("Phone Number", text: $phoneNumber)
                        .keyboardType(.phonePad)
                }
                
                // MARK: - Professional Information Section
                Section("Professional Information") {
                    TextField("Company Name", text: $companyName)
                    TextField("Job Title", text: $jobTitle)
                }
                
                // MARK: - Additional Contacts Section
                Section("Additional Contacts") {
                    // Additional Emails
                    VStack(alignment: .leading, spacing: 8) {
                        HStack {
                            Text("Email Addresses")
                            Spacer()
                            Button("Add Email") {
                                print("🔘 Add Email tapped in Edit View")
                                // Create empty email to trigger sheet
                                editingEmail = EmailContact(email: "", type: .work, label: nil)
                            }
                            .buttonStyle(.bordered)
                        }
                        
                        ForEach(additionalEmails) { email in
                            HStack {
                                VStack(alignment: .leading) {
                                    Text(email.email)
                                        .font(.caption)
                                    Text(email.type.displayName)
                                        .font(.caption2)
                                        .foregroundColor(.secondary)
                                }
                                Spacer()
                                HStack {
                                    Button("Edit") {
                                        print("BusinessCardEditView: Editing email: \(email)")
                                        editingEmail = email
                                    }
                                    .buttonStyle(.bordered)
                                    
                                    Button("Remove") {
                                        additionalEmails.removeAll { $0.id == email.id }
                                    }
                                    .buttonStyle(.bordered)
                                    .foregroundColor(.red)
                                }
                            }
                        }
                    }
                    
                    // Additional Phones
                    VStack(alignment: .leading, spacing: 8) {
                        HStack {
                            Text("Phone Numbers")
                            Spacer()
                            Button("Add Phone") {
                                print("🔘 Add Phone tapped in Edit View")
                                // Create empty phone to trigger sheet
                                editingPhone = PhoneContact(phoneNumber: "", type: .mobile, label: nil)
                            }
                            .buttonStyle(.bordered)
                        }
                        
                        ForEach(additionalPhones) { phone in
                            HStack {
                                VStack(alignment: .leading) {
                                    Text(phone.phoneNumber)
                                        .font(.caption)
                                    Text(phone.type.displayName)
                                        .font(.caption2)
                                        .foregroundColor(.secondary)
                                }
                                Spacer()
                                HStack {
                                    Button("Edit") {
                                        editingPhone = phone
                                    }
                                    .buttonStyle(.bordered)
                                    
                                    Button("Remove") {
                                        additionalPhones.removeAll { $0.id == phone.id }
                                    }
                                    .buttonStyle(.bordered)
                                    .foregroundColor(.red)
                                }
                            }
                        }
                    }
                }
                
                // MARK: - Website Links Section
                Section("Website Links") {
                    HStack {
                        Text("Websites")
                        Spacer()
                        Button("Add Website") {
                            print("🔘 Add Website tapped in Edit View")
                            // Create empty website to trigger sheet
                            editingWebsite = WebsiteLink(name: "", url: "", description: nil)
                        }
                        .buttonStyle(.bordered)
                    }
                    
                    ForEach(websiteLinks) { website in
                        VStack(alignment: .leading) {
                            HStack {
                                Text(website.name)
                                    .font(.caption)
                                    .fontWeight(.semibold)
                                Spacer()
                                HStack {
                                    Button("Edit") {
                                        editingWebsite = website
                                    }
                                    .buttonStyle(.bordered)
                                    
                                    Button("Remove") {
                                        websiteLinks.removeAll { $0.id == website.id }
                                    }
                                    .buttonStyle(.bordered)
                                    .foregroundColor(.red)
                                }
                            }
                            Text(website.url)
                                .font(.caption2)
                                .foregroundColor(.blue)
                        }
                    }
                }
                
                // MARK: - Address Section
                Section("Address") {
                    Button("Edit Address") {
                        showingAddressSheet = true
                    }
                    .buttonStyle(.bordered)
                    
                    if !street.isEmpty || !city.isEmpty {
                        VStack(alignment: .leading) {
                            Group {
                                if !street.isEmpty { Text(street) }
                                if !city.isEmpty { Text(city) }
                                if !state.isEmpty { Text(state) }
                                if !zipCode.isEmpty { Text(zipCode) }
                                if !country.isEmpty { Text(country) }
                            }
                        }
                        .font(.caption)
                    }
                }
                
                // MARK: - About Section
                Section("About") {
                    TextField("Tell us about yourself...", text: $bio, axis: .vertical)
                        .lineLimit(3...6)
                }
                
                // MARK: - Media Section
                Section("Media") {
                    ImageSelectionView(
                        selectedImage: $profilePhoto,
                        serverPath: $profilePhotoPath,
                        imageType: .profilePhoto
                    )
                    
                    ImageSelectionView(
                        selectedImage: $companyLogo,
                        serverPath: $companyLogoPath,
                        imageType: .companyLogo
                    )
                    
                    ImageSelectionView(
                        selectedImage: $coverGraphic,
                        serverPath: $coverGraphicPath,
                        imageType: .coverGraphic
                    )
                }
                
                // MARK: - Danger Zone
                Section("Danger Zone") {
                    Button("Delete Business Card") {
                        showingDeleteConfirmation = true
                    }
                    .foregroundColor(.red)
                    .buttonStyle(.bordered)
                }
            }
            .navigationTitle("Edit Business Card")
            .navigationBarTitleDisplayMode(.inline)
            .alert("Delete Business Card?", isPresented: $showingDeleteConfirmation) {
                Button("Cancel", role: .cancel) { }
                Button("Delete", role: .destructive) {
                    deleteBusinessCard()
                }
            } message: {
                Text("This action cannot be undone. The card will be deleted from both your device and the server.")
            }
            .toolbar {
                ToolbarItem(placement: .navigationBarLeading) {
                    Button("Cancel") {
                        dismiss()
                    }
                }
                
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button("Save") {
                        saveBusinessCard()
                    }
                    .disabled(!isFormValid)
                }
            }
        }
        .sheet(item: $editingEmail) { email in
            let _ = print("BusinessCardEditView: Presenting email sheet with editingEmail: \(email.email)")
            let isNewEmail = email.email.isEmpty
            AddEmailView(
                email: email.email,
                type: email.type,
                label: email.label ?? "",
                isPrimary: email.isPrimary,
                existingEmail: isNewEmail ? nil : email,
                onSave: { updatedEmail in
                    if isNewEmail {
                        // Add new email
                        additionalEmails.append(updatedEmail)
                    } else {
                        // Update existing email
                        if let index = additionalEmails.firstIndex(where: { $0.id == email.id }) {
                            additionalEmails[index] = updatedEmail
                        }
                    }
                    
                    // Ensure only one primary email
                    if updatedEmail.isPrimary {
                        for i in additionalEmails.indices {
                            if additionalEmails[i].id != updatedEmail.id {
                                additionalEmails[i] = EmailContact(
                                    id: additionalEmails[i].id,
                                    email: additionalEmails[i].email,
                                    type: additionalEmails[i].type,
                                    label: additionalEmails[i].label,
                                    isPrimary: false
                                )
                            }
                        }
                    }
                }
            )
        }
        .sheet(item: $editingPhone) { phone in
            let isNewPhone = phone.phoneNumber.isEmpty
            AddPhoneView(
                phoneNumber: phone.phoneNumber,
                type: phone.type,
                label: phone.label ?? "",
                existingPhone: isNewPhone ? nil : phone,
                onSave: { updatedPhone in
                    if isNewPhone {
                        // Add new phone
                        additionalPhones.append(updatedPhone)
                    } else {
                        // Update existing phone
                        if let index = additionalPhones.firstIndex(where: { $0.id == phone.id }) {
                            additionalPhones[index] = updatedPhone
                        }
                    }
                }
            )
        }
        .sheet(item: $editingWebsite) { website in
            let isNewWebsite = website.url.isEmpty
            AddWebsiteView(
                name: website.name,
                url: website.url,
                description: website.description ?? "",
                isPrimary: website.isPrimary,
                existingWebsite: isNewWebsite ? nil : website,
                onSave: { updatedWebsite in
                    if isNewWebsite {
                        // Add new website
                        websiteLinks.append(updatedWebsite)
                    } else {
                        // Update existing website
                        if let index = websiteLinks.firstIndex(where: { $0.id == website.id }) {
                            websiteLinks[index] = updatedWebsite
                        }
                    }
                    
                    // Ensure only one primary website
                    if updatedWebsite.isPrimary {
                        for i in websiteLinks.indices {
                            if websiteLinks[i].id != updatedWebsite.id {
                                websiteLinks[i] = WebsiteLink(
                                    id: websiteLinks[i].id,
                                    name: websiteLinks[i].name,
                                    url: websiteLinks[i].url,
                                    description: websiteLinks[i].description,
                                    isPrimary: false
                                )
                            }
                        }
                    }
                }
            )
        }
        .sheet(isPresented: $showingAddressSheet) {
            AddAddressView(
                street: $street,
                city: $city,
                state: $state,
                zipCode: $zipCode,
                country: $country
            )
        }
    }
    
    // MARK: - Form Validation
    private var isFormValid: Bool {
        !firstName.isEmpty && !lastName.isEmpty && !phoneNumber.isEmpty
    }
    
    // MARK: - Save Business Card
    private func saveBusinessCard() {
        var updatedBusinessCard = BusinessCard(
            firstName: firstName,
            lastName: lastName,
            phoneNumber: phoneNumber
        )
        
        // Set optional fields
        updatedBusinessCard.companyName = companyName.isEmpty ? nil : companyName
        updatedBusinessCard.jobTitle = jobTitle.isEmpty ? nil : jobTitle
        updatedBusinessCard.bio = bio.isEmpty ? nil : bio
        updatedBusinessCard.additionalEmails = additionalEmails
        updatedBusinessCard.additionalPhones = additionalPhones
        updatedBusinessCard.websiteLinks = websiteLinks
        
        // Process and set images
        if let profilePhoto = profilePhoto {
            updatedBusinessCard.profilePhoto = ImageCompressionUtility.processImageForBusinessCard(profilePhoto, type: .profilePhoto)
        }
        if let companyLogo = companyLogo {
            updatedBusinessCard.companyLogo = ImageCompressionUtility.processImageForBusinessCard(companyLogo, type: .companyLogo)
        }
        if let coverGraphic = coverGraphic {
            updatedBusinessCard.coverGraphic = ImageCompressionUtility.processImageForBusinessCard(coverGraphic, type: .coverGraphic)
        }
        
        // Set server image paths
        updatedBusinessCard.profilePhotoPath = profilePhotoPath
        updatedBusinessCard.companyLogoPath = companyLogoPath
        updatedBusinessCard.coverGraphicPath = coverGraphicPath
        
        // Set address if any field is filled
        if !street.isEmpty || !city.isEmpty || !state.isEmpty || !zipCode.isEmpty || !country.isEmpty {
            updatedBusinessCard.address = Address(
                street: street.isEmpty ? nil : street,
                city: city.isEmpty ? nil : city,
                state: state.isEmpty ? nil : state,
                zipCode: zipCode.isEmpty ? nil : zipCode,
                country: country.isEmpty ? nil : country
            )
        }
        
        // Update in Core Data
        dataManager.updateBusinessCard(businessCardEntity, with: updatedBusinessCard)
        
        // Push to server and upload images
        Task {
            do {
                try await SyncManager.shared.pushToServer()
                
                // After card is synced, upload images if we have a server ID
                if let serverId = businessCardEntity.serverCardId, !serverId.isEmpty {
                    await uploadImagesForCard(serverId: serverId)
                }
            } catch {
                print("⚠️ Auto-sync failed: \(error.localizedDescription)")
                // Don't block the UI - sync will happen on next manual sync or login
            }
        }
        
        // Dismiss the view
        dismiss()
    }
    
    // MARK: - Delete Business Card
    private func deleteBusinessCard() {
        let serverCardId = businessCardEntity.serverCardId
        
        // Delete from local Core Data
        dataManager.deleteBusinessCard(businessCardEntity)
        
        // Delete from server if it has a server ID
        if let serverId = serverCardId, !serverId.isEmpty {
            Task {
                do {
                    print("🔄 Deleting card from server...")
                    try await CardService.deleteCard(id: serverId)
                    print("✅ Card deleted from server")
                } catch {
                    print("⚠️ Server delete failed: \(error.localizedDescription)")
                    // Local delete still succeeded, so don't block the UI
                }
            }
        }
        
        dismiss()
    }
    
    // MARK: - Upload Images
    private func uploadImagesForCard(serverId: String) async {
        // Upload profile photo
        if let profilePhoto = profilePhoto, businessCardEntity.profilePhotoPath == nil || profilePhotoPath == nil {
            do {
                let response = try await MediaService.uploadImage(
                    profilePhoto,
                    type: APIConfig.MediaType.profilePhoto,
                    businessCardId: serverId
                )
                businessCardEntity.profilePhotoPath = response.filename
                print("✅ Profile photo uploaded: \(response.filename)")
            } catch {
                print("❌ Profile photo upload failed: \(error.localizedDescription)")
            }
        }
        
        // Upload company logo
        if let companyLogo = companyLogo, businessCardEntity.companyLogoPath == nil || companyLogoPath == nil {
            do {
                let response = try await MediaService.uploadImage(
                    companyLogo,
                    type: APIConfig.MediaType.companyLogo,
                    businessCardId: serverId
                )
                businessCardEntity.companyLogoPath = response.filename
                print("✅ Company logo uploaded: \(response.filename)")
            } catch {
                print("❌ Company logo upload failed: \(error.localizedDescription)")
            }
        }
        
        // Upload cover graphic
        if let coverGraphic = coverGraphic, businessCardEntity.coverGraphicPath == nil || coverGraphicPath == nil {
            do {
                let response = try await MediaService.uploadImage(
                    coverGraphic,
                    type: APIConfig.MediaType.coverGraphic,
                    businessCardId: serverId
                )
                businessCardEntity.coverGraphicPath = response.filename
                print("✅ Cover graphic uploaded: \(response.filename)")
            } catch {
                print("❌ Cover graphic upload failed: \(error.localizedDescription)")
            }
        }
        
        // Save updated paths to Core Data
        if profilePhoto != nil || companyLogo != nil || coverGraphic != nil {
            DataManager.shared.save()
            
            // Sync again to ensure paths are consistent with server
            do {
                print("🔄 Syncing after image upload...")
                try await SyncManager.shared.performFullSync()
                print("✅ Post-upload sync complete")
            } catch {
                print("⚠️ Post-upload sync failed: \(error.localizedDescription)")
            }
        }
    }
}

// MARK: - Updated Supporting Views with Edit Support

#Preview {
    // This would need a sample BusinessCardEntity for preview
    Text("BusinessCardEditView Preview")
}
