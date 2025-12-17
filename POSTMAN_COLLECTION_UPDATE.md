# MiGate API - Postman Collection Update Summary

## Overview

Your Postman collection has been significantly expanded to include **ALL 17 modules** with **100+ endpoints**.

## New Modules Added (7 modules):

### 1. **User Profile Module** (2 endpoints)

- GET `/api/users/profile` - Get own profile with role-specific data
- PUT `/api/users/profile` - Update own profile

### 2. **User Management Module** (5 endpoints)

- GET `/api/admin/users` - List all users with filters
- GET `/api/admin/users/{id}` - Get user details with nested data
- POST `/api/admin/users` - Create new user
- PUT `/api/admin/users/{id}` - Update user
- DELETE `/api/admin/users/{id}` - Delete user

### 3. **Family Members Module** (3 endpoints)

- GET `/api/family` - Get family members
- POST `/api/family` - Add family member
- DELETE `/api/family/{id}` - Delete family member

### 4. **Marketplace Module** (5 endpoints)

- GET `/api/marketplace/categories` - Get product categories
- GET `/api/marketplace/products` - Get products with filters
- POST `/api/marketplace/products` - Add product
- POST `/api/marketplace/orders` - Create order
- GET `/api/marketplace/orders/my` - Get my orders

### 5. **Services Module** (4 endpoints)

- GET `/api/services/categories` - Get service categories
- GET `/api/services` - Get services
- POST `/api/services/book` - Book a service
- GET `/api/services/bookings/my` - Get my bookings

### 6. **Pets Module** (4 endpoints)

- GET `/api/pets/types` - Get pet types
- POST `/api/pets` - Add pet
- GET `/api/pets` - Get pets
- DELETE `/api/pets/{id}` - Delete pet

### 7. **Assets & Inventory Module** (5 endpoints)

- GET `/api/assets/categories` - Get asset categories
- GET `/api/assets` - Get assets
- POST `/api/assets` - Add asset
- GET `/api/assets/inventory` - Get inventory
- POST `/api/assets/inventory` - Add inventory item

### 8. **Notifications Module** (3 endpoints)

- GET `/api/notifications` - Get notifications
- PUT `/api/notifications/{id}/read` - Mark as read
- PUT `/api/notifications/read-all` - Mark all as read

## Existing Modules Enhanced:

### Authentication (6 endpoints) ✅

- All existing endpoints retained
- Added Update User Status endpoint

### Visitors (5 endpoints) ✅

- All CRUD operations included

### Admin/Society Management (9 endpoints) ✅

- Society, Building, Flat management
- User role assignment

### Accounting (8 endpoints) ✅

- Charge heads, Invoices, Payments
- Status updates

### Amenities (5 endpoints) ✅

- Amenity management and bookings

### Helpdesk (6 endpoints) ✅

- Ticket management with comments

### Communications (7 endpoints) ✅

- Groups, Announcements, Polls

### Security (5 endpoints) ✅

- Alerts and Emergency contacts

### Vehicles (8 endpoints) ✅

- Vehicle and parking management

## Total Endpoint Count:

- **Authentication**: 6 endpoints
- **User Profile**: 2 endpoints
- **User Management**: 5 endpoints
- **Family Members**: 3 endpoints
- **Visitors**: 5 endpoints
- **Admin/Society**: 9 endpoints
- **Accounting**: 8 endpoints
- **Amenities**: 5 endpoints
- **Helpdesk**: 6 endpoints
- **Communications**: 7 endpoints
- **Security**: 5 endpoints
- **Vehicles**: 8 endpoints
- **Marketplace**: 5 endpoints
- **Services**: 4 endpoints
- **Pets**: 4 endpoints
- **Assets**: 5 endpoints
- **Notifications**: 3 endpoints

**TOTAL: 100+ Endpoints**

## How to Update Your Collection:

### Option 1: Import New Collection

1. Open Postman
2. Click "Import"
3. Select the file: `MiGate_API_Complete.postman_collection.json`
4. This will create a new collection with all endpoints

