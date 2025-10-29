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
    
    // Get updated contact from viewModel if available, otherwise use the initial contact
    private var currentContact: Contact {
        viewModel.contacts.first { $0.id == contact.id } ?? contact
    }
    
    var body: some View {
        NavigationView {
            Form {
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
                    
                    HStack {
                        Text("Email")
                        Spacer()
                        Text(currentContact.email ?? "Not provided")
                            .foregroundColor(.secondary)
                    }
                    
                    HStack {
                        Text("Work Phone")
                        Spacer()
                        Text(currentContact.phone ?? "Not provided")
                            .foregroundColor(.secondary)
                    }
                    
                    HStack {
                        Text("Mobile Phone")
                        Spacer()
                        Text(currentContact.mobilePhone ?? "Not provided")
                            .foregroundColor(.secondary)
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
                    HStack {
                        Text("Website")
                        Spacer()
                        Text(currentContact.website ?? "Not provided")
                            .foregroundColor(.secondary)
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
}
