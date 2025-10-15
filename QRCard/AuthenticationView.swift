//
//  AuthenticationView.swift
//  ShareMyCard
//
//  Root view that handles authentication state
//

import SwiftUI

struct AuthenticationView: View {
    @State private var isAuthenticated = AuthService.isAuthenticated()
    
    var body: some View {
        Group {
            if isAuthenticated {
                ContentView()
            } else {
                LoginView(isAuthenticated: $isAuthenticated)
            }
        }
        .onReceive(NotificationCenter.default.publisher(for: NSNotification.Name("UserLoggedOut"))) { _ in
            isAuthenticated = false
        }
        .onAppear {
            print("🔍 AuthenticationView appeared. isAuthenticated: \(isAuthenticated)")
        }
    }
}

#Preview {
    AuthenticationView()
}

