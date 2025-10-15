//
//  LoginView.swift
//  ShareMyCard
//
//  Login and Registration View
//

import SwiftUI

struct LoginView: View {
    @State private var email = ""
    @State private var verificationCode = ""
    @State private var isRegistering = false
    @State private var showingVerification = false
    @State private var isLoading = false
    @State private var errorMessage = ""
    
    @Binding var isAuthenticated: Bool
    
    var body: some View {
        NavigationView {
            VStack(spacing: 20) {
                // Logo
                Image(systemName: "person.circle.fill")
                    .imageScale(.large)
                    .foregroundStyle(.blue)
                    .font(.system(size: 80))
                
                Text("ShareMyCard")
                    .font(.largeTitle)
                    .fontWeight(.bold)
                
                Text(isRegistering ? "Create Your Account" : "Welcome Back")
                    .font(.title3)
                    .foregroundColor(.secondary)
                
                Spacer()
                
                if !showingVerification {
                    // Email Form
                    VStack(spacing: 16) {
                        Text(isRegistering ? "We'll send a verification code to your email" : "We'll send a login code to your email")
                            .font(.caption)
                            .foregroundColor(.secondary)
                            .multilineTextAlignment(.center)
                        
                        TextField("Email Address", text: $email)
                            .textContentType(.emailAddress)
                            .keyboardType(.emailAddress)
                            .autocapitalization(.none)
                            .textFieldStyle(.roundedBorder)
                        
                        Button(action: handleAuth) {
                            if isLoading {
                                ProgressView()
                                    .progressViewStyle(.circular)
                                    .tint(.white)
                            } else {
                                Text(isRegistering ? "Send Verification Code" : "Send Login Code")
                                    .fontWeight(.semibold)
                                    .frame(maxWidth: .infinity)
                            }
                        }
                        .buttonStyle(.borderedProminent)
                        .disabled(isLoading || email.isEmpty)
                        
                        Button(action: {
                            isRegistering.toggle()
                            errorMessage = ""
                        }) {
                            Text(isRegistering ? "Already have an account? Sign In" : "Don't have an account? Sign Up")
                                .font(.footnote)
                        }
                    }
                } else {
                    // Verification Code Form
                    VStack(spacing: 16) {
                        Text("Check Your Email")
                            .font(.title2)
                            .fontWeight(.semibold)
                        
                        Text("A verification code has been sent to:")
                            .font(.subheadline)
                            .foregroundColor(.secondary)
                        
                        Text(email)
                            .font(.subheadline)
                            .fontWeight(.semibold)
                        
                        TextField("6-Digit Code", text: $verificationCode)
                            .textContentType(.oneTimeCode)
                            .keyboardType(.numberPad)
                            .textFieldStyle(.roundedBorder)
                            .multilineTextAlignment(.center)
                            .font(.title2)
                        
                        Button(action: handleVerification) {
                            if isLoading {
                                ProgressView()
                                    .progressViewStyle(.circular)
                                    .tint(.white)
                            } else {
                                Text("Verify & Continue")
                                    .fontWeight(.semibold)
                                    .frame(maxWidth: .infinity)
                            }
                        }
                        .buttonStyle(.borderedProminent)
                        .disabled(isLoading || verificationCode.count != 6)
                        
                        Button("Use Different Email") {
                            showingVerification = false
                            verificationCode = ""
                            errorMessage = ""
                        }
                        .font(.footnote)
                    }
                }
                
                if !errorMessage.isEmpty {
                    Text(errorMessage)
                        .font(.caption)
                        .foregroundColor(.red)
                        .padding()
                        .background(Color.red.opacity(0.1))
                        .cornerRadius(8)
                }
                
                Spacer()
            }
            .padding()
            .navigationBarTitleDisplayMode(.inline)
            .onAppear {
                print("🔍 LoginView appeared")
            }
        }
    }
    
    private func handleAuth() {
        isLoading = true
        errorMessage = ""
        
        Task {
            do {
                if isRegistering {
                    _ = try await AuthService.register(email: email)
                } else {
                    _ = try await AuthService.login(email: email)
                }
                
                await MainActor.run {
                    showingVerification = true
                    isLoading = false
                }
            } catch {
                await MainActor.run {
                    errorMessage = error.localizedDescription
                    isLoading = false
                }
            }
        }
    }
    
    private func handleVerification() {
        isLoading = true
        errorMessage = ""
        
        Task {
            do {
                let result = try await AuthService.verify(email: email, code: verificationCode)
                print("Verified: \(result.email)")
                
                // Perform sync after successful login
                print("🔄 Starting sync...")
                do {
                    try await SyncManager.shared.performFullSync()
                    print("✅ Sync completed successfully")
                } catch {
                    print("⚠️ Sync failed: \(error.localizedDescription)")
                    // Don't block login if sync fails
                }
                
                await MainActor.run {
                    isAuthenticated = true
                    isLoading = false
                }
            } catch {
                await MainActor.run {
                    errorMessage = error.localizedDescription
                    isLoading = false
                }
            }
        }
    }
}

#Preview {
    LoginView(isAuthenticated: .constant(false))
}

