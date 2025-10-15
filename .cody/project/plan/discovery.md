# Discovery
This document captures the raw, unfiltered idea or initial AI prompt that sparked the project.

## Starting Prompt
**Digital Business Card**

A modern digital business card system with QR code sharing, available both as a web application and iOS app.

## Questions

**AGENT Q1:** Who are the target users for this digital business card system? Are you primarily targeting professionals, small business owners, or a broader audience?  
**USER A1:** anyone using an ios phone to share a digital business card with other mobile phone users

**AGENT Q2:** What specific problem does this digital business card system solve that traditional business cards or existing digital solutions don't address well?  
**USER A2:** although there are many digital business card proviers like popl.co, I wanted to build this one as an educational project which I intend to release as open source.

**AGENT Q3:** What would success look like for this project? Are you aiming for a certain number of users, specific functionality milestones, or is the primary goal to create a well-documented open source project that others can learn from and contribute to?  
**USER A3:** The primary goal is a functional IOS application with excellent documentation and complimentary website with similar functionalty to the ios app, but with more functionality than can be done on the ios app

**AGENT Q4:** What are the must-have features for version 1.0? Based on your description, I'm thinking: user registration, basic business card creation with required fields (name, phone), QR code generation, and web/mobile sync. Are there any other critical features that must be included in the initial release?  
**USER A4:** Digital business card system available online which is accessible via a QR code people can share with their phone. Editable both online with a web browser and via the IOS app. Tech stack PHP 8.1+ Apache 2.4+ MySQL 8+ HTML, CSS, JavaScript for website front end API for data retrieval, insert, update, deletion. Registration: Simple registration - only email is required to register. Verification: send login code to entered email, enter to verify registration. Future logins: Persistent "logged in" state on mobile device - stay logged in until they logout. Simple sign on - send login code to email. Business Card Data: Required fields: First Name, Last Name, Phone Number. Optional data: Additional emails (select type: personal, work), Additional phone numbers (select type: mobile, home, work), Links to website (name of website, link), Address, Company Name, Job Title, Brief bio (text only, but auto-hyperlink URLs), Profile photo, Company logo, Cover graphic. Backlog features: Additional digital business cards, Store connections, Lead capture, Store contacts received via this app, Live chat between contacts

**AGENT Q5:** What are the primary use cases for this system? For example, are users mainly sharing their cards at networking events, including them in email signatures, or using them for day-to-day business interactions? Understanding the main scenarios will help prioritize features.  
**USER A5:** primarily for in-person networking events

**AGENT Q6:** What constraints should we keep in mind for this project? For example, do you have a specific timeline for completion, any budget limitations, or technical constraints like hosting requirements or specific iOS version targets?  
**USER A6:** Tech stack PHP 8.1+ Apache 2.4+ MySQL 8+ HTML, CSS, JavaScript for website front end API for data retrieval, insert, update, deletion

**AGENT Q7:** What existing solutions are people using today for digital business cards at networking events? You mentioned Popl.co - are there other competitors or alternatives that users might be comparing this to, and what would make your solution stand out?  
**USER A7:** HiHello, Blinq, Mobilo, Linq, and V1CE are common alternatives... my solution will stand out as an open-source project

**AGENT Q8:** What risks or assumptions should we note early in the planning? For example, are there any technical challenges you anticipate with the QR code generation, cross-platform sync, or email authentication system? Any assumptions about user behavior or adoption that could impact the design?  
**USER A8:** for project success, must assume that data will synchronize between the mysql server and each user's ios client... we will need to establish a secure api between the two so that login credentials of the mysql server itself are not stored anywhere on the app

## Discovery Summary

The project is an **open-source digital business card system** designed to compete with commercial solutions like Popl.co, HiHello, Blinq, Mobilo, Linq, and V1CE by being freely available and educational. The system targets iOS users who want to share digital business cards at in-person networking events.

**Core Architecture:**
- iOS SwiftUI app for mobile users
- Web application with enhanced functionality beyond mobile capabilities
- PHP 8.1+/Apache 2.4+/MySQL 8+ backend with RESTful API
- QR code-based sharing mechanism

**Key Features:**
- Email-only registration with verification codes (no passwords)
- Persistent login state on mobile devices
- Real-time synchronization between web and iOS platforms
- Comprehensive business card data management (required: first name, last name, phone; optional: multiple emails/phones, addresses, photos, company info, bio with auto-hyperlinked URLs)

**Technical Requirements:**
- Secure API architecture protecting database credentials
- Cross-platform data synchronization
- QR code generation and sharing
- Email-based authentication system

**Success Criteria:**
- Functional iOS application with excellent documentation
- Complementary website with enhanced features
- Open-source project for educational purposes

**Primary Use Case:** In-person networking events where users share QR codes to exchange contact information

**Key Risk/Assumption:** Successful implementation of secure data synchronization between MySQL server and iOS clients without exposing database credentials in the mobile app.
