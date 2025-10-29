//
//  EditContactView.swift
//  ShareMyCard
//
//  Edit Contact form view
//

import SwiftUI

struct EditContactView: View {
    let contact: Contact
    @ObservedObject var viewModel: ContactsViewModel
    @Environment(\.dismiss) private var dismiss
    
    // Callback to show contact details after successful update
    let onUpdateSuccess: (Contact) -> Void
    
    @State private var firstName: String
    @State private var lastName: String
    @State private var email: String
    @State private var workPhone: String
    @State private var mobilePhone: String
    @State private var company: String
    @State private var jobTitle: String
    @State private var streetAddress: String
    @State private var city: String
    @State private var state: String
    @State private var zipCode: String
    @State private var country: String
    @State private var website: String
    @State private var notes: String
    @State private var birthdate: String
    @State private var photoUrl: String
    
    @State private var isUpdating = false
    @State private var showingError = false
    @State private var errorMessage = ""
    
    init(contact: Contact, viewModel: ContactsViewModel, onUpdateSuccess: @escaping (Contact) -> Void = { _ in }) {
        self.contact = contact
        self.viewModel = viewModel
        self.onUpdateSuccess = onUpdateSuccess
        
        // Initialize form state with existing contact data
        _firstName = State(initialValue: contact.firstName)
        _lastName = State(initialValue: contact.lastName)
        _email = State(initialValue: contact.email ?? "")
        _workPhone = State(initialValue: contact.phone ?? "")
        _mobilePhone = State(initialValue: contact.mobilePhone ?? "")
        _company = State(initialValue: contact.company ?? "")
        _jobTitle = State(initialValue: contact.jobTitle ?? "")
        _streetAddress = State(initialValue: contact.address ?? "")
        _city = State(initialValue: contact.city ?? "")
        _state = State(initialValue: contact.state ?? "")
        _zipCode = State(initialValue: contact.zipCode ?? "")
        _country = State(initialValue: contact.country ?? "")
        _website = State(initialValue: contact.website ?? "")
        _notes = State(initialValue: contact.notes ?? "")
        _birthdate = State(initialValue: contact.birthdate ?? "")
        _photoUrl = State(initialValue: contact.photoUrl ?? "")
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
                        TextField("Optional", text: $workPhone)
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
                        TextField("Optional", text: $streetAddress)
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
            }
            .navigationTitle("Edit Contact")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarLeading) {
                    Button("Cancel") {
                        dismiss()
                    }
                }
                
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button("Save") {
                        updateContact()
                    }
                    .disabled(isUpdating || firstName.isEmpty || lastName.isEmpty)
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
    
    private func updateContact() {
        guard !firstName.isEmpty && !lastName.isEmpty else {
            errorMessage = "First name and last name are required"
            showingError = true
            return
        }
        
        isUpdating = true
        
        let contactData = ContactCreateData(
            firstName: firstName,
            lastName: lastName,
            email: email.isEmpty ? nil : email,
            phone: workPhone.isEmpty ? nil : workPhone,
            mobilePhone: mobilePhone.isEmpty ? nil : mobilePhone,
            company: company.isEmpty ? nil : company,
            jobTitle: jobTitle.isEmpty ? nil : jobTitle,
            address: streetAddress.isEmpty ? nil : streetAddress,
            city: city.isEmpty ? nil : city,
            state: state.isEmpty ? nil : state,
            zipCode: zipCode.isEmpty ? nil : zipCode,
            country: country.isEmpty ? nil : country,
            website: website.isEmpty ? nil : website,
            notes: notes.isEmpty ? nil : notes,
            commentsFromLead: contact.commentsFromLead, // Preserve original comments
            birthdate: birthdate.isEmpty ? nil : birthdate,
            photoUrl: photoUrl.isEmpty ? nil : photoUrl,
            source: contact.source, // Preserve original source
            sourceMetadata: contact.sourceMetadata // Preserve original metadata
        )
        
        Task {
            do {
                // Step 1 & 2: Update on server, then fetch latest via ViewModel helper
                print("🔄 EditContactView: Updating contact and fetching latest via ViewModel...")
                let latestContact = try await viewModel.updateContactAndReload(contact, with: contactData)
                print("✅ EditContactView: Received latest contact: \(latestContact.displayName)")
                
                // Step 3 & 4: Dismiss edit and navigate to details
                await MainActor.run {
                    isUpdating = false
                    dismiss()
                    onUpdateSuccess(latestContact)
                }
            } catch {
                print("❌ EditContactView: Update failed: \(error)")
                await MainActor.run {
                    isUpdating = false
                    errorMessage = "Failed to update contact: \(error.localizedDescription)"
                    showingError = true
                }
            }
        }
    }
}

#Preview {
    EditContactView(
        contact: Contact(
            id: "1",
            firstName: "John",
            lastName: "Doe",
            email: "john@example.com",
            phone: "555-0100",
            mobilePhone: "555-0101",
            company: "Acme Corp",
            jobTitle: "Software Engineer",
            address: "123 Main St",
            city: "San Francisco",
            state: "CA",
            zipCode: "94102",
            country: "USA",
            website: "https://example.com",
            notes: "Met at conference",
            commentsFromLead: nil,
            birthdate: "1990-01-01",
            photoUrl: nil,
            source: "manual",
            sourceMetadata: nil,
            createdAt: "2025-01-01T00:00:00Z",
            updatedAt: "2025-01-01T00:00:00Z"
        ),
        viewModel: ContactsViewModel(),
        onUpdateSuccess: { _ in }
    )
}
