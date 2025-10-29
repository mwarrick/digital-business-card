//
//  ContactsViewModel.swift
//  ShareMyCard
//
//  View model for contact management
//

import Foundation
import SwiftUI
import Combine

@MainActor
class ContactsViewModel: ObservableObject {
    @Published var contacts: [Contact] = []
    @Published var isLoading = false
    @Published var errorMessage: String?
    @Published var searchText = ""
    @Published var selectedContact: Contact?
    @Published var showingContactForm = false
    @Published var showingContactDetails = false
    
    private let dataManager = DataManager.shared
    private let apiClient = ContactsAPIClient()
    private var cancellables = Set<AnyCancellable>()
    
    init() {
        loadContacts()

        // Listen for sync completion to refresh from local storage
        NotificationCenter.default.addObserver(forName: Notification.Name("ContactsUpdated"), object: nil, queue: .main) { [weak self] _ in
            guard let self = self else { return }
            Task { await self.loadLocalContacts() }
        }
    }
    
    // MARK: - Data Loading
    
    func loadContacts() {
        isLoading = true
        errorMessage = nil
        
        Task {
            do {
                print("🔄 ContactsViewModel: Starting API fetch...")
                // First try to load from API
                let apiContacts = try await apiClient.fetchContacts()
                print("📡 ContactsViewModel: API returned \(apiContacts.count) contacts")
                
                // Update local storage
                await updateLocalContacts(apiContacts)
                print("💾 ContactsViewModel: Updated local storage")
                
                // Load from local storage for display
                await loadLocalContacts()
                print("📱 ContactsViewModel: Loaded \(contacts.count) contacts for display")
                
            } catch {
                print("❌ ContactsViewModel: API error: \(error)")
                // If API fails, load from local storage
                await loadLocalContacts()
                errorMessage = "Failed to sync with server: \(error.localizedDescription)"
                print("📱 ContactsViewModel: Loaded \(contacts.count) contacts from local storage")
            }
            
            isLoading = false
        }
    }
    
    /// Force refresh contacts from server (bypasses local cache)
    func refreshFromServer() {
        isLoading = true
        errorMessage = nil
        
        Task {
            do {
                print("🔄 ContactsViewModel: Force refreshing from server...")
                // Fetch from API
                let apiContacts = try await apiClient.fetchContacts()
                print("📡 ContactsViewModel: Server returned \(apiContacts.count) contacts")
                
                // Clear local storage completely
                let existingEntities = dataManager.fetchContacts()
                for entity in existingEntities {
                    dataManager.deleteContact(entity)
                }
                
                // Add server contacts
                for contact in apiContacts {
                    _ = dataManager.createContact(from: contact)
                }
                
                // Load from local storage for display
                await loadLocalContacts()
                print("📱 ContactsViewModel: Refreshed with \(contacts.count) contacts from server")
                
            } catch {
                print("❌ ContactsViewModel: Refresh error: \(error)")
                errorMessage = "Failed to refresh from server: \(error.localizedDescription)"
            }
            
            isLoading = false
        }
    }
    
    private func loadLocalContacts() async {
        let entities = dataManager.fetchContacts()
        contacts = entities.map { $0.toContact() }
    }
    
    
    private func updateLocalContacts(_ apiContacts: [Contact]) async {
        // Clear existing contacts
        let existingEntities = dataManager.fetchContacts()
        for entity in existingEntities {
            dataManager.deleteContact(entity)
        }
        
        // Add new contacts
        for contact in apiContacts {
            _ = dataManager.createContact(from: contact)
        }
    }
    
    // MARK: - Contact Management
    
    func createContact(_ contactData: ContactCreateData) async throws -> Contact {
        isLoading = true
        errorMessage = nil
        
        do {
            let newContact = try await apiClient.createContact(contactData)
            
            // Add to local storage
            _ = dataManager.createContact(from: newContact)
            
            // Refresh local contacts
            await loadLocalContacts()
            
            showingContactForm = false
            isLoading = false
            
            // Trigger sync to ensure data consistency
            Task {
                do {
                    try await SyncManager.shared.performFullSync()
                } catch {
                    print("⚠️ Sync after contact creation failed: \(error)")
                }
            }
            
            return newContact
            
        } catch {
            errorMessage = "Failed to create contact: \(error.localizedDescription)"
            isLoading = false
            throw error
        }
    }
    
