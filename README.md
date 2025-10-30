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
- 🎨 **Visual Themes** - 12 professional themes to customize card appearance
- 🖼️ **Web Image Editor** - Crop, rotate, zoom with 5 aspect ratios matching iOS app
- 📊 **Analytics Dashboard** - Track card views, link clicks, downloads with geographic and device insights
- ✉️ **Email Signature Generator** - Create professional email signatures with customizable images (profile photo or company logo) and **email open tracking**
- 🖼️ **Virtual Backgrounds** - Generate custom virtual backgrounds for video calls with embedded QR codes
- 🎴 **Multiple Cards Support** - Create different cards for different roles (personal, business, etc.) with unique signatures
- 📧 **User Invitation System** - Invite others to join ShareMyCard with personalized business card sharing and response tracking
- 📋 **Leads & Contacts Management** - Complete lead capture and contact management system with conversion workflow
- 🔄 **Duplicate Card Feature** - One-click card duplication with all contact information, emails, phones, websites, and addresses
- 🔒 **Secure Configuration System** - All sensitive data stored outside web root with proper permissions
- 🔒 Dual authentication (password + email codes)
- 🔄 Real-time sync between web and mobile
- 👥 Multi-user support with admin panel
- 📊 **Image Creation Logging** - Complete audit trail of all image creation events with admin monitoring

### 🎨 Professional Themes

ShareMyCard includes 12 professionally designed themes to customize your business card appearance:

**Original Themes:**
- Professional Blue - Classic corporate gradient
- Minimalist Gray - Clean, sophisticated design  
- Creative Sunset - Vibrant pink-to-red gradient
- Corporate Green - Professional green gradient
- Tech Purple - Modern tech-focused design

**New Professional Themes:**
- Modern Red - Bold red gradient for energetic brands
- Ocean Blue - Deep ocean blue for maritime/consulting
- Royal Gold - Elegant gold gradient for luxury brands
- Forest Green - Natural green for eco/health brands
- Slate Black - Sophisticated dark theme for modern professionals
- Coral Pink - Warm coral for creative/lifestyle brands
- Electric Teal - Vibrant teal for tech/innovation

Each theme includes custom color gradients, typography, and text colors optimized for professional business card display.

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
- **Duplicate Card Feature** - One-click card duplication with pre-filled form

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
- **Duplicate Card** - One-click duplication with all contact information
- **Delete Card** - Remove cards with confirmation
- **QR Code Generation** - Complete vCard QR codes

#### Business Card Management (Admin)
- **My Cards** - Admins can manage their personal cards
- **All Cards Overview** - View all cards in system with duplicate functionality
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
- **QR Code Scanning** - Camera-based scanning to import business cards ✨ NEW!
  - **Camera Access** - HTML5 camera integration for real-time QR scanning
  - **vCard Parsing** - Automatic parsing of contact data from QR codes
  - **URL Processing** - Handles QR codes that redirect to vCard files (like ShareMyCard)
  - **Flexible Form** - All fields optional, works with any vCard format
  - **Contact Creation** - Seamless creation of new contacts from scanned data
  - **Error Handling** - Clear error messages and "Try Again" functionality
  - **Source Tracking** - Tracks QR-scanned contacts with metadata

#### Analytics Dashboard ✨ NEW!
- **Event Tracking** - Views, link clicks, vCard downloads, **email opens**
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
- **📧 Email Open Tracking** - Optional 1x1 tracking pixel to measure email open rates
- **Analytics Integration** - Email opens appear in analytics dashboard with geographic and device data
- **Privacy Controls** - Tracking can be disabled when generating signatures

#### Virtual Backgrounds Generator ✨ NEW!
- **Custom Background Creation** - Generate professional virtual backgrounds for video calls
- **QR Code Integration** - Embed your business card QR code directly into backgrounds
- **Multiple Resolutions** - 4K (3840x2160), 2K (2560x1440), HD (1920x1080), Standard (1366x768)
- **Theme-Matched Gradients** - 5 professional gradient backgrounds matching card themes
- **Custom Color Options** - Choose theme colors or create custom gradient combinations
- **QR Positioning** - Place QR code in any corner with adjustable padding
- **Size Control** - Adjustable QR code size (200-500px) with live preview
- **Smart File Naming** - Downloads named with your actual name, title, and company
- **Live Preview** - Real-time preview as you adjust settings
- **Professional Quality** - High-resolution PNG output optimized for video calls

#### Name Tag Generator ✨ NEW!
- **Professional Name Tags** - Generate printable name tags with your business card information
- **PDF Output** - 8 name tags per standard 8.5" x 11" sheet (2 columns x 4 rows)
- **QR Code Integration** - Each name tag includes your business card QR code
- **Custom Messages** - Add personalized messages above and below your contact information
- **Content Selection** - Choose which contact details to include (name, title, company, phone, email, address, website)
- **Font Size Control** - Adjustable font size with dynamic scaling based on content length
- **Live Preview** - Real-time preview of name tag layout and styling
- **Professional Layout** - Two-column design with contact info on left, QR code on right
- **Smart Sizing** - QR code automatically sizes based on text content to prevent overlap
- **Label Compatibility** - Designed for standard 3.375" x 2.33" name tag labels
- **Usage Instructions** - Built-in guidance with links to compatible label products

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
- **Login Reports** - Track user logins with browser, device, and IP analytics
- **Image Creation History** - Monitor all image creation events with full context tracking

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

