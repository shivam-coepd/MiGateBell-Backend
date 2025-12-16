# MiGate Backend API Documentation

## Overview
This document provides comprehensive documentation for the MiGate backend API, covering all modules and functionalities based on the MiGate offerings.

## Base URL
```
https://your-domain.com/api
```

## Authentication
Most API endpoints require authentication using JWT tokens. Include the token in the Authorization header:

```
Authorization: Bearer <your-jwt-token>
```

## Error Responses
All error responses follow this format:
```json
{
  "status": false,
  "message": "Error description",
  "data": null
}
```

## Modules

### 1. Authentication

#### Register
```
POST /auth/register
```
**Request Body:**
```json
{
  "name": "John Doe",
  "phone": "9876543210",
  "password": "securepassword",
  "society_id": 1,
  "role": "resident"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Registration successful",
  "data": {
    "user_id": 1,
    "token": "jwt-token"
  }
}
```

#### Login
```
POST /auth/login
```
**Request Body:**
```json
{
  "phone": "9876543210",
  "password": "securepassword"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "phone": "9876543210",
      "role": "resident",
      "society_id": 1
    },
    "token": "jwt-token"
  }
}
```

#### Refresh Token
```
POST /auth/refresh
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Token refreshed",
  "data": {
    "token": "new-jwt-token"
  }
}
```

#### Change Password
```
POST /auth/change-password
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Request Body:**
```json
{
  "current_password": "oldpassword",
  "new_password": "newpassword"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Password changed successfully",
  "data": {}
}
```

#### Forgot Password
```
POST /auth/forgot-password
```
**Request Body:**
```json
{
  "phone": "9876543210"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Password reset instructions sent to your phone",
  "data": {}
}
```

#### Logout
```
POST /auth/logout
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Logged out successfully",
  "data": {}
}
```

### Registration Flow APIs

These endpoints support the mobile app registration flow with live search and nested dropdowns.

#### Search Societies
```
GET /societies/search
```
**Query Parameters:**
- `q` (string, required): Search term for society name, address, or city
- `limit` (integer, optional): Maximum number of results (default: 10, max: 50)

**Response:**
```json
{
  "status": true,
  "message": "Societies retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Green Valley Apartments",
      "address": "123 Main Street",
      "city": "New York",
      "state": "NY",
      "country": "USA"
    }
  ]
}
```

#### Get Buildings by Society
```
GET /buildings/by-society/{society_id}
```
**Path Parameters:**
- `society_id` (integer, required): ID of the society

**Response:**
```json
{
  "status": true,
  "message": "Buildings retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Building A",
      "total_floors": 10,
      "description": "Main residential building"
    }
  ]
}
```

#### Get Flats by Building
```
GET /flats/by-building/{building_id}
```
**Path Parameters:**
- `building_id` (integer, required): ID of the building

**Response:**
```json
{
  "status": true,
  "message": "Flats retrieved successfully",
  "data": {
    "building": {
      "id": 1,
      "name": "Building A",
      "society_id": 1,
      "society_name": "Green Valley Apartments"
    },
    "flats": [
      {
        "id": 1,
        "flat_number": "A101",
        "floor_number": "1",
        "area_sqft": 1200
      }
    ]
  }
}
```

#### Create Building
```
POST /buildings
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Request Body:**
```json
{
  "name": "Building A",
  "society_id": 1,
  "total_floors": 10,
  "description": "Main residential building"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Building created successfully",
  "data": {
    "building_id": 1
  }
}
```

### 2. Visitors Management

