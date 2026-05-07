# Quick Reference: Society Plan Field

## ✅ What Was Added

A **`plan`** field to societies with three enum values:
- `starter` (default)
- `professional`  
- `enterprise`

---

## 📁 Files Modified

1. **`database/migrations/add_plan_to_societies.sql`** ⭐ NEW
   - Migration to add plan column

2. **`app/modules/admin/AdminController.php`**
   - Create society: Validates and stores plan
   - Get society: Returns plan field
   - Update society: Plan can be updated
   - List societies: Plan included
   - Search societies: Plan included

3. **`PLAN_FIELD_IMPLEMENTATION.md`** ⭐ NEW
   - Complete documentation

---

## 🚀 Quick Start

### 1. Run Migration
```bash
mysql -u root -p migate < database/migrations/add_plan_to_societies.sql
```

### 2. Create Society with Plan
```bash
curl -X POST http://localhost:8080/api/admin/societies \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "My Society",
    "address": "123 Street",
    "city": "Mumbai",
    "contact_phone": "+919876543210",
    "plan": "professional"
  }'
```

### 3. Create Society (Defaults to Starter)
```bash
curl -X POST http://localhost:8080/api/admin/societies \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Basic Society",
    "address": "456 Street",
    "city": "Pune",
    "contact_phone": "+919876543211"
  }'
# Will be created with plan: "starter"
```

---

## 📊 Plan Values

| Plan | Use Case | Default |
|------|----------|---------|
| `starter` | Small/basic societies | ✅ Yes |
| `professional` | Medium societies with advanced features | ❌ |
| `enterprise` | Large societies with full features | ❌ |

---

## 🎯 Validation

- ✅ Optional field (defaults to 'starter')
- ✅ Must be: `starter`, `professional`, or `enterprise`
- ✅ Case-sensitive (must be lowercase)
- ✅ Can be updated anytime

---

## 📱 Response Examples

### Create Society Response
```json
{
  "status": true,
  "message": "Society created successfully",
  "data": {
    "society_id": 15
  }
}
```

### Get Society Response
```json
{
  "status": true,
  "message": "Society retrieved successfully",
  "data": {
    "id": 15,
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

## 🔧 Update Society Plan

```bash
curl -X PUT http://localhost:8080/api/admin/societies/15 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "plan": "enterprise"
  }'
```

---

## 📋 All Modified Endpoints

1. ✅ `POST /api/admin/societies` - Create with plan
2. ✅ `GET /api/admin/societies` - List includes plan
3. ✅ `GET /api/admin/societies/{id}` - Get includes plan
4. ✅ `GET /api/admin/societies/{id}/complete` - Complete includes plan
5. ✅ `PUT /api/admin/societies/{id}` - Update plan
6. ✅ `GET /api/societies/search` - Search includes plan

---

## ⚠️ Important Notes

- Existing societies will have `plan = 'starter'` after migration
- Plan field is indexed for performance
- Cannot be NULL (has default value)
- Easy to add more plan types in future

---

## 📚 Full Documentation

See `PLAN_FIELD_IMPLEMENTATION.md` for:
- Complete API examples
- Mobile app integration guide
- Testing checklist
- Deployment steps
- Troubleshooting

---

**Ready to use! Run the migration and start using the plan field.** 🚀
