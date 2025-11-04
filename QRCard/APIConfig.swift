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
        
        // Password Management
        static let setPassword = "/auth/set-password"
        static let changePassword = "/auth/change-password"
        static let resetPasswordRequest = "/auth/reset-password-request"
        static let resetPasswordComplete = "/auth/reset-password-complete"
        static let checkPasswordStatus = "/auth/check-password-status"
        
        // Business Cards
        static let cards = "/cards/"
        static let qrCode = "/cards/qrcode"
        
        // Contacts
        static let contacts = "/contacts/"
        
        // Leads
        static let leads = "/leads/"
        static let convertLead = "/leads/convert"
        
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