#### Add Visitor
```
POST /visitors
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Request Body:**
```json
{
  "name": "Visitor Name",
  "phone": "9876543210",
  "email": "visitor@example.com",
  "purpose": "Meeting",
  "visit_date": "2023-06-15",
  "visit_time": "10:00:00",
  "expected_exit_time": "12:00:00",
  "visitor_type": "guest",
  "resident_id": 1,
  "image_url": "https://example.com/image.jpg"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Visitor added successfully",
  "data": {
    "visitor_id": 1
  }
}
```

#### Get Visitors
```
GET /visitors?page=1&limit=10&status=pending
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Visitors retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "name": "Visitor Name",
        "phone": "9876543210",
        "email": "visitor@example.com",
        "purpose": "Meeting",
        "visit_date": "2023-06-15",
        "visit_time": "10:00:00",
        "expected_exit_time": "12:00:00",
        "actual_exit_time": null,
        "status": "pending",
        "visitor_type": "guest",
        "resident_id": 1,
        "guard_id": null,
        "society_id": 1,
        "image_url": "https://example.com/image.jpg",
        "qr_code": null,
        "created_at": "2023-06-15 10:00:00",
        "updated_at": "2023-06-15 10:00:00",
        "resident_name": "John Doe",
        "guard_name": null
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 1,
      "pages": 1
    }
  }
}
```

#### Get Visitor By ID
```
GET /visitors/{id}
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Visitor retrieved successfully",
  "data": {
    "id": 1,
    "name": "Visitor Name",
    "phone": "9876543210",
    "email": "visitor@example.com",
    "purpose": "Meeting",
    "visit_date": "2023-06-15",
    "visit_time": "10:00:00",
    "expected_exit_time": "12:00:00",
    "actual_exit_time": null,
    "status": "pending",
    "visitor_type": "guest",
    "resident_id": 1,
    "guard_id": null,
    "society_id": 1,
    "image_url": "https://example.com/image.jpg",
    "qr_code": null,
    "created_at": "2023-06-15 10:00:00",
    "updated_at": "2023-06-15 10:00:00",
    "resident_name": "John Doe",
    "guard_name": null
  }
}
```

#### Update Visitor Status
```
PUT /visitors/{id}/status
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Request Body:**
```json
{
  "status": "approved"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Visitor status updated successfully",
  "data": {}
}
```

#### Delete Visitor
```
DELETE /visitors/{id}
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Visitor deleted successfully",
  "data": {}
}
```

### 3. Admin Management

#### Create Society
```
POST /admin/societies
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Request Body:**
```json
{
  "name": "Society Name",
  "address": "123 Main St",
  "city": "City",
  "state": "State",
  "country": "Country",
  "pincode": "123456",
  "contact_person": "Contact Person",
  "contact_phone": "9876543210",
  "contact_email": "contact@society.com"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Society created successfully",
  "data": {
    "society_id": 1
  }
}
```

#### Get Societies
```
GET /admin/societies?page=1&limit=10
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Societies retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "name": "Society Name",
        "address": "123 Main St",
        "city": "City",
        "state": "State",
        "country": "Country",
        "pincode": "123456",
        "contact_person": "Contact Person",
        "contact_phone": "9876543210",
        "contact_email": "contact@society.com",
        "created_at": "2023-06-15 10:00:00"
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 1,
      "pages": 1
    }
  }
}
```

#### Get Society By ID
```
GET /admin/societies/{id}
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Society retrieved successfully",
  "data": {
    "id": 1,
    "name": "Society Name",
    "address": "123 Main St",
    "city": "City",
    "state": "State",
    "country": "Country",
    "pincode": "123456",
    "contact_person": "Contact Person",
    "contact_phone": "9876543210",
    "contact_email": "contact@society.com",
    "created_at": "2023-06-15 10:00:00"
  }
}
```

#### Update Society
```
PUT /admin/societies/{id}
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Request Body:**
```json
{
  "name": "Updated Society Name",
  "address": "456 New St"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Society updated successfully",
  "data": {}
}
```

#### Delete Society
```
DELETE /admin/societies/{id}
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Society deleted successfully",
  "data": {}
}
```

