# Product Requirements Document (PRD)
This document formalizes the idea and defines the what and the why of the product the USER is building.

## Section Explanations
| Section           | Overview |
|-------------------|--------------------------|
| Summary           | Sets the high-level context for the product. |
| Goals             | Articulates the product's purpose — core to the "why". |
| Target Users      | Clarifies the audience, essential for shaping features and priorities. |
| Key Features      | Describes what needs to be built to meet the goals — part of the "what". |
| Success Criteria  | Defines what outcomes validate the goals. |
| Out of Scope      | Prevents scope creep and sets boundaries. |
| User Stories      | High-level stories keep focus on user needs (why) and guide what to build. |
| Assumptions       | Makes the context and unknowns explicit — essential for product clarity. |
| Dependencies      | Identifies blockers and critical integrations — valuable for planning dependencies and realism. |

## Summary
An open-source digital business card system that enables iOS users to create, manage, and share digital business cards via QR codes at networking events, with both mobile and web interfaces for comprehensive contact management.

## Goals
- Create a functional iOS SwiftUI application for digital business card management
- Develop a complementary web application with enhanced functionality
- Establish a secure, scalable backend API architecture
- Provide excellent documentation for open-source community adoption
- Enable seamless cross-platform synchronization between mobile and web interfaces

## Target Users
iOS users who attend in-person networking events and want to share contact information digitally. This includes professionals, entrepreneurs, small business owners, and anyone who needs to exchange contact details efficiently at networking events, conferences, or business meetings.

## Key Features
- **Email-based Authentication**: Simple registration and login using only email addresses with verification codes
- **Business Card Creation**: Comprehensive contact information management with required fields (name, phone) and optional fields (emails, addresses, photos, company info, bio)
- **QR Code Generation**: Automatic QR code creation for easy sharing at networking events
- **Cross-Platform Sync**: Real-time synchronization between iOS app and web interface
- **Persistent Mobile Login**: Stay logged in on mobile devices until manual logout
- **Secure API Architecture**: RESTful API protecting database credentials from mobile clients
- **Web Enhancement**: Additional functionality available through web interface beyond mobile capabilities

## Success Criteria
- Functional iOS application that successfully creates and shares digital business cards
- Web application with enhanced features complementing the mobile app
- Secure API implementation with proper credential protection
- Complete documentation enabling open-source community contribution
- Successful data synchronization between all platforms
- QR code sharing functionality working reliably at networking events

## Out of Scope (Version 1.0)
- Multiple business cards per user
- Contact management and storage
- Lead capture analytics
- Live chat between contacts
- Advanced sharing options beyond QR codes
- Android application
- Offline capabilities

## User Stories
- As a networking event attendee, I want to quickly share my contact information by showing a QR code so that others can easily save my details to their phone
- As a professional, I want to create a comprehensive digital business card with my photo, company logo, and multiple contact methods so that I present a professional image
- As a mobile user, I want to stay logged into the app so that I don't have to re-authenticate every time I use it
- As a user, I want to edit my business card information on both my phone and computer so that I can manage my details from any device
- As a developer, I want access to well-documented open-source code so that I can learn from and contribute to the project

## Assumptions
- Users have reliable internet connectivity at networking events
- iOS users are comfortable with QR code scanning
- Email delivery for verification codes will be reliable
- Users will primarily use the system for in-person networking events
- The open-source nature will attract developer interest and contributions
- Cross-platform synchronization is technically feasible with the chosen tech stack
- PHP/MySQL backend can handle the expected user load and data synchronization requirements

## Dependencies
- PHP 8.1+ server environment
- Apache 2.4+ web server
- MySQL 8+ database system
- iOS development environment (Xcode, Swift 5.9+)
- Email service provider for verification codes
- QR code generation library/API
- Secure API authentication and authorization system
- Cross-platform data synchronization mechanism
