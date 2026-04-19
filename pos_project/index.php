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
    <title>Create Sale — MonkeyZone POS</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="navbar">
    <div class="logo">🐒 <span>MonkeyZone</span></div>
    <div class="menu">
        <a href="index.php" class="active">Create Sale</a>
        <a href="calendar.php">Calendar</a>
        <a href="book_party.php">Book Party</a>
        <a href="customer.php">Customer Search</a>
        <?php if ($role === 'admin'): ?>
        <a href="report.php">📊 Reports</a>
        <?php endif; ?>
    </div>
    <div class="profile-container">
        <div class="profile" id="profileBtn"><?= $initials ?></div>
        <div class="dropdown" id="dropdownMenu">
            <p>👤 <?= htmlspecialchars($_SESSION['username']) ?> <?php if($role==='admin'): ?><span class="badge-admin">ADMIN</span><?php endif; ?></p>
            <a href="change_password.php">🔒 Change Password</a>
            <?php if ($role === 'admin'): ?>
            <a href="report.php">📊 Reports</a>
            <?php endif; ?>
            <a href="logout.php">🚪 Logout</a>
        </div>
    </div>
</div>

<div class="container">
    <!-- LEFT -->
    <div class="left">
        <h2>🛒 Create Sale</h2>

        <div class="tabs">
            <button class="active" onclick="switchTab('activities', this)">Activities</button>
            <button onclick="switchTab('combo', this)">Combo</button>
        </div>

        <div id="tab-activities">
            <div class="section-box">
                <div class="section-title">🏃 Trampoline</div>
                <div class="items">
                    <div class="item" data-name="Jump 30 min" data-price="500">Jump 30 🛒</div>
                    <div class="item" data-name="Jump 60 min" data-price="800">Jump 60 🛒</div>
                    <div class="item" data-name="Jump 90 min" data-price="1000">Jump 90 🛒</div>
                </div>
            </div>
            <div class="section-box">
                <div class="section-title">🔫 Laser Tag</div>
                <div class="items">
                    <div class="item" data-name="Laser Tag 30 min" data-price="400">Laser Tag 30 🛒</div>
                    <div class="item" data-name="Laser Tag 60 min" data-price="700">Laser Tag 60 🛒</div>
                    <div class="item" data-name="Laser War" data-price="900">Laser War 🛒</div>
                </div>
            </div>
        </div>

        <div id="tab-combo" style="display:none;">
            <div class="section-box">
                <div class="section-title">🎉 Combo Packages</div>
                <div class="items">
                    <div class="item" data-name="Trampoline + Laser (60 min)" data-price="1100">Tramp + Laser 60 🛒</div>
                    <div class="item" data-name="Trampoline + Laser (90 min)" data-price="1500">Tramp + Laser 90 🛒</div>
                    <div class="item" data-name="Full Day Pass" data-price="1999">Full Day Pass 🛒</div>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT -->
    <div class="right">
        <div class="top-boxes">
            <div class="box purple">🎟️ Tickets &nbsp;<b id="ticketCount">0</b></div>
            <div class="box orange-box">💰 Total &nbsp;<b id="totalBox">₹0</b></div>
        </div>

        <div style="display:flex;justify-content:flex-end;margin-bottom:8px;">
            <button class="reset" id="resetBtn">🗑 RESET</button>
        </div>

        <table id="cartTable">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Tax</th>
                    <th>Total</th>
                    <th>Del</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

        <div class="summary">
            <p>Subtotal <span id="subtotalVal">₹0</span></p>
            <p>Discount <span id="discountVal">₹0</span></p>
            <h3>Total <span id="totalVal">₹0</span></h3>
            <button id="discountBtn" class="reset" style="width:100%;margin-top:8px;">🏷 Apply Discount</button>
        </div>

        <button class="pay">💳 Pay Now</button>
    </div>
</div>

<!-- PAYMENT MODAL -->
<div class="modal" id="paymentModal">
    <div class="modal-content">
        <h3>💳 Complete Payment</h3>

        <div class="customer-form">
            <input type="text" id="custName" placeholder="Customer Name *">
            <input type="tel" id="custPhone" placeholder="Phone Number *" maxlength="10">
        </div>

        <h4>Payment Method</h4>
        <div class="payment-options">
            <button onclick="selectPayment('Cash', event)">💵 Cash</button>
            <button onclick="selectPayment('Card', event)">💳 Card</button>
            <button onclick="selectPayment('UPI', event)">📱 UPI</button>
        </div>

        <div id="cashSection" class="hidden" style="margin:10px 0;">
            <input type="number" id="cashGiven" placeholder="Cash Given" style="width:100%;padding:9px;border:1.5px solid #e0d6f0;border-radius:8px;font-family:'Nunito',sans-serif;">
            <p style="font-weight:700;color:#6a0dad;margin-top:8px;">Change: ₹<span id="changeAmount">0</span></p>
        </div>

        <button class="confirm" onclick="confirmPayment()">✅ Confirm Payment</button>
        <button class="close" onclick="closeModal()">Cancel</button>
    </div>
</div>

<!-- DISCOUNT MODAL -->
<div class="modal" id="discountModal">
    <div class="modal-content">
        <h3>🏷 Apply Discount</h3>
        <div class="payment-options">
            <button onclick="selectDiscount('flat', event)">₹ Flat</button>
            <button onclick="selectDiscount('percent', event)">% Percentage</button>
        </div>
        <input type="number" id="discountValue" placeholder="Enter value" style="width:100%;padding:10px;border:1.5px solid #e0d6f0;border-radius:8px;margin:10px 0;font-family:'Nunito',sans-serif;">
        <button class="confirm" onclick="applyDiscount()">Apply</button>
        <button class="close" onclick="closeDiscount()">Cancel</button>
    </div>
</div>

<script src="script.js" defer></script>
<script>
function switchTab(tab, btn) {
    document.getElementById('tab-activities').style.display = tab === 'activities' ? 'block' : 'none';
    document.getElementById('tab-combo').style.display = tab === 'combo' ? 'block' : 'none';
    document.querySelectorAll('.tabs button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}
</script>

</body>
</html>
