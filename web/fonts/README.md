# Font Files for QR Name Tags

This directory should contain TrueType font files (.ttf) for use in name tag generation.

## Installing Handwriting Fonts

### Option 1: Download from Google Fonts (Recommended)

1. Visit Google Fonts: https://fonts.google.com/
2. Search for the font you want (e.g., "Dancing Script", "Caveat", "Kalam")
3. Click "Download family" button
4. Extract the ZIP file
5. Find the `.ttf` files (usually in a `static/` or `ttf/` subdirectory)
6. Copy the Regular weight `.ttf` files to this directory with these exact names:
   - `DancingScript-Regular.ttf` (for Dancing Script)
   - `Caveat-Regular.ttf` (for Caveat)
   - `Kalam-Regular.ttf` (for Kalam)

### Option 2: Use Command Line (if you have SSH access)

```bash
# Navigate to the fonts directory
cd /path/to/project/web/fonts

# Download Dancing Script
wget -O DancingScript-Regular.ttf "https://github.com/google/fonts/raw/main/ofl/dancingscript/DancingScript-Regular.ttf"

# Download Caveat
wget -O Caveat-Regular.ttf "https://github.com/google/fonts/raw/main/ofl/caveat/Caveat-Regular.ttf"

# Download Kalam
wget -O Kalam-Regular.ttf "https://github.com/google/fonts/raw/main/ofl/kalam/Kalam-Regular.ttf"
```

### Required Files

For full handwriting font support, place these files here:
- `DancingScript-Regular.ttf`
- `Caveat-Regular.ttf`  
- `Kalam-Regular.ttf`

The code will automatically use these fonts if they exist, otherwise it will fall back to system fonts.

