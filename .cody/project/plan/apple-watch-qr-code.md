# Apple Watch - QR Code Display

## Overview
Create an Apple Watch app that displays a user's business card QR code on their Apple Watch, allowing quick access to share their card from their wrist without needing their iPhone.

## Goals
- Display business card QR code on Apple Watch
- Allow users to select which card to display (if multiple cards exist)
- Sync data between iPhone and Watch
- Work offline once data is synced
- Provide quick access via watch face complication
- Optimize for battery life and performance

## Technical Requirements

### 1. Project Setup
- [ ] Add watchOS target to Xcode project
- [ ] Configure Watch App bundle identifier
- [ ] Set up WatchKit App and WatchKit Extension
- [ ] Configure Watch Connectivity for iPhone ↔ Watch communication
- [ ] Set up App Groups for shared data (optional, if needed)

### 2. Watch App Architecture

#### App Structure
- **Watch App**: Main watchOS app entry point
- **Watch Extension**: Contains UI and logic
- **Watch Connectivity**: Handles iPhone ↔ Watch data transfer

#### Navigation Structure
- **Main View**: QR code display (full screen)
- **Card Selection View**: List of available cards (if multiple)
- **Settings View**: Watch-specific settings (optional)

### 3. Data Synchronization

#### WatchConnectivity Framework
- Use `WCSession` for bidirectional communication
- Implement `WCSessionDelegate` in both iPhone and Watch apps
- Handle session activation and state changes

#### Data Transfer Strategy
- **Option A**: Send full card data (JSON) via `transferUserInfo`
- **Option B**: Send card ID, generate QR code on Watch
- **Recommendation**: Send minimal data (card ID + QR URL), generate QR on Watch

#### Data Structure
```swift
struct WatchCardData: Codable {
    let cardId: String
    let serverCardId: String?
    let firstName: String
    let lastName: String
    let companyName: String?
    let qrCodeUrl: String  // Public profile URL
    let lastUpdated: Date
}
```

#### Sync Triggers
- On Watch app launch
- When user changes card selection on iPhone
- Manual refresh via Watch app
- When Watch app becomes active

### 4. QR Code Generation on Watch

#### Implementation
- Port `QRCodeGenerator` logic to WatchKit
- Generate QR code URL: `https://sharemycard.app/card.php?id={serverCardId}&src=qr-watch`
- Render using `WKInterfaceImage` or SwiftUI `Image`
- Consider Watch screen size (smaller QR codes)

#### QR Code Size Considerations
- Watch screen: ~38mm (272x340) or ~42mm (312x390)
- QR code must be scannable at ~200-250px
- Test readability on physical devices
- May need to adjust error correction level

### 5. Watch App Views

#### Main QR Code View
```swift
struct QRCodeWatchView: View {
    @StateObject private var watchManager = WatchConnectivityManager.shared
    @State private var selectedCard: WatchCardData?
    
    var body: some View {
        if let card = selectedCard {
            VStack {
                QRCodeView(url: card.qrCodeUrl)
                    .frame(width: 200, height: 200)
                Text("\(card.firstName) \(card.lastName)")
                    .font(.caption)
            }
        } else {
            Text("No card selected")
                .font(.caption)
        }
    }
}
```

#### Card Selection View
```swift
struct CardSelectionWatchView: View {
    @StateObject private var watchManager = WatchConnectivityManager.shared
    
    var body: some View {
        List(watchManager.availableCards) { card in
            Button(action: {
                watchManager.selectCard(card)
            }) {
                VStack(alignment: .leading) {
                    Text("\(card.firstName) \(card.lastName)")
                    if let company = card.companyName {
                        Text(company)
                            .font(.caption2)
                    }
                }
            }
        }
        .navigationTitle("Select Card")
    }
}
```

### 6. Watch Connectivity Manager

