<?php
require_once '../config/db.php';

$order   = null;
$items   = [];
$del     = null;
$error   = '';
$order_id_input = trim($_GET['order_id'] ?? $_POST['order_id'] ?? '');

if ($order_id_input !== '') {
    $oid  = (int)$order_id_input;
    $stmt = $conn->prepare("
        SELECT o.order_id, o.order_date, o.order_type, o.fulfillment_method,
               o.order_status, o.total_amount, o.payment_method,
               o.pickup_name, o.customer_phone, o.special_instructions
        FROM `Order` o
        WHERE o.order_id = ? LIMIT 1
    ");
    $stmt->bind_param("i", $oid);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($order) {
        $ir = $conn->query("
            SELECT oi.quantity, mi.item_name, oi.subtotal
            FROM Order_Item oi
            INNER JOIN Menu_Item mi ON oi.item_id = mi.item_id
            WHERE oi.order_id = $oid
        ");
        while ($r = $ir->fetch_assoc()) $items[] = $r;

        if ($order['fulfillment_method'] === 'Delivery') {
            $dr  = $conn->query("SELECT * FROM Delivery WHERE order_id = $oid");
            $del = $dr->fetch_assoc();
        }
    } else {
        $error = 'Order #' . $oid . ' not found. Please check the order ID.';
    }
}

// Status steps
$all_steps = [
    ['key'=>'Pending',      'label'=>'Order Received',    'sub'=>'Your order has been placed.',                   'icon'=>'📋'],
    ['key'=>'In-Progress',  'label'=>'Being Prepared',    'sub'=>'Our kitchen is cooking your food.',             'icon'=>'👨‍🍳'],
    ['key'=>'Completed',    'label'=>'Ready / Delivered', 'sub'=>'Your order is ready or has been delivered.',    'icon'=>'✅'],
];
$delivery_steps = [
    ['key'=>'Pending',           'label'=>'Order Received',   'sub'=>'Waiting for a rider to be assigned.',       'icon'=>'📋'],
    ['key'=>'In-Progress',       'label'=>'Being Prepared',   'sub'=>'Our kitchen is preparing your food.',       'icon'=>'👨‍🍳'],
    ['key'=>'Out for Delivery',  'label'=>'Out for Delivery', 'sub'=>'Your rider is on the way!',                 'icon'=>'🚴'],
    ['key'=>'Completed',         'label'=>'Delivered',        'sub'=>'Your order has been delivered. Enjoy!',     'icon'=>'🎉'],
];

function getStatusStep($status) {
    $map = ['Pending'=>0,'In-Progress'=>1,'Completed'=>3,'Cancelled'=>-1];
    return $map[$status] ?? 0;
}
function getDeliveryStep($ordStatus, $delStatus) {
    if ($ordStatus==='Completed' || $delStatus==='Delivered') return 3;
    if ($delStatus==='Out for Delivery') return 2;
    if ($ordStatus==='In-Progress') return 1;
    return 0;
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Track Order — Neychurlava</title>
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
    <a href="/neychurlava/customer/track.php" class="active">Track Order</a>
  </div>
</nav>

<div class="track-wrap">
  <div class="track-form-card">
    <h2>📍 Track Your Order</h2>
    <p>Enter your Order ID to see the current status of your food.</p>
    <form method="GET" style="display:flex;gap:10px">
      <input type="number" name="order_id" class="form-control"
             placeholder="Enter Order ID (e.g. 62)"
             value="<?= htmlspecialchars($order_id_input) ?>" required
             style="flex:1">
      <button type="submit" class="btn btn-primary">Track →</button>
    </form>
    <?php if ($error): ?>
    <div class="c-alert c-alert-error" style="margin-top:14px"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
  </div>

  <?php if ($order): ?>
  <?php
    $isDelivery  = ($order['fulfillment_method'] === 'Delivery');
    $steps       = $isDelivery ? $delivery_steps : $all_steps;
    $currentStep = $isDelivery
        ? getDeliveryStep($order['order_status'], $del['delivery_status'] ?? '')
        : getStatusStep($order['order_status']);
    $isCancelled = ($order['order_status'] === 'Cancelled');
  ?>

  <!-- ORDER STATUS CARD -->
  <div class="status-track">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px">
      <div>
        <h3 style="font-size:1.3rem;font-weight:900;color:var(--pri)">
          Order #<?= str_pad($order['order_id'],5,'0',STR_PAD_LEFT) ?>
        </h3>
        <p style="color:var(--muted);font-size:.88rem;margin-top:3px">
          Placed on <?= date('M d, Y h:i A', strtotime($order['order_date'])) ?>
        </p>
      </div>
      <?php if ($isCancelled): ?>
      <span class="status-pill" style="background:#FEE2E2;color:#DC2626">Cancelled</span>
      <?php else: ?>
      <span class="status-pill pill-pending"><?= htmlspecialchars($order['order_status']) ?></span>
      <?php endif; ?>
    </div>

    <!-- STATUS STEPS -->
    <?php if (!$isCancelled): ?>
    <div class="status-steps" style="margin-bottom:24px">
      <?php foreach ($steps as $i => $step):
        $done   = $i <  $currentStep;
        $active = $i === $currentStep;
        $pend   = $i >  $currentStep;
        $dotClass = $done ? 'done' : ($active ? 'active' : 'pending');
        $lineClass = $done ? 'done' : '';
      ?>
      <div class="status-step">
        <div class="step-dot <?= $dotClass ?>">
          <?= $done ? '✓' : ($active ? $step['icon'] : '·') ?>
        </div>
        <div class="step-line <?= $lineClass ?>"></div>
        <div class="step-info" style="padding-top:6px">
          <div class="step-title" style="<?= $active?'color:var(--acc)':'' ?>">
            <?= $step['label'] ?>
          </div>
          <div class="step-sub"><?= $step['sub'] ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ORDER DETAILS -->
    <div style="background:#F9FAFB;border-radius:var(--r-sm);padding:16px">
      <div style="font-weight:700;color:var(--pri);margin-bottom:10px">Order Details</div>
      <?php foreach ($items as $it): ?>
      <div class="cd-row">
        <span class="cd-label"><?= htmlspecialchars($it['item_name']) ?> × <?= $it['quantity'] ?></span>
        <span class="cd-value">₱<?= number_format($it['subtotal'],2) ?></span>
      </div>
      <?php endforeach; ?>
      <div class="cd-row" style="border-top:2px solid var(--pri);margin-top:8px;padding-top:10px">
        <span class="cd-label" style="font-weight:900;color:var(--pri)">Total</span>
        <span class="cd-value" style="color:var(--acc);font-size:1.1rem">
          ₱<?= number_format($order['total_amount'],2) ?>
        </span>
      </div>
    </div>

    <div style="margin-top:14px;display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:.88rem">
      <div style="background:#EFF6FF;padding:12px;border-radius:var(--r-sm)">
        <strong style="color:var(--pri)">Service</strong><br>
        <?= $isDelivery ? '🚴 Delivery' : '🥡 Takeout' ?>
      </div>
      <div style="background:#FFF5F0;padding:12px;border-radius:var(--r-sm)">
        <strong style="color:var(--pri)">Payment</strong><br>
        <?= htmlspecialchars($order['payment_method']) ?>
      </div>
      <?php if ($isDelivery && $del): ?>
      <div style="background:#F9FAFB;padding:12px;border-radius:var(--r-sm);grid-column:1/-1">
        <strong style="color:var(--pri)">📍 Delivery Address</strong><br>
        <?= htmlspecialchars($del['delivery_address']) ?>
      </div>
      <?php elseif (!$isDelivery && $order['pickup_name']): ?>
      <div style="background:#F9FAFB;padding:12px;border-radius:var(--r-sm);grid-column:1/-1">
        <strong style="color:var(--pri)">🥡 Pickup Name</strong><br>
        <?= htmlspecialchars($order['pickup_name']) ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div style="margin-top:20px;text-align:center">
    <a href="/neychurlava/customer/menu.php" class="btn btn-primary">🍜 Order Again</a>
  </div>
  <?php endif; ?>
</div>

<footer class="c-footer"><p>🍽️ Neychurlava Food-House · Abuyog, Leyte</p></footer>
</body></html>