#### Apple TestFlight Compliance & iOS Polish (v1.12.0) ✅ COMPLETE
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

#### Demo Account System (v1.13.0) 🚀 NEW!
- [x] Complete demo account implementation ✅
  - Instant login without authentication for Apple TestFlight reviewers
  - Pre-populated with 3 professional sample business cards
  - Email suppression (no emails sent to demo users)
  - Rate limiting bypass for demo users
  - Clean slate approach (cards reset on every login)
- [x] Database migrations ✅
  - Added user role system (user, admin, demo)
  - Created demo user with proper permissions
  - Sample business cards with realistic data
  - Cascade deletion support for card management
- [x] Backend demo system ✅
  - `DemoUserHelper` utility class for demo user detection
  - Login bypass in authentication endpoints
  - Email suppression in Gmail client
  - Auto-regeneration of demo cards on login
- [x] Web interface demo features ✅
  - Professional card deletion with modal confirmation
  - Session-based authentication for web operations
  - Admin dashboard shows demo user role correctly
  - User dashboard with delete functionality
- [x] iOS app demo integration ✅
  - Beautiful gradient "Demo Login" button
  - Instant demo access without authentication
  - Full functionality (create, edit, delete cards)
  - Seamless integration with existing authentication flow

#### Admin User Management Enhancement (v1.14.0) ✅ COMPLETE!
- [x] Complete user management system ✅
  - Professional user edit modal with email, password, status, and role fields
  - Real-time validation and error handling with loading states
  - Session-based API endpoints for secure admin operations
  - Comprehensive user deletion with full cascade cleanup
- [x] User impersonation system ✅
  - Secure "Login As" functionality with session isolation
  - Orange impersonation banner on user dashboard
  - "Return to Admin" functionality with proper session restoration
  - New window/tab opening to maintain admin session
- [x] Interactive admin dashboard ✅
  - Clickable statistics cards with smooth hover effects
  - Direct navigation to filtered user views (All, Admins, Recent, Inactive)
  - Professional visual feedback (lift, shadow, color changes)
  - Enhanced "Recent" filter for 7-day user activity
- [x] Security and protection systems ✅
  - Admin self-demotion protection (cannot change own role)
  - Admin self-deactivation protection (cannot deactivate self)
  - Demo user protection (cannot edit/delete demo accounts)
  - Comprehensive audit logging for all admin actions
- [x] Database and API enhancements ✅
  - New API endpoints: `/admin/api/edit-user.php`, `/admin/api/delete-user.php`
  - Router updates for impersonation endpoints
  - Transaction-safe user deletion with proper foreign key handling
  - Session management improvements for impersonation

#### Login Tracking & Admin Enhancement (v1.15.0) 🚀 NEW!
- [x] Complete login tracking system ✅
  - Database migration with `last_login` and `login_count` fields
  - Comprehensive tracking across all authentication methods (web, API, demo, password, code)
  - Proper timezone handling (EDT server time display)
  - Admin dashboard with login statistics and activity metrics
- [x] Enhanced admin interface ✅
  - Replaced "Recent Users" with practical "Admin Tools" navigation grid
  - 6 navigation cards: User Management, Business Cards, Analytics, Debug Log, My Cards, Future Tool
  - Professional hover effects and responsive grid layout
  - Removed clutter (eliminated "Created" column from user table)
- [x] Business cards admin enhancement ✅
  - Added "Login As" button to business cards view
  - Consistent green gradient styling matching user management
  - Full impersonation capabilities from business cards interface
  - Responsive button layout with proper wrapping
- [x] User activity insights ✅
  - Last login display with login count tooltips
  - Smart time formatting (EDT timestamps without conversion)
  - Activity-based filtering (recently active, inactive, never logged in)
  - Comprehensive login statistics on admin dashboard

#### Virtual Backgrounds Feature (v1.16.0) 🚀 NEW!
- [x] Complete virtual backgrounds system ✅
  - Professional image generation using PHP GD library
  - Real QR code integration (not placeholders) using external QR Server API
  - 5 theme-matched gradient backgrounds with custom color options
  - Multiple resolutions: 4K, 2K, HD, and standard formats
  - Advanced image embedding with configurable positioning and padding
- [x] User interface and experience ✅
  - Intuitive controls: sliders for QR size/padding, corner buttons for positioning
  - Live preview system with real-time updates
  - Professional UI matching ShareMyCard design language
  - Smart file naming with user's actual information
  - Session-based authentication for secure downloads
- [x] Database and API architecture ✅
  - Virtual background preferences table with color support
  - Database migration system for schema updates
  - Comprehensive error handling and validation
  - Rate limiting and security measures
