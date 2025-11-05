# iOS Home Screen Widget - QR Code Display

## Overview
Create an iOS Widget Extension that displays a user's business card QR code on their iPhone home screen, allowing quick access to share their card without opening the app.

## Goals
- Display business card QR code as a home screen widget
- Allow users to select which card to display (if multiple cards exist)
- Support multiple widget sizes (Small, Medium, Large)
- Automatically update when card changes
- Efficient data sharing between main app and widget

## Technical Requirements

### 1. Project Setup
- [ ] Add Widget Extension target to Xcode project
- [ ] Configure App Groups capability for both main app and widget extension
- [ ] Set up shared container identifier (e.g., `group.net.warrick.ShareMyCard`)
- [ ] Configure widget bundle identifier

### 2. Widget Architecture

#### Widget Types
- **Small Widget (2x2)**: QR code only, minimal text
- **Medium Widget (4x2)**: QR code + card name/company
- **Large Widget (4x4)**: QR code + full card details (name, company, title)

#### Timeline Provider
- Implement `TimelineProvider` protocol
- Provide timeline entries for widget updates
- Handle refresh intervals (iOS controls when widgets refresh)

### 3. Data Sharing Strategy

#### App Groups Configuration
- Use shared UserDefaults container for widget data
- Store selected card ID and metadata
- Share QR code URL or generate in widget

#### Data Structure
```swift
struct WidgetCardData: Codable {
    let cardId: String
    let serverCardId: String?
    let firstName: String
    let lastName: String
    let companyName: String?
    let qrCodeUrl: String  // Public profile URL
    let lastUpdated: Date
}
```

#### Shared Storage Location
- File: `SharedUserDefaults` with App Group container
- Key: `selectedWidgetCard`
- Update when user changes card selection in main app

### 4. QR Code Generation

#### Options
- **Option A**: Generate QR code in widget (reuse `QRCodeGenerator`)
- **Option B**: Generate QR code in main app, save as image, share via App Groups
- **Recommendation**: Generate in widget for real-time accuracy

#### Implementation
- Reuse existing `QRCodeGenerator.swift` logic
- Generate QR code URL: `https://sharemycard.app/card.php?id={serverCardId}&src=qr-app`
- Render as `Image` in SwiftUI widget view

### 5. Widget Configuration

#### Intent Configuration
- Use `IntentConfiguration` for user-selectable cards
- Create `SelectCardIntent` to allow users to choose which card
- Support dynamic options (load cards from shared storage)

#### User Selection Flow
1. User long-presses widget → "Edit Widget"
2. Widget shows card picker (from Intent)
3. User selects card
4. Widget updates to show selected card's QR code

### 6. Widget Views

#### Small Widget View
```swift
struct SmallWidgetView: View {
    let cardData: WidgetCardData
    
    var body: some View {
        VStack {
            QRCodeView(url: cardData.qrCodeUrl)
            Text(cardData.firstName)
                .font(.caption)
        }
    }
}
```

#### Medium Widget View
```swift
struct MediumWidgetView: View {
    let cardData: WidgetCardData
    
    var body: some View {
        HStack {
            QRCodeView(url: cardData.qrCodeUrl)
            VStack(alignment: .leading) {
                Text("\(cardData.firstName) \(cardData.lastName)")
                if let company = cardData.companyName {
                    Text(company)
                }
            }
        }
    }
}
```

#### Large Widget View
```swift
struct LargeWidgetView: View {
    let cardData: WidgetCardData
    
    var body: some View {
        VStack {
            QRCodeView(url: cardData.qrCodeUrl)
            Text("\(cardData.firstName) \(cardData.lastName)")
            if let company = cardData.companyName {
                Text(company)
            }
            Text("Tap to scan QR code")
                .font(.caption)
                .foregroundColor(.secondary)
        }
    }
}
```

### 7. Widget Update Strategy

#### Timeline Entries
- **Immediate Entry**: Show current card data
- **Future Entries**: Refresh every 15 minutes (iOS controls actual refresh)
- **Error Handling**: Show placeholder if card data unavailable

#### Refresh Triggers
- When user changes card selection in main app
- When widget is first added to home screen
- Periodic refresh (iOS system controlled)
- On app launch (if user changes card)

