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
        print("🔄 ContactsAPIClient: Updating contact with ID: \(id)")
        
        // Encode ContactCreateData to dictionary and add the ID
        // ContactCreateData has custom CodingKeys, so encoding will use those
        let encoder = JSONEncoder()
        let data = try encoder.encode(contactData)
        var bodyDict = try JSONSerialization.jsonObject(with: data) as! [String: Any]
        
        // Add the ID to the body (server expects it in the JSON body for PUT requests)
        bodyDict["id"] = id
        
        print("📦 ContactsAPIClient: Request body: \(bodyDict)")
        
        // First, try decoding as a Contact (ideal path)
        do {
            let response: APIResponse<Contact> = try await apiClient.request(
                endpoint: APIConfig.Endpoints.contacts, // Use base endpoint, ID is in body
                method: "PUT",
                body: bodyDict
            )
            print("📥 ContactsAPIClient: Response received - success: \(response.success), message: \(response.message)")
            if let data = response.data {
                print("✅ ContactsAPIClient: Contact updated successfully with data")
                return data
            }
            print("⚠️ ContactsAPIClient: No data in response (Contact decode), will fetch updated contact")
        } catch {
            // If decoding as Contact fails (e.g., server returns array or no data), try a lenient decode
            print("⚠️ ContactsAPIClient: Contact decode failed, retrying as EmptyResponse. Error: \(error)")
            let _: APIResponse<EmptyResponse> = try await apiClient.request(
                endpoint: APIConfig.Endpoints.contacts,
                method: "PUT",
                body: bodyDict
            )
            print("✅ ContactsAPIClient: PUT acknowledged without data (EmptyResponse)")
        }

        // At this point, the server acknowledged the update; fetch the updated contact
        do {
            let fetched = try await getContact(id: id)
            print("✅ ContactsAPIClient: Successfully fetched updated contact after PUT")
            return fetched
        } catch {
            print("❌ ContactsAPIClient: Failed to fetch updated contact after PUT: \(error)")
            throw APIError.serverError("Update succeeded but failed to fetch updated contact: \(error.localizedDescription)")
        }
    }
    
    // MARK: - Delete Contact
    func deleteContact(id: String) async throws {
        // Use path parameter only: /contacts/{id}
        let endpoint = "\(APIConfig.Endpoints.contacts)\(id)"
        let _: APIResponse<EmptyResponse> = try await apiClient.request(
            endpoint: endpoint,
            method: "DELETE"
        )
    }
    
    // MARK: - Get Contact by ID
    func getContact(id: String) async throws -> Contact {
        print("🔍 ContactsAPIClient: Getting contact with ID: \(id)")
        // Prefer RESTful route that works with server routing: /contacts/{id}
        let response: APIResponse<Contact> = try await apiClient.request(
            endpoint: "\(APIConfig.Endpoints.contacts)\(id)",
            method: "GET"
        )
        print("📥 ContactsAPIClient: Get contact response - success: \(response.success), message: \(response.message)")
        guard let data = response.data else {
            print("❌ ContactsAPIClient: No data in get contact response")
            throw APIError.serverError("No data returned from server")
        }
        print("✅ ContactsAPIClient: Successfully got contact: \(data.displayName)")
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