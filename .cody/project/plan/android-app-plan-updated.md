# Technical Specification: ShareMyCard Android App (Kotlin) - UPDATED

## Overview

This specification outlines the requirements to build an Android version of the ShareMyCard iOS app using Kotlin. The app is a digital business card system with QR code generation/scanning, cloud sync, media management, leads and contacts management, and comprehensive account features.

**Last Updated**: Based on iOS app version 1.8 functionality

**Recent Updates**:
- ✅ Account Security: Password settings (set/change password) - COMPLETED
- ✅ Demo Account Login: One-tap demo login bypassing verification - COMPLETED
- ✅ Contact Export: Export contacts to device contacts with runtime permissions - COMPLETED

---

## Complete Feature List (iOS Parity)

### ✅ Core Features Implemented in iOS

1. **Authentication & Security** ✅
   - ✅ Email-based registration
   - ✅ Dual authentication: Password OR email verification code
   - ✅ Demo account login
   - ✅ Password management (set, change, reset)
   - ✅ JWT token storage in EncryptedSharedPreferences (Keychain equivalent)
   - ✅ User email storage and retrieval
   - ✅ JWT token decoding for user info extraction

2. **Business Cards**
   - Full CRUD operations (Create, Read, Update, Delete)
   - Multiple cards per user
   - Theme support (12 professional themes)
   - Media management (profile photo, company logo, cover graphic)
   - Image cropping and editing
   - Multiple emails, phones, websites per card
   - Full address support
   - Bio with auto-hyperlinked URLs
   - Active/inactive card status
   - Server sync with timestamp comparison

3. **Contacts Management**
   - Full CRUD operations
   - Contact creation from QR scans
   - Contact editing
   - Contact deletion
   - Source tracking (manual, converted, qr_scan)
   - Source metadata storage (JSON)
   - Created/updated date display
   - ✅ Contact export to device contacts (with runtime permissions)
   - Search and filtering
   - Pull-to-refresh

4. **Leads Management** (View-Only in App)
   - View all leads
   - Lead details view
   - Lead conversion to contacts
   - Search leads
   - Pull-to-refresh
   - Sort by most recent first
   - Display capture dates
   - Lead status tracking (new/converted)
   - Source information (business card or custom QR)

5. **QR Code Features**
   - QR code generation for business cards
   - QR code scanning (camera)
   - QR code scanning (image upload)
   - vCard parsing from QR codes
   - URL handling (when QR contains URL without vCard)
   - Server-side QR image processing
   - Contact form pre-filling from QR data
   - Support for custom QR code types

6. **Sync & Data Management**
   - Full sync (push + pull)
   - Timestamp-based conflict resolution
   - Auto-sync after local changes
   - Contacts sync
   - Leads sync
   - Media upload/download
   - Error handling and retry logic
   - Cancellation error handling

7. **UI & Navigation**
   - Tab-based navigation (Home, Cards, Contacts, Leads, Settings)
   - Home screen with counts (cards, contacts, leads)
   - User email display
   - Sync button with status
   - Report Issues link
   - Version display
   - Pull-to-refresh on lists
   - Search functionality
   - Form validation
   - Error messages
   - Loading states

8. **Settings & Account** ✅
   - ✅ Password settings (set password if none, change password if exists)
   - ✅ Account security
   - ✅ Logout functionality
   - ✅ User information display
   - ✅ Report Issues link

---

## Architecture

### Application Architecture Pattern

- **MVVM (Model-View-ViewModel)** with Clean Architecture principles
- **Single Activity** architecture with Jetpack Compose for UI
- **Repository Pattern** for data access abstraction
- **Dependency Injection** using Hilt/Dagger

### Layer Structure

```
com.sharemycard.android/
├── data/
│   ├── local/
│   │   ├── database/        # Room database entities & DAOs
│   │   │   ├── BusinessCardEntity
│   │   │   ├── ContactEntity
│   │   │   ├── LeadEntity
│   │   │   └── related entities (Email, Phone, Website, Address)
│   │   └── preferences/     # Encrypted SharedPreferences for tokens
│   ├── remote/
│   │   ├── api/             # Retrofit API interfaces
│   │   │   ├── AuthApi
│   │   │   ├── CardApi
│   │   │   ├── ContactApi
│   │   │   ├── LeadApi
│   │   │   └── MediaApi
│   │   └── models/          # API request/response DTOs
│   └── repository/          # Repository implementations
├── domain/
│   ├── models/              # Business logic models
│   │   ├── BusinessCard
│   │   ├── Contact
│   │   ├── Lead
│   │   └── User
│   ├── repository/          # Repository interfaces
│   └── usecase/             # Use cases for business logic
├── presentation/
│   ├── screens/             # Composable screens
│   │   ├── auth/
│   │   ├── home/
│   │   ├── cards/
│   │   ├── contacts/
│   │   ├── leads/
│   │   └── settings/
│   ├── components/          # Reusable UI components
│   ├── viewmodels/          # ViewModels
│   └── navigation/          # Navigation graph
└── di/                      # Dependency injection modules
```

