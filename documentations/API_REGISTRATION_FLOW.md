# Registration Flow API Endpoints

This document describes the new API endpoints for the enhanced registration flow with live search and nested dropdowns.

## 1. Live Society Search

Search for societies by name, address, or city with partial matching.

### Endpoint
```
GET /api/societies/search
```

### Query Parameters
| Parameter | Type   | Required | Description                            |
|-----------|--------|----------|----------------------------------------|
| q         | string | Yes      | Search term (partial match)            |
| limit     | int    | No       | Maximum results (default: 10, max: 50) |

### Response
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
    },
    {
      "id": 2,
      "name": "Sunset Gardens",
      "address": "456 Oak Avenue",
      "city": "New York",
      "state": "NY",
      "country": "USA"
    }
  ]
}
```

## 2. Create Building

Create a new building for a society (Admin/Super Admin only).

### Endpoint
```
POST /api/buildings
```

### Request Body
```json
{
  "name": "Building A",
  "society_id": 1,
  "total_floors": 10,
  "description": "Main residential building"
}
```

### Response
```json
{
  "status": true,
  "message": "Building created successfully",
  "data": {
    "building_id": 1
  }
}
```

## 3. Get Buildings by Society

Retrieve all buildings for a specific society.

### Endpoint
```
GET /api/buildings/by-society/{society_id}
```

### Response
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
    },
    {
      "id": 2,
      "name": "Building B",
      "total_floors": 5,
      "description": "Service apartments"
    }
  ]
}
```

## 4. Get Flats by Building

Retrieve all available (non-occupied) flats for a specific building.

### Endpoint
```
GET /api/flats/by-building/{building_id}
```

### Response
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
      },
      {
        "id": 2,
        "flat_number": "A102",
        "floor_number": "1",
        "area_sqft": 1100
      }
    ]
  }
}
```

## Performance Optimizations

1. **Database Indexes**: Added indexes on frequently queried columns:
   - `idx_buildings_society` on `buildings(society_id)`
   - `idx_flats_building` on `flats(building_id)`
   - `idx_flats_society` on `flats(society_id)`

2. **Efficient Queries**: All endpoints use optimized SQL queries with proper LIMIT clauses to prevent excessive data transfer.

3. **Caching-Friendly**: Responses are designed to be cacheable where appropriate.

4. **Unauthenticated Access**: Search and lookup endpoints allow unauthenticated access for better user experience during registration, while still supporting authenticated access for additional features.

## Implementation Notes

1. **Database Schema Changes**:
   - Added `buildings` table with foreign key relationship to `societies`
   - Modified `flats` table to reference `buildings` instead of storing building name directly
   - Added proper indexing for performance

2. **Migration**: A migration script (`database/migrations/add_buildings_table.sql`) is provided to update existing databases.

3. **Backward Compatibility**: Existing functionality remains unchanged while adding new features.

4. **Security**: All endpoints properly validate permissions where required and sanitize inputs.