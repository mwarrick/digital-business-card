# iOS Integration Quick Start Guide

## Overview
This guide provides Swift code examples for integrating with the ShareMyCard API.

---

## Setup

### 1. API Configuration
```swift
struct APIConfig {
    static let baseURL = "https://sharemycard.app/api"
    static let timeout: TimeInterval = 30
}
```

### 2. Store JWT Token Securely
```swift
import Security

class KeychainHelper {
    static func saveToken(_ token: String) {
        let data = token.data(using: .utf8)!
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrAccount as String: "jwt_token",
            kSecValueData as String: data
        ]
        
        SecItemDelete(query as CFDictionary)
        SecItemAdd(query as CFDictionary, nil)
    }
    
    static func getToken() -> String? {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrAccount as String: "jwt_token",
            kSecReturnData as String: true
        ]
        
        var result: AnyObject?
        SecItemCopyMatching(query as CFDictionary, &result)
        
        if let data = result as? Data {
            return String(data: data, encoding: .utf8)
        }
        return nil
    }
    
    static func deleteToken() {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrAccount as String: "jwt_token"
        ]
        SecItemDelete(query as CFDictionary)
    }
}
```

---

## API Client

### Base API Client
```swift
import Foundation

class APIClient {
    static let shared = APIClient()
    
    private init() {}
    
    func request<T: Decodable>(
        endpoint: String,
        method: String = "GET",
        body: [String: Any]? = nil,
        requiresAuth: Bool = true
    ) async throws -> APIResponse<T> {
        
        guard let url = URL(string: APIConfig.baseURL + endpoint) else {
            throw APIError.invalidURL
        }
        
        var request = URLRequest(url: url)
        request.httpMethod = method
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        
        // Add authorization header if required
        if requiresAuth, let token = KeychainHelper.getToken() {
            request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        }
        
        // Add body if provided
        if let body = body {
            request.httpBody = try JSONSerialization.data(withJSONObject: body)
        }
        
        let (data, response) = try await URLSession.shared.data(for: request)
        
        guard let httpResponse = response as? HTTPURLResponse else {
            throw APIError.invalidResponse
        }
        
        // Handle rate limiting
        if httpResponse.statusCode == 429 {
            throw APIError.rateLimitExceeded
        }
        
        // Handle unauthorized
        if httpResponse.statusCode == 401 {
            throw APIError.unauthorized
        }
        
        let apiResponse = try JSONDecoder().decode(APIResponse<T>.self, from: data)
        
        if !apiResponse.success {
            throw APIError.serverError(apiResponse.message)
        }
        
        return apiResponse
    }
}

// Response structure
struct APIResponse<T: Decodable>: Decodable {
    let success: Bool
    let message: String
    let data: T?
}

// Error types
enum APIError: LocalizedError {
    case invalidURL
    case invalidResponse
    case unauthorized
    case rateLimitExceeded
    case serverError(String)
    
    var errorDescription: String? {
        switch self {
        case .invalidURL: return "Invalid URL"
        case .invalidResponse: return "Invalid response from server"
        case .unauthorized: return "Session expired. Please log in again."
        case .rateLimitExceeded: return "Too many requests. Please try again later."
        case .serverError(let message): return message
        }
    }
}
```

---

## Authentication

### Register User
```swift
struct AuthService {
    static func register(email: String) async throws -> String {
        let response: APIResponse<RegisterResponse> = try await APIClient.shared.request(
            endpoint: "/auth/register",
            method: "POST",
            body: ["email": email],
            requiresAuth: false
        )
        
        return response.data?.userId ?? ""
    }
}

struct RegisterResponse: Decodable {
    let userId: String
    let email: String
    let message: String
    
    enum CodingKeys: String, CodingKey {
        case userId = "user_id"
        case email
        case message
    }
}
```

