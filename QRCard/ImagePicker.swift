//
//  ImagePicker.swift
//  ShareMyCard
//
//  Created by Mark Warrick on 10/10/25.
//

import SwiftUI
import PhotosUI
import UIKit

// MARK: - Image Picker Coordinator
class ImagePickerCoordinator: NSObject, PHPickerViewControllerDelegate, UIImagePickerControllerDelegate, UINavigationControllerDelegate {
    let parent: ImagePicker
    
    init(parent: ImagePicker) {
        self.parent = parent
    }
    
    // MARK: - PHPickerViewControllerDelegate
    func picker(_ picker: PHPickerViewController, didFinishPicking results: [PHPickerResult]) {
        picker.dismiss(animated: true)
        
        guard let provider = results.first?.itemProvider else { return }
        
        if provider.canLoadObject(ofClass: UIImage.self) {
            provider.loadObject(ofClass: UIImage.self) { [weak self] image, _ in
                DispatchQueue.main.async {
                    if let uiImage = image as? UIImage {
                        self?.parent.selectedImage = uiImage
                    }
                }
            }
        }
    }
    
    // MARK: - UIImagePickerControllerDelegate
    func imagePickerController(_ picker: UIImagePickerController, didFinishPickingMediaWithInfo info: [UIImagePickerController.InfoKey : Any]) {
        picker.dismiss(animated: true)
        
        if let uiImage = info[.originalImage] as? UIImage {
            parent.selectedImage = uiImage
        }
    }
    
    func imagePickerControllerDidCancel(_ picker: UIImagePickerController) {
        picker.dismiss(animated: true)
    }
}

// MARK: - Image Picker
struct ImagePicker: UIViewControllerRepresentable {
    @Binding var selectedImage: UIImage?
    let sourceType: UIImagePickerController.SourceType
    
    func makeCoordinator() -> ImagePickerCoordinator {
        ImagePickerCoordinator(parent: self)
    }
    
    func makeUIViewController(context: Context) -> UIImagePickerController {
        let picker = UIImagePickerController()
        picker.sourceType = sourceType
        picker.delegate = context.coordinator
        return picker
    }
    
    func updateUIViewController(_ uiViewController: UIImagePickerController, context: Context) {}
}

// MARK: - Photo Picker
struct PhotoPicker: UIViewControllerRepresentable {
    @Binding var selectedImage: UIImage?
    
    func makeCoordinator() -> ImagePickerCoordinator {
        ImagePickerCoordinator(parent: ImagePicker(selectedImage: $selectedImage, sourceType: .photoLibrary))
    }
    
    func makeUIViewController(context: Context) -> PHPickerViewController {
        var config = PHPickerConfiguration()
        config.filter = .images
        config.selectionLimit = 1
        
        let picker = PHPickerViewController(configuration: config)
        picker.delegate = context.coordinator
        return picker
    }
    
    func updateUIViewController(_ uiViewController: PHPickerViewController, context: Context) {}
}

// MARK: - Image Selection View
struct ImageSelectionView: View {
    @Binding var selectedImage: UIImage?
    @Binding var serverPath: String?
    let imageType: ImageType
    @State private var showingImagePicker = false
    @State private var showingActionSheet = false
    @State private var showingPhotoLibrary = false
    @State private var showingCamera = false
    @State private var showingCropper = false
    @State private var imageToCrop: UIImage?
    @State private var isUploading = false
    @State private var uploadProgress: Double = 0
    @State private var uploadError: String?
    
    enum ImageType {
        case profilePhoto, companyLogo, coverGraphic
        
        var title: String {
            switch self {
            case .profilePhoto: return "Profile Photo"
            case .companyLogo: return "Company Logo"
            case .coverGraphic: return "Cover Graphic"
            }
        }
        
        var icon: String {
            switch self {
            case .profilePhoto: return "person.circle"
            case .companyLogo: return "building.2"
            case .coverGraphic: return "photo"
            }
        }
        
        var serverType: String {
            switch self {
            case .profilePhoto: return APIConfig.MediaType.profilePhoto
            case .companyLogo: return APIConfig.MediaType.companyLogo
            case .coverGraphic: return APIConfig.MediaType.coverGraphic
            }
        }
    }
    
