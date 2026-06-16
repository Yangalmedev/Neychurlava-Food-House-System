<?php
require_once '../config/db.php';

$order_id = (int)($_GET['order_id'] ?? 0);
if (!$order_id) { header('Location: /neychurlava/customer/landing.php'); exit(); }

$stmt = $conn->prepare("
    SELECT o.order_id, o.order_date, o.order_type, o.fulfillment_method,
           o.order_status, o.total_amount, o.payment_method,
           o.pickup_name, o.customer_phone, o.special_instructions
    FROM `Order` o
    WHERE o.order_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) { header('Location: /neychurlava/customer/landing.php'); exit(); }

// Get order items
$items_res = $conn->query("
    SELECT oi.quantity, oi.unit_price, oi.subtotal, mi.item_name
    FROM Order_Item oi
    INNER JOIN Menu_Item mi ON oi.item_id = mi.item_id
    WHERE oi.order_id = $order_id
");

// Get delivery info
$del = null;
if ($order['fulfillment_method'] === 'Delivery') {
    $dr = $conn->query("SELECT * FROM Delivery WHERE order_id = $order_id");
    $del = $dr->fetch_assoc();
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Order Confirmed! — Neychurlava</title>
<link rel="stylesheet" href="/neychurlava/assets/css/customer.css">
</head>
<body>

<nav class="c-nav">
  <a href="/neychurlava/customer/landing.php" class="c-nav-brand">
    <span>🍽️</span> Neychurlava
  </a>
  <div class="c-nav-links">
    <a href="/neychurlava/customer/landing.php">Home</a>
    <a href="/neychurlava/customer/menu.php">Menu</a>
    <a href="/neychurlava/customer/track.php">Track Order</a>
  </div>
</nav>

<div class="confirm-wrap">
  <div class="confirm-icon">🎉</div>
  <h1>Order Confirmed!</h1>
  <p>Your order has been received. Keep your Order ID safe — you'll need it to track your order.</p>

  <div class="order-id-badge">#<?= str_pad($order_id, 5, '0', STR_PAD_LEFT) ?></div>

  <div class="confirm-details">
    <div class="cd-row">
      <span class="cd-label">Order ID</span>
      <span class="cd-value">#<?= str_pad($order_id, 5, '0', STR_PAD_LEFT) ?></span>
    </div>
    <div class="cd-row">
      <span class="cd-label">Date & Time</span>
      <span class="cd-value"><?= date('M d, Y h:i A', strtotime($order['order_date'])) ?></span>
    </div>
    <div class="cd-row">
      <span class="cd-label">Service Type</span>
      <span class="cd-value">
        <?= $order['fulfillment_method'] === 'Delivery' ? '🚴 Delivery' : '🥡 Walk-In / Takeout' ?>
      </span>
    </div>
    <div class="cd-row">
      <span class="cd-label">Payment</span>
      <span class="cd-value">
        <?= $order['fulfillment_method'] === 'Delivery'
            ? '💵 Cash on Delivery (COD)'
            : '🏪 Cash at Counter' ?>
      </span>
    </div>
    <?php if ($order['fulfillment_method'] === 'Delivery' && $del): ?>
    <div class="cd-row">
      <span class="cd-label">Deliver To</span>
      <span class="cd-value"><?= htmlspecialchars($del['delivery_address']) ?></span>
    </div>
    <div class="cd-row">
      <span class="cd-label">Contact</span>
      <span class="cd-value"><?= htmlspecialchars($order['customer_phone'] ?? '') ?></span>
    </div>
    <?php elseif ($order['fulfillment_method'] === 'Takeout'): ?>
    <div class="cd-row">
      <span class="cd-label">Pickup Name</span>
      <span class="cd-value"><?= htmlspecialchars($order['pickup_name'] ?? '') ?></span>
    </div>
    <div class="cd-row">
      <span class="cd-label">Est. Ready In</span>
      <span class="cd-value">⏱ 15–25 minutes</span>
    </div>
    <?php endif; ?>
    <?php while ($it = $items_res->fetch_assoc()): ?>
    <div class="cd-row">
      <span class="cd-label"><?= htmlspecialchars($it['item_name']) ?> × <?= $it['quantity'] ?></span>
      <span class="cd-value">₱<?= number_format($it['subtotal'],2) ?></span>
    </div>
    <?php endwhile; ?>
    <div class="cd-row" style="border-top:2px solid var(--pri);padding-top:12px;margin-top:4px">
      <span class="cd-label" style="font-weight:900;color:var(--pri);font-size:1rem">Total</span>
      <span class="cd-value" style="font-size:1.2rem;color:var(--acc)">
        ₱<?= number_format($order['total_amount'],2) ?>
      </span>
    </div>
  </div>

  <?php if ($order['fulfillment_method'] === 'Delivery'): ?>
  <div class="payment-notice" style="text-align:left;margin-bottom:24px">
    <span class="pn-icon">💵</span>
    <div class="pn-text">
      <div class="pn-title">Cash on Delivery — Pay when it arrives</div>
      <p>Please prepare <strong>₱<?= number_format($order['total_amount'],2) ?></strong>
         in cash. Our rider will collect payment upon delivery.</p>
    </div>
  </div>
  <?php else: ?>
  <div class="pickup-info" style="text-align:left;margin-bottom:24px">
    <strong>🏪 Cash at Counter — Pay when you pick up</strong>
    Please proceed to our counter and present Order ID
    <strong>#<?= str_pad($order_id, 5, '0', STR_PAD_LEFT) ?></strong>.
    Have <strong>₱<?= number_format($order['total_amount'],2) ?></strong> ready in cash.
  </div>
  <?php endif; ?>

  <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center">
    <a href="/neychurlava/customer/track.php?order_id=<?= $order_id ?>"
       class="btn btn-dark">📍 Track My Order</a>
    <a href="/neychurlava/customer/menu.php" class="btn btn-outline">🍜 Order Again</a>
  </div>
</div>

<footer class="c-footer"><p>🍽️ Neychurlava Food-House · Abuyog, Leyte</p></footer>
</body></html>