#### Assign User Role
```
POST /admin/user-role
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Request Body:**
```json
{
  "user_id": 1,
  "role_id": 2
}
```

**Response:**
```json
{
  "status": true,
  "message": "Role assigned successfully",
  "data": {
    "assignment_id": 1
  }
}
```

### 4. Accounting

#### Create Charge Head
```
POST /accounting/charge-heads
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Request Body:**
```json
{
  "name": "Maintenance Fee",
  "description": "Monthly maintenance fee",
  "charge_type": "per_area",
  "amount": 10.00,
  "gst_rate": 18.00,
  "is_active": 1
}
```

**Response:**
```json
{
  "status": true,
  "message": "Charge head created successfully",
  "data": {
    "charge_head_id": 1
  }
}
```

#### Get Charge Heads
```
GET /accounting/charge-heads?page=1&limit=10&is_active=1
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Charge heads retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "name": "Maintenance Fee",
        "description": "Monthly maintenance fee",
        "charge_type": "per_area",
        "amount": 10.00,
        "slab_details": null,
        "gst_rate": 18.00,
        "is_active": 1,
        "society_id": 1,
        "created_at": "2023-06-15 10:00:00",
        "updated_at": "2023-06-15 10:00:00"
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 1,
      "pages": 1
    }
  }
}
```

#### Create Invoice
```
POST /accounting/invoices
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Request Body:**
```json
{
  "flat_id": 1,
  "resident_id": 1,
  "invoice_date": "2023-06-15",
  "due_date": "2023-07-15",
  "total_discount": 0,
  "arrears_amount": 0,
  "fine_amount": 0,
  "notes": "Monthly maintenance invoice",
  "items": [
    {
      "charge_head_id": 1,
      "description": "Maintenance Fee",
      "quantity": 1,
      "unit_price": 1000.00,
      "gst_rate": 18.00
    }
  ]
}
```

**Response:**
```json
{
  "status": true,
  "message": "Invoice created successfully",
  "data": {
    "invoice_id": 1
  }
}
```

#### Get Invoices
```
GET /accounting/invoices?page=1&limit=10&status=draft
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Invoices retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "invoice_number": "INV-1-000001",
        "flat_id": 1,
        "resident_id": 1,
        "society_id": 1,
        "invoice_date": "2023-06-15",
        "due_date": "2023-07-15",
        "total_amount": 1000.00,
        "total_gst": 180.00,
        "total_discount": 0,
        "arrears_amount": 0,
        "fine_amount": 0,
        "status": "draft",
        "notes": "Monthly maintenance invoice",
        "created_by": 1,
        "created_at": "2023-06-15 10:00:00",
        "updated_at": "2023-06-15 10:00:00",
        "flat_number": "A101",
        "resident_name": "John Doe"
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 1,
      "pages": 1
    }
  }
}
```

#### Get Invoice By ID
```
GET /accounting/invoices/{id}
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Invoice retrieved successfully",
  "data": {
    "id": 1,
    "invoice_number": "INV-1-000001",
    "flat_id": 1,
    "resident_id": 1,
    "society_id": 1,
    "invoice_date": "2023-06-15",
    "due_date": "2023-07-15",
    "total_amount": 1000.00,
    "total_gst": 180.00,
    "total_discount": 0,
    "arrears_amount": 0,
    "fine_amount": 0,
    "status": "draft",
    "notes": "Monthly maintenance invoice",
    "created_by": 1,
    "created_at": "2023-06-15 10:00:00",
    "updated_at": "2023-06-15 10:00:00",
    "flat_number": "A101",
    "resident_name": "John Doe",
    "created_by_name": "Admin User",
    "items": [
      {
        "id": 1,
        "invoice_id": 1,
        "charge_head_id": 1,
        "description": "Maintenance Fee",
        "quantity": 1,
        "unit_price": 1000.00,
        "gst_rate": 18.00,
        "gst_amount": 180.00,
        "total_amount": 1000.00,
        "charge_head_name": "Maintenance Fee"
      }
    ]
  }
}
```

#### Process Payment
```
POST /accounting/payments
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Request Body:**
```json
{
  "invoice_id": 1,
  "amount": 1180.00,
  "payment_method": "upi",
  "transaction_id": "UPI123456",
  "notes": "Payment for June maintenance"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Payment processed successfully",
  "data": {
    "payment_id": 1,
    "receipt_id": 1,
    "payment_reference": "PAY-1686823200-1234",
    "receipt_number": "REC-1-000001"
  }
}
```