---

## Technology Stack

### Core Technologies

- **Language**: Kotlin 1.9+
- **Minimum SDK**: Android 7.1.1 (API 25)
- **Target SDK**: Android 14 (API 34)
- **Build System**: Gradle with Kotlin DSL

### Key Libraries

#### UI & Navigation
- **Jetpack Compose**: UI framework (replaces SwiftUI)
- **Material Design 3**: Design system
- **Compose Navigation**: Screen navigation
- **Accompanist**: Permissions, system UI controller

#### Data & Persistence
- **Room**: Local SQLite database (replaces Core Data)
- **DataStore**: Preferences storage
- **EncryptedSharedPreferences**: Secure token storage (replaces Keychain)

#### Networking
- **Retrofit**: HTTP client (replaces URLSession)
- **OkHttp**: HTTP engine with interceptors
- **Gson/Moshi**: JSON serialization (replaces JSONDecoder)

#### Image Handling
- **Coil**: Image loading and caching
- **CameraX**: Camera integration for QR scanning
- **Image Cropper**: For image editing

#### QR Code
- **ZXing (Zebra Crossing)**: QR code generation/scanning (replaces Core Image)

#### Dependency Injection
- **Hilt**: DI framework

#### Concurrency
- **Coroutines + Flow**: Async operations (replaces async/await)
- **StateFlow/SharedFlow**: Reactive state management (replaces @Published)

---

## Data Models

### Domain Models

See separate modular plans for detailed data models:
- [Business Cards Plan](./android-app-business-cards.md)
- [Contacts Plan](./android-app-contacts.md)
- [Leads Plan](./android-app-leads.md)

### Key Model Features

1. **BusinessCard**
   - Multiple emails, phones, websites
   - Address support
   - Media paths and local storage
   - Theme support
   - Active/inactive status
   - Server sync tracking

2. **Contact**
   - Full contact information
   - Source tracking (manual, converted, qr_scan)
   - Source metadata (JSON)
   - Created/updated dates
   - Date parsing (ISO8601, MySQL DATETIME)

3. **Lead**
   - View-only model
   - Conversion status
   - Source information (card or QR)
   - Date parsing and formatting
   - Display name computation

---

## API Integration

### API Endpoints Required

#### Authentication ✅
- ✅ `POST /api/auth/register` - User registration
- ✅ `POST /api/auth/login` - Login (password or email code)
- ✅ `POST /api/auth/verify` - Verify email code or password
- ✅ `POST /api/auth/password/set` - Set password
- ✅ `POST /api/auth/password/change` - Change password
- ✅ `POST /api/auth/password/reset` - Reset password
- ✅ Demo account login (bypasses verification)

#### Business Cards
- `GET /api/cards/` - Fetch all cards
- `POST /api/cards/` - Create card
- `PUT /api/cards/` - Update card
- `DELETE /api/cards/` - Delete card

#### Contacts
- `GET /api/contacts/` - Fetch all contacts
- `POST /api/contacts/` - Create contact
- `PUT /api/contacts/` - Update contact
- `DELETE /api/contacts/` - Delete contact
- `POST /api/contacts/create-from-qr` - Create from QR scan

#### Leads
- `GET /api/leads/` - Fetch all leads (view-only)
- `POST /api/leads/convert.php` - Convert lead to contact

#### Media
- `POST /api/media/upload` - Upload image
- `GET /api/media/view` - Download image
- `DELETE /api/media/delete` - Delete image

#### QR Processing
- `POST /api/qr/process-image` - Process QR image on server

### API Response Handling

- Handle both boolean and integer success fields (`success: true` or `success: 1`)
- Handle integer IDs (convert to String)
- Robust date parsing (ISO8601, MySQL DATETIME)
- Error handling for cancellation errors (code -999)
- Retry logic for transient failures

---

## Local Storage

### Room Database

Entities required:
- `BusinessCardEntity`
- `ContactEntity`
- `LeadEntity`
- `EmailContactEntity`
- `PhoneContactEntity`
- `WebsiteLinkEntity`
- `AddressEntity`

### Secure Storage

- JWT token in EncryptedSharedPreferences
- User email in EncryptedSharedPreferences
- JWT decoding fallback for email extraction

