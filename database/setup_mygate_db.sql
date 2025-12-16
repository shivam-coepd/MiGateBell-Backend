-- MyGate Database Setup Script for XAMPP
-- Run this script in phpMyAdmin or MySQL command line

-- Create the database
CREATE DATABASE IF NOT EXISTS migate;
USE migate;

-- Create a sample user for testing
CREATE USER IF NOT EXISTS 'mygate_user'@'localhost' IDENTIFIED BY 'mygate_pass';
GRANT ALL PRIVILEGES ON migate.* TO 'mygate_user'@'localhost';
FLUSH PRIVILEGES;

-- Import the complete schema
-- Note: In practice, you would run the migate.sql file separately
-- This is just to confirm the database is set up correctly

-- Create a simple test table to verify setup
CREATE TABLE IF NOT EXISTS api_test (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert a test record
INSERT INTO api_test (message) VALUES ('MyGate API Database Setup Successful');

-- Apply the migration to add society_id column to users table
-- This fixes the registration error where society_id column was missing
ALTER TABLE users ADD COLUMN society_id INT AFTER role;
ALTER TABLE users ADD CONSTRAINT fk_users_society_id FOREIGN KEY (society_id) REFERENCES societies(id);

-- Display success message
SELECT 'Database setup completed successfully!' as result;