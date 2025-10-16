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
    @State private var password = ""
    @State private var isRegistering = false
    @State private var showingVerification = false
    @State private var showingPassword = false
    @State private var hasPassword = false
    @State private var isLoading = false
    @State private var errorMessage = ""
    @State private var showingForgotPassword = false
    
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
                
                if !showingVerification && !showingPassword {
                    // Email Form
                    VStack(spacing: 16) {
                        Text(isRegistering ? "We'll send a verification code to your email" : "We'll check if you have a password set or send a login code")
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
                                Text(isRegistering ? "Send Verification Code" : "Continue")
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
                } else if showingPassword {
                    // Password Form
                    VStack(spacing: 16) {
                        Text("Enter Your Password")
                            .font(.title2)
                            .fontWeight(.semibold)
                        
                        Text("Password for \(email)")
                            .font(.subheadline)
                            .foregroundColor(.secondary)
                        
                        SecureField("Password", text: $password)
                            .textContentType(.password)
                            .textFieldStyle(.roundedBorder)
                        
                        Button(action: handlePasswordLogin) {
                            if isLoading {
                                ProgressView()
                                    .progressViewStyle(.circular)
                                    .tint(.white)
                            } else {
                                Text("Sign In")
                                    .fontWeight(.semibold)
                                    .frame(maxWidth: .infinity)
                            }
                        }
                        .buttonStyle(.borderedProminent)
                        .disabled(isLoading || password.isEmpty)
                        
                        Button("Use Email Code Instead") {
                            sendEmailCode()
                        }
                        .font(.footnote)
                        
                        Button("Forgot Password?") {
                            showingForgotPassword = true
                        }
                        .font(.footnote)
                        .foregroundColor(.blue)
                        
                        Button("Use Different Email") {
                            showingPassword = false
                            showingVerification = false
                            password = ""
                            errorMessage = ""
                        }
                        .font(.footnote)
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
                        
                        if hasPassword {
                            Button("Use Password Instead") {
                                showingVerification = false
                                showingPassword = true
                                verificationCode = ""
                                errorMessage = ""
                            }
                            .font(.footnote)
                        }
                        
                        Button("Use Different Email") {
                            showingVerification = false
                            showingPassword = false
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
            .sheet(isPresented: $showingForgotPassword) {
                ForgotPasswordView()
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
                    await MainActor.run {
                        showingVerification = true
                        isLoading = false
                    }
                } else {
                    let response = try await AuthService.login(email: email)
                    await MainActor.run {
                        hasPassword = response.hasPassword
                        if response.hasPassword {
                            showingPassword = true
                        } else {
                            showingVerification = true
                        }
                        isLoading = false
                    }
                }
            } catch {
                await MainActor.run {
                    errorMessage = error.localizedDescription
                    isLoading = false
                }
            }
        }
    }
    
    private func sendEmailCode() {
        isLoading = true
        errorMessage = ""
        
        Task {
            do {
                _ = try await AuthService.login(email: email, forceEmailCode: true)
                await MainActor.run {
                    showingPassword = false
                    showingVerification = true
                    password = ""
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
    
    private func handlePasswordLogin() {
        isLoading = true
        errorMessage = ""
        
        Task {
            do {
                let result = try await AuthService.verify(email: email, password: password)
                print("Password login successful: \(result.email)")
                
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