### 5. Communications

#### Create Group
```
POST /communications/groups
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Request Body:**
```json
{
  "name": "Owners Group",
  "description": "Group for all apartment owners",
  "is_active": 1
}
```

**Response:**
```json
{
  "status": true,
  "message": "Group created successfully",
  "data": {
    "group_id": 1
  }
}
```

#### Get Groups
```
GET /communications/groups?page=1&limit=10&is_active=1
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Groups retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "name": "Owners Group",
        "description": "Group for all apartment owners",
        "society_id": 1,
        "created_by": 1,
        "is_active": 1,
        "created_at": "2023-06-15 10:00:00",
        "updated_at": "2023-06-15 10:00:00",
        "member_count": 10
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 1,
      "pages": 1
    }
  }
}
```

#### Join Group
```
POST /communications/groups/{id}/join
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Joined group successfully",
  "data": {
    "membership_id": 1
  }
}
```

#### Leave Group
```
POST /communications/groups/{id}/leave
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Left group successfully",
  "data": {}
}
```

#### Create Announcement
```
POST /communications/announcements
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Request Body:**
```json
{
  "title": "Maintenance Notice",
  "content": "Scheduled maintenance on June 20th",
  "target_group_id": 1,
  "send_via": "app",
  "is_draft": 0
}
```

**Response:**
```json
{
  "status": true,
  "message": "Announcement created successfully",
  "data": {
    "announcement_id": 1
  }
}
```

#### Get Announcements
```
GET /communications/announcements?page=1&limit=10&is_draft=0
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Announcements retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "title": "Maintenance Notice",
        "content": "Scheduled maintenance on June 20th",
        "society_id": 1,
        "created_by": 1,
        "target_group_id": 1,
        "send_via": "app",
        "scheduled_at": null,
        "sent_at": null,
        "is_draft": 0,
        "created_at": "2023-06-15 10:00:00",
        "updated_at": "2023-06-15 10:00:00",
        "created_by_name": "Admin User",
        "target_group_name": "Owners Group"
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 1,
      "pages": 1
    }
  }
}
```

#### Create Poll
```
POST /communications/polls
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Request Body:**
```json
{
  "question": "Should we install solar panels?",
  "poll_type": "public",
  "starts_at": "2023-06-15 10:00:00",
  "ends_at": "2023-06-20 10:00:00",
  "options": [
    "Yes",
    "No",
    "Abstain"
  ]
}
```

**Response:**
```json
{
  "status": true,
  "message": "Poll created successfully",
  "data": {
    "poll_id": 1
  }
}
```

#### Get Polls
```
GET /communications/polls?page=1&limit=10&is_active=1
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Polls retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "question": "Should we install solar panels?",
        "poll_type": "public",
        "society_id": 1,
        "created_by": 1,
        "starts_at": "2023-06-15 10:00:00",
        "ends_at": "2023-06-20 10:00:00",
        "is_active": 1,
        "created_at": "2023-06-15 10:00:00",
        "updated_at": "2023-06-15 10:00:00",
        "created_by_name": "Admin User",
        "options": [
          {
            "id": 1,
            "poll_id": 1,
            "option_text": "Yes",
            "vote_count": 5
          },
          {
            "id": 2,
            "poll_id": 1,
            "option_text": "No",
            "vote_count": 3
          },
          {
            "id": 3,
            "poll_id": 1,
            "option_text": "Abstain",
            "vote_count": 2
          }
        ],
        "has_voted": true
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 1,
      "pages": 1
    }
  }
}
```

#### Vote On Poll
```
POST /communications/polls/{id}/vote
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Request Body:**
```json
{
  "option_id": 1
}
```

**Response:**
```json
{
  "status": true,
  "message": "Vote recorded successfully",
  "data": {
    "vote_id": 1
  }
}
```

