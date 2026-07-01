<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/layout.php';
require_role([3,4]);

$sid = $_SESSION['staff_id'];
$my_orders = $conn->query("
    SELECT o.order_id,o.order_date,o.order_type,o.order_status,o.total_amount,
           COALESCE(CONCAT(c.first_name,' ',c.last_name),'Walk-in') AS customer
    FROM `Order` o LEFT JOIN Customer c ON o.customer_id=c.customer_id
    WHERE o.staff_id=$sid ORDER BY o.order_date DESC LIMIT 10
");
$pending = $conn->query("SELECT COUNT(*) FROM `Order` WHERE staff_id=$sid AND order_status='Pending'")->fetch_row()[0];
$today_rev = $conn->query("SELECT COALESCE(SUM(total_amount),0) FROM `Order` WHERE staff_id=$sid AND order_status='Completed' AND DATE(order_date)=CURDATE()")->fetch_row()[0];
$today_cnt = $conn->query("SELECT COUNT(*) FROM `Order` WHERE staff_id=$sid AND DATE(order_date)=CURDATE()")->fetch_row()[0];
function badge($st){$m=['Completed'=>'success','Cancelled'=>'danger','Pending'=>'warning','In-Progress'=>'info'];return '<span class="badge badge-'.($m[$st]??'secondary').'">'.$st.'</span>';}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Cashier</title>
        <link rel="stylesheet" href="/neychurlava/assets/css/style.css">
        <link rel="shortcut icon" href="/neychurlava/assets/favicon.png" type="image/x-icon">
    </head>
<body><div class="wrapper">
<?php sidebar('dashboard'); ?>
<div class="main">
<?php topbar('Cashier Dashboard','Cashier > Dashboard'); ?>
<div class="content">
<div class="stats-grid">
    <div class="stat-card yellow"><div class="label">Pending Orders</div><div class="value"><?= $pending ?></div><div class="sub">Needs processing</div></div>
    <div class="stat-card green"><div class="label">Today's Revenue</div><div class="value">₱<?= number_format($today_rev,2) ?></div><div class="sub">Your completed orders</div></div>
    <div class="stat-card blue"><div class="label">Orders Today</div><div class="value"><?= $today_cnt ?></div><div class="sub">All types</div></div>
</div>

<div class="card">
    <div class="card-header"><h2>My Recent Orders</h2>
        <a href="/neychurlava/cashier/new_order.php" class="btn btn-success btn-sm">+ New Order</a>
    </div>
    <div class="card-body"><div class="table-wrap"><table>
        <thead><tr><th>#</th><th>Date</th><th>Type</th><th>Customer</th><th>Total</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($r=$my_orders->fetch_assoc()): ?>
        <tr>
            <td>#<?= $r['order_id'] ?></td>
            <td><?= date('M d H:i',strtotime($r['order_date'])) ?></td>
            <td><span class="badge badge-primary"><?= $r['order_type'] ?></span></td>
            <td><?= htmlspecialchars($r['customer']) ?></td>
            <td>₱<?= number_format($r['total_amount'],2) ?></td>
            <td><?= badge($r['order_status']) ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table></div></div>
</div>
</div></div></div></body></html>
