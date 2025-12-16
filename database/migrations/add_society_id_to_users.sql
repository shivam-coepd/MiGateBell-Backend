-- Migration script to add society_id column to users table
-- This fixes the registration error where society_id column was missing

ALTER TABLE users ADD COLUMN society_id INT AFTER role;
ALTER TABLE users ADD CONSTRAINT fk_users_society_id FOREIGN KEY (society_id) REFERENCES societies(id);