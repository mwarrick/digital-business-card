# iOS Leads & Contacts Integration Plan

## Overview

Integrate the complete leads and contacts management system from the web app into the iOS ShareMyCard app, providing users with full lead capture, contact management, and conversion workflow capabilities on mobile devices.

## Current Web Functionality Analysis

### Leads Management (Web)
- **Lead Capture**: Public forms accessible via business card URLs
- **Lead Dashboard**: View, search, filter, and manage captured leads
- **Lead Details**: Comprehensive lead information display
- **Lead Conversion**: Convert leads to contacts with one-click
- **Lead Analytics**: Statistics and conversion tracking
- **Lead Deletion**: Remove leads with confirmation

### Contacts Management (Web)
- **Contact Creation**: Manual contact creation and QR code scanning
- **Contact Dashboard**: View, search, filter contacts by source
- **Contact Details**: Full contact information with lead history
- **Contact Editing**: Update contact information
- **Contact Deletion**: Remove contacts with cascade cleanup
- **Source Tracking**: Track contact origin (manual, converted, QR scanned)
- **Contact Analytics**: Statistics and source breakdown

### API Endpoints (Web)
- **Leads API**: `/api/leads/` (GET, PUT, DELETE)
- **Contacts API**: `/api/contacts/` (GET, POST, PUT, DELETE)
- **Lead Conversion**: `/api/leads/convert.php`
- **QR Contact Creation**: `/user/api/create-contact-from-qr.php`
- **Lead Capture**: `/api/leads/capture.php` (public endpoint)

## iOS Implementation Plan

### Phase 1: Data Models & Core Data Integration

#### 1.1 Lead Data Model
**File**: `Lead.swift`

```swift
import Foundation
import CoreData

@objc(Lead)
public class Lead: NSManagedObject {
    @NSManaged public var id: Int32
    @NSManaged public var idBusinessCard: Int32
    @NSManaged public var firstName: String
    @NSManaged public var lastName: String
    @NSManaged public var email: String?
    @NSManaged public var phone: String?
    @NSManaged public var company: String?
    @NSManaged public var jobTitle: String?
    @NSManaged public var message: String?
    @NSManaged public var notes: String?
    @NSManaged public var source: String
    @NSManaged public var status: String // "new" or "converted"
    @NSManaged public var capturedAt: Date
    @NSManaged public var updatedAt: Date
    @NSManaged public var ipAddress: String?
    @NSManaged public var userAgent: String?
    @NSManaged public var referrer: String?
    
    // Relationships
    @NSManaged public var businessCard: BusinessCard?
    @NSManaged public var convertedContact: Contact?
}

extension Lead {
    var fullName: String {
        return "\(firstName) \(lastName)"
    }
    
    var isConverted: Bool {
        return status == "converted"
    }
    
    var displayStatus: String {
        return isConverted ? "Converted" : "New"
    }
}
```

#### 1.2 Contact Data Model Enhancement
**File**: `Contact.swift` (enhance existing)

```swift
import Foundation
import CoreData

@objc(Contact)
public class Contact: NSManagedObject {
    // Existing fields...
    @NSManaged public var id: Int32
    @NSManaged public var idUser: String
    @NSManaged public var idLead: Int32
    @NSManaged public var firstName: String
    @NSManaged public var lastName: String
    @NSManaged public var emailPrimary: String?
    @NSManaged public var workPhone: String?
    @NSManaged public var mobilePhone: String?
    @NSManaged public var organizationName: String?
    @NSManaged public var jobTitle: String?
    @NSManaged public var streetAddress: String?
    @NSManaged public var city: String?
    @NSManaged public var state: String?
    @NSManaged public var zipCode: String?
    @NSManaged public var country: String?
    @NSManaged public var websiteUrl: String?
    @NSManaged public var commentsFromLead: String?
    @NSManaged public var source: String? // "manual", "converted", "qr_scan"
    @NSManaged public var sourceMetadata: String? // JSON string
    @NSManaged public var createdAt: Date
    @NSManaged public var updatedAt: Date
    
    // Relationships
    @NSManaged public var lead: Lead?
    @NSManaged public var businessCard: BusinessCard?
}

extension Contact {
    var fullName: String {
        return "\(firstName) \(lastName)"
    }
    
    var sourceType: String {
        return source ?? "manual"
    }
    
    var displaySource: String {
        switch sourceType {
        case "converted": return "From Lead"
        case "qr_scan": return "QR Scanned"
        case "manual": return "Manual"
        default: return "Unknown"
        }
    }
    
    var hasLeadHistory: Bool {
        return lead != nil
    }
}
```

#### 1.3 Core Data Model Updates
**File**: `CoreDataEntities.swift` (enhance existing)

```swift
// Add Lead entity to Core Data model
// Add source and sourceMetadata fields to Contact entity
// Add relationships between Lead and Contact
// Add relationships between Lead and BusinessCard
```

### Phase 2: API Integration

#### 2.1 Leads API Client
**File**: `LeadsAPIClient.swift`

```swift
import Foundation

class LeadsAPIClient {
    private let apiClient: APIClient
    
    init(apiClient: APIClient) {
        self.apiClient = apiClient
    }
    
    // MARK: - Leads Management
    
    func fetchLeads() async throws -> [LeadData] {
        // GET /api/leads/
        // Parse response and return LeadData array
    }
    
    func fetchLead(id: Int32) async throws -> LeadData {
        // GET /api/leads/get.php?id={id}
        // Parse response and return LeadData
    }
    
    func updateLead(id: Int32, data: LeadUpdateData) async throws -> LeadData {
        // PUT /api/leads/
        // Update lead with new data
    }
    
    func deleteLead(id: Int32) async throws {
        // DELETE /api/leads/
        // Remove lead from server
    }
    
    func convertLeadToContact(leadId: Int32) async throws -> ContactData {
        // POST /api/leads/convert.php
        // Convert lead to contact and return contact data
    }
}

// MARK: - Data Models

struct LeadData: Codable {
    let id: Int32
    let idBusinessCard: Int32
    let firstName: String
    let lastName: String
    let email: String?
    let phone: String?
    let company: String?
    let jobTitle: String?
    let message: String?
    let notes: String?
    let source: String
    let status: String
    let capturedAt: String
    let updatedAt: String
    let ipAddress: String?
    let userAgent: String?
    let referrer: String?
    let cardFirstName: String?
    let cardLastName: String?
    let cardCompany: String?
    let cardJobTitle: String?
    let convertedContact: ContactData?
}

struct LeadUpdateData: Codable {
    let firstName: String?
    let lastName: String?
    let email: String?
    let phone: String?
    let company: String?
    let jobTitle: String?
    let message: String?
    let notes: String?
}
```

#### 2.2 Contacts API Client Enhancement
**File**: `ContactsAPIClient.swift`

```swift
import Foundation

class ContactsAPIClient {
    private let apiClient: APIClient
    
    init(apiClient: APIClient) {
        self.apiClient = apiClient
    }
    
    // MARK: - Contacts Management
    
    func fetchContacts() async throws -> [ContactData] {
        // GET /api/contacts/
        // Parse response and return ContactData array
    }
    
    func fetchContact(id: Int32) async throws -> ContactData {
        // GET /user/api/get-contact.php?id={id}
        // Parse response and return ContactData
    }
    
    func createContact(data: ContactCreateData) async throws -> ContactData {
        // POST /api/contacts/
        // Create new contact and return data
    }
    
    func updateContact(id: Int32, data: ContactUpdateData) async throws -> ContactData {
        // PUT /api/contacts/
        // Update contact with new data
    }
    
    func deleteContact(id: Int32) async throws {
        // DELETE /api/contacts/
        // Remove contact from server
    }
    
    func createContactFromQR(data: QRContactData) async throws -> ContactData {
        // POST /user/api/create-contact-from-qr.php
        // Create contact from QR scan data
    }
}

// MARK: - Data Models

struct ContactData: Codable {
    let id: Int32
    let idUser: String
    let idLead: Int32?
    let firstName: String
    let lastName: String
    let emailPrimary: String?
    let workPhone: String?
    let mobilePhone: String?
    let organizationName: String?
    let jobTitle: String?
    let streetAddress: String?
    let city: String?
    let state: String?
    let zipCode: String?
    let country: String?
    let websiteUrl: String?
    let commentsFromLead: String?
    let source: String?
    let sourceMetadata: String?
    let createdAt: String
    let updatedAt: String
    let leadId: Int32?
    let cardFirstName: String?
    let cardLastName: String?
    let sourceType: String?
}

struct ContactCreateData: Codable {
    let firstName: String
    let lastName: String
    let emailPrimary: String?
    let workPhone: String?
    let mobilePhone: String?
    let organizationName: String?
    let jobTitle: String?
    let streetAddress: String?
    let city: String?
    let state: String?
    let zipCode: String?
    let country: String?
    let websiteUrl: String?
    let commentsFromLead: String?
}

struct ContactUpdateData: Codable {
    let firstName: String?
    let lastName: String?
    let emailPrimary: String?
    let workPhone: String?
    let mobilePhone: String?
    let organizationName: String?
    let jobTitle: String?
    let streetAddress: String?
    let city: String?
    let state: String?
    let zipCode: String?
    let country: String?
    let websiteUrl: String?
    let commentsFromLead: String?
}

struct QRContactData: Codable {
    let firstName: String?
    let lastName: String?
    let emailPrimary: String?
    let workPhone: String?
    let mobilePhone: String?
    let organizationName: String?
    let jobTitle: String?
    let streetAddress: String?
    let city: String?
    let state: String?
    let zipCode: String?
    let country: String?
    let websiteUrl: String?
    let commentsFromLead: String?
    let source: String
    let sourceMetadata: String
}
```

### Phase 3: User Interface Implementation

#### 3.1 Leads Dashboard View
**File**: `LeadsDashboardView.swift`

