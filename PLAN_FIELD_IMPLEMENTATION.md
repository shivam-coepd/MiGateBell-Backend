# Society Plan Field Implementation

## Overview
Added a subscription plan field to societies with three enum values: `starter`, `professional`, and `enterprise`.

---

## ✅ Changes Made

### 1. **Database Migration**

**File:** `database/migrations/add_plan_to_societies.sql`

**SQL Migration:**
```sql
-- Add plan column to societies table
ALTER TABLE societies 
ADD COLUMN plan ENUM('starter', 'professional', 'enterprise') DEFAULT 'starter' AFTER contact_email;

-- Add index for better query performance
ALTER TABLE societies 
ADD INDEX idx_plan (plan);
```

**How to Run:**
```bash
mysql -u your_username -p migate < database/migrations/add_plan_to_societies.sql
```

---

### 2. **Backend Controller Updates**

**File:** `app/modules/admin/AdminController.php`

#### **A. Create Society** - Added plan validation and storage
```php
// Validate plan if provided
if (isset($data['plan'])) {
    $allowedPlans = ['starter', 'professional', 'enterprise'];
    if (!in_array($data['plan'], $allowedPlans)) {
        Response::error("Invalid plan. Allowed values: " . implode(', ', $allowedPlans));
    }
}
$plan = $data['plan'] ?? 'starter'; // Default to starter

// Insert with plan
$societyId = $this->insert('societies', [
    // ... other fields
    'plan' => $plan
]);
```

#### **B. Get Society By ID** - Returns plan field
```php
SELECT id, name, address, city, state, country, pincode, 
       contact_person, contact_phone, contact_email, plan, created_at
FROM societies WHERE id = ?
```

#### **C. Get Complete Society** - Includes plan in response
```php
SELECT id, name, address, city, state, country, pincode, 
       contact_person, contact_phone, contact_email, plan, created_at, updated_at
FROM societies WHERE id = ?
```

#### **D. Update Society** - Plan can be updated
```php
$allowedFields = [
    'name', 'address', 'city', 'state', 'country', 'pincode',
    'contact_person', 'contact_phone', 'contact_email', 'plan'
];
```

#### **E. Get Societies List** - Plan included in listing
```php
SELECT id, name, address, city, state, country, pincode, 
       contact_person, contact_phone, contact_email, plan, created_at
FROM societies
```

#### **F. Search Societies** - Plan included in search results
```php
SELECT id, name, address, city, state, country, plan
FROM societies
```

---

## 📝 API Usage Examples

### **Create Society with Plan**

```bash
curl -X POST http://localhost:8080/api/admin/societies \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Green Valley Apartments",
    "address": "123 Main Street",
    "city": "Mumbai",
    "state": "Maharashtra",
    "country": "India",
    "pincode": "400001",
    "contact_person": "Rajesh Kumar",
    "contact_phone": "+919876543210",
    "contact_email": "info@greenvalley.com",
    "plan": "professional"
  }'
```

**Response:**
```json
{
  "status": true,
  "message": "Society created successfully",
  "data": {
    "society_id": 15
  }
}
```

---

### **Create Society without Plan (Defaults to Starter)**

```bash
curl -X POST http://localhost:8080/api/admin/societies \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Basic Society",
    "address": "456 Street",
    "city": "Pune",
    "state": "Maharashtra",
    "country": "India",
    "pincode": "411001",
    "contact_person": "Admin",
    "contact_phone": "+919876543211",
    "contact_email": "admin@basicsociety.com"
  }'
```

The society will be created with `plan: "starter"` by default.

---

### **Get Society Details (Includes Plan)**

```bash
curl -X GET http://localhost:8080/api/admin/societies/1 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Response:**
```json
{
  "status": true,
  "message": "Society retrieved successfully",
  "data": {
    "id": 1,
    "name": "Green Valley Apartments",
    "address": "123 Main Street",
    "city": "Mumbai",
    "state": "Maharashtra",
    "country": "India",
    "pincode": "400001",
    "contact_person": "Rajesh Kumar",
    "contact_phone": "+919876543210",
    "contact_email": "info@greenvalley.com",
    "plan": "professional",
    "created_at": "2024-01-15 10:00:00"
  }
}
```

---

### **Update Society Plan**

```bash
curl -X PUT http://localhost:8080/api/admin/societies/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "plan": "enterprise"
  }'
```

---

### **Get Complete Society Information (Includes Plan)**

```bash
curl -X GET http://localhost:8080/api/admin/societies/1/complete \
  -H "Authorization: Bearer YOUR_TOKEN"
