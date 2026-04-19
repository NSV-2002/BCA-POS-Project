<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}
$role = $_SESSION['role'] ?? 'staff';
$initials = strtoupper(substr($_SESSION['username'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar — MonkeyZone POS</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="navbar">
    <div class="logo">🐒 <span>MonkeyZone</span></div>
    <div class="menu">
        <a href="index.php">Create Sale</a>
        <a href="calendar.php" class="active">Calendar</a>
        <a href="book_party.php">Book Party</a>
        <a href="customer.php">Customer Search</a>
        <?php if ($role === 'admin'): ?><a href="report.php">📊 Reports</a><?php endif; ?>
    </div>
    <div class="profile-container">
        <div class="profile" id="profileBtn"><?= $initials ?></div>
        <div class="dropdown" id="dropdownMenu">
            <p>👤 <?= htmlspecialchars($_SESSION['username']) ?></p>
            <a href="change_password.php">🔒 Change Password</a>
            <?php if ($role === 'admin'): ?><a href="report.php">📊 Reports</a><?php endif; ?>
            <a href="logout.php">🚪 Logout</a>
        </div>
    </div>
</div>

<div class="page-container">
    <h2>📅 Booked Parties</h2>

    <div style="display:flex;gap:12px;margin-bottom:16px;align-items:center;flex-wrap:wrap;">
        <input type="month" id="filterMonth" style="padding:8px 12px;border:1.5px solid #e0d6f0;border-radius:8px;font-family:'Nunito',sans-serif;font-size:14px;">
        <button onclick="loadParties()" style="padding:9px 18px;background:#6a0dad;color:white;border:none;border-radius:8px;font-weight:700;font-family:'Nunito',sans-serif;cursor:pointer;">Filter</button>
        <button onclick="clearFilter()" style="padding:9px 18px;background:#eee;color:#333;border:none;border-radius:8px;font-weight:700;font-family:'Nunito',sans-serif;cursor:pointer;">Show All</button>
    </div>

    <div class="table-container">
        <table id="partyTable">
            <thead>
                <tr>
                    <th>Booking ID</th>
                    <th>Event Date</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Activity</th>
                    <th>Type</th>
                    <th>Guests</th>
                    <th>Advance</th>
                    <th>Payment</th>
                </tr>
            </thead>
            <tbody id="partyBody">
                <tr><td colspan="9" style="color:#aaa;padding:30px;">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script src="script.js" defer></script>
<script>
function loadParties() {
    const month = document.getElementById("filterMonth").value;
    const url = "get_parties.php" + (month ? "?month=" + month : "");

    fetch(url)
    .then(r => r.json())
    .then(data => {
        const tbody = document.getElementById("partyBody");
        if (!data || data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" style="color:#aaa;padding:30px;">No bookings found.</td></tr>`;
            return;
        }
        tbody.innerHTML = data.map(p => `
            <tr>
                <td><span class="badge badge-primary">${p.booking_id}</span></td>
                <td>${p.event_date}</td>
                <td><b>${p.name}</b></td>
                <td>${p.phone}</td>
                <td>${p.activity}</td>
                <td>${p.event_type}</td>
                <td>${p.expected_guests || '—'}</td>
                <td><b>₹${parseFloat(p.advance_amount).toLocaleString()}</b></td>
                <td><span class="badge badge-success">${p.payment_method}</span></td>
            </tr>
        `).join('');
    })
    .catch(() => {
        document.getElementById("partyBody").innerHTML = `<tr><td colspan="9" style="color:#e74c3c;">Error loading parties.</td></tr>`;
    });
}

function clearFilter() {
    document.getElementById("filterMonth").value = "";
    loadParties();
}

loadParties();
</script>

</body>
</html>