```swift
import SwiftUI

struct LeadsDashboardView: View {
    @StateObject private var viewModel = LeadsViewModel()
    @State private var searchText = ""
    @State private var selectedFilter: LeadFilter = .all
    @State private var showingLeadDetails = false
    @State private var selectedLead: Lead?
    
    enum LeadFilter: String, CaseIterable {
        case all = "All"
        case new = "New"
        case converted = "Converted"
    }
    
    var body: some View {
        NavigationView {
            VStack(spacing: 0) {
                // Header with statistics
                LeadsHeaderView(
                    totalLeads: viewModel.leads.count,
                    newLeads: viewModel.newLeadsCount,
                    convertedLeads: viewModel.convertedLeadsCount
                )
                
                // Search and filter controls
                LeadsSearchFilterView(
                    searchText: $searchText,
                    selectedFilter: $selectedFilter
                )
                
                // Leads list
                LeadsListView(
                    leads: filteredLeads,
                    onLeadTap: { lead in
                        selectedLead = lead
                        showingLeadDetails = true
                    },
                    onConvertLead: { lead in
                        viewModel.convertLead(lead)
                    },
                    onDeleteLead: { lead in
                        viewModel.deleteLead(lead)
                    }
                )
            }
            .navigationTitle("Leads")
            .navigationBarTitleDisplayMode(.large)
            .refreshable {
                await viewModel.refreshLeads()
            }
            .sheet(isPresented: $showingLeadDetails) {
                if let lead = selectedLead {
                    LeadDetailsView(lead: lead)
                }
            }
        }
        .task {
            await viewModel.loadLeads()
        }
    }
    
    private var filteredLeads: [Lead] {
        var leads = viewModel.leads
        
        // Apply search filter
        if !searchText.isEmpty {
            leads = leads.filter { lead in
                lead.fullName.localizedCaseInsensitiveContains(searchText) ||
                lead.company?.localizedCaseInsensitiveContains(searchText) == true ||
                lead.email?.localizedCaseInsensitiveContains(searchText) == true
            }
        }
        
        // Apply status filter
        switch selectedFilter {
        case .all:
            break
        case .new:
            leads = leads.filter { !$0.isConverted }
        case .converted:
            leads = leads.filter { $0.isConverted }
        }
        
        return leads
    }
}
```

#### 3.2 Contacts Dashboard View
**File**: `ContactsDashboardView.swift`

```swift
import SwiftUI

struct ContactsDashboardView: View {
    @StateObject private var viewModel = ContactsViewModel()
    @State private var searchText = ""
    @State private var selectedFilter: ContactFilter = .all
    @State private var showingContactDetails = false
    @State private var selectedContact: Contact?
    @State private var showingCreateContact = false
    @State private var showingQRScanner = false
    
    enum ContactFilter: String, CaseIterable {
        case all = "All Sources"
        case converted = "From Leads"
        case manual = "Manual"
        case qrScanned = "QR Scanned"
    }
    
    var body: some View {
        NavigationView {
            VStack(spacing: 0) {
                // Header with statistics
                ContactsHeaderView(
                    totalContacts: viewModel.contacts.count,
                    convertedContacts: viewModel.convertedContactsCount,
                    manualContacts: viewModel.manualContactsCount,
                    qrScannedContacts: viewModel.qrScannedContactsCount
                )
                
                // Search and filter controls
                ContactsSearchFilterView(
                    searchText: $searchText,
                    selectedFilter: $selectedFilter
                )
                
                // Action buttons
                ContactsActionButtonsView(
                    onCreateContact: { showingCreateContact = true },
                    onScanQR: { showingQRScanner = true }
                )
                
                // Contacts list
                ContactsListView(
                    contacts: filteredContacts,
                    onContactTap: { contact in
                        selectedContact = contact
                        showingContactDetails = true
                    },
                    onEditContact: { contact in
                        // Navigate to edit contact
                    },
                    onDeleteContact: { contact in
                        viewModel.deleteContact(contact)
                    }
                )
            }
            .navigationTitle("Contacts")
            .navigationBarTitleDisplayMode(.large)
            .refreshable {
                await viewModel.refreshContacts()
            }
            .sheet(isPresented: $showingContactDetails) {
                if let contact = selectedContact {
                    ContactDetailsView(contact: contact)
                }
            }
            .sheet(isPresented: $showingCreateContact) {
                CreateContactView()
            }
            .sheet(isPresented: $showingQRScanner) {
                QRScannerView()
            }
        }
        .task {
            await viewModel.loadContacts()
        }
    }
    
    private var filteredContacts: [Contact] {
        var contacts = viewModel.contacts
        
        // Apply search filter
        if !searchText.isEmpty {
            contacts = contacts.filter { contact in
                contact.fullName.localizedCaseInsensitiveContains(searchText) ||
                contact.organizationName?.localizedCaseInsensitiveContains(searchText) == true ||
                contact.emailPrimary?.localizedCaseInsensitiveContains(searchText) == true
            }
        }
        
        // Apply source filter
        switch selectedFilter {
        case .all:
            break
        case .converted:
            contacts = contacts.filter { $0.sourceType == "converted" }
        case .manual:
            contacts = contacts.filter { $0.sourceType == "manual" }
        case .qrScanned:
            contacts = contacts.filter { $0.sourceType == "qr_scan" }
        }
        
        return contacts
    }
}
```

