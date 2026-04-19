<?php
// Database configuration - update these values for your environment
define('DB_HOST', 'localhost');
define('DB_NAME', 'pos_db');
define('DB_USER', 'postgres');
define('DB_PASS', 'Nilesh@123'); // Change this

$conn = pg_connect("host=" . DB_HOST . " dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASS);

if (!$conn) {
    http_response_code(500);
    die(json_encode(["status" => "error", "message" => "Database connection failed"]));
}
?>
