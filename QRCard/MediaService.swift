//
//  MediaService.swift
//  ShareMyCard
//
//  Media upload/download/delete service
//

import Foundation
import UIKit

// MARK: - Media Upload Response

struct MediaUploadResponse: Codable {
    let filename: String
    let path: String
    let url: String
}

// MARK: - Media Service

class MediaService {
    
    /// Upload an image to the server
    /// - Parameters:
    ///   - image: The UIImage to upload
    ///   - type: The type of image (profile_photo, company_logo, cover_graphic)
    ///   - businessCardId: The ID of the business card this image belongs to
    /// - Returns: MediaUploadResponse with filename and URL
    static func uploadImage(_ image: UIImage, type: String, businessCardId: String) async throws -> MediaUploadResponse {
        print("📤 Uploading \(type) for card \(businessCardId)...")
        
        // Compress image
        guard let imageData = image.jpegData(compressionQuality: 0.8) else {
            throw MediaError.compressionFailed
        }
        
        print("  📦 Image size: \(imageData.count / 1024)KB")
        
        // Get auth token
        guard let token = KeychainHelper.getToken() else {
            throw APIError.unauthorized
        }
        
        // Create request
        let url = URL(string: APIConfig.baseURL + APIConfig.Endpoints.mediaUpload)!
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        request.setValue("ShareMyCard-iOS/1.0", forHTTPHeaderField: "User-Agent")
        request.timeoutInterval = 60 // Longer timeout for uploads
        
        // Create multipart form data
        let boundary = "Boundary-\(UUID().uuidString)"
        request.setValue("multipart/form-data; boundary=\(boundary)", forHTTPHeaderField: "Content-Type")
        
        var body = Data()
        
        // Add business_card_id field
        body.append("--\(boundary)\r\n".data(using: .utf8)!)
        body.append("Content-Disposition: form-data; name=\"business_card_id\"\r\n\r\n".data(using: .utf8)!)
        body.append("\(businessCardId)\r\n".data(using: .utf8)!)
        
        // Add media_type field
        body.append("--\(boundary)\r\n".data(using: .utf8)!)
        body.append("Content-Disposition: form-data; name=\"media_type\"\r\n\r\n".data(using: .utf8)!)
        body.append("\(type)\r\n".data(using: .utf8)!)
        
        // Add file field
        body.append("--\(boundary)\r\n".data(using: .utf8)!)
        body.append("Content-Disposition: form-data; name=\"file\"; filename=\"image.jpg\"\r\n".data(using: .utf8)!)
        body.append("Content-Type: image/jpeg\r\n\r\n".data(using: .utf8)!)
        body.append(imageData)
        body.append("\r\n".data(using: .utf8)!)
        
        // End boundary
        body.append("--\(boundary)--\r\n".data(using: .utf8)!)
        
        request.httpBody = body
        
        // Upload with progress
        let (data, response) = try await URLSession.shared.data(for: request)
        
        guard let httpResponse = response as? HTTPURLResponse else {
            throw APIError.invalidResponse
        }
        
        print("  📡 Server response: \(httpResponse.statusCode)")
        
        guard httpResponse.statusCode == 200 else {
            if let errorResponse = try? JSONDecoder().decode(APIResponse<EmptyResponse>.self, from: data) {
                throw APIError.serverError(errorResponse.messageValue)
            }
            throw APIError.serverError("Upload failed with status \(httpResponse.statusCode)")
        }
        
        // Parse response
        let apiResponse = try JSONDecoder().decode(APIResponse<MediaUploadResponse>.self, from: data)
        
        guard let mediaData = apiResponse.data else {
            throw APIError.serverError("No data in response")
        }
        
        print("  ✅ Upload successful: \(mediaData.filename)")
        
        return mediaData
    }
    
    /// Download an image from the server
    /// - Parameter filename: The filename to download
    /// - Returns: UIImage if successful
    static func downloadImage(filename: String) async throws -> UIImage {
        print("📥 Downloading image: \(filename)")
        
        // Get auth token
        guard let token = KeychainHelper.getToken() else {
            throw APIError.unauthorized
        }
        
        // Create request
        let urlString = APIConfig.baseURL + APIConfig.Endpoints.mediaView + "?filename=" + filename
        guard let url = URL(string: urlString) else {
            throw APIError.invalidURL
        }
        
        var request = URLRequest(url: url)
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        request.timeoutInterval = 30
        
        // Download
        let (data, response) = try await URLSession.shared.data(for: request)
        
        guard let httpResponse = response as? HTTPURLResponse else {
            throw APIError.invalidResponse
        }
        
        guard httpResponse.statusCode == 200 else {
            throw APIError.serverError("Download failed with status \(httpResponse.statusCode)")
        }
        
        // Convert to image
        guard let image = UIImage(data: data) else {
            throw MediaError.invalidImageData
        }
        
        print("  ✅ Download successful")
        
        return image
    }
    
    /// Delete an image from the server
    /// - Parameter filename: The filename to delete
    static func deleteImage(filename: String) async throws {
        print("🗑️ Deleting image: \(filename)")
        
        // Get auth token
        guard let token = KeychainHelper.getToken() else {
            throw APIError.unauthorized
        }
        
        // Create request
        let url = URL(string: APIConfig.baseURL + APIConfig.Endpoints.mediaDelete)!
        var request = URLRequest(url: url)
        request.httpMethod = "DELETE"
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.timeoutInterval = 30
        
        // Create body
        let body: [String: String] = ["filename": filename]
        request.httpBody = try JSONSerialization.data(withJSONObject: body)
        
        // Send request
        let (data, response) = try await URLSession.shared.data(for: request)
        
        guard let httpResponse = response as? HTTPURLResponse else {
            throw APIError.invalidResponse
        }
        
        guard httpResponse.statusCode == 200 else {
            // Use EmptyResponse from APIClient.swift
            if let errorResponse = try? JSONDecoder().decode(APIResponse<EmptyResponse>.self, from: data) {
                throw APIError.serverError(errorResponse.messageValue)
            }
            throw APIError.serverError("Delete failed with status \(httpResponse.statusCode)")
        }
        
        print("  ✅ Delete successful")
    }
}

// MARK: - Media Errors

enum MediaError: LocalizedError {
    case compressionFailed
    case invalidImageData
    
    var errorDescription: String? {
        switch self {
        case .compressionFailed:
            return "Failed to compress image"
        case .invalidImageData:
            return "Invalid image data received"
        }
    }
}

