<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(["status" => "error"]);
    exit();
}

require_once 'db.php';

$search = '%' . pg_escape_string($conn, $_GET['search'] ?? '') . '%';

$result = pg_query_params($conn,
    "SELECT c.id, c.name, c.phone,
            COUNT(DISTINCT t.id) AS visit_count,
            COALESCE(SUM(t.total), 0) AS total_spent
     FROM customers c
     LEFT JOIN transactions t ON c.id = t.customer_id
     WHERE c.name ILIKE $1 OR c.phone ILIKE $1
     GROUP BY c.id, c.name, c.phone
     ORDER BY c.name ASC
     LIMIT 100",
    [$search]
);

$rows = [];
while ($row = pg_fetch_assoc($result)) {
    $rows[] = $row;
}

echo json_encode($rows);
?>
