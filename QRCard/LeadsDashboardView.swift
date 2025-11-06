//
//  LeadsDashboardView.swift
//  ShareMyCard
//
//  Leads list view
//

import SwiftUI

struct LeadsDashboardView: View {
    @StateObject private var viewModel = LeadsViewModel()
    @State private var selectedLead: Lead?
    
    var body: some View {
        NavigationView {
            VStack(spacing: 0) {
                // Search Bar
                searchBar
                
                // Leads List
                leadsList
            }
            .navigationTitle("Leads")
            .navigationBarTitleDisplayMode(.large)
            .refreshable {
                await viewModel.refreshFromServer()
            }
            .sheet(item: $selectedLead) { lead in
                LeadDetailsView(lead: lead, viewModel: viewModel)
            }
            .task {
                // Load leads when view appears
                viewModel.loadLeads()
            }
            .alert("Error", isPresented: .constant(viewModel.errorMessage != nil), presenting: viewModel.errorMessage) { _ in
                Button("OK") {
                    viewModel.errorMessage = nil
                }
            } message: { message in
                Text(message)
            }
        }
    }
    
    // MARK: - Search Bar
    
    private var searchBar: some View {
        HStack {
            Image(systemName: "magnifyingglass")
                .foregroundColor(.secondary)
            
            TextField("Search leads...", text: $viewModel.searchText)
                .textFieldStyle(PlainTextFieldStyle())
        }
        .padding()
        .background(Color(.systemBackground))
        .cornerRadius(10)
        .padding(.horizontal)
        .padding(.top, 8)
    }
    
    // MARK: - Leads List
    
    private var leadsList: some View {
        Group {
            if viewModel.isLoading {
                ProgressView("Loading leads...")
                    .frame(maxWidth: .infinity, maxHeight: .infinity)
            } else if viewModel.filteredLeads.isEmpty {
                emptyState
            } else {
                List {
                    ForEach(viewModel.filteredLeads) { lead in
                        LeadRowView(lead: lead) {
                            selectedLead = lead
                        }
                    }
                }
                .listStyle(PlainListStyle())
            }
        }
    }
    
    // MARK: - Empty State
    
    private var emptyState: some View {
        VStack(spacing: 16) {
            Image(systemName: "person.crop.circle.badge.plus")
                .font(.system(size: 60))
                .foregroundColor(.secondary)
            
            Text(viewModel.searchText.isEmpty ? "No Leads" : "No Matching Leads")
                .font(.title2)
                .fontWeight(.semibold)
            
            Text(viewModel.searchText.isEmpty 
                ? "Leads will appear here when people submit information through your business cards"
                : "Try adjusting your search terms")
                .font(.subheadline)
                .foregroundColor(.secondary)
                .multilineTextAlignment(.center)
                .padding(.horizontal)
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity)
        .padding()
    }
}

// MARK: - Lead Row View
struct LeadRowView: View {
    let lead: Lead
    let onTap: () -> Void
    
    var body: some View {
        Button(action: onTap) {
            HStack(spacing: 12) {
                // Status indicator
                Circle()
                    .fill(lead.isConverted ? Color.gray : Color.green)
                    .frame(width: 8, height: 8)
                
                VStack(alignment: .leading, spacing: 4) {
                    HStack {
                        Text(lead.displayName)
                            .font(.headline)
                            .foregroundColor(.primary)
                        
                        Spacer()
                        
                        if !lead.formattedDate.isEmpty {
                            Text(lead.formattedDate)
                                .font(.caption)
                                .foregroundColor(.secondary)
                        } else if !lead.relativeDate.isEmpty {
                            Text(lead.relativeDate)
                                .font(.caption)
                                .foregroundColor(.secondary)
                        }
                    }
                    
                    if let email = lead.emailPrimary {
                        Text(email)
                            .font(.subheadline)
                            .foregroundColor(.secondary)
                            .lineLimit(1)
                    } else if let phone = lead.workPhone ?? lead.mobilePhone {
                        Text(phone)
                            .font(.subheadline)
                            .foregroundColor(.secondary)
                            .lineLimit(1)
                    }
                    
                    if let company = lead.organizationName {
                        Text(company)
                            .font(.caption)
                            .foregroundColor(.secondary)
                            .lineLimit(1)
                    }
                    
                    HStack(spacing: 4) {
                        Text("From:")
                            .font(.caption2)
                            .foregroundColor(.secondary)
                        Text(lead.cardDisplayName)
                            .font(.caption2)
                            .foregroundColor(.secondary)
                    }
                }
                
                Spacer()
                
                if lead.isConverted {
                    Image(systemName: "checkmark.circle.fill")
                        .foregroundColor(.gray)
                        .font(.title3)
                } else {
                    Image(systemName: "chevron.right")
                        .foregroundColor(.secondary)
                        .font(.caption)
                }
            }
            .padding(.vertical, 4)
        }
        .buttonStyle(PlainButtonStyle())
    }
}

#Preview {
    LeadsDashboardView()
}


