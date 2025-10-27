# QR Code Scanner - iOS Implementation TODO

## Overview
This document outlines the implementation plan for adding QR code scanning functionality to the iOS ShareMyCard app, based on the web implementation completed in January 2025.

## Web Implementation Reference
The web implementation is located at:
- Scanner Page: `web/user/contacts/scan-qr.php`
- API Endpoint: `web/user/api/create-contact-from-qr.php`
- Database Migration: `web/config/migrations/add_contact_source_tracking.sql`

## iOS Implementation Plan

### 1. Required Frameworks
- **AVFoundation**: Camera access and video capture
- **VisionKit**: QR code detection and scanning
- **SwiftUI**: UI components and navigation

### 2. Key Components to Implement

#### A. QRScannerView (SwiftUI View)
```swift
struct QRScannerView: View {
    @StateObject private var scanner = QRCodeScanner()
    @State private var scannedData: String?
    @State private var showingContactForm = false
    
    var body: some View {
        // Camera preview with QR detection overlay
        // Start/Stop scanning controls
        // Camera selection (front/back)
    }
}
```

#### B. QRCodeScanner (ObservableObject)
```swift
class QRCodeScanner: NSObject, ObservableObject {
    private let captureSession = AVCaptureSession()
    private var videoPreviewLayer: AVCaptureVideoPreviewLayer?
    private let metadataOutput = AVCaptureMetadataOutput()
    
    @Published var isScanning = false
    @Published var scannedText: String?
    
    func startScanning()
    func stopScanning()
    func switchCamera()
}
```

#### C. VCardParser (Utility Class)
```swift
class VCardParser {
    static func parse(_ vcardString: String) -> ContactData? {
        // Parse vCard format similar to web implementation
        // Extract: FN, N, TEL, EMAIL, ORG, TITLE, ADR, URL, BDAY, NOTE
        // Handle multiple phone/email entries
        // Map to ContactData struct
    }
}

struct ContactData {
    var firstName: String
    var lastName: String
    var email: String
    var phones: [PhoneContact]
    var organization: String?
    var jobTitle: String?
    var address: Address?
    var website: String?
    var birthday: Date?
    var notes: String?
}
```

#### D. ContactConfirmationView (SwiftUI View)
```swift
struct ContactConfirmationView: View {
    @State private var contactData: ContactData
    @State private var isSaving = false
    
    var body: some View {
        // Editable form with all contact fields
        // Pre-populated with parsed vCard data
        // Save/Cancel buttons
        // Real-time validation
    }
}
```

### 3. Integration Points

#### A. Add to ContentView
Add "Scan QR Code" button to the main ContentView action buttons section:
```swift
Button("Scan QR Code") {
    showingQRScanner = true
}
.buttonStyle(.bordered)
.controlSize(.large)
```

#### B. Add to BusinessCardListView
Add QR scanner option in the contacts/leads management section.

#### C. API Integration
Use the existing `APIClient` to call the web API endpoint:
```swift
func createContactFromQR(_ contactData: ContactData) async throws -> ContactResponse {
    // POST to /user/api/create-contact-from-qr.php
    // Include source metadata (scan_timestamp, device_type, etc.)
}
```

### 4. vCard Format Support
Implement the same vCard parsing logic as the web version:
- Support vCard 3.0 and 4.0 formats
- Handle line folding (continuation lines starting with space/tab)
- Parse escaped characters in values
- Support multiple TYPE parameters (e.g., `TEL;TYPE=WORK,VOICE`)
- Handle both `\n` and `\r\n` line breaks

### 5. Camera Permissions
Add required permissions to Info.plist:
```xml
<key>NSCameraUsageDescription</key>
<string>This app needs camera access to scan QR codes for contact import.</string>
```

### 6. Error Handling
- Camera access denied
- QR code not detected
- Invalid vCard format
- Network errors during save
- Graceful fallback for unsupported devices

### 7. UI/UX Considerations
- Full-screen camera view with scanning overlay
- Visual feedback for successful scans
- Loading states during API calls
- Error messages with retry options
- Accessibility support for VoiceOver

### 8. Testing Strategy
- Test with various vCard formats
- Test camera switching on devices with multiple cameras
- Test error scenarios (no camera, denied permissions)
- Test on different iOS versions (iOS 14.0+)
- Test on different device types (iPhone, iPad)

### 9. Dependencies
- iOS 14.0+ (for VisionKit)
- Xcode 12.0+
- Swift 5.3+

### 10. Implementation Order
1. Set up basic camera access and QR detection
2. Implement vCard parsing utility
3. Create contact confirmation form
4. Integrate with existing API client
5. Add UI navigation and error handling
6. Test and refine user experience

## Notes
- The web implementation uses html5-qrcode library for QR detection
- iOS will use native VisionKit framework for better performance and integration
- Consider adding haptic feedback for successful scans
- The web API endpoint already supports the required metadata tracking
- Database schema changes are already in place from web implementation