### Login
```swift
extension AuthService {
    static func login(email: String) async throws -> String {
        let response: APIResponse<LoginResponse> = try await APIClient.shared.request(
            endpoint: "/auth/login",
            method: "POST",
            body: ["email": email],
            requiresAuth: false
        )
        
        return response.data?.userId ?? ""
    }
}

struct LoginResponse: Decodable {
    let userId: String
    let email: String
    let isAdmin: Bool
    
    enum CodingKeys: String, CodingKey {
        case userId = "user_id"
        case email
        case isAdmin = "is_admin"
    }
}
```

### Verify Code
```swift
extension AuthService {
    static func verify(email: String, code: String) async throws -> VerifyResponse {
        let response: APIResponse<VerifyResponse> = try await APIClient.shared.request(
            endpoint: "/auth/verify",
            method: "POST",
            body: ["email": email, "code": code],
            requiresAuth: false
        )
        
        if let data = response.data {
            // Save token to Keychain
            KeychainHelper.saveToken(data.token)
            return data
        }
        
        throw APIError.serverError("Verification failed")
    }
}

struct VerifyResponse: Decodable {
    let token: String
    let userId: String
    let email: String
    let isAdmin: Bool
    let isActive: Bool
    let tokenExpiresIn: Int
    
    enum CodingKeys: String, CodingKey {
        case token
        case userId = "user_id"
        case email
        case isAdmin = "is_admin"
        case isActive = "is_active"
        case tokenExpiresIn = "token_expires_in"
    }
}
```

---

## Business Cards

### Data Models
```swift
struct BusinessCardAPI: Codable {
    let id: String
    let userId: String
    let firstName: String
    let lastName: String
    let phoneNumber: String
    let companyName: String?
    let jobTitle: String?
    let bio: String?
    let emails: [EmailContact]
    let phones: [PhoneContact]
    let websites: [WebsiteLink]
    let address: Address?
    let isActive: Bool
    let createdAt: String
    let updatedAt: String
    
    enum CodingKeys: String, CodingKey {
        case id
        case userId = "user_id"
        case firstName = "first_name"
        case lastName = "last_name"
        case phoneNumber = "phone_number"
        case companyName = "company_name"
        case jobTitle = "job_title"
        case bio
        case emails
        case phones
        case websites
        case address
        case isActive = "is_active"
        case createdAt = "created_at"
        case updatedAt = "updated_at"
    }
}

struct EmailContact: Codable {
    let id: String?
    let email: String
    let type: String
    let label: String?
}

struct PhoneContact: Codable {
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

struct WebsiteLink: Codable {
    let id: String?
    let url: String
    let name: String?
    let description: String?
}

struct Address: Codable {
    let id: String?
    let street: String?
    let city: String?
    let state: String?
    let postalCode: String?
    let country: String?
    
    enum CodingKeys: String, CodingKey {
        case id
        case street
        case city
        case state
        case postalCode = "postal_code"
        case country
    }
}
```

### Card Service
```swift
struct CardService {
    // Fetch all cards
    static func fetchCards() async throws -> [BusinessCardAPI] {
        let response: APIResponse<[BusinessCardAPI]> = try await APIClient.shared.request(
            endpoint: "/cards/"
        )
        
        return response.data ?? []
    }
    
    // Create card
    static func createCard(_ card: BusinessCardAPI) async throws -> BusinessCardAPI {
        let body = try card.asDictionary()
        
        let response: APIResponse<BusinessCardAPI> = try await APIClient.shared.request(
            endpoint: "/cards/",
            method: "POST",
            body: body
        )
        
        guard let data = response.data else {
            throw APIError.serverError("Failed to create card")
        }
        
        return data
    }
    
    // Update card
    static func updateCard(_ card: BusinessCardAPI) async throws -> BusinessCardAPI {
        let body = try card.asDictionary()
        
        let response: APIResponse<BusinessCardAPI> = try await APIClient.shared.request(
            endpoint: "/cards/?id=\(card.id)",
            method: "PUT",
            body: body
        )
        
        guard let data = response.data else {
            throw APIError.serverError("Failed to update card")
        }
        
        return data
    }
    
    // Delete card
    static func deleteCard(id: String) async throws {
        let _: APIResponse<EmptyResponse> = try await APIClient.shared.request(
            endpoint: "/cards/?id=\(id)",
            method: "DELETE"
        )
    }
}

struct EmptyResponse: Decodable {}

extension Encodable {
    func asDictionary() throws -> [String: Any] {
        let data = try JSONEncoder().encode(self)
        guard let dictionary = try JSONSerialization.jsonObject(with: data) as? [String: Any] else {
            throw NSError()
        }
        return dictionary
    }
}
```

