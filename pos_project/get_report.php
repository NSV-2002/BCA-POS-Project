<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? 'staff') !== 'admin') {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

require_once 'db.php';

$from    = pg_escape_string($conn, $_GET['from'] ?? date('Y-m-01'));
$to      = pg_escape_string($conn, $_GET['to']   ?? date('Y-m-d'));
$payment = pg_escape_string($conn, $_GET['payment'] ?? '');

$pay_filter = $payment ? "AND t.payment_method = '$payment'" : "";

// Transactions list
$tx_result = pg_query($conn,
    "SELECT t.transaction_id, t.created_at, t.payment_method, t.total,
            t.discount_amount, c.name, c.phone,
            STRING_AGG(ti.item_name || ' x' || ti.qty, ', ') AS items
     FROM transactions t
     LEFT JOIN customers c ON t.customer_id = c.id
     LEFT JOIN transaction_items ti ON t.id = ti.transaction_id
     WHERE DATE(t.created_at) BETWEEN '$from' AND '$to'
     $pay_filter
     GROUP BY t.id, t.transaction_id, t.created_at, t.payment_method, t.total,
              t.discount_amount, c.name, c.phone
     ORDER BY t.created_at DESC"
);

$transactions = [];
while ($row = pg_fetch_assoc($tx_result)) {
    $transactions[] = $row;
}

// Stats
$stats_result = pg_query($conn,
    "SELECT
        COALESCE(SUM(total), 0)                    AS total_revenue,
        COUNT(*)                                    AS tx_count,
        COUNT(DISTINCT customer_id)                AS unique_customers,
        COALESCE(AVG(total), 0)                    AS avg_order
     FROM transactions
     WHERE DATE(created_at) BETWEEN '$from' AND '$to'
     $pay_filter"
);
$stats = pg_fetch_assoc($stats_result);

// Daily revenue
$daily_result = pg_query($conn,
    "SELECT TO_CHAR(DATE(created_at), 'DD Mon') AS day,
            SUM(total) AS revenue
     FROM transactions
     WHERE DATE(created_at) BETWEEN '$from' AND '$to'
     $pay_filter
     GROUP BY DATE(created_at)
     ORDER BY DATE(created_at) ASC"
);
$daily = [];
while ($row = pg_fetch_assoc($daily_result)) {
    $daily[] = $row;
}

// Payment breakdown
$pay_result = pg_query($conn,
    "SELECT payment_method, SUM(total) AS total, COUNT(*) AS cnt
     FROM transactions
     WHERE DATE(created_at) BETWEEN '$from' AND '$to'
     GROUP BY payment_method"
);
$payment_breakdown = [];
while ($row = pg_fetch_assoc($pay_result)) {
    $payment_breakdown[] = $row;
}

echo json_encode([
    "transactions"      => $transactions,
    "stats"             => $stats,
    "daily"             => $daily,
    "payment_breakdown" => $payment_breakdown
]);
?>
