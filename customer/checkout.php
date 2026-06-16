<?php
require_once '../config/db.php';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Checkout — Neychurlava Food-House</title>
<link rel="stylesheet" href="/neychurlava/assets/css/customer.css">
</head>
<body>

<nav class="c-nav">
  <a href="/neychurlava/customer/landing.php" class="c-nav-brand">
    <span>🍽️</span> Neychurlava
  </a>
  <div class="c-nav-links">
    <a href="/neychurlava/customer/landing.php">Home</a>
    <a href="/neychurlava/customer/menu.php">← Back to Menu</a>
  </div>
  <div class="c-nav-actions">
    <button class="c-cart-btn" onclick="window.location='/neychurlava/customer/menu.php'">
      🛒 Edit Cart <span class="c-cart-badge" style="display:none">0</span>
    </button>
  </div>
</nav>

<div style="max-width:1100px;margin:0 auto;padding:40px 5%">
  <h1 style="font-size:1.7rem;font-weight:900;color:var(--pri);margin-bottom:28px">
    🧾 Checkout
  </h1>

  <div id="emptyMsg" style="display:none;text-align:center;padding:60px">
    <p style="font-size:1.1rem;color:var(--muted)">Your cart is empty. 
      <a href="/neychurlava/customer/menu.php" style="color:var(--acc);font-weight:700">Go back to menu</a>
    </p>
  </div>

  <div class="checkout-wrap" id="checkoutContent">

    <!-- LEFT: CHECKOUT FORM -->
    <div>

      <!-- STEP 1: FULFILLMENT METHOD -->
      <div class="c-panel" style="margin-bottom:20px">
        <h2>Step 1 — How do you want your order?</h2>
        <div class="fulfill-choice">
          <label class="fulfill-opt" id="optDelivery" onclick="selectFulfill('Delivery')">
            <input type="radio" name="fulfillment" value="Delivery" id="radioDelivery">
            <div class="fo-icon">🚴</div>
            <div class="fo-title">Delivery</div>
            <div class="fo-sub">We deliver to your address</div>
          </label>
          <label class="fulfill-opt" id="optTakeout" onclick="selectFulfill('Takeout')">
            <input type="radio" name="fulfillment" value="Takeout" id="radioTakeout">
            <div class="fo-icon">🥡</div>
            <div class="fo-title">Walk-In / Takeout</div>
            <div class="fo-sub">Pick up at the counter</div>
          </label>
        </div>

        <!-- DELIVERY FIELDS -->
        <div class="form-section" id="deliveryFields">
          <div class="form-group">
            <label>Full Delivery Address <span style="color:red">*</span></label>
            <textarea class="form-control" id="del_address"
              placeholder="House No., Street, Barangay, Abuyog, Leyte" rows="3"></textarea>
          </div>
          <div class="form-group">
            <label>Contact Number <span style="color:red">*</span></label>
            <input type="tel" class="form-control" id="del_phone"
              placeholder="e.g. 09171234567">
          </div>
          <div class="payment-notice">
            <span class="pn-icon">💵</span>
            <div class="pn-text">
              <div class="pn-title">Cash on Delivery (COD) Only</div>
              <p>For delivery orders, payment is collected by our rider when your food arrives. No online payment required.</p>
            </div>
          </div>
          <div class="payment-badge">💵 Payment: Cash on Delivery (COD)</div>
        </div>

        <!-- TAKEOUT FIELDS -->
        <div class="form-section" id="takeoutFields">
          <div class="form-group">
            <label>Your Name (for pickup) <span style="color:red">*</span></label>
            <input type="text" class="form-control" id="pickup_name"
              placeholder="Name to call when your order is ready">
          </div>
          <div class="form-group">
            <label>Contact Number <span style="color:red">*</span></label>
            <input type="tel" class="form-control" id="takeout_phone"
              placeholder="e.g. 09171234567">
          </div>
          <div class="pickup-info">
            <strong>⏱ Estimated pickup time: 15–25 minutes</strong>
            Your order will be ready at the counter. Please present your Order ID when you arrive.
          </div>
          <div class="payment-notice" style="margin-top:14px">
            <span class="pn-icon">🏪</span>
            <div class="pn-text">
              <div class="pn-title">Cash at Counter Only</div>
              <p>For takeout orders, payment is made at the counter when you pick up your food.</p>
            </div>
          </div>
          <div class="payment-badge">🏪 Payment: Cash at Counter (upon pickup)</div>
        </div>

      </div>

      <!-- STEP 2: SPECIAL INSTRUCTIONS -->
      <div class="c-panel">
        <h2>Step 2 — Any special instructions?</h2>
        <div class="form-group" style="margin:0">
          <textarea class="form-control" id="special_instructions"
            placeholder="e.g. Less spicy, no onions, extra rice... (optional)"
            rows="3"></textarea>
        </div>
      </div>
    </div>

    <!-- RIGHT: ORDER SUMMARY -->
    <div>
      <div class="c-panel" style="position:sticky;top:80px">
        <h2>Your Order</h2>
        <div id="summaryItems"></div>
        <div class="summary-total">
          <span class="label">Total</span>
          <span class="amount" id="summaryTotal">₱0.00</span>
        </div>
        <div id="paymentSummary" style="margin-top:16px;display:none">
          <div id="paymentBadgeSummary" class="payment-badge" style="margin-bottom:14px"></div>
        </div>
        <button class="btn btn-primary btn-full" style="margin-top:20px;font-size:1.05rem;padding:16px"
          onclick="placeOrder()">
          ✅ Place Order
        </button>
        <p style="font-size:.78rem;color:var(--muted);text-align:center;margin-top:12px">
          By placing your order, you agree to pay the amount shown above.
        </p>
      </div>
    </div>
  </div>
