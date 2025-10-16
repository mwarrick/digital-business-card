//
//  BusinessCardDisplayView.swift
//  ShareMyCard
//
//  Created by Mark Warrick on 10/10/25.
//

import SwiftUI

struct BusinessCardDisplayView: View {
    let businessCard: BusinessCard
    @Environment(\.dismiss) private var dismiss
    @State private var showingShareSheet = false
    @State private var showingContactActions = false
    @State private var selectedContactMethod: ContactMethod?
    
    enum ContactMethod {
        case phone, email, website, address
        
        var title: String {
            switch self {
            case .phone: return "Call"
            case .email: return "Email"
            case .website: return "Visit Website"
            case .address: return "Get Directions"
            }
        }
        
        var icon: String {
            switch self {
            case .phone: return "phone.fill"
            case .email: return "envelope.fill"
            case .website: return "globe"
            case .address: return "location.fill"
            }
        }
    }
    
    var body: some View {
        NavigationView {
            ScrollView {
                VStack(spacing: 0) {
                    // Business Card Visual
                    BusinessCardVisualView(businessCard: businessCard)
                        .padding()
                        .background(Color(UIColor.secondarySystemBackground))
                    
                    // Contact Actions
                    VStack(spacing: 16) {
                        Text("Contact Information")
                            .font(.title2)
                            .fontWeight(.semibold)
                            .padding(.top)
                        
                        // Primary Contact
                        ContactActionButton(
                            title: "Call \(businessCard.firstName)",
                            subtitle: businessCard.primaryPhone,
                            icon: "phone.fill",
                            color: Color.green
                        ) {
                            selectedContactMethod = .phone
                            showingContactActions = true
                        }
                        
                        // Primary Email
                        if let primaryEmail = businessCard.primaryEmail {
                            ContactActionButton(
                                title: "Email \(businessCard.firstName)",
                                subtitle: primaryEmail.email,
                                icon: "envelope.fill",
                                color: .blue
                            ) {
                                selectedContactMethod = .email
                                showingContactActions = true
                            }
                        }
                        
                        // Primary Website
                        if let primaryWebsite = businessCard.primaryWebsite {
                            ContactActionButton(
                                title: "Visit \(primaryWebsite.name)",
                                subtitle: primaryWebsite.url,
                                icon: "globe",
                                color: .purple
                            ) {
                                selectedContactMethod = .website
                                showingContactActions = true
                            }
                        }
                        
                        // Address
                        if let address = businessCard.address, !address.fullAddress.isEmpty {
                            ContactActionButton(
                                title: "Get Directions",
                                subtitle: address.fullAddress,
                                icon: "location.fill",
                                color: .orange
                            ) {
                                selectedContactMethod = .address
                                showingContactActions = true
                            }
                        }
                    }
                    .padding()
                    
                    // Additional Information
                    if hasAdditionalInfo {
                        VStack(alignment: .leading, spacing: 16) {
                            Text("Additional Information")
                                .font(.title2)
                                .fontWeight(.semibold)
                            
                            // Additional Emails (only show if there are multiple emails)
                            if businessCard.additionalEmails.count > 1 {
                                let nonPrimaryEmails = businessCard.additionalEmails.filter { !$0.isPrimary }
                                if !nonPrimaryEmails.isEmpty {
                                    AdditionalInfoSection(
                                        title: "Other Email Addresses",
                                        items: nonPrimaryEmails.map { email in
                                            AdditionalInfoItem(
                                                title: email.email,
                                                subtitle: email.type.displayName
                                            )
                                        }
                                    )
                                }
                            }
                            
                            // Additional Phones
                            if !businessCard.additionalPhones.isEmpty {
                                AdditionalInfoSection(
                                    title: "Other Phone Numbers",
                                    items: businessCard.additionalPhones.map { phone in
                                        AdditionalInfoItem(
                                            title: phone.phoneNumber,
                                            subtitle: phone.type.displayName
                                        )
                                    }
                                )
                            }
                            
                            // Additional Websites (only show if there are multiple websites)
                            if businessCard.websiteLinks.count > 1 {
                                let nonPrimaryWebsites = businessCard.websiteLinks.filter { !$0.isPrimary }
                                if !nonPrimaryWebsites.isEmpty {
                                    AdditionalInfoSection(
                                        title: "Other Websites",
                                        items: nonPrimaryWebsites.map { website in
                                            AdditionalInfoItem(
                                                title: website.name,
                                                subtitle: website.url
                                            )
                                        }
                                    )
                                }
                            }
                            
                            // Bio
                            if let bio = businessCard.bio, !bio.isEmpty {
                                VStack(alignment: .leading, spacing: 8) {
                                    Text("About \(businessCard.firstName)")
                                        .font(.headline)
                                    
                                    Text(bio)
                                        .font(.body)
                                        .foregroundColor(.secondary)
                                }
                                .padding()
                                .background(Color(UIColor.secondarySystemBackground))
                                .cornerRadius(12)
                            }
                        }
                        .padding()
                    }
                    
                    Spacer(minLength: 100)
                }
            }
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarLeading) {
                    Button("Done") {
                        dismiss()
                    }
                }
                
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button {
                        showingShareSheet = true
                    } label: {
                        Image(systemName: "square.and.arrow.up")
                    }
                }
            }
        }
        .actionSheet(isPresented: $showingContactActions) {
            if let method = selectedContactMethod {
                return ActionSheet(
                    title: Text(method.title),
                    message: Text(getContactMethodMessage(for: method)),
                    buttons: [
                        .default(Text(method.title)) {
                            performContactAction(method)
                        },
                        .cancel()
                    ]
                )
            }
            return ActionSheet(title: Text("Contact"), buttons: [.cancel()])
        }
        .sheet(isPresented: $showingShareSheet) {
            ShareSheet(items: [createShareableContent()])
        }
    }
    
    private var hasAdditionalInfo: Bool {
        // Only show additional info if there are multiple items of any type
        let hasMultipleEmails = businessCard.additionalEmails.count > 1
        let hasMultiplePhones = businessCard.additionalPhones.count > 0
        let hasMultipleWebsites = businessCard.websiteLinks.count > 1
        let hasBio = businessCard.bio != nil && !businessCard.bio!.isEmpty
        
        return hasMultipleEmails || hasMultiplePhones || hasMultipleWebsites || hasBio
    }
    
    private func getContactMethodMessage(for method: ContactMethod) -> String {
        switch method {
        case .phone:
            return "Call \(businessCard.primaryPhone)?"
        case .email:
            return "Send email to \(businessCard.primaryEmail?.email ?? "")?"
        case .website:
            return "Visit \(businessCard.primaryWebsite?.url ?? "")?"
        case .address:
            return "Get directions to \(businessCard.address?.fullAddress ?? "")?"
        }
    }
    
    private func performContactAction(_ method: ContactMethod) {
        switch method {
        case .phone:
            if let url = URL(string: "tel:\(businessCard.primaryPhone)") {
                UIApplication.shared.open(url)
            }
        case .email:
            if let email = businessCard.primaryEmail,
               let url = URL(string: "mailto:\(email)") {
                UIApplication.shared.open(url)
            }
        case .website:
            if let website = businessCard.primaryWebsite,
               let url = URL(string: website.url) {
                UIApplication.shared.open(url)
            }
        case .address:
            if let address = businessCard.address,
               let encodedAddress = address.fullAddress.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed),
               let url = URL(string: "http://maps.apple.com/?q=\(encodedAddress)") {
                UIApplication.shared.open(url)
            }
        }
    }
    
    private func createShareableContent() -> String {
        var content = "\(businessCard.fullName)"
        
        if let jobTitle = businessCard.jobTitle {
            content += "\n\(jobTitle)"
        }
        
        if let companyName = businessCard.companyName {
            content += "\n\(companyName)"
        }
        
        content += "\n\nPhone: \(businessCard.primaryPhone)"
        
        if let email = businessCard.primaryEmail {
            content += "\nEmail: \(email)"
        }
        
        if let website = businessCard.primaryWebsite {
            content += "\nWebsite: \(website.url)"
        }
        
        if let address = businessCard.address {
            content += "\nAddress: \(address.fullAddress)"
        }
        
        return content
    }
}

