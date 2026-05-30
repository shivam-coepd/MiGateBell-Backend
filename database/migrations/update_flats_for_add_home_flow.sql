-- Migration script to update flats table for Add Home flow
-- Adds columns for user role, occupancy status, document URL and verification status

ALTER TABLE flats 
ADD COLUMN user_role ENUM('owner', 'renting_family', 'renting_flatmates') NOT NULL,
ADD COLUMN occupancy_status ENUM('residing', 'let_out', 'empty') NULL,
ADD COLUMN document_url VARCHAR(255) NULL,
ADD COLUMN verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending';