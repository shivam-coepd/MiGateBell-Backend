# Postman Collection Updates

## Overview
The Postman collection has been updated to reflect the recent backend enhancements for registration/login responses and the new complete society information endpoint.

---

## 📝 Updates Made

### 1. **Enhanced Registration Response**

**Endpoint:** `POST /api/auth/register`

**What Changed:**
- Updated response examples to show complete user profile instead of basic info
- Response now includes full user object with all details

**Previous Response Example:**
```json
{
  "status": true,
  "message": "Registration successful",
  "data": {
    "user_id": "8",
    "app_user_id": "SPA-173678",
    "token": "..."
  }
}
```

**New Response Example:**
```json
{
  "status": true,
  "message": "Registration successful",
  "data": {
    "user": {
      "id": 123,
      "app_user_id": "USR000123",
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "9876543210",
      "role": "resident",
      "society_id": 1,
      "profile_image": null,
      "cover_image_url": null,
      "resident_type": "owner",
      "bio": null,
      "profession": null,
      "hometown": null,
      "status": "pending_verification",
      "created_at": "2024-01-20 10:00:00",
      "updated_at": "2024-01-20 10:00:00",
      "society": {
        "id": 1,
        "name": "Green Valley Society",
        "address": "123 Main Street",
        "city": "Mumbai",
        "state": "Maharashtra",
        "pincode": "400001"
      },
      "resident_data": {
        "family_members": [],
        "flats": [],
        "vehicles": [],
        "pets": []
      }
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
  }
}
```

---

### 2. **Enhanced Login Response**

**Endpoint:** `POST /api/auth/login`

**What Changed:**
- Updated response examples to show complete user profile
- Includes all user details, society info, and role-specific data
- Shows comprehensive example for resident role

**Previous Response Example:**
```json
{
  "status": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 4,
      "app_user_id": "USR-00004",
      "name": "Shivam Khule",
      "phone": "8010155144",
      "role": "resident",
      "society_id": 1
    },
    "token": "..."
  }
}
```

**New Response Example:**
```json
{
  "status": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 4,
      "app_user_id": "USR-00004",
      "name": "Shivam Khule",
      "email": "shivam@example.com",
      "phone": "8010155144",
      "role": "resident",
      "society_id": 1,
      "profile_image": null,
      "cover_image_url": null,
      "resident_type": "owner",
      "bio": null,
      "profession": "Software Engineer",
      "hometown": "Ahilyanagar",
      "status": "active",
      "created_at": "2025-12-16 11:56:25",
      "updated_at": "2025-12-22 05:00:44",
      "society": {
        "id": 1,
        "name": "Blue Horizon Apartments",
        "address": "Sector 7, Aundh-Baner Link Road",
        "city": "Pune",
        "state": "Maharashtra",
        "pincode": "411007"
      },
      "resident_data": {
        "family_members": [
          {
            "id": 1,
            "name": "Rohini Khule",
            "relation": "Mother",
            "phone": "9325821320",
            "image_url": "https://example.com/photo.jpg",
            "is_active": 1
          }
        ],
        "flats": [
          {
            "id": 1,
            "flat_number": "101",
            "floor_number": "1",
            "area_sqft": "1050.00",
            "is_occupied": 1,
            "building_name": "Building A"
          }
        ],
        "vehicles": [
          {
            "id": 1,
            "vehicle_number": "MH01AB1234",
            "type_name": "Car",
            "color": "Black"
          }
        ],
        "pets": [
          {
            "id": 1,
            "name": "Max",
            "breed": "Golden Retriever",
            "age": 3,
            "pet_type_name": "Dog"
          }
        ]
      }
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
  }
}
```

---

### 3. **NEW: Complete Society Information Endpoint**

**Endpoint:** `GET /api/admin/societies/{society_id}/complete`

**Location in Collection:**
- Navigate to: **Society & Building Management** → **Get Complete Society Information**

**Authentication:**
- Required: Yes (Bearer Token)

**Request Example:**
```
GET {{app_url}}/api/admin/societies/1/complete
Authorization: Bearer {{auth_token}}
```

