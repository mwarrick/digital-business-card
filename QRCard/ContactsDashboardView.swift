//
//  ContactsDashboardView.swift
//  ShareMyCard
//
//  Contacts dashboard view
//

import SwiftUI

struct ContactsDashboardView: View {
    @StateObject private var viewModel = ContactsViewModel()
    @State private var showingAddContact = false
    @State private var showingQRScanner = false
    
    var body: some View {
        NavigationView {
            VStack(spacing: 0) {
                // Statistics Header
                statisticsHeader
                
                // Search Bar
                searchBar
                
                // Contacts List
                contactsList
            }
            .navigationTitle("Contacts")
            .toolbar {
                ToolbarItem(placement: .navigationBarTrailing) {
                    Menu {
                        Button(action: {
                            showingAddContact = true
                        }) {
                            Label("Add Contact", systemImage: "person.badge.plus")
                        }
                        
                        Button(action: {
                            showingQRScanner = true
                        }) {
                            Label("Scan QR Code", systemImage: "qrcode.viewfinder")
                        }
                    } label: {
                        Image(systemName: "plus")
                    }
                }
            }
            .sheet(isPresented: $showingAddContact) {
                AddContactView(viewModel: viewModel)
            }
            .sheet(isPresented: $showingQRScanner) {
                QRScannerView(viewModel: viewModel)
            }
            .sheet(item: $viewModel.selectedContact) { contact in
                ContactDetailsView(contact: contact, viewModel: viewModel)
            }
            .refreshable {
                viewModel.loadContacts()
            }
        }
    }
    
    // MARK: - Statistics Header
    
    private var statisticsHeader: some View {
        VStack(spacing: 12) {
            HStack {
                StatCard(
                    title: "Total",
                    value: "\(viewModel.totalContacts)",
                    icon: "person.2.fill",
                    color: .blue
                )
                
                StatCard(
                    title: "QR Scanned",
                    value: "\(viewModel.qrScannedContacts)",
                    icon: "qrcode.viewfinder",
                    color: .green
                )
                
                StatCard(
                    title: "Manual",
                    value: "\(viewModel.manualContacts)",
                    icon: "person.badge.plus",
                    color: .orange
                )
            }
            .padding(.horizontal)
        }
        .padding(.vertical)
        .background(Color(.systemGroupedBackground))
    }
    
    // MARK: - Search Bar
    
    private var searchBar: some View {
        HStack {
            Image(systemName: "magnifyingglass")
                .foregroundColor(.secondary)
            
            TextField("Search contacts...", text: $viewModel.searchText)
                .textFieldStyle(PlainTextFieldStyle())
        }
        .padding()
        .background(Color(.systemBackground))
        .cornerRadius(10)
        .padding(.horizontal)
    }
    
    // MARK: - Contacts List
    
    private var contactsList: some View {
        Group {
            if viewModel.isLoading {
                ProgressView("Loading contacts...")
                    .frame(maxWidth: .infinity, maxHeight: .infinity)
            } else if viewModel.filteredContacts.isEmpty {
                emptyState
            } else {
                List {
                    ForEach(viewModel.filteredContacts) { contact in
                        ContactRowView(contact: contact) {
                            viewModel.selectContact(contact)
                        }
                    }
                    .onDelete(perform: deleteContacts)
                }
                .listStyle(PlainListStyle())
            }
        }
    }
    
    // MARK: - Empty State
    
    private var emptyState: some View {
        VStack(spacing: 20) {
            Image(systemName: "person.2.slash")
                .font(.system(size: 60))
                .foregroundColor(.secondary)
            
            Text("No Contacts")
                .font(.title2)
                .fontWeight(.semibold)
            
            Text("Add your first contact to get started")
                .font(.subheadline)
                .foregroundColor(.secondary)
                .multilineTextAlignment(.center)
            
            Button("Add Contact") {
                showingAddContact = true
            }
            .buttonStyle(.borderedProminent)
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity)
        .padding()
    }
    
    // MARK: - Actions
    
    private func deleteContacts(offsets: IndexSet) {
        for index in offsets {
            let contact = viewModel.filteredContacts[index]
            viewModel.deleteContact(contact)
        }
    }
}

// MARK: - Stat Card

struct StatCard: View {
    let title: String
    let value: String
    let icon: String
    let color: Color
    
    var body: some View {
        VStack(spacing: 8) {
            Image(systemName: icon)
                .font(.title2)
                .foregroundColor(color)
            
            Text(value)
                .font(.title2)
                .fontWeight(.bold)
            
            Text(title)
                .font(.caption)
                .foregroundColor(.secondary)
        }
        .frame(maxWidth: .infinity)
        .padding()
        .background(Color(.systemBackground))
        .cornerRadius(12)
    }
}

// MARK: - Contact Row View

struct ContactRowView: View {
    let contact: Contact
    let onTap: () -> Void
    
