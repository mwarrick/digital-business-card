//
//  QRContactFormView.swift
//  ShareMyCard
//
//  Contact form for QR scanned contacts
//

import SwiftUI

struct QRContactFormView: View {
    @State private var contactData: ContactCreateData
    @ObservedObject var viewModel: ContactsViewModel
    @Environment(\.dismiss) private var dismiss
    
    @State private var isCreating = false
    @State private var showingError = false
    @State private var errorMessage = ""
    
    init(contactData: ContactCreateData, viewModel: ContactsViewModel) {
        self._contactData = State(initialValue: contactData)
        self.viewModel = viewModel
    }
    
    var body: some View {
        NavigationView {
            Form {
                // Basic Information Section
                Section("Basic Information") {
                    HStack {
                        Text("First Name")
                        Spacer()
                        TextField("Required", text: $contactData.firstName)
                            .textFieldStyle(RoundedBorderTextFieldStyle())
                            .multilineTextAlignment(.trailing)
                    }
                    
                    HStack {
                        Text("Last Name")
                        Spacer()
                        TextField("Required", text: $contactData.lastName)
                            .textFieldStyle(RoundedBorderTextFieldStyle())
                            .multilineTextAlignment(.trailing)
                    }
                    
                    HStack {
                        Text("Email")
                        Spacer()
                        TextField("Optional", text: Binding(
                            get: { contactData.email ?? "" },
                            set: { contactData.email = $0.isEmpty ? nil : $0 }
                        ))
                        .textFieldStyle(RoundedBorderTextFieldStyle())
                        .multilineTextAlignment(.trailing)
                        .keyboardType(.emailAddress)
                        .autocapitalization(.none)
                    }
                    
                    HStack {
                        Text("Work Phone")
                        Spacer()
                        TextField("Optional", text: Binding(
                            get: { contactData.phone ?? "" },
                            set: { contactData.phone = $0.isEmpty ? nil : $0 }
                        ))
                        .textFieldStyle(RoundedBorderTextFieldStyle())
                        .multilineTextAlignment(.trailing)
                        .keyboardType(.phonePad)
                    }
                    
                    HStack {
                        Text("Mobile Phone")
                        Spacer()
                        TextField("Optional", text: Binding(
                            get: { contactData.mobilePhone ?? "" },
                            set: { contactData.mobilePhone = $0.isEmpty ? nil : $0 }
                        ))
                        .textFieldStyle(RoundedBorderTextFieldStyle())
                        .multilineTextAlignment(.trailing)
                        .keyboardType(.phonePad)
                    }
                }
                
                // Professional Information Section
                Section("Professional Information") {
                    HStack {
                        Text("Company")
                        Spacer()
                        TextField("Optional", text: Binding(
                            get: { contactData.company ?? "" },
                            set: { contactData.company = $0.isEmpty ? nil : $0 }
                        ))
                        .textFieldStyle(RoundedBorderTextFieldStyle())
                        .multilineTextAlignment(.trailing)
                    }
                    
                    HStack {
                        Text("Job Title")
                        Spacer()
                        TextField("Optional", text: Binding(
                            get: { contactData.jobTitle ?? "" },
                            set: { contactData.jobTitle = $0.isEmpty ? nil : $0 }
                        ))
                        .textFieldStyle(RoundedBorderTextFieldStyle())
                        .multilineTextAlignment(.trailing)
                    }
                }
                
                // Address Section
                Section("Address") {
                    HStack {
                        Text("Street Address")
                        Spacer()
                        TextField("Optional", text: Binding(
                            get: { contactData.address ?? "" },
                            set: { contactData.address = $0.isEmpty ? nil : $0 }
                        ))
                        .textFieldStyle(RoundedBorderTextFieldStyle())
                        .multilineTextAlignment(.trailing)
                    }
                    
                    HStack {
                        Text("City")
                        Spacer()
                        TextField("Optional", text: Binding(
                            get: { contactData.city ?? "" },
                            set: { contactData.city = $0.isEmpty ? nil : $0 }
                        ))
                        .textFieldStyle(RoundedBorderTextFieldStyle())
                        .multilineTextAlignment(.trailing)
                    }
                    
                    HStack {
                        Text("State")
                        Spacer()
                        TextField("Optional", text: Binding(
                            get: { contactData.state ?? "" },
                            set: { contactData.state = $0.isEmpty ? nil : $0 }
                        ))
                        .textFieldStyle(RoundedBorderTextFieldStyle())
                        .multilineTextAlignment(.trailing)
                    }
                    
                    HStack {
                        Text("ZIP Code")
                        Spacer()
                        TextField("Optional", text: Binding(
                            get: { contactData.zipCode ?? "" },
                            set: { contactData.zipCode = $0.isEmpty ? nil : $0 }
                        ))
                        .textFieldStyle(RoundedBorderTextFieldStyle())
                        .multilineTextAlignment(.trailing)
                        .keyboardType(.numberPad)
                    }
                    
                    HStack {
                        Text("Country")
                        Spacer()
                        TextField("Optional", text: Binding(
                            get: { contactData.country ?? "" },
                            set: { contactData.country = $0.isEmpty ? nil : $0 }
                        ))
                        .textFieldStyle(RoundedBorderTextFieldStyle())
                        .multilineTextAlignment(.trailing)
                    }
                }
                
                // Additional Information Section
                Section("Additional Information") {
                    HStack {
                        Text("Website")
                        Spacer()
                        TextField("Optional", text: Binding(
                            get: { contactData.website ?? "" },
                            set: { contactData.website = $0.isEmpty ? nil : $0 }
                        ))
                        .textFieldStyle(RoundedBorderTextFieldStyle())
                        .multilineTextAlignment(.trailing)
                        .keyboardType(.URL)
                        .autocapitalization(.none)
                    }
                    
                    HStack {
                        Text("Birthdate")
                        Spacer()
                        TextField("YYYY-MM-DD", text: Binding(
                            get: { contactData.birthdate ?? "" },
                            set: { contactData.birthdate = $0.isEmpty ? nil : $0 }
                        ))
                        .textFieldStyle(RoundedBorderTextFieldStyle())
                        .multilineTextAlignment(.trailing)
                    }
                    
                    HStack {
                        Text("Photo URL")
                        Spacer()
                        TextField("Optional", text: Binding(
                            get: { contactData.photoUrl ?? "" },
                            set: { contactData.photoUrl = $0.isEmpty ? nil : $0 }
                        ))
                        .textFieldStyle(RoundedBorderTextFieldStyle())
                        .multilineTextAlignment(.trailing)
                        .keyboardType(.URL)
                        .autocapitalization(.none)
                    }
                }
                
                // Notes Section
                Section("Notes") {
                    VStack(alignment: .leading) {
                        Text("Notes")
                            .font(.headline)
                        TextEditor(text: Binding(
                            get: { contactData.notes ?? "" },
                            set: { contactData.notes = $0.isEmpty ? nil : $0 }
                        ))
                        .frame(minHeight: 100)
                        .overlay(
                            RoundedRectangle(cornerRadius: 8)
                                .stroke(Color.gray.opacity(0.3), lineWidth: 1)
                        )
                    }
                }
                
                // QR Source Info
                Section("Source") {
                    HStack {
                        Text("Source")
                        Spacer()
                        Text("QR Code Scan")
                            .foregroundColor(.secondary)
                    }
                    
                    if let metadata = contactData.sourceMetadata {
                        HStack {
                            Text("QR Data")
                            Spacer()
                            Text(String(metadata.prefix(50)) + "...")
                                .foregroundColor(.secondary)
                                .font(.caption)
                        }
                    }
                }
            }
            .navigationTitle("Add Contact")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarLeading) {
                    Button("Cancel") {
                        dismiss()
                    }
                }
                
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button("Save") {
                        createContact()
                    }
                    .disabled(isCreating || contactData.firstName.isEmpty || contactData.lastName.isEmpty)
                }
            }
            .alert("Error", isPresented: $showingError) {
                Button("OK") { }
            } message: {
                Text(errorMessage)
            }
        }
    }
    
    // MARK: - Actions
    
    private func createContact() {
        guard !contactData.firstName.isEmpty && !contactData.lastName.isEmpty else {
            errorMessage = "First name and last name are required"
            showingError = true
            return
        }
        
        isCreating = true
        
        Task {
            do {
                try await viewModel.createContact(contactData)
                await MainActor.run {
                    dismiss()
                }
            } catch {
                await MainActor.run {
                    errorMessage = "Failed to create contact: \(error.localizedDescription)"
                    showingError = true
                    isCreating = false
                }
            }
        }
    }
}

#Preview {
    QRContactFormView(
        contactData: ContactCreateData(
            firstName: "John",
            lastName: "Doe",
            email: "john@example.com",
            phone: "+1-555-123-4567",
            mobilePhone: nil,
            company: "Tech Corp",
            jobTitle: "Software Engineer",
            address: "123 Main St",
            city: "San Francisco",
            state: "CA",
            zipCode: "94105",
            country: "USA",
            website: "https://example.com",
            notes: "Met at conference",
            commentsFromLead: nil,
            birthdate: nil,
            photoUrl: nil,
            source: "qr_scan",
            sourceMetadata: "{\"qr_data\":\"BEGIN:VCARD...\"}"
        ),
        viewModel: ContactsViewModel()
    )
}
