# MiGate Backend API Documentation

## Base URL

- **Localhost**: `http://localhost/backend/api`
- **Production**: `https://your-domain.com/api`

## Authentication

Most endpoints require JWT authentication. Include the token in the Authorization header:

```
Authorization: Bearer <your_jwt_token>
```

---

## 1. Authentication Module

### Register User

- **POST** `/auth/register`
- **Body**: `{ name, phone, password, role, society_id }`
- **Roles**: `resident`, `guard`, `staff`, `admin`, `super_admin`

### Login

- **POST** `/auth/login`
- **Body**: `{ phone, password }`

### Refresh Token

- **POST** `/auth/refresh`
- **Headers**: Authorization Bearer token

### Change Password

- **POST** `/auth/change-password`
- **Body**: `{ current_password, new_password }`

### Logout

- **POST** `/auth/logout`

### Update User Status

- **PUT** `/auth/users/{userId}/status`
- **Body**: `{ status }` (active, inactive, blocked, pending_verification)
- **Access**: Admin, Super Admin

---

## 2. User Profile Module

### Get Own Profile

- **GET** `/users/profile`
- **Returns**: User details + role-specific data (flats, vehicles, pets for residents)

### Update Own Profile

- **PUT** `/users/profile`
- **Body**: `{ name, email, profile_image }`

---

## 3. User Management (Admin)

### List All Users

- **GET** `/admin/users?page=1&limit=20&role=resident&status=active&search=john`
- **Access**: Admin, Super Admin
- **Returns**: Paginated users with nested counts (flats, vehicles)

### Get User Details

- **GET** `/admin/users/{userId}`
- **Access**: Admin, Super Admin
- **Returns**: Full user profile with flats, vehicles, family members, recent visitors

### Create User

- **POST** `/admin/users`
- **Body**: `{ name, phone, email, password, role, society_id, status }`
- **Access**: Admin, Super Admin

### Update User

- **PUT** `/admin/users/{userId}`
- **Body**: `{ name, email, status, profile_image }`
- **Access**: Admin, Super Admin

### Delete User

- **DELETE** `/admin/users/{userId}`
- **Access**: Admin, Super Admin

---

## 4. Family Members Module

### Get Family Members

- **GET** `/family?resident_id={id}` (admin only)
- **Access**: Resident (own), Admin (any)

### Add Family Member

- **POST** `/family`
- **Body**: `{ name, relation, phone, image_url, resident_id }` (resident_id for admin)

### Delete Family Member

- **DELETE** `/family/{memberId}`

---

## 5. Visitors Module

### Add Visitor

- **POST** `/visitors`
- **Body**: `{ name, phone, email, purpose, visit_date, visit_time, visitor_type, resident_id }`
- **Access**: Resident, Guard, Admin

### Get Visitors

- **GET** `/visitors?page=1&status=pending`
- **Access**: All authenticated (filtered by role)

### Get Visitor Details

- **GET** `/visitors/{visitorId}`

### Update Visitor Status

- **PUT** `/visitors/{visitorId}/status`
- **Body**: `{ status }` (pending, approved, rejected, entered, exited)
- **Access**: Guard, Admin

### Delete Visitor

- **DELETE** `/visitors/{visitorId}`
- **Access**: Admin

---

## 6. Society & Building Management

### Create Society

- **POST** `/admin/societies`
- **Body**: `{ name, address, city, state, country, pincode, contact_person, contact_phone, contact_email }`
- **Access**: Super Admin

### Get Societies

- **GET** `/admin/societies?page=1&limit=10`
- **Access**: Super Admin

### Search Societies

- **GET** `/societies/search?q=society_name`
- **Access**: Public (for registration)

### Get Society Details

- **GET** `/admin/societies/{societyId}`

### Update Society

- **PUT** `/admin/societies/{societyId}`
- **Access**: Admin, Super Admin

### Delete Society

- **DELETE** `/admin/societies/{societyId}`
- **Access**: Super Admin

### Create Building

- **POST** `/buildings`
- **Body**: `{ name, society_id, total_floors, description }`
- **Access**: Admin, Super Admin

### Get Buildings by Society

- **GET** `/buildings/by-society/{societyId}`

### Create Flats

- **POST** `/flats`
- **Body**: `{ building_id, flats: [{flat_number, floor_number, area_sqft}] }`
- **Access**: Admin, Super Admin

### Get Flats by Building

- **GET** `/flats/by-building/{buildingId}`

---

## 7. Accounting Module

### Create Charge Head

- **POST** `/accounting/charge-heads`
- **Body**: `{ name, description, charge_type, amount, gst_rate }`
- **Access**: Admin

### Get Charge Heads

- **GET** `/accounting/charge-heads?page=1&is_active=1`

### Create Invoice

- **POST** `/accounting/invoices`
- **Body**: `{ flat_id, resident_id, invoice_date, due_date, items: [{charge_head_id, quantity, unit_price, gst_rate}] }`
- **Access**: Admin

### Get Invoices

- **GET** `/accounting/invoices?page=1&status=pending`
- **Access**: All (filtered by role)

### Get Invoice Details

- **GET** `/accounting/invoices/{invoiceId}`
- **Returns**: Invoice with items

### Update Invoice Status

- **PUT** `/accounting/invoices/{invoiceId}/status`
- **Body**: `{ status }` (draft, sent, paid, overdue, cancelled)
- **Access**: Admin

### Process Payment

- **POST** `/accounting/payments`
- **Body**: `{ invoice_id, amount, payment_method, transaction_id }`
- **Returns**: Payment and receipt details

### Update Payment Status

- **PUT** `/accounting/payments/{paymentId}/status`
- **Body**: `{ transaction_status }` (pending, success, failed, refunded)
- **Access**: Admin

---

## 8. Amenities Module

### Create Amenity

- **POST** `/amenities`
- **Body**: `{ name, description, capacity, booking_fee, cancellation_fee }`
- **Access**: Admin

### Get Amenities

- **GET** `/amenities?page=1&is_active=1`

### Book Amenity

- **POST** `/amenities/{amenityId}/book`
- **Body**: `{ booking_date, start_time, end_time }`
- **Access**: Resident

### Get Bookings

- **GET** `/amenities/bookings?page=1&status=confirmed`
- **Access**: All (filtered by role)

### Update Booking Status

- **PUT** `/amenities/bookings/{bookingId}/status`
- **Body**: `{ status }` (requested, confirmed, cancelled, completed)
- **Access**: Admin

---

## 9. Helpdesk Module

### Create Ticket

- **POST** `/helpdesk/tickets`
- **Body**: `{ title, description, category, priority }`
- **Access**: Resident

### Get Tickets

- **GET** `/helpdesk/tickets?page=1&status=open&category=maintenance&priority=high`
- **Access**: All (filtered by role)

### Get Ticket Details

- **GET** `/helpdesk/tickets/{ticketId}`
- **Returns**: Ticket with comments

### Update Ticket Status

- **PUT** `/helpdesk/tickets/{ticketId}/status`
- **Body**: `{ status }` (open, in_progress, resolved, closed)
- **Access**: Resident (own), Admin, Staff

### Assign Ticket

- **PUT** `/helpdesk/tickets/{ticketId}/assign`
- **Body**: `{ assigned_to }` (user_id)
- **Access**: Admin, Staff

### Add Comment

- **POST** `/helpdesk/tickets/{ticketId}/comments`
- **Body**: `{ comment }`

---

## 10. Communications Module

### Create Group

- **POST** `/communications/groups`
- **Body**: `{ name, description }`
- **Access**: Admin

### Get Groups

- **GET** `/communications/groups`

### Join Group

- **POST** `/communications/groups/{groupId}/join`

### Leave Group

- **POST** `/communications/groups/{groupId}/leave`

### Create Announcement

- **POST** `/communications/announcements`
- **Body**: `{ title, content, target_group_id, send_via }`
- **Access**: Admin

### Get Announcements

- **GET** `/communications/announcements?page=1`

### Create Poll

- **POST** `/communications/polls`
- **Body**: `{ question, poll_type, options: [{option_text}], ends_at }`
- **Access**: Admin

### Get Polls

- **GET** `/communications/polls?page=1`

### Vote on Poll

- **POST** `/communications/polls/{pollId}/vote`
- **Body**: `{ option_id }`

