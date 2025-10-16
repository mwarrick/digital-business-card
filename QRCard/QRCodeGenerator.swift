//
//  QRCodeGenerator.swift
//  ShareMyCard
//
//  Created by Mark Warrick on 10/10/25.
//

import Foundation
import CoreImage.CIFilterBuiltins
import SwiftUI
import Combine

// MARK: - QR Code Generator
class QRCodeGenerator: ObservableObject {
    static let shared = QRCodeGenerator()
    
    private let context = CIContext()
    private let filter = CIFilter.qrCodeGenerator()
    
    private init() {}
    
    // MARK: - Generate QR Code from Business Card
    func generateQRCode(from businessCard: BusinessCard) -> UIImage? {
        if let serverId = businessCard.serverCardId, !serverId.isEmpty {
            // Trackable QR pointing to server vCard endpoint
            let url = "https://sharemycard.app/vcard.php?id=\(serverId)&src=qr-app"
            return generateQRCode(from: url)
        } else {
            // Fallback: embed vCard directly (not tracked)
            let qrData = createSimpleQRData(from: businessCard)
            return generateQRCode(from: qrData)
        }
    }
    
    // MARK: - Generate QR Code from String
    func generateQRCode(from string: String) -> UIImage? {
        guard let data = string.data(using: .utf8) else { return nil }
        return generateQRCode(from: data)
    }
    
    // MARK: - Generate QR Code from Data
    private func generateQRCode(from data: Data) -> UIImage? {
        filter.setValue(data, forKey: "inputMessage")
        filter.setValue("M", forKey: "inputCorrectionLevel") // Medium error correction
        
        guard let outputImage = filter.outputImage else { return nil }
        
        // Scale up the image for better quality
        let scale = 10.0
        let transform = CGAffineTransform(scaleX: scale, y: scale)
        let scaledImage = outputImage.transformed(by: transform)
        
        guard let cgImage = context.createCGImage(scaledImage, from: scaledImage.extent) else { return nil }
        
        return UIImage(cgImage: cgImage)
    }
    
    // MARK: - Create QR Data from Business Card
    private func createQRData(from businessCard: BusinessCard) -> String {
        var qrData: [String: Any] = [:]
        
        // Basic Information
        qrData["firstName"] = businessCard.firstName
        qrData["lastName"] = businessCard.lastName
        qrData["phoneNumber"] = businessCard.phoneNumber
        
        // Optional Information
        if let companyName = businessCard.companyName {
            qrData["companyName"] = companyName
        }
        if let jobTitle = businessCard.jobTitle {
            qrData["jobTitle"] = jobTitle
        }
        if let bio = businessCard.bio {
            qrData["bio"] = bio
        }
        
        // Additional Emails
        if !businessCard.additionalEmails.isEmpty {
            qrData["emails"] = businessCard.additionalEmails.map { email in
                [
                    "email": email.email,
                    "type": email.type.rawValue,
                    "label": email.label ?? ""
                ]
            }
        }
        
        // Additional Phones
        if !businessCard.additionalPhones.isEmpty {
            qrData["phones"] = businessCard.additionalPhones.map { phone in
                [
                    "phoneNumber": phone.phoneNumber,
                    "type": phone.type.rawValue,
                    "label": phone.label ?? ""
                ]
            }
        }
        
        // Website Links
        if !businessCard.websiteLinks.isEmpty {
            qrData["websites"] = businessCard.websiteLinks.map { website in
                [
                    "name": website.name,
                    "url": website.url,
                    "description": website.description ?? ""
                ]
            }
        }
        
        // Address
        if let address = businessCard.address {
            var addressData: [String: String] = [:]
            if let street = address.street { addressData["street"] = street }
            if let city = address.city { addressData["city"] = city }
            if let state = address.state { addressData["state"] = state }
            if let zipCode = address.zipCode { addressData["zipCode"] = zipCode }
            if let country = address.country { addressData["country"] = country }
            qrData["address"] = addressData
        }
        
        // Metadata
        qrData["createdAt"] = ISO8601DateFormatter().string(from: businessCard.createdAt)
        qrData["appVersion"] = "1.0.0"
        qrData["appName"] = "ShareMyCard"
        
        // Convert to JSON string
        do {
            let jsonData = try JSONSerialization.data(withJSONObject: qrData, options: .prettyPrinted)
            return String(data: jsonData, encoding: .utf8) ?? ""
        } catch {
            print("Error creating QR data: \(error)")
            return createSimpleQRData(from: businessCard)
        }
    }
    
