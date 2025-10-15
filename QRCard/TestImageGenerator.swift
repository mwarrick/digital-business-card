//
//  TestImageGenerator.swift
//  ShareMyCard
//
//  Test image generator for development/testing
//

import UIKit
import Photos

class TestImageGenerator {
    
    /// Generate a test profile photo
    static func generateProfilePhoto() -> UIImage {
        return generateImage(
            size: CGSize(width: 400, height: 400),
            backgroundColor: .systemBlue,
            text: "Profile\nPhoto",
            emoji: "👤"
        )
    }
    
    /// Generate a test company logo
    static func generateCompanyLogo() -> UIImage {
        return generateImage(
            size: CGSize(width: 400, height: 400),
            backgroundColor: .systemGreen,
            text: "Company\nLogo",
            emoji: "🏢"
        )
    }
    
    /// Generate a test cover graphic
    static func generateCoverGraphic() -> UIImage {
        return generateImage(
            size: CGSize(width: 800, height: 400),
            backgroundColor: .systemPurple,
            text: "Cover\nGraphic",
            emoji: "🎨"
        )
    }
    
    /// Generate a colored image with text and emoji
    private static func generateImage(size: CGSize, backgroundColor: UIColor, text: String, emoji: String) -> UIImage {
        let renderer = UIGraphicsImageRenderer(size: size)
        
        return renderer.image { context in
            // Background
            backgroundColor.setFill()
            context.fill(CGRect(origin: .zero, size: size))
            
            // White overlay with opacity
            UIColor.white.withAlphaComponent(0.2).setFill()
            context.fill(CGRect(origin: .zero, size: size))
            
            // Emoji
            let emojiSize: CGFloat = size.width * 0.4
            let emojiFont = UIFont.systemFont(ofSize: emojiSize)
            let emojiAttributes: [NSAttributedString.Key: Any] = [
                .font: emojiFont,
                .foregroundColor: UIColor.white.withAlphaComponent(0.8)
            ]
            
            let emojiString = emoji as NSString
            let emojiRect = emojiString.boundingRect(
                with: size,
                options: .usesLineFragmentOrigin,
                attributes: emojiAttributes,
                context: nil
            )
            
            let emojiX = (size.width - emojiRect.width) / 2
            let emojiY = (size.height - emojiRect.height) / 2 - 40
            
            emojiString.draw(
                at: CGPoint(x: emojiX, y: emojiY),
                withAttributes: emojiAttributes
            )
            
            // Text
            let textFont = UIFont.systemFont(ofSize: 32, weight: .bold)
            let paragraphStyle = NSMutableParagraphStyle()
            paragraphStyle.alignment = .center
            
            let textAttributes: [NSAttributedString.Key: Any] = [
                .font: textFont,
                .foregroundColor: UIColor.white,
                .paragraphStyle: paragraphStyle
            ]
            
            let textString = text as NSString
            let textRect = CGRect(
                x: 20,
                y: size.height - 120,
                width: size.width - 40,
                height: 100
            )
            
            textString.draw(in: textRect, withAttributes: textAttributes)
            
            // Test watermark
            let watermarkFont = UIFont.systemFont(ofSize: 16, weight: .medium)
            let watermarkAttributes: [NSAttributedString.Key: Any] = [
                .font: watermarkFont,
                .foregroundColor: UIColor.white.withAlphaComponent(0.6),
                .paragraphStyle: paragraphStyle
            ]
            
            let watermark = "TEST IMAGE" as NSString
            let watermarkRect = CGRect(
                x: 20,
                y: 20,
                width: size.width - 40,
                height: 30
            )
            
            watermark.draw(in: watermarkRect, withAttributes: watermarkAttributes)
        }
    }
    
    /// Save test images to photo library (requires photo library permission)
    static func saveTestImagesToPhotoLibrary(completion: @escaping (Bool, Error?) -> Void) {
        // Check permission
        let status = PHPhotoLibrary.authorizationStatus(for: .addOnly)
        
        switch status {
        case .authorized, .limited:
            saveImages(completion: completion)
        case .notDetermined:
            PHPhotoLibrary.requestAuthorization(for: .addOnly) { newStatus in
                if newStatus == .authorized || newStatus == .limited {
                    saveImages(completion: completion)
                } else {
                    completion(false, NSError(domain: "TestImageGenerator", code: 1, userInfo: [NSLocalizedDescriptionKey: "Photo library access denied"]))
                }
            }
        default:
            completion(false, NSError(domain: "TestImageGenerator", code: 1, userInfo: [NSLocalizedDescriptionKey: "Photo library access denied"]))
        }
    }
    
    private static func saveImages(completion: @escaping (Bool, Error?) -> Void) {
        let images = [
            generateProfilePhoto(),
            generateCompanyLogo(),
            generateCoverGraphic()
        ]
        
        PHPhotoLibrary.shared().performChanges({
            for image in images {
                PHAssetChangeRequest.creationRequestForAsset(from: image)
            }
        }, completionHandler: completion)
    }
}

