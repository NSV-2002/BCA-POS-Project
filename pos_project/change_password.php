<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}
$role     = $_SESSION['role'] ?? 'staff';
$initials = strtoupper(substr($_SESSION['username'], 0, 1));
$message  = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'db.php';

    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new !== $confirm) {
        $message  = 'New passwords do not match!';
        $msg_type = 'error';
    } elseif (strlen($new) < 6) {
        $message  = 'New password must be at least 6 characters!';
        $msg_type = 'error';
    } else {
        $result = pg_query_params($conn,
            "SELECT id, password_hash FROM users WHERE id = $1",
            [$_SESSION['user_id']]
        );
        $user = pg_fetch_assoc($result);

        $valid = password_verify($current, $user['password_hash']) ||
                 ($current === $user['password_hash']); // legacy

        if (!$valid) {
            $message  = 'Current password is incorrect!';
            $msg_type = 'error';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT);
            pg_query_params($conn,
                "UPDATE users SET password_hash = $1 WHERE id = $2",
                [$hash, $_SESSION['user_id']]
            );
            $message  = 'Password changed successfully!';
            $msg_type = 'success';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password — MonkeyZone POS</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="navbar">
    <div class="logo">🐒 <span>MonkeyZone</span></div>
    <div class="menu">
        <a href="index.php">Create Sale</a>
        <a href="calendar.php">Calendar</a>
        <a href="book_party.php">Book Party</a>
        <a href="customer.php">Customer Search</a>
        <?php if ($role === 'admin'): ?><a href="report.php">📊 Reports</a><?php endif; ?>
    </div>
    <div class="profile-container">
        <div class="profile" id="profileBtn"><?= $initials ?></div>
        <div class="dropdown" id="dropdownMenu">
            <p>👤 <?= htmlspecialchars($_SESSION['username']) ?></p>
            <a href="change_password.php">🔒 Change Password</a>
            <a href="logout.php">🚪 Logout</a>
        </div>
    </div>
</div>

<div class="page-container">
    <h2>🔒 Change Password</h2>

    <div class="section-box" style="max-width:420px;">
        <?php if ($message): ?>
        <div style="padding:12px;border-radius:8px;margin-bottom:16px;font-weight:700;font-size:14px;
             background:<?= $msg_type==='success' ? '#e8f8f0' : '#fff0f0' ?>;
             color:<?= $msg_type==='success' ? '#2ecc71' : '#e74c3c' ?>;
             border:1px solid <?= $msg_type==='success' ? '#b3e8cc' : '#f5c6c6' ?>;">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="customer-form">
            <input type="password" name="current_password" placeholder="Current Password *" required>
            <input type="password" name="new_password" placeholder="New Password * (min 6 chars)" required>
            <input type="password" name="confirm_password" placeholder="Confirm New Password *" required>
            <button type="submit" class="confirm" style="margin-top:0;">Update Password</button>
            <a href="index.php" style="display:block;text-align:center;margin-top:8px;color:#6a0dad;font-weight:700;font-size:14px;">← Back to Dashboard</a>
        </form>
    </div>
</div>

<script src="script.js" defer></script>
</body>
</html>