#### Implementation
```swift
class WatchConnectivityManager: NSObject, ObservableObject, WCSessionDelegate {
    static let shared = WatchConnectivityManager()
    
    @Published var availableCards: [WatchCardData] = []
    @Published var selectedCard: WatchCardData?
    @Published var isConnected: Bool = false
    
    private let session = WCSession.default
    
    override init() {
        super.init()
        if WCSession.isSupported() {
            session.delegate = self
            session.activate()
        }
    }
    
    func requestCardsFromPhone() {
        // Send message to iPhone requesting card data
        session.sendMessage(["request": "cards"], replyHandler: { response in
            // Handle response with card data
        })
    }
    
    // WCSessionDelegate methods
    func session(_ session: WCSession, activationDidCompleteWith activationState: WCSessionActivationState, error: Error?) {
        // Handle session activation
    }
    
    func session(_ session: WCSession, didReceiveMessage message: [String : Any]) {
        // Handle messages from iPhone
    }
}
```

### 7. iPhone App Integration

#### Watch Connectivity on iPhone
- Implement `WCSessionDelegate` in main app
- Send card data when Watch requests it
- Push updates when user changes card selection
- Handle Watch app activation

#### Code Changes Needed
- `ShareMyCardApp.swift`: Initialize Watch Connectivity
- `BusinessCardDisplayView.swift`: Add "Send to Watch" option
- `WatchConnectivityManager.swift`: Handle iPhone-side connectivity

### 8. Watch Face Complication

#### Complication Family Support
- **Modular Small**: Small QR code icon/thumbnail
- **Modular Large**: QR code + card name
- **Circular Small**: Circular QR code preview
- **Utility Small**: Small QR code

#### Implementation
- Create `ComplicationController` class
- Implement `CLKComplicationDataSource` protocol
- Provide timeline entries for complication updates
- Cache QR code image for performance

#### Complication Data
```swift
struct ComplicationData {
    let qrCodeImage: UIImage
    let cardName: String
    let lastUpdated: Date
}
```

### 9. Storage Management

#### Local Storage on Watch
- Use `UserDefaults` for selected card ID
- Cache QR code image temporarily
- Clear cache when switching cards
- Limit storage to active card only

#### Storage Strategy
- Store only currently selected card
- Don't store all cards (Watch has limited storage)
- Request card data from iPhone when needed
- Clear old data when new card is selected

### 10. Performance Optimization

