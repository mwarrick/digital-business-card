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
    let hasPassword: Bool
    let verificationCodeSent: Bool
    
    enum CodingKeys: String, CodingKey {
        case userId = "user_id"
        case email
        case isAdmin = "is_admin"
        case hasPassword = "has_password"
        case verificationCodeSent = "verification_code_sent"
    }
}

struct VerifyRequest: Encodable {
    let email: String
    let code: String?
    let password: String?
    
    init(email: String, code: String? = nil, password: String? = nil) {
        self.email = email
        self.code = code
        self.password = password
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

// MARK: - Password Management Models

struct SetPasswordRequest: Encodable {
    let password: String
}

struct ChangePasswordRequest: Encodable {
    let currentPassword: String
    let newPassword: String
    
    enum CodingKeys: String, CodingKey {
        case currentPassword = "current_password"
        case newPassword = "new_password"
    }
}

struct ResetPasswordRequestRequest: Encodable {
    let email: String
}

struct ResetPasswordCompleteRequest: Encodable {
    let email: String
    let code: String
    let newPassword: String
    
    enum CodingKeys: String, CodingKey {
        case email
        case code
        case newPassword = "new_password"
    }
}

struct PasswordStatusResponse: Decodable {
    let hasPassword: Bool
    let userId: String
    let email: String
    
    enum CodingKeys: String, CodingKey {
        case hasPassword = "has_password"
        case userId = "user_id"
        case email
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
    static func login(email: String, forceEmailCode: Bool = false) async throws -> LoginResponse {
        var body: [String: Any] = ["email": email]
        if forceEmailCode {
            body["force_email_code"] = true
        }
        
        let response: APIResponse<LoginResponse> = try await APIClient.shared.request(
            endpoint: APIConfig.Endpoints.login,
            method: "POST",
            body: body,
            requiresAuth: false
        )
        
        guard let data = response.data else {
            throw APIError.serverError("No data returned from login")
        }
        
        return data
    }
    
    /// Verify email with code or password
    static func verify(email: String, code: String? = nil, password: String? = nil) async throws -> VerifyResponse {
        let request = VerifyRequest(email: email, code: code, password: password)
        
        let response: APIResponse<VerifyResponse> = try await APIClient.shared.request(
            endpoint: APIConfig.Endpoints.verify,
            method: "POST",
            body: request,
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
    
    /// Check if current user has a password set
    static func checkPasswordStatus() async throws -> Bool {
        let response: APIResponse<PasswordStatusResponse> = try await APIClient.shared.request(
            endpoint: APIConfig.Endpoints.checkPasswordStatus,
            method: "GET",
            requiresAuth: true
        )
        
        guard let data = response.data else {
            throw APIError.serverError("Could not check password status")
        }
        
        return data.hasPassword
    }
    
    // MARK: - Password Management
    
    /// Set password for the first time
    static func setPassword(password: String) async throws {
        let request = SetPasswordRequest(password: password)
        
        let _: APIResponse<EmptyResponse> = try await APIClient.shared.request(
            endpoint: APIConfig.Endpoints.setPassword,
            method: "POST",
            body: request,
            requiresAuth: true
        )
    }
    
    /// Change existing password
    static func changePassword(currentPassword: String, newPassword: String) async throws {
        let request = ChangePasswordRequest(currentPassword: currentPassword, newPassword: newPassword)
        
        let _: APIResponse<EmptyResponse> = try await APIClient.shared.request(
            endpoint: APIConfig.Endpoints.changePassword,
            method: "POST",
            body: request,
            requiresAuth: true
        )
    }
    
    /// Request password reset code
    static func requestPasswordReset(email: String) async throws {
        let request = ResetPasswordRequestRequest(email: email)
        
        let _: APIResponse<EmptyResponse> = try await APIClient.shared.request(
            endpoint: APIConfig.Endpoints.resetPasswordRequest,
            method: "POST",
            body: request,
            requiresAuth: false
        )
    }
    
    /// Complete password reset with code
    static func resetPassword(email: String, code: String, newPassword: String) async throws {
        let request = ResetPasswordCompleteRequest(email: email, code: code, newPassword: newPassword)
        
        let _: APIResponse<EmptyResponse> = try await APIClient.shared.request(
            endpoint: APIConfig.Endpoints.resetPasswordComplete,
            method: "POST",
            body: request,
            requiresAuth: false
        )
    }
}