#### 3.3 Lead Details View
**File**: `LeadDetailsView.swift`

```swift
import SwiftUI

struct LeadDetailsView: View {
    let lead: Lead
    @StateObject private var viewModel = LeadDetailsViewModel()
    @Environment(\.dismiss) private var dismiss
    @State private var showingConvertConfirmation = false
    
    var body: some View {
        NavigationView {
            ScrollView {
                VStack(alignment: .leading, spacing: 20) {
                    // Lead header
                    LeadHeaderView(lead: lead)
                    
                    // Lead information
                    LeadInformationView(lead: lead)
                    
                    // Business card information
                    if let businessCard = lead.businessCard {
                        BusinessCardInfoView(businessCard: businessCard)
                    }
                    
                    // Lead message
                    if let message = lead.message, !message.isEmpty {
                        LeadMessageView(message: message)
                    }
                    
                    // Conversion status
                    if lead.isConverted {
                        ConvertedStatusView(contact: lead.convertedContact)
                    } else {
                        ConversionActionsView(
                            onConvert: { showingConvertConfirmation = true },
                            onDelete: { viewModel.deleteLead(lead) }
                        )
                    }
                }
                .padding()
            }
            .navigationTitle("Lead Details")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button("Done") {
                        dismiss()
                    }
                }
            }
            .alert("Convert Lead", isPresented: $showingConvertConfirmation) {
                Button("Convert") {
                    viewModel.convertLead(lead)
                    dismiss()
                }
                Button("Cancel", role: .cancel) { }
            } message: {
                Text("This will create a new contact from this lead. The lead will be marked as converted.")
            }
        }
    }
}
```

#### 3.4 Contact Details View
**File**: `ContactDetailsView.swift`

```swift
import SwiftUI

struct ContactDetailsView: View {
    let contact: Contact
    @StateObject private var viewModel = ContactDetailsViewModel()
    @Environment(\.dismiss) private var dismiss
    @State private var showingEditContact = false
    @State private var showingDeleteConfirmation = false
    
    var body: some View {
        NavigationView {
            ScrollView {
                VStack(alignment: .leading, spacing: 20) {
                    // Contact header
                    ContactHeaderView(contact: contact)
                    
                    // Contact information
                    ContactInformationView(contact: contact)
                    
                    // Lead history
                    if contact.hasLeadHistory {
                        LeadHistoryView(lead: contact.lead)
                    }
                    
                    // Source information
                    SourceInformationView(contact: contact)
                }
                .padding()
            }
            .navigationTitle("Contact Details")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarLeading) {
                    Button("Done") {
                        dismiss()
                    }
                }
                ToolbarItem(placement: .navigationBarTrailing) {
                    Menu {
                        Button("Edit Contact") {
                            showingEditContact = true
                        }
                        Button("Delete Contact", role: .destructive) {
                            showingDeleteConfirmation = true
                        }
                    } label: {
                        Image(systemName: "ellipsis.circle")
                    }
                }
            }
            .sheet(isPresented: $showingEditContact) {
                EditContactView(contact: contact)
            }
            .alert("Delete Contact", isPresented: $showingDeleteConfirmation) {
                Button("Delete", role: .destructive) {
                    viewModel.deleteContact(contact)
                    dismiss()
                }
                Button("Cancel", role: .cancel) { }
            } message: {
                Text("This will permanently delete this contact and all associated data.")
            }
        }
    }
}
```

#### 3.5 QR Scanner Integration
**File**: `QRScannerView.swift`

```swift
import SwiftUI
import AVFoundation

struct QRScannerView: View {
    @StateObject private var scanner = QRCodeScanner()
    @StateObject private var viewModel = QRScannerViewModel()
    @Environment(\.dismiss) private var dismiss
    @State private var showingContactForm = false
    @State private var scannedData: String?
    
    var body: some View {
        NavigationView {
            ZStack {
                // Camera preview
                QRCodeScannerPreview(scanner: scanner)
                    .ignoresSafeArea()
                
                // Scanning overlay
                QRScanningOverlay()
                
                // Instructions
                VStack {
                    Spacer()
                    QRScanningInstructionsView()
                        .padding()
                        .background(.ultraThinMaterial)
                        .cornerRadius(12)
                        .padding()
                }
            }
            .navigationTitle("Scan QR Code")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarLeading) {
                    Button("Cancel") {
                        dismiss()
                    }
                }
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button("Switch Camera") {
                        scanner.switchCamera()
                    }
                }
            }
            .onReceive(scanner.$scannedText) { text in
                if let text = text {
                    scannedData = text
                    viewModel.processQRCode(text)
                    showingContactForm = true
                }
            }
            .sheet(isPresented: $showingContactForm) {
                if let contactData = viewModel.parsedContactData {
                    QRContactFormView(
                        contactData: contactData,
                        onSave: { contact in
                            viewModel.saveContact(contact)
                            dismiss()
                        },
                        onCancel: {
                            dismiss()
                        }
                    )
                }
            }
        }
    }
}
```