### 8. Integration with Main App

#### Card Selection Mechanism
- Add "Add to Home Screen" option in card details view
- Settings page to configure widget card
- Store selection in shared UserDefaults

#### Code Changes Needed
- `BusinessCardDisplayView.swift`: Add "Add to Widget" button
- `SettingsTabView.swift`: Add widget configuration section
- Create `WidgetDataManager.swift`: Handles shared storage

### 9. Error Handling

#### Edge Cases
- No cards available: Show "No cards" placeholder
- Card deleted: Show "Card unavailable" message
- Network issues: QR code still works (static URL)
- Multiple cards: Default to first active card, allow selection

### 10. Performance Considerations

#### Optimization
- Cache QR code image generation
- Minimize widget timeline entries
- Efficient data serialization
- Lazy loading of card data

#### Size Constraints
- Small widget: ~150KB
- Medium widget: ~200KB
- Large widget: ~300KB

## Implementation Steps

### Phase 1: Foundation (Week 1)
1. Add Widget Extension target
2. Configure App Groups
3. Create basic widget structure
4. Implement simple QR code display (hardcoded card)

### Phase 2: Data Sharing (Week 1-2)
1. Create `WidgetDataManager` for shared storage
2. Implement data serialization
3. Update main app to write to shared storage
4. Update widget to read from shared storage

### Phase 3: Intent Configuration (Week 2)
1. Create `SelectCardIntent`
2. Implement dynamic card options
3. Add card selection UI in widget configuration
4. Handle intent updates

### Phase 4: Multiple Sizes (Week 2-3)
1. Implement Small widget view
2. Implement Medium widget view
3. Implement Large widget view
4. Test all sizes on different devices

### Phase 5: Polish & Testing (Week 3)
1. Add error states and placeholders
2. Optimize performance
3. Test on physical devices
4. Handle edge cases (no cards, deleted cards, etc.)

## Files to Create/Modify

### New Files
- `QRCardWidget/QRCardWidget.swift` - Main widget entry point
- `QRCardWidget/QRCardWidgetBundle.swift` - Widget bundle
- `QRCardWidget/QRCardWidgetProvider.swift` - Timeline provider
- `QRCardWidget/QRCardWidgetViews.swift` - Widget view components
- `QRCard/WidgetDataManager.swift` - Shared data manager
- `QRCard/WidgetConfigurationView.swift` - Settings UI for widget

### Modified Files
- `QRCard/BusinessCardDisplayView.swift` - Add "Add to Widget" button
- `QRCard/SettingsTabView.swift` - Add widget configuration section
- `QRCard.xcodeproj/project.pbxproj` - Add widget extension target

## Testing Checklist

- [ ] Widget displays QR code correctly
- [ ] QR code is scannable at widget size
- [ ] Card selection works via Intent
- [ ] Widget updates when card changes in main app
- [ ] All widget sizes work correctly
- [ ] Error states display properly
- [ ] Works with single card (no selection needed)
- [ ] Works with multiple cards (selection required)
- [ ] Widget refreshes periodically
- [ ] Performance is acceptable (no lag)

## Future Enhancements

- **Multiple Widgets**: Allow users to add multiple widgets for different cards
- **Complications**: Support for Lock Screen widgets (iOS 16+)
- **Live Activities**: Show QR code scanning activity (iOS 16+)
- **Widget Interactions**: Tap widget to open app with specific card
- **Customization**: Allow users to customize widget appearance (colors, style)

## Resources

- [WidgetKit Documentation](https://developer.apple.com/documentation/widgetkit)
- [App Groups Guide](https://developer.apple.com/documentation/xcode/configuring-app-groups)
- [Widget Timeline Provider](https://developer.apple.com/documentation/widgetkit/timelineprovider)
- [Widget Configuration](https://developer.apple.com/documentation/widgetkit/intentconfiguration)

## Notes

- Widgets have limited refresh frequency (iOS controls this)
- Widgets cannot perform network requests directly
- Widgets are read-only (cannot modify data)
- Widget updates are system-controlled (not real-time)
- Consider widget family size limitations when designing

