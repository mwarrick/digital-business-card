# ShareMyCard - Digital Business Card System

A modern digital business card system with QR code sharing, available both as a web application and iOS app.

## 🎯 Project Status

### ✅ Completed Features

#### iOS App (SwiftUI)
- **Data Model**: Complete `BusinessCard` struct with all required and optional fields
- **Local Storage**: Core Data integration with programmatic model
- **Business Card Creation**: Full form with image picker integration
- **Business Card Editing**: Complete edit functionality for all fields
- **Business Card Display**: Beautiful, shareable card view
- **QR Code Generation**: vCard format QR codes for easy contact sharing
- **QR Code Scanning**: Camera-based QR code scanning to import contacts
- **Image Management**: Profile photos, company logos, cover graphics
- **Navigation**: Complete app navigation between all views

#### Web Application Foundation
- **Database Setup**: Remote MySQL database with SSH tunnel connection
- **Database Schema**: Complete schema with 6 tables (users, business_cards, email_contacts, phone_contacts, website_links, addresses)
- **PHP Development Server**: Local development environment
- **Database Connection**: Working connection through SSH tunnel
- **Sample Data**: Test business card data in database

### 🚧 In Progress
- PHP REST API development
- Web interface for business card management
- iOS-Web integration

### 📋 Planned Features
- User registration and authentication
- Real-time sync between iOS and web
- Lead capture analytics
- Contact management
- Advanced sharing options

## 🏗️ Architecture

### iOS App (SwiftUI)
- **Data Layer**: Core Data with programmatic model
- **UI Layer**: SwiftUI views with modern design
- **Features**: QR generation/scanning, image picker, form validation
- **Navigation**: Tab-based navigation with sheet presentations

### Web Application
- **Backend**: PHP 8.1+ with MySQL 8+
- **Database**: Remote MySQL with SSH tunnel security
- **API**: RESTful endpoints (in development)
- **Frontend**: HTML, CSS, JavaScript (planned)

## 📱 Business Card Data Model

### Required Fields
- First Name
- Last Name  
- Phone Number

### Optional Fields
- Additional emails (personal, work)
- Additional phone numbers (mobile, home, work)
- Website links with descriptions
- Full address
- Company name and job title
- Bio with auto-hyperlinked URLs
- Profile photo, company logo, cover graphic

## 🛠️ Tech Stack

### iOS Development
- **Language**: Swift 5.9+
- **Framework**: SwiftUI
- **Storage**: Core Data
- **QR Codes**: Core Image
 
- **Images**: UIImagePickerController, PHPickerViewController

### Web Development
- **Backend**: PHP 8.1+
- **Database**: MySQL 8+
- **Security**: SSH tunnel for database access
- **Server**: Apache 2.4+ (production), PHP built-in server (development)

## 📁 Project Structure

```
ShareMyCard/
├── ShareMyCard/                    # iOS SwiftUI App
│   ├── BusinessCard.swift     # Data model
│   ├── DataManager.swift      # Core Data manager
│   ├── ContentView.swift      # Main app view
│   ├── BusinessCardCreationView.swift
│   ├── BusinessCardEditView.swift
│   ├── BusinessCardListView.swift
│   ├── BusinessCardDisplayView.swift
│   ├── QRCodeGenerator.swift
│   ├── QRCodeScannerView.swift
│   ├── ImagePicker.swift
│   ├── SharedViews.swift
│   └── CoreDataEntities.swift
├── web/                       # Web Application
│   ├── config/
│   │   ├── database.php       # Database configuration
│   │   └── schema.sql         # Database schema
│   ├── test-db.php           # Database connection test
│   ├── test-simple.php       # Simple connection test
│   └── index.php             # Welcome page
└── docs/                     # Documentation
    ├── api-spec.md           # API specification
    └── database-schema.md    # Database documentation
```

## 🚀 Getting Started

### iOS Development
```bash
cd ShareMyCard/
open ShareMyCard.xcodeproj
```
- Build and run in Xcode
- Test on iOS Simulator or device
- All features are functional and ready to use

### Web Development
```bash
cd ShareMyCard/web/
php -S localhost:8000
```
- Access at `http://localhost:8000`
- Database connection tests available
- SSH tunnel required for database access

### Database Setup
1. Set up SSH tunnel: `ssh -L 3306:localhost:3306 -i ~/.ssh/id_rsa -p [PORT] [USERNAME]@[HOST]`
2. Database credentials configured in `web/config/database.php`
3. Schema automatically creates all required tables

## 🔧 Development Status

### iOS App: ✅ Complete
- All core features implemented and tested
- Ready for App Store submission
- Comprehensive error handling and validation
- Modern SwiftUI design patterns

### Web App: 🚧 Foundation Complete
- Database connection established
- Schema deployed
- Ready for API development
- Next: REST API endpoints and web interface

## 📊 Recent Achievements

- ✅ Complete iOS app with all features
- ✅ Remote MySQL database setup with SSH security
- ✅ Database schema deployment
- ✅ Connection testing and validation
- ✅ Sample data integration
- ✅ Development environment setup

## 🎯 Next Steps

1. **Build PHP REST API** for business card operations
2. **Create web interface** for business card management
3. **Implement user authentication** system
4. **Test iOS-Web integration**
5. **Deploy to production** environment

## License

[Add your license here]