//
//  MinimalQRScannerView.swift
//  ShareMyCard
//
//  Minimal QR Code scanner for adding contacts
//

import SwiftUI
import PhotosUI
import AVFoundation

struct MinimalQRScannerView: View {
    @ObservedObject var viewModel: ContactsViewModel
    @Environment(\.dismiss) private var dismiss
    
    @State private var showingContactForm = false
    @State private var scannedContactData: ContactCreateData?
    @State private var showingError = false
    @State private var errorMessage = ""
    @State private var showingImagePicker = false
    @State private var selectedImage: UIImage?
    @State private var isProcessingImage = false
    @State private var isScanning = false
    @State private var showingCamera = false
    
    var body: some View {
        NavigationView {
            VStack(spacing: 20) {
                Text("QR Code Scanner")
                    .font(.largeTitle)
                    .fontWeight(.bold)
                
                Text("Scan a QR code with your camera or upload an image")
                    .font(.subheadline)
                    .foregroundColor(.secondary)
                    .multilineTextAlignment(.center)
                
                VStack(spacing: 12) {
                    #if targetEnvironment(simulator)
                    Button("Upload QR Image (Simulator)") {
                        showingImagePicker = true
                    }
                    .buttonStyle(.borderedProminent)
                    .disabled(isProcessingImage)
                    
                    Text("Camera not available in simulator")
                        .font(.caption)
                        .foregroundColor(.secondary)
                        .multilineTextAlignment(.center)
                    #else
                    Button("Scan with Camera") {
                        showingCamera = true
                    }
                    .buttonStyle(.borderedProminent)
                    .disabled(isProcessingImage)
                    
                    Button("Upload QR Image") {
                        showingImagePicker = true
                    }
                    .buttonStyle(.bordered)
                    .disabled(isProcessingImage)
                    #endif
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
            .sheet(isPresented: $showingCamera) {
                QRCodeScannerView(
                    onQRCodeDetected: { qrCodeString in
                        processQRCodeString(qrCodeString)
                    },
                    onDismiss: {
                        showingCamera = false
                    }
                )
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
    
    private func processQRCodeString(_ qrCodeString: String) {
        print("🔍 QR Code detected: \(qrCodeString)")
        
        // Close camera
        showingCamera = false
        
        // Process the QR code string
        if let contactData = parseQRCodeString(qrCodeString) {
            scannedContactData = contactData
            showingContactForm = true
        } else {
            errorMessage = "Could not parse contact information from QR code"
            showingError = true
        }
    }
    
    private func parseQRCodeString(_ qrString: String) -> ContactCreateData? {
        // Check if it's a vCard
        if qrString.hasPrefix("BEGIN:VCARD") {
            return parseVCard(qrString)
        }
        
        // Check if it's a URL that might contain vCard data
        if qrString.hasPrefix("http://") || qrString.hasPrefix("https://") {
            // For URL-based QR codes, we need to fetch the content
            // For now, create a placeholder that indicates URL processing is needed
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
                website: qrString,
                notes: "URL-based QR code detected. Processing...",
                commentsFromLead: nil,
                birthdate: nil,
                photoUrl: nil,
                source: "qr_scan",
                sourceMetadata: "{\"qr_data\":\"\(qrString)\",\"type\":\"url\",\"needs_processing\":true}"
            )
        }
        
        // Try to parse as plain text contact info
        return parsePlainTextContact(qrString)
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
        
        // Clean the response data to remove any HTML warnings that might be mixed in
        let responseString = String(data: data, encoding: .utf8) ?? ""
        let cleanedResponse = cleanJSONResponse(responseString)
        let cleanedData = cleanedResponse.data(using: .utf8) ?? data
        
        let jsonResponse = try JSONSerialization.jsonObject(with: cleanedData) as? [String: Any]
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
    
    // MARK: - Helper Functions
    
    /// Clean JSON response by removing HTML warnings and extracting pure JSON
    private func cleanJSONResponse(_ response: String) -> String {
        // Look for JSON object starting with { and ending with }
        if let jsonStart = response.range(of: "{"),
           let jsonEnd = response.range(of: "}", options: .backwards) {
            let startIndex = jsonStart.lowerBound
            let endIndex = jsonEnd.upperBound
            return String(response[startIndex..<endIndex])
        }
        
        // If no JSON object found, return original response
        return response
    }
}

#Preview {
    MinimalQRScannerView(viewModel: ContactsViewModel())
}
