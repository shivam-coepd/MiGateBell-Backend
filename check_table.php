<?php
require 'app/core/Database.php';
require 'app/core/Config.php';

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SHOW TABLES LIKE 'guard_vehicle_entries'");
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($tables) > 0) {
        echo "Table exists.\n";
        // Check columns
        $stmt2 = $db->query("DESCRIBE guard_vehicle_entries");
        print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
    } else {
        echo "Table does NOT exist.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
