<?php
require_once __DIR__ . '/app/core/Database.php';

try {
    $dbConfig = require __DIR__ . '/app/config/database.php';
    $db = Database::connect($dbConfig);

    $sql = "CREATE TABLE IF NOT EXISTS society_registrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        society_name VARCHAR(150) NOT NULL,
        address TEXT,
        city VARCHAR(100) NOT NULL,
        state VARCHAR(100),
        pincode VARCHAR(20),
        towers INT DEFAULT 1,
        total_flats INT DEFAULT 10,
        contact_name VARCHAR(100) NOT NULL,
        contact_email VARCHAR(100) NOT NULL,
        contact_phone VARCHAR(20) NOT NULL,
        gst VARCHAR(50),
        pan VARCHAR(50),
        message TEXT,
        status VARCHAR(50) DEFAULT 'new',
        reviewed_by INT NULL,
        rejection_reason TEXT,
        reviewed_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $db->exec($sql);
    echo "Table 'society_registrations' created successfully.";

    // Also let's check if 'status', 'plan', 'code' exist in societies table and add them if not
    $checkSql = "SHOW COLUMNS FROM societies LIKE 'status'";
    $stmt = $db->query($checkSql);
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE societies ADD COLUMN status VARCHAR(50) DEFAULT 'pending'");
        echo " Added status to societies.";
    }

    $checkSql = "SHOW COLUMNS FROM societies LIKE 'plan'";
    $stmt = $db->query($checkSql);
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE societies ADD COLUMN plan VARCHAR(50) DEFAULT 'starter'");
        echo " Added plan to societies.";
    }

    $checkSql = "SHOW COLUMNS FROM societies LIKE 'code'";
    $stmt = $db->query($checkSql);
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE societies ADD COLUMN code VARCHAR(20) NULL UNIQUE");
        echo " Added code to societies.";
    }
    
    $checkSql = "SHOW COLUMNS FROM societies LIKE 'admin_id'";
    $stmt = $db->query($checkSql);
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE societies ADD COLUMN admin_id INT NULL");
        echo " Added admin_id to societies.";
    }
    
    $checkSql = "SHOW COLUMNS FROM societies LIKE 'total_flats'";
    $stmt = $db->query($checkSql);
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE societies ADD COLUMN total_flats INT DEFAULT 0");
        echo " Added total_flats to societies.";
    }
    
    $checkSql = "SHOW COLUMNS FROM societies LIKE 'towers'";
    $stmt = $db->query($checkSql);
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE societies ADD COLUMN towers INT DEFAULT 1");
        echo " Added towers to societies.";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
