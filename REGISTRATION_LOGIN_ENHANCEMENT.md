# Registration & Login API Enhancement

## Overview
Enhanced the registration and login endpoints to return complete user profile details, enabling mobile apps to properly handle user data without requiring additional API calls.

## Changes Made

### 1. Enhanced Registration Response
**Endpoint:** `POST /api/auth/register`

**Previous Response:**
```json
{
  "status": true,
  "message": "Registration successful",
  "data": {
    "user_id": 123,
    "app_user_id": "USR00001",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
  }
}
```

**New Response:**
```json
{
  "status": true,
  "message": "Registration successful",
  "data": {
    "user": {
      "id": 123,
      "app_user_id": "USR00001",
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "9876543210",
      "role": "resident",
      "society_id": 1,
      "profile_image": "https://example.com/image.jpg",
      "cover_image_url": "https://example.com/cover.jpg",
      "resident_type": "owner",
      "bio": "Short bio about the user",
      "profession": "Software Engineer",
      "hometown": "Mumbai",
      "status": "pending_verification",
      "created_at": "2024-01-01T10:00:00Z",
      "updated_at": "2024-01-01T10:00:00Z",
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

### 2. Enhanced Login Response
**Endpoint:** `POST /api/auth/login`

**Previous Response:**
```json
{
  "status": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 123,
      "app_user_id": "USR00001",
      "name": "John Doe",
      "phone": "9876543210",
      "role": "resident",
      "society_id": 1
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
  }
}
```

**New Response:**
```json
{
  "status": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 123,
      "app_user_id": "USR00001",
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "9876543210",
      "role": "resident",
      "society_id": 1,
      "profile_image": "https://example.com/image.jpg",
      "cover_image_url": "https://example.com/cover.jpg",
      "resident_type": "owner",
      "bio": "Short bio about the user",
      "profession": "Software Engineer",
      "hometown": "Mumbai",
      "status": "active",
      "created_at": "2024-01-01T10:00:00Z",
      "updated_at": "2024-01-01T10:00:00Z",
      "society": {
        "id": 1,
        "name": "Green Valley Society",
        "address": "123 Main Street",
        "city": "Mumbai",
        "state": "Maharashtra",
        "pincode": "400001"
      },
      "resident_data": {
        "family_members": [
          {
            "id": 1,
            "name": "Jane Doe",
            "relation": "Spouse",
            "phone": "9876543211",
            "image_url": "https://example.com/jane.jpg",
            "is_active": 1,
            "resident_name": "John Doe",
            "resident_email": "john@example.com"
          }
        ],
        "flats": [
          {
            "id": 1,
            "flat_number": "101",
            "floor_number": "1",
            "area_sqft": 1200.00,
            "is_occupied": true,
            "building_name": "Building A"
          }
        ],
        "vehicles": [
          {
            "id": 1,
            "vehicle_number": "MH01AB1234",
            "type_name": "Car",
            "resident_id": 123
          }
        ],
        "pets": [
          {
            "id": 1,
            "name": "Max",
            "breed": "Golden Retriever",
            "age": 3,
            "vaccination_status": "Up to date",
            "pet_type_name": "Dog"
          }
        ]
      }
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
  }
}
```

## Role-Specific Data

The response includes role-specific data based on the user's role:

### 1. Resident Role
Includes `resident_data` with:
- **family_members**: List of family members associated with the resident
- **flats**: Properties owned or rented by the resident
- **vehicles**: Registered vehicles
- **pets**: Registered pets

### 2. Guard Role
Includes `guard_data` with:
- **today_stats**: Statistics about today's visitors handled by the guard

### 3. Staff Role
Includes `staff_data` with:
- **tasks**: Count of open tickets/tasks assigned to the staff

### 4. Admin & Super Admin Roles
Basic profile information without role-specific data (can be extended in the future)

## Implementation Details

### New Methods Added to AuthController

1. **`getCompleteUserProfile($userId)`**
   - Fetches complete user profile from the database
   - Includes society details if applicable
   - Calls role-specific data methods based on user role

2. **`getResidentData($userId)`**
   - Fetches family members, flats, vehicles, and pets for residents

3. **`getGuardData($userId)`**
   - Fetches today's visitor statistics for guards

4. **`getStaffData($userId)`**
   - Fetches open task/ticket count for staff members

## Benefits

1. **Reduced API Calls**: Mobile app gets all necessary data in a single response
2. **Better User Experience**: No need for multiple loading states after registration/login
3. **Consistent Data Structure**: Registration and login responses have the same structure
4. **Easier State Management**: Mobile app can directly store complete user data in local state
5. **Role-Aware UI**: App can immediately render role-specific UI elements

## Mobile App Implementation Guide

### After Registration/Login:
```dart
// Flutter/Dart example
Future<void> handleAuthSuccess(AuthResponse response) async {
  // Save token
  await sharedPreferences.setString('token', response.data.token);
  
  // Save complete user profile
  await sharedPreferences.setString('user', jsonEncode(response.data.user));
  
  // Navigate based on user role
  if (response.data.user.role == 'resident') {
    Navigator.pushReplacement(context, ResidentDashboardRoute());
  } else if (response.data.user.role == 'guard') {
    Navigator.pushReplacement(context, GuardDashboardRoute());
  }
  
  // Initialize app with complete user data
  AppUser.currentUser = User.fromJson(response.data.user);
}
```

### Accessing User Data:
```dart
// Access basic info
String name = AppUser.currentUser.name;
String email = AppUser.currentUser.email;
String role = AppUser.currentUser.role;

// Access society info (if available)
if (AppUser.currentUser.society != null) {
  String societyName = AppUser.currentUser.society.name;
  String city = AppUser.currentUser.society.city;
}

// Access resident-specific data (for residents)
if (AppUser.currentUser.residentData != null) {
  List<Flat> flats = AppUser.currentUser.residentData.flats;
  List<Vehicle> vehicles = AppUser.currentUser.residentData.vehicles;
}
```

## Files Modified

1. `app/modules/auth/AuthController.php`
   - Enhanced `register()` method
   - Enhanced `login()` method
   - Added `getCompleteUserProfile()` method
   - Added `getResidentData()` method
   - Added `getGuardData()` method
   - Added `getStaffData()` method

## Testing

### Registration Test:
```bash
curl -X POST http://localhost:8080/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "phone": "9876543210",
    "password": "Test@1234",
    "society_id": 1,
    "role": "resident"
  }'
```

### Login Test:
```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "9876543210",
    "password": "Test@1234"
  }'
```

## Notes

- All existing functionality remains intact
- The changes are backward compatible
- Empty arrays are returned for role-specific data if no records exist
- Society details are only included if the user has a society_id
- Profile fields that are NULL will still be included in the response with null values
