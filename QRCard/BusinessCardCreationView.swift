//
//  BusinessCardCreationView.swift
//  ShareMyCard
//
//  Created by Mark Warrick on 10/10/25.
//

import SwiftUI

struct BusinessCardCreationView: View {
    @Environment(\.dismiss) private var dismiss
    @StateObject private var dataManager = DataManager.shared
    
    // MARK: - Form State
    @State private var firstName = ""
    @State private var lastName = ""
    @State private var phoneNumber = ""
    @State private var companyName = ""
    @State private var jobTitle = ""
    @State private var bio = ""
    
    // MARK: - Additional Contacts
    @State private var additionalEmails: [EmailContact] = []
    @State private var additionalPhones: [PhoneContact] = []
    @State private var websiteLinks: [WebsiteLink] = []
    
    // MARK: - Address
    @State private var street = ""
    @State private var city = ""
    @State private var state = ""
    @State private var zipCode = ""
    @State private var country = ""
    
    // MARK: - Image State
    @State private var profilePhoto: UIImage?
    @State private var companyLogo: UIImage?
    @State private var coverGraphic: UIImage?
    @State private var profilePhotoPath: String?
    @State private var companyLogoPath: String?
    @State private var coverGraphicPath: String?
    
    // MARK: - UI State
    @State private var showingEmailSheet = false
    @State private var showingPhoneSheet = false
    @State private var showingWebsiteSheet = false
    @State private var showingAddressSheet = false
    
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
                
                // MARK: - Email Addresses Section
                Section(header: Text("Email Addresses")) {
                    Button(action: {
                        print("🔘 Add Email button tapped")
                        showingEmailSheet = true
                        print("🔘 showingEmailSheet = \(showingEmailSheet)")
                    }) {
                        Label("Add Email Address", systemImage: "plus.circle.fill")
                    }
                    
                    ForEach(additionalEmails) { email in
                        HStack {
                            VStack(alignment: .leading) {
                                Text(email.email)
                                    .font(.body)
                                Text(email.type.displayName)
                                    .font(.caption)
                                    .foregroundColor(.secondary)
                            }
                            Spacer()
                            Button("Remove") {
                                additionalEmails.removeAll { $0.id == email.id }
                            }
                            .foregroundColor(.red)
                        }
                    }
                }
                
                // MARK: - Phone Numbers Section
                Section(header: Text("Phone Numbers")) {
                    Button(action: {
                        print("🔘 Add Phone button tapped")
                        showingPhoneSheet = true
                        print("🔘 showingPhoneSheet = \(showingPhoneSheet)")
                    }) {
                        Label("Add Phone Number", systemImage: "plus.circle.fill")
                    }
                    
                    ForEach(additionalPhones) { phone in
                        HStack {
                            VStack(alignment: .leading) {
                                Text(phone.phoneNumber)
                                    .font(.body)
                                Text(phone.type.displayName)
                                    .font(.caption)
                                    .foregroundColor(.secondary)
                            }
                            Spacer()
                            Button("Remove") {
                                additionalPhones.removeAll { $0.id == phone.id }
                            }
                            .foregroundColor(.red)
                        }
                    }
                }
                
                // MARK: - Website Links Section
                Section(header: Text("Website Links")) {
                    Button(action: {
                        print("🔘 Add Website button tapped")
                        showingWebsiteSheet = true
                        print("🔘 showingWebsiteSheet = \(showingWebsiteSheet)")
                    }) {
                        Label("Add Website", systemImage: "plus.circle.fill")
                    }
                    
                    ForEach(websiteLinks) { website in
                        VStack(alignment: .leading, spacing: 4) {
                            HStack {
                                Text(website.name)
                                    .font(.body)
                                Spacer()
                                Button("Remove") {
                                    websiteLinks.removeAll { $0.id == website.id }
                                }
                                .foregroundColor(.red)
                            }
                            Text(website.url)
                                .font(.caption)
                                .foregroundColor(.secondary)
                        }
                    }
                }
                