    // MARK: - Create Simple QR Data (Fallback)
    private func createSimpleQRData(from businessCard: BusinessCard) -> String {
        var simpleData = "BEGIN:VCARD\n"
        simpleData += "VERSION:3.0\n"
        simpleData += "FN:\(businessCard.fullName)\n"
        simpleData += "N:\(businessCard.lastName);\(businessCard.firstName);;;\n"
        simpleData += "TEL:\(businessCard.phoneNumber)\n"
        
        if let companyName = businessCard.companyName {
            simpleData += "ORG:\(companyName)\n"
        }
        if let jobTitle = businessCard.jobTitle {
            simpleData += "TITLE:\(jobTitle)\n"
        }
        
        for email in businessCard.additionalEmails {
            let emailType = email.type.rawValue.uppercased()
            simpleData += "EMAIL;TYPE=\(emailType):\(email.email)\n"
        }
        
        for phone in businessCard.additionalPhones {
            let phoneType = phone.type.rawValue.uppercased()
            simpleData += "TEL;TYPE=\(phoneType):\(phone.phoneNumber)\n"
        }
        
        for website in businessCard.websiteLinks {
            simpleData += "URL:\(website.url)\n"
        }
        
        if let address = businessCard.address {
            simpleData += "ADR:;;\(address.street ?? "");\(address.city ?? "");\(address.state ?? "");\(address.zipCode ?? "");\(address.country ?? "")\n"
        }
        
        if let bio = businessCard.bio {
            simpleData += "NOTE:\(bio)\n"
        }
        
        // Profile Photo URL (if available)
        if let profilePhotoPath = businessCard.profilePhotoPath, !profilePhotoPath.isEmpty {
            let photoURL = "https://sharemycard.app/api/media/view?filename=\(profilePhotoPath)"
            simpleData += "PHOTO;VALUE=uri:\(photoURL)\n"
        }
        
        simpleData += "END:VCARD"
        return simpleData
    }
}

// MARK: - QR Code Display View
struct QRCodeDisplayView: View {
    let businessCard: BusinessCard
    @State private var qrCodeImage: UIImage?
    @State private var showingShareSheet = false
    @State private var showingBusinessCardView = false
    
    var body: some View {
        NavigationView {
            VStack(spacing: 20) {
                // Business Card Preview (header-only, no duplicate contact rows)
                BusinessCardPreviewView(businessCard: businessCard)
                    .padding()
                    .background(Color(UIColor.systemBackground))
                    .cornerRadius(12)
                    .shadow(radius: 4)
                
                // QR Code
                if let qrCodeImage = qrCodeImage {
                    VStack(spacing: 12) {
                        Text("Scan to Add Contact")
                            .font(.headline)
                            .foregroundColor(.secondary)
                        
                        Image(uiImage: qrCodeImage)
                            .interpolation(.none)
                            .resizable()
                            .scaledToFit()
                            .frame(width: 200, height: 200)
                            .background(Color(UIColor.systemBackground))
                            .cornerRadius(12)
                            .shadow(radius: 4)
                        
                        Text("QR Code contains your contact information")
                            .font(.caption)
                            .foregroundColor(.secondary)
                            .multilineTextAlignment(.center)
                    }
                } else {
                    VStack(spacing: 12) {
                        ProgressView()
                        Text("Generating QR Code...")
                            .font(.caption)
                            .foregroundColor(.secondary)
                    }
                    .frame(width: 200, height: 200)
                }
                
                Spacer()
                
                // Action Buttons
                VStack(spacing: 12) {
                    Button("Share QR Code") {
                        showingShareSheet = true
                    }
                    .buttonStyle(.borderedProminent)
                    .disabled(qrCodeImage == nil)
                    
                    Button("View Business Card") {
                        showingBusinessCardView = true
                    }
                    .buttonStyle(.bordered)
                }
            }
            .padding()
            .navigationBarTitleDisplayMode(.inline)
            .onAppear {
                generateQRCode()
            }
        }
        .sheet(isPresented: $showingShareSheet) {
            if let qrCodeImage = qrCodeImage {
                ShareSheet(items: [qrCodeImage])
            }
        }
        .sheet(isPresented: $showingBusinessCardView) {
            BusinessCardDisplayView(businessCard: businessCard)
        }
    }
    
    private func generateQRCode() {
        qrCodeImage = QRCodeGenerator.shared.generateQRCode(from: businessCard)
    }
}

