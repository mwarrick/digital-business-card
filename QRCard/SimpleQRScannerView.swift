//
//  SimpleQRScannerView.swift
//  ShareMyCard
//
//  Simplified QR Code scanner for adding contacts
//

import SwiftUI
import AVFoundation

struct SimpleQRScannerView: View {
    @ObservedObject var viewModel: ContactsViewModel
    @Environment(\.dismiss) private var dismiss
    
    @StateObject private var scanner = SimpleQRScanner()
    @State private var showingContactForm = false
    @State private var scannedContactData: ContactCreateData?
    @State private var showingError = false
    @State private var errorMessage = ""
    
    var body: some View {
        NavigationView {
            ZStack {
                // Camera view
                SimpleQRScannerPreviewView(scanner: scanner)
                    .ignoresSafeArea()
                
                // Overlay UI
                VStack {
                    // Top bar
                    HStack {
                        Button("Cancel") {
                            dismiss()
                        }
                        .foregroundColor(.white)
                        .padding()
                        .background(Color.black.opacity(0.6))
                        .cornerRadius(8)
                        
                        Spacer()
                        
                        Text("Scan QR Code")
                            .foregroundColor(.white)
                            .font(.headline)
                            .padding()
                            .background(Color.black.opacity(0.6))
                            .cornerRadius(8)
                        
                        Spacer()
                        
                        // Flashlight toggle
                        Button(action: {
                            scanner.toggleTorch()
                        }) {
                            Image(systemName: scanner.isTorchOn ? "flashlight.on.fill" : "flashlight.off.fill")
                                .foregroundColor(.white)
                                .padding()
                                .background(Color.black.opacity(0.6))
                                .cornerRadius(8)
                        }
                    }
                    .padding()
                    
                    Spacer()
                    
                    // Scanning area indicator
                    VStack {
                        Text("Position QR code within the frame")
                            .foregroundColor(.white)
                            .font(.subheadline)
                            .padding()
                            .background(Color.black.opacity(0.6))
                            .cornerRadius(8)
                        
                        // Scanning frame
                        RoundedRectangle(cornerRadius: 20)
                            .stroke(Color.white, lineWidth: 2)
                            .frame(width: 250, height: 250)
                            .overlay(
                                RoundedRectangle(cornerRadius: 20)
                                    .stroke(Color.green, lineWidth: 2)
                                    .opacity(scanner.isScanning ? 1 : 0)
                                    .animation(.easeInOut(duration: 1).repeatForever(), value: scanner.isScanning)
                            )
                    }
                    .padding()
                }
            }
            .onAppear {
                scanner.startScanning()
            }
            .onDisappear {
                scanner.stopScanning()
            }
            .onChange(of: scanner.scannedCode) { code in
                if let code = code {
                    handleScannedCode(code)
                }
            }
            .sheet(isPresented: $showingContactForm) {
                if let contactData = scannedContactData {
                    QRContactFormView(contactData: contactData, viewModel: viewModel)
                }
            }
            .alert("Error", isPresented: $showingError) {
                Button("OK") { }
            } message: {
                Text(errorMessage)
            }
        }
    }
    
    private func handleScannedCode(_ code: String) {
        print("📱 SimpleQRScannerView: Scanned code: \(code)")
        
        // Parse the QR code data
        if let contactData = parseQRCode(code) {
            scannedContactData = contactData
            showingContactForm = true
        } else {
            errorMessage = "Could not parse contact information from QR code"
            showingError = true
        }
    }
    
    private func parseQRCode(_ code: String) -> ContactCreateData? {
        // Check if it's a vCard
        if code.hasPrefix("BEGIN:VCARD") {
            return parseVCard(code)
        }
        
        // Check if it's a URL that might contain vCard data
        if code.hasPrefix("http://") || code.hasPrefix("https://") {
            return handleURLBasedQRCode(code)
        }
        
        // Try to parse as plain text contact info
        return parsePlainTextContact(code)
    }
    
    private func handleURLBasedQRCode(_ url: String) -> ContactCreateData? {
        // Handle URLs that redirect to vCard files (like ShareMyCard)
        return ContactCreateData(
            firstName: "QR",
            lastName: "Contact",
            email: nil,
            phone: nil,
            mobilePhone: nil,
            company: nil,
            jobTitle: nil,
            address: nil,
            city: nil,
            state: nil,
            zipCode: nil,
            country: nil,
            website: url,
            notes: "Scanned from QR code URL. This may redirect to a vCard file.",
            commentsFromLead: nil,
            birthdate: nil,
            photoUrl: nil,
            source: "qr_scan",
            sourceMetadata: "{\"qr_data\":\"\(url)\",\"type\":\"url\"}"
        )
    }
    
