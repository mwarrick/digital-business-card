//
//  KeychainHelper.swift
//  ShareMyCard
//
//  Secure storage for JWT tokens
//

import Foundation
import Security

class KeychainHelper {
    
    /// Save JWT token to Keychain
    static func saveToken(_ token: String) {
        guard let data = token.data(using: .utf8) else { return }
        
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrAccount as String: "jwt_token",
            kSecValueData as String: data,
            kSecAttrAccessible as String: kSecAttrAccessibleWhenUnlocked
        ]
        
        // Delete any existing token first
        SecItemDelete(query as CFDictionary)
        
        // Add new token
        let status = SecItemAdd(query as CFDictionary, nil)
        if status != errSecSuccess {
            print("Error saving token to Keychain: \(status)")
        }
    }
    
    /// Get JWT token from Keychain
    static func getToken() -> String? {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrAccount as String: "jwt_token",
            kSecReturnData as String: true,
            kSecMatchLimit as String: kSecMatchLimitOne
        ]
        
        var result: AnyObject?
        let status = SecItemCopyMatching(query as CFDictionary, &result)
        
        guard status == errSecSuccess,
              let data = result as? Data,
              let token = String(data: data, encoding: .utf8) else {
            return nil
        }
        
        return token
    }
    
    /// Delete JWT token from Keychain
    static func deleteToken() {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrAccount as String: "jwt_token"
        ]
        
        SecItemDelete(query as CFDictionary)
    }
    
    /// Check if user is authenticated
    static func isAuthenticated() -> Bool {
        return getToken() != nil
    }
    
    /// Save user email to Keychain
    static func saveEmail(_ email: String) {
        guard let data = email.data(using: .utf8) else { return }
        
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrAccount as String: "user_email",
            kSecValueData as String: data,
            kSecAttrAccessible as String: kSecAttrAccessibleWhenUnlocked
        ]
        
        // Delete any existing email first
        SecItemDelete(query as CFDictionary)
        
        // Add new email
        let status = SecItemAdd(query as CFDictionary, nil)
        if status != errSecSuccess {
            print("Error saving email to Keychain: \(status)")
        }
    }
    
    /// Get user email from Keychain, or decode from JWT token as fallback
    static func getEmail() -> String? {
        // First try to get from Keychain
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrAccount as String: "user_email",
            kSecReturnData as String: true,
            kSecMatchLimit as String: kSecMatchLimitOne
        ]
        
        var result: AnyObject?
        let status = SecItemCopyMatching(query as CFDictionary, &result)
        
        if status == errSecSuccess,
           let data = result as? Data,
           let email = String(data: data, encoding: .utf8),
           !email.isEmpty {
            return email
        }
        
        // Fallback: try to decode from JWT token
        if let token = getToken() {
            return decodeEmailFromToken(token)
        }
        
        return nil
    }
    
    /// Decode email from JWT token payload
    private static func decodeEmailFromToken(_ token: String) -> String? {
        // JWT format: header.payload.signature
        let parts = token.components(separatedBy: ".")
        guard parts.count >= 2 else { return nil }
        
        // Decode the payload (second part)
        let payload = parts[1]
        
        // Add padding if needed for base64 decoding
        var base64String = payload
        let remainder = base64String.count % 4
        if remainder > 0 {
            base64String = base64String.padding(toLength: base64String.count + 4 - remainder, withPad: "=", startingAt: 0)
        }
        
        // Decode base64
        guard let data = Data(base64Encoded: base64String) else { return nil }
        
        // Parse JSON
        guard let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any],
              let email = json["email"] as? String else {
            return nil
        }
        
        // Save it for future use
        if !email.isEmpty {
            saveEmail(email)
        }
        
        return email
    }
    
    /// Delete user email from Keychain
    static func deleteEmail() {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrAccount as String: "user_email"
        ]
        
        SecItemDelete(query as CFDictionary)
    }
}

