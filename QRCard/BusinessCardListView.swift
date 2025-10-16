//
//  BusinessCardListView.swift
//  ShareMyCard
//
//  Created by Mark Warrick on 10/10/25.
//

import SwiftUI

struct BusinessCardListView: View {
    @StateObject private var dataManager = DataManager.shared
    @State private var showingCreationForm = false
    
    // Simple sort on company name (A→Z)
    private var sortedCards: [BusinessCardEntity] {
        dataManager.businessCards.sorted {
            ($0.companyName ?? "").localizedCaseInsensitiveCompare($1.companyName ?? "") == .orderedAscending
        }
    }
    
    var body: some View {
        NavigationView {
            List {
                ForEach(sortedCards, id: \.id) { cardEntity in
                    BusinessCardRowView(cardEntity: cardEntity) {
                        // This closure is no longer used since edit is handled in BusinessCardRowView
                    }
                }
                .onDelete(perform: deleteBusinessCards)
            }
            .navigationBarTitleDisplayMode(.large)
            .toolbar {
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button {
                        showingCreationForm = true
                    } label: {
                        Image(systemName: "plus")
                    }
                }
            }
            .sheet(isPresented: $showingCreationForm) {
                BusinessCardCreationView()
            }
            .overlay {
                if dataManager.businessCards.isEmpty {
                    VStack(spacing: 16) {
                        Image(systemName: "person.crop.rectangle")
                            .font(.system(size: 60))
                            .foregroundColor(.secondary)
                        
                        Text("No Business Cards")
                            .font(.title2)
                            .fontWeight(.semibold)
                        
                        Text("Create your first business card to get started")
                            .font(.subheadline)
                            .foregroundColor(.secondary)
                            .multilineTextAlignment(.center)
                        
                        Button("Create Business Card") {
                            showingCreationForm = true
                        }
                        .buttonStyle(.borderedProminent)
                    }
                    .padding()
                }
            }
        }
    }
    
    private func deleteBusinessCards(offsets: IndexSet) {
        for index in offsets {
            let cardEntity = dataManager.businessCards[index]
            let serverCardId = cardEntity.serverCardId
            
            // Delete from local Core Data
            dataManager.deleteBusinessCard(cardEntity)
            
            // Delete from server if it has a server ID
            if let serverId = serverCardId {
                Task {
                    do {
                        print("🔄 Auto-sync: Deleting card from server...")
                        try await CardService.deleteCard(id: serverId)
                        print("✅ Auto-sync: Card deleted from server")
                    } catch {
                        print("⚠️ Auto-sync delete failed: \(error.localizedDescription)")
                        // Don't block the UI - manual sync can clean up later
                    }
                }
            }
        }
    }
}

struct BusinessCardRowView: View {
    @ObservedObject var cardEntity: BusinessCardEntity
    let onTap: () -> Void
    @State private var showingQRCode = false
    @State private var showingBusinessCardDisplay = false
    @State private var showingEditView = false
    
    private var businessCard: BusinessCard {
        DataManager.shared.businessCardEntityToBusinessCard(cardEntity)
    }
    