// MARK: - Business Card Visual View
struct BusinessCardVisualView: View {
    let businessCard: BusinessCard
    @State private var showingFullImage = false
    
    var body: some View {
        VStack(spacing: 0) {
            // Cover Graphic (if available)
            if let coverGraphicData = businessCard.coverGraphic,
               let coverImage = UIImage(data: coverGraphicData) {
                Image(uiImage: coverImage)
                    .resizable()
                    .aspectRatio(contentMode: .fill)
                    .frame(height: 80)
                    .clipped()
            } else {
                // Default cover
                Rectangle()
                    .fill(LinearGradient(
                        gradient: Gradient(colors: [Color.blue.opacity(0.8), Color.purple.opacity(0.6)]),
                        startPoint: .topLeading,
                        endPoint: .bottomTrailing
                    ))
                    .frame(height: 80)
            }
            
            // Main Content
            VStack(spacing: 16) {
                // Profile Photo and Basic Info
                HStack(spacing: 16) {
                    // Profile Photo
                    Button {
                        if businessCard.profilePhoto != nil {
                            showingFullImage = true
                        }
                    } label: {
                        Group {
                            if let profilePhotoData = businessCard.profilePhoto,
                               let profileImage = UIImage(data: profilePhotoData) {
                                Image(uiImage: profileImage)
                                    .resizable()
                                    .aspectRatio(contentMode: .fill)
                                    .frame(width: 80, height: 80)
                                    .clipShape(Circle())
                                    .overlay(
                                        Circle()
                                            .stroke(Color.white, lineWidth: 3)
                                    )
                            } else {
                                Circle()
                                    .fill(Color.white.opacity(0.9))
                                    .frame(width: 80, height: 80)
                                    .overlay(
                                        Image(systemName: "person.fill")
                                            .font(.system(size: 40))
                                            .foregroundColor(.blue)
                                    )
                                    .overlay(
                                        Circle()
                                            .stroke(Color.white, lineWidth: 3)
                                    )
                            }
                        }
                    }
                    .buttonStyle(PlainButtonStyle())
                    
                    // Name and Title
                    VStack(alignment: .leading, spacing: 4) {
                        Text(businessCard.fullName)
                            .font(.title2)
                            .fontWeight(.bold)
                            .foregroundColor(.primary)
                        
                        if let jobTitle = businessCard.jobTitle {
                            Text(jobTitle)
                                .font(.subheadline)
                                .foregroundColor(.secondary)
                        }
                        
                        if let companyName = businessCard.companyName {
                            Text(companyName)
                                .font(.subheadline)
                                .foregroundColor(.secondary)
                        }
                    }
                    
                    Spacer()
                    
                    // Company Logo (if available)
                    if let companyLogoData = businessCard.companyLogo,
                       let companyImage = UIImage(data: companyLogoData) {
                        Image(uiImage: companyImage)
                            .resizable()
                            .aspectRatio(contentMode: .fit)
                            .frame(width: 60, height: 60)
                            .background(Color.white)
                            .cornerRadius(8)
                    }
                }
                
                // Note: Primary contact details are shown in the section below.
                // We intentionally omit phone/email/website rows here to avoid
                // duplication in the header area.
            }
            .padding()
            .background(Color(UIColor.systemBackground))
        }
        .cornerRadius(16)
        .shadow(radius: 8)
        .sheet(isPresented: $showingFullImage) {
            if let profilePhotoData = businessCard.profilePhoto,
               let profileImage = UIImage(data: profilePhotoData) {
                ImageFullScreenView(image: profileImage, name: businessCard.fullName)
            }
        }
    }
}

