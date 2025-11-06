//
//  LeadDetailsView.swift
//  ShareMyCard
//
//  Lead details view with clickable contact information and convert to contact functionality
//

import SwiftUI

struct LeadDetailsView: View {
    let lead: Lead
    @ObservedObject var viewModel: LeadsViewModel
    @Environment(\.dismiss) private var dismiss
    @State private var isConverting = false
    @State private var showConversionSuccess = false
    @State private var showConversionError = false
    @State private var conversionError: String?
    
    var body: some View {
        NavigationView {
            Form {
                statusSection
                basicInformationSection
                professionalInformationSection
                addressSection
                additionalInformationSection
                sourceInformationSection
                convertToContactSection
            }
            .navigationTitle("Lead Details")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button("Close") {
                        dismiss()
                    }
                }
            }
            .alert("Success", isPresented: $showConversionSuccess) {
                Button("OK") {
                    // Refresh leads list and dismiss
                    Task {
                        await viewModel.refreshFromServer()
                    }
                    dismiss()
                }
            } message: {
                Text("Lead has been successfully converted to a contact.")
            }
            .alert("Error", isPresented: $showConversionError, presenting: conversionError) { _ in
                Button("OK") {}
            } message: { error in
                Text(error)
            }
        }
    }
    
    // MARK: - View Sections
    
    private var statusSection: some View {
        Group {
            if lead.isConverted {
                Section {
                    HStack {
                        Image(systemName: "checkmark.circle.fill")
                            .foregroundColor(.green)
                        Text("Converted to Contact")
                            .foregroundColor(.green)
                    }
                }
            }
        }
    }
    
    private var basicInformationSection: some View {
        Section("Basic Information") {
                    HStack {
                        Text("First Name")
                        Spacer()
                        Text(lead.firstName)
                            .foregroundColor(.secondary)
                    }
                    
                    HStack {
                        Text("Last Name")
                        Spacer()
                        Text(lead.lastName)
                            .foregroundColor(.secondary)
                    }
                    
                    if let email = lead.emailPrimary, !email.isEmpty {
                        HStack {
                            Text("Email")
                            Spacer()
                            Link(email, destination: URL(string: "mailto:\(email)") ?? URL(string: "mailto:")!)
                                .foregroundColor(.blue)
                        }
                    } else {
                        HStack {
                            Text("Email")
                            Spacer()
                            Text("Not provided")
                                .foregroundColor(.secondary)
                        }
                    }
                    
                    if let workPhone = lead.workPhone, !workPhone.isEmpty {
                        HStack {
                            Text("Work Phone")
                            Spacer()
                            Button(action: {
                                let cleanedPhone = workPhone.components(separatedBy: CharacterSet.decimalDigits.inverted).joined()
                                if let url = URL(string: "tel:\(cleanedPhone)") {
                                    UIApplication.shared.open(url)
                                }
                            }) {
                                Text(workPhone)
                                    .foregroundColor(.blue)
                            }
                        }
                    } else {
                        HStack {
                            Text("Work Phone")
                            Spacer()
                            Text("Not provided")
                                .foregroundColor(.secondary)
                        }
                    }
                    
                    if let mobilePhone = lead.mobilePhone, !mobilePhone.isEmpty {
                        HStack {
                            Text("Mobile Phone")
                            Spacer()
                            Button(action: {
                                let cleanedPhone = mobilePhone.components(separatedBy: CharacterSet.decimalDigits.inverted).joined()
                                if let url = URL(string: "tel:\(cleanedPhone)") {
                                    UIApplication.shared.open(url)
                                }
                            }) {
                                Text(mobilePhone)
                                    .foregroundColor(.blue)
                            }
                        }
                    } else {
                        HStack {
                            Text("Mobile Phone")
                            Spacer()
                            Text("Not provided")
                                .foregroundColor(.secondary)
                        }
                    }
                }
                
    }
    
    private var professionalInformationSection: some View {
        Section("Professional Information") {
            HStack {
                Text("Company")
                Spacer()
                Text(lead.organizationName ?? "Not provided")
                    .foregroundColor(.secondary)
            }
            
            HStack {
                Text("Job Title")
                Spacer()
                Text(lead.jobTitle ?? "Not provided")
                    .foregroundColor(.secondary)
            }
        }
    }
    
    private var addressSection: some View {
        Section("Address") {
            HStack {
                Text("Street Address")
                Spacer()
                Text(lead.streetAddress ?? "Not provided")
                    .foregroundColor(.secondary)
            }
            
            HStack {
                Text("City")
                Spacer()
                Text(lead.city ?? "Not provided")
                    .foregroundColor(.secondary)
            }
            
            HStack {
                Text("State")
                Spacer()
                Text(lead.state ?? "Not provided")
                    .foregroundColor(.secondary)
            }
            
            HStack {
                Text("ZIP Code")
                Spacer()
                Text(lead.zipCode ?? "Not provided")
                    .foregroundColor(.secondary)
            }
            
            HStack {
                Text("Country")
                Spacer()
                Text(lead.country ?? "Not provided")
                    .foregroundColor(.secondary)
            }
        }
    }
    
    private var additionalInformationSection: some View {
        Section("Additional Information") {
            if let website = lead.websiteUrl, !website.isEmpty {
                HStack {
                    Text("Website")
                    Spacer()
                    Button(action: {
                        var urlStringToOpen = website
                        if !urlStringToOpen.hasPrefix("http://") && !urlStringToOpen.hasPrefix("https://") {
                            urlStringToOpen = "https://\(urlStringToOpen)"
                        }
                        if let url = URL(string: urlStringToOpen) {
                            UIApplication.shared.open(url)
                        }
                    }) {
                        Text(website)
                            .foregroundColor(.blue)
                            .lineLimit(1)
                    }
                }
            } else {
                HStack {
                    Text("Website")
                    Spacer()
                    Text("Not provided")
                        .foregroundColor(.secondary)
                }
            }
            
            if let comments = lead.commentsFromLead, !comments.isEmpty {
                HStack {
                    Text("Comments")
                    Spacer()
                    Text(comments)
                        .foregroundColor(.secondary)
                        .multilineTextAlignment(.trailing)
                }
            }
            
            if let birthdate = lead.birthdate {
                HStack {
                    Text("Birthdate")
                    Spacer()
                    Text(birthdate)
                        .foregroundColor(.secondary)
                }
            }
        }
    }
    
    private var sourceInformationSection: some View {
        Section("Source Information") {
            HStack {
                Text("From Business Card")
                Spacer()
                Text(lead.cardDisplayName)
                    .foregroundColor(.secondary)
            }
            
            HStack {
                Text("Captured Date")
                Spacer()
                if !lead.formattedDate.isEmpty {
                    Text(lead.formattedDate)
                        .foregroundColor(.secondary)
                } else if !lead.relativeDate.isEmpty {
                    Text(lead.relativeDate)
                        .foregroundColor(.secondary)
                } else {
                    Text("Unknown")
                        .foregroundColor(.secondary)
                }
            }
        }
    }
    
    private var convertToContactSection: some View {
        Group {
            if !lead.isConverted {
                Section {
                    Button(action: {
                        convertToContact()
                    }) {
                        HStack {
                            if isConverting {
                                ProgressView()
                                    .progressViewStyle(CircularProgressViewStyle())
                            } else {
                                Image(systemName: "person.crop.circle.badge.plus")
                            }
                            Text(isConverting ? "Converting..." : "Convert to Contact")
                        }
                        .frame(maxWidth: .infinity)
                    }
                    .disabled(isConverting)
                }
            }
        }
    }
    
    private func convertToContact() {
        isConverting = true
        
        Task {
            do {
                let contactId = try await viewModel.convertLeadToContact(leadId: lead.id)
                print("✅ Lead converted to contact with ID: \(contactId)")
                
                await MainActor.run {
                    isConverting = false
                    showConversionSuccess = true
                }
            } catch {
                print("❌ Failed to convert lead: \(error)")
                await MainActor.run {
                    isConverting = false
                    conversionError = error.localizedDescription
                    showConversionError = true
                }
            }
        }
    }
    
}

#Preview {
    LeadDetailsView(
        lead: Lead(
            id: "1",
            firstName: "John",
            lastName: "Doe",
            fullName: "John Doe",
            emailPrimary: "john@example.com",
            workPhone: "555-1234",
            mobilePhone: nil,
            streetAddress: "123 Main St",
            city: "New York",
            state: "NY",
            zipCode: "10001",
            country: "USA",
            organizationName: "Acme Corp",
            jobTitle: "Manager",
            birthdate: nil,
            websiteUrl: "https://example.com",
            photoUrl: nil,
            commentsFromLead: "Interested in product",
            createdAt: "2024-01-01 12:00:00",
            updatedAt: nil,
            cardFirstName: "Jane",
            cardLastName: "Smith",
            cardCompany: "My Company",
            cardJobTitle: "CEO",
            qrTitle: nil,
            qrType: nil,
            status: "new"
        ),
        viewModel: LeadsViewModel()
    )
}

