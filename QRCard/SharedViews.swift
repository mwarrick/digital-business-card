//
//  SharedViews.swift
//  ShareMyCard
//
//  Created by Mark Warrick on 10/10/25.
//

import SwiftUI

// MARK: - Contact Info Row
struct ContactInfoRow: View {
    let icon: String
    let text: String
    let color: Color
    
    init(icon: String, text: String, color: Color = .blue) {
        self.icon = icon
        self.text = text
        self.color = color
    }
    
    var body: some View {
        HStack(spacing: 12) {
            Image(systemName: icon)
                .foregroundColor(color)
                .frame(width: 20)
            
            Text(text)
                .font(.subheadline)
        }
    }
}

// MARK: - Add Email View
struct AddEmailView: View {
    @Environment(\.dismiss) private var dismiss
    @State private var email: String
    @State private var type: EmailType = .work
    @State private var label: String = ""
    @State private var isPrimary: Bool = false
    
    let onSave: (EmailContact) -> Void
    private let existingEmail: EmailContact?
    
    init(email: String = "", type: EmailType = .work, label: String = "", isPrimary: Bool = false, existingEmail: EmailContact? = nil, onSave: @escaping (EmailContact) -> Void) {
        print("AddEmailView: Initializing with email: '\(email)', type: \(type), label: '\(label)', isPrimary: \(isPrimary), existingEmail: \(existingEmail?.email ?? "nil")")
        self._email = State(initialValue: email)
        self._type = State(initialValue: type)
        self._label = State(initialValue: label)
        self._isPrimary = State(initialValue: isPrimary)
        self.existingEmail = existingEmail
        self.onSave = onSave
    }
    
    var body: some View {
        NavigationView {
            Form {
                Section("Email Details") {
                    TextField("Email Address", text: $email)
                        .keyboardType(.emailAddress)
                        .autocapitalization(.none)
                    
                    Picker("Type", selection: $type) {
                        ForEach(EmailType.allCases, id: \.self) { emailType in
                            Text(emailType.displayName).tag(emailType)
                        }
                    }
                    
                    TextField("Label (Optional)", text: $label)
                    
                    Toggle("Primary Email", isOn: $isPrimary)
                }
            }
            .navigationTitle(email.isEmpty ? "Add Email" : "Edit Email")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarLeading) {
                    Button("Cancel") {
                        dismiss()
                    }
                }
                
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button("Save") {
                        let emailContact: EmailContact
                        if let existing = existingEmail {
                            // Preserve the original ID when editing
                            emailContact = EmailContact(
                                id: existing.id,
                                email: email,
                                type: type,
                                label: label.isEmpty ? nil : label,
                                isPrimary: isPrimary
                            )
                        } else {
                            // Create new email contact
                            emailContact = EmailContact(
                                email: email,
                                type: type,
                                label: label.isEmpty ? nil : label,
                                isPrimary: isPrimary
                            )
                        }
                        onSave(emailContact)
                        dismiss()
                    }
                    .disabled(email.isEmpty)
                }
            }
        }
    }
}

// MARK: - Add Phone View
struct AddPhoneView: View {
    @Environment(\.dismiss) private var dismiss
    @State private var phoneNumber: String
    @State private var type: PhoneType = .mobile
    @State private var label: String = ""
    
    let onSave: (PhoneContact) -> Void
    private let existingPhone: PhoneContact?
    
    init(phoneNumber: String = "", type: PhoneType = .mobile, label: String = "", existingPhone: PhoneContact? = nil, onSave: @escaping (PhoneContact) -> Void) {
        self._phoneNumber = State(initialValue: phoneNumber)
        self._type = State(initialValue: type)
        self._label = State(initialValue: label)
        self.existingPhone = existingPhone
        self.onSave = onSave
    }
    
    var body: some View {
        NavigationView {
            Form {
                Section("Phone Details") {
                    TextField("Phone Number", text: $phoneNumber)
                        .keyboardType(.phonePad)
                    
                    Picker("Type", selection: $type) {
                        ForEach(PhoneType.allCases, id: \.self) { phoneType in
                            Text(phoneType.displayName).tag(phoneType)
                        }
                    }
                    
                    TextField("Label (Optional)", text: $label)
                }
            }
            .navigationTitle(phoneNumber.isEmpty ? "Add Phone" : "Edit Phone")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarLeading) {
                    Button("Cancel") {
                        dismiss()
                    }
                }
                
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button("Save") {
                        let phoneContact: PhoneContact
                        if let existing = existingPhone {
                            // Preserve the original ID when editing
                            phoneContact = PhoneContact(
                                id: existing.id,
                                phoneNumber: phoneNumber,
                                type: type,
                                label: label.isEmpty ? nil : label
                            )
                        } else {
                            // Create new phone contact
                            phoneContact = PhoneContact(
                                phoneNumber: phoneNumber,
                                type: type,
                                label: label.isEmpty ? nil : label
                            )
                        }
                        onSave(phoneContact)
                        dismiss()
                    }
                    .disabled(phoneNumber.isEmpty)
                }
            }
        }
    }
}

// MARK: - Add Website View
struct AddWebsiteView: View {
    @Environment(\.dismiss) private var dismiss
    @State private var name: String
    @State private var url: String
    @State private var description: String
    @State private var isPrimary: Bool = false
    
    let onSave: (WebsiteLink) -> Void
    private let existingWebsite: WebsiteLink?
    
    init(name: String = "", url: String = "", description: String = "", isPrimary: Bool = false, existingWebsite: WebsiteLink? = nil, onSave: @escaping (WebsiteLink) -> Void) {
        self._name = State(initialValue: name)
        self._url = State(initialValue: url)
        self._description = State(initialValue: description)
        self._isPrimary = State(initialValue: isPrimary)
        self.existingWebsite = existingWebsite
        self.onSave = onSave
    }
    
    var body: some View {
        NavigationView {
            Form {
                Section("Website Details") {
                    TextField("Website Name", text: $name)
                    TextField("URL", text: $url)
                        .keyboardType(.URL)
                        .autocapitalization(.none)
                    TextField("Description (Optional)", text: $description)
                    
                    Toggle("Primary Website", isOn: $isPrimary)
                }
            }
            .navigationTitle(name.isEmpty ? "Add Website" : "Edit Website")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarLeading) {
                    Button("Cancel") {
                        dismiss()
                    }
                }
                
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button("Save") {
                        let websiteLink: WebsiteLink
                        if let existing = existingWebsite {
                            // Preserve the original ID when editing
                            websiteLink = WebsiteLink(
                                id: existing.id,
                                name: name,
                                url: url,
                                description: description.isEmpty ? nil : description,
                                isPrimary: isPrimary
                            )
                        } else {
                            // Create new website link
                            websiteLink = WebsiteLink(
                                name: name,
                                url: url,
                                description: description.isEmpty ? nil : description,
                                isPrimary: isPrimary
                            )
                        }
                        onSave(websiteLink)
                        dismiss()
                    }
                    .disabled(name.isEmpty || url.isEmpty)
                }
            }
        }
    }
}
