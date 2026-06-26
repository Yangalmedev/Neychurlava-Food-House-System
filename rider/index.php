<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_role([8,9]); // Delivery Supervisor (8) or Delivery Rider (9)

$msg = ''; $msg_type = 'success';

// Update delivery status
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'accept') {
        $did = (int)$_POST['delivery_id'];
        $oid = (int)$_POST['order_id'];
        // Assign rider and mark as Out for Delivery
        $conn->begin_transaction();
        try {
            $s1 = $conn->prepare("UPDATE Delivery SET delivery_status='Out for Delivery' WHERE delivery_id=?");
            $s1->bind_param("i",$did); $s1->execute(); $s1->close();
            $s2 = $conn->prepare("UPDATE `Order` SET order_status='In-Progress', staff_id=? WHERE order_id=?");
            $s2->bind_param("ii",$_SESSION['staff_id'],$oid); $s2->execute(); $s2->close();
            $conn->commit();
            $msg = 'Delivery accepted. You are now Out for Delivery.';
        } catch(Exception $e) { $conn->rollback(); $msg=$e->getMessage(); $msg_type='danger'; }
    }
    if ($_POST['action'] === 'delivered') {
        $did = (int)$_POST['delivery_id'];
        $oid = (int)$_POST['order_id'];
        $conn->begin_transaction();
        try {
            $s1 = $conn->prepare("UPDATE Delivery SET delivery_status='Delivered', delivered_at=NOW() WHERE delivery_id=?");
            $s1->bind_param("i",$did); $s1->execute(); $s1->close();
            $s2 = $conn->prepare("UPDATE `Order` SET order_status='Completed' WHERE order_id=?");
            $s2->bind_param("i",$oid); $s2->execute(); $s2->close();
            $conn->commit();
            $msg = 'Order marked as Delivered. ';
        } catch(Exception $e) { $conn->rollback(); $msg=$e->getMessage(); $msg_type='danger'; }
    }
}

