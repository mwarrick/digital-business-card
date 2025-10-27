//
//  ForgotPasswordView.swift
//  ShareMyCard
//
//  Password reset flow for forgotten passwords
//

import SwiftUI

struct ForgotPasswordView: View {
    @State private var email = ""
    @State private var resetCode = ""
    @State private var newPassword = ""
    @State private var confirmPassword = ""
    @State private var isLoading = false
    @State private var errorMessage = ""
    @State private var successMessage = ""
    @State private var step: ResetStep = .email
    
    @Environment(\.dismiss) private var dismiss
    
    enum ResetStep {
        case email
        case code
        case newPassword
    }
    
    var body: some View {
        NavigationView {
            VStack(spacing: 20) {
                // Header
                VStack(spacing: 8) {
                    Image(systemName: "key.horizontal.fill")
                        .imageScale(.large)
                        .foregroundStyle(.blue)
                        .font(.system(size: 50))
                    
                    Text("Reset Password")
                        .font(.title)
                        .fontWeight(.bold)
                    
                    Text(stepDescription)
                        .font(.subheadline)
                        .foregroundColor(.secondary)
                        .multilineTextAlignment(.center)
                }
                .padding(.top)
                
                Spacer()
                
                switch step {
                case .email:
                    emailStepView
                case .code:
                    codeStepView
                case .newPassword:
                    newPasswordStepView
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
                    Button("Cancel") {
                        dismiss()
                    }
                }
            }
        }
    }
    
    private var stepDescription: String {
        switch step {
        case .email:
            return "Enter your email address to receive a reset code"
        case .code:
            return "Enter the 6-digit code sent to your email"
        case .newPassword:
            return "Create your new password"
        }
    }
    
    private var emailStepView: some View {
        VStack(spacing: 16) {
            TextField("Email Address", text: $email)
                .textContentType(.emailAddress)
                .keyboardType(.emailAddress)
                .autocapitalization(.none)
                .textFieldStyle(.roundedBorder)
            
            Button(action: handleEmailSubmit) {
                if isLoading {
                    ProgressView()
                        .progressViewStyle(.circular)
                        .tint(.white)
                } else {
                    Text("Send Reset Code")
                        .fontWeight(.semibold)
                        .frame(maxWidth: .infinity)
                }
            }
            .buttonStyle(.borderedProminent)
            .disabled(isLoading || email.isEmpty)
        }
    }
    
    private var codeStepView: some View {
        VStack(spacing: 16) {
            Text("Check Your Email")
                .font(.title2)
                .fontWeight(.semibold)
            
            Text("A reset code has been sent to:")
                .font(.subheadline)
                .foregroundColor(.secondary)
            
            Text(email)
                .font(.subheadline)
                .fontWeight(.semibold)
            
            TextField("6-Digit Code", text: $resetCode)
                .textContentType(.oneTimeCode)
                .keyboardType(.numberPad)
                .textFieldStyle(.roundedBorder)
                .multilineTextAlignment(.center)
                .font(.title2)
            
            Button(action: handleCodeSubmit) {
                if isLoading {
                    ProgressView()
                        .progressViewStyle(.circular)
                        .tint(.white)
                } else {
                    Text("Verify Code")
                        .fontWeight(.semibold)
                        .frame(maxWidth: .infinity)
                }
            }
            .buttonStyle(.borderedProminent)
            .disabled(isLoading || resetCode.count != 6)
            
            Button("Use Different Email") {
                step = .email
                resetCode = ""
                errorMessage = ""
            }
            .font(.footnote)
        }
    }
    
    private var newPasswordStepView: some View {
        VStack(spacing: 16) {
            Text("Create New Password")
                .font(.title2)
                .fontWeight(.semibold)
            
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
            
            Button(action: handleNewPasswordSubmit) {
                if isLoading {
                    ProgressView()
                        .progressViewStyle(.circular)
                        .tint(.white)
                } else {
                    Text("Reset Password")
                        .fontWeight(.semibold)
                        .frame(maxWidth: .infinity)
                }
            }
            .buttonStyle(.borderedProminent)
            .disabled(isLoading || newPassword.isEmpty || confirmPassword.isEmpty || newPassword != confirmPassword)
        }
    }
    
    private func handleEmailSubmit() {
        isLoading = true
        errorMessage = ""
        successMessage = ""
        
        Task {
            do {
                try await AuthService.requestPasswordReset(email: email)
                await MainActor.run {
                    step = .code
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
    
    private func handleCodeSubmit() {
        isLoading = true
        errorMessage = ""
        successMessage = ""
        
        Task {
            // For now, just proceed to password step
            // In a real implementation, you might verify the code first
            await MainActor.run {
                step = .newPassword
                isLoading = false
            }
        }
    }
    
    private func handleNewPasswordSubmit() {
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
                try await AuthService.resetPassword(email: email, code: resetCode, newPassword: newPassword)
                await MainActor.run {
                    successMessage = "Password reset successfully!"
                    isLoading = false
                    
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

#Preview {
    ForgotPasswordView()
}
