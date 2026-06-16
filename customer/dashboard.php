<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_customer();

$cid  = (int)$_SESSION['customer_id'];
$name = htmlspecialchars($_SESSION['customer_name']);

// Load customer info
$cust = $conn->query("SELECT * FROM Customer WHERE customer_id=$cid")->fetch_assoc();

// Load order history
$orders = $conn->query("
    SELECT o.order_id, o.order_date, o.order_type, o.fulfillment_method,
           o.order_status, o.total_amount, o.payment_method, o.pickup_name
    FROM `Order` o
    WHERE o.customer_id = $cid
    ORDER BY o.order_date DESC
    LIMIT 20
");

// Stats
$total_orders    = $conn->query("SELECT COUNT(*) FROM `Order` WHERE customer_id=$cid")->fetch_row()[0];
$total_spent     = $conn->query("SELECT COALESCE(SUM(total_amount),0) FROM `Order` WHERE customer_id=$cid AND order_status='Completed'")->fetch_row()[0];
$active_orders   = $conn->query("SELECT COUNT(*) FROM `Order` WHERE customer_id=$cid AND order_status IN ('Pending','In-Progress')")->fetch_row()[0];

function badge($s){
  $m=['Completed'=>'#D1FAE5:#065F46','Cancelled'=>'#FEE2E2:#DC2626',
      'Pending'=>'#FEF3C7:#92400E','In-Progress'=>'#DBEAFE:#1E40AF'];
  [$bg,$tc]=explode(':',$m[$s]??'#F3F4F6:#374151');
  return "<span style='background:$bg;color:$tc;padding:4px 12px;border-radius:20px;font-size:.78rem;font-weight:700'>$s</span>";
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Account — Neychurlava</title>
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
    <a href="/neychurlava/customer/dashboard.php" class="active">My Account</a>
  </div>
  <div class="c-nav-actions">
    <a href="/neychurlava/auth/logout.php" class="btn btn-outline btn-sm">Logout</a>
    <button class="c-cart-btn" onclick="window.location='/neychurlava/customer/menu.php'">
      🛒 Order <span class="c-cart-badge" style="display:none">0</span>
    </button>
  </div>
</nav>

<div style="max-width:1000px;margin:0 auto;padding:40px 5%">

  <!-- WELCOME HEADER -->
  <div style="background:linear-gradient(135deg,#1A2638,#2E4057);border-radius:16px;
              padding:32px 36px;color:#fff;margin-bottom:28px;
              display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px">
    <div>
      <h1 style="font-size:1.5rem;font-weight:900;margin-bottom:4px">
        👋 Welcome back, <?= $name ?>!
      </h1>
      <p style="color:rgba(255,255,255,.7);font-size:.9rem">
        <?= htmlspecialchars($cust['email']) ?> · Customer since <?= date('M Y', strtotime($cust['created_at'])) ?>
      </p>
    </div>
    <a href="/neychurlava/customer/menu.php" class="btn btn-primary">🍜 Order Now</a>
  </div>

  <!-- STATS -->
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px">
    <div style="background:#fff;border-radius:14px;padding:22px 24px;border:1.5px solid #E5E7EB;text-align:center">
      <div style="font-size:2rem;font-weight:900;color:#2E4057"><?= $total_orders ?></div>
      <div style="font-size:.82rem;color:#6B7280;margin-top:3px">Total Orders</div>
    </div>
    <div style="background:#fff;border-radius:14px;padding:22px 24px;border:1.5px solid #E5E7EB;text-align:center">
      <div style="font-size:2rem;font-weight:900;color:#22C55E">₱<?= number_format($total_spent,2) ?></div>
      <div style="font-size:.82rem;color:#6B7280;margin-top:3px">Total Spent</div>
    </div>
    <div style="background:#fff;border-radius:14px;padding:22px 24px;border:1.5px solid #E5E7EB;text-align:center">
      <div style="font-size:2rem;font-weight:900;color:#F4845F"><?= $active_orders ?></div>
      <div style="font-size:.82rem;color:#6B7280;margin-top:3px">Active Orders</div>
    </div>
  </div>

  <!-- ORDER HISTORY -->
  <div style="background:#fff;border-radius:14px;border:1.5px solid #E5E7EB;overflow:hidden">
    <div style="padding:20px 24px;border-bottom:1px solid #E5E7EB;display:flex;
                justify-content:space-between;align-items:center">
      <h2 style="font-size:1.1rem;font-weight:800;color:#2E4057">Order History</h2>
      <a href="/neychurlava/customer/menu.php" class="btn btn-primary btn-sm">+ New Order</a>
    </div>
    <div style="overflow-x:auto">
    <?php if ($orders->num_rows === 0): ?>
    <div style="text-align:center;padding:48px;color:#6B7280">
      <div style="font-size:2.5rem;margin-bottom:12px">🍜</div>
      <p>You haven't placed any orders yet.</p>
      <a href="/neychurlava/customer/menu.php" class="btn btn-primary" style="margin-top:14px">Browse Menu</a>
    </div>
    <?php else: ?>
    <table style="width:100%;border-collapse:collapse;font-size:.9rem">
      <thead>
        <tr style="background:#F9FAFB">
          <th style="padding:12px 16px;text-align:left;font-weight:700;color:#374151;white-space:nowrap">Order</th>
          <th style="padding:12px 16px;text-align:left;font-weight:700;color:#374151">Date</th>
          <th style="padding:12px 16px;text-align:left;font-weight:700;color:#374151">Type</th>
          <th style="padding:12px 16px;text-align:left;font-weight:700;color:#374151">Payment</th>
          <th style="padding:12px 16px;text-align:right;font-weight:700;color:#374151">Total</th>
          <th style="padding:12px 16px;text-align:center;font-weight:700;color:#374151">Status</th>
          <th style="padding:12px 16px;text-align:center;font-weight:700;color:#374151">Track</th>
        </tr>
      </thead>
      <tbody>
      <?php while ($o = $orders->fetch_assoc()): ?>
      <tr style="border-top:1px solid #F0F0F0">
        <td style="padding:12px 16px;font-weight:800;color:#2E4057">
          #<?= str_pad($o['order_id'],5,'0',STR_PAD_LEFT) ?>
        </td>
        <td style="padding:12px 16px;color:#6B7280;white-space:nowrap">
          <?= date('M d, Y', strtotime($o['order_date'])) ?>
        </td>
        <td style="padding:12px 16px">
          <?= $o['fulfillment_method']==='Delivery' ? '🚴 Delivery' : '🥡 Takeout' ?>
        </td>
        <td style="padding:12px 16px;font-size:.82rem;color:#6B7280">
          <?= htmlspecialchars($o['payment_method']) ?>
        </td>
        <td style="padding:12px 16px;text-align:right;font-weight:800;color:#2E4057">
          ₱<?= number_format($o['total_amount'],2) ?>
        </td>
        <td style="padding:12px 16px;text-align:center">
          <?= badge($o['order_status']) ?>
        </td>
        <td style="padding:12px 16px;text-align:center">
          <a href="/neychurlava/customer/track.php?order_id=<?= $o['order_id'] ?>"
             style="color:#F4845F;font-weight:700;font-size:.82rem">Track →</a>
        </td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
    <?php endif; ?>
    </div>
  </div>

  <!-- ACCOUNT INFO -->
  <div style="background:#fff;border-radius:14px;border:1.5px solid #E5E7EB;
              padding:24px;margin-top:20px">
    <h2 style="font-size:1rem;font-weight:800;color:#2E4057;margin-bottom:16px">
      Account Information
    </h2>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;font-size:.9rem">
      <div><span style="color:#6B7280">Name:</span>
        <strong style="margin-left:8px"><?= htmlspecialchars($cust['first_name'].' '.$cust['last_name']) ?></strong></div>
      <div><span style="color:#6B7280">Email:</span>
        <strong style="margin-left:8px"><?= htmlspecialchars($cust['email']) ?></strong></div>
      <div><span style="color:#6B7280">Phone:</span>
        <strong style="margin-left:8px"><?= htmlspecialchars($cust['phone'] ?? '—') ?></strong></div>
      <div><span style="color:#6B7280">Type:</span>
        <strong style="margin-left:8px"><?= htmlspecialchars($cust['customer_type']) ?></strong></div>
      <?php if ($cust['address']): ?>
      <div style="grid-column:1/-1"><span style="color:#6B7280">Address:</span>
        <strong style="margin-left:8px"><?= htmlspecialchars($cust['address']) ?></strong></div>
      <?php endif; ?>
    </div>
  </div>

</div>

<footer class="c-footer"><p>🍽️ Neychurlava Food-House · Abuyog, Leyte</p></footer>
<script src="/neychurlava/assets/js/cart.js"></script>
</body></html>