    /// Update a contact on the server, then fetch the latest copy and update local storage.
    func updateContactAndReload(_ contact: Contact, with contactData: ContactCreateData) async throws -> Contact {
        isLoading = true
        errorMessage = nil
        do {
            // Update on server
            let _ = try await apiClient.updateContact(id: contact.id, contactData: contactData)
            // Fetch latest from server
            let latest = try await apiClient.getContact(id: contact.id)
            // Persist to local storage
            if let entity = dataManager.fetchContact(by: contact.id) {
                dataManager.updateContact(entity, with: latest)
            } else {
                _ = dataManager.createContact(from: latest)
            }
            // Refresh local view data
            await loadLocalContacts()
            isLoading = false
            return latest
        } catch {
            errorMessage = "Failed to update contact: \(error.localizedDescription)"
            isLoading = false
            throw error
        }
    }

    func updateContact(_ contact: Contact, with contactData: ContactCreateData) {
        isLoading = true
        errorMessage = nil
        
        Task {
            do {
                let updatedContact = try await apiClient.updateContact(id: contact.id, contactData: contactData)
                
                // Update local storage
                if let entity = dataManager.fetchContact(by: contact.id) {
                    dataManager.updateContact(entity, with: updatedContact)
                }
                
                // Refresh local contacts
                await loadLocalContacts()
                
                showingContactForm = false
                selectedContact = nil
                
                // Trigger sync to ensure data consistency
                Task {
                    do {
                        try await SyncManager.shared.performFullSync()
                    } catch {
                        print("⚠️ Sync after contact update failed: \(error)")
                    }
                }
                
            } catch {
                errorMessage = "Failed to update contact: \(error.localizedDescription)"
            }
            
            isLoading = false
        }
    }
    
    func deleteContact(_ contact: Contact) {
        // Update UI state first
        selectedContact = nil
        showingContactDetails = false
        
        // Make API call first to delete from server
        Task {
            do {
                try await apiClient.deleteContact(id: contact.id)
                print("✅ Contact deleted successfully from server")
                
                // Only remove from local storage after successful server deletion
                await MainActor.run {
                    contacts.removeAll { $0.id == contact.id }
                    if let entity = dataManager.fetchContact(by: contact.id) {
                        dataManager.deleteContact(entity)
                    }
                }
                
                // Trigger sync to ensure data consistency
                Task {
                    do {
                        try await SyncManager.shared.performFullSync()
                    } catch {
                        print("⚠️ Sync after contact deletion failed: \(error)")
                    }
                }
                
            } catch {
                print("❌ Failed to delete contact from server: \(error)")
                // If server deletion fails, show error but don't remove from UI
                await MainActor.run {
                    errorMessage = "Failed to delete contact: \(error.localizedDescription)"
                }
            }
        }
    }
    
    // MARK: - Search and Filtering
    
    var filteredContacts: [Contact] {
        if searchText.isEmpty {
            return contacts
        }
        
        return contacts.filter { contact in
            contact.fullName.localizedCaseInsensitiveContains(searchText) ||
            contact.email?.localizedCaseInsensitiveContains(searchText) == true ||
            contact.company?.localizedCaseInsensitiveContains(searchText) == true
        }
    }
    
    // MARK: - UI Actions
    
    func selectContact(_ contact: Contact) {
        selectedContact = contact
        showingContactDetails = true
    }
    
    func showContactForm(for contact: Contact? = nil) {
        selectedContact = contact
        showingContactForm = true
    }
    
    func hideContactForm() {
        showingContactForm = false
        selectedContact = nil
    }
    
    func hideContactDetails() {
        showingContactDetails = false
        selectedContact = nil
    }
    
    // MARK: - Statistics
    
    var totalContacts: Int {
        contacts.count
    }
    
    var qrScannedContacts: Int {
        contacts.filter { $0.source == "qr_scan" }.count
    }
    
    var manualContacts: Int {
        contacts.filter { $0.source != "qr_scan" }.count
    }
}