-- Migration script to improve authentication system
-- 1. Replace is_active with status ENUM
-- 2. Add social login fields
-- 3. Add OTP table

-- Modify users table
ALTER TABLE users 
DROP COLUMN is_active,
ADD COLUMN status ENUM('active','inactive','blocked','pending_verification') DEFAULT 'pending_verification' AFTER profile_image,
ADD COLUMN google_id VARCHAR(255) UNIQUE AFTER status,
ADD COLUMN facebook_id VARCHAR(255) UNIQUE AFTER google_id;

-- Create user_otps table
CREATE TABLE user_otps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    otp VARCHAR(6),
    expires_at DATETIME,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX(user_id, otp)
);