# ShareMyCard.app

A modern digital business card system with QR code sharing, available both as a web application and iOS app. Built as an open-source educational project.

**Open Source • As-Is • No Guaranteed Support**

---

## 🎯 Project Overview

ShareMyCard allows users to create, manage, and share digital business cards via QR codes. The system includes a native iOS app for mobile users and a comprehensive web application for browser-based management and administration.

### Key Features
- 📱 Native iOS SwiftUI application
- 🌐 Full-featured web interface
- 🔗 QR code generation with complete vCard data
- 🎨 **Visual Themes** - 5 professional themes to customize card appearance
- 🖼️ **Web Image Editor** - Crop, rotate, zoom with 5 aspect ratios matching iOS app
- 📊 **Analytics Dashboard** - Track card views, link clicks, downloads with geographic and device insights
- ✉️ **Email Signature Generator** - Create professional email signatures with customizable images (profile photo or company logo)
- 🎴 **Multiple Cards Support** - Create different cards for different roles (personal, business, etc.) with unique signatures
- 🔒 **Secure Configuration System** - All sensitive data stored outside web root with proper permissions
- 🔒 Email-based authentication (no passwords!)
- 🔄 Real-time sync between web and mobile
- 👥 Multi-user support with admin panel

---

## 📱 iOS App Status

### ✅ Completed Features

#### Core Data Management
- **Business Card Model** - Complete data structure with all fields
- **Core Data Integration** - Programmatic model, relationships, CRUD operations
- **Local Storage** - Persistent storage on device using Core Data

#### User Interface
- **Business Card Creation** - Complete form with all contact fields
- **Business Card Editing** - Full editing capabilities with state management
- **Business Card List** - Grid view of all cards with search/filter
- **Business Card Display** - Beautiful preview of card data
- **Dashboard** - Main view with card management

#### Contact Information Support
- ✅ **Required Fields**: First Name, Last Name, Primary Phone
- ✅ **Additional Emails** (Personal, Work, Other with optional labels)
- ✅ **Additional Phones** (Mobile, Home, Work, Other with optional labels)
- ✅ **Website Links** (Name, URL, Description)
- ✅ **Address** (Street, City, State, ZIP, Country)
- ✅ **Professional Info** (Job Title, Company Name, Bio)
- ✅ **Media Assets** (Profile Photo, Company Logo, Cover Graphic - fully functional with upload/display)

#### QR Code Features
- **QR Code Generation** - Creates vCard format QR codes
- **QR Code Display** - Shows generated QR code for sharing
- **QR Code Scanning** - Camera-based scanning to import business cards
- **vCard Format** - Complete contact data in industry-standard format

##### Known Limitations
- Offline QR tracking is not supported. When the device lacks a server-assigned card ID or is offline, the app falls back to an embedded vCard QR (not logged in analytics).

#### Media Management (FULLY WORKING)
- **Image Picker Integration** - Photo library and camera access ✅
- **Image Compression** - Optimized storage of images ✅
- **PHPickerViewController** - Modern iOS image selection ✅
- **Image Cropping & Editing** - Crop, rotate, aspect ratios ✅
- **Upload to Server** - Profile photo, company logo, cover graphic ✅
- **Auto-Sync Images** - Images sync immediately after upload ✅
- **Web Display** - Images display on both admin and user view pages ✅

#### Additional Features
- **Sample Data Generator** - Quick testing with realistic data
- **Auto-linking URLs** - Clickable links in bio fields
- **SwiftUI Previews** - Development-time previews for all views

### ✅ API Integration Complete!

#### Sync System (FULLY WORKING)
- [x] API server with all endpoints ✅
- [x] JWT authentication system ✅
- [x] Rate limiting and security ✅
- [x] iOS API client implementation ✅
- [x] Auto-sync on create/edit/delete ✅
- [x] Timestamp-based conflict resolution ✅
- [x] Manual sync with bidirectional sync ✅
- [x] Graceful error handling ✅

