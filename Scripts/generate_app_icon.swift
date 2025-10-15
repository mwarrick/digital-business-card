#!/usr/bin/env swift

import SwiftUI
import Foundation

#if canImport(UIKit)
import UIKit
#endif

// MARK: - App Icon View
struct AppIconView: View {
    var body: some View {
        ZStack {
            // Background gradient (blue to purple)
            LinearGradient(
                gradient: Gradient(colors: [Color.blue, Color.purple]),
                startPoint: .topLeading,
                endPoint: .bottomTrailing
            )
            
            // Stacked cards icon
            Image(systemName: "square.stack.3d.up.fill")
                .font(.system(size: 512))
                .foregroundColor(.white)
        }
    }
}

// MARK: - Icon Sizes
let iconSizes: [(name: String, size: CGFloat)] = [
    ("AppIcon~ios-marketing", 1024),      // App Store
    ("AppIcon@3x", 180),                   // iPhone 60pt @3x
    ("AppIcon@2x", 120),                   // iPhone 60pt @2x
    ("AppIcon-83.5@2x~ipad", 167),        // iPad Pro 83.5pt @2x
    ("AppIcon@2x~ipad", 152),             // iPad 76pt @2x
    ("AppIcon~ipad", 76),                  // iPad 76pt @1x
    ("AppIcon-40@3x", 120),               // Spotlight 40pt @3x
    ("AppIcon-40@2x", 80),                // Spotlight 40pt @2x
    ("AppIcon-40@2x~ipad", 80),           // iPad Spotlight 40pt @2x
    ("AppIcon-40~ipad", 40),              // iPad Spotlight 40pt @1x
    ("AppIcon-20@3x", 60),                // Notification 20pt @3x
    ("AppIcon-20@2x", 40),                // Notification 20pt @2x
    ("AppIcon-20@2x~ipad", 40),           // iPad Notification 20pt @2x
    ("AppIcon-20~ipad", 20),              // iPad Notification 20pt @1x
    ("AppIcon-29@3x", 87),                // Settings 29pt @3x
    ("AppIcon-29@2x", 58),                // Settings 29pt @2x
    ("AppIcon-29", 29),                   // Settings 29pt @1x
    ("AppIcon-29@2x~ipad", 58),           // iPad Settings 29pt @2x
    ("AppIcon-29~ipad", 29),              // iPad Settings 29pt @1x
]

// MARK: - Renderer
@available(macOS 12.0, *)
@MainActor
func renderIcon(size: CGFloat) -> NSImage {
    let view = AppIconView()
        .frame(width: size, height: size)
    
    let renderer = ImageRenderer(content: view)
    renderer.scale = 1.0
    
    guard let nsImage = renderer.nsImage else {
        fatalError("Failed to render image")
    }
    
    return nsImage
}

@available(macOS 12.0, *)
@MainActor
func saveIcon(name: String, size: CGFloat, outputDir: URL) {
    print("📱 Generating \(name).png (\(Int(size))x\(Int(size)))...")
    
    let image = renderIcon(size: size)
    
    guard let tiffData = image.tiffRepresentation,
          let bitmap = NSBitmapImageRep(data: tiffData),
          let pngData = bitmap.representation(using: .png, properties: [:]) else {
        print("❌ Failed to generate PNG for \(name)")
        return
    }
    
    let fileURL = outputDir.appendingPathComponent("\(name).png")
    
    do {
        try pngData.write(to: fileURL)
        print("✅ Saved \(name).png")
    } catch {
        print("❌ Failed to save \(name).png: \(error)")
    }
}

// MARK: - Main
@available(macOS 12.0, *)
@MainActor
func main() {
    print("🎨 ShareMyCard App Icon Generator")
    print("==================================\n")
    
    // Get the project root directory
    let currentPath = URL(fileURLWithPath: FileManager.default.currentDirectoryPath)
    let outputDir = currentPath
        .appendingPathComponent("QRCard")
        .appendingPathComponent("Assets.xcassets")
        .appendingPathComponent("AppIcon.appiconset")
    
    print("📂 Output directory: \(outputDir.path)\n")
    
    // Create directory if needed
    try? FileManager.default.createDirectory(at: outputDir, withIntermediateDirectories: true)
    
    // Generate all icon sizes
    for (name, size) in iconSizes {
        saveIcon(name: name, size: size, outputDir: outputDir)
    }
    
    print("\n✨ Done! Generated \(iconSizes.count) app icons.")
    print("📱 Open the project in Xcode to see the new icons!")
}

if #available(macOS 12.0, *) {
    Task { @MainActor in
        main()
    }
    RunLoop.main.run()
} else {
    print("❌ This script requires macOS 12.0 or later")
    exit(1)
}

