<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode([]);
    exit();
}

require_once 'db.php';

$customer_id = intval($_GET['customer_id'] ?? 0);
if (!$customer_id) { echo json_encode([]); exit(); }

$result = pg_query_params($conn,
    "SELECT t.transaction_id, t.created_at, t.payment_method, t.total,
            STRING_AGG(ti.item_name || ' x' || ti.qty, ', ') AS items
     FROM transactions t
     LEFT JOIN transaction_items ti ON t.id = ti.transaction_id
     WHERE t.customer_id = $1
     GROUP BY t.id, t.transaction_id, t.created_at, t.payment_method, t.total
     ORDER BY t.created_at DESC
     LIMIT 50",
    [$customer_id]
);

$rows = [];
while ($row = pg_fetch_assoc($result)) {
    $rows[] = $row;
}

echo json_encode($rows);
?>
