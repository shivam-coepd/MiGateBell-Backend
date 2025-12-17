# Quick Reference - All New API Endpoints

## Base URL

```
{{base_url}}/api
```

---

## 1. USER PROFILE (2 endpoints)

### Get Own Profile

```
GET /users/profile
Headers: Authorization: Bearer {{auth_token}}
```

### Update Profile

```
PUT /users/profile
Headers: Authorization: Bearer {{auth_token}}
Body: {
  "name": "Updated Name",
  "email": "newemail@example.com",
  "profile_image": "https://example.com/image.jpg"
}
```

---

## 2. USER MANAGEMENT - ADMIN (5 endpoints)

### List All Users

```
GET /admin/users?page=1&limit=20&role=resident&status=active&search=john
Headers: Authorization: Bearer {{auth_token}}
```

### Get User Details

```
GET /admin/users/1
Headers: Authorization: Bearer {{auth_token}}
```

### Create User

```
POST /admin/users
Headers: Authorization: Bearer {{auth_token}}
Body: {
  "name": "New User",
  "phone": "9988776655",
  "email": "user@example.com",
  "password": "Pass@123",
  "role": "resident",
  "society_id": 1,
  "status": "active"
}
```

### Update User

```
PUT /admin/users/1
Headers: Authorization: Bearer {{auth_token}}
Body: {
  "name": "Updated Name",
  "status": "active"
}
```

### Delete User

```
DELETE /admin/users/1
Headers: Authorization: Bearer {{auth_token}}
```

---

## 3. FAMILY MEMBERS (3 endpoints)

### Get Family Members

```
GET /family
Headers: Authorization: Bearer {{auth_token}}
Query (Admin only): ?resident_id=1
```

### Add Family Member

```
POST /family
Headers: Authorization: Bearer {{auth_token}}
Body: {
  "name": "John Doe",
  "relation": "Father",
  "phone": "9876543210",
  "image_url": "https://example.com/photo.jpg"
}
```

### Delete Family Member

```
DELETE /family/1
Headers: Authorization: Bearer {{auth_token}}
```

---

## 4. MARKETPLACE (5 endpoints)

### Get Categories

```
GET /marketplace/categories
Headers: Authorization: Bearer {{auth_token}}
```

### Get Products

```
GET /marketplace/products?category_id=1&search=sofa
Headers: Authorization: Bearer {{auth_token}}
```

### Add Product

```
POST /marketplace/products
Headers: Authorization: Bearer {{auth_token}}
Body: {
  "name": "Sofa Set",
  "description": "3-seater sofa",
  "category_id": 1,
  "price": 15000,
  "image_urls": ["https://example.com/sofa.jpg"]
}
```

### Create Order

```
POST /marketplace/orders
Headers: Authorization: Bearer {{auth_token}}
Body: {
  "items": [
    {
      "product_id": 1,
      "quantity": 1
    }
  ],
  "address": "Flat 101, Building A"
}
```

### Get My Orders

```
GET /marketplace/orders/my
Headers: Authorization: Bearer {{auth_token}}
```

---

## 5. SERVICES (4 endpoints)

### Get Service Categories

```
GET /services/categories
Headers: Authorization: Bearer {{auth_token}}
```

### Get Services

```
GET /services?category_id=1
Headers: Authorization: Bearer {{auth_token}}
```

### Book Service

```
POST /services/book
Headers: Authorization: Bearer {{auth_token}}
Body: {
  "service_id": 1,
  "booking_date": "2024-01-20",
  "booking_time": "10:00:00",
  "notes": "Please call before arriving"
}
```

### Get My Bookings

```
GET /services/bookings/my
Headers: Authorization: Bearer {{auth_token}}
```

---

## 6. PETS (4 endpoints)

### Get Pet Types

```
GET /pets/types
Headers: Authorization: Bearer {{auth_token}}
```

### Add Pet

```
POST /pets
Headers: Authorization: Bearer {{auth_token}}
Body: {
  "name": "Bruno",
  "pet_type_id": 1,
  "breed": "German Shepherd",
  "age": 3,
  "weight": 25.5,
  "vaccination_status": "up_to_date",
  "image_url": "https://example.com/bruno.jpg"
}
```

### Get Pets

```
GET /pets
Headers: Authorization: Bearer {{auth_token}}
```

### Delete Pet

```
DELETE /pets/1
Headers: Authorization: Bearer {{auth_token}}
```

---

## 7. ASSETS & INVENTORY (5 endpoints)

### Get Asset Categories

```
GET /assets/categories
Headers: Authorization: Bearer {{auth_token}}
```

### Get Assets

```
GET /assets
Headers: Authorization: Bearer {{auth_token}}
Access: Admin, Super Admin, Staff
```

### Add Asset

```
POST /assets
Headers: Authorization: Bearer {{auth_token}}
Body: {
  "name": "Generator",
  "category_id": 1,
  "serial_number": "GEN-2024-001",
  "purchase_date": "2024-01-01",
  "purchase_cost": 50000,
  "location": "Basement"
}
Access: Admin, Super Admin
```

### Get Inventory

```
GET /assets/inventory
Headers: Authorization: Bearer {{auth_token}}
Access: Admin, Super Admin, Staff
```

