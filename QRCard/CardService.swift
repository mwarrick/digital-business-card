//
//  CardService.swift
//  ShareMyCard
//
//  API service for business card CRUD operations
//

import Foundation

// MARK: - API Models

struct BusinessCardAPI: Codable {
    let id: String?
    let userId: String?
    let firstName: String
    let lastName: String
    let phoneNumber: String
    let companyName: String?
    let jobTitle: String?
    let bio: String?
    let profilePhotoPath: String?
    let companyLogoPath: String?
    let coverGraphicPath: String?
    let theme: String?
    let emails: [EmailContactAPI]
    let phones: [PhoneContactAPI]
    let websites: [WebsiteLinkAPI]
    let address: AddressAPI?
    let isActive: Bool?
    let createdAt: String?
    let updatedAt: String?
    
    enum CodingKeys: String, CodingKey {
        case id
        case userId = "user_id"
        case firstName = "first_name"
        case lastName = "last_name"
        case phoneNumber = "phone_number"
        case companyName = "company_name"
        case jobTitle = "job_title"
        case bio
        case profilePhotoPath = "profile_photo_path"
        case companyLogoPath = "company_logo_path"
        case coverGraphicPath = "cover_graphic_path"
        case theme
        case emails
        case phones
        case websites
        case address
        case isActive = "is_active"
        case createdAt = "created_at"
        case updatedAt = "updated_at"
    }
    
    // Regular initializer for creating instances
    init(
        id: String?,
        userId: String?,
        firstName: String,
        lastName: String,
        phoneNumber: String,
        companyName: String?,
        jobTitle: String?,
        bio: String?,
        profilePhotoPath: String?,
        companyLogoPath: String?,
        coverGraphicPath: String?,
        theme: String?,
        emails: [EmailContactAPI],
        phones: [PhoneContactAPI],
        websites: [WebsiteLinkAPI],
        address: AddressAPI?,
        isActive: Bool?,
        createdAt: String?,
        updatedAt: String?
    ) {
        self.id = id
        self.userId = userId
        self.firstName = firstName
        self.lastName = lastName
        self.phoneNumber = phoneNumber
        self.companyName = companyName
        self.jobTitle = jobTitle
        self.bio = bio
        self.profilePhotoPath = profilePhotoPath
        self.companyLogoPath = companyLogoPath
        self.coverGraphicPath = coverGraphicPath
        self.theme = theme
        self.emails = emails
        self.phones = phones
        self.websites = websites
        self.address = address
        self.isActive = isActive
        self.createdAt = createdAt
        self.updatedAt = updatedAt
    }
    
    // Custom decoder to handle string "1"/"0" as Bool
    init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)
        
        id = try container.decodeIfPresent(String.self, forKey: .id)
        userId = try container.decodeIfPresent(String.self, forKey: .userId)
        firstName = try container.decode(String.self, forKey: .firstName)
        lastName = try container.decode(String.self, forKey: .lastName)
        phoneNumber = try container.decode(String.self, forKey: .phoneNumber)
        companyName = try container.decodeIfPresent(String.self, forKey: .companyName)
        jobTitle = try container.decodeIfPresent(String.self, forKey: .jobTitle)
        bio = try container.decodeIfPresent(String.self, forKey: .bio)
        profilePhotoPath = try container.decodeIfPresent(String.self, forKey: .profilePhotoPath)
        companyLogoPath = try container.decodeIfPresent(String.self, forKey: .companyLogoPath)
        coverGraphicPath = try container.decodeIfPresent(String.self, forKey: .coverGraphicPath)
        theme = try container.decodeIfPresent(String.self, forKey: .theme)
        emails = try container.decode([EmailContactAPI].self, forKey: .emails)
        phones = try container.decode([PhoneContactAPI].self, forKey: .phones)
        websites = try container.decode([WebsiteLinkAPI].self, forKey: .websites)
        address = try container.decodeIfPresent(AddressAPI.self, forKey: .address)
        createdAt = try container.decodeIfPresent(String.self, forKey: .createdAt)
        updatedAt = try container.decodeIfPresent(String.self, forKey: .updatedAt)
        
        // Handle isActive as either Bool or String "1"/"0"
        if let boolValue = try? container.decodeIfPresent(Bool.self, forKey: .isActive) {
            isActive = boolValue
        } else if let stringValue = try? container.decodeIfPresent(String.self, forKey: .isActive) {
            isActive = (stringValue == "1" || stringValue.lowercased() == "true")
        } else {
            isActive = nil
        }
    }
}

struct EmailContactAPI: Codable {
    let id: String?
    let email: String
    let type: String
    let label: String?
    let is_primary: Bool?
    
    // Regular initializer for creating instances programmatically
    init(id: String?, email: String, type: String, label: String?, is_primary: Bool?) {
        self.id = id
        self.email = email
        self.type = type
        self.label = label
        self.is_primary = is_primary
    }
    
    // Custom decoder to handle both Bool and String for is_primary
    init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)
        
        id = try container.decodeIfPresent(String.self, forKey: .id)
        email = try container.decode(String.self, forKey: .email)
        type = try container.decode(String.self, forKey: .type)
        label = try container.decodeIfPresent(String.self, forKey: .label)
        
        // Handle is_primary as either Bool or String
        if let boolValue = try? container.decodeIfPresent(Bool.self, forKey: .is_primary) {
            is_primary = boolValue
        } else if let stringValue = try? container.decodeIfPresent(String.self, forKey: .is_primary) {
            is_primary = (stringValue == "1" || stringValue.lowercased() == "true")
        } else {
            is_primary = nil
        }
    }
    
    // Custom encoder
    func encode(to encoder: Encoder) throws {
        var container = encoder.container(keyedBy: CodingKeys.self)
        try container.encodeIfPresent(id, forKey: .id)
        try container.encode(email, forKey: .email)
        try container.encode(type, forKey: .type)
        try container.encodeIfPresent(label, forKey: .label)
        try container.encodeIfPresent(is_primary, forKey: .is_primary)
    }
    
    enum CodingKeys: String, CodingKey {
        case id, email, type, label, is_primary
    }
}

struct PhoneContactAPI: Codable {
    let id: String?
    let phoneNumber: String
    let type: String
    let label: String?
    
    enum CodingKeys: String, CodingKey {
        case id
        case phoneNumber = "phone_number"
        case type
        case label
    }
}

struct WebsiteLinkAPI: Codable {
    let id: String?
    let url: String
    let name: String?
    let description: String?
    let is_primary: Bool?
    
    // Regular initializer for creating instances programmatically
    init(id: String?, url: String, name: String?, description: String?, is_primary: Bool?) {
        self.id = id
        self.url = url
        self.name = name
        self.description = description
        self.is_primary = is_primary
    }
    
    // Custom decoder to handle both Bool and String for is_primary
    init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)
        
        id = try container.decodeIfPresent(String.self, forKey: .id)
        url = try container.decode(String.self, forKey: .url)
        name = try container.decodeIfPresent(String.self, forKey: .name)
        description = try container.decodeIfPresent(String.self, forKey: .description)
        
        // Handle is_primary as either Bool or String
        if let boolValue = try? container.decodeIfPresent(Bool.self, forKey: .is_primary) {
            is_primary = boolValue
        } else if let stringValue = try? container.decodeIfPresent(String.self, forKey: .is_primary) {
            is_primary = (stringValue == "1" || stringValue.lowercased() == "true")
        } else {
            is_primary = nil
        }
    }
    
    // Custom encoder
    func encode(to encoder: Encoder) throws {
        var container = encoder.container(keyedBy: CodingKeys.self)
        try container.encodeIfPresent(id, forKey: .id)
        try container.encode(url, forKey: .url)
        try container.encodeIfPresent(name, forKey: .name)
        try container.encodeIfPresent(description, forKey: .description)
        try container.encodeIfPresent(is_primary, forKey: .is_primary)
    }
    
    enum CodingKeys: String, CodingKey {
        case id, url, name, description, is_primary
    }
}

struct AddressAPI: Codable {
    let id: String?
    let street: String?
    let city: String?
    let state: String?
    let zipCode: String?
    let country: String?
    
    enum CodingKeys: String, CodingKey {
        case id
        case street
        case city
        case state
        case zipCode = "zip_code"
        case country
    }
}

// MARK: - Card Service

class CardService {
    
    /// Fetch all cards from server
    static func fetchCards() async throws -> [BusinessCardAPI] {
        print("📡 CardService.fetchCards() - Starting request")
        print("   🎯 Endpoint: \(APIConfig.Endpoints.cards)")
        
        let response: APIResponse<[BusinessCardAPI]> = try await APIClient.shared.request(
            endpoint: APIConfig.Endpoints.cards,
            method: "GET",
            requiresAuth: true
        )
        
        print("📦 CardService.fetchCards() - Response received")
        print("   📊 Data count: \(response.data?.count ?? 0)")
        
        return response.data ?? []
    }
    
    /// Create card on server
    static func createCard(_ card: BusinessCardAPI) async throws -> BusinessCardAPI {
        let encoder = JSONEncoder()
        // Note: Not using keyEncodingStrategy because we have explicit CodingKeys
        let data = try encoder.encode(card)
        let body = try JSONSerialization.jsonObject(with: data) as! [String: Any]
        
        let response: APIResponse<BusinessCardAPI> = try await APIClient.shared.request(
            endpoint: APIConfig.Endpoints.cards,
            method: "POST",
            body: body,
            requiresAuth: true
        )
        
        guard let data = response.data else {
            throw APIError.serverError("Failed to create card")
        }
        
        return data
    }
    
    /// Update card on server
    static func updateCard(_ card: BusinessCardAPI) async throws -> BusinessCardAPI {
        guard let cardId = card.id else {
            throw APIError.serverError("Card ID is required for update")
        }
        
        print("🔄 Updating card on server: \(cardId)")
        
        let encoder = JSONEncoder()
        // Note: Not using keyEncodingStrategy because we have explicit CodingKeys
        let data = try encoder.encode(card)
        let body = try JSONSerialization.jsonObject(with: data) as! [String: Any]
        
        print("📤 Update request body keys: \(body.keys.joined(separator: ", "))")
        
        let response: APIResponse<BusinessCardAPI> = try await APIClient.shared.request(
            endpoint: "\(APIConfig.Endpoints.cards)?id=\(cardId)",
            method: "PUT",
            body: body,
            requiresAuth: true
        )
        
        guard let data = response.data else {
            print("❌ Update failed - no data in response")
            print("📥 Response: success=\(response.success), message=\(response.message)")
            throw APIError.serverError(response.message)
        }
        
        print("✅ Card updated successfully")
        return data
    }
    
    /// Delete card from server
    static func deleteCard(id: String) async throws {
        print("🗑️ Deleting card from server with ID: \(id)")
        
        let _: APIResponse<EmptyResponse> = try await APIClient.shared.request(
            endpoint: "\(APIConfig.Endpoints.cards)?id=\(id)",
            method: "DELETE",
            requiresAuth: true
        )
        
        print("✅ Card deletion request completed")
    }
}