### 6. Amenities

#### Create Amenity
```
POST /amenities
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Request Body:**
```json
{
  "name": "Swimming Pool",
  "description": "Community swimming pool",
  "capacity": 20,
  "booking_fee": 100.00,
  "cancellation_fee": 50.00,
  "is_active": 1
}
```

**Response:**
```json
{
  "status": true,
  "message": "Amenity created successfully",
  "data": {
    "amenity_id": 1
  }
}
```

#### Get Amenities
```
GET /amenities?page=1&limit=10&is_active=1
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Amenities retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "name": "Swimming Pool",
        "description": "Community swimming pool",
        "image_url": null,
        "capacity": 20,
        "booking_fee": 100.00,
        "cancellation_fee": 50.00,
        "cancellation_policy": null,
        "society_id": 1,
        "is_active": 1,
        "created_at": "2023-06-15 10:00:00",
        "updated_at": "2023-06-15 10:00:00"
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 1,
      "pages": 1
    }
  }
}
```

#### Book Amenity
```
POST /amenities/{id}/book
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Request Body:**
```json
{
  "booking_date": "2023-06-20",
  "start_time": "10:00:00",
  "end_time": "12:00:00"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Amenity booking requested successfully",
  "data": {
    "booking_id": 1
  }
}
```

#### Get Bookings
```
GET /amenities/bookings?page=1&limit=10&status=requested
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Bookings retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "amenity_id": 1,
        "resident_id": 1,
        "booking_date": "2023-06-20",
        "start_time": "10:00:00",
        "end_time": "12:00:00",
        "status": "requested",
        "total_amount": 100.00,
        "payment_status": "pending",
        "notes": null,
        "created_at": "2023-06-15 10:00:00",
        "updated_at": "2023-06-15 10:00:00",
        "amenity_name": "Swimming Pool",
        "resident_name": "John Doe"
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 1,
      "pages": 1
    }
  }
}
```

#### Update Booking Status
```
PUT /amenities/bookings/{id}/status
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Request Body:**
```json
{
  "status": "confirmed"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Booking status updated successfully",
  "data": {}
}
```

### 7. Helpdesk

#### Create Ticket
```
POST /helpdesk/tickets
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Request Body:**
```json
{
  "title": "Water Leakage Issue",
  "description": "Water leaking from ceiling in living room",
  "category": "maintenance",
  "priority": "high"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Ticket created successfully",
  "data": {
    "ticket_id": 1
  }
}
```

#### Get Tickets
```
GET /helpdesk/tickets?page=1&limit=10&status=open&category=maintenance&priority=high
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Tickets retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "ticket_number": "TK-1-000001",
        "title": "Water Leakage Issue",
        "description": "Water leaking from ceiling in living room",
        "category": "maintenance",
        "priority": "high",
        "status": "open",
        "resident_id": 1,
        "assigned_to": null,
        "society_id": 1,
        "created_at": "2023-06-15 10:00:00",
        "updated_at": "2023-06-15 10:00:00",
        "resolved_at": null,
        "resident_name": "John Doe",
        "assigned_to_name": null
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 1,
      "pages": 1
    }
  }
}
```

#### Get Ticket By ID
```
GET /helpdesk/tickets/{id}
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Ticket retrieved successfully",
  "data": {
    "id": 1,
    "ticket_number": "TK-1-000001",
    "title": "Water Leakage Issue",
    "description": "Water leaking from ceiling in living room",
    "category": "maintenance",
    "priority": "high",
    "status": "open",
    "resident_id": 1,
    "assigned_to": null,
    "society_id": 1,
    "created_at": "2023-06-15 10:00:00",
    "updated_at": "2023-06-15 10:00:00",
    "resolved_at": null,
    "resident_name": "John Doe",
    "assigned_to_name": null,
    "comments": [
      {
        "id": 1,
        "ticket_id": 1,
        "user_id": 1,
        "comment": "We will look into this issue today.",
        "created_at": "2023-06-15 11:00:00",
        "commenter_name": "Admin User"
      }
    ]
  }
}
```