    var body: some View {
        VStack(spacing: 16) {
            // Current Image Display
            if let selectedImage = selectedImage {
                VStack(spacing: 12) {
                    Text("Current \(imageType.title)")
                        .font(.headline)
                    
                    Image(uiImage: selectedImage)
                        .resizable()
                        .aspectRatio(contentMode: .fit)
                        .frame(maxHeight: 200)
                        .cornerRadius(12)
                        .shadow(radius: 4)
                    
                    // Upload Progress
                    if isUploading {
                        VStack(spacing: 8) {
                            ProgressView("Uploading...")
                                .progressViewStyle(CircularProgressViewStyle())
                        }
                    } else if let uploadError = uploadError {
                        Text("Upload failed: \(uploadError)")
                            .font(.caption)
                            .foregroundColor(.red)
                    } else if serverPath != nil {
                        Label("Synced to server", systemImage: "checkmark.icloud")
                            .font(.caption)
                            .foregroundColor(.green)
                    }
                    
                    HStack(spacing: 12) {
                        Button("Change Image") {
                            showingActionSheet = true
                        }
                        .buttonStyle(.bordered)
                        .disabled(isUploading)
                        
                        Button("Remove Image") {
                            self.selectedImage = nil
                            self.serverPath = nil
                            self.uploadError = nil
                        }
                        .buttonStyle(.bordered)
                        .foregroundColor(.red)
                        .disabled(isUploading)
                    }
                }
            } else {
                // No Image State
                VStack(spacing: 12) {
                    Image(systemName: imageType.icon)
                        .font(.system(size: 60))
                        .foregroundColor(.secondary)
                    
                    Text("No \(imageType.title)")
                        .font(.headline)
                        .foregroundColor(.secondary)
                    
                    Button("Add \(imageType.title)") {
                        showingActionSheet = true
                    }
                    .buttonStyle(.borderedProminent)
                }
                .frame(height: 200)
                .frame(maxWidth: .infinity)
                .background(Color.gray.opacity(0.1))
                .cornerRadius(12)
            }
        }
        .actionSheet(isPresented: $showingActionSheet) {
            ActionSheet(
                title: Text("Select \(imageType.title)"),
                message: Text("Choose how you want to add your \(imageType.title.lowercased())"),
                buttons: [
                    .default(Text("Photo Library")) {
                        showingPhotoLibrary = true
                    },
                    .default(Text("Camera")) {
                        if UIImagePickerController.isSourceTypeAvailable(.camera) {
                            showingCamera = true
                        }
                    },
                    .cancel()
                ]
            )
        }
        .sheet(isPresented: $showingPhotoLibrary) {
            PhotoPicker(selectedImage: Binding(
                get: { imageToCrop },
                set: { newImage in
                    if let image = newImage {
                        imageToCrop = image
                        showingCropper = true
                    }
                }
            ))
        }
        .sheet(isPresented: $showingCamera) {
            ImagePicker(selectedImage: Binding(
                get: { imageToCrop },
                set: { newImage in
                    if let image = newImage {
                        imageToCrop = image
                        showingCropper = true
                    }
                }
            ), sourceType: .camera)
        }
        .sheet(isPresented: $showingCropper) {
            if let image = imageToCrop {
                ImageCropperView(originalImage: image) { croppedImage in
                    selectedImage = croppedImage
                    imageToCrop = nil
                }
            }
        }
        .onAppear {
            // Download image from server if we have a path but no local image
            if selectedImage == nil, let filename = serverPath {
                downloadImage(filename: filename)
            }
        }
    }
    
    // MARK: - Download Handler
    private func downloadImage(filename: String) {
        guard !isUploading else { return }
        
        isUploading = true
        uploadError = nil
        
        Task {
            do {
                let image = try await MediaService.downloadImage(filename: filename)
                
                await MainActor.run {
                    self.selectedImage = image
                    self.isUploading = false
                    print("✅ \(imageType.title) downloaded: \(filename)")
                }
            } catch {
                await MainActor.run {
                    self.uploadError = "Download failed"
                    self.isUploading = false
                    print("❌ Download failed: \(error.localizedDescription)")
                }
            }
        }
    }
}

// MARK: - Image Preview Component
struct ImagePreviewComponent: View {
    let image: UIImage?
    let placeholder: String
    let size: CGFloat
    
    init(image: UIImage?, placeholder: String = "photo", size: CGFloat = 100) {
        self.image = image
        self.placeholder = placeholder
        self.size = size
    }
    
    var body: some View {
        Group {
            if let image = image {
                Image(uiImage: image)
                    .resizable()
                    .aspectRatio(contentMode: .fill)
                    .frame(width: size, height: size)
                    .clipShape(RoundedRectangle(cornerRadius: 8))
            } else {
                RoundedRectangle(cornerRadius: 8)
                    .fill(Color.gray.opacity(0.2))
                    .frame(width: size, height: size)
                    .overlay {
                        Image(systemName: placeholder)
                            .font(.system(size: size * 0.4))
                            .foregroundColor(.gray)
                    }
            }
        }
    }
}

