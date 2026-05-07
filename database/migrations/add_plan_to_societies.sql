-- Migration: Add plan column to societies table
-- Description: Adds subscription plan field with enum values: starter, professional, enterprise

USE migate;

-- Add plan column to societies table
ALTER TABLE societies 
ADD COLUMN plan ENUM('starter', 'professional', 'enterprise') DEFAULT 'starter' AFTER contact_email;

-- Add index for better query performance
ALTER TABLE societies 
ADD INDEX idx_plan (plan);

-- Update existing societies to 'starter' plan (or choose appropriate default)
-- UPDATE societies SET plan = 'starter' WHERE plan IS NULL;