- [x] Homepage integration ✅
  - Modern card-based layout replacing simple list design
  - Interactive QR code demo featuring actual Warrick AI business card
  - Clickable QR code (both scannable AND clickable)
  - Responsive grid layout with hover effects and smooth transitions
  - Virtual backgrounds prominently featured as key capability

#### Android vCard Compatibility Fix (v1.16.1) 🔧 NEW!
- [x] Android vCard import compatibility ✅
  - Changed vCard format from 4.0 to 3.0 for better Android support
  - Updated PHOTO parameter from MEDIATYPE to TYPE for vCard 3.0 compliance
  - Improved email type mapping for better Android recognition
  - Maintained full compatibility with iOS and other platforms
- [x] Cross-platform contact import ✅
  - Significantly improved contact import success rate on Android devices
  - Profile photos now display correctly in Android contacts
  - Email addresses properly categorized in Android contacts
  - Phone numbers correctly recognized and imported
  - Company information imports properly on all platforms

#### Email Signature Tracking (v1.17.0) 🚀 NEW!
- [x] Complete email tracking system ✅
  - 1x1 transparent GIF tracking pixel for email open detection
  - Database schema updates to support `email_open` event type
  - Analytics integration with geographic and device tracking
  - Privacy-compliant tracking with optional opt-out
- [x] Email signature enhancements ✅
  - Dedicated email signature pages for better user experience
  - Granular element control (name, title, company, phone, email, website, address)
  - Individual checkboxes for each signature element
  - Element reordering with up/down arrows
  - Placeholder text for missing data elements
- [x] Analytics dashboard integration ✅
  - Email opens appear in all analytics dashboards (user, admin, global)
  - Interactive charts showing email open trends over time
  - Geographic tracking of email opens by country/city
  - Device analytics for email opens (desktop, mobile, tablet)
  - Time period filtering (7d, 30d, 90d, all time)
- [x] Privacy and compliance ✅
  - Updated privacy policy to disclose email tracking
  - Optional tracking with clear user controls
  - DNT (Do Not Track) header support
  - 30-day data retention policy
  - Transparent data collection practices

#### User Invitation System (v1.18.0) 🚀 NEW!
- [x] Complete invitation system ✅
  - Invitation form with invitee details (name, email, business card selection)
  - Personalized email templates with business card links
  - Response tracking (interested/not interested/no response)
  - Account creation linking for conversion tracking
- [x] Email invitation features ✅
  - Professional email templates with business card preview
  - "Yes, I'm Interested" and "No, Not Interested" response buttons
  - Email open tracking with 1x1 transparent pixel
  - Secure invitation tokens for response validation
- [x] Analytics and tracking ✅
  - User invitation analytics page with response tracking
  - Admin invitation analytics with system-wide statistics
  - Conversion tracking from invitation to account creation
  - Resend invitation functionality with success/error feedback
- [x] Database and API architecture ✅
  - Invitations table with comprehensive tracking fields
  - Database migration system for schema updates
  - Secure token generation and validation
  - Rate limiting and security measures
- [x] User interface enhancements ✅
  - Clean invitation form with validation
  - Professional analytics tables with status indicators
  - Modal feedback system for user actions
  - Responsive design for all screen sizes
- [x] Navigation integration ✅
  - "📧 Invitations" link added to user hamburger menu navigation
  - "Send Invitation" button on invitation analytics page
  - Admin navigation includes invitation analytics access
  - Consistent navigation across all user pages
- [x] Demo account restrictions ✅
  - Demo accounts cannot send actual invitation emails
  - Demo invitation data cleared on each login for fresh experience
  - Invitation analytics work normally for demo users
  - Clear messaging about demo account limitations

#### Demo System Enhancement (v1.18.1) 🔧 NEW!
- [x] Database-driven demo system ✅
  - Demo data stored in dedicated `demo_data` table
  - Primary information only (one record per demo user)
  - Extra contact details stored in proper contact tables
  - No image generation - uses existing demo images
- [x] Enhanced demo cards ✅
  - Full addresses (street, city, state, zip, country)
  - Primary website links with proper tracking
  - Profile photos, company logos, and cover graphics
  - Professional themes (Professional Blue, Creative Sunset, Minimalist Gray)
- [x] Demo image management ✅
  - Uses existing demo images in `/storage/media/`
  - Correct filename mapping (demo-alex-profile.jpg, etc.)
  - No image generation during login
  - Clean database-driven approach
- [x] Demo system architecture ✅
  - `DemoUserHelper` class for demo user management
  - Database migrations for demo data structure
  - Proper table relationships and data integrity
  - Clean slate approach (cards reset on each login)

#### Image Creation Logging System (v1.19.0) 🚀 NEW!
- [x] Comprehensive image creation tracking ✅
  - Database table `image_creation_log` with full context tracking
  - Logs all image creation events (uploads, generation, demo copies)
  - Captures user context, page URLs, URL parameters, and file details
  - Tracks image types: profile photos, company logos, cover graphics, virtual backgrounds, name tags