---

## Media Upload

### Upload Media
```swift
struct MediaService {
    static func uploadMedia(
        cardId: String,
        mediaType: MediaType,
        image: UIImage
    ) async throws -> MediaUploadResponse {
        
        guard let imageData = image.jpegData(compressionQuality: 0.8) else {
            throw APIError.serverError("Failed to convert image")
        }
        
        guard let url = URL(string: APIConfig.baseURL + "/media/upload") else {
            throw APIError.invalidURL
        }
        
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        
        if let token = KeychainHelper.getToken() {
            request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        }
        
        // Create multipart form data
        let boundary = UUID().uuidString
        request.setValue("multipart/form-data; boundary=\(boundary)", forHTTPHeaderField: "Content-Type")
        
        var body = Data()
        
        // Add business_card_id
        body.append("--\(boundary)\r\n")
        body.append("Content-Disposition: form-data; name=\"business_card_id\"\r\n\r\n")
        body.append("\(cardId)\r\n")
        
        // Add media_type
        body.append("--\(boundary)\r\n")
        body.append("Content-Disposition: form-data; name=\"media_type\"\r\n\r\n")
        body.append("\(mediaType.rawValue)\r\n")
        
        // Add file
        body.append("--\(boundary)\r\n")
        body.append("Content-Disposition: form-data; name=\"file\"; filename=\"image.jpg\"\r\n")
        body.append("Content-Type: image/jpeg\r\n\r\n")
        body.append(imageData)
        body.append("\r\n")
        body.append("--\(boundary)--\r\n")
        
        request.httpBody = body
        
        let (data, _) = try await URLSession.shared.data(for: request)
        let response = try JSONDecoder().decode(APIResponse<MediaUploadResponse>.self, from: data)
        
        guard let result = response.data else {
            throw APIError.serverError("Upload failed")
        }
        
        return result
    }
}

enum MediaType: String {
    case profilePhoto = "profile_photo"
    case companyLogo = "company_logo"
    case coverGraphic = "cover_graphic"
}

struct MediaUploadResponse: Decodable {
    let filename: String
    let url: String
    let mediaType: String
    let businessCardId: String
    let size: Int
    let mimeType: String
    
    enum CodingKeys: String, CodingKey {
        case filename
        case url
        case mediaType = "media_type"
        case businessCardId = "business_card_id"
        case size
        case mimeType = "mime_type"
    }
}

extension Data {
    mutating func append(_ string: String) {
        if let data = string.data(using: .utf8) {
            append(data)
        }
    }
}
```

---

## QR Code Generation

### Generate QR Code
```swift
struct QRCodeService {
    static func generateQRCode(cardId: String, size: Int = 300) async throws -> QRCodeResponse {
        let response: APIResponse<QRCodeResponse> = try await APIClient.shared.request(
            endpoint: "/cards/qrcode?id=\(cardId)&size=\(size)"
        )
        
        guard let data = response.data else {
            throw APIError.serverError("Failed to generate QR code")
        }
        
        return data
    }
    
    static func downloadQRCodeImage(cardId: String, size: Int = 500) async throws -> UIImage {
        guard let url = URL(string: APIConfig.baseURL + "/cards/qrcode?id=\(cardId)&format=image&size=\(size)") else {
            throw APIError.invalidURL
        }
        
        var request = URLRequest(url: url)
        if let token = KeychainHelper.getToken() {
            request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        }
        
        let (data, _) = try await URLSession.shared.data(for: request)
        
        guard let image = UIImage(data: data) else {
            throw APIError.serverError("Failed to load QR code image")
        }
        
        return image
    }
}

struct QRCodeResponse: Decodable {
    let businessCardId: String
    let qrCodeUrl: String
    let vcardData: String
    let size: Int
    let format: String
    
    enum CodingKeys: String, CodingKey {
        case businessCardId = "business_card_id"
        case qrCodeUrl = "qr_code_url"
        case vcardData = "vcard_data"
        case size
        case format
    }
}
```