### Phase 4: View Models & Business Logic

#### 4.1 Leads View Model
**File**: `LeadsViewModel.swift`

```swift
import Foundation
import CoreData

@MainActor
class LeadsViewModel: ObservableObject {
    @Published var leads: [Lead] = []
    @Published var isLoading = false
    @Published var errorMessage: String?
    
    private let leadsAPIClient: LeadsAPIClient
    private let dataManager: DataManager
    
    init(leadsAPIClient: LeadsAPIClient, dataManager: DataManager) {
        self.leadsAPIClient = leadsAPIClient
        self.dataManager = dataManager
    }
    
    var newLeadsCount: Int {
        leads.filter { !$0.isConverted }.count
    }
    
    var convertedLeadsCount: Int {
        leads.filter { $0.isConverted }.count
    }
    
    func loadLeads() async {
        isLoading = true
        errorMessage = nil
        
        do {
            // Load from Core Data first
            leads = dataManager.fetchLeads()
            
            // Sync with server
            let serverLeads = try await leadsAPIClient.fetchLeads()
            leads = try await dataManager.syncLeads(serverLeads)
            
        } catch {
            errorMessage = "Failed to load leads: \(error.localizedDescription)"
        }
        
        isLoading = false
    }
    
    func refreshLeads() async {
        await loadLeads()
    }
    
    func convertLead(_ lead: Lead) async {
        do {
            let contactData = try await leadsAPIClient.convertLeadToContact(leadId: lead.id)
            let contact = try await dataManager.createContactFromData(contactData)
            
            // Update lead status
            lead.status = "converted"
            lead.convertedContact = contact
            try dataManager.saveContext()
            
        } catch {
            errorMessage = "Failed to convert lead: \(error.localizedDescription)"
        }
    }
    
    func deleteLead(_ lead: Lead) async {
        do {
            try await leadsAPIClient.deleteLead(id: lead.id)
            dataManager.deleteLead(lead)
            leads.removeAll { $0.id == lead.id }
            
        } catch {
            errorMessage = "Failed to delete lead: \(error.localizedDescription)"
        }
    }
}
```

#### 4.2 Contacts View Model
**File**: `ContactsViewModel.swift`

```swift
import Foundation
import CoreData

@MainActor
class ContactsViewModel: ObservableObject {
    @Published var contacts: [Contact] = []
    @Published var isLoading = false
    @Published var errorMessage: String?
    
    private let contactsAPIClient: ContactsAPIClient
    private let dataManager: DataManager
    
    init(contactsAPIClient: ContactsAPIClient, dataManager: DataManager) {
        self.contactsAPIClient = contactsAPIClient
        self.dataManager = dataManager
    }
    
    var convertedContactsCount: Int {
        contacts.filter { $0.sourceType == "converted" }.count
    }
    
    var manualContactsCount: Int {
        contacts.filter { $0.sourceType == "manual" }.count
    }
    
    var qrScannedContactsCount: Int {
        contacts.filter { $0.sourceType == "qr_scan" }.count
    }
    
    func loadContacts() async {
        isLoading = true
        errorMessage = nil
        
        do {
            // Load from Core Data first
            contacts = dataManager.fetchContacts()
            
            // Sync with server
            let serverContacts = try await contactsAPIClient.fetchContacts()
            contacts = try await dataManager.syncContacts(serverContacts)
            
        } catch {
            errorMessage = "Failed to load contacts: \(error.localizedDescription)"
        }
        
        isLoading = false
    }
    
    func refreshContacts() async {
        await loadContacts()
    }
    
    func createContact(_ contactData: ContactCreateData) async {
        do {
            let serverContact = try await contactsAPIClient.createContact(data: contactData)
            let contact = try await dataManager.createContactFromData(serverContact)
            contacts.insert(contact, at: 0)
            
        } catch {
            errorMessage = "Failed to create contact: \(error.localizedDescription)"
        }
    }
    
    func updateContact(_ contact: Contact, with data: ContactUpdateData) async {
        do {
            let serverContact = try await contactsAPIClient.updateContact(id: contact.id, data: data)
            try await dataManager.updateContact(contact, with: serverContact)
            
        } catch {
            errorMessage = "Failed to update contact: \(error.localizedDescription)"
        }
    }
    
    func deleteContact(_ contact: Contact) async {
        do {
            try await contactsAPIClient.deleteContact(id: contact.id)
            dataManager.deleteContact(contact)
            contacts.removeAll { $0.id == contact.id }
            
        } catch {
            errorMessage = "Failed to delete contact: \(error.localizedDescription)"
        }
    }
}
```

### Phase 5: Data Manager Integration

#### 5.1 Data Manager Enhancement
**File**: `DataManager.swift` (enhance existing)

