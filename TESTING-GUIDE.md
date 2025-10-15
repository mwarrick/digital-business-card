# Testing Guide - Media Upload

## How to Add Test Images to iOS Simulator

There are **three easy ways** to add test images for testing the media upload functionality:

---

### ✅ Method 1: Generate Test Images (Easiest - Built-in)

1. Launch the app in iOS Simulator
2. On the home screen, tap **"Generate Test Images"** button
3. Grant photo library permission when prompted
4. You'll see: "✅ 3 test images added to Photos"
5. Go to create/edit a card and tap "Add Profile Photo" → "Photo Library"
6. You'll see 3 colorful test images:
   - 👤 **Blue** - Profile Photo
   - 🏢 **Green** - Company Logo
   - 🎨 **Purple** - Cover Graphic

**Pros:**
- No external files needed
- Instant - generates in-app
- Different colors for easy identification
- Already labeled for their purpose

---

### ✅ Method 2: Drag & Drop into Simulator

1. Launch the iOS Simulator
2. Find any image file on your Mac (JPEG, PNG, HEIC, etc.)
3. **Drag and drop** the file directly onto the simulator window
4. The image will automatically be saved to the Photos app
5. Access it via "Photo Library" in the app

**Pros:**
- Use your own images
- Super quick
- Works with any image format

**Example sources:**
- Desktop screenshots
- Downloaded stock photos
- Your own photos from Mac Photos app

---

### ✅ Method 3: Safari Download in Simulator

1. Open Safari in the iOS Simulator
2. Go to any image search (e.g., Unsplash, Pexels)
3. Long press on any image → "Save to Photos"
4. Access it via "Photo Library" in the app

**Pros:**
- Access to millions of free stock photos
- Great for realistic testing

**Recommended sites:**
- https://unsplash.com - Free high-quality photos
- https://pexels.com - Free stock photos
- https://picsum.photos - Random placeholder images

---

## What Each Image Type Is For

### Profile Photo
- **Recommended size:** 400x400 or larger (square)
- **Use case:** User headshot/avatar
- **Test image:** Blue with 👤 emoji

### Company Logo
- **Recommended size:** 400x400 or larger (square)
- **Use case:** Business/company branding
- **Test image:** Green with 🏢 emoji

### Cover Graphic
- **Recommended size:** 800x400 or larger (2:1 ratio)
- **Use case:** Banner/header image for card
- **Test image:** Purple with 🎨 emoji

---

## Testing Checklist

Use this checklist to test all media upload functionality:

### Profile Photo
- [ ] Upload from Photo Library
- [ ] Upload from Camera (device only)
- [ ] See "Uploading..." indicator
- [ ] See "Synced to server" checkmark
- [ ] Change image
- [ ] Delete image
- [ ] Create card and save
- [ ] Sync to server
- [ ] Login on another device/simulator - image downloads

### Company Logo
- [ ] Upload from Photo Library
- [ ] Upload from Camera (device only)
- [ ] See "Uploading..." indicator
- [ ] See "Synced to server" checkmark
- [ ] Change image
- [ ] Delete image
- [ ] Create card and save
- [ ] Sync to server
- [ ] Login on another device/simulator - image downloads

### Cover Graphic
- [ ] Upload from Photo Library
- [ ] Upload from Camera (device only)
- [ ] See "Uploading..." indicator
- [ ] See "Synced to server" checkmark
- [ ] Change image
- [ ] Delete image
- [ ] Create card and save
- [ ] Sync to server
- [ ] Login on another device/simulator - image downloads

---

## Troubleshooting

### "Generate Test Images" button not working?
- Make sure you granted photo library permission
- Check Console for error messages
- Try tapping it again

### Images not appearing in Photo Library?
- Close and reopen the Photos app in simulator
- Try Hardware → Restart in the simulator menu

### Upload failing?
- Check you're logged in (have valid JWT token)
- Check network connection
- Look for error messages in the app
- Check server logs

### Images not syncing?
- Make sure you saved the card after uploading
- Try manual "Sync with Server" button
- Check you're online

---

## Quick Test Script

1. **Generate test images**: Tap "Generate Test Images"
2. **Create new card**: Tap "Create Business Card"
3. **Add profile photo**: Blue test image
4. **Add company logo**: Green test image
5. **Add cover graphic**: Purple test image
6. **Watch uploads**: Should see 3 "Uploading..." → "Synced to server"
7. **Save card**: Tap "Save"
8. **Check sync**: Images should sync to server
9. **Delete app data**: Delete and reinstall app OR clear Core Data
10. **Login again**: Images should download from server

---

## Development Notes

### TestImageGenerator.swift
- Programmatically generates colored test images
- Creates images with emojis and labels
- Saves to photo library with permission
- Useful for automated testing

### Photo Library Permissions
- App will request "Add Photos Only" permission
- Required for "Generate Test Images" feature
- Not required for Photo Picker (selecting existing photos)

---

**Happy Testing! 🎉**

