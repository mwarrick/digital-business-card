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
    
    func createContact(_ contactData: ContactCreateData) async throws {
        isLoading = true
        errorMessage = nil
        
        do {
            let newContact = try await apiClient.createContact(contactData)
            
            // Add to local storage
            _ = dataManager.createContact(from: newContact)
            
            // Refresh local contacts
            await loadLocalContacts()
            
            showingContactForm = false
            
        } catch {
            errorMessage = "Failed to create contact: \(error.localizedDescription)"
            throw error
        }
        
        isLoading = false
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
                
            } catch {
                errorMessage = "Failed to update contact: \(error.localizedDescription)"
            }
            
            isLoading = false
        }
    }
    
    func deleteContact(_ contact: Contact) {
        isLoading = true
        errorMessage = nil
        
        Task {
            do {
                try await apiClient.deleteContact(id: contact.id)
                
                // Remove from local storage
                if let entity = dataManager.fetchContact(by: contact.id) {
                    dataManager.deleteContact(entity)
                }
                
                // Refresh local contacts
                await loadLocalContacts()
                
                selectedContact = nil
                showingContactDetails = false
                
            } catch {
                errorMessage = "Failed to delete contact: \(error.localizedDescription)"
            }
            
            isLoading = false
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