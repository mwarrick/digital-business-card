//
//  ContentView.swift
//  ShareMyCard
//
//  Created by Mark Warrick on 10/9/25.
//

import SwiftUI

struct ContentView: View {
    @StateObject private var dataManager = DataManager.shared
    @State private var showingCreationForm = false
    @State private var showingBusinessCardList = false
    @State private var isSyncing = false
    @State private var syncMessage = ""
    
    var body: some View {
        NavigationView {
            VStack(spacing: 24) {
                Spacer()
                
                // App Icon and Title
                Image(systemName: "square.stack.3d.up.fill")
                    .font(.system(size: 80))
                    .foregroundStyle(
                        LinearGradient(
                            colors: [.blue, .purple],
                            startPoint: .topLeading,
                            endPoint: .bottomTrailing
                        )
                    )
                    .padding(.bottom, 8)
                
                Text("ShareMyCard")
                    .font(.largeTitle)
                    .fontWeight(.bold)
                
                Text("Your Digital Business Cards")
                    .font(.subheadline)
                    .foregroundColor(.secondary)
                
                // Card Count
                if dataManager.businessCards.count > 0 {
                    Text("\(dataManager.businessCards.count) \(dataManager.businessCards.count == 1 ? "Card" : "Cards")")
                        .font(.title2)
                        .fontWeight(.semibold)
                        .foregroundColor(.blue)
                        .padding(.top, 8)
                }
                
                Spacer()
                
                // Action Buttons
                VStack(spacing: 16) {
                    Button("View All Business Cards") {
                        showingBusinessCardList = true
                    }
                    .buttonStyle(.borderedProminent)
                    .controlSize(.large)
                    
                    Button("Create New Business Card") {
                        showingCreationForm = true
                    }
                    .buttonStyle(.bordered)
                    .controlSize(.large)
                    
                    Divider()
                        .padding(.vertical, 8)
                    
                    // Sync Button
                    Button(action: {
                        performSync()
                    }) {
                        HStack {
                            if isSyncing {
                                ProgressView()
                                    .progressViewStyle(CircularProgressViewStyle())
                                    .scaleEffect(0.8)
                            } else {
                                Image(systemName: "arrow.triangle.2.circlepath")
                            }
                            Text(isSyncing ? "Syncing..." : "Sync with Server")
                        }
                    }
                    .buttonStyle(.bordered)
                    .disabled(isSyncing)
                    
                    if !syncMessage.isEmpty {
                        Text(syncMessage)
                            .font(.caption)
                            .foregroundColor(syncMessage.contains("✅") ? .green : .orange)
                            .padding(.horizontal)
                    }
                    
                    Divider()
                        .padding(.vertical, 8)
                    
                    Button("Logout") {
                        // Dismiss any presented sheets first
                        showingCreationForm = false
                        showingBusinessCardList = false
                        isSyncing = false
                        // Perform logout and notify root view to switch to LoginView
                        AuthService.logout()
                        DispatchQueue.main.async {
                            NotificationCenter.default.post(name: NSNotification.Name("UserLoggedOut"), object: nil)
                        }
                    }
                    .buttonStyle(.bordered)
                    .foregroundColor(.red)
                }
                .padding(.horizontal, 32)
                
                Spacer()
            }
            .padding()
            .navigationTitle("")
            .navigationBarTitleDisplayMode(.inline)
        }
        .sheet(isPresented: $showingCreationForm) {
            BusinessCardCreationView()
        }
        .sheet(isPresented: $showingBusinessCardList) {
            BusinessCardListView()
        }
        .onAppear {
            // Auto-sync on app startup
            performSync()
        }
    }
    
    private func performSync() {
        print("🔄 ContentView.performSync() - Starting sync")
        print("   🔑 Auth status: \(AuthService.isAuthenticated())")
        print("   📱 Local cards count: \(dataManager.businessCards.count)")
        
        isSyncing = true
        syncMessage = ""
        
        Task {
            do {
                print("📡 ContentView.performSync() - Calling SyncManager.performFullSync()")
                try await SyncManager.shared.performFullSync()
                
                print("✅ ContentView.performSync() - Sync completed successfully")
                await MainActor.run {
                    syncMessage = "✅ Sync completed"
                    isSyncing = false
                }
                
                // Clear the success message after 3 seconds
                try? await Task.sleep(nanoseconds: 3_000_000_000)
                await MainActor.run {
                    syncMessage = ""
                }
            } catch {
                print("❌ ContentView.performSync() - Sync failed with error: \(error)")
                print("   🔍 Error type: \(type(of: error))")
                print("   📝 Error description: \(error.localizedDescription)")
                
                await MainActor.run {
                    syncMessage = "⚠️ Sync failed: \(error.localizedDescription)"
                    isSyncing = false
                }
                
                // Clear the error message after 5 seconds
                try? await Task.sleep(nanoseconds: 5_000_000_000)
                await MainActor.run {
                    syncMessage = ""
                }
            }
        }
    }
}

#Preview {
    ContentView()
}
