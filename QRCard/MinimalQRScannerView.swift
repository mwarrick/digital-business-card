//
//  MinimalQRScannerView.swift
//  ShareMyCard
//
//  Minimal QR Code scanner for adding contacts
//

import SwiftUI

struct MinimalQRScannerView: View {
    @ObservedObject var viewModel: ContactsViewModel
    @Environment(\.dismiss) private var dismiss
    
    @State private var qrCodeText = ""
    @State private var showingContactForm = false
    @State private var scannedContactData: ContactCreateData?
    @State private var showingError = false
    @State private var errorMessage = ""
    
    var body: some View {
        NavigationView {
            VStack(spacing: 20) {
                Text("QR Code Scanner")
                    .font(.largeTitle)
                    .fontWeight(.bold)
                
                Text("Enter QR code data manually or use camera scanning")
                    .font(.subheadline)
                    .foregroundColor(.secondary)
                    .multilineTextAlignment(.center)
                
                VStack(alignment: .leading, spacing: 8) {
                    Text("QR Code Data")
                        .font(.headline)
                    
                    TextEditor(text: $qrCodeText)
                        .frame(minHeight: 200)
                        .overlay(
                            RoundedRectangle(cornerRadius: 8)
                                .stroke(Color.gray.opacity(0.3), lineWidth: 1)
                        )
                        .overlay(
                            Group {
                                if qrCodeText.isEmpty {
                                    Text("Paste vCard data or URL here...")
                                        .foregroundColor(.gray)
                                        .padding(.top, 8)
                                        .padding(.leading, 4)
                                }
                            }
                        )
                }
                
                Button("Process QR Code") {
                    processQRCode()
                }
                .buttonStyle(.borderedProminent)
                .disabled(qrCodeText.isEmpty)
                
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
            notes: "Scanned from QR code URL. This may redirect to a vCard file.",
            commentsFromLead: nil,
            birthdate: nil,
            photoUrl: nil,
            source: "qr_scan",
            sourceMetadata: "{\"qr_data\":\"\(url)\",\"type\":\"url\"}"
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
        var firstName = "QR"
        var lastName = "Contact"
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
            } else if !trimmedLine.isEmpty && !trimmedLine.contains("@") && !trimmedLine.range(of: "\\d{3}[-.\\s]?\\d{3}[-.\\s]?\\d{4}", options: .regularExpression) != nil {
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
}

#Preview {
    MinimalQRScannerView(viewModel: ContactsViewModel())
}
