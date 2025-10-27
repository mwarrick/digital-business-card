#!/usr/bin/env python3
import sys
import os

try:
    from PIL import Image
    import cv2
    import numpy as np
    from pyzbar import pyzbar
except ImportError:
    print("Required libraries not installed. Please install: pip install pillow opencv-python pyzbar")
    sys.exit(1)

def read_qr_code(image_path):
    """Read QR code from image file"""
    try:
        # Load image
        image = cv2.imread(image_path)
        if image is None:
            return None
        
        # Convert to grayscale
        gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
        
        # Find QR codes
        qr_codes = pyzbar.decode(gray)
        
        if qr_codes:
            # Return the first QR code data
            return qr_codes[0].data.decode('utf-8')
        else:
            return None
            
    except Exception as e:
        print(f"Error reading QR code: {e}")
        return None

if __name__ == "__main__":
    if len(sys.argv) != 2:
        print("Usage: python3 qr_reader.py <image_path>")
        sys.exit(1)
    
    image_path = sys.argv[1]
    
    if not os.path.exists(image_path):
        print("Image file not found")
        sys.exit(1)
    
    qr_data = read_qr_code(image_path)
    
    if qr_data:
        print(qr_data)
    else:
        print("No QR code found")
        sys.exit(1)