#### Battery Life
- Minimize Watch app activity when not in use
- Cache QR code image (don't regenerate constantly)
- Use efficient image rendering
- Limit background updates

#### Memory Management
- Keep memory footprint small
- Release unused resources promptly
- Optimize image sizes for Watch
- Avoid heavy computations

#### Network Usage
- QR code URL is static (no network needed to display)
- Only sync card data when necessary
- Use efficient data transfer methods

### 11. User Experience Flow

#### Initial Setup
1. User opens Watch app on Watch
2. Watch requests card data from iPhone
3. iPhone sends available cards
4. Watch displays cards (or single card if only one)
5. User selects card (or automatically uses only card)
6. Watch displays QR code

#### Daily Usage
1. User raises wrist and opens Watch app
2. QR code displays immediately (cached)
3. User shows QR code to be scanned
4. No iPhone needed

#### Card Changes
1. User changes card selection on iPhone
2. iPhone sends update to Watch
3. Watch updates displayed QR code
4. User sees new QR code on next Watch app open

### 12. Error Handling

#### Edge Cases
- **Watch not connected to iPhone**: Show cached card or "Connect to iPhone" message
- **No cards available**: Show "No cards" message with sync button
- **Card deleted**: Show "Card unavailable" and request update
- **Watch app crashes**: Graceful recovery on next launch
- **Data sync fails**: Show retry option

### 13. Offline Support

#### Requirements
- QR code must work without iPhone connection
- Store QR code URL (static, doesn't need network)
- Cache QR code image locally
- Display cached version if iPhone unavailable

#### Limitations
- Cannot change card selection without iPhone
- Cannot sync new cards without iPhone
- QR code still works (static URL)

## Implementation Steps

### Phase 1: Foundation (Week 1)
1. Add watchOS target to project
2. Create basic Watch app structure
3. Implement Watch Connectivity framework
4. Create simple QR code display view

### Phase 2: iPhone Integration (Week 1-2)
1. Implement Watch Connectivity on iPhone side
2. Create data transfer mechanism
3. Add "Send to Watch" option in main app
4. Test bidirectional communication

### Phase 3: Card Selection (Week 2)
1. Implement card list view on Watch
2. Add card selection logic
3. Handle single vs. multiple cards
4. Store selection in Watch storage

### Phase 4: QR Code Generation (Week 2-3)
1. Port QR code generator to Watch
2. Optimize for Watch screen size
3. Test QR code readability
4. Implement caching

### Phase 5: Complication (Week 3)
1. Create complication controller
2. Implement complication data source
3. Add complication templates
4. Test on watch faces

### Phase 6: Polish & Testing (Week 3-4)
1. Add error handling
2. Optimize performance and battery
3. Test on physical Watch devices
4. Handle edge cases

## Files to Create/Modify

### New Files
- `WatchApp/WatchApp.swift` - Watch app entry point
- `WatchApp/WatchContentView.swift` - Main Watch view
- `WatchApp/QRCodeWatchView.swift` - QR code display view
- `WatchApp/CardSelectionWatchView.swift` - Card selection view
- `WatchApp/WatchConnectivityManager.swift` - Connectivity manager
- `WatchApp/ComplicationController.swift` - Complication controller
- `QRCard/WatchConnectivityManager.swift` - iPhone-side connectivity (or shared)

### Modified Files
- `QRCard/ShareMyCardApp.swift` - Initialize Watch Connectivity
- `QRCard/BusinessCardDisplayView.swift` - Add "Send to Watch" button
- `QRCard.xcodeproj/project.pbxproj` - Add watchOS target

## Testing Checklist

- [ ] Watch app displays QR code correctly
- [ ] QR code is scannable from Watch screen
- [ ] Data syncs from iPhone to Watch
- [ ] Card selection works on Watch
- [ ] Works with single card (no selection needed)
- [ ] Works with multiple cards (selection required)
- [ ] Works offline (cached QR code)
- [ ] Complication displays correctly
- [ ] Battery life is acceptable
- [ ] Performance is smooth (no lag)
- [ ] Error states handle gracefully
- [ ] Works on both Watch sizes (38mm, 42mm)

## Watch Size Considerations

### 38mm Watch (Series 1-3)
- Screen: 272x340 pixels
- QR code size: ~200x200px recommended
- Text: Small font sizes

### 42mm Watch (Series 1-3)
- Screen: 312x390 pixels
- QR code size: ~230x230px recommended
- Text: Slightly larger fonts

### 40mm Watch (Series 4+)
- Screen: 324x394 pixels
- QR code size: ~240x240px recommended

### 44mm Watch (Series 4+)
- Screen: 368x448 pixels
- QR code size: ~270x270px recommended

## Future Enhancements

- **Multiple Complications**: Support different cards in different complications
- **Haptic Feedback**: Vibrate when QR code is displayed
- **Quick Actions**: Swipe gestures to switch cards
- **Digital Crown Navigation**: Use crown to navigate between cards
- **Siri Integration**: "Show my QR code" voice command
- **Watch Face Widget**: Dedicated watch face with QR code
- **Auto-rotate**: Show different cards at different times

## Resources

- [watchOS Documentation](https://developer.apple.com/documentation/watchkit)
- [WatchConnectivity Guide](https://developer.apple.com/documentation/watchconnectivity)
- [Complications Guide](https://developer.apple.com/documentation/clockkit)
- [Watch App Architecture](https://developer.apple.com/documentation/watchkit)

## Notes

- Watch apps have strict memory limits (~50MB)
- Watch apps should be lightweight and fast
- Battery life is critical - minimize background activity
- QR codes must be readable at Watch screen size
- Test on physical devices (simulator may not reflect real performance)
- Watch Connectivity requires both devices to be nearby
- Complications have size and update frequency limitations

