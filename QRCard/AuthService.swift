//
//  AuthService.swift
//  ShareMyCard
//
//  Authentication service for user login/registration
//

import Foundation

// MARK: - Request/Response Models

struct RegisterRequest: Encodable {
    let email: String
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

struct LoginRequest: Encodable {
    let email: String
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

struct VerifyRequest: Encodable {
    let email: String
    let code: String
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

// MARK: - Auth Service

class AuthService {
    
    /// Register a new user
    static func register(email: String) async throws -> RegisterResponse {
        let response: APIResponse<RegisterResponse> = try await APIClient.shared.request(
            endpoint: APIConfig.Endpoints.register,
            method: "POST",
            body: [
                "email": email
            ],
            requiresAuth: false
        )
        
        guard let data = response.data else {
            throw APIError.serverError("No data returned from registration")
        }
        
        return data
    }
    
    /// Login existing user
    static func login(email: String) async throws -> LoginResponse {
        let response: APIResponse<LoginResponse> = try await APIClient.shared.request(
            endpoint: APIConfig.Endpoints.login,
            method: "POST",
            body: [
                "email": email
            ],
            requiresAuth: false
        )
        
        guard let data = response.data else {
            throw APIError.serverError("No data returned from login")
        }
        
        return data
    }
    
    /// Verify email with code
    static func verify(email: String, code: String) async throws -> VerifyResponse {
        let response: APIResponse<VerifyResponse> = try await APIClient.shared.request(
            endpoint: APIConfig.Endpoints.verify,
            method: "POST",
            body: [
                "email": email,
                "code": code
            ],
            requiresAuth: false
        )
        
        guard let data = response.data else {
            throw APIError.serverError("Verification failed")
        }
        
        // Save token to Keychain
        KeychainHelper.saveToken(data.token)
        
        return data
    }
    
    /// Logout (clear token)
    static func logout() {
        // Clear auth token
        KeychainHelper.deleteToken()
        // Clear all local Core Data to avoid cross-account leakage
        DataManager.shared.clearAllData()
    }
    
    /// Check if user is authenticated
    static func isAuthenticated() -> Bool {
        return KeychainHelper.isAuthenticated()
    }
}

