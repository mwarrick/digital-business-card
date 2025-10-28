//
//  MinimalQRScannerView.swift
//  ShareMyCard
//
//  Minimal QR Code scanner for adding contacts
//

import SwiftUI
import PhotosUI

struct MinimalQRScannerView: View {
    @ObservedObject var viewModel: ContactsViewModel
    @Environment(\.dismiss) private var dismiss
    
    @State private var qrCodeText = ""
    @State private var showingContactForm = false
    @State private var scannedContactData: ContactCreateData?
    @State private var showingError = false
    @State private var errorMessage = ""
    @State private var showingImagePicker = false
    @State private var selectedImage: UIImage?
    @State private var isProcessingImage = false
    
    var body: some View {
        NavigationView {
            VStack(spacing: 20) {
                Text("QR Code Scanner")
                    .font(.largeTitle)
                    .fontWeight(.bold)
                
                Text("Enter QR code data manually, upload an image, or use camera scanning")
                    .font(.subheadline)
                    .foregroundColor(.secondary)
                    .multilineTextAlignment(.center)
                
                VStack(alignment: .leading, spacing: 8) {
                    Text("QR Code Data")
                        .font(.headline)
                    
                    ZStack(alignment: .topLeading) {
                        TextEditor(text: $qrCodeText)
                            .frame(minHeight: 200)
                            .overlay(
                                RoundedRectangle(cornerRadius: 8)
                                    .stroke(Color.gray.opacity(0.3), lineWidth: 1)
                            )
                        
                        if qrCodeText.isEmpty {
                            Text("Paste vCard data or URL here...")
                                .foregroundColor(.gray)
                                .padding(.top, 8)
                                .padding(.leading, 4)
                                .allowsHitTesting(false)
                        }
                    }
                }
                
                HStack(spacing: 12) {
                    Button("Upload QR Image") {
                        showingImagePicker = true
                    }
                    .buttonStyle(.bordered)
                    .disabled(isProcessingImage)
                    
                    Button("Process QR Code") {
                        processQRCode()
                    }
                    .buttonStyle(.borderedProminent)
                    .disabled(qrCodeText.isEmpty || isProcessingImage)
                }
                
                if isProcessingImage {
                    HStack {
                        ProgressView()
                            .scaleEffect(0.8)
                        Text("Processing QR image...")
                            .font(.caption)
                            .foregroundColor(.secondary)
                    }
                }
                
                Spacer()
            }
            .padding()
            .navigationTitle("Scan QR Code")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarLeading) {
                    Button("Cancel") {
                        dismiss()
                    }
                }
            }
            .sheet(isPresented: $showingContactForm) {
                if let contactData = scannedContactData {
                    QRContactFormView(contactData: contactData, viewModel: viewModel)
                }
            }
            .sheet(isPresented: $showingImagePicker) {
                PhotoPicker(selectedImage: $selectedImage)
            }
            .onChange(of: selectedImage) { image in
                if let image = image {
                    processQRImage(image)
                }
            }
            .alert("Error", isPresented: $showingError) {
                Button("OK") { }
            } message: {
                Text(errorMessage)
            }
        }
    }
    
    private func processQRCode() {
        guard !qrCodeText.isEmpty else { return }
        
        if let contactData = parseQRCode(qrCodeText) {
            scannedContactData = contactData
            showingContactForm = true
        } else {
            errorMessage = "Could not parse contact information from QR code data"
            showingError = true
        }
    }
    
    private func parseQRCode(_ code: String) -> ContactCreateData? {
        // Check if it's a vCard
        if code.hasPrefix("BEGIN:VCARD") {
            return parseVCard(code)
        }
        
        // Check if it's a URL that might contain vCard data
        if code.hasPrefix("http://") || code.hasPrefix("https://") {
            return handleURLBasedQRCode(code)
        }
        
        // Try to parse as plain text contact info
        return parsePlainTextContact(code)
    }
    
    private func handleURLBasedQRCode(_ url: String) -> ContactCreateData? {
        // This is a synchronous function, but URL fetching is async
        // For now, return a placeholder that indicates URL processing is needed
        // The actual URL fetching should be handled in the async processQRImageOnServer function
        return ContactCreateData(
            firstName: "QR",
            lastName: "Contact",
            email: nil,
            phone: nil,
            mobilePhone: nil,
            company: nil,
            jobTitle: nil,
            address: nil,
            city: nil,
            state: nil,
            zipCode: nil,
            country: nil,
            website: url,
            notes: "URL-based QR code detected. Processing...",
            commentsFromLead: nil,
            birthdate: nil,
            photoUrl: nil,
            source: "qr_scan",
            sourceMetadata: "{\"qr_data\":\"\(url)\",\"type\":\"url\",\"needs_processing\":true}"
        )
    }
    
    private func parseVCard(_ vcardData: String) -> ContactCreateData? {
        var firstName = ""
        var lastName = ""
        var email: String?
        var phone: String?
        var mobilePhone: String?
        var company: String?
        var jobTitle: String?
        var streetAddress: String?
        var city: String?
        var state: String?
        var zipCode: String?
        var country: String?
        var website: String?
        var notes: String?
        
        let lines = vcardData.components(separatedBy: .newlines)
        
        for line in lines {
            let trimmedLine = line.trimmingCharacters(in: .whitespaces)
            
            if trimmedLine.hasPrefix("FN:") {
                let fullName = String(trimmedLine.dropFirst(3))
                let nameParts = fullName.components(separatedBy: " ")
                if nameParts.count >= 2 {
                    firstName = nameParts[0]
                    lastName = nameParts.dropFirst().joined(separator: " ")
                } else {
                    firstName = fullName
                }
            } else if trimmedLine.hasPrefix("N:") {
                let nameData = String(trimmedLine.dropFirst(2))
                let nameParts = nameData.components(separatedBy: ";")
                if nameParts.count >= 2 {
                    lastName = nameParts[0]
                    firstName = nameParts[1]
                }
            } else if trimmedLine.hasPrefix("EMAIL:") {
                email = String(trimmedLine.dropFirst(6))
            } else if trimmedLine.hasPrefix("TEL:") {
                let phoneData = String(trimmedLine.dropFirst(4))
                if trimmedLine.contains("TYPE=CELL") || trimmedLine.contains("TYPE=MOBILE") {
                    mobilePhone = phoneData
                } else {
                    phone = phoneData
                }
            } else if trimmedLine.hasPrefix("ORG:") {
                company = String(trimmedLine.dropFirst(4))
            } else if trimmedLine.hasPrefix("TITLE:") {
                jobTitle = String(trimmedLine.dropFirst(6))
            } else if trimmedLine.hasPrefix("ADR:") {
                let addressData = String(trimmedLine.dropFirst(4))
                let addressParts = addressData.components(separatedBy: ";")
                if addressParts.count >= 6 {
                    streetAddress = addressParts[2]
                    city = addressParts[3]
                    state = addressParts[4]
                    zipCode = addressParts[5]
                    country = addressParts[6]
                }
            } else if trimmedLine.hasPrefix("URL:") {
                website = String(trimmedLine.dropFirst(4))
            } else if trimmedLine.hasPrefix("NOTE:") {
                notes = String(trimmedLine.dropFirst(5))
            }
        }
        
        // Ensure we have at least a first name
        if firstName.isEmpty {
            firstName = "QR"
            lastName = "Contact"
        }
        
        return ContactCreateData(
            firstName: firstName,
            lastName: lastName,
            email: email,
            phone: phone,
            mobilePhone: mobilePhone,
            company: company,
            jobTitle: jobTitle,
            address: streetAddress,
            city: city,
            state: state,
            zipCode: zipCode,
            country: country,
            website: website,
            notes: notes,
            commentsFromLead: nil,
            birthdate: nil,
            photoUrl: nil,
            source: "qr_scan",
            sourceMetadata: "{\"qr_data\":\"\(vcardData.prefix(100))\"}"
        )
    }
    
    private func parsePlainTextContact(_ text: String) -> ContactCreateData? {
        let lines = text.components(separatedBy: .newlines)
        let firstName = "QR"
        let lastName = "Contact"
        var email: String?
        var phone: String?
        var company: String?
        var notes: String?
        
        for line in lines {
            let trimmedLine = line.trimmingCharacters(in: .whitespaces)
            
            if trimmedLine.contains("@") && trimmedLine.contains(".") {
                email = trimmedLine
            } else if trimmedLine.range(of: "\\d{3}[-.\\s]?\\d{3}[-.\\s]?\\d{4}", options: .regularExpression) != nil {
                phone = trimmedLine
            } else if !trimmedLine.isEmpty && !trimmedLine.contains("@") && trimmedLine.range(of: "\\d{3}[-.\\s]?\\d{3}[-.\\s]?\\d{4}", options: .regularExpression) == nil {
                if company == nil {
                    company = trimmedLine
                } else {
                    notes = (notes ?? "") + trimmedLine + "\n"
                }
            }
        }
        
        return ContactCreateData(
            firstName: firstName,
            lastName: lastName,
            email: email,
            phone: phone,
            mobilePhone: nil,
            company: company,
            jobTitle: nil,
            address: nil,
            city: nil,
            state: nil,
            zipCode: nil,
            country: nil,
            website: nil,
            notes: notes?.trimmingCharacters(in: .whitespacesAndNewlines),
            commentsFromLead: nil,
            birthdate: nil,
            photoUrl: nil,
            source: "qr_scan",
            sourceMetadata: "{\"qr_data\":\"\(text.prefix(100))\"}"
        )
    }
    
    private func processQRImage(_ image: UIImage) {
        isProcessingImage = true
        
        Task {
            do {
                // Convert image to base64 for server processing
                guard let imageData = image.jpegData(compressionQuality: 0.8) else {
                    await MainActor.run {
                        errorMessage = "Failed to convert image to data"
                        showingError = true
                        isProcessingImage = false
                    }
                    return
                }
                
                let base64String = imageData.base64EncodedString()
                
                // Send to server for QR processing
                let result = try await processQRImageOnServer(base64Image: base64String)
                
                await MainActor.run {
                    if let contactData = result {
                        scannedContactData = contactData
                        showingContactForm = true
                    } else {
                        errorMessage = "No QR code found in the image or could not parse contact information"
                        showingError = true
                    }
                    isProcessingImage = false
                }
                
            } catch {
                await MainActor.run {
                    errorMessage = "Failed to process QR image: \(error.localizedDescription)"
                    showingError = true
                    isProcessingImage = false
                }
            }
        }
    }
    
    private func processQRImageOnServer(base64Image: String) async throws -> ContactCreateData? {
        guard let url = URL(string: "https://sharemycard.app/api/process-qr-image.php") else {
            throw NSError(domain: "InvalidURL", code: 0, userInfo: [NSLocalizedDescriptionKey: "Invalid server URL"])
        }
        
        print("🔍 Sending QR image to server...")
        print("📏 Base64 data length: \(base64Image.count)")
        print("📄 Base64 preview: \(String(base64Image.prefix(100)))")
        
        // Decode base64 to image data
        guard let imageData = Data(base64Encoded: base64Image) else {
            throw NSError(domain: "InvalidData", code: 0, userInfo: [NSLocalizedDescriptionKey: "Failed to decode base64 image data"])
        }
        
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.timeoutInterval = 60 // Longer timeout for uploads
        
        // Create multipart form data (same as business card uploads)
        let boundary = "Boundary-\(UUID().uuidString)"
        request.setValue("multipart/form-data; boundary=\(boundary)", forHTTPHeaderField: "Content-Type")
        
        var body = Data()
        
        // Add file field
        body.append("--\(boundary)\r\n".data(using: .utf8)!)
        body.append("Content-Disposition: form-data; name=\"image\"; filename=\"qr_image.jpg\"\r\n".data(using: .utf8)!)
        body.append("Content-Type: image/jpeg\r\n\r\n".data(using: .utf8)!)
        body.append(imageData)
        body.append("\r\n".data(using: .utf8)!)
        
        // End boundary
        body.append("--\(boundary)--\r\n".data(using: .utf8)!)
        
        request.httpBody = body
        
        print("📦 Request body size: \(body.count) bytes (multipart/form-data)")
        
        let (data, response) = try await URLSession.shared.data(for: request)
        
        print("📡 Server response received")
        print("📊 Response data size: \(data.count) bytes")
        print("📄 Raw response: \(String(data: data, encoding: .utf8) ?? "Could not decode as UTF-8")")
        
        guard let httpResponse = response as? HTTPURLResponse else {
            print("❌ Invalid response type")
            throw NSError(domain: "InvalidResponse", code: 0, userInfo: [NSLocalizedDescriptionKey: "Invalid response"])
        }
        
        print("📊 HTTP Status Code: \(httpResponse.statusCode)")
        
        guard httpResponse.statusCode == 200 else {
            print("❌ Server error: \(httpResponse.statusCode)")
            let errorMessage = String(data: data, encoding: .utf8) ?? "No error message"
            print("📄 Error response: \(errorMessage)")
            throw NSError(domain: "ServerError", code: httpResponse.statusCode, userInfo: [NSLocalizedDescriptionKey: "Server returned status \(httpResponse.statusCode): \(errorMessage)"])
        }
        
        let jsonResponse = try JSONSerialization.jsonObject(with: data) as? [String: Any]
        print("📦 Parsed JSON response: \(jsonResponse ?? [:])")
        
        guard let success = jsonResponse?["success"] as? Bool, success else {
            let message = jsonResponse?["message"] as? String ?? "Unknown error"
            let debug = jsonResponse?["debug"] as? String ?? "No debug info"
            print("❌ Processing failed: \(message)")
            print("🔍 Debug info: \(debug)")
            throw NSError(domain: "ProcessingError", code: 0, userInfo: [NSLocalizedDescriptionKey: "\(message) - \(debug)"])
        }
        
        guard let contactData = jsonResponse?["contact_data"] as? [String: Any] else {
            return nil
        }
        
        // Parse the contact data from server response
        return ContactCreateData(
            firstName: contactData["first_name"] as? String ?? "",
            lastName: contactData["last_name"] as? String ?? "",
            email: contactData["email_primary"] as? String,
            phone: contactData["work_phone"] as? String,
            mobilePhone: contactData["mobile_phone"] as? String,
            company: contactData["organization_name"] as? String,
            jobTitle: contactData["job_title"] as? String,
            address: contactData["street_address"] as? String,
            city: contactData["city"] as? String,
            state: contactData["state"] as? String,
            zipCode: contactData["zip_code"] as? String,
            country: contactData["country"] as? String,
            website: contactData["website_url"] as? String,
            notes: contactData["notes"] as? String,
            commentsFromLead: contactData["comments_from_lead"] as? String,
            birthdate: contactData["birthdate"] as? String,
            photoUrl: contactData["photo_url"] as? String,
            source: "qr_scan",
            sourceMetadata: "{\"qr_image_upload\":true,\"processed_at\":\"\(ISO8601DateFormatter().string(from: Date()))\"}"
        )
    }
}

#Preview {
    MinimalQRScannerView(viewModel: ContactsViewModel())
}
