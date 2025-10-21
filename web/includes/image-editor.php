<!-- Image Editor Modal -->
<div id="imageEditorModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 10000; overflow: auto;">
    <div style="position: relative; max-width: 1200px; margin: 20px auto; padding: 20px;">
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 0 10px;">
            <h2 style="color: white; margin: 0;">Edit Image</h2>
            <button onclick="closeImageEditor()" style="background: #e74c3c; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px;">✕ Cancel</button>
        </div>
        
        <!-- Controls (moved above image) -->
        <div style="background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
            <!-- Aspect Ratio Selection -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 10px; color: #333;">Aspect Ratio:</label>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button onclick="setAspectRatio(1)" class="aspect-btn active" data-ratio="1">1:1 Square</button>
                    <button onclick="setAspectRatio(0.75)" class="aspect-btn" data-ratio="0.75">3:4 Portrait</button>
                    <button onclick="setAspectRatio(1.33)" class="aspect-btn" data-ratio="1.33">4:3 Landscape</button>
                    <button onclick="setAspectRatio(1.78)" class="aspect-btn" data-ratio="1.78">16:9 Wide</button>
                    <button onclick="setAspectRatio('free')" class="aspect-btn" data-ratio="free">Free</button>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <button onclick="rotateImage(-90)" style="flex: 1; padding: 12px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 500;">
                    ↺ Rotate Left
                </button>
                <button onclick="rotateImage(90)" style="flex: 1; padding: 12px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 500;">
                    ↻ Rotate Right
                </button>
                <button onclick="flipImage('horizontal')" style="flex: 1; padding: 12px; background: #9b59b6; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 500;">
                    ⇄ Flip H
                </button>
                <button onclick="flipImage('vertical')" style="flex: 1; padding: 12px; background: #9b59b6; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 500;">
                    ⇅ Flip V
                </button>
                <button onclick="resetImage()" style="flex: 1; padding: 12px; background: #95a5a6; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 500;">
                    ↻ Reset
                </button>
            </div>
            
            <!-- Zoom Control -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 10px; color: #333;">Zoom:</label>
                <input type="range" id="zoomSlider" min="0" max="100" value="0" style="width: 100%;" oninput="zoomImage(this.value)">
                <div style="display: flex; justify-content: space-between; font-size: 12px; color: #666;">
                    <span>0%</span>
                    <span>100%</span>
                </div>
            </div>
            
            <!-- Save Button -->
            <button onclick="saveEditedImage()" style="width: 100%; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600;">
                ✓ Save & Apply
            </button>
        </div>
        
        <!-- Image Container (moved below controls) -->
        <div style="background: #000; border-radius: 10px; overflow: hidden;">
            <div style="max-height: 70vh; display: flex; align-items: center; justify-content: center;">
                <img id="imageToEdit" style="max-width: 100%; max-height: 100%; display: block;">
            </div>
        </div>
    </div>
</div>

<style>
.aspect-btn {
    padding: 10px 15px;
    background: #ecf0f1;
    color: #2c3e50;
    border: 2px solid #bdc3c7;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
}

.aspect-btn:hover {
    background: #d5dbdb;
}

.aspect-btn.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: #667eea;
}
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

<script>
let cropper = null;
let currentImageField = null;
let currentImageFile = null;

function openImageEditor(file, fieldId) {
    currentImageFile = file;
    currentImageField = fieldId;
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const image = document.getElementById('imageToEdit');
        image.src = e.target.result;
        
        // Show modal
        document.getElementById('imageEditorModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        // Initialize cropper
        if (cropper) {
            cropper.destroy();
        }
        
        cropper = new Cropper(image, {
            aspectRatio: 1,
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 1,
            restore: false,
            guides: true,
            center: true,
            highlight: false,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: false,
            background: false,
            zoomable: true,
            scalable: true,
            rotatable: true
        });
    };
    reader.readAsDataURL(file);
}