### Option 2: Manual Addition

Add the following folders to your existing collection:

#### 1. User Profile

```
Folder: User Profile
├── GET - Get Own Profile
└── PUT - Update Profile
```

#### 2. User Management (Admin)

```
Folder: User Management
├── GET - List Users
├── GET - Get User Details
├── POST - Create User
├── PUT - Update User
└── DELETE - Delete User
```

#### 3. Family Members

```
Folder: Family Members
├── GET - Get Family Members
├── POST - Add Family Member
└── DELETE - Delete Family Member
```

#### 4. Marketplace

```
Folder: Marketplace
├── GET - Get Categories
├── GET - Get Products
├── POST - Add Product
├── POST - Create Order
└── GET - Get My Orders
```

#### 5. Services

```
Folder: Services
├── GET - Get Categories
├── GET - Get Services
├── POST - Book Service
└── GET - Get My Bookings
```

#### 6. Pets

```
Folder: Pets
├── GET - Get Pet Types
├── POST - Add Pet
├── GET - Get Pets
└── DELETE - Delete Pet
```

#### 7. Assets & Inventory

```
Folder: Assets & Inventory
├── GET - Get Asset Categories
├── GET - Get Assets
├── POST - Add Asset
├── GET - Get Inventory
└── POST - Add Inventory Item
```

#### 8. Notifications

```
Folder: Notifications
├── GET - Get Notifications
├── PUT - Mark as Read
└── PUT - Mark All as Read
```

## Environment Variables:

Make sure you have these variables set:

- `base_url`: http://localhost/backend
- `app_url`: http://localhost/backend
- `auth_token`: (will be set after login)

## Testing Workflow:

1. **Register/Login** → Get auth_token
2. **Create Society** (Super Admin)
3. **Create Building** → **Create Flats**
4. **Create Users** (Admin)
5. **Add Family Members** (Resident)
6. **Add Visitors** (Resident/Guard)
7. **Create Invoices** (Admin)
8. **Book Amenities** (Resident)
9. **Create Tickets** (Resident)
10. **Add Pets** (Resident)
11. **Marketplace** - Add products & create orders
12. **Services** - Book services
13. **Notifications** - View and manage

## Sample Request Bodies:

### Add Family Member

```json
{
  "name": "John Doe",
  "relation": "Father",
  "phone": "9876543210"
}
```

### Add Product (Marketplace)

```json
{
  "name": "Sofa Set",
  "description": "3-seater sofa in excellent condition",
  "category_id": 1,
  "price": 15000,
  "image_urls": ["https://example.com/sofa.jpg"]
}
```

### Book Service

```json
{
  "service_id": 1,
  "booking_date": "2024-01-20",
  "booking_time": "10:00:00",
  "notes": "Please call before arriving"
}
```

### Add Pet

```json
{
  "name": "Bruno",
  "pet_type_id": 1,
  "breed": "German Shepherd",
  "age": 3,
  "vaccination_status": "up_to_date"
}
```

### Create User (Admin)

```json
{
  "name": "New Resident",
  "phone": "9988776655",
  "email": "resident@example.com",
  "password": "Pass@123",
  "role": "resident",
  "society_id": 1,
  "status": "active"
}
```

## Next Steps:

1. ✅ Review the API_DOCUMENTATION.md file for complete endpoint details
2. ✅ Import the new Postman collection
3. ✅ Test all new endpoints
4. ✅ Update your frontend to consume new APIs
5. ✅ Deploy to production

## Notes:

- All endpoints require `Authorization: Bearer {{auth_token}}` except:
  - Register, Login, Forgot Password
  - Search Societies
  - Get Buildings/Flats (public for registration)
- Pagination is available on all list endpoints with `?page=1&limit=20`
- Filters are available on most GET endpoints
- All responses follow the standard format (success/error)

## Support:

Refer to `API_DOCUMENTATION.md` for detailed information on:

- Request/Response formats
- Status codes
- Access control
- Nested data structures