- [x] ImageCreationLogger class ✅
  - Professional logging system with error handling
  - Session management and user context detection
  - File size and dimension tracking for created images
  - IP address and user agent logging for security
- [x] Admin dashboard integration ✅
  - Professional image creation history page with statistics
  - Real-time metrics: total images, by type, by method, recent activity
  - Filtering by image type, creation method, user, and date range
  - Direct links to view created images
- [x] System-wide logging coverage ✅
  - Media upload endpoints (profile photos, logos, cover graphics)
  - Virtual background generation and preview systems
  - Name tag PDF and HTML generation
  - Demo image copying during login process
  - All image creation points now have comprehensive logging
- [x] Critical bug fixes ✅
  - Fixed demo card recreation during session verification
  - Prevented unnecessary image generation on page access
  - Improved authentication flow to separate login from page access
  - Enhanced session handling for edge cases
- [x] Debugging and monitoring capabilities ✅
  - Complete audit trail of all image creation events
  - User behavior insights and system performance monitoring
  - Issue diagnosis and troubleshooting support
  - Future-proof foundation for system optimization

#### Duplicate Card Feature (v1.21.0) 🚀 NEW!
- [x] Complete duplicate card system ✅
  - iOS app: Duplicate button in card view toolbar with pre-filled creation form
  - Web app: Duplicate button with server-side API for complete card copying
  - Copies all contact information: emails, phones, websites, addresses
  - Generates new UUIDs for all related records to prevent conflicts
  - Redirects to edit page for immediate customization
- [x] Dashboard enhancements ✅
  - Card ID numbers displayed below action buttons for easy identification
  - Creation dates shown to distinguish original cards from duplicates
  - Clean, organized layout with visual separation
- [x] Cross-platform consistency ✅
  - Identical functionality on both iOS and web platforms
  - Server-side API ensures complete data duplication
  - Proper error handling and user feedback

#### Leads & Contacts Management System (v1.20.0) 🚀 NEW!
- [x] Complete lead capture system ✅
  - Public lead capture forms accessible via business card URLs
  - Comprehensive form with all contact fields (name, email, phone, company, address, etc.)
  - Rate limiting and spam prevention (5 submissions per hour)
  - Form validation and error handling
  - Professional styling with responsive design
- [x] Lead management dashboard ✅
  - Clean, simplified lead display (name, company, title, captured date)
  - Lead details modal with full information
  - Search and filter functionality
  - Lead conversion to contacts
  - Lead deletion with confirmation
  - Statistics dashboard (total leads, new leads, converted, conversion rate)
- [x] Contact management system ✅
  - Contact creation (manual or from leads)
  - Contact editing with full form validation
  - Contact details modal with comprehensive information
  - Contact deletion with cascade cleanup
  - Search and filter by source (converted from leads, manual)
  - Statistics dashboard (total contacts, from leads, manual, conversion rate)
- [x] Lead to contact conversion ✅
  - One-click conversion from leads to contacts
  - Preserves all lead data in contact record
  - Updates lead status to "converted"
  - Maintains relationship between lead and contact
  - Conversion tracking and analytics
- [x] Admin functionality ✅
  - Admin leads view (all leads across all users)
  - Admin contacts view (all contacts across all users)
  - Admin statistics and analytics
  - Read-only access for security
  - User activity monitoring
- [x] Demo system integration ✅
  - Demo data population (9 leads, 15 contacts)
  - Realistic sample data with professional information
  - Auto-population on demo user login
  - Clean slate approach (data resets on each login)
  - Full functionality demonstration
- [x] API endpoints ✅
  - Lead capture API with rate limiting
  - Lead CRUD operations (view, convert, delete)
  - Contact CRUD operations (create, read, update, delete)
  - Lead conversion API
  - Comprehensive error handling and validation