// MARK: - Image Compression Utility
class ImageCompressionUtility {
    static func compressImage(_ image: UIImage, maxSizeKB: Int = 500) -> Data? {
        var compression: CGFloat = 1.0
        let maxCompression: CGFloat = 0.1
        let maxSizeBytes = maxSizeKB * 1024
        
        guard var imageData = image.jpegData(compressionQuality: compression) else { return nil }
        
        while imageData.count > maxSizeBytes && compression > maxCompression {
            compression -= 0.1
            guard let newImageData = image.jpegData(compressionQuality: compression) else { break }
            imageData = newImageData
        }
        
        return imageData
    }
    
    static func resizeImage(_ image: UIImage, targetSize: CGSize) -> UIImage? {
        let renderer = UIGraphicsImageRenderer(size: targetSize)
        return renderer.image { _ in
            image.draw(in: CGRect(origin: .zero, size: targetSize))
        }
    }
    
    static func processImageForBusinessCard(_ image: UIImage, type: ImageSelectionView.ImageType) -> Data? {
        let targetSize: CGSize
        
        switch type {
        case .profilePhoto:
            targetSize = CGSize(width: 300, height: 300) // Square for profile
        case .companyLogo:
            targetSize = CGSize(width: 300, height: 300) // Square for logo
        case .coverGraphic:
            targetSize = CGSize(width: 600, height: 300) // Wide for cover
        }
        
        guard let resizedImage = resizeImage(image, targetSize: targetSize) else { return nil }
        return compressImage(resizedImage, maxSizeKB: 500)
    }
}

// MARK: - Enhanced Business Card Creation View with Image Picker
struct BusinessCardCreationViewWithImages: View {
    @Environment(\.dismiss) private var dismiss
    @StateObject private var dataManager = DataManager.shared
    
    // MARK: - Form State
    @State private var firstName = ""
    @State private var lastName = ""
    @State private var phoneNumber = ""
    @State private var companyName = ""
    @State private var jobTitle = ""
    @State private var bio = ""
    
    // MARK: - Image State
    @State private var profilePhoto: UIImage?
    @State private var companyLogo: UIImage?
    @State private var coverGraphic: UIImage?
    @State private var profilePhotoPath: String?
    @State private var companyLogoPath: String?
    @State private var coverGraphicPath: String?
    
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
                
                // MARK: - Additional Contacts Section
                Section("Additional Contacts") {
                    // Additional Emails
                    VStack(alignment: .leading, spacing: 8) {
                        HStack {
                            Text("Email Addresses")
                            Spacer()
                            Button("Add Email") {
                                showingEmailSheet = true
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
                                Button("Remove") {
                                    additionalEmails.removeAll { $0.id == email.id }
                                }
                                .buttonStyle(.bordered)
                                .foregroundColor(.red)
                            }
                        }
                    }
                    
                    // Additional Phones
                    VStack(alignment: .leading, spacing: 8) {
                        HStack {
                            Text("Phone Numbers")
                            Spacer()
                            Button("Add Phone") {
                                showingPhoneSheet = true
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
                                Button("Remove") {
                                    additionalPhones.removeAll { $0.id == phone.id }
                                }
                                .buttonStyle(.bordered)
                                .foregroundColor(.red)
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
                            showingWebsiteSheet = true
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
                                Button("Remove") {
                                    websiteLinks.removeAll { $0.id == website.id }
                                }
                                .buttonStyle(.bordered)
                                .foregroundColor(.red)
                            }
                            Text(website.url)
                                .font(.caption2)
                                .foregroundColor(.blue)
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
            AddEmailView { email in
                additionalEmails.append(email)
            }
        }
        .sheet(isPresented: $showingPhoneSheet) {
            AddPhoneView { phone in
                additionalPhones.append(phone)
            }
        }
        .sheet(isPresented: $showingWebsiteSheet) {
            AddWebsiteView { website in
                websiteLinks.append(website)
            }
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
        _ = dataManager.createBusinessCard(from: businessCard)
        
        // Dismiss the view
        dismiss()
    }
}

#Preview {
    ImageSelectionView(
        selectedImage: .constant(nil),
        serverPath: .constant(nil),
        imageType: .profilePhoto
    )
}
