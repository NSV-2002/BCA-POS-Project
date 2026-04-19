<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    require_once 'db.php';

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        header("Location: login.html?error=empty");
        exit();
    }

    // Use parameterized query to prevent SQL injection
    $query = "SELECT id, username, password_hash, role FROM users WHERE username = $1";
    $result = pg_query_params($conn, $query, [$username]);

    if ($result && pg_num_rows($result) == 1) {
        $user = pg_fetch_assoc($result);

        // Support both hashed passwords (new) and plain (legacy migration)
        $valid = false;
        if (strlen($user['password_hash']) == 60 || strlen($user['password_hash']) > 40) {
            $valid = password_verify($password, $user['password_hash']);
        } else {
            // Legacy plain-text check (migrate immediately)
            $valid = ($password === $user['password_hash']);
            if ($valid) {
                // Auto-upgrade to hashed
                $hash = password_hash($password, PASSWORD_BCRYPT);
                pg_query_params($conn, "UPDATE users SET password_hash=$1 WHERE id=$2", [$hash, $user['id']]);
            }
        }

        if ($valid) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role']; // 'admin' or 'staff'

            header("Location: index.php");
            exit();
        }
    }

    header("Location: login.html?error=invalid");
    exit();
}
?>