---

## 11. Security Module

### Report Alert

- **POST** `/security/alerts`
- **Body**: `{ alert_type, description, severity, location, image_url }`
- **Access**: Resident, Guard, Admin

### Get Alerts

- **GET** `/security/alerts?page=1&status=open&severity=high`

### Get Alert Details

- **GET** `/security/alerts/{alertId}`

### Update Alert Status

- **PUT** `/security/alerts/{alertId}/status`
- **Body**: `{ status }` (open, in_progress, resolved, closed)
- **Access**: Guard, Admin

### Get Emergency Contacts

- **GET** `/security/emergency-contacts?contact_type=police`

### Add Emergency Contact

- **POST** `/security/emergency-contacts`
- **Body**: `{ name, phone, email, contact_type }`
- **Access**: Admin

---

## 12. Vehicles Module

### Add Vehicle

- **POST** `/vehicles`
- **Body**: `{ vehicle_type_id, make, model, color, registration_number, parking_spot }`

### Get Vehicles

- **GET** `/vehicles?page=1`

### Get Vehicle Details

- **GET** `/vehicles/{vehicleId}`

### Update Vehicle

- **PUT** `/vehicles/{vehicleId}`
- **Body**: `{ make, model, color, parking_spot }`

### Delete Vehicle

- **DELETE** `/vehicles/{vehicleId}`

### Get Parking Spots

- **GET** `/vehicles/parking-spots?spot_type=resident`

### Assign Parking Spot

- **POST** `/vehicles/parking-spots/{spotId}/assign`
- **Body**: `{ vehicle_id }`

### Release Parking Spot

- **POST** `/vehicles/parking-spots/{spotId}/release`

---

## 13. Marketplace Module

### Get Categories

- **GET** `/marketplace/categories`

### Get Products

- **GET** `/marketplace/products?category_id=1&search=keyword`

### Add Product

- **POST** `/marketplace/products`
- **Body**: `{ name, description, category_id, price, image_urls }`

### Create Order

- **POST** `/marketplace/orders`
- **Body**: `{ items: [{product_id, quantity}], address }`

### Get My Orders

- **GET** `/marketplace/orders/my`

---

## 14. Services Module

### Get Service Categories

- **GET** `/services/categories`

### Get Services

- **GET** `/services?category_id=1`

### Book Service

- **POST** `/services/book`
- **Body**: `{ service_id, booking_date, booking_time, notes }`

### Get My Bookings

- **GET** `/services/bookings/my`

---

## 15. Pets Module

### Get Pet Types

- **GET** `/pets/types`

### Add Pet

- **POST** `/pets`
- **Body**: `{ name, pet_type_id, breed, age, weight, vaccination_status }`

### Get Pets

- **GET** `/pets`
- **Access**: Resident (own), Admin/Guard (all in society)

### Delete Pet

- **DELETE** `/pets/{petId}`

---

## 16. Assets & Inventory Module

### Get Asset Categories

- **GET** `/assets/categories`

### Get Assets

- **GET** `/assets`
- **Access**: Admin, Super Admin, Staff

### Add Asset

- **POST** `/assets`
- **Body**: `{ name, category_id, serial_number, purchase_date, purchase_cost, location }`
- **Access**: Admin, Super Admin

### Get Inventory

- **GET** `/assets/inventory`
- **Access**: Admin, Super Admin, Staff

### Add Inventory Item

- **POST** `/assets/inventory`
- **Body**: `{ name, category, unit, quantity_in_stock, reorder_level }`
- **Access**: Admin, Super Admin

---

## 17. Notifications Module

### Get Notifications

- **GET** `/notifications?page=1&limit=20`
- **Returns**: Notifications with unread count

### Mark as Read

- **PUT** `/notifications/{notificationId}/read`

### Mark All as Read

- **PUT** `/notifications/read-all`

---

## Response Format

### Success Response

```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Error description",
  "errors": ["Error 1", "Error 2"]
}
```

### Paginated Response

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

- **200**: Success
- **201**: Created
- **400**: Bad Request
- **401**: Unauthorized
- **403**: Forbidden
- **404**: Not Found
- **409**: Conflict
- **500**: Internal Server Error