```swift
// Add leads and contacts management methods

extension DataManager {
    
    // MARK: - Leads Management
    
    func fetchLeads() -> [Lead] {
        let request: NSFetchRequest<Lead> = Lead.fetchRequest()
        request.sortDescriptors = [NSSortDescriptor(keyPath: \Lead.capturedAt, ascending: false)]
        
        do {
            return try context.fetch(request)
        } catch {
            print("Error fetching leads: \(error)")
            return []
        }
    }
    
    func syncLeads(_ serverLeads: [LeadData]) async throws -> [Lead] {
        var localLeads: [Lead] = []
        
        for leadData in serverLeads {
            let lead = try await createOrUpdateLead(from: leadData)
            localLeads.append(lead)
        }
        
        try saveContext()
        return localLeads
    }
    
    func createOrUpdateLead(from data: LeadData) async throws -> Lead {
        let request: NSFetchRequest<Lead> = Lead.fetchRequest()
        request.predicate = NSPredicate(format: "id == %d", data.id)
        
        let lead = try context.fetch(request).first ?? Lead(context: context)
        
        // Update lead properties
        lead.id = data.id
        lead.idBusinessCard = data.idBusinessCard
        lead.firstName = data.firstName
        lead.lastName = data.lastName
        lead.email = data.email
        lead.phone = data.phone
        lead.company = data.company
        lead.jobTitle = data.jobTitle
        lead.message = data.message
        lead.notes = data.notes
        lead.source = data.source
        lead.status = data.status
        lead.capturedAt = ISO8601DateFormatter().date(from: data.capturedAt) ?? Date()
        lead.updatedAt = ISO8601DateFormatter().date(from: data.updatedAt) ?? Date()
        lead.ipAddress = data.ipAddress
        lead.userAgent = data.userAgent
        lead.referrer = data.referrer
        
        // Set business card relationship
        if let businessCard = fetchBusinessCard(id: data.idBusinessCard) {
            lead.businessCard = businessCard
        }
        
        return lead
    }
    
    func deleteLead(_ lead: Lead) {
        context.delete(lead)
        try? saveContext()
    }
    
    // MARK: - Contacts Management
    
    func fetchContacts() -> [Contact] {
        let request: NSFetchRequest<Contact> = Contact.fetchRequest()
        request.sortDescriptors = [NSSortDescriptor(keyPath: \Contact.createdAt, ascending: false)]
        
        do {
            return try context.fetch(request)
        } catch {
            print("Error fetching contacts: \(error)")
            return []
        }
    }
    
    func syncContacts(_ serverContacts: [ContactData]) async throws -> [Contact] {
        var localContacts: [Contact] = []
        
        for contactData in serverContacts {
            let contact = try await createOrUpdateContact(from: contactData)
            localContacts.append(contact)
        }
        
        try saveContext()
        return localContacts
    }
    
    func createOrUpdateContact(from data: ContactData) async throws -> Contact {
        let request: NSFetchRequest<Contact> = Contact.fetchRequest()
        request.predicate = NSPredicate(format: "id == %d", data.id)
        
        let contact = try context.fetch(request).first ?? Contact(context: context)
        
        // Update contact properties
        contact.id = data.id
        contact.idUser = data.idUser
        contact.idLead = data.idLead ?? 0
        contact.firstName = data.firstName
        contact.lastName = data.lastName
        contact.emailPrimary = data.emailPrimary
        contact.workPhone = data.workPhone
        contact.mobilePhone = data.mobilePhone
        contact.organizationName = data.organizationName
        contact.jobTitle = data.jobTitle
        contact.streetAddress = data.streetAddress
        contact.city = data.city
        contact.state = data.state
        contact.zipCode = data.zipCode
        contact.country = data.country
        contact.websiteUrl = data.websiteUrl
        contact.commentsFromLead = data.commentsFromLead
        contact.source = data.source
        contact.sourceMetadata = data.sourceMetadata
        contact.createdAt = ISO8601DateFormatter().date(from: data.createdAt) ?? Date()
        contact.updatedAt = ISO8601DateFormatter().date(from: data.updatedAt) ?? Date()
        
        // Set lead relationship
        if let leadId = data.leadId, leadId > 0 {
            if let lead = fetchLead(id: leadId) {
                contact.lead = lead
            }
        }
        
        return contact
    }
    
    func createContactFromData(_ data: ContactData) async throws -> Contact {
        return try await createOrUpdateContact(from: data)
    }
    
    func updateContact(_ contact: Contact, with data: ContactData) async throws {
        _ = try await createOrUpdateContact(from: data)
    }
    
    func deleteContact(_ contact: Contact) {
        context.delete(contact)
        try? saveContext()
    }
    
    // MARK: - Helper Methods
    
    private func fetchBusinessCard(id: Int32) -> BusinessCard? {
        let request: NSFetchRequest<BusinessCard> = BusinessCard.fetchRequest()
        request.predicate = NSPredicate(format: "id == %d", id)
        return try? context.fetch(request).first
    }
    
    private func fetchLead(id: Int32) -> Lead? {
        let request: NSFetchRequest<Lead> = Lead.fetchRequest()
        request.predicate = NSPredicate(format: "id == %d", id)
        return try? context.fetch(request).first
    }
}
```