    var body: some View {
        Button(action: onTap) {
            HStack {
                // Avatar
                Circle()
                    .fill(Color.blue.gradient)
                    .frame(width: 50, height: 50)
                    .overlay {
                        Text(contact.displayName.prefix(1).uppercased())
                            .font(.title2)
                            .fontWeight(.semibold)
                            .foregroundColor(.white)
                    }
                
                VStack(alignment: .leading, spacing: 4) {
                    Text(contact.displayName)
                        .font(.headline)
                        .foregroundColor(.primary)
                    
                    if let company = contact.company {
                        Text(company)
                            .font(.subheadline)
                            .foregroundColor(.secondary)
                    }
                    
                    if let email = contact.email {
                        Text(email)
                            .font(.caption)
                            .foregroundColor(.secondary)
                    }
                }
                
                Spacer()
                
                // Source badge
                if contact.source == "qr_scan" {
                    Image(systemName: "qrcode")
                        .foregroundColor(.green)
                        .font(.caption)
                }
            }
            .padding(.vertical, 4)
        }
        .buttonStyle(PlainButtonStyle())
    }
}

// MARK: - Add Contact View (Placeholder)

struct AddContactView: View {
    @ObservedObject var viewModel: ContactsViewModel
    @Environment(\.dismiss) private var dismiss
    
    var body: some View {
        NavigationView {
            VStack {
                Text("Add Contact Form")
                    .font(.title)
                    .padding()
                
                Text("This will be implemented in the next phase")
                    .foregroundColor(.secondary)
                    .multilineTextAlignment(.center)
                    .padding()
                
                Spacer()
            }
            .navigationTitle("Add Contact")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarLeading) {
                    Button("Cancel") {
                        dismiss()
                    }
                }
            }
        }
    }
}

// MARK: - Contact Details View (Placeholder)

struct ContactDetailsView: View {
    let contact: Contact
    @ObservedObject var viewModel: ContactsViewModel
    @Environment(\.dismiss) private var dismiss
    
    var body: some View {
        NavigationView {
            VStack(alignment: .leading, spacing: 16) {
                Text("Contact Details")
                    .font(.title)
                    .padding()
                
                ScrollView {
                    VStack(alignment: .leading, spacing: 16) {
                        // Basic Info
                        VStack(alignment: .leading, spacing: 8) {
                            Text("Basic Information")
                                .font(.headline)
                                .foregroundColor(.primary)
                            
                            Text("Name: \(contact.displayName)")
                            if let company = contact.company {
                                Text("Company: \(company)")
                            }
                            if let jobTitle = contact.jobTitle {
                                Text("Job Title: \(jobTitle)")
                            }
                            if let email = contact.email {
                                Text("Email: \(email)")
                            }
                            if let phone = contact.phone {
                                Text("Work Phone: \(phone)")
                            }
                            if let mobilePhone = contact.mobilePhone {
                                Text("Mobile Phone: \(mobilePhone)")
                            }
                        }
                        .padding()
                        .background(Color(.systemGray6))
                        .cornerRadius(8)
                        
                        // Address Info
                        if contact.address != nil || contact.city != nil || contact.state != nil || contact.zipCode != nil || contact.country != nil {
                            VStack(alignment: .leading, spacing: 8) {
                                Text("Address")
                                    .font(.headline)
                                    .foregroundColor(.primary)
                                
                                if let address = contact.address {
                                    Text("Street: \(address)")
                                }
                                if let city = contact.city {
                                    Text("City: \(city)")
                                }
                                if let state = contact.state {
                                    Text("State: \(state)")
                                }
                                if let zipCode = contact.zipCode {
                                    Text("ZIP: \(zipCode)")
                                }
                                if let country = contact.country {
                                    Text("Country: \(country)")
                                }
                            }
                            .padding()
                            .background(Color(.systemGray6))
                            .cornerRadius(8)
                        }
                        
                        // Additional Info
                        VStack(alignment: .leading, spacing: 8) {
                            Text("Additional Information")
                                .font(.headline)
                                .foregroundColor(.primary)
                            
                            if let website = contact.website {
                                Text("Website: \(website)")
                            }
                            if let birthdate = contact.birthdate {
                                Text("Birthdate: \(birthdate)")
                            }
                            if let source = contact.source {
                                Text("Source: \(source)")
                            }
                        }
                        .padding()
                        .background(Color(.systemGray6))
                        .cornerRadius(8)
                        
                        // Notes/Comments
                        if let notes = contact.notes, !notes.isEmpty {
                            VStack(alignment: .leading, spacing: 8) {
                                Text("Notes")
                                    .font(.headline)
                                    .foregroundColor(.primary)
                                
                                Text(notes)
                                    .font(.body)
                            }
                            .padding()
                            .background(Color(.systemGray6))
                            .cornerRadius(8)
                        }
                        
                        if let commentsFromLead = contact.commentsFromLead, !commentsFromLead.isEmpty {
                            VStack(alignment: .leading, spacing: 8) {
                                Text("Message from Lead")
                                    .font(.headline)
                                    .foregroundColor(.primary)
                                
                                Text(commentsFromLead)
                                    .font(.body)
                            }
                            .padding()
                            .background(Color(.systemGray6))
                            .cornerRadius(8)
                        }
                    }
                    .padding()
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
            }
        }
    }
}

#Preview {
    ContactsDashboardView()
}