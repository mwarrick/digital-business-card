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
    private var isRefreshing = false
    
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
            
            // Then sync from server with retry on cancellation
            await syncWithRetry()
        }
    }
    
    /// Sync from server with automatic retry on cancellation
    private func syncWithRetry(maxRetries: Int = 1) async {
        var retryCount = 0
        
        while retryCount <= maxRetries {
            do {
                print("🔄 LeadsViewModel: Starting API sync (attempt \(retryCount + 1))...")
                try await syncFromServer()
                print("✅ LeadsViewModel: Sync complete")
                
                // Reload from local storage after sync
                await loadLocalLeads()
                await MainActor.run {
                    isLoading = false
                }
                return // Success - exit
            } catch {
                // Check if this is a cancellation error (code -999)
                if let apiError = error as? APIError,
                   case .networkError(let underlyingError) = apiError,
                   let urlError = underlyingError as? URLError,
                   urlError.code == .cancelled {
                    print("ℹ️ LeadsViewModel: Request was cancelled (attempt \(retryCount + 1))")
                    
                    // Retry if we haven't exceeded max retries
                    if retryCount < maxRetries {
                        retryCount += 1
                        print("🔄 LeadsViewModel: Retrying after cancellation...")
                        // Small delay before retry
                        try? await Task.sleep(nanoseconds: 500_000_000) // 0.5 seconds
                        continue
                    } else {
                        // Max retries reached, just use local data
                        print("ℹ️ LeadsViewModel: Max retries reached, using local data")
                        await MainActor.run {
                            isLoading = false
                        }
                        return
                    }
                }
                
                // Real error (not cancellation)
                print("❌ LeadsViewModel: Sync error: \(error)")
                await MainActor.run {
                    errorMessage = "Failed to sync with server: \(error.localizedDescription)"
                    isLoading = false
                }
                return
            }
        }
    }
    
    /// Load leads from local Core Data storage
    private func loadLocalLeads() async {
        await MainActor.run {
            let leadEntities = dataManager.fetchLeads()
            var loadedLeads = leadEntities.map { $0.toLead() }
            
            // Sort by most recent first (createdAt date descending)
            // This ensures proper sorting even if CoreData sort doesn't work
            loadedLeads.sort { lead1, lead2 in
                let date1 = lead1.createdAtDate ?? Date.distantPast
                let date2 = lead2.createdAtDate ?? Date.distantPast
                return date1 > date2
            }
            
            leads = loadedLeads
            
            // Debug: Log dates to verify sorting
            if !leads.isEmpty {
                print("📅 LeadsViewModel: Lead dates (first 3):")
                for (index, lead) in leads.prefix(3).enumerated() {
                    let dateStr = lead.createdAtDate?.description ?? "nil"
                    print("   \(index + 1). \(lead.displayName): \(dateStr)")
                }
            }
            
            isLoading = false
            print("📱 LeadsViewModel: Loaded \(leads.count) leads from local storage (sorted by date)")
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
        // Prevent multiple simultaneous refreshes
        let shouldProceed = await MainActor.run {
            if isRefreshing {
                print("ℹ️ LeadsViewModel: Refresh already in progress, skipping")
                return false
            }
            isRefreshing = true
            isLoading = true
            errorMessage = nil
            return true
        }
        
        guard shouldProceed else {
            // Refresh already in progress, just complete normally
            return
        }
        
        do {
            try await syncFromServer()
            await loadLocalLeads()
            await MainActor.run {
                isLoading = false
                isRefreshing = false
            }
        } catch {
            // Always reset the flag, even on error
            await MainActor.run {
                isRefreshing = false
            }
            
            // Check if this is a cancellation error (code -999)
            // Cancellation errors are not real errors and should be ignored
            if let apiError = error as? APIError,
               case .networkError(let underlyingError) = apiError,
               let urlError = underlyingError as? URLError,
               urlError.code == .cancelled {
                print("ℹ️ LeadsViewModel: Request was cancelled (this is normal)")
                await MainActor.run {
                    isLoading = false
                    // Don't set errorMessage for cancellations
                }
                // Return normally so refresh control completes
                return
            }
            
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
        var result: [Lead]
        
        if searchText.isEmpty {
            result = leads
        } else {
            result = leads.filter { lead in
                lead.displayName.localizedCaseInsensitiveContains(searchText) ||
                lead.emailPrimary?.localizedCaseInsensitiveContains(searchText) == true ||
                lead.organizationName?.localizedCaseInsensitiveContains(searchText) == true ||
                lead.workPhone?.localizedCaseInsensitiveContains(searchText) == true ||
                lead.mobilePhone?.localizedCaseInsensitiveContains(searchText) == true ||
                lead.cardDisplayName.localizedCaseInsensitiveContains(searchText)
            }
        }
        
        // Sort by most recent first (createdAt date descending)
        return result.sorted { lead1, lead2 in
            let date1 = lead1.createdAtDate ?? Date.distantPast
            let date2 = lead2.createdAtDate ?? Date.distantPast
            return date1 > date2
        }
    }
}

