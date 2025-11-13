# Duplicate Contacts Cleanup Scripts

These scripts remove duplicate contacts that were created from the same lead (same `id_lead` value).

## Scripts

### 1. `cleanup-duplicate-contacts.php` (Recommended)
A PHP script that can be run via web browser or command line.

**Features:**
- Uses soft delete (sets `is_deleted = 1`) so data can be recovered
- Keeps the most recent contact for each `leadId`
- Provides detailed logging of what was deleted
- Requires admin authentication for web access
- Safe transaction handling

**Usage:**

**Via Web Browser:**
1. Navigate to: `https://sharemycard.app/admin/cleanup-duplicate-contacts.php`
2. Must be logged in as admin
3. View the JSON output showing what was cleaned up

**Via Command Line:**
```bash
cd /path/to/web/admin
php cleanup-duplicate-contacts.php
```

**Output:**
- Shows how many duplicate groups were found
- Lists which contacts were kept vs deleted
- Verifies no duplicates remain

### 2. `cleanup-duplicate-contacts.sql`
A SQL script for direct database execution.

**Warning:** Review the queries before running!

**Usage:**
1. Review Step 1 query to see what duplicates exist
2. Run Step 2 to mark duplicates as deleted
3. Run Step 3 to verify cleanup

**Via MySQL command line:**
```bash
mysql -u username -p database_name < cleanup-duplicate-contacts.sql
```

**Via phpMyAdmin:**
1. Select your database
2. Go to SQL tab
3. Copy and paste each step separately
4. Review results before proceeding

## How It Works

1. **Finds duplicates:** Groups contacts by `id_lead` where `id_lead` is not null/empty/0
2. **Keeps the best one:** For each group, keeps the contact with:
   - Most recent `updated_at` timestamp
   - If tied, most recent `created_at`
   - If still tied, highest `id` (most recent)
3. **Soft deletes others:** Marks remaining duplicates with `is_deleted = 1`

## Safety

- Uses **soft delete** - contacts are marked as deleted but not permanently removed
- Can be recovered by setting `is_deleted = 0` if needed
- Uses database transactions - rolls back on error
- Provides detailed logging of all actions

## Recovery

If you need to recover a deleted contact:

```sql
UPDATE contacts 
SET is_deleted = 0, updated_at = NOW() 
WHERE id = 'contact_id_here';
```

## Notes

- Only processes contacts where `id_lead` is not null/empty/0
- Only affects non-deleted contacts (`is_deleted = 0`)
- Does not affect manually created contacts (those without `id_lead`)

