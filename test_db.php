<?php
require_once "app/core/Database.php";
$db = Database::connect(['host'=>'127.0.0.1','user'=>'root','pass'=>'','db'=>'u233781988_mygatebell']);
$stmt = $db->query("SHOW TABLES");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
?>
