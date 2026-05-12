# Society Approval & Admin Assignment Flow — FIXED

## Issues Fixed

### 1. **Duplicate Email Error on Approval** ✅
**Problem:** When approving a registration, the backend always tried to INSERT a new admin user. If that email already existed in `users` (e.g., from a previous approval that was later deleted), it threw a duplicate key error.

**Solution:** `approveRegistrationLead()` now checks if a user with that email/phone already exists. If yes, it **reuses** that user by updating their role, society link, and password. If no, it creates a fresh user.

```php
// Check for existing user
$stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
$stmt->execute([$lead['contact_email'], $normalizedPhone]);
$existingUser = $stmt->fetch();

if ($existingUser) {
    // Reuse — update role, society, password
    $adminId = $existingUser['id'];
    $this->update('users', [
        'role'       => 'admin',
        'society_id' => $societyId,
        'password'   => password_hash($password, PASSWORD_DEFAULT),
        'status'     => 'active'
    ], 'id = :id', ['id' => $adminId]);
} else {
    // Create fresh admin user
    $adminId = $this->insert('users', [...]);
}
```

---

### 2. **Missing Transaction Wrapper** ✅
**Problem:** The `beginTransaction()` call was missing after the ALTER TABLE block was added, so the whole approval wasn't atomic.

**Solution:** Added `$this->beginTransaction()` right before the society INSERT, and `$this->commit()` / `$this->rollback()` at the end.

---

### 3. **Registrations Page Missing Full Functionality** ✅
**Problem:** The Registrations page only had "Mark Under Review", "Approve", and "Reject" buttons. It lacked:
- "Open in Add Society" (pre-fill form with registration data)
- Full modal view matching the Societies page

**Solution:**
- Added "Open in Add Society" button in `saViewReg()` modal — pre-fills the Add Society form with all registration data
- Improved modal layout to match `saViewSociety()` style
- Fixed ID comparison bug (was using strict `===` on mixed number/string types)

---

### 4. **Data Sync Between Tables** ✅
**Problem:** Updates from one page weren't reflecting in the other table.

**Solution:** Implemented bidirectional sync:

| Action | `society_registrations` | `societies` |
|---|---|---|
| **Approve registration** | Row **deleted** | New row **inserted** with `registration_id` reference |
| **Update registration status** | Status updated | If linked society exists → status synced |
| **Update society** | If `registration_id` set → matching fields synced back | Updated |
| **Suspend society** | If `registration_id` set → status → `rejected` | Status → `suspended` |
| **Delete society** | No action (already deleted on approval) | Row deleted |

---

## Complete Approval Flow

### From Registrations Page

1. User submits registration from landing page → stored in `society_registrations` with status `new`
2. Super admin marks as `under_review` → status updated
3. Super admin clicks **"Approve & Activate"**:
   - Backend ensures required columns exist in `societies` table (ALTER TABLE IF NOT EXISTS)
   - Begins transaction
   - Creates society row with all fields + `registration_id` reference
   - Checks if admin user with that email/phone exists:
     - **If yes** → reuses that user, updates role/society/password
     - **If no** → creates fresh admin user
   - Links `admin_id` to society
   - **Deletes** registration row from `society_registrations`
   - Commits transaction
   - Returns society code + admin credentials
4. Frontend shows success modal with:
   - Society name + code
   - Admin login phone
   - Temporary password
5. Registration disappears from Registrations page
6. Society appears in All Societies page with status `approved` and admin assigned

### From All Societies Page

- **Approve** → status → `approved`
- **Suspend** → status → `suspended` (syncs to registration if linked)
- **Create Admin** → creates admin user, links to society
- **Edit** → updates society fields (syncs to registration if linked)
- **Delete** → removes society

---

## API Endpoints

### Registrations
- `GET /api/superadmin/registrations` — list all
- `POST /api/superadmin/registrations` — create (public, from landing page)
- `PUT /api/superadmin/registrations/:id` — update status
- `POST /api/superadmin/registrations/:id/approve` — approve & activate

