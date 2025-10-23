# Add Image Removal Feature

## Overview

Add "Remove" buttons for profile photos, company logos, and cover graphics in the web interface. When clicked, the system will set the database field to NULL and delete the physical file from storage.

## Implementation Plan

### 1. Create Session-Based Delete Endpoint

**File**: `web/user/api/delete-image.php` (new file)

- Create session-authenticated endpoint for image removal
- Verify user owns the card before deletion
- Set `{media_type}_path` to NULL in database
- Delete physical file from `/storage/media/`
- Return success/error JSON response
- Handle three media types: `profile_photo`, `company_logo`, `cover_graphic`

**Key logic**:

```php
// Verify ownership
$card = $db->querySingle("SELECT {$mediaType}_path FROM business_cards WHERE id = ? AND user_id = ?", [$cardId, $userId]);

// Delete file
$filepath = __DIR__ . '/../../storage/media/' . $card["{$mediaType}_path"];
if (file_exists($filepath)) unlink($filepath);

// Update DB
$db->execute("UPDATE business_cards SET {$mediaType}_path = NULL, {$mediaType} = NULL, updated_at = NOW() WHERE id = ?", [$cardId]);
```

### 2. Update User Edit Page

**File**: `web/user/cards/edit.php`

Add "Remove" button for each image type in the Media section (lines 886-928):

**Profile Photo** (after line 892):

```php
<button type="button" onclick="removeImage('profile_photo', '<?php echo $cardId; ?>')" 
        style="padding: 8px 16px; background: #e74c3c; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 5px;">
    Remove Photo
</button>
```

**Company Logo** (after line 908):

```php
<button type="button" onclick="removeImage('company_logo', '<?php echo $cardId; ?>')" 
        style="padding: 8px 16px; background: #e74c3c; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 5px;">
    Remove Logo
</button>
```

**Cover Graphic** (after line 924):

```php
<button type="button" onclick="removeImage('cover_graphic', '<?php echo $cardId; ?>')" 
        style="padding: 8px 16px; background: #e74c3c; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 5px;">
    Remove Cover
</button>
```

Add JavaScript function in the `<script>` section (after line 939):

```javascript
function removeImage(mediaType, cardId) {
    if (!confirm('Are you sure you want to remove this image? This cannot be undone.')) {
        return;
    }
    
    fetch('/user/api/delete-image.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            card_id: cardId,
            media_type: mediaType
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Image removed successfully');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Failed to remove image');
    });
}
```

### 3. Update Admin Edit Page

**File**: `web/admin/cards/edit.php`

Apply identical changes as user edit page:

- Add "Remove" buttons at lines 868, 884, 900
- Add `removeImage()` JavaScript function in script section (after line 915)
- Update endpoint path to `/user/api/delete-image.php` (works with session auth)

### 4. Update Router (if needed)

**File**: `web/router.php`

Check if routing rule exists for `/user/api/delete-image.php`. If not using router, no changes needed.

## Testing Checklist

- Test removing profile photo in user interface
- Test removing company logo in user interface  
- Test removing cover graphic in user interface
- Test removing images in admin interface
- Verify files are deleted from `/storage/media/`
- Verify database fields are set to NULL
- Verify page reloads and shows no image
- Test with demo account (should work normally)
- Verify error handling for invalid card IDs
- Verify unauthorized users cannot remove images

## Files to Create

- `web/user/api/delete-image.php` (new session-based endpoint)

## Files to Modify

- `web/user/cards/edit.php` (add remove buttons + JavaScript)
- `web/admin/cards/edit.php` (add remove buttons + JavaScript)

## Notes

- No iOS changes required per user request
- Existing API delete endpoint at `/api/media/delete.php` uses JWT auth (iOS), new endpoint uses session auth (web)
- Images can be re-uploaded later if needed
- Demo user images should not be protected - they reset on each login anyway