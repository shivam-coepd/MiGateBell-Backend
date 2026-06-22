<?php
require_once __DIR__ . '/app/core/Database.php';

try {
    $db = Database::connect(require __DIR__.'/app/config/database.php');
    $sql = file_get_contents(__DIR__ . '/migrations/005_events_management.sql');
    $db->exec($sql);
    echo "Migration 005_events_management.sql applied successfully.\n";

    $sql = file_get_contents(__DIR__ . '/migrations/006_communications_polls.sql');
    $db->exec($sql);
    echo "Migration 006_communications_polls.sql applied successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
