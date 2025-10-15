//
//  APIConfig.swift
//  ShareMyCard
//
//  API Configuration for production server
//

import Foundation

struct APIConfig {
    /// Base URL for the API
    static let baseURL = "https://sharemycard.app/api"
    
    /// Request timeout
    static let timeout: TimeInterval = 30
    
    /// API Endpoints
    struct Endpoints {
        // Authentication
        static let register = "/auth/register"
        static let login = "/auth/login"
        static let verify = "/auth/verify"
        
        // Business Cards
        static let cards = "/cards/"
        static let qrCode = "/cards/qrcode"
        
        // Media
        static let mediaUpload = "/media/upload"
        static let mediaView = "/media/view"
        static let mediaDelete = "/media/delete"
    }
    
    /// Media Types
    struct MediaType {
        static let profilePhoto = "profile_photo"
        static let companyLogo = "company_logo"
        static let coverGraphic = "cover_graphic"
    }
}

