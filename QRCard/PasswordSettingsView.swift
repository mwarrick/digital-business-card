//
//  PasswordSettingsView.swift
//  ShareMyCard
//
//  Password management view for setting and changing passwords
//

import SwiftUI

struct PasswordSettingsView: View {
    @State private var currentPassword = ""
    @State private var newPassword = ""
    @State private var confirmPassword = ""
    @State private var isLoading = false
    @State private var errorMessage = ""
    @State private var successMessage = ""
    @State private var hasPassword = false
    @State private var showingSetPassword = false
    @State private var checkingPasswordStatus = true
    
    @Environment(\.dismiss) private var dismiss
    
    var body: some View {
        NavigationView {
            VStack(spacing: 20) {
                // Header
                VStack(spacing: 8) {
                    Image(systemName: "lock.shield.fill")
                        .imageScale(.large)
                        .foregroundStyle(.blue)
                        .font(.system(size: 50))
                    
                    Text("Account Security")
                        .font(.title)
                        .fontWeight(.bold)
                    
                    Text("Manage your password settings")
                        .font(.subheadline)
                        .foregroundColor(.secondary)
                }
                .padding(.top)
                
                Spacer()
                
                if checkingPasswordStatus {
                    // Loading state
                    VStack(spacing: 16) {
                        ProgressView()
                            .progressViewStyle(CircularProgressViewStyle())
                            .scaleEffect(1.2)
                        
                        Text("Checking password status...")
                            .font(.subheadline)
                            .foregroundColor(.secondary)
                    }
                } else if !hasPassword {
                    // Set Password Form
                    VStack(spacing: 16) {
                        Text("Set Your Password")
                            .font(.title2)
                            .fontWeight(.semibold)
                        
                        Text("Create a password to enable faster login")
                            .font(.caption)
                            .foregroundColor(.secondary)
                            .multilineTextAlignment(.center)
                        
                        SecureField("New Password", text: $newPassword)
                            .textContentType(.newPassword)
                            .textFieldStyle(.roundedBorder)
                        
                        SecureField("Confirm Password", text: $confirmPassword)
                            .textContentType(.newPassword)
                            .textFieldStyle(.roundedBorder)
                        
                        // Password strength indicator
                        if !newPassword.isEmpty {
                            PasswordStrengthView(password: newPassword)
                        }
                        
                        Button(action: handleSetPassword) {
                            if isLoading {
                                ProgressView()
                                    .progressViewStyle(.circular)
                                    .tint(.white)
                            } else {
                                Text("Set Password")
                                    .fontWeight(.semibold)
                                    .frame(maxWidth: .infinity)
                            }
                        }
                        .buttonStyle(.borderedProminent)
                        .disabled(isLoading || newPassword.isEmpty || confirmPassword.isEmpty || newPassword != confirmPassword)
                    }
                } else {
                    // Change Password Form
                    VStack(spacing: 16) {
                        Text("Change Your Password")
                            .font(.title2)
                            .fontWeight(.semibold)
                        
                        SecureField("Current Password", text: $currentPassword)
                            .textContentType(.password)
                            .textFieldStyle(.roundedBorder)
                        
                        SecureField("New Password", text: $newPassword)
                            .textContentType(.newPassword)
                            .textFieldStyle(.roundedBorder)
                        
                        SecureField("Confirm New Password", text: $confirmPassword)
                            .textContentType(.newPassword)
                            .textFieldStyle(.roundedBorder)
                        
                        // Password strength indicator
                        if !newPassword.isEmpty {
                            PasswordStrengthView(password: newPassword)
                        }
                        
                        Button(action: handleChangePassword) {
                            if isLoading {
                                ProgressView()
                                    .progressViewStyle(.circular)
                                    .tint(.white)
                            } else {
                                Text("Change Password")
                                    .fontWeight(.semibold)
                                    .frame(maxWidth: .infinity)
                            }
                        }
                        .buttonStyle(.borderedProminent)
                        .disabled(isLoading || currentPassword.isEmpty || newPassword.isEmpty || confirmPassword.isEmpty || newPassword != confirmPassword)
                    }
                }
                
                // Messages
                if !errorMessage.isEmpty {
                    Text(errorMessage)
                        .font(.caption)
                        .foregroundColor(.red)
                        .padding()
                        .background(Color.red.opacity(0.1))
                        .cornerRadius(8)
                }
                
                if !successMessage.isEmpty {
                    Text(successMessage)
                        .font(.caption)
                        .foregroundColor(.green)
                        .padding()
                        .background(Color.green.opacity(0.1))
                        .cornerRadius(8)
                }
                
                Spacer()
            }
            .padding()
            .navigationBarTitleDisplayMode(.inline)
            .navigationBarBackButtonHidden(true)
            .toolbar {
                ToolbarItem(placement: .navigationBarLeading) {
                    Button("Done") {
                        dismiss()
                    }
                }
            }
            .onAppear {
                checkPasswordStatus()
            }
        }
    }
    
