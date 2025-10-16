//
//  APIClient.swift
//  ShareMyCard
//
//  HTTP client for API communication
//

import Foundation

/// API Response structure
struct APIResponse<T: Decodable>: Decodable {
    let success: Bool
    let message: String
    let data: T?
    let errors: [String]?
}

/// Empty response for endpoints that don't return data
struct EmptyResponse: Decodable {}

/// API Error types
enum APIError: LocalizedError {
    case invalidURL
    case invalidResponse
    case unauthorized
    case rateLimitExceeded
    case serverError(String)
    case networkError(Error)
    
    var errorDescription: String? {
        switch self {
        case .invalidURL:
            return "Invalid URL"
        case .invalidResponse:
            return "Invalid response from server"
        case .unauthorized:
            return "Session expired. Please log in again."
        case .rateLimitExceeded:
            return "Too many requests. Please try again later."
        case .serverError(let message):
            return message
        case .networkError(let error):
            return "Network error: \(error.localizedDescription)"
        }
    }
}

class APIClient {
    static let shared = APIClient()
    
    private init() {}
    
    /// Make an API request
    func request<T: Decodable>(
        endpoint: String,
        method: String = "GET",
        body: Any? = nil,
        requiresAuth: Bool = true
    ) async throws -> APIResponse<T> {
        
        print("🚀 API Request Starting:")
        print("   📍 Endpoint: \(endpoint)")
        print("   🔧 Method: \(method)")
        print("   🔐 Requires Auth: \(requiresAuth)")
        
        guard let url = URL(string: APIConfig.baseURL + endpoint) else {
            print("❌ Invalid URL: \(APIConfig.baseURL + endpoint)")
            throw APIError.invalidURL
        }
        
        print("   🌐 Full URL: \(url)")
        
        var request = URLRequest(url: url)
        request.httpMethod = method
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.setValue("ShareMyCard-iOS/1.0", forHTTPHeaderField: "User-Agent")
        request.setValue("ios-app", forHTTPHeaderField: "X-App-Platform")
        request.timeoutInterval = APIConfig.timeout
        
        // Add authorization header if required
        if requiresAuth {
            if let token = KeychainHelper.getToken() {
                request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
                print("   🔑 Auth Token: \(token.prefix(20))...")
            } else {
                print("   ⚠️ No auth token found in keychain!")
            }
        }
        
        // Add body if provided
        if let body = body {
            do {
                if let encodableBody = body as? Encodable {
                    // Handle Encodable objects
                    let encoder = JSONEncoder()
                    request.httpBody = try encoder.encode(encodableBody)
                    print("   📦 Request Body (Encodable): \(body)")
                } else if let dictBody = body as? [String: Any] {
                    // Handle dictionary objects (legacy)
                    request.httpBody = try JSONSerialization.data(withJSONObject: dictBody)
                    print("   📦 Request Body (Dictionary): \(dictBody)")
                } else {
                    print("   ❌ Unsupported body type: \(type(of: body))")
                    throw APIError.networkError(NSError(domain: "APIClient", code: -1, userInfo: [NSLocalizedDescriptionKey: "Unsupported body type"]))
                }
            } catch {
                print("   ❌ Failed to serialize request body: \(error)")
                throw APIError.networkError(error)
            }
        }
        
        do {
            print("   📡 Sending request...")
            let (data, response) = try await URLSession.shared.data(for: request)
            
            guard let httpResponse = response as? HTTPURLResponse else {
                print("   ❌ Invalid HTTP response")
                throw APIError.invalidResponse
            }
            
            print("   📊 HTTP Status: \(httpResponse.statusCode)")
            print("   📏 Response Size: \(data.count) bytes")
            
            // Log raw response for debugging
            if let responseString = String(data: data, encoding: .utf8) {
                print("   📥 Raw Response: \(responseString)")
            } else {
                print("   ❌ Could not decode response as UTF-8")
            }
            
            // Handle different status codes
            switch httpResponse.statusCode {
            case 200...299:
                print("   ✅ Success status code")
                break
            case 401:
                print("   🔒 Unauthorized - clearing token")
                KeychainHelper.deleteToken()
                throw APIError.unauthorized
            case 429:
                print("   ⏰ Rate limit exceeded")
                throw APIError.rateLimitExceeded
            default:
                print("   ⚠️ Non-success status code: \(httpResponse.statusCode)")
                // Try to decode error message
                if let apiResponse = try? JSONDecoder().decode(APIResponse<T>.self, from: data) {
                    print("   📝 Decoded error response: \(apiResponse.message)")
                    throw APIError.serverError(apiResponse.message)
                } else {
                    print("   ❌ Could not decode error response as JSON")
                    throw APIError.serverError("Server error: \(httpResponse.statusCode)")
                }
            }
            
            // Decode response
            print("   🔍 Attempting to decode JSON response...")
            let decoder = JSONDecoder()
            // Note: Not using keyDecodingStrategy because we have explicit CodingKeys
            
            do {
                let apiResponse = try decoder.decode(APIResponse<T>.self, from: data)
                print("   ✅ Successfully decoded JSON response")
                print("   📋 Response success: \(apiResponse.success)")
                print("   💬 Response message: \(apiResponse.message ?? "nil")")
                
                if !apiResponse.success {
                    print("   ❌ API returned success=false")
                    throw APIError.serverError(apiResponse.message)
                }
                
                print("   🎉 API request completed successfully")
                return apiResponse
            } catch {
                print("   ❌ JSON Decode Error: \(error)")
                print("   🔍 Error details: \(error.localizedDescription)")
                if let responseString = String(data: data, encoding: .utf8) {
                    print("   📄 Raw response that failed to decode: \(responseString)")
                }
                throw APIError.networkError(error)
            }
            
        } catch let error as APIError {
            throw error
        } catch {
            throw APIError.networkError(error)
        }
    }
}

