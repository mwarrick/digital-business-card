import SwiftUI

/// Aspect ratio options for cropping
enum CropAspectRatio: String, CaseIterable {
    case square = "1:1"
    case portrait = "3:4"
    case landscape = "4:3"
    case wide = "16:9"
    case free = "Free"
    
    var ratio: CGFloat? {
        switch self {
        case .square: return 1.0
        case .portrait: return 3.0 / 4.0
        case .landscape: return 4.0 / 3.0
        case .wide: return 16.0 / 9.0
        case .free: return nil
        }
    }
}

/// Image cropping and editing view
struct ImageCropperView: View {
    @Environment(\.dismiss) var dismiss
    
    let originalImage: UIImage
    let onSave: (UIImage) -> Void
    
    @State private var scale: CGFloat = 1.0
    @State private var lastScale: CGFloat = 1.0
    @State private var offset: CGSize = .zero
    @State private var lastOffset: CGSize = .zero
    @State private var rotation: Angle = .zero
    @State private var selectedAspect: CropAspectRatio = .square
    
    // Image dimensions
    @State private var imageSize: CGSize = .zero
    @State private var viewSize: CGSize = .zero
    
    var body: some View {
        NavigationView {
            VStack(spacing: 0) {
                // Crop area
                GeometryReader { geometry in
                    ZStack {
                        Color.black
                        
                        // Image with transformations
                        Image(uiImage: originalImage)
                            .resizable()
                            .aspectRatio(contentMode: .fit)
                            .scaleEffect(scale)
                            .rotationEffect(rotation)
                            .offset(offset)
                            .frame(width: geometry.size.width, height: geometry.size.height)
                            .gesture(
                                MagnificationGesture()
                                    .onChanged { value in
                                        let delta = value / lastScale
                                        lastScale = value
                                        scale *= delta
                                        // Constrain scale
                                        scale = max(0.5, min(scale, 5.0))
                                    }
                                    .onEnded { _ in
                                        lastScale = 1.0
                                    }
                            )
                            .simultaneousGesture(
                                DragGesture()
                                    .onChanged { value in
                                        offset = CGSize(
                                            width: lastOffset.width + value.translation.width,
                                            height: lastOffset.height + value.translation.height
                                        )
                                    }
                                    .onEnded { _ in
                                        lastOffset = offset
                                    }
                            )
                        
                        // Crop overlay
                        CropOverlay(aspectRatio: selectedAspect.ratio)
                    }
                    .onAppear {
                        viewSize = geometry.size
                    }
                }
                
                // Controls
                VStack(spacing: 20) {
                    // Aspect ratio picker
                    ScrollView(.horizontal, showsIndicators: false) {
                        HStack(spacing: 15) {
                            ForEach(CropAspectRatio.allCases, id: \.self) { aspect in
                                Button(action: {
                                    selectedAspect = aspect
                                }) {
                                    VStack(spacing: 5) {
                                        AspectRatioIcon(aspect: aspect)
                                            .frame(width: 40, height: 40)
                                        Text(aspect.rawValue)
                                            .font(.caption2)
                                    }
                                    .foregroundColor(selectedAspect == aspect ? .blue : .gray)
                                    .padding(.horizontal, 8)
                                    .padding(.vertical, 8)
                                    .background(
                                        RoundedRectangle(cornerRadius: 8)
                                            .fill(selectedAspect == aspect ? Color.blue.opacity(0.2) : Color.clear)
                                    )
                                }
                            }
                        }
                        .padding(.horizontal)
                    }
                    
                    // Action buttons
                    HStack(spacing: 20) {
                        // Rotate button
                        Button(action: {
                            withAnimation(.spring(response: 0.3)) {
                                rotation += .degrees(90)
                            }
                        }) {
                            VStack {
                                Image(systemName: "rotate.right")
                                    .font(.title2)
                                Text("Rotate")
                                    .font(.caption)
                            }
                            .frame(maxWidth: .infinity)
                            .padding(.vertical, 12)
                            .background(Color.gray.opacity(0.2))
                            .cornerRadius(10)
                        }
                        
                        // Reset button
                        Button(action: {
                            withAnimation(.spring(response: 0.3)) {
                                scale = 1.0
                                lastScale = 1.0
                                offset = .zero
                                lastOffset = .zero
                                rotation = .zero
                            }
                        }) {
                            VStack {
                                Image(systemName: "arrow.counterclockwise")
                                    .font(.title2)
                                Text("Reset")
                                    .font(.caption)
                            }
                            .frame(maxWidth: .infinity)
                            .padding(.vertical, 12)
                            .background(Color.gray.opacity(0.2))
                            .cornerRadius(10)
                        }
                    }
                    .padding(.horizontal)
                }
                .padding(.vertical, 20)
                .background(Color(UIColor.systemBackground))
            }
            .navigationTitle("Edit Image")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarLeading) {
                    Button("Cancel") {
                        dismiss()
                    }
                }
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button("Done") {
                        cropAndSave()
                    }
                    .fontWeight(.semibold)
                }
            }
        }
    }
    
    private func cropAndSave() {
        guard let croppedImage = cropImage() else {
            dismiss()
            return
        }
        onSave(croppedImage)
        dismiss()
    }
    
    private func cropImage() -> UIImage? {
        // Calculate the crop rect based on current transformations
        let imageSize = originalImage.size
        let viewAspect = viewSize.width / viewSize.height
        let imageAspect = imageSize.width / imageSize.height
        
        // Determine the actual displayed size of the image
        var displayedImageSize: CGSize
        if imageAspect > viewAspect {
            // Image is wider than view
            displayedImageSize = CGSize(
                width: viewSize.width,
                height: viewSize.width / imageAspect
            )
        } else {
            // Image is taller than view
            displayedImageSize = CGSize(
                width: viewSize.height * imageAspect,
                height: viewSize.height
            )
        }
        
        // Apply scale
        displayedImageSize.width *= scale
        displayedImageSize.height *= scale
        
        // Calculate crop rect in image coordinates
        let cropWidth: CGFloat
        let cropHeight: CGFloat
        
        if let aspectRatio = selectedAspect.ratio {
            if viewAspect > aspectRatio {
                // Crop width is constrained by height
                cropHeight = viewSize.height
                cropWidth = cropHeight * aspectRatio
            } else {
                // Crop height is constrained by width
                cropWidth = viewSize.width
                cropHeight = cropWidth / aspectRatio
            }
        } else {
            cropWidth = viewSize.width
            cropHeight = viewSize.height
        }
        
        // Convert to image scale
        let scaleX = imageSize.width / displayedImageSize.width
        let scaleY = imageSize.height / displayedImageSize.height
        
        let centerX = imageSize.width / 2 - offset.width * scaleX
        let centerY = imageSize.height / 2 - offset.height * scaleY
        
        let cropRect = CGRect(
            x: centerX - (cropWidth * scaleX / 2),
            y: centerY - (cropHeight * scaleY / 2),
            width: cropWidth * scaleX,
            height: cropHeight * scaleY
        )
        
        // Perform the crop with rotation
        return cropAndRotateImage(originalImage, toRect: cropRect, rotation: rotation)
    }
    
    private func cropAndRotateImage(_ image: UIImage, toRect rect: CGRect, rotation: Angle) -> UIImage? {
        // First rotate
        let rotatedImage = rotateImage(image, by: rotation)
        
        // Then crop
        guard let cgImage = rotatedImage.cgImage,
              let croppedCGImage = cgImage.cropping(to: rect) else {
            return nil
        }
        
        return UIImage(cgImage: croppedCGImage, scale: image.scale, orientation: image.imageOrientation)
    }
    
    private func rotateImage(_ image: UIImage, by angle: Angle) -> UIImage {
        let radians = CGFloat(angle.radians)
        
        // Calculate new size after rotation
        let rotatedSize = CGRect(origin: .zero, size: image.size)
            .applying(CGAffineTransform(rotationAngle: radians))
            .integral.size
        
        // Create graphics context
        UIGraphicsBeginImageContextWithOptions(rotatedSize, false, image.scale)
        defer { UIGraphicsEndImageContext() }
        
        guard let context = UIGraphicsGetCurrentContext() else { return image }
        
        // Move origin to center
        context.translateBy(x: rotatedSize.width / 2, y: rotatedSize.height / 2)
        
        // Rotate
        context.rotate(by: radians)
        
        // Draw image
        image.draw(in: CGRect(
            x: -image.size.width / 2,
            y: -image.size.height / 2,
            width: image.size.width,
            height: image.size.height
        ))
        
        return UIGraphicsGetImageFromCurrentImageContext() ?? image
    }
}

