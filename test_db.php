<?php
require 'app/core/Database.php';
$c = require 'app/config/database.php';
$db = Database::connect($c);
echo "VEHICLE TYPES:\n";
print_r($db->query('SELECT * FROM vehicle_types')->fetchAll());
echo "PET TYPES:\n";
print_r($db->query('SELECT * FROM pet_types')->fetchAll());