**Response Includes:**
✅ Basic society details  
✅ Buildings with occupancy statistics  
✅ User statistics (by role and status)  
✅ Recent users (last 10)  
✅ Visitor statistics (comprehensive breakdown)  
✅ Today's visitors list  
✅ Financial summary (invoices, payments, revenue)  
✅ Charge heads configuration  
✅ Amenities with booking statistics  
✅ Communication groups  
✅ Helpdesk/tickets summary  
✅ Recent tickets (last 10)  
✅ Recent announcements (last 5)  
✅ Assets summary  
✅ Total vehicles and pets count  

**Response Example (Summary):**
```json
{
  "status": true,
  "message": "Complete society information retrieved successfully",
  "data": {
    "id": 1,
    "name": "Blue Horizon Apartments",
    "address": "Sector 7, Aundh-Baner Link Road",
    "city": "Pune",
    "state": "Maharashtra",
    "country": "India",
    "pincode": "411007",
    "buildings": [...],
    "user_statistics": {...},
    "visitor_statistics": {...},
    "financial_summary": {...},
    "amenities": [...],
    "helpdesk_summary": {...},
    "total_vehicles": 300,
    "total_pets": 75
  }
}
```

---

## 📋 How to Use the Updated Collection

### Import the Collection

1. Open Postman
2. Click **Import** button
3. Select the file: `MyGate API Copy.postman_collection.json`
4. Click **Import**

### Test Registration

1. Navigate to: **Authentication** → **Register**
2. Select the request
3. Use the pre-filled example in the body
4. Click **Send**
5. View the complete user profile in response

### Test Login

1. Navigate to: **Authentication** → **Login**
2. Select the request
3. Use one of the example credentials:
   - Resident: `8010155144` / `Pass@123`
   - Admin: Check body comments for other users
4. Click **Send**
5. View the complete user profile in response

### Test Complete Society Information

1. Navigate to: **Society & Building Management** → **Get Complete Society Information**
2. First, login and copy the token
3. Set the environment variable `{{auth_token}}` with your token
4. Set the environment variable `{{app_url}}` (e.g., `http://localhost:8080`)
5. Click **Send**
6. View the comprehensive society data

---

## 🔧 Environment Variables Required

Make sure these variables are set in your Postman environment:

| Variable | Example Value | Description |
|----------|--------------|-------------|
| `app_url` | `http://localhost:8080` | Your API base URL |
| `auth_token` | `eyJ0eXAiOiJKV1Qi...` | JWT token from login |
| `base_url` | `http://localhost:8080` | Same as app_url (alternative) |

---

## 📊 Response Examples Included

### Registration
- ✅ Complete user profile with society details
- ✅ Empty resident_data arrays for new users
- ✅ JWT token included

### Login
- ✅ Complete user profile for resident role
- ✅ Family members list
- ✅ Flats information
- ✅ Vehicles list
- ✅ Pets list
- ✅ Society details

### Complete Society
- ✅ Full society information with all related data
- ✅ Buildings with occupancy stats
- ✅ User statistics breakdown
- ✅ Visitor statistics
- ✅ Financial summary
- ✅ Helpdesk summary
- ✅ Assets summary

---

## 🎯 Benefits of Updated Collection

### For Developers
1. **Clear Expectations** - See exactly what the API returns
2. **Faster Integration** - Copy response structures directly
3. **Better Testing** - Comprehensive examples for all scenarios

### For Mobile App Development
1. **Data Models** - Use response examples to create data classes
2. **UI Planning** - Know what data is available for each screen
3. **State Management** - Understand complete data structure

### For QA/Testing
1. **Validation** - Verify API responses match examples
2. **Edge Cases** - Test with different user roles
3. **Performance** - Monitor response sizes

---

## 📝 Notes

- All response examples are properly formatted JSON
- Sensitive data (tokens, personal info) are examples only
- Actual responses may vary based on database content
- Role-specific data only appears for applicable roles

---

## 🚀 Next Steps

1. **Import** the updated collection into Postman
2. **Set up** environment variables
3. **Test** each endpoint
4. **Use** response examples for mobile app data models
5. **Refer** to documentation for detailed explanations

---

## 📚 Related Documentation

- `REGISTRATION_LOGIN_ENHANCEMENT.md` - Registration/Login changes
- `COMPLETE_SOCIETY_INFORMATION.md` - Complete society endpoint details
- `API_DOCUMENTATION.md` - General API documentation

---

**Collection is now up-to-date with all latest backend changes!** 🎉
