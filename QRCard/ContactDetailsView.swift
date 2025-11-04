//
//  ContactDetailsView.swift
//  ShareMyCard
//
//  Contact details view with consistent formatting
//

import SwiftUI
import Contacts

struct ContactDetailsView: View {
    let contact: Contact
    @ObservedObject var viewModel: ContactsViewModel
    @Environment(\.dismiss) private var dismiss
    @State private var showingEditContact = false
    @State private var showingExport = false
    @State private var exportError: String? = nil
    @State private var showingExportError = false
    @State private var showingDeleteConfirmation = false
    @State private var isDeleting = false
    
    // Get updated contact from viewModel if available, otherwise use the initial contact
    private var currentContact: Contact {
        viewModel.contacts.first { $0.id == contact.id } ?? contact
    }
    
    var body: some View {
        NavigationView {
            Form {
                // Status Section - Show if converted from lead
                if isConvertedFromLead {
                    statusSection
                }
                
                // Basic Information Section
                Section("Basic Information") {
                    HStack {
                        Text("First Name")
                        Spacer()
                        Text(currentContact.firstName)
                            .foregroundColor(.secondary)
                    }
                    
                    HStack {
                        Text("Last Name")
                        Spacer()
                        Text(currentContact.lastName)
                            .foregroundColor(.secondary)
                    }
                    
                    if let email = currentContact.email, !email.isEmpty {
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
                    
                    if let phone = currentContact.phone, !phone.isEmpty {
                        HStack {
                            Text("Work Phone")
                            Spacer()
                            Button(action: {
                                let cleanedPhone = phone.components(separatedBy: CharacterSet.decimalDigits.inverted).joined()
                                if let url = URL(string: "tel:\(cleanedPhone)") {
                                    UIApplication.shared.open(url)
                                }
                            }) {
                                Text(phone)
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
                    
                    if let mobilePhone = currentContact.mobilePhone, !mobilePhone.isEmpty {
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
                
                // Professional Information Section
                Section("Professional Information") {
                    HStack {
                        Text("Company")
                        Spacer()
                        Text(currentContact.company ?? "Not provided")
                            .foregroundColor(.secondary)
                    }
                    
                    HStack {
                        Text("Job Title")
                        Spacer()
                        Text(currentContact.jobTitle ?? "Not provided")
                            .foregroundColor(.secondary)
                    }
                }
                
                // Address Section
                Section("Address") {
                    HStack {
                        Text("Street Address")
                        Spacer()
                        Text(currentContact.address ?? "Not provided")
                            .foregroundColor(.secondary)
                    }
                    
                    HStack {
                        Text("City")
                        Spacer()
                        Text(currentContact.city ?? "Not provided")
                            .foregroundColor(.secondary)
                    }
                    
                    HStack {
                        Text("State")
                        Spacer()
                        Text(currentContact.state ?? "Not provided")
                            .foregroundColor(.secondary)
                    }
                    
                    HStack {
                        Text("ZIP Code")
                        Spacer()
                        Text(currentContact.zipCode ?? "Not provided")
                            .foregroundColor(.secondary)
                    }
                    
                    HStack {
                        Text("Country")
                        Spacer()
                        Text(currentContact.country ?? "Not provided")
                            .foregroundColor(.secondary)
                    }
                }
                
                // Additional Information Section
                Section("Additional Information") {
                    if let website = currentContact.website, !website.isEmpty {
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
                    
                    HStack {
                        Text("Notes")
                        Spacer()
                        Text(currentContact.notes ?? "Not provided")
                            .foregroundColor(.secondary)
                    }
                    
                    HStack {
                        Text("Birthdate")
                        Spacer()
                        Text(currentContact.birthdate ?? "Not provided")
                            .foregroundColor(.secondary)
                    }
                    
                    HStack {
                        Text("Source")
                        Spacer()
                        Text(currentContact.source ?? "Not provided")
                            .foregroundColor(.secondary)
                    }
                }
            }
            .navigationTitle("Contact Details")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarLeading) {
                    Button("Close") {
                        dismiss()
                    }
                }
                
                ToolbarItem(placement: .navigationBarTrailing) {
                    HStack(spacing: 16) {
                        Button("Export") {
                            requestContactsPermissionAndExport()
                        }
                        Button("Edit") {
                            showingEditContact = true
                        }
                        Button(role: .destructive) {
                            showingDeleteConfirmation = true
                        } label: {
                            Image(systemName: "trash")
                        }
                        .disabled(isDeleting)
                    }
                }
            }
            .sheet(isPresented: $showingEditContact) {
                EditContactView(contact: currentContact, viewModel: viewModel) { updatedContact in
                    // After successful update, the view will automatically refresh
                    // because currentContact will return the updated contact from viewModel.contacts
                    print("✅ ContactDetailsView: Contact updated successfully, view will refresh")
                }
            }
            .sheet(isPresented: $showingExport) {
                NewDeviceContactView(contact: currentContact)
            }
            .alert("Contacts Permission Required", isPresented: $showingExportError, actions: {
                Button("OK", role: .cancel) {}
            }, message: {
                Text(exportError ?? "Please enable Contacts access in Settings to export.")
            })
            .confirmationDialog(
                (currentContact.commentsFromLead != nil && !currentContact.commentsFromLead!.isEmpty) || currentContact.source == "converted"
                    ? "Revert to Lead"
                    : "Delete Contact",
                isPresented: $showingDeleteConfirmation,
                titleVisibility: .visible
            ) {
                Button(
                    (currentContact.commentsFromLead != nil && !currentContact.commentsFromLead!.isEmpty) || currentContact.source == "converted"
                        ? "Revert to Lead"
                        : "Delete",
                    role: .destructive
                ) {
                    deleteContact()
                }
                Button("Cancel", role: .cancel) {}
            } message: {
                Text(
                    (currentContact.commentsFromLead != nil && !currentContact.commentsFromLead!.isEmpty) || currentContact.source == "converted"
                        ? "This contact will be reverted back to a lead. You can convert it again later."
                        : "This contact will be permanently deleted. This action cannot be undone."
                )
            }
        }
    }
    
    // MARK: - Computed Properties
    
    private var isConvertedFromLead: Bool {
        (currentContact.commentsFromLead != nil && !currentContact.commentsFromLead!.isEmpty) || 
        currentContact.source == "converted"
    }
    
    // MARK: - View Sections
    
    private var statusSection: some View {
        Section {
            HStack {
                Image(systemName: "person.crop.circle.badge.plus")
                    .foregroundColor(.blue)
                Text("Converted from Lead")
                    .foregroundColor(.blue)
            }
        }
    }
    
    private func requestContactsPermissionAndExport() {
        let store = CNContactStore()
        let status = CNContactStore.authorizationStatus(for: .contacts)
        switch status {
        case .authorized:
            showingExport = true
        case .notDetermined:
            store.requestAccess(for: .contacts) { granted, error in
                DispatchQueue.main.async {
                    if granted {
                        showingExport = true
                    } else {
                        exportError = error?.localizedDescription ?? "ShareMyCard needs Contacts access to export."
                        showingExportError = true
                    }
                }
            }
        case .denied, .restricted:
            exportError = "ShareMyCard needs Contacts access to export. You can enable it in Settings."
            showingExportError = true
        @unknown default:
            exportError = nil
            showingExportError = true
        }
    }
    
    private func deleteContact() {
        isDeleting = true
        viewModel.deleteContact(currentContact)
        
        // Wait a moment for the deletion to process, then dismiss
        Task {
            try? await Task.sleep(nanoseconds: 500_000_000) // 0.5 seconds
            await MainActor.run {
                isDeleting = false
                dismiss()
            }
        }
    }
}