---

## Usage Examples

### Complete Authentication Flow
```swift
class AuthViewModel: ObservableObject {
    @Published var isAuthenticated = false
    @Published var errorMessage: String?
    
    func register(email: String) async {
        do {
            let userId = try await AuthService.register(email: email)
            print("User registered: \(userId)")
        } catch {
            errorMessage = error.localizedDescription
        }
    }
    
    func verify(email: String, code: String) async {
        do {
            let result = try await AuthService.verify(email: email, code: code)
            isAuthenticated = true
            print("Logged in as: \(result.email)")
        } catch {
            errorMessage = error.localizedDescription
        }
    }
    
    func logout() {
        KeychainHelper.deleteToken()
        isAuthenticated = false
    }
}
```

### Sync Business Cards
```swift
class CardSyncManager {
    func syncCards() async throws {
        // Fetch from API
        let apiCards = try await CardService.fetchCards()
        
        // Sync with Core Data
        for apiCard in apiCards {
            // Update or create local Core Data entity
            // Handle conflicts based on updated_at timestamp
        }
    }
    
    func pushNewCard(_ localCard: BusinessCard) async throws {
        // Convert Core Data entity to API model
        let apiCard = localCard.toAPIModel()
        
        // Create on server
        let createdCard = try await CardService.createCard(apiCard)
        
        // Update local entity with server ID
        localCard.id = createdCard.id
    }
}
```

---

## Error Handling

```swift
func handleAPICall() {
    Task {
        do {
            let cards = try await CardService.fetchCards()
            // Success
        } catch APIError.unauthorized {
            // Token expired - show login
            await MainActor.run {
                showLogin()
            }
        } catch APIError.rateLimitExceeded {
            // Too many requests - show message
            await MainActor.run {
                showAlert("Please wait before trying again")
            }
        } catch {
            // Other errors
            await MainActor.run {
                showError(error.localizedDescription)
            }
        }
    }
}
```

---

## Testing

```swift
// Mock API Client for testing
class MockAPIClient: APIClient {
    var shouldFail = false
    var mockData: Any?
    
    override func request<T: Decodable>(
        endpoint: String,
        method: String,
        body: [String: Any]?,
        requiresAuth: Bool
    ) async throws -> APIResponse<T> {
        
        if shouldFail {
            throw APIError.serverError("Mock error")
        }
        
        // Return mock data
        return APIResponse(success: true, message: "Success", data: mockData as? T)
    }
}
```

---

## Best Practices

1. **Token Management**:
   - Store JWT in Keychain securely
   - Check token expiration before requests
   - Implement automatic token refresh if needed

2. **Sync Strategy**:
   - Use timestamp-based conflict resolution
   - Implement offline queue for pending changes
   - Sync on app launch and resume

3. **Error Handling**:
   - Handle 401 (unauthorized) by re-authenticating
   - Handle 429 (rate limit) with exponential backoff
   - Show user-friendly error messages

4. **Performance**:
   - Cache images locally
   - Implement pagination for large lists
   - Use background queues for sync operations

5. **Security**:
   - Never log JWT tokens
   - Use HTTPS in production
   - Validate all API responses

---

## Next Steps

1. Implement network layer with these examples
2. Create sync manager for Core Data ↔ API
3. Add offline support with pending operations queue
4. Implement background sync
5. Add unit tests with mock API client

**Happy coding! 🚀**