// MARK: - Contact Action Button
struct ContactActionButton: View {
    let title: String
    let subtitle: String
    let icon: String
    let color: Color
    let action: () -> Void
    
    var body: some View {
        Button(action: action) {
            HStack(spacing: 16) {
                Image(systemName: icon)
                    .font(.title2)
                    .foregroundColor(color)
                    .frame(width: 30)
                
                VStack(alignment: .leading, spacing: 2) {
                    Text(title)
                        .font(.headline)
                        .foregroundColor(.primary)
                    
                    Text(subtitle)
                        .font(.subheadline)
                        .foregroundColor(.secondary)
                        .lineLimit(2)
                }
                
                Spacer()
                
                Image(systemName: "chevron.right")
                    .font(.caption)
                    .foregroundColor(.secondary)
            }
            .padding()
            .background(Color(UIColor.secondarySystemBackground))
            .cornerRadius(12)
        }
        .buttonStyle(PlainButtonStyle())
    }
}

// MARK: - Additional Info Section
struct AdditionalInfoSection: View {
    let title: String
    let items: [AdditionalInfoItem]
    
    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text(title)
                .font(.headline)
            
            ForEach(Array(items.enumerated()), id: \.offset) { index, item in
                HStack {
                    VStack(alignment: .leading, spacing: 2) {
                        Text(item.title)
                            .font(.subheadline)
                        
                        Text(item.subtitle)
                            .font(.caption)
                            .foregroundColor(.secondary)
                    }
                    
                    Spacer()
                }
                .padding(.vertical, 4)
                
                if index < items.count - 1 {
                    Divider()
                }
            }
        }
        .padding()
        .background(Color.gray.opacity(0.1))
        .cornerRadius(12)
    }
}

// MARK: - Additional Info Item
struct AdditionalInfoItem {
    let title: String
    let subtitle: String
}


// MARK: - Image Full Screen View
struct ImageFullScreenView: View {
    let image: UIImage
    let name: String
    @Environment(\.dismiss) private var dismiss
    
    var body: some View {
        NavigationView {
            VStack {
                Image(uiImage: image)
                    .resizable()
                    .aspectRatio(contentMode: .fit)
                    .clipped()
            }
            .navigationTitle(name)
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button("Done") {
                        dismiss()
                    }
                }
            }
        }
    }
}

#Preview {
    BusinessCardDisplayView(businessCard: BusinessCard.sampleData.first!)
}