// Load pending + active deliveries
$pending = $conn->query("
    SELECT d.delivery_id, d.order_id, d.delivery_address, d.contact_number,
           d.delivery_status, d.estimated_time, d.delivered_at,
           o.order_date, o.total_amount, o.payment_method, o.order_status,
           o.special_instructions
    FROM Delivery d
    INNER JOIN `Order` o ON d.order_id = o.order_id
    WHERE d.delivery_status IN ('Pending','Out for Delivery')
    ORDER BY o.order_date ASC
");

// Load today's completed deliveries
$completed_today = $conn->query("
    SELECT COUNT(*) AS cnt FROM Delivery d
    INNER JOIN `Order` o ON d.order_id=o.order_id
    WHERE d.delivery_status='Delivered' AND DATE(d.delivered_at)=CURDATE()
    AND o.staff_id=" . (int)$_SESSION['staff_id']
)->fetch_assoc()['cnt'];

$active_count = $conn->query("
    SELECT COUNT(*) AS cnt FROM Delivery WHERE delivery_status='Out for Delivery'
")->fetch_assoc()['cnt'];

$pending_count = $conn->query("
    SELECT COUNT(*) AS cnt FROM Delivery WHERE delivery_status='Pending'
")->fetch_assoc()['cnt'];

// Get items per order (for display)
function getOrderItems($conn, $order_id) {
    $r = $conn->query("
        SELECT mi.item_name, oi.quantity
        FROM Order_Item oi INNER JOIN Menu_Item mi ON oi.item_id=mi.item_id
        WHERE oi.order_id=" . (int)$order_id
    );
    $items = [];
    while ($row = $r->fetch_assoc()) $items[] = $row['quantity'].'× '.$row['item_name'];
    return implode(', ', $items);
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Rider Dashboard — Neychurlava</title>
<link rel="stylesheet" href="/neychurlava/assets/css/customer.css">
<meta http-equiv="refresh" content="45">
</head>
<body style="background:#F1F5F9">

<!-- TOP BAR -->
<div style="background:var(--pri-d);color:#fff;padding:12px 5%;display:flex;justify-content:space-between;align-items:center">
  <span style="font-size:.85rem;opacity:.7">🍽️ Neychurlava Food-House — Rider Portal</span>
  <span style="font-size:.82rem;opacity:.7">Auto-refreshes every 45s · <a href="/neychurlava/auth/logout.php" style="color:rgba(255,255,255,.8)">Logout</a></span>
</div>

<div class="rider-wrap">

  <!-- HEADER -->
  <div class="rider-header">
    <div>
      <h1>🚴 Rider Dashboard</h1>
      <p>Welcome, <?= htmlspecialchars($_SESSION['staff_name']) ?> · <?= date('l, M d Y') ?></p>
    </div>
    <div class="rider-stats">
      <div class="r-stat">
        <div class="rs-num"><?= $pending_count ?></div>
        <div class="rs-label">Pending</div>
      </div>
      <div class="r-stat">
        <div class="rs-num"><?= $active_count ?></div>
        <div class="rs-label">On the Way</div>
      </div>
      <div class="r-stat">
        <div class="rs-num"><?= $completed_today ?></div>
        <div class="rs-label">Done Today</div>
      </div>
    </div>
  </div>

  <?php if ($msg): ?>
  <div class="c-alert c-alert-<?= $msg_type==='success'?'success':'error' ?>"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <!-- DELIVERIES LIST -->
  <?php $count = 0; while ($d = $pending->fetch_assoc()): $count++;
    $isOut = ($d['delivery_status'] === 'Out for Delivery');
    $statusClass = $isOut ? 'pill-out' : 'pill-pending';
    $itemsList = getOrderItems($conn, $d['order_id']);
  ?>
  <div class="delivery-card">
    <div class="dc-head">
      <div>
        <div class="dc-order">Order #<?= str_pad($d['order_id'],5,'0',STR_PAD_LEFT) ?></div>
        <div class="dc-date"><?= date('M d, Y h:i A', strtotime($d['order_date'])) ?></div>
      </div>
      <span class="status-pill <?= $statusClass ?>"><?= $d['delivery_status'] ?></span>
    </div>

    <div class="dc-address">
      <span class="icon">📍</span>
      <div>
        <div class="addr-text"><?= htmlspecialchars($d['delivery_address']) ?></div>
        <div class="addr-phone">📞 <?= htmlspecialchars($d['contact_number']) ?></div>
      </div>
    </div>

    <div class="dc-items">🍜 <?= htmlspecialchars($itemsList) ?></div>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
      <div class="dc-total">₱<?= number_format($d['total_amount'],2) ?></div>
      <div style="background:#FEF3C7;color:#92400E;padding:5px 12px;border-radius:20px;font-size:.8rem;font-weight:700">
        💵 <?= htmlspecialchars($d['payment_method']) ?>
      </div>
    </div>

    <?php if ($d['special_instructions']): ?>
    <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:10px;
                font-size:.83rem;color:#92400E;margin-bottom:12px">
      📝 <?= htmlspecialchars($d['special_instructions']) ?>
    </div>
    <?php endif; ?>

    <div class="dc-actions">
      <?php if (!$isOut): ?>
      <form method="POST" style="flex:1">
        <input type="hidden" name="action" value="accept">
        <input type="hidden" name="delivery_id" value="<?= $d['delivery_id'] ?>">
        <input type="hidden" name="order_id"    value="<?= $d['order_id'] ?>">
        <button class="btn btn-primary btn-full btn-sm"
          onclick="return confirm('Accept this delivery?')">
          🚴 Accept & Go for Delivery
        </button>
      </form>
      <?php else: ?>
      <form method="POST" style="flex:1">
        <input type="hidden" name="action" value="delivered">
        <input type="hidden" name="delivery_id" value="<?= $d['delivery_id'] ?>">
        <input type="hidden" name="order_id"    value="<?= $d['order_id'] ?>">
        <button class="btn btn-green btn-full btn-sm"
          onclick="return confirm('Mark this order as delivered?')">
          ✅ Mark as Delivered
        </button>
      </form>
      <?php endif; ?>
    </div>
  </div>
  <?php endwhile; ?>

  <?php if ($count === 0): ?>
  <div style="text-align:center;padding:60px 20px;background:#fff;border-radius:var(--r);border:1.5px solid var(--border)">
    <div style="font-size:3rem;margin-bottom:12px">✅</div>
    <h3 style="color:var(--pri);font-weight:800;margin-bottom:6px">No pending deliveries</h3>
    <p style="color:var(--muted)">All caught up! New orders will appear here automatically.</p>
  </div>
  <?php endif; ?>

</div>

<div style="text-align:center;padding:20px;color:var(--muted);font-size:.82rem">
  🍽️ Neychurlava Food-House · Rider Portal · Page refreshes every 45 seconds
</div>
</body></html>
