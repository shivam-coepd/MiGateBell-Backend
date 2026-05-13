<?php
/**
 * One-time migration runner — DELETE THIS FILE after running.
 * Hit this URL once in your browser or via curl:
 *   https://yourdomain.com/run_migration.php
 */

// Simple secret key to prevent accidental public access
$secret = $_GET['secret'] ?? '';
if ($secret !== 'migrate_002_run') {
    http_response_code(403);
    die('Forbidden. Pass ?secret=migrate_002_run');
}

// Load DB config
require_once __DIR__ . '/app/core/Database.php';
$config = require __DIR__ . '/app/config/database.php';
$pdo = Database::connect($config);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$results = [];

$statements = [

    // ── societies table: add missing columns ──────────────────────────────

    "ALTER TABLE `societies`
        ADD COLUMN IF NOT EXISTS `code` varchar(20) DEFAULT NULL
        COMMENT 'Unique society code e.g. FERN421' AFTER `name`",

    "ALTER TABLE `societies`
        ADD COLUMN IF NOT EXISTS `towers` int(11) DEFAULT 1 AFTER `pincode`",

    "ALTER TABLE `societies`
        ADD COLUMN IF NOT EXISTS `total_flats` int(11) DEFAULT 0 AFTER `towers`",

    "ALTER TABLE `societies`
        ADD COLUMN IF NOT EXISTS `admin_id` int(11) DEFAULT NULL AFTER `total_flats`",

    "ALTER TABLE `societies`
        ADD COLUMN IF NOT EXISTS `gst` varchar(20) DEFAULT NULL AFTER `admin_id`",

    "ALTER TABLE `societies`
        ADD COLUMN IF NOT EXISTS `pan` varchar(20) DEFAULT NULL AFTER `gst`",

    "ALTER TABLE `societies`
        ADD COLUMN IF NOT EXISTS `registration_id` int(11) DEFAULT NULL
        COMMENT 'Source society_registrations.id — set when approved from a lead' AFTER `pan`",

    // Unique constraint — one society per registration, prevents duplicate approvals at DB level
    "ALTER TABLE `societies` ADD UNIQUE KEY `uq_societies_registration_id` (`registration_id`)",

    // Fix status enum to include 'approved' — use try/catch since MODIFY IF EXISTS isn't valid MySQL
    // This runs after CREATE TABLE so it handles both new and existing tables

    // ── society_registrations table: create if not exists ─────────────────

    "CREATE TABLE IF NOT EXISTS `society_registrations` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Ensure status enum includes 'approved' on existing tables
    "ALTER TABLE `society_registrations`
        MODIFY COLUMN `status` enum('pending','new','under_review','approved','rejected') DEFAULT 'new'",
];

foreach ($statements as $sql) {
    $label = trim(substr($sql, 0, 80)) . '...';
    try {
        $pdo->exec($sql);
        $results[] = ['sql' => $label, 'status' => 'OK'];
    } catch (PDOException $e) {
        $results[] = ['sql' => $label, 'status' => 'ERROR: ' . $e->getMessage()];
    }
}

// Verify columns now exist in societies
$cols = $pdo->query("SHOW COLUMNS FROM `societies`")->fetchAll(PDO::FETCH_COLUMN);
$expected = ['code', 'towers', 'total_flats', 'admin_id', 'gst', 'pan', 'registration_id'];
$verification = [];
foreach ($expected as $col) {
    $verification[$col] = in_array($col, $cols) ? '✅ exists' : '❌ MISSING';
}

// Verify unique constraint exists
$constraints = $pdo->query("SHOW INDEX FROM `societies` WHERE Key_name = 'uq_societies_registration_id'")->fetchAll();
$verification['uq_registration_id_constraint'] = count($constraints) > 0 ? '✅ exists' : '❌ MISSING (duplicate approvals possible!)';

header('Content-Type: application/json');
echo json_encode([
    'migration'    => '002_society_registrations_and_societies_update',
    'results'      => $results,
    'verification' => $verification,
    'note'         => 'DELETE this file (run_migration.php) after confirming all columns show ✅'
], JSON_PRETTY_PRINT);
