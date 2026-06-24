<?php
require_once "app/core/Database.php";
$db = Database::connect(['host'=>'127.0.0.1','user'=>'u233781988_mygatebell','pass'=>'Coepd@#2026','db'=>'u233781988_mygatebell']);
$stmt = $db->query("SELECT id, invoice_number, status FROM invoices LIMIT 10");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