#### Update Ticket Status
```
PUT /helpdesk/tickets/{id}/status
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Request Body:**
```json
{
  "status": "in_progress"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Ticket status updated successfully",
  "data": {}
}
```

#### Assign Ticket
```
PUT /helpdesk/tickets/{id}/assign
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Request Body:**
```json
{
  "assigned_to": 2
}
```

**Response:**
```json
{
  "status": true,
  "message": "Ticket assigned successfully",
  "data": {}
}
```

#### Add Comment
```
POST /helpdesk/tickets/{id}/comments
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Request Body:**
```json
{
  "comment": "Issue has been resolved."
}
```

**Response:**
```json
{
  "status": true,
  "message": "Comment added successfully",
  "data": {
    "comment_id": 1
  }
}
```

### 8. Security

#### Report Alert
```
POST /security/alerts
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Request Body:**
```json
{
  "alert_type": "suspicious_activity",
  "description": "Suspicious person seen near parking area",
  "severity": "medium",
  "location": "Near Gate 2",
  "image_url": "https://example.com/image.jpg"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Security alert reported successfully",
  "data": {
    "alert_id": 1
  }
}
```

#### Get Alerts
```
GET /security/alerts?page=1&limit=10&status=open&severity=medium&alert_type=suspicious_activity
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Security alerts retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "alert_type": "suspicious_activity",
        "description": "Suspicious person seen near parking area",
        "severity": "medium",
        "reported_by": 1,
        "society_id": 1,
        "resolved_by": null,
        "resolved_at": null,
        "status": "open",
        "image_url": "https://example.com/image.jpg",
        "location": "Near Gate 2",
        "created_at": "2023-06-15 10:00:00",
        "updated_at": "2023-06-15 10:00:00",
        "reported_by_name": "John Doe",
        "resolved_by_name": null
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 1,
      "pages": 1
    }
  }
}
```

#### Get Alert By ID
```
GET /security/alerts/{id}
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Security alert retrieved successfully",
  "data": {
    "id": 1,
    "alert_type": "suspicious_activity",
    "description": "Suspicious person seen near parking area",
    "severity": "medium",
    "reported_by": 1,
    "society_id": 1,
    "resolved_by": null,
    "resolved_at": null,
    "status": "open",
    "image_url": "https://example.com/image.jpg",
    "location": "Near Gate 2",
    "created_at": "2023-06-15 10:00:00",
    "updated_at": "2023-06-15 10:00:00",
    "reported_by_name": "John Doe",
    "resolved_by_name": null
  }
}
```

#### Update Alert Status
```
PUT /security/alerts/{id}/status
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Request Body:**
```json
{
  "status": "resolved"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Alert status updated successfully",
  "data": {}
}
```

#### Get Emergency Contacts
```
GET /security/emergency-contacts?page=1&limit=10&is_active=1&contact_type=police
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Emergency contacts retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "name": "Local Police Station",
        "phone": "100",
        "email": "police@local.gov",
        "contact_type": "police",
        "society_id": 1,
        "is_active": 1,
        "created_at": "2023-06-15 10:00:00",
        "updated_at": "2023-06-15 10:00:00"
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 1,
      "pages": 1
    }
  }
}
```

#### Add Emergency Contact
```
POST /security/emergency-contacts
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Request Body:**
```json
{
  "name": "Local Hospital",
  "phone": "102",
  "email": "hospital@local.gov",
  "contact_type": "hospital",
  "is_active": 1
}
```

**Response:**
```json
{
  "status": true,
  "message": "Emergency contact added successfully",
  "data": {
    "contact_id": 1
  }
}
```

### 9. Vehicles

