//
//  ContactsAPIClient.swift
//  ShareMyCard
//
//  API client for contact management
//

import Foundation
import Combine

// MARK: - Contacts API Client
class ContactsAPIClient: ObservableObject {
    private let apiClient: APIClient
    
    init(apiClient: APIClient = APIClient.shared) {
        self.apiClient = apiClient
    }
    
    // MARK: - Fetch Contacts
    func fetchContacts() async throws -> [Contact] {
        print("🌐 ContactsAPIClient: Fetching contacts from \(APIConfig.Endpoints.contacts)")
        
        do {
            let response: APIResponse<[Contact]> = try await apiClient.request(
                endpoint: APIConfig.Endpoints.contacts,
                method: "GET"
            )
            print("📡 ContactsAPIClient: Response success: \(response.success)")
            print("📡 ContactsAPIClient: Response message: \(response.message)")
            print("📡 ContactsAPIClient: Data count: \(response.data?.count ?? 0)")
            return response.data ?? []
        } catch {
            print("❌ ContactsAPIClient: Error fetching contacts: \(error)")
            // If there's an error, return empty array instead of throwing
            // This prevents the sync from failing completely
            return []
        }
    }
    
    // MARK: - Create Contact
    func createContact(_ contactData: ContactCreateData) async throws -> Contact {
        let response: APIResponse<Contact> = try await apiClient.request(
            endpoint: APIConfig.Endpoints.contacts,
            method: "POST",
            body: contactData
        )
        guard let data = response.data else {
            throw APIError.serverError("No data returned from server")
        }
        return data
    }
    
    // MARK: - Update Contact
    func updateContact(id: String, contactData: ContactCreateData) async throws -> Contact {
        let response: APIResponse<Contact> = try await apiClient.request(
            endpoint: "\(APIConfig.Endpoints.contacts)\(id)",
            method: "PUT",
            body: contactData
        )
        guard let data = response.data else {
            throw APIError.serverError("No data returned from server")
        }
        return data
    }
    
    // MARK: - Delete Contact
    func deleteContact(id: String) async throws {
        let _: APIResponse<EmptyResponse> = try await apiClient.request(
            endpoint: "\(APIConfig.Endpoints.contacts)\(id)",
            method: "DELETE"
        )
    }
    
    // MARK: - Get Contact by ID
    func getContact(id: String) async throws -> Contact {
        let response: APIResponse<Contact> = try await apiClient.request(
            endpoint: "\(APIConfig.Endpoints.contacts)\(id)",
            method: "GET"
        )
        guard let data = response.data else {
            throw APIError.serverError("No data returned from server")
        }
        return data
    }
    
    // MARK: - Search Contacts
    func searchContacts(query: String) async throws -> [Contact] {
        let response: APIResponse<[Contact]> = try await apiClient.request(
            endpoint: "\(APIConfig.Endpoints.contacts)search?q=\(query.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? "")",
            method: "GET"
        )
        return response.data ?? []
    }
}

// MARK: - Empty Response for DELETE operations
// Note: EmptyResponse is defined in APIClient.swift