### Add Inventory Item

```
POST /assets/inventory
Headers: Authorization: Bearer {{auth_token}}
Body: {
  "name": "LED Bulbs",
  "category": "Electrical",
  "unit": "pcs",
  "quantity_in_stock": 100,
  "reorder_level": 20
}
Access: Admin, Super Admin
```

---

## 8. NOTIFICATIONS (3 endpoints)

### Get Notifications

```
GET /notifications?page=1&limit=20
Headers: Authorization: Bearer {{auth_token}}
```

### Mark as Read

```
PUT /notifications/1/read
Headers: Authorization: Bearer {{auth_token}}
```

### Mark All as Read

```
PUT /notifications/read-all
Headers: Authorization: Bearer {{auth_token}}
```

---

## EXISTING ENDPOINTS (Quick Reference)

### Authentication

- POST `/auth/register`
- POST `/auth/login`
- POST `/auth/refresh`
- POST `/auth/change-password`
- POST `/auth/forgot-password`
- POST `/auth/logout`
- PUT `/auth/users/{id}/status`

### Visitors

- POST `/visitors`
- GET `/visitors`
- GET `/visitors/{id}`
- PUT `/visitors/{id}/status`
- DELETE `/visitors/{id}`

### Admin/Society

- POST `/admin/societies`
- GET `/admin/societies`
- GET `/societies/search`
- GET `/admin/societies/{id}`
- PUT `/admin/societies/{id}`
- DELETE `/admin/societies/{id}`
- POST `/buildings`
- GET `/buildings/by-society/{id}`
- POST `/flats`
- GET `/flats/by-building/{id}`
- POST `/admin/user-role`

### Accounting

- POST `/accounting/charge-heads`
- GET `/accounting/charge-heads`
- POST `/accounting/invoices`
- GET `/accounting/invoices`
- GET `/accounting/invoices/{id}`
- PUT `/accounting/invoices/{id}/status`
- POST `/accounting/payments`
- PUT `/accounting/payments/{id}/status`

### Amenities

- POST `/amenities`
- GET `/amenities`
- POST `/amenities/{id}/book`
- GET `/amenities/bookings`
- PUT `/amenities/bookings/{id}/status`

### Helpdesk

- POST `/helpdesk/tickets`
- GET `/helpdesk/tickets`
- GET `/helpdesk/tickets/{id}`
- PUT `/helpdesk/tickets/{id}/status`
- PUT `/helpdesk/tickets/{id}/assign`
- POST `/helpdesk/tickets/{id}/comments`

### Communications

- POST `/communications/groups`
- GET `/communications/groups`
- POST `/communications/groups/{id}/join`
- POST `/communications/groups/{id}/leave`
- POST `/communications/announcements`
- GET `/communications/announcements`
- POST `/communications/polls`
- GET `/communications/polls`
- POST `/communications/polls/{id}/vote`

### Security

- POST `/security/alerts`
- GET `/security/alerts`
- GET `/security/alerts/{id}`
- PUT `/security/alerts/{id}/status`
- GET `/security/emergency-contacts`
- POST `/security/emergency-contacts`

### Vehicles

- POST `/vehicles`
- GET `/vehicles`
- GET `/vehicles/{id}`
- PUT `/vehicles/{id}`
- DELETE `/vehicles/{id}`
- GET `/vehicles/parking-spots`
- POST `/vehicles/parking-spots/{id}/assign`
- POST `/vehicles/parking-spots/{id}/release`

---

## Testing Order (Recommended)

1. **Setup**

   - Register Super Admin
   - Login → Save auth_token
   - Create Society
   - Create Buildings
   - Create Flats

2. **User Management**

   - Create Admin user
   - Create Resident users
   - Create Guard/Staff users
   - View all users
   - Get user details (with nested data)

3. **Resident Features**

   - Get own profile
   - Add family members
   - Add pets
   - Add visitors
   - View invoices
   - Book amenities
   - Create tickets
   - Browse marketplace
   - Book services

4. **Admin Features**

   - Manage users
   - Create invoices
   - Manage amenities
   - Assign tickets
   - View all data

5. **Notifications**
   - View notifications
   - Mark as read

---

## Common Query Parameters

### Pagination (Most GET endpoints)

```
?page=1&limit=20
```

### Filtering

```
?status=active
?role=resident
?category_id=1
?search=keyword
?is_active=1
```

### Date Filters

```
?start_date=2024-01-01
?end_date=2024-01-31
```

---

## Response Format

### Success

```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Error

```json
{
  "success": false,
  "message": "Error message",
  "errors": ["Error 1", "Error 2"]
}
```

### Paginated

```json
{
  "success": true,
  "message": "Data retrieved",
  "data": {
    "data": [...],
    "pagination": {
      "page": 1,
      "limit": 20,
      "total": 100,
      "pages": 5
    }
  }
}
```

---

## Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `409` - Conflict
- `500` - Internal Server Error

---

## Environment Variables Setup

```
base_url = http://localhost/backend
app_url = http://localhost/backend
auth_token = (set after login)
```

---

**Total Endpoints: 100+**
**Modules: 17**
**Ready for Production: ✅**
