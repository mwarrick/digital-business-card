//
//  LeadsViewModel.swift
//  ShareMyCard
//
//  ViewModel for managing leads
//

import Foundation
import Combine

class LeadsViewModel: ObservableObject {
    @Published var leads: [Lead] = []
    @Published var isLoading = false
    @Published var errorMessage: String?
    @Published var searchText = ""
    
    private let leadsAPIClient = LeadsAPIClient()
    private let dataManager = DataManager.shared
    
    init() {
        // Listen for leads updates from sync
        NotificationCenter.default.addObserver(
            forName: Notification.Name("LeadsUpdated"),
            object: nil,
            queue: .main
        ) { [weak self] _ in
            Task { @MainActor in
                await self?.loadLocalLeads()
            }
        }
    }
    
    deinit {
        NotificationCenter.default.removeObserver(self)
    }
    
    // MARK: - Data Loading
    
    func loadLeads() {
        isLoading = true
        errorMessage = nil
        
        Task {
            // First load from local storage for immediate display
            await loadLocalLeads()
            
            // Then sync from server
            do {
                print("🔄 LeadsViewModel: Starting API sync...")
                try await syncFromServer()
                print("✅ LeadsViewModel: Sync complete")
                
                // Reload from local storage after sync
                await loadLocalLeads()
            } catch {
                print("❌ LeadsViewModel: Sync error: \(error)")
                // Keep local data if sync fails
                await MainActor.run {
                    errorMessage = "Failed to sync with server: \(error.localizedDescription)"
                    isLoading = false
                }
            }
        }
    }
    
    /// Load leads from local Core Data storage
    private func loadLocalLeads() async {
        await MainActor.run {
            let leadEntities = dataManager.fetchLeads()
            leads = leadEntities.map { $0.toLead() }
            isLoading = false
            print("📱 LeadsViewModel: Loaded \(leads.count) leads from local storage")
        }
    }
    
    /// Sync leads from server to local storage
    private func syncFromServer() async throws {
        print("📡 LeadsViewModel: Fetching leads from server...")
        let fetchedLeads = try await leadsAPIClient.fetchLeads()
        print("📦 LeadsViewModel: Received \(fetchedLeads.count) leads from server")
        
        // Clear existing local leads
        let existingEntities = dataManager.fetchLeads()
        for entity in existingEntities {
            dataManager.deleteLead(entity)
        }
        
        // Add server leads to local storage
        for lead in fetchedLeads {
            _ = dataManager.createLead(from: lead)
        }
        
        print("💾 LeadsViewModel: Updated local storage with \(fetchedLeads.count) leads")
    }
    
    /// Force refresh from server (for pull-to-refresh)
    func refreshFromServer() async {
        await MainActor.run {
            isLoading = true
            errorMessage = nil
        }
        
        do {
            try await syncFromServer()
            await loadLocalLeads()
        } catch {
            print("❌ LeadsViewModel: Error refreshing leads: \(error)")
            await MainActor.run {
                errorMessage = error.localizedDescription
                isLoading = false
            }
        }
    }
    
    func convertLeadToContact(leadId: String) async throws -> String {
        let contactId = try await leadsAPIClient.convertLeadToContact(leadId: leadId)
        
        // Update local lead status after conversion
        if let leadEntity = dataManager.fetchLead(by: leadId) {
            var updatedLead = leadEntity.toLead()
            // Update status to converted - we'll need to refetch from server
            // For now, just remove from local list
            dataManager.deleteLead(leadEntity)
            
            // Reload to get updated data
            try await syncFromServer()
            await loadLocalLeads()
        }
        
        return contactId
    }
    
    // MARK: - Search and Filtering
    
    var filteredLeads: [Lead] {
        if searchText.isEmpty {
            return leads
        }
        
        return leads.filter { lead in
            lead.displayName.localizedCaseInsensitiveContains(searchText) ||
            lead.emailPrimary?.localizedCaseInsensitiveContains(searchText) == true ||
            lead.organizationName?.localizedCaseInsensitiveContains(searchText) == true ||
            lead.workPhone?.localizedCaseInsensitiveContains(searchText) == true ||
            lead.mobilePhone?.localizedCaseInsensitiveContains(searchText) == true ||
            lead.cardDisplayName.localizedCaseInsensitiveContains(searchText)
        }
    }
}

