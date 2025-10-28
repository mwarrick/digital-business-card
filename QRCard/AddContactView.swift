//
//  AddContactView.swift
//  ShareMyCard
//
//  Add Contact form view
//

import SwiftUI

struct AddContactView: View {
    @ObservedObject var viewModel: ContactsViewModel
    @Environment(\.dismiss) private var dismiss
    
    @State private var firstName = ""
    @State private var lastName = ""
    @State private var email = ""
    @State private var workPhone = ""
    @State private var mobilePhone = ""
    @State private var company = ""
    @State private var jobTitle = ""
    @State private var streetAddress = ""
    @State private var city = ""
    @State private var state = ""
    @State private var zipCode = ""
    @State private var country = ""
    @State private var website = ""
    @State private var notes = ""
    @State private var birthdate = ""
    @State private var photoUrl = ""
    
    @State private var isCreating = false
    @State private var showingError = false
    @State private var errorMessage = ""
    
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
            commentsFromLead: nil,
            birthdate: birthdate.isEmpty ? nil : birthdate,
            photoUrl: photoUrl.isEmpty ? nil : photoUrl,
            source: "manual",
            sourceMetadata: nil
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
    AddContactView(viewModel: ContactsViewModel())
}
