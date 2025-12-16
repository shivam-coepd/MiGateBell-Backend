<?php
// Simple Database Connection Test Script
// Upload this to your Hostinger server public_html/backend/ folder and visit:
// https://app.mygatebell.com/backend/db_test_final.php

// 1. Manually enter your Hostinger Database Credentials here to test
$host = 'localhost'; // Usually 'localhost' on Hostinger
$db_name = 'u233781988_mygatebell_db'; // CHECK THIS! Your error showed user 'u233781988_mygatebell', usually DB name is similar
$username = 'u233781988_mygatebell';   // This is the user from your error message
$password = 'ENTER_YOUR_DB_PASSWORD_HERE'; // <--- PUT YOUR PASSWORD HERE

echo "<h2>Database Connection Test</h2>";
echo "Attempting to connect with:<br>";
echo "User: <strong>$username</strong><br>";
echo "Host: <strong>$host</strong><br>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h3 style='color:green'>SUCCESS! Connected to database '$db_name'.</h3>";
} catch (PDOException $e) {
    echo "<h3 style='color:red'>CONNECTION FAILED</h3>";
    echo "Error: " . $e->getMessage();
    echo "<br><br><strong>Common Solutions:</strong>";
    echo "<ul>";
    echo "<li>Check if the PASSWORD is correct.</li>";
    echo "<li>Check if the DATABASE NAME is correct (it often looks like u12345_dbname).</li>";
    echo "<li>Go to Hostinger -> Databases -> MySQL Databases and ensure the user is assigned to the database.</li>";
    echo "</ul>";
}
?>