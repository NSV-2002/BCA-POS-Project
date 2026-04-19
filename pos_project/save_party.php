<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

require_once 'db.php';

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON"]);
    exit();
}

$name            = pg_escape_string($conn, trim($data['name'] ?? ''));
$phone           = pg_escape_string($conn, trim($data['phone'] ?? ''));
$event_date      = pg_escape_string($conn, $data['event_date'] ?? '');
$activity        = pg_escape_string($conn, $data['activity'] ?? '');
$event_type      = pg_escape_string($conn, $data['event_type'] ?? '');
$expected_guests = intval($data['expected_guests'] ?? 0);
$advance_amount  = floatval($data['advance_amount'] ?? 0);
$payment_method  = pg_escape_string($conn, $data['payment_method'] ?? '');
$user_id         = $_SESSION['user_id'] ?? 0;

// Unique booking ID
$booking_id = 'PARTY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

pg_query($conn, "BEGIN");

try {
    // Upsert customer
    $cust = pg_query_params($conn,
        "INSERT INTO customers (name, phone) VALUES ($1, $2)
         ON CONFLICT (phone) DO UPDATE SET name=EXCLUDED.name RETURNING id",
        [$name, $phone]
    );
    $cust_row   = pg_fetch_assoc($cust);
    $customer_id = $cust_row['id'];

    // Save party booking
    pg_query_params($conn,
        "INSERT INTO party_bookings (booking_id, customer_id, event_date, activity, event_type, expected_guests, advance_amount, payment_method, created_by)
         VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)",
        [
            $booking_id, $customer_id, $event_date, $activity,
            $event_type, $expected_guests, $advance_amount, $payment_method, $user_id
        ]
    );

    pg_query($conn, "COMMIT");

    echo json_encode([
        "status"      => "success",
        "booking_id"  => $booking_id,
        "customer_id" => $customer_id
    ]);

} catch (Exception $e) {
    pg_query($conn, "ROLLBACK");
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
