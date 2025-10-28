//
//  ContentView.swift
//  ShareMyCard
//
//  Main content view with tab navigation
//

import SwiftUI

struct ContentView: View {
    @StateObject private var dataManager = DataManager.shared
    @State private var selectedTab = 0
    
    var body: some View {
        TabView(selection: $selectedTab) {
            // Business Cards Tab
            BusinessCardsTabView()
                .tabItem {
                    Image(systemName: "creditcard")
                    Text("Cards")
                }
                .tag(0)
            
            // Contacts Tab
            ContactsDashboardView()
                .tabItem {
                    Image(systemName: "person.2")
                    Text("Contacts")
                }
                .tag(1)
            
            // Profile Tab
            ProfileTabView()
                .tabItem {
                    Image(systemName: "person.circle")
                    Text("Profile")
                }
                .tag(2)
        }
        .onAppear {
            // Trigger sync when app starts to ensure data is up to date
            Task {
                do {
                    try await SyncManager.shared.performFullSync()
                } catch {
                    print("⚠️ Initial sync failed: \(error)")
                }
            }
        }
    }
}

// MARK: - Business Cards Tab View

struct BusinessCardsTabView: View {
    @StateObject private var dataManager = DataManager.shared
    @State private var showingBusinessCardList = false
    @State private var showingPasswordSettings = false
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
                    
                    Button("Logout") {
                        // Dismiss any presented sheets first
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
        .sheet(isPresented: $showingBusinessCardList) {
            BusinessCardListView()
        }
        .sheet(isPresented: $showingPasswordSettings) {
            PasswordSettingsView()
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

// MARK: - Profile Tab View

struct ProfileTabView: View {
    @State private var showingPasswordSettings = false
    
    var body: some View {
        NavigationView {
            VStack(spacing: 24) {
                Spacer()
                
                Image(systemName: "person.circle.fill")
                    .font(.system(size: 80))
                    .foregroundColor(.blue)
                
                Text("Profile")
                    .font(.largeTitle)
                    .fontWeight(.bold)
                
                Text("Manage your account settings")
                    .font(.subheadline)
                    .foregroundColor(.secondary)
                
                Spacer()
                
                Button(action: {
                    showingPasswordSettings = true
                }) {
                    HStack {
                        Image(systemName: "gear")
                        Text("Settings")
                    }
                    .font(.headline)
                    .foregroundColor(.white)
                    .frame(maxWidth: .infinity)
                    .padding()
                    .background(Color.blue)
                    .cornerRadius(12)
                }
                .padding(.horizontal, 32)
                
                Spacer()
            }
            .padding()
            .navigationTitle("Profile")
            .sheet(isPresented: $showingPasswordSettings) {
                PasswordSettingsView()
            }
        }
    }
}

#Preview {
    ContentView()
}