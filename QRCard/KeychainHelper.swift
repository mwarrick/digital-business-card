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
}

