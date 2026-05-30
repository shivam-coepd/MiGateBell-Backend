-- Migration script to add buildings table and modify flats table
-- This migration adds a dedicated buildings table for better organization

-- Create buildings table
CREATE TABLE buildings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    society_id INT,
    total_floors INT DEFAULT 1,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (society_id) REFERENCES societies(id) ON DELETE CASCADE
);

-- Modify flats table to reference buildings
ALTER TABLE flats 
DROP COLUMN building_name,
ADD COLUMN building_id INT AFTER floor_number,
ADD FOREIGN KEY (building_id) REFERENCES buildings(id) ON DELETE SET NULL;

-- Add indexes for better performance
CREATE INDEX idx_buildings_society ON buildings(society_id);
CREATE INDEX idx_flats_building ON flats(building_id);
CREATE INDEX idx_flats_society ON flats(society_id);