    private func parseVCard(_ vcardData: String) -> ContactCreateData? {
        var firstName = ""
        var lastName = ""
        var email: String?
        var phone: String?
        var mobilePhone: String?
        var company: String?
        var jobTitle: String?
        var streetAddress: String?
        var city: String?
        var state: String?
        var zipCode: String?
        var country: String?
        var website: String?
        var notes: String?
        
        let lines = vcardData.components(separatedBy: .newlines)
        
        for line in lines {
            let trimmedLine = line.trimmingCharacters(in: .whitespaces)
            
            if trimmedLine.hasPrefix("FN:") {
                let fullName = String(trimmedLine.dropFirst(3))
                let nameParts = fullName.components(separatedBy: " ")
                if nameParts.count >= 2 {
                    firstName = nameParts[0]
                    lastName = nameParts.dropFirst().joined(separator: " ")
                } else {
                    firstName = fullName
                }
            } else if trimmedLine.hasPrefix("N:") {
                let nameData = String(trimmedLine.dropFirst(2))
                let nameParts = nameData.components(separatedBy: ";")
                if nameParts.count >= 2 {
                    lastName = nameParts[0]
                    firstName = nameParts[1]
                }
            } else if trimmedLine.hasPrefix("EMAIL:") {
                email = String(trimmedLine.dropFirst(6))
            } else if trimmedLine.hasPrefix("TEL:") {
                let phoneData = String(trimmedLine.dropFirst(4))
                if trimmedLine.contains("TYPE=CELL") || trimmedLine.contains("TYPE=MOBILE") {
                    mobilePhone = phoneData
                } else {
                    phone = phoneData
                }
            } else if trimmedLine.hasPrefix("ORG:") {
                company = String(trimmedLine.dropFirst(4))
            } else if trimmedLine.hasPrefix("TITLE:") {
                jobTitle = String(trimmedLine.dropFirst(6))
            } else if trimmedLine.hasPrefix("ADR:") {
                let addressData = String(trimmedLine.dropFirst(4))
                let addressParts = addressData.components(separatedBy: ";")
                if addressParts.count >= 6 {
                    streetAddress = addressParts[2]
                    city = addressParts[3]
                    state = addressParts[4]
                    zipCode = addressParts[5]
                    country = addressParts[6]
                }
            } else if trimmedLine.hasPrefix("URL:") {
                website = String(trimmedLine.dropFirst(4))
            } else if trimmedLine.hasPrefix("NOTE:") {
                notes = String(trimmedLine.dropFirst(5))
            }
        }
        
        // Ensure we have at least a first name
        if firstName.isEmpty {
            firstName = "QR"
            lastName = "Contact"
        }
        
        return ContactCreateData(
            firstName: firstName,
            lastName: lastName,
            email: email,
            phone: phone,
            mobilePhone: mobilePhone,
            company: company,
            jobTitle: jobTitle,
            address: streetAddress,
            city: city,
            state: state,
            zipCode: zipCode,
            country: country,
            website: website,
            notes: notes,
            commentsFromLead: nil,
            birthdate: nil,
            photoUrl: nil,
            source: "qr_scan",
            sourceMetadata: "{\"qr_data\":\"\(vcardData.prefix(100))\"}"
        )
    }
    
    private func parsePlainTextContact(_ text: String) -> ContactCreateData? {
        // Simple parsing for plain text contact info
        let lines = text.components(separatedBy: .newlines)
        var firstName = "QR"
        var lastName = "Contact"
        var email: String?
        var phone: String?
        var company: String?
        var notes: String?
        
        for line in lines {
            let trimmedLine = line.trimmingCharacters(in: .whitespaces)
            
            if trimmedLine.contains("@") && trimmedLine.contains(".") {
                email = trimmedLine
            } else if trimmedLine.range(of: #"\d{3}[-.\s]?\d{3}[-.\s]?\d{4}"#, options: .regularExpression) != nil {
                phone = trimmedLine
            } else if !trimmedLine.isEmpty && !trimmedLine.contains("@") && !trimmedLine.range(of: #"\d{3}[-.\s]?\d{3}[-.\s]?\d{4}"#, options: .regularExpression) != nil {
                if company == nil {
                    company = trimmedLine
                } else {
                    notes = (notes ?? "") + trimmedLine + "\n"
                }
            }
        }
        
        return ContactCreateData(
            firstName: firstName,
            lastName: lastName,
            email: email,
            phone: phone,
            mobilePhone: nil,
            company: company,
            jobTitle: nil,
            address: nil,
            city: nil,
            state: nil,
            zipCode: nil,
            country: nil,
            website: nil,
            notes: notes?.trimmingCharacters(in: .whitespacesAndNewlines),
            commentsFromLead: nil,
            birthdate: nil,
            photoUrl: nil,
            source: "qr_scan",
            sourceMetadata: "{\"qr_data\":\"\(text.prefix(100))\"}"
        )
    }
}

