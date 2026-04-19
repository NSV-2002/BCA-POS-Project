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
    <title>Customer Search — MonkeyZone POS</title>
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
        <a href="customer.php" class="active">Customer Search</a>
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
    <h2>🔍 Customer Search</h2>

    <div class="search-box">
        <input type="text" id="search" placeholder="Search by name or phone number...">
    </div>

    <div class="table-container" style="margin-bottom:24px;">
        <table id="customerTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Total Visits</th>
                    <th>Total Spent</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="customerBody">
                <tr><td colspan="6" style="color:#aaa;padding:30px;">Loading...</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Transaction History Panel -->
    <div id="historyPanel" style="display:none;">
        <h2>📋 Transaction History — <span id="historyName"></span></h2>
        <div class="table-container">
            <table id="historyTable">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Payment</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody id="historyBody"></tbody>
            </table>
        </div>
    </div>
</div>

<script src="script.js" defer></script>
<script>
function loadCustomers(query = "") {
    fetch(`get_customers.php?search=${encodeURIComponent(query)}`)
    .then(r => r.json())
    .then(data => {
        const tbody = document.getElementById("customerBody");
        if (!data || data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" style="color:#aaa;padding:24px;">No customers found.</td></tr>`;
            return;
        }
        tbody.innerHTML = data.map(c => `
            <tr>
                <td><span class="badge badge-primary">#${c.id}</span></td>
                <td><b>${c.name}</b></td>
                <td>${c.phone}</td>
                <td><span class="badge badge-warning">${c.visit_count || 0} visits</span></td>
                <td><b>₹${parseFloat(c.total_spent || 0).toLocaleString()}</b></td>
                <td><button onclick="loadHistory(${c.id}, '${c.name}')" style="padding:6px 14px;background:#6a0dad;color:white;border:none;border-radius:7px;cursor:pointer;font-weight:700;font-family:'Nunito',sans-serif;font-size:12px;">View History</button></td>
            </tr>
        `).join('');
    });
}

function loadHistory(customerId, customerName) {
    document.getElementById("historyPanel").style.display = "block";
    document.getElementById("historyName").textContent = customerName;
    document.getElementById("historyBody").innerHTML = `<tr><td colspan="5" style="color:#aaa;padding:20px;">Loading...</td></tr>`;

    fetch(`get_customer_history.php?customer_id=${customerId}`)
    .then(r => r.json())
    .then(data => {
        if (!data || data.length === 0) {
            document.getElementById("historyBody").innerHTML = `<tr><td colspan="5" style="color:#aaa;padding:20px;">No transactions found.</td></tr>`;
            return;
        }
        document.getElementById("historyBody").innerHTML = data.map(t => `
            <tr>
                <td><span class="badge badge-primary">${t.transaction_id}</span></td>
                <td>${new Date(t.created_at).toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'})}</td>
                <td style="text-align:left;max-width:200px;">${t.items || '—'}</td>
                <td><span class="badge badge-success">${t.payment_method}</span></td>
                <td><b>₹${parseFloat(t.total).toLocaleString()}</b></td>
            </tr>
        `).join('');
        document.getElementById("historyPanel").scrollIntoView({behavior:'smooth'});
    });
}

document.getElementById("search").addEventListener("input", (e) => {
    loadCustomers(e.target.value);
});

loadCustomers();
</script>

</body>
</html>