#### Add Vehicle
```
POST /vehicles
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Request Body:**
```json
{
  "registration_number": "KA01AB1234",
  "vehicle_type_id": 1,
  "make": "Toyota",
  "model": "Camry",
  "color": "White",
  "parking_spot": "P101"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Vehicle added successfully",
  "data": {
    "vehicle_id": 1
  }
}
```

#### Get Vehicles
```
GET /vehicles?page=1&limit=10&is_parked=1
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Vehicles retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "resident_id": 1,
        "vehicle_type_id": 1,
        "make": "Toyota",
        "model": "Camry",
        "color": "White",
        "registration_number": "KA01AB1234",
        "parking_spot": "P101",
        "is_parked": 1,
        "society_id": 1,
        "created_at": "2023-06-15 10:00:00",
        "updated_at": "2023-06-15 10:00:00",
        "vehicle_type_name": "Car",
        "resident_name": "John Doe",
        "parking_spot_number": "P101"
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 1,
      "pages": 1
    }
  }
}
```

#### Get Vehicle By ID
```
GET /vehicles/{id}
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Vehicle retrieved successfully",
  "data": {
    "id": 1,
    "resident_id": 1,
    "vehicle_type_id": 1,
    "make": "Toyota",
    "model": "Camry",
    "color": "White",
    "registration_number": "KA01AB1234",
    "parking_spot": "P101",
    "is_parked": 1,
    "society_id": 1,
    "created_at": "2023-06-15 10:00:00",
    "updated_at": "2023-06-15 10:00:00",
    "vehicle_type_name": "Car",
    "resident_name": "John Doe",
    "parking_spot_number": "P101"
  }
}
```

#### Update Vehicle
```
PUT /vehicles/{id}
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Request Body:**
```json
{
  "color": "Black",
  "parking_spot": "P102"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Vehicle updated successfully",
  "data": {}
}
```

#### Delete Vehicle
```
DELETE /vehicles/{id}
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Vehicle deleted successfully",
  "data": {}
}
```

#### Get Parking Spots
```
GET /vehicles/parking-spots?page=1&limit=10&is_occupied=1&spot_type=resident
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Parking spots retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "spot_number": "P101",
        "spot_type": "resident",
        "is_occupied": 1,
        "vehicle_id": 1,
        "society_id": 1,
        "created_at": "2023-06-15 10:00:00",
        "updated_at": "2023-06-15 10:00:00",
        "registration_number": "KA01AB1234",
        "make": "Toyota",
        "model": "Camry",
        "resident_name": "John Doe"
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 1,
      "pages": 1
    }
  }
}
```

#### Assign Parking Spot
```
POST /vehicles/parking-spots/{id}/assign
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Request Body:**
```json
{
  "vehicle_id": 1
}
```

**Response:**
```json
{
  "status": true,
  "message": "Parking spot assigned successfully",
  "data": {}
}
```

#### Release Parking Spot
```
POST /vehicles/parking-spots/{id}/release
```
**Headers:**
```
Authorization: Bearer <your-jwt-token>
```

**Response:**
```json
{
  "status": true,
  "message": "Parking spot released successfully",
  "data": {}
}
```

## Rate Limiting
API requests are rate-limited to prevent abuse:
- 100 requests per minute per IP address
- 1000 requests per hour per authenticated user

Exceeding these limits will result in a 429 Too Many Requests response.

## CORS Policy
The API allows cross-origin requests from any domain to support mobile and web applications.

## SSL/TLS
All API communications must be encrypted using HTTPS.

## Versioning
The API is versioned through the URL path:
```
https://your-domain.com/api/v1/
```

## Pagination
All list endpoints support pagination with the following query parameters:
- `page`: Page number (default: 1)
- `limit`: Number of items per page (default: 10, max: 100)

## Filtering
Many endpoints support filtering through query parameters. See individual endpoint documentation for available filters.

## Sorting
Endpoints that return lists support sorting through the `sort` query parameter:
- `sort=created_at` (ascending)
- `sort=-created_at` (descending)

## Error Codes
- 200: Success
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 409: Conflict
- 422: Unprocessable Entity
- 429: Too Many Requests
- 500: Internal Server Error

## Support
For API support, contact the development team at api-support@migate.com.