#### How Sync Works
- **Auto-Sync**: Changes in iOS app push to server immediately
- **Conflict Resolution**: Newest timestamp wins (no data loss)
- **Manual Sync**: "Sync with Server" button for full bidirectional sync
- **Smart Push**: Compares timestamps before pushing (won't overwrite newer server data)

#### Advanced Features
- [ ] Multiple business cards per user
- [ ] Card templates/themes

---

## 🌐 Web App Status

### ✅ Completed Features

#### Authentication System
- **Email Verification** - Passwordless authentication via email codes
- **Gmail API Integration** - Professional email delivery
- **Session Management** - Secure session handling with timeout
- **User Registration** - Simple email-based registration
- **User Login** - Code-based login flow
- **Admin Authentication** - Separate admin login with role checking

#### User Interface
- **Landing Page** - Professional homepage with clear CTAs
- **User Dashboard** - Grid view of user's business cards
- **Admin Dashboard** - Statistics, user management, card overview
- **Responsive Design** - Works on desktop and mobile browsers

#### Business Card Management (User)
- **Create Card** - Complete form with all contact fields
- **Edit Card** - Update all information with dynamic fields
- **View Card** - Beautiful display with organized sections
- **Delete Card** - Remove cards with confirmation
- **QR Code Generation** - Complete vCard QR codes

#### Business Card Management (Admin)
- **My Cards** - Admins can manage their personal cards
- **All Cards Overview** - View all cards in system
- **User Management** - View, edit, deactivate users
- **Statistics Dashboard** - User count, card count, recent activity

#### Contact Information Support
- ✅ **Required Fields**: First Name, Last Name, Primary Phone
- ✅ **Dynamic Email Fields** (Personal, Work, Other with labels)
- ✅ **Dynamic Phone Fields** (Mobile, Home, Work, Other with labels)
- ✅ **Dynamic Website Fields** (Name, URL, Description)
- ✅ **Address Section** (Street, City, State, ZIP, Country)
- ✅ **Professional Info** (Job Title, Company Name, Bio)
- ✅ **Add/Remove Functionality** - JavaScript-powered dynamic forms

#### QR Code Features
- **vCard Generation** - Complete contact data in vCard 3.0 format
- **QR Code Display** - Visual QR code with download option
- **All Contact Types** - Emails, phones, websites, address included
- **Mobile-Friendly** - Scannable from any QR reader

#### Analytics Dashboard ✨ NEW!
- **Event Tracking** - Views, link clicks, vCard downloads
- **Unique Visitors** - Session-based tracking with 30-day cookies
- **Geographic Insights** - Country and city data from IP addresses
- **Device Analytics** - Browser, OS, device type (mobile/tablet/desktop)
- **Interactive Charts** - Line, pie, bar, and doughnut charts via Chart.js
- **Time Periods** - View data for 7, 30, 90 days, or all time
- **CSV Export** - Download analytics data for external analysis
- **Privacy Compliant** - Cookie consent, DNT support, 30-day data retention

#### Email Signature Generator
- **Visual Signature Builder** - Live preview with instant updates
- **Image Options** - Choose between profile photo (round), company logo (rounded corners), or no image
- **Individual URL Selection** - Pick specific websites to include in signature
- **Element Reordering** - Use ↑↓ arrows to customize order (phone, email, websites, address)
- **Copy for Gmail** - Special button to copy visual signature (not HTML code)
- **Copy HTML Code** - For Outlook, Apple Mail, and other clients
- **Email Client Instructions** - Step-by-step guides for Gmail, Outlook, Apple Mail
- **Multiple Signatures** - Create different cards for different roles, each with unique signatures

#### Visual Themes
- **5 Professional Themes** - Professional Blue (default), Minimalist Gray, Creative Sunset, Corporate Green, Tech Purple
- **Interactive Selector** - Click color swatches or dropdown to preview themes
- **Live Theme Application** - Applies to all card views, QR code pages, and public profile
- **CSS Variable System** - Dynamic color schemes with gradients and typography
- **Consistent Branding** - Theme carries across admin, user, and public views

#### Email System
- **Gmail API Integration** - OAuth2 authentication
- **Email Templates** - Registration, login, verification
- **Clickable Links** - Pre-filled codes in verification emails
- **Professional Branding** - ShareMyCard branded emails

#### Database
- **MySQL Backend** - Remote database via SSH tunnel
- **Complete Schema** - Users, cards, emails, phones, websites, addresses
- **Relationships** - Proper foreign keys and cascading deletes
- **Verification Codes** - Time-limited email verification

#### Admin Panel
- **User Management** - View all users, edit, activate/deactivate
- **Business Card Overview** - See all cards across all users
- **Statistics** - Real-time counts and activity
- **Admin Role Management** - Control admin access

### ✅ Recently Completed Features

#### Media Management (v1.5.0)
- [x] Profile photo upload (API) ✅
- [x] Company logo upload (API) ✅
- [x] Cover graphic upload (API) ✅
- [x] iOS photo picker integration ✅
- [x] iOS → Server upload and sync ✅
- [x] Web display of images ✅
- [x] Image cropping/editing ✅
- [x] Multiple aspect ratios (1:1, 3:4, 4:3, 16:9, free) ✅
- [x] Image rotation (90° increments) ✅

#### Sharing & Analytics (v1.6.0)
- [x] Card sharing via link (public view) ✅
- [x] Email signature generator with customization ✅
- [x] Analytics dashboard (views, clicks, downloads) ✅
- [x] Device/browser/OS tracking ✅
- [x] Session tracking with privacy consent ✅

#### Visual Customization (v1.7.0)
- [x] Card themes system (5 professional themes) ✅
- [x] Interactive theme selector with color swatches ✅
- [x] Dynamic CSS variables for theming ✅
- [x] Theme persistence and sync with iOS app ✅
- [x] Consistent theme application across all views ✅

#### Security & Configuration System (v1.9.0)
- [x] Secure configuration system outside web root ✅
- [x] Removed sensitive credentials from Git history ✅
- [x] Comprehensive .gitignore for security ✅
- [x] Template-based configuration files ✅
- [x] Environment variable support ✅
- [x] Secure file permissions (600/700) ✅
- [x] Production deployment guide ✅
- [x] Fixed login system with proper Gmail API constants ✅
- [x] Database connection security improvements ✅

#### Primary Contact Features & Sync Fixes (v1.10.0) 🚧 TESTING NEEDED
- [x] Primary email designation system ✅
- [x] Primary website designation system ✅
- [x] Database schema updates (is_primary fields) ✅
- [x] Web UI for primary contact selection ✅
- [x] API support for primary contact flags ✅
- [x] iOS Core Data model updates ✅
- [x] iOS API model fixes (string/boolean handling) ✅
- [x] Comprehensive debug logging system ✅
- [x] Sync system fixes and improvements ✅
- [ ] **Testing required**: Primary contact features across all platforms
- [ ] **Testing required**: Sync functionality with new primary fields
- [ ] **Testing required**: Cross-platform consistency verification

#### iOS Stability Fixes (v1.10.1)
- [x] Logout now fully clears local data without crashes ✅
  - Replaced `NSBatchDeleteRequest` with safe iterative deletes due to programmatic Core Data model limitations
  - Clears in-memory `businessCards` before deletion to avoid UI referencing invalidated objects
- [x] Resolved logout showing previous user's data ✅
  - `AuthService.logout()` now calls `DataManager.clearAllData()` after removing JWT
  - Ensures a clean state between user sessions

#### iOS App Store & TestFlight Submission (v1.11.0) ✅ COMPLETE
- [x] Removed camera dependencies and permissions ✅
  - Deleted `QRCodeScannerView.swift` (app is for sharing, not collecting cards)
  - Removed `NSCameraUsageDescription` from Info.plist
  - Cleaned up AVFoundation imports
- [x] iOS App Store compliance ✅
  - Proper Apple Distribution certificate setup
  - App Store Connect provisioning profile
  - Export compliance documentation (standard HTTPS/ATS only)
  - Bundle ID registration: `net.warrick.ShareMyCard`
- [x] TestFlight submission ✅
  - Successfully uploaded build to App Store Connect
  - Configured TestFlight testing groups
  - Set up proper user roles and permissions
  - Created comprehensive TestFlight description
- [x] Certificate management ✅
  - Clean slate approach: revoked and recreated fresh certificates
  - Proper keychain cleanup and Xcode configuration
  - Release build configured for distribution

#### Apple TestFlight Compliance & iOS Polish (v1.12.0) 🚀 NEW!
- [x] Password authentication system ✅
  - Dual authentication: password OR email code (satisfies Apple reviewers)
  - Backward compatibility: existing users can continue using email codes
  - Password management: set, change, reset with security best practices
  - Rate limiting and failed login attempt tracking
- [x] iOS app authentication updates ✅
  - Updated `AuthService.swift` with password management methods
  - Enhanced `LoginView.swift` with dual authentication flow
  - Created `PasswordSettingsView.swift` and `ForgotPasswordView.swift`
  - Added `checkPasswordStatus()` API integration
- [x] Web interface enhancements ✅
  - Updated admin and user login pages with password support
  - Created session-authenticated password management endpoints
  - Fixed "Use Email Code Instead" flow to avoid infinite loops
  - Added account security modals with proper navigation
- [x] iOS app UI polish ✅
  - Fixed dark mode navigation title visibility issues
  - Corrected cover image aspect ratio (3:1, 80pt height)
  - Fixed "Additional Information" section logic (only shows when multiple items)
  - Moved password settings to dedicated modal interfaces

#### Web Image Editor & UX Improvements (v1.8.0)
- [x] Web-based image editor with Cropper.js ✅
- [x] Crop, rotate, zoom, flip functionality ✅
- [x] 5 aspect ratio options (1:1, 3:4, 4:3, 16:9, Free) ✅
- [x] Square image containers with padding ✅
- [x] Cover graphic banner-style display ✅
- [x] "View Public Card" button for quick preview ✅
- [x] Image editor auto-opens on file selection ✅
- [x] Live preview with edited state indicators ✅
- [x] High-quality JPEG output (90% quality, max 2048px) ✅

### 🚧 Not Yet Implemented

#### Branded QR Codes
- [ ] Logo overlay in center of QR codes
- [ ] Brand color customization (foreground/background)
- [ ] Optional caption text below QR code
- [ ] High error correction (level H) to tolerate logo overlay
- [ ] Caching system for generated QR codes
- [ ] Web API endpoint for on-the-fly QR generation
- [ ] iOS QR code compositor with logo overlay

#### Social Features
- [ ] Social media integration (Facebook, LinkedIn, Twitter)

---

## 🔄 Integration Status

### ✅ Ready for Integration
- **Matching Data Models** - iOS and Web use identical field structures
- **Database Schema** - Complete and tested
- **Authentication Flow** - Email verification ready for mobile
- **vCard Format** - Both systems generate compatible QR codes

### 🚀 Future Enhancements


---

## 🛠 Tech Stack

### iOS Application
- **Language**: Swift 5.9+
- **Framework**: SwiftUI
- **Persistence**: Core Data (programmatic model)
- **Architecture**: MVVM
- **Image Processing**: UIKit integration
- **QR Code**: Core Image
- **Camera**: AVFoundation

### Web Application
- **Backend**: PHP 8.1+
- **Web Server**: Apache 2.4+ / PHP built-in server (development)
- **Database**: MySQL 8+
- **Frontend**: HTML, CSS, JavaScript (Vanilla)
- **Email**: Gmail API with OAuth2
- **Authentication**: Session-based with email verification
- **QR Code**: API-based generation

### Infrastructure
- **Version Control**: Git / GitHub
- **Database Access**: SSH tunneling for secure remote access
- **Development**: Local PHP server, Xcode for iOS
- **Deployment**: Ready for production hosting

---

## 📋 Project Structure

```
digital-business-card/
├── QRCard/                         # iOS Application (ShareMyCard)
│   ├── BusinessCard.swift          # Data model
│   ├── DataManager.swift           # Core Data manager
│   ├── ContentView.swift           # Main view
│   ├── ShareMyCardApp.swift        # App entry point
│   ├── BusinessCardCreationView.swift
│   ├── BusinessCardEditView.swift
│   ├── BusinessCardListView.swift
│   ├── BusinessCardDisplayView.swift
│   ├── QRCodeGenerator.swift
│   ├── QRCodeScannerView.swift
│   ├── ImagePicker.swift
│   ├── docsapi-spec.md             # API specification
│   ├── docsdatabase-schema.md      # Database schema
│   └── README.md                   # iOS app readme
│
├── QRCard/web/                     # Web Application
│   ├── index.php                   # Landing page
│   ├── user/                       # User interface
│   │   ├── dashboard.php
│   │   ├── login.php
│   │   ├── register.php
│   │   └── cards/                  # Card management
│   │       ├── create.php
│   │       ├── edit.php
│   │       ├── view.php
│   │       └── qr.php
│   ├── admin/                      # Admin interface
│   │   ├── dashboard.php
│   │   ├── login.php
│   │   ├── users.php
│   │   ├── cards.php
│   │   ├── my-cards.php
│   │   └── cards/                  # Admin card management
│   ├── api/                        # API (ready for mobile)
│   │   ├── auth/
│   │   │   ├── register.php
│   │   │   ├── login.php
│   │   │   └── verify.php
│   │   ├── cards/
│   │   │   └── index.php
│   │   └── includes/
│   │       ├── Database.php
│   │       ├── Api.php
│   │       ├── GmailClient.php
│   │       └── EmailTemplates.php
│   └── config/
│       ├── database.php
│       ├── gmail.php
│       └── schema.sql
│
├── RETROSPECTIVE.md                # iOS development retrospective
├── RETROSPECTIVE-WEB-ENHANCEMENT.md # Web enhancement retrospective
└── README.md                       # This file
```

---

## 🚀 Getting Started

### iOS Development

1. **Requirements**:
   - macOS with Xcode 15+
   - iOS 17+ deployment target

2. **Setup**:
   ```bash
   # Open the Xcode project
   open QRCard.xcodeproj
   ```

3. **Run**:
   - Select target device/simulator in Xcode
   - Press `Cmd+R` to build and run
   - The app is named "ShareMyCard" in the project

### Web Development

1. **Requirements**:
   - PHP 8.1+
   - MySQL 8+
   - Composer (for dependencies, if needed)

2. **Setup**:
   ```bash
   cd QRCard/web
   
   # Create config files from templates
   cp config/database.php.template config/database.php
   cp config/gmail.php.template config/gmail.php
   
   # Edit config files with your credentials
   # Run database schema
   mysql -u username -p database_name < config/schema.sql
   
   # Start development server
   php -S localhost:8000 router.php
   ```

3. **Access**:
   - Landing: `http://localhost:8000`
   - User Login: `http://localhost:8000/user/login.php`
   - Admin Login: `http://localhost:8000/admin/login.php`

---

## 📚 Documentation

- [API Specification](QRCard/docsapi-spec.md) - API design (original)
- [Database Schema](QRCard/docsdatabase-schema.md) - Database structure (original)
- [API Documentation](QRCard/web/api/README.md) - RESTful API endpoints
- [Gmail API Setup](QRCard/web/api/README-Gmail.md) - Email configuration
- [Configuration Setup](QRCard/web/config/README.md) - Config file setup
- [Cody Framework](.cody/) - Spec-driven development docs
- [iOS Retrospective](RETROSPECTIVE.md) - iOS development insights
- [Web Enhancement Retrospective](RETROSPECTIVE-WEB-ENHANCEMENT.md) - Web enhancement insights

---

## 🎯 Roadmap

### Phase 1: Foundation ✅ COMPLETE
- [x] iOS app with local storage
- [x] Web app with user management
- [x] Admin panel
- [x] Email authentication
- [x] QR code generation
- [x] Complete contact information support

### Phase 2: Integration ✅ COMPLETE
- [x] RESTful API for mobile
  - [x] JWT token authentication (30-day expiration)
  - [x] Rate limiting (per endpoint)
  - [x] Business card CRUD operations
  - [x] Media upload/delete endpoints
  - [x] QR code generation with vCard
  - [x] Complete API documentation
- [x] iOS API client ✅
- [x] Authentication sync ✅
- [x] Data synchronization (auto + manual) ✅
- [x] Conflict resolution (timestamp-based) ✅
- [x] Graceful error handling ✅

### Phase 3: Enhanced Features ✅ COMPLETE
- [x] Media upload API (backend complete) ✅
- [x] Media upload in iOS app ✅
- [x] Image cropping/editing in iOS ✅
- [x] Analytics dashboard ✅
- [x] Public card sharing ✅
- [x] Card themes/templates ✅
- [x] iOS App Store & TestFlight submission ✅
- [ ] Branded QR codes with logo overlay
- [ ] Social media integration (Facebook, LinkedIn, Twitter)

### Phase 4: Scale & Polish 🔮 FUTURE
- [ ] Performance optimization
- [ ] Advanced analytics
- [ ] Team/organization support
- [ ] API for third-party integrations
- [ ] Mobile app for Android
- [ ] Premium features

---

## 🤝 Contributing & Support

This is an open-source educational project provided **as-is** with **no guaranteed support**.

### Bug Reports & Feature Requests
You may submit bug reports and feature requests via [GitHub Issues](https://github.com/mwarrick/digital-business-card/issues), however:
- ⚠️ **No guarantee of response or resolution**
- ⚠️ This is a side project with no dedicated support team
- ✅ Community contributions are welcome and encouraged!

### How to Contribute
If you'd like to contribute improvements:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Development Guidelines
- Follow existing code style
- Write clear commit messages
- Update documentation as needed
- Test thoroughly before submitting

**Community-driven improvements are appreciated!** If you fix a bug or add a feature, share it back with the community via pull request.

---

## 📝 License

This project is **open source** and provided **as-is** with no warranty or guaranteed support.

You are free to:
- ✅ Use this code for personal or commercial projects
- ✅ Modify and distribute the code
- ✅ Learn from and build upon this project

**No Support Guarantee:** This is an educational project. While you may submit bug reports and feature requests via GitHub Issues, there is no guarantee of response or resolution.

---

## 👨‍💻 Author

**Mark Warrick**
- Email: mark@warrick.net
- GitHub: [@mwarrick](https://github.com/mwarrick)

---

## 🙏 Acknowledgments

- Built as an educational open-source project
- Inspired by commercial solutions like Popl, HiHello, Blinq, Mobilo, Linq, and V1CE
- Created with assistance from Cursor AI and Claude Sonnet 4.5

---

## 📊 Project Status

**Current Version**: 1.12.0 (Apple TestFlight Compliance & iOS Polish)  
**Last Updated**: January 15, 2025  
**Status**: 🚀 **Apple Review Ready** - iOS app now fully compliant with Apple's TestFlight review requirements

### What's Working
- ✅ iOS app with full CRUD operations
- ✅ Web app with user and admin interfaces
- ✅ Complete contact information management
- ✅ QR code generation with full vCard data + profile photos
- ✅ **Dual Authentication System** ✨ NEW!
  - Password authentication (satisfies Apple reviewers)
  - Email verification codes (passwordless option)
  - Backward compatibility for existing users
  - Password management (set, change, reset)
  - Rate limiting and security best practices
- ✅ Bidirectional sync with timestamp-based conflict resolution
- ✅ Media upload (profile photo, company logo, cover graphic)
- ✅ Image cropping and editing in iOS
- ✅ Beautiful, responsive UI on both platforms
- ✅ Custom debug logging for production
- ✅ Automated app icon generation
- ✅ **Complete RESTful API for mobile integration**
  - JWT token authentication (30-day expiration)
  - Rate limiting (100 requests/hour per user)
  - Comprehensive API documentation
- ✅ **Public shareable card portal**
  - Share card via public link, QR code, or email
  - No login required for viewing
  - vCard download for direct contact import
- ✅ **Advanced email signature generator**
  - Live preview with instant updates
  - Image selection (profile photo, logo, or none)
  - Individual URL selection and reordering
  - Copy for Gmail (visual) and HTML code for other clients
- ✅ **Analytics Dashboard**
  - Real-time tracking (views, clicks, downloads)
  - Geographic insights (countries, cities)
  - Device/browser/OS analytics
  - Interactive charts (Chart.js)
  - Time period filters (7d, 30d, 90d, all time)
  - CSV export
  - Privacy-compliant (cookie consent, DNT support)
  - Automated daily aggregation via CRON

### 🧪 Testing Required (v1.10.0)

**Primary Contact Features Testing:**
- [ ] **Web App**: Primary email/website selection in edit forms
- [ ] **Web App**: Primary contacts display correctly in view pages
- [ ] **Web App**: Primary contacts appear first in public card view
- [ ] **iOS App**: Primary contact toggles in edit forms
- [ ] **iOS App**: Primary contacts display correctly in card view
- [ ] **Sync**: Primary flags sync correctly between web and iOS
- [ ] **API**: Primary contact data round-trip through API
- [ ] **Database**: Primary flags persist correctly in database

**Sync System Testing:**
- [ ] **iOS → Server**: Local changes push to server correctly
- [ ] **Server → iOS**: Server changes pull to iOS correctly
- [ ] **Conflict Resolution**: Timestamp-based sync works with primary fields
- [ ] **Error Handling**: Sync handles network errors gracefully
- [ ] **Debug Logging**: Comprehensive logging shows sync process clearly

### What's Next (v1.13.0)
- 🔄 Submit updated iOS app to TestFlight for Apple review
- 🔄 Test password authentication flows thoroughly
- 🔄 Consider reverting rate limits to production values
- 🔄 Gather feedback from TestFlight testers
- 🔄 Branded QR codes with logo overlay
- 🔄 Background sync with retry logic
- 🔄 Sync status indicators in UI
- 🔄 Offline queue for network failures
- 🔄 Export to PDF/PNG
- 🔄 Search and filter functionality

---

**Ready to share your digital business card! 🚀**

