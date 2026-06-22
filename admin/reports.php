<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/layout.php';
require_role([1,2]);

$monthly = $conn->query("
    SELECT DATE_FORMAT(order_date,'%Y-%m') AS month,
           COUNT(*) AS total_orders,
           SUM(CASE WHEN order_status='Completed' THEN total_amount ELSE 0 END) AS revenue,
           SUM(CASE WHEN order_status='Completed' THEN 1 ELSE 0 END) AS completed,
           SUM(CASE WHEN order_status='Cancelled' THEN 1 ELSE 0 END) AS cancelled
    FROM `Order` GROUP BY month ORDER BY month DESC
");

$by_type = $conn->query("
    SELECT order_type, COUNT(*) AS cnt,
           SUM(CASE WHEN order_status='Completed' THEN total_amount ELSE 0 END) AS rev
    FROM `Order` GROUP BY order_type
");

$top_items = $conn->query("
    SELECT mi.item_name, cat.category_name,
           SUM(oi.quantity) AS total_sold, SUM(oi.subtotal) AS revenue
    FROM Order_Item oi
    INNER JOIN Menu_Item mi  ON oi.item_id=mi.item_id
    INNER JOIN Category  cat ON mi.category_id=cat.category_id
    INNER JOIN `Order`   o   ON oi.order_id=o.order_id
    WHERE o.order_status='Completed'
    GROUP BY mi.item_id,mi.item_name,cat.category_name
    ORDER BY total_sold DESC LIMIT 10
");

$pay_methods = $conn->query("
    SELECT payment_method, COUNT(*) AS cnt, SUM(amount_paid) AS total
    FROM Payment WHERE payment_status='Paid' GROUP BY payment_method
");
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Reports</title>
    <link rel="shortcut icon" href="/neychurlava/assets/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="/neychurlava/assets/css/style.css">
</head>
<body><div class="wrapper">
<?php sidebar('reports'); ?>
<div class="main">
<?php topbar('Reports & Analytics','Admin > Reports'); ?>
<div class="content">

<!-- Order Type Summary -->
<div class="stats-grid">
<?php while ($r=$by_type->fetch_assoc()): ?>
<div class="stat-card blue">
    <div class="label"><?= $r['order_type'] ?></div>
    <div class="value"><?= $r['cnt'] ?> orders</div>
    <div class="sub">₱<?= number_format($r['rev'],2) ?> revenue</div>
</div>
<?php endwhile; ?>
</div>

<!-- Monthly Sales -->
<div class="card">
    <div class="card-header"><h2>Monthly Sales Summary</h2></div>
    <div class="card-body"><div class="table-wrap"><table>
        <thead><tr><th>Month</th><th>Total Orders</th><th>Completed</th><th>Cancelled</th><th>Revenue</th></tr></thead>
        <tbody>
        <?php while ($r=$monthly->fetch_assoc()): ?>
        <tr>
            <td><?= $r['month'] ?></td>
            <td><?= $r['total_orders'] ?></td>
            <td><span class="badge badge-success"><?= $r['completed'] ?></span></td>
            <td><span class="badge badge-danger"><?= $r['cancelled'] ?></span></td>
            <td>₱<?= number_format($r['revenue'],2) ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table></div></div>
</div>

<!-- Top Items -->
<div class="card">
    <div class="card-header"><h2>Top 10 Best-Selling Items</h2></div>
    <div class="card-body"><div class="table-wrap"><table>
        <thead><tr><th>Rank</th><th>Item Name</th><th>Category</th><th>Qty Sold</th><th>Revenue</th></tr></thead>
        <tbody>
        <?php $rank=1; while ($r=$top_items->fetch_assoc()): ?>
        <tr>
            <td><?= $rank++ ?></td>
            <td><?= htmlspecialchars($r['item_name']) ?></td>
            <td><?= htmlspecialchars($r['category_name']) ?></td>
            <td><?= $r['total_sold'] ?></td>
            <td>₱<?= number_format($r['revenue'],2) ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table></div></div>
</div>

<!-- Payment Methods -->
<div class="card">
    <div class="card-header"><h2>Revenue by Payment Method</h2></div>
    <div class="card-body"><div class="table-wrap"><table>
        <thead><tr><th>Method</th><th>Transactions</th><th>Total Collected</th></tr></thead>
        <tbody>
        <?php while ($r=$pay_methods->fetch_assoc()): ?>
        <tr>
            <td><span class="badge badge-info"><?= $r['payment_method'] ?></span></td>
            <td><?= $r['cnt'] ?></td>
            <td>₱<?= number_format($r['total'],2) ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table></div></div>
</div>

</div></div></div></body></html>