- [x] Security and validation ✅
  - Rate limiting for lead capture (prevents spam)
  - Input validation and sanitization
  - Authentication and authorization
  - SQL injection prevention
  - XSS protection
  - Session management

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
QRCard/                             # Main Project Directory
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
│   ├── ImagePicker.swift
│   ├── AuthService.swift           # Authentication service
│   ├── APIClient.swift             # API communication
│   ├── LoginView.swift             # Login interface
│   ├── PasswordSettingsView.swift  # Password management
│   ├── ForgotPasswordView.swift    # Password reset
│   ├── KeychainHelper.swift        # Secure token storage
│   ├── MediaService.swift          # Media upload service
│   ├── SyncManager.swift           # Data synchronization
│   ├── docsapi-spec.md             # API specification
│   ├── docsdatabase-schema.md      # Database schema
│   └── README.md                   # iOS app readme
│
├── web/                            # Web Application
│   ├── index.php                   # Landing page (redesigned with card layout)
│   ├── user/                       # User interface
│   │   ├── dashboard.php           # User dashboard
│   │   ├── login.php               # User login
│   │   ├── register.php            # User registration
│   │   ├── includes/               # User includes
│   │   │   ├── UserAuth.php        # User authentication
│   │   │   └── header.php          # User header
│   │   └── cards/                  # Card management
│   │       ├── create.php          # Create card
│   │       ├── edit.php            # Edit card
│   │       ├── view.php            # View card
│   │       ├── qr.php              # QR code display
│   │       ├── email-signature.php # Email signature generator ✨ NEW!
│   │       ├── virtual-background.php # Virtual backgrounds ✨ NEW!
│   │       ├── download-background.php # Background download
│   │       ├── preview-background.php # Background preview
│   │       ├── name-tags.php       # Name tag generator ✨ NEW!
│   │       ├── preview-name-tag.php # Name tag preview
│   │       ├── download-name-tags-html.php # Name tag PDF download
│   │       ├── invite.php          # Invitation form ✨ NEW!
│   │       ├── invitation-analytics.php # User invitation analytics ✨ NEW!
│   │       └── email-preview.php   # Email preview window ✨ NEW!
│   │   ├── contacts/               # Contact management ✨ NEW!
│   │   │   ├── index.php           # Contacts listing
│   │   │   ├── scan-qr.php         # QR code scanner
│   │   │   ├── qr-process.php      # QR processing page
│   │   │   └── api/                # Contact API endpoints
│   │   │       └── create-contact-from-qr.php # QR contact creation
│   ├── admin/                      # Admin interface
│   │   ├── dashboard.php           # Admin dashboard
│   │   ├── login.php               # Admin login
│   │   ├── users.php               # User management
│   │   ├── cards.php               # Business cards overview
│   │   ├── my-cards.php            # Admin's personal cards
│   │   ├── analytics.php           # Analytics dashboard
│   │   ├── debug-log.php           # Debug logging
│   │   ├── impersonate.php         # User impersonation
│   │   ├── end-impersonation.php   # End impersonation
│   │   ├── invitations.php         # Admin invitation analytics ✨ NEW!
│   │   ├── test/                   # Admin test files
│   │   │   └── test-*.php         # Admin test scripts
│   │   └── cards/                  # Admin card management
│   │       ├── email-signature.php # Admin email signature generator ✨ NEW!
│   │   ├── includes/               # Admin includes
│   │   │   ├── AdminAuth.php       # Admin authentication
│   │   │   ├── header.php          # Admin header
│   │   │   └── admin-style.css     # Admin styles
│   │   └── api/                    # Admin API endpoints
│   │       ├── edit-user.php       # Edit user
│   │       └── delete-user.php     # Delete user
│   ├── api/                        # RESTful API
│   │   ├── auth/                   # Authentication endpoints
│   │   │   ├── register.php        # User registration
│   │   │   ├── login.php           # User login
│   │   │   ├── verify.php          # Email verification
│   │   │   ├── set-password.php    # Set password
│   │   │   ├── change-password.php # Change password
│   │   │   ├── reset-password-request.php # Password reset request
│   │   │   ├── reset-password-complete.php # Password reset complete
│   │   │   └── check-password-status.php # Check password status
│   │   ├── cards/                  # Card endpoints
│   │   │   ├── index.php           # Card CRUD operations
│   │   │   ├── qrcode.php          # QR code generation
│   │   │   ├── generate-background.php # Virtual background generation
│   │   │   └── background-preferences.php # Background preferences
│   │   ├── media/                  # Media endpoints
│   │   │   ├── upload.php          # Media upload
│   │   │   ├── delete.php          # Media deletion
│   │   │   └── list.php            # Media listing
│   │   ├── analytics/              # Analytics endpoints
│   │   │   ├── track.php           # Event tracking
│   │   │   ├── stats.php           # Statistics
│   │   │   └── pixel.php           # Email tracking pixel ✨ NEW!
│   │   ├── process-qr-image.php    # QR code processing API ✨ NEW!
│   │   ├── test/                   # API test files
│   │   │   └── test-*.php         # API test scripts
│   │   ├── user/                   # User API endpoints
│   │   │   └── api/                # User API subdirectory
│   │   │       ├── send-invitation.php # Send invitation API ✨ NEW!
│   │   │       ├── resend-invitation.php # Resend invitation API ✨ NEW!
│   │   │       └── preview-invitation.php # Preview invitation API ✨ NEW!
│   │   └── includes/               # API includes
│   │       ├── Database.php        # Database connection
│   │       ├── Api.php             # API base class
│   │       ├── GmailClient.php     # Email service
│   │       ├── EmailTemplates.php  # Email templates
│   │       ├── VirtualBackgroundGenerator.php # Virtual backgrounds ✨ NEW!
│   │       ├── NameTagGenerator.php # Name tag generation ✨ NEW!
│   │       ├── DemoUserHelper.php  # Demo user utilities
│   │       └── RateLimiter.php     # Rate limiting
│   ├── config/                     # Configuration
│   │   ├── database.php            # Database config
│   │   ├── gmail.php               # Email config
│   │   ├── secure-config.php       # Secure config loader
│   │   ├── env.example             # Environment template
│   │   └── migrations/             # Database migrations
│   │       ├── 001_initial_schema.sql
│   │       ├── 002_add_media_fields.sql
│   │       ├── 003_add_analytics.sql
│   │       ├── 004_add_themes.sql
│   │       ├── 005_add_primary_contacts.sql
│   │       ├── 006_add_password_auth.sql
│   │       ├── 007_add_demo_user.sql
│   │       ├── 008_demo_cards.sql
│   │       ├── 009_demo_cards_data.sql
│   │       ├── 010_add_user_roles.sql
│   │       ├── 011_add_login_tracking.sql
│   │       ├── 012_virtual_backgrounds_preferences.sql # ✨ NEW!
│   │       ├── 013_add_color_columns.sql # ✨ NEW!
│   │       ├── 014_name_tag_preferences.sql # ✨ NEW!
│   │       ├── 015_add_website_and_numeric_line_spacing.sql # ✨ NEW!
│   │       ├── 016_add_font_size.sql # ✨ NEW!
│   │       ├── 017_add_custom_messages.sql # ✨ NEW!
│   │       ├── 017_add_email_tracking.sql # Email tracking support ✨ NEW!
│   │       ├── 018_add_email_opens_to_daily.sql # Email opens analytics ✨ NEW!
│   │       └── 019_add_invitations.sql # Invitation system ✨ NEW!
│   ├── includes/                   # Shared includes
│   │   ├── cookie-banner.php       # Cookie consent
│   │   ├── image-editor.php        # Image editing
│   │   └── themes.php              # Theme system
│   ├── storage/                    # File storage
│   │   ├── media/                  # User media files
│   │   └── rate-limits/            # Rate limiting data
│   ├── card.php                    # Public card view
│   ├── vcard.php                   # vCard generation
│   ├── privacy.php                 # Privacy policy
│   ├── invitation-response.php     # Invitation response page ✨ NEW!
│   └── router.php                  # URL routing
│
├── sharemycard-config/             # Secure Configuration (outside web root)
│   ├── database.php                # Production database config
│   ├── gmail.php                   # Production email config
│   ├── env.production              # Production environment
│   └── setup.php                   # Configuration setup
│
├── scripts/                        # Development Scripts & Tests
│   ├── test/                       # Test scripts and files
│   │   ├── test-*.sh              # Shell test scripts
│   │   └── test-*.php             # PHP test files
│   ├── debug/                      # Debug utilities
│   │   └── debug-*.sh             # Debug scripts
│   ├── check/                      # Validation scripts
│   │   └── check-*.sh             # Check scripts
│   ├── contacts/                   # Contact management scripts
│   │   └── contacts-*.sh          # Contact sync scripts
│   └── README.md                   # Scripts documentation
├── Scripts/                        # Utility Scripts
│   ├── generate_app_icon.swift     # App icon generation
│   ├── generate_icons.sh           # Icon generation script
│   ├── aggregate-analytics.php     # Analytics aggregation
│   ├── generate-test-analytics.php # Test data generation
│   └── verify-analytics-deployment.sh # Deployment verification
│
├── .cody/                          # Cody Framework Documentation
│   └── project/
│       └── plan/                   # Implementation plans
│           ├── virtual-backgrounds.md # Virtual backgrounds plan ✨ NEW!
│           ├── admin-enhancements.md  # Admin enhancements plan
│           └── login-tracking.md      # Login tracking plan
│
├── images/                         # Static Images
│   └── warrick-ai-qr.png          # Demo QR code ✨ NEW!
│
├── RETROSPECTIVE.md                # Complete development retrospective
├── RETROSPECTIVE-WEB-ENHANCEMENT.md # Web enhancement retrospective
├── ANALYTICS-IMPLEMENTATION.md     # Analytics implementation guide
├── MEDIA-UPLOAD-IMPLEMENTATION.md  # Media upload guide
├── THEMES-IMPLEMENTATION.md        # Themes implementation guide
├── TESTFLIGHT-SETUP.md             # TestFlight setup guide
├── TESTING-GUIDE.md                # Testing guide
├── SECURITY.md                     # Security documentation
├── PRIVACY-POLICY.md               # Privacy policy
├── deploy.sh                       # Deployment script
├── security-check.sh               # Security verification
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
- [Custom QR Codes](CUSTOM-QR-CODES.md) - End-to-end docs for Custom QR functionality ✨ NEW!
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
- [x] **Leads & Contacts Management System** ✅
  - [x] Lead capture forms and API
  - [x] Lead management dashboard
  - [x] Contact management system
  - [x] Lead to contact conversion
  - [x] Admin functionality
  - [x] Demo system integration