### Phase 6: Navigation Integration

#### 6.1 Main Content View Update
**File**: `ContentView.swift` (enhance existing)

```swift
// Add leads and contacts tabs to main navigation

struct ContentView: View {
    @StateObject private var dataManager = DataManager()
    @State private var selectedTab = 0
    
    var body: some View {
        TabView(selection: $selectedTab) {
            // Existing business cards tab
            BusinessCardListView()
                .tabItem {
                    Image(systemName: "creditcard")
                    Text("Cards")
                }
                .tag(0)
            
            // New leads tab
            LeadsDashboardView()
                .tabItem {
                    Image(systemName: "person.badge.plus")
                    Text("Leads")
                }
                .tag(1)
            
            // New contacts tab
            ContactsDashboardView()
                .tabItem {
                    Image(systemName: "person.2")
                    Text("Contacts")
                }
                .tag(2)
            
            // Existing settings/profile tab
            ProfileView()
                .tabItem {
                    Image(systemName: "person.circle")
                    Text("Profile")
                }
                .tag(3)
        }
        .environmentObject(dataManager)
    }
}
```

### Phase 7: QR Scanner Integration

#### 7.1 QR Scanner View Model
**File**: `QRScannerViewModel.swift`

```swift
import Foundation

@MainActor
class QRScannerViewModel: ObservableObject {
    @Published var parsedContactData: ContactCreateData?
    @Published var isProcessing = false
    @Published var errorMessage: String?
    
    private let contactsAPIClient: ContactsAPIClient
    private let vCardParser = VCardParser()
    
    init(contactsAPIClient: ContactsAPIClient) {
        self.contactsAPIClient = contactsAPIClient
    }
    
    func processQRCode(_ qrData: String) {
        isProcessing = true
        errorMessage = nil
        
        Task {
            do {
                // Check if QR data is a URL
                if qrData.hasPrefix("http") {
                    // Handle URL-based QR codes
                    let vCardData = try await fetchVCardFromURL(qrData)
                    parsedContactData = vCardParser.parse(vCardData)
                } else {
                    // Handle direct vCard data
                    parsedContactData = vCardParser.parse(qrData)
                }
            } catch {
                errorMessage = "Failed to process QR code: \(error.localizedDescription)"
            }
            
            isProcessing = false
        }
    }
    
    func saveContact(_ contactData: ContactCreateData) async {
        do {
            let qrData = QRContactData(
                firstName: contactData.firstName,
                lastName: contactData.lastName,
                emailPrimary: contactData.emailPrimary,
                workPhone: contactData.workPhone,
                mobilePhone: contactData.mobilePhone,
                organizationName: contactData.organizationName,
                jobTitle: contactData.jobTitle,
                streetAddress: contactData.streetAddress,
                city: contactData.city,
                state: contactData.state,
                zipCode: contactData.zipCode,
                country: contactData.country,
                websiteUrl: contactData.websiteUrl,
                commentsFromLead: contactData.commentsFromLead,
                source: "qr_scan",
                sourceMetadata: createSourceMetadata()
            )
            
            _ = try await contactsAPIClient.createContactFromQR(data: qrData)
            
        } catch {
            errorMessage = "Failed to save contact: \(error.localizedDescription)"
        }
    }
    
    private func fetchVCardFromURL(_ url: String) async throws -> String {
        // Implement URL fetching logic
        // Similar to web implementation
        return ""
    }
    
    private func createSourceMetadata() -> String {
        let metadata = [
            "scan_timestamp": ISO8601DateFormatter().string(from: Date()),
            "device_type": "ios",
            "app_version": Bundle.main.infoDictionary?["CFBundleShortVersionString"] as? String ?? "unknown"
        ]
        
        return try! JSONSerialization.data(withJSONObject: metadata).base64EncodedString()
    }
}
```

#### 7.2 VCard Parser
**File**: `VCardParser.swift`

