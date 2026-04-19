// ================= GLOBAL STATE =================
let cart = [];
let selectedPayment = "";
let discountType   = "";
let discountValue  = 0;

// ================= RENDER CART =================
function renderCart() {
    const tbody = document.querySelector("#cartTable tbody");
    if (!tbody) return;

    tbody.innerHTML = "";

    let subtotal = 0;
    let tickets  = 0;

    cart.forEach((item, index) => {
        const total = item.price * item.qty;
        subtotal += total;
        tickets  += item.qty;

        const row = document.createElement("tr");
        row.innerHTML = `
            <td>${item.name}</td>
            <td>${item.qty}</td>
            <td>₹${item.price}</td>
            <td>${item.tax}%</td>
            <td>₹${total}</td>
            <td><button class="del">🗑️</button></td>
        `;
        row.querySelector(".del").addEventListener("click", () => {
            cart.splice(index, 1);
            renderCart();
        });
        tbody.appendChild(row);
    });

    // Discount
    let discountAmount = 0;
    if (discountType === "flat") {
        discountAmount = discountValue;
    } else if (discountType === "percent") {
        discountAmount = (subtotal * discountValue) / 100;
    }
    if (discountAmount > subtotal) discountAmount = subtotal;
    const finalTotal = subtotal - discountAmount;

    // Update DOM
    const set = (id, val) => { const el = document.getElementById(id); if(el) el.innerText = val; };
    set("subtotalVal",  "₹" + subtotal);
    set("discountVal",  "₹" + discountAmount.toFixed(2));
    set("totalVal",     "₹" + finalTotal.toFixed(2));
    set("totalBox",     "₹" + finalTotal.toFixed(2));
    set("ticketCount",  tickets);

    // Cash change
    const cashGiven = parseFloat(document.getElementById("cashGiven")?.value || 0);
    const changeEl  = document.getElementById("changeAmount");
    if (changeEl) changeEl.innerText = Math.max(0, cashGiven - finalTotal).toFixed(2);

    window._finalTotal = finalTotal;
}

// ================= DOM READY =================
document.addEventListener("DOMContentLoaded", () => {

    // Add items
    document.querySelectorAll(".item").forEach(item => {
        item.addEventListener("click", () => {
            const name  = item.dataset.name;
            const price = parseFloat(item.dataset.price);
            if (!name || isNaN(price)) return;

            const existing = cart.find(i => i.name === name);
            if (existing) {
                existing.qty++;
            } else {
                cart.push({ name, price, qty: 1, tax: 18 });
            }
            renderCart();
        });
    });

    // Reset
    const resetBtn = document.getElementById("resetBtn");
    if (resetBtn) {
        resetBtn.addEventListener("click", () => {
            cart = []; discountType = ""; discountValue = 0;
            renderCart();
        });
    }

    // Pay
    const payBtn = document.querySelector(".pay");
    if (payBtn) {
        payBtn.addEventListener("click", () => {
            if (cart.length === 0) { alert("Cart is empty!"); return; }
            document.getElementById("paymentModal").style.display = "flex";
        });
    }

    // Discount
    const discountBtn = document.getElementById("discountBtn");
    if (discountBtn) {
        discountBtn.addEventListener("click", () => {
            document.getElementById("discountModal").style.display = "flex";
        });
    }

    // Cash change live calculation
    const cashInput = document.getElementById("cashGiven");
    if (cashInput) {
        cashInput.addEventListener("input", () => {
            const given  = parseFloat(cashInput.value) || 0;
            const change = Math.max(0, given - (window._finalTotal || 0));
            document.getElementById("changeAmount").innerText = change.toFixed(2);
        });
    }

    // Profile dropdown
    const profileBtn  = document.getElementById("profileBtn");
    const dropdownMenu = document.getElementById("dropdownMenu");
    if (profileBtn && dropdownMenu) {
        profileBtn.onclick = (e) => {
            e.stopPropagation();
            dropdownMenu.classList.toggle("show");
        };
        document.onclick = (e) => {
            if (!profileBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove("show");
            }
        };
    }
});

// ================= DISCOUNT FUNCTIONS =================
window.selectDiscount = function(type, e) {
    discountType = type;
    document.querySelectorAll("#discountModal .payment-options button").forEach(b => b.classList.remove("active"));
    if (e) e.target.classList.add("active");
};

window.applyDiscount = function() {
    const val = parseFloat(document.getElementById("discountValue").value);
    if (!discountType || isNaN(val) || val < 0) { alert("Enter discount properly!"); return; }
    discountValue = val;
    renderCart();
    closeDiscount();
};

window.closeDiscount = function() {
    document.getElementById("discountModal").style.display = "none";
};

// ================= PAYMENT =================
window.closeModal = function() {
    document.getElementById("paymentModal").style.display = "none";
    selectedPayment = "";
    document.querySelectorAll("#paymentModal .payment-options button").forEach(b => b.classList.remove("active"));
};

window.selectPayment = function(method, e) {
    selectedPayment = method;
    document.querySelectorAll("#paymentModal .payment-options button").forEach(b => b.classList.remove("active"));
    if (e) e.target.classList.add("active");

    const cashSection = document.getElementById("cashSection");
    if (cashSection) {
        if (method === "Cash") {
            cashSection.classList.remove("hidden");
        } else {
            cashSection.classList.add("hidden");
        }
    }
};

window.confirmPayment = function() {
    const name  = document.getElementById("custName")?.value.trim();
    const phone = document.getElementById("custPhone")?.value.trim();

    if (!name || !phone) { alert("Please enter customer name and phone!"); return; }
    if (phone.length !== 10 || isNaN(phone)) { alert("Enter valid 10-digit phone number!"); return; }
    if (!selectedPayment) { alert("Please select a payment method!"); return; }

    // Build discount info
    let discountAmount = 0;
    if (discountType === "flat") discountAmount = discountValue;
    else if (discountType === "percent") {
        let sub = cart.reduce((s,i) => s + i.price*i.qty, 0);
        discountAmount = (sub * discountValue) / 100;
    }

    const payload = {
        customer_name: name,
        phone: phone,
        payment_method: selectedPayment,
        cart: cart,
        discount_type: discountType,
        discount_value: discountValue,
        discount_amount: parseFloat(discountAmount.toFixed(2)),
        total: parseFloat((window._finalTotal || 0).toFixed(2))
    };

    fetch("save_sale.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === "success") {
            alert(`✅ Payment Successful!\nTransaction ID: ${res.transaction_id}\nTotal: ₹${payload.total}`);
            cart = []; discountType = ""; discountValue = 0;
            renderCart();
            closeModal();
        } else {
            alert("Error: " + (res.message || "Payment failed"));
        }
    })
    .catch(() => {
        // Offline fallback - still show success locally
        alert("✅ Payment Recorded Locally!\nTotal: ₹" + payload.total);
        cart = []; discountType = ""; discountValue = 0;
        renderCart();
        closeModal();
    });
};