- [x] **QR Code Scanning System** ✅
  - [x] Camera-based QR code scanning
  - [x] vCard parsing and contact creation
  - [x] URL processing for redirect QR codes
  - [x] Source tracking and metadata
  - [x] Error handling and user feedback
- [ ] Branded QR codes with logo overlay
- [ ] Social media integration (Facebook, LinkedIn, Twitter)

### Phase 4: Advanced Features 🔮 FUTURE
- [ ] **Enhanced Analytics & Reporting**
  - [ ] Advanced lead analytics (conversion rates, lead sources, trends)
  - [ ] Contact interaction tracking
  - [ ] Export functionality (CSV/Excel)
  - [ ] Custom date range filtering
- [ ] **Automation Features**
  - [ ] Lead scoring system
  - [ ] Automated follow-up emails
  - [ ] Email notifications for new leads
  - [ ] Activity tracking and history
- [ ] **Integration Features**
  - [ ] CRM integration (Salesforce, HubSpot)
  - [ ] Email marketing platforms (Mailchimp, Constant Contact)
  - [ ] Calendar integration
  - [ ] Social media profile linking
- [ ] **Mobile Features**
  - [ ] QR code scanning for lead capture
  - [ ] Push notifications
  - [ ] Offline lead capture
  - [ ] Native mobile app enhancements