                // MARK: - Address Section
                Section("Address") {
                    Button("Add Address") {
                        showingAddressSheet = true
                    }
                    .buttonStyle(.bordered)
                    
                    if !street.isEmpty || !city.isEmpty {
                        VStack(alignment: .leading) {
                            if !street.isEmpty { Text(street) }
                            if !city.isEmpty { Text(city) }
                            if !state.isEmpty { Text(state) }
                            if !zipCode.isEmpty { Text(zipCode) }
                            if !country.isEmpty { Text(country) }
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
            }
            .navigationTitle("Create Business Card")
            .navigationBarTitleDisplayMode(.inline)
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
        .sheet(isPresented: $showingEmailSheet) {
            print("📧 Showing Email Sheet")
            return AddEmailView(onSave: { email in
                print("📧 Email saved: \(email.email)")
                additionalEmails.append(email)
            })
        }
        .sheet(isPresented: $showingPhoneSheet) {
            print("📱 Showing Phone Sheet")
            return AddPhoneView(onSave: { phone in
                print("📱 Phone saved: \(phone.phoneNumber)")
                additionalPhones.append(phone)
            })
        }
        .sheet(isPresented: $showingWebsiteSheet) {
            print("🌐 Showing Website Sheet")
            return AddWebsiteView(onSave: { website in
                print("🌐 Website saved: \(website.url)")
                websiteLinks.append(website)
            })
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
        var businessCard = BusinessCard(
            firstName: firstName,
            lastName: lastName,
            phoneNumber: phoneNumber
        )
        
        // Set optional fields
        businessCard.companyName = companyName.isEmpty ? nil : companyName
        businessCard.jobTitle = jobTitle.isEmpty ? nil : jobTitle
        businessCard.bio = bio.isEmpty ? nil : bio
        businessCard.additionalEmails = additionalEmails
        businessCard.additionalPhones = additionalPhones
        businessCard.websiteLinks = websiteLinks
        
        // Process and set images
        if let profilePhoto = profilePhoto {
            businessCard.profilePhoto = ImageCompressionUtility.processImageForBusinessCard(profilePhoto, type: .profilePhoto)
        }
        if let companyLogo = companyLogo {
            businessCard.companyLogo = ImageCompressionUtility.processImageForBusinessCard(companyLogo, type: .companyLogo)
        }
        if let coverGraphic = coverGraphic {
            businessCard.coverGraphic = ImageCompressionUtility.processImageForBusinessCard(coverGraphic, type: .coverGraphic)
        }
        
        // Set server image paths
        businessCard.profilePhotoPath = profilePhotoPath
        businessCard.companyLogoPath = companyLogoPath
        businessCard.coverGraphicPath = coverGraphicPath
        
        // Set address if any field is filled
        if !street.isEmpty || !city.isEmpty || !state.isEmpty || !zipCode.isEmpty || !country.isEmpty {
            businessCard.address = Address(
                street: street.isEmpty ? nil : street,
                city: city.isEmpty ? nil : city,
                state: state.isEmpty ? nil : state,
                zipCode: zipCode.isEmpty ? nil : zipCode,
                country: country.isEmpty ? nil : country
            )
        }
        
        // Save to Core Data
        let cardEntity = dataManager.createBusinessCard(from: businessCard)
        
        // Push to server and upload images
        Task {
            do {
                try await SyncManager.shared.pushToServer()
                
                // After card is synced, upload images if we have a server ID
                if let serverId = cardEntity.serverCardId, !serverId.isEmpty {
                    await uploadImagesForCard(serverId: serverId, cardEntity: cardEntity)
                }
            } catch {
                print("⚠️ Auto-sync failed: \(error.localizedDescription)")
                // Don't block the UI - sync will happen on next manual sync or login
            }
        }
        
        // Dismiss the view
        dismiss()
    }
    
    // MARK: - Upload Images
    private func uploadImagesForCard(serverId: String, cardEntity: BusinessCardEntity) async {
        // Upload profile photo
        if let profilePhoto = profilePhoto {
            do {
                let response = try await MediaService.uploadImage(
                    profilePhoto,
                    type: APIConfig.MediaType.profilePhoto,
                    businessCardId: serverId
                )
                cardEntity.profilePhotoPath = response.filename
                print("✅ Profile photo uploaded: \(response.filename)")
            } catch {
                print("❌ Profile photo upload failed: \(error.localizedDescription)")
            }
        }
        
        // Upload company logo
        if let companyLogo = companyLogo {
            do {
                let response = try await MediaService.uploadImage(
                    companyLogo,
                    type: APIConfig.MediaType.companyLogo,
                    businessCardId: serverId
                )
                cardEntity.companyLogoPath = response.filename
                print("✅ Company logo uploaded: \(response.filename)")
            } catch {
                print("❌ Company logo upload failed: \(error.localizedDescription)")
            }
        }
        
        // Upload cover graphic
        if let coverGraphic = coverGraphic {
            do {
                let response = try await MediaService.uploadImage(
                    coverGraphic,
                    type: APIConfig.MediaType.coverGraphic,
                    businessCardId: serverId
                )
                cardEntity.coverGraphicPath = response.filename
                print("✅ Cover graphic uploaded: \(response.filename)")
            } catch {
                print("❌ Cover graphic upload failed: \(error.localizedDescription)")
            }
        }
        
        // Save updated paths to Core Data
        if profilePhoto != nil || companyLogo != nil || coverGraphic != nil {
            dataManager.save()
            
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

// MARK: - Supporting Views

struct AddAddressView: View {
    @Environment(\.dismiss) private var dismiss
    @Binding var street: String
    @Binding var city: String
    @Binding var state: String
    @Binding var zipCode: String
    @Binding var country: String
    
    var body: some View {
        NavigationView {
            Form {
                Section("Address Information") {
                    TextField("Street Address", text: $street)
                    TextField("City", text: $city)
                    TextField("State", text: $state)
                    TextField("ZIP Code", text: $zipCode)
                        .keyboardType(.numberPad)
                    TextField("Country", text: $country)
                }
            }
            .navigationTitle(street.isEmpty && city.isEmpty ? "Add Address" : "Edit Address")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarLeading) {
                    Button("Cancel") {
                        dismiss()
                    }
                }
                
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button("Save") {
                        dismiss()
                    }
                }
            }
        }
    }
}

#Preview {
    BusinessCardCreationView()
}
