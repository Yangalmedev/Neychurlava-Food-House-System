<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/layout.php';
require_role([5,6,7]);

// Update order status from kitchen
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='update_status') {
    $stmt=$conn->prepare("UPDATE `Order` SET order_status=? WHERE order_id=?");
    $stmt->bind_param("si",$_POST['status'],$_POST['order_id']);
    $stmt->execute(); $stmt->close();
    header("Location: /neychurlava/kitchen/index.php");
    exit();
}

// Active orders (Pending + In-Progress)
$orders=$conn->query("
    SELECT o.order_id,o.order_date,o.order_type,o.order_status,o.special_instructions,
           COALESCE(CONCAT(c.first_name,' ',c.last_name),'Walk-in') AS customer
    FROM `Order` o LEFT JOIN Customer c ON o.customer_id=c.customer_id
    WHERE o.order_status IN ('Pending','In-Progress')
    ORDER BY o.order_date ASC
");

$completed=$conn->query("SELECT COUNT(*) FROM `Order` WHERE order_status='Completed' AND DATE(order_date)=CURDATE()")->fetch_row()[0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Kitchen</title>
    <link rel="stylesheet" href="/neychurlava/assets/css/style.css">
    <link rel="shortcut icon" href="/neychurlava/assets/favicon.png" type="image/x-icon">
    <meta http-equiv="refresh" content="30">
</head>
<body>
<div class="wrapper">
<?php sidebar('dashboard'); ?>
<div class="main">
<?php topbar('Kitchen Order Queue','Kitchen > Active Orders'); ?>
<div class="content">
<div class="stats-grid">
    <div class="stat-card yellow"><div class="label">Active Orders</div><div class="value" id="activeCount">Loading...</div><div class="sub">Pending + In-Progress</div></div>
    <div class="stat-card green"><div class="label">Completed Today</div><div class="value"><?= $completed ?></div><div class="sub">Orders served today</div></div>
    <div class="stat-card blue"><div class="label">Auto-refresh</div><div class="value">30s</div><div class="sub">Page refreshes automatically</div></div>
</div>

<div class="order-grid" id="orderGrid">
<?php $count=0; while ($o=$orders->fetch_assoc()): $count++;
    $items_res=$conn->query("SELECT oi.quantity,mi.item_name FROM Order_Item oi INNER JOIN Menu_Item mi ON oi.item_id=mi.item_id WHERE oi.order_id=".$o['order_id']);
    $cls=$o['order_status']==='Completed'?'completed':'';
?>
<div class="order-card <?= $cls ?>">
    <div class="order-card-header">
        <div>
            <strong>#<?= $o['order_id'] ?></strong>
            <span class="badge badge-primary" style="margin-left:8px"><?= $o['order_type'] ?></span>
        </div>
        <span class="badge badge-<?= $o['order_status']==='Pending'?'warning':'info' ?>"><?= $o['order_status'] ?></span>
    </div>
    <div class="order-card-body">
        <div style="font-size:11px;color:#888;margin-bottom:8px">
            <?= date('H:i', strtotime($o['order_date'])) ?> &nbsp;·&nbsp; <?= htmlspecialchars($o['customer']) ?>
        </div>
        <ul>
        <?php while ($it=$items_res->fetch_assoc()): ?>
            <li><strong>×<?= $it['quantity'] ?></strong> <?= htmlspecialchars($it['item_name']) ?></li>
        <?php endwhile; ?>
        </ul>
        <?php if ($o['special_instructions']): ?>
        <div style="margin-top:8px;padding:6px 10px;background:#fff3cd;border-radius:6px;font-size:12px">
            📝 <?= htmlspecialchars($o['special_instructions']) ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="order-card-footer">
        <form method="POST" style="display:flex;gap:8px">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
            <?php if ($o['order_status']==='Pending'): ?>
            <button name="status" value="In-Progress" class="btn btn-info btn-sm btn-block">▶ Start Preparing</button>
            <?php else: ?>
            <button name="status" value="Completed" class="btn btn-success btn-sm btn-block">✓ Mark Done</button>
            <?php endif; ?>
        </form>
    </div>
</div>
<?php endwhile; ?>
<?php if ($count===0): ?>
<div style="grid-column:1/-1;text-align:center;padding:60px;color:#aaa">
    <div style="font-size:48px">✅</div>
    <h3>No active orders right now.</h3>
    <p>All orders have been processed.</p>
</div>
<?php endif; ?>
</div>
</div></div></div>
<script>document.getElementById('activeCount').textContent=<?= $count ?>;</script>
</body></html>