// MARK: - Business Card Preview View
struct BusinessCardPreviewView: View {
    let businessCard: BusinessCard
    
    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            // Header
            HStack {
                VStack(alignment: .leading, spacing: 4) {
                    Text(businessCard.fullName)
                        .font(.title2)
                        .fontWeight(.bold)
                    
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
                
                // Profile Photo or Default
                Group {
                    if let profilePhotoData = businessCard.profilePhoto,
                       let profileImage = UIImage(data: profilePhotoData) {
                        Image(uiImage: profileImage)
                            .resizable()
                            .aspectRatio(contentMode: .fill)
                            .frame(width: 60, height: 60)
                            .clipShape(Circle())
                    } else {
                        Circle()
                            .fill(Color.blue.opacity(0.2))
                            .frame(width: 60, height: 60)
                            .overlay {
                                Image(systemName: "person.fill")
                                    .foregroundColor(.blue)
                                    .font(.title2)
                            }
                    }
                }
            }
            
            Divider()
            
            // Intentionally omit primary contact rows in the preview to
            // avoid duplication with the Contact Information section below.
        }
        .padding()
    }
}

// MARK: - Business Card Detail View
struct BusinessCardDetailView: View {
    let businessCard: BusinessCard
    @Environment(\.dismiss) private var dismiss
    
    var body: some View {
        NavigationView {
            ScrollView {
                VStack(alignment: .leading, spacing: 16) {
                    // Header
                    HStack {
                        VStack(alignment: .leading) {
                            Text(businessCard.fullName)
                                .font(.largeTitle)
                                .fontWeight(.bold)
                            
                            if let jobTitle = businessCard.jobTitle {
                                Text(jobTitle)
                                    .font(.title2)
                                    .foregroundColor(.secondary)
                            }
                            
                            if let companyName = businessCard.companyName {
                                Text(companyName)
                                    .font(.title3)
                                    .foregroundColor(.secondary)
                            }
                        }
                        
                        Spacer()
                    }
                    .padding()
                    
                    // Contact Information
                    VStack(alignment: .leading, spacing: 12) {
                        Text("Contact Information")
                            .font(.headline)
                        
                        ContactInfoRow(icon: "phone.fill", text: businessCard.primaryPhone)
                        
                        ForEach(businessCard.additionalEmails, id: \.id) { email in
                            ContactInfoRow(icon: "envelope.fill", text: email.email)
                        }
                        
                        ForEach(businessCard.additionalPhones, id: \.id) { phone in
                            ContactInfoRow(icon: "phone.fill", text: phone.phoneNumber)
                        }
                    }
                    .padding()
                    .background(Color.gray.opacity(0.1))
                    .cornerRadius(12)
                    
                    // Websites
                    if !businessCard.websiteLinks.isEmpty {
                        VStack(alignment: .leading, spacing: 12) {
                            Text("Websites")
                                .font(.headline)
                            
                            ForEach(businessCard.websiteLinks) { website in
                                VStack(alignment: .leading) {
                                    Text(website.name)
                                        .fontWeight(.semibold)
                                    Text(website.url)
                                        .font(.caption)
                                        .foregroundColor(.blue)
                                }
                            }
                        }
                        .padding()
                        .background(Color.gray.opacity(0.1))
                        .cornerRadius(12)
                    }
                    
                    // Address
                    if let address = businessCard.address {
                        VStack(alignment: .leading, spacing: 12) {
                            Text("Address")
                                .font(.headline)
                            
                            Text(address.fullAddress)
                        }
                        .padding()
                        .background(Color.gray.opacity(0.1))
                        .cornerRadius(12)
                    }
                    
                    // Bio
                    if let bio = businessCard.bio {
                        VStack(alignment: .leading, spacing: 12) {
                            Text("About")
                                .font(.headline)
                            
                            Text(bio)
                        }
                        .padding()
                        .background(Color.gray.opacity(0.1))
                        .cornerRadius(12)
                    }
                }
                .padding()
            }
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


// MARK: - Share Sheet
struct ShareSheet: UIViewControllerRepresentable {
    let items: [Any]
    
    func makeUIViewController(context: Context) -> UIActivityViewController {
        UIActivityViewController(activityItems: items, applicationActivities: nil)
    }
    
    func updateUIViewController(_ uiViewController: UIActivityViewController, context: Context) {}
}

#Preview {
    QRCodeDisplayView(businessCard: BusinessCard.sampleData.first!)
}