### Phase 5: Scale & Polish 🔮 FUTURE
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

**Current Version**: 1.22.0 (Duplicate Card Feature)  
**Last Updated**: December 15, 2024  
**Status**: 🚀 **Complete Digital Business Card Platform with Lead Generation, Contact Management, QR Scanning & Card Duplication** - Full-featured lead capture system, contact management, conversion workflow, QR code scanning, card duplication, and comprehensive demo system

### What's Working
- ✅ iOS app with full CRUD operations
- ✅ Web app with user and admin interfaces
- ✅ Complete contact information management
- ✅ QR code generation with full vCard data + profile photos
- ✅ **Dual Authentication System** ✅
  - Password authentication (satisfies Apple reviewers)
  - Email verification codes (passwordless option)
  - Backward compatibility for existing users
  - Password management (set, change, reset)
  - Rate limiting and security best practices
- ✅ **Demo Account System** ✨ NEW!
  - Instant login for Apple TestFlight reviewers
  - Pre-populated with 3 professional sample business cards
  - Email suppression (no emails sent to demo users)
  - Rate limiting bypass for demo users
  - Clean slate approach (cards reset on every login)
  - Full functionality (create, edit, delete cards)
  - Professional card deletion with modal confirmation
- ✅ **Enterprise Admin User Management** ✨ NEW!
  - Complete user editing with email, password, status, and role management
  - Secure user impersonation ("Login As") with session isolation
  - Professional user deletion with full cascade cleanup
  - Interactive dashboard with clickable statistics cards
  - Admin self-protection (cannot demote/deactivate self)
  - Demo user protection (cannot edit/delete demo accounts)
  - Comprehensive audit logging for all admin actions
- ✅ **Login Tracking & User Activity Insights** ✨ NEW!
  - Complete login tracking across all authentication methods
  - Last login timestamps with proper EDT timezone display
  - Login count tracking with tooltip information
  - User activity filtering (recently active, inactive, never logged in)
  - Enhanced admin dashboard with login statistics
  - Professional admin tools navigation grid
  - Business cards view with impersonation capabilities
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
- ✅ **Virtual Backgrounds Generator** ✨ NEW!
  - Professional image generation using PHP GD library
  - Real QR code integration with external QR Server API
  - 5 theme-matched gradient backgrounds with custom color options
  - Multiple resolutions: 4K, 2K, HD, and standard formats
  - Advanced image embedding with configurable positioning and padding
  - Smart file naming with user's actual information
  - Live preview system with real-time updates
  - Session-based authentication for secure downloads
- ✅ **Cross-Platform vCard Compatibility** ✨ NEW!
  - vCard 3.0 format for excellent Android compatibility
  - Profile photos display correctly on all platforms
  - Email addresses properly categorized in Android contacts
  - Phone numbers correctly recognized and imported
  - Company information imports properly across all devices
- ✅ **Name Tag Generator** ✨ NEW!
  - Professional PDF generation with 8 name tags per sheet
  - QR code integration with business card information
  - Custom message support (above and below contact info)
  - Content selection (name, title, company, phone, email, address, website)
  - Font size control with dynamic scaling based on content length
  - Live preview with real-time layout updates
  - Smart QR code sizing to prevent text overlap
  - Standard label compatibility (3.375" x 2.33")
  - Usage instructions with product links
- ✅ **Mobile User Experience** ✨ NEW!
  - Hamburger menu for user navigation on mobile devices
  - Responsive design with touch-friendly navigation
  - Smooth animations and transitions
  - Auto-close functionality for better UX
  - Admin navigation remains unchanged (desktop-style)
- ✅ **Analytics Dashboard**
  - Real-time tracking (views, clicks, downloads, **email opens**)
  - Geographic insights (countries, cities)
  - Device/browser/OS analytics
  - Interactive charts (Chart.js)
  - Time period filters (7d, 30d, 90d, all time)
  - CSV export
  - Privacy-compliant (cookie consent, DNT support)
  - Automated daily aggregation via CRON
- ✅ **Email Signature Tracking** ✨ NEW!
  - 1x1 transparent GIF tracking pixel for email open detection
  - Optional tracking with user controls and privacy compliance
  - Email opens integrated into analytics dashboards
  - Geographic and device tracking for email opens
  - Dedicated email signature pages with granular element control
  - Element reordering and placeholder text for missing data