### Societies
- `GET /api/superadmin/societies` — list all
- `GET /api/superadmin/societies/:id` — get one
- `POST /api/superadmin/societies` — create directly
- `PUT /api/superadmin/societies/:id` — update fields
- `PUT /api/superadmin/societies/:id/approve` — approve
- `PUT /api/superadmin/societies/:id/suspend` — suspend
- `DELETE /api/superadmin/societies/:id` — delete
- `POST /api/superadmin/societies/:id/admin` — create admin for society

---

## Database Schema

### `society_registrations` (temporary holding table)
```sql
CREATE TABLE `society_registrations` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `society_name` varchar(150) NOT NULL,
  `address` text,
  `city` varchar(100) NOT NULL,
  `state` varchar(100),
  `pincode` varchar(10),
  `towers` int(11) DEFAULT 1,
  `total_flats` int(11) DEFAULT 0,
  `contact_name` varchar(100) NOT NULL,
  `contact_email` varchar(100) NOT NULL,
  `contact_phone` varchar(20) NOT NULL,
  `gst` varchar(20),
  `pan` varchar(20),
  `message` text,
  `status` enum('pending','new','under_review','rejected') DEFAULT 'new',
  `reviewed_by` int(11),
  `reviewed_at` timestamp NULL,
  `rejection_reason` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### `societies` (main table)
```sql
ALTER TABLE `societies`
  ADD COLUMN `code` varchar(20) DEFAULT NULL,
  ADD COLUMN `towers` int(11) DEFAULT 1,
  ADD COLUMN `total_flats` int(11) DEFAULT 0,
  ADD COLUMN `admin_id` int(11) DEFAULT NULL,
  ADD COLUMN `gst` varchar(20) DEFAULT NULL,
  ADD COLUMN `pan` varchar(20) DEFAULT NULL,
  ADD COLUMN `registration_id` int(11) DEFAULT NULL COMMENT 'Source registration lead ID';
```

---

## Testing Checklist

- [ ] Submit registration from landing page → appears in Registrations page with status `new`
- [ ] Mark as "Under Review" → status updates
- [ ] Approve registration → society created, admin assigned, registration deleted
- [ ] Success modal shows society code + admin credentials
- [ ] Society appears in All Societies page with correct status
- [ ] Try approving same email twice (after delete) → should reuse user, no duplicate error
- [ ] Edit society from All Societies → changes sync to registration if linked
- [ ] Suspend society → status syncs to registration
- [ ] "Open in Add Society" from registration modal → form pre-fills correctly
- [ ] Create admin from All Societies page → admin assigned, status updates

---

## Files Modified

1. `mygate-backend-FULL/app/modules/admin/SuperAdminController.php`
   - Fixed `approveRegistrationLead()` — reuse existing users, proper transaction
   - Added `updateSociety()` — sync changes back to registrations
   - Updated `updateRegistration()`, `approveSociety()`, `suspendSociety()` — bidirectional sync

2. `mygate-backend-FULL/app/routes/api.php`
   - Added `PUT /api/superadmin/societies/:id` route

3. `MyBellGate-Saas - Integrated/public/static/app.js`
   - Fixed `renderSARegistrations()` — removed "approved" tab (registrations are deleted on approval)
   - Fixed `saViewReg()` — proper ID comparison, added "Open in Add Society" button
   - Updated `saApproveRegistrationLead()` — remove from local array after approval

4. `mygate-backend-FULL/migrations/002_society_registrations_and_societies_update.sql`
   - Added `gst`, `pan`, `registration_id` columns to `societies`
   - Created `society_registrations` table

5. `mygate-backend-FULL/run_migration.php`
   - One-time migration runner script (delete after running)

---

## Next Steps

1. Deploy updated `SuperAdminController.php` and `api.php` to server
2. Deploy updated `app.js` to frontend
3. Run migration: `https://yourdomain.com/run_migration.php?secret=migrate_002_run`
4. Verify all columns show ✅ in response
5. Delete `run_migration.php` from server
6. Test full approval flow end-to-end