</div>

<footer class="c-footer"><p>🍽️ Neychurlava Food-House · Abuyog, Leyte</p></footer>

<script src="/neychurlava/assets/js/cart.js"></script>
<script>
let selectedFulfill = '';

function selectFulfill(type) {
  selectedFulfill = type;
  document.getElementById('radioDelivery').checked = (type === 'Delivery');
  document.getElementById('radioTakeout').checked  = (type === 'Takeout');
  document.getElementById('optDelivery').classList.toggle('selected', type === 'Delivery');
  document.getElementById('optTakeout').classList.toggle('selected',  type === 'Takeout');
  document.getElementById('deliveryFields').classList.toggle('visible', type === 'Delivery');
  document.getElementById('takeoutFields').classList.toggle('visible',  type === 'Takeout');
  // Update summary payment badge
  const badge = document.getElementById('paymentBadgeSummary');
  const ps    = document.getElementById('paymentSummary');
  if (type === 'Delivery') {
    badge.innerHTML = '💵 Payment: Cash on Delivery (COD)';
    ps.style.display = 'block';
  } else {
    badge.innerHTML = '🏪 Payment: Cash at Counter (upon pickup)';
    ps.style.display = 'block';
  }
}

function renderSummary() {
  const cart = getCart();
  if (cart.length === 0) {
    document.getElementById('emptyMsg').style.display = 'block';
    document.getElementById('checkoutContent').style.display = 'none';
    return;
  }
  const el = document.getElementById('summaryItems');
  el.innerHTML = cart.map(i => `
    <div class="oi-row">
      <div>
        <div class="oi-name">${i.emoji} ${i.name}</div>
        <div class="oi-qty">× ${i.qty}  ·  ₱${i.price.toFixed(2)} each</div>
      </div>
      <div class="oi-sub">₱${(i.price * i.qty).toFixed(2)}</div>
    </div>`).join('');
  document.getElementById('summaryTotal').textContent = '₱' + cartTotal().toFixed(2);
}

async function placeOrder() {
  const cart = getCart();
  if (cart.length === 0)      { alert('Your cart is empty.'); return; }
  if (!selectedFulfill)       { alert('Please choose Delivery or Takeout.'); return; }

  let payload = {
    fulfillment_method: selectedFulfill,
    items: cart,
    total: cartTotal(),
    special_instructions: document.getElementById('special_instructions').value.trim(),
    payment_method: selectedFulfill === 'Delivery' ? 'Cash on Delivery' : 'Cash at Counter',
  };

  if (selectedFulfill === 'Delivery') {
    const addr  = document.getElementById('del_address').value.trim();
    const phone = document.getElementById('del_phone').value.trim();
    if (!addr)  { alert('Please enter your delivery address.'); return; }
    if (!phone) { alert('Please enter your contact number.'); return; }
    payload.delivery_address = addr;
    payload.customer_phone   = phone;
  } else {
    const name  = document.getElementById('pickup_name').value.trim();
    const phone = document.getElementById('takeout_phone').value.trim();
    if (!name)  { alert('Please enter your name for pickup.'); return; }
    if (!phone) { alert('Please enter your contact number.'); return; }
    payload.pickup_name    = name;
    payload.customer_phone = phone;
  }

  const btn = document.querySelector('#checkoutContent .btn-primary');
  btn.textContent = '⏳ Placing order...';
  btn.disabled = true;

  try {
    const res  = await fetch('/neychurlava/customer/process_order.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) {
      clearCart();
      window.location = '/neychurlava/customer/confirm.php?order_id=' + data.order_id;
    } else {
      alert('Error: ' + data.message);
      btn.textContent = '✅ Place Order';
      btn.disabled = false;
    }
  } catch (e) {
    alert('Network error. Please try again.');
    btn.textContent = '✅ Place Order';
    btn.disabled = false;
  }
}

document.addEventListener('DOMContentLoaded', renderSummary);
</script>
</body></html>
