<?php
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/core/Database.php';

try {
    $config = require __DIR__.'/app/config/database.php';
    $db = Database::connect($config);

    $search = 'kiran';
    $whereClause = "WHERE u.society_id = :society_id AND u.role = 'resident' AND u.status = 'active'";
    $params = ['society_id' => 1];

    if ($search) {
        $whereClause .= " AND (u.name LIKE :search_name OR u.phone LIKE :search_phone)";
        $params['search_name'] = "%{$search}%";
        $params['search_phone'] = "%{$search}%";
    }

    $countSql = "SELECT COUNT(*) as count FROM users u {$whereClause}";
    $countStmt = $db->prepare($countSql);
    try {
        $countStmt->execute($params);
        echo "Count success. ";
    } catch (Exception $e) {
        echo "Count Error: " . $e->getMessage() . " ";
    }

    $sql = "
        SELECT u.id, u.name, u.phone,
               (SELECT CONCAT(b.name, '-', f.flat_number)
                FROM flats f
                LEFT JOIN buildings b ON f.building_id = b.id
                WHERE (f.owner_id = u.id OR f.tenant_id = u.id)
                LIMIT 1) AS flat_number
        FROM users u
        {$whereClause}
        ORDER BY u.name ASC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
    }
    $stmt->bindValue(':limit', 50, PDO::PARAM_INT);
    $stmt->bindValue(':offset', 0, PDO::PARAM_INT);
    try {
        $stmt->execute();
        echo "Select success.";
    } catch (Exception $e) {
        echo "Select Error: " . $e->getMessage();
    }

} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage();
}