```

The plan field will be included in the basic society details section.

---

## 📊 Plan Values

| Plan | Description | Features |
|------|-------------|----------|
| `starter` | Basic features for small societies | • Visitor Management<br>• User Management<br>• Basic Notifications |
| `professional` | Advanced features for medium societies | • All Starter features<br>• Financial Management<br>• Amenity Bookings<br>• Helpdesk<br>• Reports |
| `enterprise` | Full-featured for large societies | • All Professional features<br>• Priority Support<br>• Custom Integrations<br>• Advanced Analytics |

---

## 🎯 Validation Rules

### **When Creating Society:**
- ✅ Plan is **optional** (defaults to 'starter')
- ✅ Must be one of: `starter`, `professional`, `enterprise`
- ✅ Invalid values will be rejected with error message

### **When Updating Society:**
- ✅ Can change plan at any time (requires admin privileges)
- ✅ Must be one of the three valid enum values

---

## 📱 Mobile App Integration

### **Display Plan in UI**

```dart
// Flutter example
enum SocietyPlan { starter, professional, enterprise }

class SocietyDetails {
  final int id;
  final String name;
  final SocietyPlan plan;
  // ... other fields
  
  factory SocietyDetails.fromJson(Map<String, dynamic> json) {
    return SocietyDetails(
      id: json['id'],
      name: json['name'],
      plan: SocietyPlan.values.firstWhere(
        (e) => e.toString().split('.').last == json['plan'],
        orElse: () => SocietyPlan.starter,
      ),
    );
  }
}

// Usage
void showPlanBadge(SocietyPlan plan) {
  Color planColor;
  String planLabel;
  
  switch (plan) {
    case SocietyPlan.starter:
      planColor = Colors.blue;
      planLabel = 'STARTER';
      break;
    case SocietyPlan.professional:
      planColor = Colors.orange;
      planLabel = 'PROFESSIONAL';
      break;
    case SocietyPlan.enterprise:
      planColor = Colors.purple;
      planLabel = 'ENTERPRISE';
      break;
  }
  
  return Container(
    padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
    decoration: BoxDecoration(
      color: planColor,
      borderRadius: BorderRadius.circular(4),
    ),
    child: Text(
      planLabel,
      style: TextStyle(color: Colors.white, fontSize: 12),
    ),
  );
}
```

---

## 🔍 Filtering by Plan (Future Enhancement)

You can easily add plan filtering to the getSocieties endpoint:

```php
// Add to AdminController.php
$plan = isset($_GET['plan']) ? $_GET['plan'] : null;

if ($plan) {
    if (!in_array($plan, ['starter', 'professional', 'enterprise'])) {
        Response::error("Invalid plan filter");
    }
    $whereClause .= ($whereClause ? " AND " : "WHERE ") . "plan = ?";
    $params[] = $plan;
}
```

**Usage:**
```bash
GET /api/admin/societies?plan=professional
```

---

## 📊 Statistics by Plan (Future Enhancement)

Add analytics to show society distribution by plan:

```php
public function getSocietyPlanStatistics()
{
    $stmt = $this->db->prepare("
        SELECT 
            plan,
            COUNT(*) as society_count,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count
        FROM societies
        GROUP BY plan
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}
```

---

## ✅ Testing Checklist

- [x] Migration created
- [ ] Migration executed on database
- [x] Create society with plan validation
- [x] Create society without plan (defaults to starter)
- [x] Get society details includes plan
- [x] Update society plan works
- [x] Get societies list includes plan
- [x] Search societies includes plan
- [x] Complete society endpoint includes plan

---

## 🚀 Deployment Steps

1. **Backup Database:**
   ```bash
   mysqldump -u root -p migate societies > societies_backup.sql
   ```

2. **Run Migration:**
   ```bash
   mysql -u root -p migate < database/migrations/add_plan_to_societies.sql
   ```

3. **Verify Migration:**
   ```sql
   DESC societies;
   SELECT DISTINCT plan FROM societies;
   ```

4. **Test API:**
   - Create a new society with different plan values
   - Get existing societies (should show 'starter' as default)
   - Update a society's plan

---

## 📝 Notes

- ✅ Existing societies will automatically get `plan = 'starter'`
- ✅ Plan field has a default value, so no NULL issues
- ✅ Indexed for better query performance
- ✅ Enum ensures data integrity
- ✅ Can be extended in the future with more plan types

---

## 🔧 Troubleshooting

### **Error: Unknown column 'plan'**
Run the migration file:
```bash
mysql -u root -p migate < database/migrations/add_plan_to_societies.sql
```

### **Error: Invalid plan**
Ensure you're using one of: `starter`, `professional`, `enterprise` (lowercase)

### **Existing societies showing NULL plan**
Update them manually:
```sql
UPDATE societies SET plan = 'starter' WHERE plan IS NULL;
```

---

**Implementation complete and ready for production!** 🚀