/// Crop overlay showing the crop area
struct CropOverlay: View {
    let aspectRatio: CGFloat?
    
    var body: some View {
        GeometryReader { geometry in
            ZStack {
                // Dark overlay
                Color.black.opacity(0.5)
                
                // Clear crop area
                Rectangle()
                    .fill(Color.clear)
                    .frame(
                        width: cropWidth(for: geometry.size),
                        height: cropHeight(for: geometry.size)
                    )
                    .overlay(
                        Rectangle()
                            .stroke(Color.white, lineWidth: 2)
                    )
                    .overlay(
                        // Grid lines
                        GeometryReader { cropGeometry in
                            Path { path in
                                // Vertical lines
                                let thirdWidth = cropGeometry.size.width / 3
                                path.move(to: CGPoint(x: thirdWidth, y: 0))
                                path.addLine(to: CGPoint(x: thirdWidth, y: cropGeometry.size.height))
                                path.move(to: CGPoint(x: thirdWidth * 2, y: 0))
                                path.addLine(to: CGPoint(x: thirdWidth * 2, y: cropGeometry.size.height))
                                
                                // Horizontal lines
                                let thirdHeight = cropGeometry.size.height / 3
                                path.move(to: CGPoint(x: 0, y: thirdHeight))
                                path.addLine(to: CGPoint(x: cropGeometry.size.width, y: thirdHeight))
                                path.move(to: CGPoint(x: 0, y: thirdHeight * 2))
                                path.addLine(to: CGPoint(x: cropGeometry.size.width, y: thirdHeight * 2))
                            }
                            .stroke(Color.white.opacity(0.5), lineWidth: 1)
                        }
                    )
                    .blendMode(.destinationOut)
            }
            .compositingGroup()
        }
        .allowsHitTesting(false)
    }
    
    private func cropWidth(for size: CGSize) -> CGFloat {
        guard let aspectRatio = aspectRatio else {
            return size.width
        }
        
        let viewAspect = size.width / size.height
        if viewAspect > aspectRatio {
            // Width is constrained by height
            return size.height * aspectRatio
        } else {
            // Width fills the view
            return size.width
        }
    }
    
    private func cropHeight(for size: CGSize) -> CGFloat {
        guard let aspectRatio = aspectRatio else {
            return size.height
        }
        
        let viewAspect = size.width / size.height
        if viewAspect > aspectRatio {
            // Height fills the view
            return size.height
        } else {
            // Height is constrained by width
            return size.width / aspectRatio
        }
    }
}

/// Icon representing the aspect ratio
struct AspectRatioIcon: View {
    let aspect: CropAspectRatio
    
    var body: some View {
        ZStack {
            RoundedRectangle(cornerRadius: 4)
                .stroke(Color.primary, lineWidth: 2)
                .aspectRatio(aspect.ratio ?? 1.0, contentMode: .fit)
        }
    }
}

#Preview {
    ImageCropperView(
        originalImage: UIImage(systemName: "photo")!,
        onSave: { _ in }
    )
}

