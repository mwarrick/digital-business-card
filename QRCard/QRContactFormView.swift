//
//  QRContactFormView.swift
//  ShareMyCard
//
//  Contact form for QR scanned contacts
//

import SwiftUI

struct QRContactFormView: View {
    @State private var firstName: String
    @State private var lastName: String
    @State private var email: String
    @State private var phone: String
    @State private var mobilePhone: String
    @State private var company: String
    @State private var jobTitle: String
    @State private var address: String
    @State private var city: String
    @State private var state: String
    @State private var zipCode: String
    @State private var country: String
    @State private var website: String
    @State private var notes: String
    @State private var birthdate: String
    @State private var photoUrl: String
    @State private var source: String
    @State private var sourceMetadata: String?
    
    @ObservedObject var viewModel: ContactsViewModel
    @Environment(\.dismiss) private var dismiss
    
    @State private var isCreating = false
    @State private var showingError = false
    @State private var errorMessage = ""
    
    init(contactData: ContactCreateData, viewModel: ContactsViewModel) {
        self._firstName = State(initialValue: contactData.firstName)
        self._lastName = State(initialValue: contactData.lastName)
        self._email = State(initialValue: contactData.email ?? "")
        self._phone = State(initialValue: contactData.phone ?? "")
        self._mobilePhone = State(initialValue: contactData.mobilePhone ?? "")
        self._company = State(initialValue: contactData.company ?? "")
        self._jobTitle = State(initialValue: contactData.jobTitle ?? "")
        self._address = State(initialValue: contactData.address ?? "")
        self._city = State(initialValue: contactData.city ?? "")
        self._state = State(initialValue: contactData.state ?? "")
        self._zipCode = State(initialValue: contactData.zipCode ?? "")
        self._country = State(initialValue: contactData.country ?? "")
        self._website = State(initialValue: contactData.website ?? "")
        self._notes = State(initialValue: contactData.notes ?? "")
        self._birthdate = State(initialValue: contactData.birthdate ?? "")
        self._photoUrl = State(initialValue: contactData.photoUrl ?? "")
        self._source = State(initialValue: contactData.source ?? "")
        self._sourceMetadata = State(initialValue: contactData.sourceMetadata)
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
                        TextField("Required", text: $firstName)
                            .textFieldStyle(RoundedBorderTextFieldStyle())
                            .multilineTextAlignment(.trailing)
                    }
                    
                    HStack {
                        Text("Last Name")
                        Spacer()
                        TextField("Required", text: $lastName)
                            .textFieldStyle(RoundedBorderTextFieldStyle())
                            .multilineTextAlignment(.trailing)
                    }
                    
                    HStack {
                        Text("Email")
                        Spacer()
                        TextField("Optional", text: $email)
                            .textFieldStyle(RoundedBorderTextFieldStyle())
                            .multilineTextAlignment(.trailing)
                            .keyboardType(.emailAddress)
                            .autocapitalization(.none)
                    }
                    
                    HStack {
                        Text("Work Phone")
                        Spacer()
                        TextField("Optional", text: $phone)
                            .textFieldStyle(RoundedBorderTextFieldStyle())
                            .multilineTextAlignment(.trailing)
                            .keyboardType(.phonePad)
                    }
                    
                    HStack {
                        Text("Mobile Phone")
                        Spacer()
                        TextField("Optional", text: $mobilePhone)
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
                        TextField("Optional", text: $company)
                            .textFieldStyle(RoundedBorderTextFieldStyle())
                            .multilineTextAlignment(.trailing)
                    }
                    
                    HStack {
                        Text("Job Title")
                        Spacer()
                        TextField("Optional", text: $jobTitle)
                            .textFieldStyle(RoundedBorderTextFieldStyle())
                            .multilineTextAlignment(.trailing)
                    }
                }
                
                // Address Section
                Section("Address") {
                    HStack {
                        Text("Street Address")
                        Spacer()
                        TextField("Optional", text: $address)
                            .textFieldStyle(RoundedBorderTextFieldStyle())
                            .multilineTextAlignment(.trailing)
                    }
                    
                    HStack {
                        Text("City")
                        Spacer()
                        TextField("Optional", text: $city)
                            .textFieldStyle(RoundedBorderTextFieldStyle())
                            .multilineTextAlignment(.trailing)
                    }
                    
                    HStack {
                        Text("State")
                        Spacer()
                        TextField("Optional", text: $state)
                            .textFieldStyle(RoundedBorderTextFieldStyle())
                            .multilineTextAlignment(.trailing)
                    }
                    
                    HStack {
                        Text("ZIP Code")
                        Spacer()
                        TextField("Optional", text: $zipCode)
                            .textFieldStyle(RoundedBorderTextFieldStyle())
                            .multilineTextAlignment(.trailing)
                            .keyboardType(.numberPad)
                    }
                    
                    HStack {
                        Text("Country")
                        Spacer()
                        TextField("Optional", text: $country)
                            .textFieldStyle(RoundedBorderTextFieldStyle())
                            .multilineTextAlignment(.trailing)
                    }
                }
                
                // Additional Information Section
                Section("Additional Information") {
                    HStack {
                        Text("Website")
                        Spacer()
                        TextField("Optional", text: $website)
                            .textFieldStyle(RoundedBorderTextFieldStyle())
                            .multilineTextAlignment(.trailing)
                            .keyboardType(.URL)
                            .autocapitalization(.none)
                    }
                    
                    HStack {
                        Text("Birthdate")
                        Spacer()
                        TextField("YYYY-MM-DD", text: $birthdate)
                            .textFieldStyle(RoundedBorderTextFieldStyle())
                            .multilineTextAlignment(.trailing)
                    }
                    
                    HStack {
                        Text("Photo URL")
                        Spacer()
                        TextField("Optional", text: $photoUrl)
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
                        TextEditor(text: $notes)
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
                    
                    if let metadata = sourceMetadata {
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
                    .disabled(isCreating || firstName.isEmpty || lastName.isEmpty)
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
        guard !firstName.isEmpty && !lastName.isEmpty else {
            errorMessage = "First name and last name are required"
            showingError = true
            return
        }
        
        isCreating = true
        
        let contactData = ContactCreateData(
            firstName: firstName,
            lastName: lastName,
            email: email.isEmpty ? nil : email,
            phone: phone.isEmpty ? nil : phone,
            mobilePhone: mobilePhone.isEmpty ? nil : mobilePhone,
            company: company.isEmpty ? nil : company,
            jobTitle: jobTitle.isEmpty ? nil : jobTitle,
            address: address.isEmpty ? nil : address,
            city: city.isEmpty ? nil : city,
            state: state.isEmpty ? nil : state,
            zipCode: zipCode.isEmpty ? nil : zipCode,
            country: country.isEmpty ? nil : country,
            website: website.isEmpty ? nil : website,
            notes: notes.isEmpty ? nil : notes,
            commentsFromLead: nil,
            birthdate: birthdate.isEmpty ? nil : birthdate,
            photoUrl: photoUrl.isEmpty ? nil : photoUrl,
            source: source.isEmpty ? "qr_scan" : source,
            sourceMetadata: sourceMetadata
        )
        
        Task {
            do {
                let createdContact = try await viewModel.createContact(contactData)
                await MainActor.run {
                    // Set the created contact as selected and show details
                    viewModel.selectedContact = createdContact
                    viewModel.showingContactDetails = true
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
