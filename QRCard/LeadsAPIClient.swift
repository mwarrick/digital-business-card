//
//  LeadsAPIClient.swift
//  ShareMyCard
//
//  API client for leads management
//

import Foundation

// MARK: - Leads API Client
class LeadsAPIClient {
    private let apiClient: APIClient
    
    init(apiClient: APIClient = APIClient.shared) {
        self.apiClient = apiClient
    }
    
    // MARK: - Fetch Leads
    func fetchLeads() async throws -> [Lead] {
        print("🌐 LeadsAPIClient: Fetching leads from \(APIConfig.Endpoints.leads)")
        
        do {
            let response: APIResponse<[Lead]> = try await apiClient.request(
                endpoint: APIConfig.Endpoints.leads,
                method: "GET"
            )
            print("📡 LeadsAPIClient: Response success: \(response.success)")
            print("📡 LeadsAPIClient: Response message: \(response.messageValue)")
            print("📡 LeadsAPIClient: Data count: \(response.data?.count ?? 0)")
            
            if let data = response.data {
                print("📡 LeadsAPIClient: Successfully decoded \(data.count) leads")
                // Log first lead if available for debugging
                if let firstLead = data.first {
                    print("📡 LeadsAPIClient: First lead - ID: \(firstLead.id), Name: \(firstLead.displayName)")
                }
                return data
            } else {
                print("⚠️ LeadsAPIClient: Response data is nil, returning empty array")
                return []
            }
        } catch {
            // Check if this is a cancellation error (code -999)
            // Cancellation errors are not real errors and should be handled silently
            if let apiError = error as? APIError,
               case .networkError(let underlyingError) = apiError,
               let urlError = underlyingError as? URLError,
               urlError.code == .cancelled {
                // Silently re-throw cancellation errors - they're expected behavior
                throw error
            }
            
            print("❌ LeadsAPIClient: Error fetching leads: \(error)")
            print("❌ LeadsAPIClient: Error type: \(type(of: error))")
            print("❌ LeadsAPIClient: Error description: \(error.localizedDescription)")
            throw error
        }
    }
    
    // MARK: - Get Lead by ID
    func getLead(id: String) async throws -> Lead {
        print("🔍 LeadsAPIClient: Getting lead with ID: \(id)")
        
        // Use the leads/get.php endpoint
        let response: APIResponse<Lead> = try await apiClient.request(
            endpoint: "\(APIConfig.Endpoints.leads)get.php?id=\(id)",
            method: "GET"
        )
        
        guard let data = response.data else {
            throw APIError.serverError("No data returned from server")
        }
        return data
    }
    
    // Note: This method is available but not currently used in the view
    
    // MARK: - Convert Lead to Contact
    func convertLeadToContact(leadId: String) async throws -> String {
        print("🔄 LeadsAPIClient: Converting lead \(leadId) to contact")
        
        let body: [String: Any] = ["lead_id": leadId]
        
        let response: APIResponse<LeadConvertResponse> = try await apiClient.request(
            endpoint: APIConfig.Endpoints.convertLead,
            method: "POST",
            body: body
        )
        
        guard let data = response.data else {
            throw APIError.serverError("No data returned from server")
        }
        
        print("✅ LeadsAPIClient: Lead converted to contact with ID: \(data.contactId)")
        return data.contactId
    }
}

// MARK: - Lead Convert Response
struct LeadConvertResponse: Codable {
    let contactId: String
    
    enum CodingKeys: String, CodingKey {
        case contactId = "contact_id"
    }
    
    init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)
        
        // Handle contactId as either String or Int
        if let idString = try? container.decode(String.self, forKey: .contactId) {
            contactId = idString
        } else if let idInt = try? container.decode(Int.self, forKey: .contactId) {
            contactId = String(idInt)
        } else {
            throw DecodingError.typeMismatch(String.self, DecodingError.Context(codingPath: [CodingKeys.contactId], debugDescription: "Expected String or Int for contact_id"))
        }
    }
}