    private func checkPasswordStatus() {
        Task {
            do {
                let passwordStatus = try await AuthService.checkPasswordStatus()
                await MainActor.run {
                    hasPassword = passwordStatus
                    checkingPasswordStatus = false
                }
            } catch {
                await MainActor.run {
                    // If we can't check status, assume no password for safety
                    hasPassword = false
                    checkingPasswordStatus = false
                    errorMessage = "Could not check password status"
                }
            }
        }
    }
    
    private func handleSetPassword() {
        guard newPassword == confirmPassword else {
            errorMessage = "Passwords do not match"
            return
        }
        
        guard isValidPassword(newPassword) else {
            errorMessage = "Password must be at least 8 characters with uppercase, lowercase, and number"
            return
        }
        
        isLoading = true
        errorMessage = ""
        successMessage = ""
        
        Task {
            do {
                try await AuthService.setPassword(password: newPassword)
                await MainActor.run {
                    successMessage = "Password set successfully!"
                    hasPassword = true
                    isLoading = false
                    
                    // Clear form
                    newPassword = ""
                    confirmPassword = ""
                    
                    // Auto-dismiss after success
                    DispatchQueue.main.asyncAfter(deadline: .now() + 1.5) {
                        dismiss()
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
    
    private func handleChangePassword() {
        guard newPassword == confirmPassword else {
            errorMessage = "Passwords do not match"
            return
        }
        
        guard isValidPassword(newPassword) else {
            errorMessage = "Password must be at least 8 characters with uppercase, lowercase, and number"
            return
        }
        
        isLoading = true
        errorMessage = ""
        successMessage = ""
        
        Task {
            do {
                try await AuthService.changePassword(currentPassword: currentPassword, newPassword: newPassword)
                await MainActor.run {
                    successMessage = "Password changed successfully!"
                    isLoading = false
                    
                    // Clear form
                    currentPassword = ""
                    newPassword = ""
                    confirmPassword = ""
                    
                    // Auto-dismiss after success
                    DispatchQueue.main.asyncAfter(deadline: .now() + 1.5) {
                        dismiss()
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
    
    private func isValidPassword(_ password: String) -> Bool {
        guard password.count >= 8 else { return false }
        guard password.rangeOfCharacter(from: .uppercaseLetters) != nil else { return false }
        guard password.rangeOfCharacter(from: .lowercaseLetters) != nil else { return false }
        guard password.rangeOfCharacter(from: .decimalDigits) != nil else { return false }
        return true
    }
}

struct PasswordStrengthView: View {
    let password: String
    
    private var strength: PasswordStrength {
        var score = 0
        
        if password.count >= 8 { score += 1 }
        if password.rangeOfCharacter(from: .uppercaseLetters) != nil { score += 1 }
        if password.rangeOfCharacter(from: .lowercaseLetters) != nil { score += 1 }
        if password.rangeOfCharacter(from: .decimalDigits) != nil { score += 1 }
        if password.rangeOfCharacter(from: CharacterSet(charactersIn: "!@#$%^&*()_+-=[]{}|;:,.<>?")) != nil { score += 1 }
        
        switch score {
        case 0...2: return .weak
        case 3: return .fair
        case 4: return .good
        default: return .strong
        }
    }
    
    var body: some View {
        VStack(alignment: .leading, spacing: 4) {
            HStack {
                Text("Password Strength:")
                    .font(.caption)
                    .foregroundColor(.secondary)
                
                Spacer()
                
                Text(strength.rawValue.capitalized)
                    .font(.caption)
                    .fontWeight(.semibold)
                    .foregroundColor(strength.color)
            }
            
            ProgressView(value: Double(strength.score), total: 5)
                .progressViewStyle(LinearProgressViewStyle(tint: strength.color))
                .scaleEffect(y: 2)
        }
    }
}

enum PasswordStrength: String, CaseIterable {
    case weak = "weak"
    case fair = "fair"
    case good = "good"
    case strong = "strong"
    
    var color: Color {
        switch self {
        case .weak: return .red
        case .fair: return .orange
        case .good: return .yellow
        case .strong: return .green
        }
    }
    
    var score: Int {
        switch self {
        case .weak: return 2
        case .fair: return 3
        case .good: return 4
        case .strong: return 5
        }
    }
}

#Preview {
    PasswordSettingsView()
}