---

## Sync Manager

### Sync Strategy

1. **Full Sync** (on app start, manual sync)
   - Fetch server cards
   - Push local changes (timestamp comparison)
   - Pull server changes
   - Sync contacts
   - Sync leads

2. **Auto Sync** (after local changes)
   - Push only recent changes
   - Timestamp comparison

3. **Conflict Resolution**
   - Last-write-wins based on timestamps
   - Server timestamp comparison
   - Local timestamp comparison

### Error Handling

- Cancellation error handling (URLError.cancelled)
- Retry logic for transient failures
- Graceful degradation (don't fail entire sync if one part fails)

---

## UI Implementation

### Main Screens

1. **Home Screen**
   - App icon and title
   - Card count
   - Contact count
   - Lead count
   - User email display
   - Sync button
   - Logout button
   - Report Issues link
   - Version display

2. **Business Cards**
   - List view with search
   - Create card
   - Edit card
   - Display card
   - QR code generation
   - Media management

3. **Contacts**
   - List view with search
   - Create contact
   - Edit contact
   - View contact details
   - Export to device contacts
   - Created/updated dates

4. **Leads**
   - List view with search (view-only)
   - Lead details
   - Convert to contact
   - Sort by most recent
   - Display capture dates

5. **Settings** ✅
   - ✅ Password management (set/change password)
   - ✅ Account security screen
   - ✅ Logout
   - ✅ Report Issues link

6. **QR Scanner**
   - Camera scanning
   - Image upload scanning
   - vCard parsing
   - URL handling
   - Contact form pre-filling

---

## Navigation

### Navigation Graph

- Authentication flow (login, register, verify)
- Main app flow (home, cards, contacts, leads, settings)
- QR scanner flow
- Card detail/edit flow
- Contact detail/edit flow
- Lead detail flow

---

## Key Differences from iOS

### Architecture Patterns
- **iOS**: SwiftUI + Combine + Core Data
- **Android**: Jetpack Compose + Flow + Room

### Async Programming
- **iOS**: `async/await` with Swift Concurrency
- **Android**: Coroutines with `suspend` functions

### Reactive State
- **iOS**: `@Published` properties with Combine
- **Android**: `StateFlow`/`SharedFlow` with Coroutines

### Secure Storage
- **iOS**: Keychain Services
- **Android**: EncryptedSharedPreferences

### Image Handling
- **iOS**: UIImage with Core Image
- **Android**: Bitmap with BitmapFactory

### QR Code
- **iOS**: Core Image CIFilter
- **Android**: ZXing library

### Camera Access
- **iOS**: AVFoundation
- **Android**: CameraX

### Date Parsing
- **iOS**: ISO8601DateFormatter, DateFormatter
- **Android**: SimpleDateFormat, DateTimeFormatter

---

## Build Configuration

See separate modular plans for detailed build configuration.

---

## Testing Strategy

### Unit Tests
- ViewModel tests using JUnit and MockK
- Repository tests with fake implementations
- Use case tests for business logic
- Date parsing tests

### UI Tests
- Compose UI tests using `@Composable` test APIs
- Navigation tests
- Integration tests with Hilt test modules

---

## Platform-Specific Features

### Android-Specific Enhancements

1. **Material You (Dynamic Color)**: Support system theming
2. **Widgets**: Home screen widget for quick QR access
3. **Share Target**: Allow sharing business cards via Android's share sheet
4. **Shortcuts**: Deep links for common actions
5. **Biometric Authentication**: Fingerprint/Face unlock for app access

### Permissions Required

```xml
<!-- AndroidManifest.xml -->
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.CAMERA" />
<uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE" />
<uses-permission android:name="android.permission.WRITE_EXTERNAL_STORAGE" />
```

---

## Performance Considerations

1. **Image Compression**: Use Coil's built-in compression
2. **Lazy Loading**: Implement pagination for large lists
3. **Database Optimization**: Add proper indexes
4. **Memory Management**: Use weak references for large bitmaps
5. **Background Work**: Use WorkManager for sync operations
6. **Concurrent Refresh Prevention**: Flag-based refresh control

---

## Modular Development Plans

This plan has been split into separate modular plans for easier development:

1. **[Authentication & Security](./android-app-authentication.md)** - Login, registration, password management
2. **[Business Cards](./android-app-business-cards.md)** - Card CRUD, themes, media
3. **[Contacts Management](./android-app-contacts.md)** - Contact CRUD, QR scanning, export
4. **[Leads Management](./android-app-leads.md)** - Lead viewing, conversion
5. **[QR Code Features](./android-app-qr-codes.md)** - Generation, scanning, parsing
6. **[Sync & Data Management](./android-app-sync.md)** - Sync logic, conflict resolution
7. **[UI & Navigation](./android-app-ui-navigation.md)** - Screens, navigation, components
8. **[Settings & Account](./android-app-settings.md)** - Settings, account management

Each modular plan can be developed independently and integrated together.

---

## Recommended Development Order

The following order is recommended for implementing the modular plans, with dependencies and rationale:

### Phase 1: Foundation (Weeks 1-2) ✅
**1. [Authentication & Security](./android-app-authentication.md)** ✅
   - **Why First**: Required for all other features (API calls need authentication)
   - **Dependencies**: None
   - **Deliverables**: ✅ Login, ✅ Registration, ✅ Password management, ✅ Secure token storage, ✅ Demo account login
   - **Estimated Time**: 1-2 weeks
   - **Status**: ✅ COMPLETED

**2. [UI & Navigation](./android-app-ui-navigation.md)** (Basic Structure)
   - **Why Second**: Provides app structure and navigation framework
   - **Dependencies**: Authentication (for auth flow)
   - **Deliverables**: Tab navigation, basic screens, home screen structure
   - **Estimated Time**: 1 week (basic structure, can be enhanced later)

### Phase 2: Core Features (Weeks 3-5)
**3. [Business Cards](./android-app-business-cards.md)**
   - **Why Third**: Core feature of the app
   - **Dependencies**: Authentication, UI Navigation
   - **Deliverables**: Card CRUD, media management, themes
   - **Estimated Time**: 2-3 weeks

**4. [Sync & Data Management](./android-app-sync.md)**
   - **Why Fourth**: Needed to sync business cards with server
   - **Dependencies**: Business Cards, Authentication
   - **Deliverables**: Full sync, conflict resolution, error handling
   - **Estimated Time**: 1-2 weeks

### Phase 3: QR Code Features (Week 6)
**5. [QR Code Features](./android-app-qr-codes.md)**
   - **Why Fifth**: Depends on business cards for QR generation
   - **Dependencies**: Business Cards
   - **Deliverables**: QR generation, scanning, vCard parsing, URL handling
   - **Estimated Time**: 1 week

### Phase 4: Contacts & Leads (Weeks 7-8)
**6. [Contacts Management](./android-app-contacts.md)**
   - **Why Sixth**: Can work independently, but benefits from QR codes
   - **Dependencies**: Authentication, UI Navigation, QR Codes (for scanning)
   - **Deliverables**: Contact CRUD, QR scanning integration, export
   - **Estimated Time**: 1-2 weeks

**7. [Leads Management](./android-app-leads.md)**
   - **Why Seventh**: Depends on contacts for conversion functionality
   - **Dependencies**: Contacts Management, Authentication
   - **Deliverables**: Lead viewing, search, conversion to contacts
   - **Estimated Time**: 1 week

### Phase 5: Polish & Settings (Week 9) ✅
**8. [Settings & Account](./android-app-settings.md)** ✅
   - **Why Last**: Can be implemented anytime, but good to have after core features
   - **Dependencies**: Authentication
   - **Deliverables**: ✅ Password settings (set/change), ✅ Account security screen, ✅ Logout, ✅ Report Issues link
   - **Estimated Time**: 3-5 days
   - **Status**: ✅ COMPLETED
   - **Completed Features**:
     - ✅ Password Settings Screen (set password if none, change password if exists)
     - ✅ Account Security section in Settings
     - ✅ Demo account login (bypasses verification, navigates directly to home)
     - ✅ Contact export to device contacts
     - ✅ Report Issues link

### Parallel Development Opportunities

Some modules can be developed in parallel:
- **UI & Navigation** (basic structure) can start alongside **Authentication**
- **Contacts Management** can be developed in parallel with **Business Cards** (after Phase 1)
- **Settings & Account** can be developed anytime after **Authentication**

### Total Estimated Timeline

- **Minimum**: 8-9 weeks (sequential development)
- **With Parallel Work**: 6-7 weeks (with some modules in parallel)
- **With Full Team**: 4-5 weeks (multiple developers working on different modules)

### Critical Path

The critical path (must be done in order):
1. Authentication & Security
2. UI & Navigation (basic)
3. Business Cards
4. Sync & Data Management
5. QR Code Features
6. Contacts Management
7. Leads Management

Settings can be done anytime after Authentication.

---

## Conclusion

This specification provides a comprehensive blueprint for building the ShareMyCard Android app in Kotlin, maintaining complete feature parity with the iOS version (v1.8) while following Android best practices and leveraging modern Android development tools.

