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
    echo json_encode(["status" => "error", "message" => "Invalid data"]);
    exit();
}

$customer_name   = pg_escape_string($conn, $data['customer_name'] ?? '');
$phone           = pg_escape_string($conn, $data['phone'] ?? '');
$payment_method  = pg_escape_string($conn, $data['payment_method'] ?? '');
$cart            = $data['cart'] ?? [];
$discount_type   = pg_escape_string($conn, $data['discount_type'] ?? '');
$discount_value  = floatval($data['discount_value'] ?? 0);
$discount_amount = floatval($data['discount_amount'] ?? 0);
$total           = floatval($data['total'] ?? 0);
$user_id         = $_SESSION['user_id'] ?? 0;

// Generate unique transaction ID: TXN-YYYYMMDD-HHMMSS-RANDOM
$transaction_id = 'TXN-' . date('Ymd') . '-' . date('His') . '-' . strtoupper(substr(uniqid(), -4));

pg_query($conn, "BEGIN");

try {
    // Upsert customer
    $cust_result = pg_query_params($conn,
        "INSERT INTO customers (name, phone) VALUES ($1, $2)
         ON CONFLICT (phone) DO UPDATE SET name=EXCLUDED.name
         RETURNING id",
        [$customer_name, $phone]
    );
    $cust_row = pg_fetch_assoc($cust_result);
    $customer_id = $cust_row['id'];

    // Create transaction
    $tx_result = pg_query_params($conn,
        "INSERT INTO transactions (transaction_id, customer_id, payment_method, subtotal, discount_type, discount_value, discount_amount, total, created_by)
         VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9) RETURNING id",
        [
            $transaction_id, $customer_id, $payment_method,
            $total + $discount_amount, $discount_type, $discount_value,
            $discount_amount, $total, $user_id
        ]
    );
    $tx_row = pg_fetch_assoc($tx_result);
    $tx_db_id = $tx_row['id'];

    // Insert cart items
    foreach ($cart as $item) {
        pg_query_params($conn,
            "INSERT INTO transaction_items (transaction_id, item_name, qty, unit_price, tax_percent, item_total)
             VALUES ($1, $2, $3, $4, $5, $6)",
            [
                $tx_db_id,
                pg_escape_string($conn, $item['name']),
                intval($item['qty']),
                floatval($item['price']),
                floatval($item['tax']),
                floatval($item['price'] * $item['qty'])
            ]
        );
    }

    pg_query($conn, "COMMIT");

    echo json_encode([
        "status"         => "success",
        "transaction_id" => $transaction_id,
        "customer_id"    => $customer_id
    ]);

} catch (Exception $e) {
    pg_query($conn, "ROLLBACK");
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