```swift
import Foundation

class VCardParser {
    
    func parse(_ vCardString: String) -> ContactCreateData? {
        let lines = vCardString.components(separatedBy: .newlines)
        var contactData = ContactCreateData(
            firstName: "",
            lastName: "",
            emailPrimary: nil,
            workPhone: nil,
            mobilePhone: nil,
            organizationName: nil,
            jobTitle: nil,
            streetAddress: nil,
            city: nil,
            state: nil,
            zipCode: nil,
            country: nil,
            websiteUrl: nil,
            commentsFromLead: nil
        )
        
        for line in lines {
            let trimmedLine = line.trimmingCharacters(in: .whitespacesAndNewlines)
            
            if trimmedLine.hasPrefix("FN:") {
                let fullName = String(trimmedLine.dropFirst(3))
                let nameParts = fullName.components(separatedBy: " ")
                contactData.firstName = nameParts.first ?? ""
                if nameParts.count > 1 {
                    contactData.lastName = nameParts.dropFirst().joined(separator: " ")
                }
            } else if trimmedLine.hasPrefix("N:") {
                let nameData = String(trimmedLine.dropFirst(2))
                let nameParts = nameData.components(separatedBy: ";")
                if nameParts.count >= 2 {
                    contactData.lastName = nameParts[0]
                    contactData.firstName = nameParts[1]
                }
            } else if trimmedLine.hasPrefix("EMAIL:") {
                contactData.emailPrimary = String(trimmedLine.dropFirst(6))
            } else if trimmedLine.hasPrefix("TEL:") {
                let phone = String(trimmedLine.dropFirst(4))
                if trimmedLine.contains("TYPE=CELL") || trimmedLine.contains("TYPE=MOBILE") {
                    contactData.mobilePhone = phone
                } else {
                    contactData.workPhone = phone
                }
            } else if trimmedLine.hasPrefix("ORG:") {
                contactData.organizationName = String(trimmedLine.dropFirst(4))
            } else if trimmedLine.hasPrefix("TITLE:") {
                contactData.jobTitle = String(trimmedLine.dropFirst(6))
            } else if trimmedLine.hasPrefix("ADR:") {
                let addressData = String(trimmedLine.dropFirst(4))
                let addressParts = addressData.components(separatedBy: ";")
                if addressParts.count >= 7 {
                    contactData.streetAddress = addressParts[2]
                    contactData.city = addressParts[3]
                    contactData.state = addressParts[4]
                    contactData.zipCode = addressParts[5]
                    contactData.country = addressParts[6]
                }
            } else if trimmedLine.hasPrefix("URL:") {
                contactData.websiteUrl = String(trimmedLine.dropFirst(4))
            } else if trimmedLine.hasPrefix("NOTE:") {
                contactData.commentsFromLead = String(trimmedLine.dropFirst(5))
            }
        }
        
        return contactData
    }
}
```

## Implementation Timeline

### Phase 1: Foundation (Week 1)
- [ ] Create Lead and enhanced Contact Core Data models
- [ ] Implement LeadsAPIClient and enhanced ContactsAPIClient
- [ ] Update DataManager with leads and contacts methods
- [ ] Create basic data models and API integration

### Phase 2: Core UI (Week 2)
- [ ] Implement LeadsDashboardView with list and search
- [ ] Implement ContactsDashboardView with list and search
- [ ] Create LeadDetailsView and ContactDetailsView
- [ ] Add basic navigation and tab integration

### Phase 3: Advanced Features (Week 3)
- [ ] Implement lead conversion functionality
- [ ] Add contact creation and editing
- [ ] Implement QR scanner integration
- [ ] Add source tracking and filtering

### Phase 4: Polish & Testing (Week 4)
- [ ] Add comprehensive error handling
- [ ] Implement offline support and sync
- [ ] Add analytics and statistics
- [ ] Testing and bug fixes

## Key Features to Implement

### Leads Management
- ✅ Lead dashboard with statistics
- ✅ Lead list with search and filtering
- ✅ Lead details view with full information
- ✅ Lead conversion to contacts
- ✅ Lead deletion with confirmation
- ✅ Lead analytics and conversion tracking

### Contacts Management
- ✅ Contact dashboard with statistics
- ✅ Contact list with search and source filtering
- ✅ Contact details view with lead history
- ✅ Contact creation (manual and QR scan)
- ✅ Contact editing and updating
- ✅ Contact deletion with confirmation
- ✅ Source tracking (manual, converted, QR scanned)

### QR Code Scanning
- ✅ Camera-based QR code scanning
- ✅ vCard parsing and contact creation
- ✅ URL-based QR code support
- ✅ Contact form pre-population
- ✅ Source tracking and metadata

### Data Synchronization
- ✅ Offline-first architecture with Core Data
- ✅ Server synchronization on app launch
- ✅ Conflict resolution and data integrity
- ✅ Background sync and updates

## Technical Considerations

### Core Data Integration
- Use existing Core Data stack
- Add Lead entity with proper relationships
- Enhance Contact entity with source tracking
- Implement proper migration strategy

### API Integration
- Extend existing APIClient for leads and contacts
- Implement proper error handling and retry logic
- Add offline support with local storage
- Ensure data consistency between local and server

### User Experience
- Maintain consistent design with existing app
- Implement smooth animations and transitions
- Add proper loading states and error messages
- Ensure accessibility compliance

### Performance
- Implement efficient data fetching and caching
- Use lazy loading for large lists
- Optimize Core Data queries and relationships
- Minimize memory usage and battery drain

## Success Metrics

### Functionality
- [ ] All web features available in iOS app
- [ ] Seamless data synchronization
- [ ] Reliable QR code scanning
- [ ] Intuitive user interface

### Performance
- [ ] Fast app launch and navigation
- [ ] Efficient data loading and caching
- [ ] Smooth animations and transitions
- [ ] Low memory usage

### User Experience
- [ ] Consistent design language
- [ ] Intuitive navigation and workflows
- [ ] Comprehensive error handling
- [ ] Accessibility compliance

This comprehensive plan provides a roadmap for integrating the complete leads and contacts management system from the web app into the iOS ShareMyCard app, ensuring feature parity and a native mobile experience.
