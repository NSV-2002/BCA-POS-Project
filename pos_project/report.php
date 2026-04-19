<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}
if (($_SESSION['role'] ?? 'staff') !== 'admin') {
    header("Location: index.php");
    exit();
}
$initials = strtoupper(substr($_SESSION['username'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports — MonkeyZone POS</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
</head>
<body>

<div class="navbar">
    <div class="logo">🐒 <span>MonkeyZone</span></div>
    <div class="menu">
        <a href="index.php">Create Sale</a>
        <a href="calendar.php">Calendar</a>
        <a href="book_party.php">Book Party</a>
        <a href="customer.php">Customer Search</a>
        <a href="report.php" class="active">📊 Reports</a>
    </div>
    <div class="profile-container">
        <div class="profile" id="profileBtn"><?= $initials ?></div>
        <div class="dropdown" id="dropdownMenu">
            <p>👤 <?= htmlspecialchars($_SESSION['username']) ?> <span class="badge-admin">ADMIN</span></p>
            <a href="change_password.php">🔒 Change Password</a>
            <a href="logout.php">🚪 Logout</a>
        </div>
    </div>
</div>

<div class="page-container" style="max-width:1200px;">
    <h2>📊 Sales Reports</h2>

    <div class="report-filters">
        <div>
            <label>From Date</label>
            <input type="date" id="fromDate">
        </div>
        <div>
            <label>To Date</label>
            <input type="date" id="toDate">
        </div>
        <div>
            <label>Payment Method</label>
            <select id="filterPayment">
                <option value="">All</option>
                <option value="Cash">Cash</option>
                <option value="Card">Card</option>
                <option value="UPI">UPI</option>
            </select>
        </div>
        <button onclick="loadReport()">🔍 Generate Report</button>
        <button onclick="exportCSV()" style="background:#2ecc71;">📥 Export CSV</button>
        <button onclick="printReport()" style="background:#3498db;">🖨 Print</button>
    </div>

    <!-- Stats -->
    <div class="stats-row" id="statsRow">
        <div class="stat-card">
            <p>Total Revenue</p>
            <h3 id="statRevenue">₹0</h3>
        </div>
        <div class="stat-card orange">
            <p>Total Transactions</p>
            <h3 id="statTx">0</h3>
        </div>
        <div class="stat-card green">
            <p>Unique Customers</p>
            <h3 id="statCust">0</h3>
        </div>
        <div class="stat-card">
            <p>Avg. Order Value</p>
            <h3 id="statAvg">₹0</h3>
        </div>
    </div>

    <!-- Charts -->
    <div style="display:flex;gap:20px;margin-bottom:24px;flex-wrap:wrap;">
        <div class="section-box" style="flex:1;min-width:280px;">
            <div class="section-title">📈 Revenue by Day</div>
            <canvas id="revenueChart" height="180"></canvas>
        </div>
        <div class="section-box" style="flex:0 0 240px;">
            <div class="section-title">💳 Payment Methods</div>
            <canvas id="paymentChart" height="180"></canvas>
        </div>
    </div>

    <!-- Table -->
    <div class="table-container" id="printArea">
        <table id="reportTable">
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Date & Time</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Items</th>
                    <th>Payment</th>
                    <th>Discount</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody id="reportBody">
                <tr><td colspan="8" style="color:#aaa;padding:30px;">Select date range and click Generate Report</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script src="script.js" defer></script>
<script>
let reportData = [];
let revenueChartInst = null;
let paymentChartInst = null;

// Set default dates (last 30 days)
const today = new Date();
const last30 = new Date(today - 30 * 86400000);
document.getElementById("toDate").value = today.toISOString().split('T')[0];
document.getElementById("fromDate").value = last30.toISOString().split('T')[0];

function loadReport() {
    const from = document.getElementById("fromDate").value;
    const to   = document.getElementById("toDate").value;
    const pay  = document.getElementById("filterPayment").value;

    if (!from || !to) { alert("Select date range!"); return; }

    let url = `get_report.php?from=${from}&to=${to}`;
    if (pay) url += `&payment=${pay}`;

    document.getElementById("reportBody").innerHTML = `<tr><td colspan="8" style="color:#aaa;padding:20px;">Loading...</td></tr>`;

    fetch(url)
    .then(r => r.json())
    .then(data => {
        reportData = data.transactions || [];
        const stats = data.stats || {};

        // Stats
        document.getElementById("statRevenue").textContent = '₹' + parseFloat(stats.total_revenue || 0).toLocaleString('en-IN');
        document.getElementById("statTx").textContent      = stats.tx_count || 0;
        document.getElementById("statCust").textContent    = stats.unique_customers || 0;
        document.getElementById("statAvg").textContent     = '₹' + parseFloat(stats.avg_order || 0).toFixed(0);

        // Table
        const tbody = document.getElementById("reportBody");
        if (reportData.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" style="color:#aaa;padding:24px;">No transactions in this range.</td></tr>`;
        } else {
            tbody.innerHTML = reportData.map(t => `
                <tr>
                    <td><span class="badge badge-primary">${t.transaction_id}</span></td>
                    <td>${new Date(t.created_at).toLocaleString('en-IN')}</td>
                    <td><b>${t.name || '—'}</b></td>
                    <td>${t.phone || '—'}</td>
                    <td style="text-align:left;font-size:12px;">${t.items || '—'}</td>
                    <td><span class="badge badge-success">${t.payment_method}</span></td>
                    <td>${t.discount_amount > 0 ? '₹' + t.discount_amount : '—'}</td>
                    <td><b>₹${parseFloat(t.total).toLocaleString()}</b></td>
                </tr>
            `).join('');
        }

        // Charts
        renderCharts(data.daily || [], data.payment_breakdown || []);
    })
    .catch(err => {
        document.getElementById("reportBody").innerHTML = `<tr><td colspan="8" style="color:red;padding:20px;">Error loading report: ${err.message}</td></tr>`;
    });
}

function renderCharts(daily, payBreak) {
    if (revenueChartInst) revenueChartInst.destroy();
    if (paymentChartInst) paymentChartInst.destroy();

    revenueChartInst = new Chart(document.getElementById("revenueChart"), {
        type: 'line',
        data: {
            labels: daily.map(d => d.day),
            datasets: [{
                label: 'Revenue (₹)',
                data: daily.map(d => d.revenue),
                borderColor: '#6a0dad',
                backgroundColor: 'rgba(106,13,173,0.08)',
                fill: true,
                tension: 0.4
            }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    paymentChartInst = new Chart(document.getElementById("paymentChart"), {
        type: 'doughnut',
        data: {
            labels: payBreak.map(p => p.payment_method),
            datasets: [{
                data: payBreak.map(p => p.total),
                backgroundColor: ['#6a0dad','#ff8c00','#2ecc71']
            }]
        },
        options: { plugins: { legend: { position: 'bottom' } } }
    });
}

function exportCSV() {
    if (reportData.length === 0) { alert("Generate report first!"); return; }
    const headers = ["Transaction ID","Date","Customer","Phone","Payment","Discount","Total"];
    const rows = reportData.map(t => [
        t.transaction_id,
        new Date(t.created_at).toLocaleString('en-IN'),
        t.name || '',
        t.phone || '',
        t.payment_method,
        t.discount_amount,
        t.total
    ]);
    const csv = [headers, ...rows].map(r => r.map(c => `"${c}"`).join(',')).join('\n');
    const blob = new Blob([csv], {type:'text/csv'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `report_${document.getElementById("fromDate").value}_to_${document.getElementById("toDate").value}.csv`;
    a.click();
}

function printReport() {
    window.print();
}

loadReport();
</script>

</body>
</html>
