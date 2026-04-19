<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

require_once 'db.php';

$month = $_GET['month'] ?? '';

if ($month) {
    $result = pg_query_params($conn,
        "SELECT pb.booking_id, pb.event_date, c.name, c.phone, pb.activity, pb.event_type, pb.expected_guests, pb.advance_amount, pb.payment_method
         FROM party_bookings pb
         JOIN customers c ON pb.customer_id = c.id
         WHERE TO_CHAR(pb.event_date, 'YYYY-MM') = $1
         ORDER BY pb.event_date ASC",
        [$month]
    );
} else {
    $result = pg_query($conn,
        "SELECT pb.booking_id, pb.event_date, c.name, c.phone, pb.activity, pb.event_type, pb.expected_guests, pb.advance_amount, pb.payment_method
         FROM party_bookings pb
         JOIN customers c ON pb.customer_id = c.id
         ORDER BY pb.event_date DESC
         LIMIT 100"
    );
}

$rows = [];
while ($row = pg_fetch_assoc($result)) {
    $rows[] = $row;
}

echo json_encode($rows);
?>
