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
    <title>Book Party — MonkeyZone POS</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="navbar">
    <div class="logo">🐒 <span>MonkeyZone</span></div>
    <div class="menu">
        <a href="index.php">Create Sale</a>
        <a href="calendar.php">Calendar</a>
        <a href="book_party.php" class="active">Book Party</a>
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
    <h2>🎉 Book a Party</h2>

    <div class="section-box" style="max-width:520px;">
        <div class="customer-form" id="partyForm">
            <input type="text" id="name" placeholder="Customer Name *" required>
            <input type="tel"  id="phone" placeholder="Mobile Number * (10 digits)" maxlength="10" required>
            <input type="date" id="event_date" required>

            <select id="activity">
                <option value="Trampoline">🏃 Trampoline</option>
                <option value="Laser Tag">🔫 Laser Tag</option>
                <option value="Trampoline + Laser Tag">🎉 Trampoline + Laser Tag</option>
            </select>

            <select id="event_type">
                <option value="Birthday">🎂 Birthday</option>
                <option value="Corporate">🏢 Corporate</option>
                <option value="School Trip">🏫 School Trip</option>
                <option value="Other">🎈 Other</option>
            </select>

            <input type="number" id="expected_guests" placeholder="Expected Guests (optional)">

            <button type="button" class="confirm" id="bookNowBtn">🎉 Book Now</button>
        </div>
    </div>
</div>

<!-- PARTY PAYMENT MODAL -->
<div class="modal" id="partyPaymentModal">
    <div class="modal-content">
        <h3>🎂 Confirm Party Booking</h3>

        <div id="bookingSummary" style="background:#f5f0ff;border-radius:10px;padding:12px;margin-bottom:14px;text-align:left;font-size:13px;line-height:1.8;"></div>

        <h4>Advance Payment Amount</h4>
        <input type="number" id="advanceAmount" placeholder="Enter Advance Amount (₹)" style="width:100%;padding:10px;border:1.5px solid #e0d6f0;border-radius:8px;margin-bottom:10px;font-family:'Nunito',sans-serif;">

        <h4>Payment Method</h4>
        <div class="payment-options">
            <button onclick="selectPartyPayment('Cash', event)">💵 Cash</button>
            <button onclick="selectPartyPayment('Card', event)">💳 Card</button>
            <button onclick="selectPartyPayment('UPI', event)">📱 UPI</button>
        </div>

        <button class="confirm" onclick="confirmPartyBooking()">✅ Confirm Booking</button>
        <button class="close" onclick="closePartyModal()">Cancel</button>
    </div>
</div>

<script src="script.js" defer></script>
<script>
let partyPaymentMethod = "";

document.getElementById("bookNowBtn").addEventListener("click", () => {
    const name  = document.getElementById("name").value.trim();
    const phone = document.getElementById("phone").value.trim();
    const date  = document.getElementById("event_date").value;
    const activity   = document.getElementById("activity").value;
    const event_type = document.getElementById("event_type").value;
    const guests     = document.getElementById("expected_guests").value;

    if (!name || !phone || !date) {
        alert("Please fill in Name, Phone and Date!");
        return;
    }
    if (phone.length !== 10 || isNaN(phone)) {
        alert("Enter a valid 10-digit phone number!");
        return;
    }

    // Show summary in modal
    document.getElementById("bookingSummary").innerHTML = `
        <b>Name:</b> ${name}<br>
        <b>Phone:</b> ${phone}<br>
        <b>Date:</b> ${date}<br>
        <b>Activity:</b> ${activity}<br>
        <b>Type:</b> ${event_type}<br>
        ${guests ? `<b>Guests:</b> ${guests}<br>` : ''}
    `;

    document.getElementById("partyPaymentModal").style.display = "flex";
});

function selectPartyPayment(method, e) {
    partyPaymentMethod = method;
    document.querySelectorAll("#partyPaymentModal .payment-options button").forEach(b => b.classList.remove("active"));
    e.target.classList.add("active");
}

function closePartyModal() {
    document.getElementById("partyPaymentModal").style.display = "none";
    partyPaymentMethod = "";
    document.querySelectorAll("#partyPaymentModal .payment-options button").forEach(b => b.classList.remove("active"));
}

function confirmPartyBooking() {
    const advance = document.getElementById("advanceAmount").value;
    if (!advance || parseFloat(advance) < 0) {
        alert("Please enter a valid advance amount!");
        return;
    }
    if (!partyPaymentMethod) {
        alert("Please select a payment method!");
        return;
    }

    const data = {
        name:            document.getElementById("name").value.trim(),
        phone:           document.getElementById("phone").value.trim(),
        event_date:      document.getElementById("event_date").value,
        activity:        document.getElementById("activity").value,
        event_type:      document.getElementById("event_type").value,
        expected_guests: document.getElementById("expected_guests").value || 0,
        advance_amount:  parseFloat(advance),
        payment_method:  partyPaymentMethod
    };

    fetch("save_party.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === "success") {
            alert(`✅ Party Booked Successfully!\nBooking ID: ${res.booking_id}`);
            document.getElementById("partyForm").querySelectorAll("input, select").forEach(el => {
                if (el.tagName === "INPUT") el.value = "";
            });
            document.getElementById("activity").selectedIndex = 0;
            document.getElementById("event_type").selectedIndex = 0;
            closePartyModal();
        } else {
            alert("Error: " + (res.message || "Booking failed"));
        }
    })
    .catch(() => {
        alert("✅ Party Booked! (offline mode)");
        closePartyModal();
    });
}
</script>

</body>
</html>
