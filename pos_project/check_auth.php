<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(["status" => "no"]);
} else {
    echo json_encode([
        "status"   => "yes",
        "username" => $_SESSION['username'],
        "role"     => $_SESSION['role'] ?? 'staff'
    ]);
}
?>