    var body: some View {
        VStack(spacing: 0) {
            // Main Content Row
            HStack(spacing: 12) {
                // Profile Photo - Tappable to view full screen
                Button {
                    showingBusinessCardDisplay = true
                } label: {
                    Group {
                        if let profilePhotoData = cardEntity.profilePhoto,
                           let uiImage = UIImage(data: profilePhotoData) {
                            Image(uiImage: uiImage)
                                .resizable()
                                .aspectRatio(contentMode: .fill)
                                .frame(width: 60, height: 60)
                                .clipShape(Circle())
                        } else {
                            Circle()
                                .fill(LinearGradient(
                                    colors: [.blue.opacity(0.3), .purple.opacity(0.3)],
                                    startPoint: .topLeading,
                                    endPoint: .bottomTrailing
                                ))
                                .frame(width: 60, height: 60)
                                .overlay {
                                    Image(systemName: "person.fill")
                                        .foregroundColor(.blue)
                                        .font(.title2)
                                }
                        }
                    }
                }
                .buttonStyle(PlainButtonStyle())
                
                // Card Information - Stacked vertically for better readability
                VStack(alignment: .leading, spacing: 4) {
                    Text(businessCard.fullName)
                        .font(.headline)
                        .foregroundColor(.primary)
                        .lineLimit(1)
                    
                    if let jobTitle = businessCard.jobTitle {
                        Text(jobTitle)
                            .font(.subheadline)
                            .foregroundColor(.secondary)
                            .lineLimit(1)
                    }
                    
                    if let companyName = businessCard.companyName {
                        Text(companyName)
                            .font(.caption)
                            .foregroundColor(.secondary)
                            .lineLimit(1)
                    }
                    
                    // Contact summary
                    HStack(spacing: 4) {
                        Image(systemName: "phone.fill")
                            .font(.caption2)
                            .foregroundColor(.blue)
                        Text(formatPhoneForDisplay(businessCard.primaryPhone))
                            .font(.caption)
                            .foregroundColor(.secondary)
                            .lineLimit(1)
                    }
                }
                
                Spacer(minLength: 8)
            }
            .padding(.vertical, 8)
            
            // Action Buttons Bar
            HStack(spacing: 0) {
                Button {
                    showingBusinessCardDisplay = true
                } label: {
                    HStack(spacing: 4) {
                        Image(systemName: "eye.fill")
                        Text("View")
                    }
                    .font(.caption)
                    .foregroundColor(.white)
                    .frame(maxWidth: .infinity)
                    .padding(.vertical, 8)
                    .background(Color.green)
                }
                .buttonStyle(PlainButtonStyle())
                
                Button {
                    showingQRCode = true
                } label: {
                    HStack(spacing: 4) {
                        Image(systemName: "qrcode")
                        Text("QR")
                    }
                    .font(.caption)
                    .foregroundColor(.white)
                    .frame(maxWidth: .infinity)
                    .padding(.vertical, 8)
                    .background(Color.blue)
                }
                .buttonStyle(PlainButtonStyle())
                
                Button {
                    print("BusinessCardRowView: Edit button tapped")
                    showingEditView = true
                } label: {
                    HStack(spacing: 4) {
                        Image(systemName: "pencil")
                        Text("Edit")
                    }
                    .font(.caption)
                    .foregroundColor(.white)
                    .frame(maxWidth: .infinity)
                    .padding(.vertical, 8)
                    .background(Color.orange)
                }
                .buttonStyle(PlainButtonStyle())
            }
        }
        .background(Color(.systemBackground))
        .cornerRadius(8)
        .overlay(
            RoundedRectangle(cornerRadius: 8)
                .stroke(Color(.separator), lineWidth: 0.5)
        )
        .padding(.horizontal, 4)
        .padding(.vertical, 4)
        .sheet(isPresented: $showingQRCode) {
            // Re-compute businessCard when sheet is presented to get latest data including downloaded images
            let currentBusinessCard = DataManager.shared.businessCardEntityToBusinessCard(cardEntity)
            QRCodeDisplayView(businessCard: currentBusinessCard)
        }
        .sheet(isPresented: $showingBusinessCardDisplay) {
            // Re-compute businessCard when sheet is presented to get latest data including downloaded images
            let currentBusinessCard = DataManager.shared.businessCardEntityToBusinessCard(cardEntity)
            BusinessCardDisplayView(businessCard: currentBusinessCard)
        }
        .sheet(isPresented: $showingEditView) {
            BusinessCardEditView(businessCardEntity: cardEntity)
        }
    }
    
    // Helper function to format phone number for display
    private func formatPhoneForDisplay(_ phone: String) -> String {
        // Remove all non-numeric characters
        let cleaned = phone.components(separatedBy: CharacterSet.decimalDigits.inverted).joined()
        
        // If it's a US number (10 or 11 digits), format it nicely
        if cleaned.count == 10 {
            let area = cleaned.prefix(3)
            let prefix = cleaned.dropFirst(3).prefix(3)
            let line = cleaned.suffix(4)
            return "(\(area)) \(prefix)-\(line)"
        } else if cleaned.count == 11 && cleaned.first == "1" {
            let area = cleaned.dropFirst().prefix(3)
            let prefix = cleaned.dropFirst(4).prefix(3)
            let line = cleaned.suffix(4)
            return "+1 (\(area)) \(prefix)-\(line)"
        }
        
        // Otherwise return as-is
        return phone
    }
}

#Preview {
    BusinessCardListView()
}
