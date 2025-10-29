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
                MinimalQRScannerView(viewModel: viewModel)
            }
            .sheet(item: $viewModel.selectedContact) { contact in
                ContactDetailsView(contact: contact, viewModel: viewModel)
            }
            .refreshable {
                viewModel.refreshFromServer()
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



#Preview {
    ContactsDashboardView()
}