-- Migration 002: Society Registrations + Societies table updates
-- Run this on the production database ONCE

-- ─────────────────────────────────────────────────────────────────────────────
-- 1. Add missing columns to `societies` table
-- ─────────────────────────────────────────────────────────────────────────────
ALTER TABLE `societies`
  ADD COLUMN IF NOT EXISTS `code`            varchar(20)  DEFAULT NULL  COMMENT 'Unique society code e.g. FERN421' AFTER `name`,
  ADD COLUMN IF NOT EXISTS `towers`          int(11)      DEFAULT 1     AFTER `pincode`,
  ADD COLUMN IF NOT EXISTS `total_flats`     int(11)      DEFAULT 0     AFTER `towers`,
  ADD COLUMN IF NOT EXISTS `admin_id`        int(11)      DEFAULT NULL  AFTER `total_flats`,
  ADD COLUMN IF NOT EXISTS `gst`             varchar(20)  DEFAULT NULL  AFTER `admin_id`,
  ADD COLUMN IF NOT EXISTS `pan`             varchar(20)  DEFAULT NULL  AFTER `gst`,
  ADD COLUMN IF NOT EXISTS `registration_id` int(11)      DEFAULT NULL  COMMENT 'FK → society_registrations.id. One society per registration.' AFTER `pan`;

-- Unique constraint: prevents duplicate societies for the same registration at DB level
-- (safe to run even if constraint already exists — IF NOT EXISTS is MySQL 8.0.19+)
ALTER TABLE `societies`
  ADD CONSTRAINT IF NOT EXISTS `uq_societies_registration_id` UNIQUE (`registration_id`);

-- ─────────────────────────────────────────────────────────────────────────────
-- 2. Update society_registrations status enum to include 'approved'
-- ─────────────────────────────────────────────────────────────────────────────
ALTER TABLE `society_registrations`
  MODIFY COLUMN `status` enum('pending','new','under_review','approved','rejected') DEFAULT 'new';

-- ─────────────────────────────────────────────────────────────────────────────
-- 3. Create `society_registrations` table if it doesn't exist
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `society_registrations` (
  `id`               int(11)      NOT NULL AUTO_INCREMENT,
  `society_name`     varchar(150) NOT NULL,
  `address`          text         DEFAULT NULL,
  `city`             varchar(100) NOT NULL,
  `state`            varchar(100) DEFAULT NULL,
  `country`          varchar(100) DEFAULT 'India',
  `pincode`          varchar(10)  DEFAULT NULL,
  `towers`           int(11)      DEFAULT 1,
  `total_flats`      int(11)      DEFAULT 0,
  `contact_name`     varchar(100) NOT NULL,
  `contact_email`    varchar(100) NOT NULL,
  `contact_phone`    varchar(20)  NOT NULL,
  `gst`              varchar(20)  DEFAULT NULL,
  `pan`              varchar(20)  DEFAULT NULL,
  `message`          text         DEFAULT NULL,
  `status`           enum('pending','new','under_review','approved','rejected') DEFAULT 'new',
  `reviewed_by`      int(11)      DEFAULT NULL,
  `reviewed_at`      timestamp    NULL DEFAULT NULL,
  `rejection_reason` text         DEFAULT NULL,
  `created_at`       timestamp    NULL DEFAULT current_timestamp(),
  `updated_at`       timestamp    NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