- ✅ **User Invitation System** ✨ NEW!
  - Complete invitation system with personalized email templates
  - Response tracking (interested/not interested/no response)
  - Account creation linking for conversion tracking
  - User and admin invitation analytics with resend functionality
  - Professional email templates with business card preview
  - Secure invitation tokens and email open tracking
- ✅ **Leads & Contacts Management System** ✨ NEW!
  - Complete lead capture system with public forms and rate limiting
  - Lead management dashboard with search, filter, and conversion
  - Contact management system with full CRUD operations
  - Lead to contact conversion workflow with data preservation
  - Admin functionality for system-wide lead and contact monitoring
  - Demo system integration with realistic sample data (9 leads, 15 contacts)
  - Comprehensive API endpoints with security and validation
  - Professional UI with clean, simplified displays and detailed modals
- ✅ **QR Code Scanning System** ✨ NEW!
  - Camera-based QR code scanning for contact import
  - Real-time QR detection using html5-qrcode library
  - vCard parsing with support for all contact fields
  - URL processing for QR codes that redirect to vCard files
  - Flexible contact form with all fields optional
  - Source tracking and metadata for scanned contacts
  - Error handling with clear user feedback
  - Integration with contacts management system
- ✅ **Duplicate Card Feature** ✨ NEW!
  - One-click card duplication on both iOS and web platforms
  - Complete data copying: all contacts, emails, phones, websites, addresses
  - Server-side API ensures reliable duplication with new UUIDs
  - Dashboard enhancements with card IDs and creation dates
  - Pre-filled forms for immediate customization
  - Cross-platform consistency and error handling

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

### What's Next (v1.21.0+)
- 🔄 **Enhanced Analytics & Reporting** - Advanced lead analytics, conversion rates, export functionality
- 🔄 **Automation Features** - Lead scoring, automated follow-up emails, activity tracking
- 🔄 **Integration Features** - CRM integration, email marketing platforms, calendar integration
- 🔄 **Mobile Features** - QR code scanning, push notifications, offline capture
- 🔄 **iOS Name Tag Integration** - Add name tag generation to iOS app
- 🔄 **iOS Virtual Backgrounds Integration** - Add virtual background generation to iOS app
- 🔄 **Advanced User Analytics** - Login patterns, activity monitoring, inactive account detection
- 🔄 **Automated Account Management** - Inactive account cleanup, user engagement insights
- 🔄 **Enhanced Admin Dashboard** - User activity trends, engagement metrics, automated reports
- 🔄 Branded QR codes with logo overlay
- 🔄 Background sync with retry logic
- 🔄 Sync status indicators in UI
- 🔄 Offline queue for network failures
- 🔄 Export to PDF/PNG
- 🔄 Search and filter functionality

---

**Ready to share your digital business card! 🚀**

---

## 🔳 Custom QR Codes (Web) ✨ NEW!

Custom QR Codes let users create QR experiences beyond business cards with optional landing pages and lead capture.

- Types: `default` (landing), `url` (redirect), `social` (username → URL), `text`, `wifi`, `appstore`
- User pages: `user/qr/index.php`, `user/qr/create.php`, `user/qr/edit.php`, `user/qr/analytics.php`
- Admin pages: `admin/qr/index.php`, `admin/qr/global-analytics.php`
- Public handler: `/qr/{uuid}` via `web/public/qr.php` (rate limited)
- Public templates: `web/public/includes/qr/{landing.php,text-landing.php,wifi-landing.php,appstore-interstitial.php}`
- Analytics tracking: `custom_qr_events` (view, redirect, lead_submit) with device/browser/OS/city/country
- Leads: linked via `qr_leads` and existing `leads` table (`qr_id` column)
- Theming: per-QR `theme_key` using `web/includes/themes.php`
- Sanitization & Security: `api/includes/Sanitize.php`, `api/includes/RateLimiter.php` with whitelist support
- QR image helper: `api/includes/qr/Generator.php` (external QRServer)
- Inactive page: `public/includes/qr/inactive.php` when QR `status !== active`

Database migrations:
- `030_create_custom_qr_codes.sql` – base tables (`custom_qr_codes`, `custom_qr_events`, `qr_leads`)
- `031_make_leads_card_nullable.sql` – lead linkage
- `032_alter_custom_qr_user_id.sql` – `user_id` to UUID (VARCHAR)
- `033_add_analytics_columns.sql` – `device_type`, `browser`, `location_type`
- `034_add_os_city_country.sql` – `os`, `city`, `country`

Routes & rewrites:
- `.htaccess` root rewrite: `^qr/.*$ → /qr.php`
- `web/router.php` routes `/qr/{id}` and `/api/leads/capture`

Testing:
- Smoke tests: `web/tests/smoke/qr_smoke.php` (redirects, 404s, interstitials)

Notes:
- Global analytics for user at `user/qr/global-analytics.php`
- GeoIP uses ip-api.com with 24h file cache; fails gracefully offline
- Views-over-time charts backfill gaps for continuous timelines