// MARK: - Simple QR Scanner Preview View

struct SimpleQRScannerPreviewView: UIViewRepresentable {
    let scanner: SimpleQRScanner
    
    func makeUIView(context: Context) -> UIView {
        let view = UIView()
        scanner.setupPreviewLayer(in: view)
        return view
    }
    
    func updateUIView(_ uiView: UIView, context: Context) {
        // Update if needed
    }
}

// MARK: - Simple QR Scanner Class

class SimpleQRScanner: NSObject, ObservableObject {
    @Published var scannedCode: String?
    @Published var isScanning = false
    @Published var isTorchOn = false
    
    private var captureSession: AVCaptureSession?
    private var previewLayer: AVCaptureVideoPreviewLayer?
    private var videoDevice: AVCaptureDevice?
    
    override init() {
        super.init()
        setupCaptureSession()
    }
    
    private func setupCaptureSession() {
        captureSession = AVCaptureSession()
        
        guard let captureSession = captureSession else { return }
        
        // Configure session
        if captureSession.canSetSessionPreset(.high) {
            captureSession.sessionPreset = .high
        }
        
        // Add video input
        guard let videoDevice = AVCaptureDevice.default(for: .video) else { return }
        self.videoDevice = videoDevice
        
        do {
            let videoInput = try AVCaptureDeviceInput(device: videoDevice)
            if captureSession.canAddInput(videoInput) {
                captureSession.addInput(videoInput)
            }
        } catch {
            print("Error setting up video input: \(error)")
            return
        }
        
        // Add metadata output
        let metadataOutput = AVCaptureMetadataOutput()
        if captureSession.canAddOutput(metadataOutput) {
            captureSession.addOutput(metadataOutput)
            
            metadataOutput.setMetadataObjectsDelegate(self, queue: DispatchQueue.main)
            metadataOutput.metadataObjectTypes = [.qr]
        }
    }
    
    func setupPreviewLayer(in view: UIView) {
        guard let captureSession = captureSession else { return }
        
        previewLayer = AVCaptureVideoPreviewLayer(session: captureSession)
        previewLayer?.videoGravity = .resizeAspectFill
        previewLayer?.frame = view.bounds
        
        if let previewLayer = previewLayer {
            view.layer.addSublayer(previewLayer)
        }
    }
    
    func startScanning() {
        guard let captureSession = captureSession else { return }
        
        DispatchQueue.global(qos: .userInitiated).async {
            if !captureSession.isRunning {
                captureSession.startRunning()
            }
        }
        
        isScanning = true
    }
    
    func stopScanning() {
        guard let captureSession = captureSession else { return }
        
        DispatchQueue.global(qos: .userInitiated).async {
            if captureSession.isRunning {
                captureSession.stopRunning()
            }
        }
        
        isScanning = false
    }
    
    func toggleTorch() {
        guard let videoDevice = videoDevice else { return }
        
        do {
            try videoDevice.lockForConfiguration()
            if videoDevice.hasTorch {
                if videoDevice.torchMode == .on {
                    videoDevice.torchMode = .off
                    isTorchOn = false
                } else {
                    try videoDevice.setTorchModeOn(level: 1.0)
                    isTorchOn = true
                }
            }
            videoDevice.unlockForConfiguration()
        } catch {
            print("Error toggling torch: \(error)")
        }
    }
}

// MARK: - AVCaptureMetadataOutputObjectsDelegate

extension SimpleQRScanner: AVCaptureMetadataOutputObjectsDelegate {
    func metadataOutput(_ output: AVCaptureMetadataOutput, didOutput metadataObjects: [AVMetadataObject], from connection: AVCaptureConnection) {
        guard let metadataObject = metadataObjects.first as? AVMetadataMachineReadableCodeObject,
              let code = metadataObject.stringValue else { return }
        
        scannedCode = code
        stopScanning()
    }
}

#Preview {
    SimpleQRScannerView(viewModel: ContactsViewModel())
}