function closeImageEditor() {
    document.getElementById('imageEditorModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
    currentImageField = null;
    currentImageFile = null;
}

function setAspectRatio(ratio) {
    // Update button states
    document.querySelectorAll('.aspect-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    if (cropper) {
        if (ratio === 'free') {
            cropper.setAspectRatio(NaN); // Free aspect ratio
        } else {
            cropper.setAspectRatio(ratio);
        }
    }
}

function rotateImage(degrees) {
    if (cropper) {
        cropper.rotate(degrees);
    }
}

function flipImage(direction) {
    if (cropper) {
        if (direction === 'horizontal') {
            cropper.scaleX(-cropper.getData().scaleX || -1);
        } else {
            cropper.scaleY(-cropper.getData().scaleY || -1);
        }
    }
}

function resetImage() {
    if (cropper) {
        cropper.reset();
        document.getElementById('zoomSlider').value = 0;
    }
}

function zoomImage(value) {
    if (cropper) {
        // Convert 0-100 to zoom ratio
        const zoomRatio = 1 + (value / 100);
        cropper.zoomTo(zoomRatio);
    }
}

function saveEditedImage() {
    if (!cropper) return;
    
    // Get cropped canvas
    const canvas = cropper.getCroppedCanvas({
        maxWidth: 2048,
        maxHeight: 2048,
        fillColor: '#fff',
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high'
    });
    
    // Convert to blob
    canvas.toBlob(function(blob) {
        // Create a new File object with the original name
        const fileName = currentImageFile.name;
        const editedFile = new File([blob], fileName, {
            type: 'image/jpeg',
            lastModified: Date.now()
        });
        
        // Update the file input
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(editedFile);
        document.getElementById(currentImageField).files = dataTransfer.files;
        
        // Show preview
        showImagePreview(currentImageField, canvas.toDataURL('image/jpeg', 0.9));
        
        // Close editor
        closeImageEditor();
    }, 'image/jpeg', 0.9);
}

function showImagePreview(fieldId, dataUrl) {
    // Find or create preview element
    const input = document.getElementById(fieldId);
    let preview = input.parentElement.querySelector('.image-preview');
    
    if (!preview) {
        preview = document.createElement('div');
        preview.className = 'image-preview';
        preview.style.cssText = 'margin-top: 10px; margin-bottom: 10px;';
        input.parentElement.insertBefore(preview, input.nextSibling);
    }
    
    // Determine styling based on field type
    let containerStyle = '';
    let imgStyle = '';
    
    if (fieldId === 'profile_photo') {
        // Profile photo - circular
        imgStyle = 'width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 3px solid #667eea;';
    } else if (fieldId === 'company_logo') {
        // Company logo - square container with padding
        containerStyle = 'width: 200px; height: 200px; background: #f8f9fa; border: 2px solid #667eea; border-radius: 8px; display: flex; align-items: center; justify-content: center; padding: 20px;';
        imgStyle = 'max-width: 100%; max-height: 100%; object-fit: contain;';
    } else if (fieldId === 'cover_graphic') {
        // Cover graphic - wide banner container with padding
        containerStyle = 'width: 100%; max-width: 500px; height: 150px; background: #f8f9fa; border: 2px solid #667eea; border-radius: 8px; display: flex; align-items: center; justify-content: center; padding: 10px;';
        imgStyle = 'max-width: 100%; max-height: 100%; object-fit: contain;';
    }
    
    if (containerStyle) {
        preview.innerHTML = `
            <div style="${containerStyle}">
                <img src="${dataUrl}" alt="Preview" style="${imgStyle}">
            </div>
            <p style="font-size: 12px; color: #27ae60; margin-top: 5px; font-weight: 600;">✓ Edited (ready to upload)</p>
        `;
    } else {
        preview.innerHTML = `
            <img src="${dataUrl}" alt="Preview" style="${imgStyle}">
            <p style="font-size: 12px; color: #27ae60; margin-top: 5px; font-weight: 600;">✓ Edited (ready to upload)</p>
        `;
    }
}

// Intercept file input changes to open editor
document.addEventListener('DOMContentLoaded', function() {
    const imageInputs = ['profile_photo', 'company_logo', 'cover_graphic'];
    
    imageInputs.forEach(inputId => {
        const input = document.getElementById(inputId);
        if (input) {
            input.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    
                    // Validate file type
                    if (!file.type.match('image.*')) {
                        alert('Please select an image file');
                        this.value = '';
                        return;
                    }
                    
                    // Validate file size (25MB)
                    if (file.size > 25 * 1024 * 1024) {
                        alert('File size must be less than 25MB');
                        this.value = '';
                        return;
                    }
                    
                    // Open editor
                    openImageEditor(file, inputId);
                }
            });
        }
    });
});

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('imageEditorModal').style.display === 'block') {
        closeImageEditor();
    }
});
</script>

