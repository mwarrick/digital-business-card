//
//  AppIconGenerator.swift
//  ShareMyCard
//
//  Created by Mark Warrick on 10/10/25.
//

import SwiftUI

// MARK: - App Icon Generator
struct AppIconGenerator: View {
    var body: some View {
        ZStack {
            // Background gradient (blue to purple)
            LinearGradient(
                gradient: Gradient(colors: [Color.blue, Color.purple]),
                startPoint: .topLeading,
                endPoint: .bottomTrailing
            )
            
            // Stacked cards icon (matching main screen)
            Image(systemName: "square.stack.3d.up.fill")
                .font(.system(size: 512))
                .foregroundColor(.white)
        }
        .frame(width: 1024, height: 1024)
        .clipShape(RoundedRectangle(cornerRadius: 226))
    }
}

// MARK: - Preview
#Preview {
    AppIconGenerator()
        .frame(width: 200, height: 200)
}
