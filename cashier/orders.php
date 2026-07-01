<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/layout.php';
require_role([3,4]);

$orders=$conn->query("
    SELECT o.*,COALESCE(CONCAT(c.first_name,' ',c.last_name),'Walk-in') AS customer
    FROM `Order` o LEFT JOIN Customer c ON o.customer_id=c.customer_id
    ORDER BY o.order_date DESC LIMIT 50
");
function badge($st){$m=['Completed'=>'success','Cancelled'=>'danger','Pending'=>'warning','In-Progress'=>'info'];return '<span class="badge badge-'.($m[$st]??'secondary').'">'.$st.'</span>';}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Orders</title>
    <link rel="stylesheet" href="/neychurlava/assets/css/style.css">
    <link rel="shortcut icon" href="/neychurlava/assets/favicon.png" type="image/x-icon">

</head>
<body><div class="wrapper">
<?php sidebar('orders'); ?>
<div class="main">
<?php topbar('Orders','Cashier > Orders'); ?>
<div class="content">
<div class="card">
    <div class="card-header"><h2>Recent Orders</h2>
        <a href="new_order.php" class="btn btn-success btn-sm">+ New Order</a>
    </div>
    <div class="card-body"><div class="table-wrap"><table>
        <thead><tr><th>#</th><th>Date</th><th>Type</th><th>Customer</th><th>Total</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php while ($r=$orders->fetch_assoc()): ?>
        <tr>
            <td>#<?= $r['order_id'] ?></td>
            <td><?= date('M d H:i',strtotime($r['order_date'])) ?></td>
            <td><span class="badge badge-primary"><?= $r['order_type'] ?></span></td>
            <td><?= htmlspecialchars($r['customer']) ?></td>
            <td>₱<?= number_format($r['total_amount'],2) ?></td>
            <td><?= badge($r['order_status']) ?></td>
            <td>
                <?php if ($r['order_status']==='Pending'): ?>
                <a href="payments.php?order_id=<?= $r['order_id'] ?>" class="btn btn-sm btn-success">Pay</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table></div></div>
</div>
</div></div></div></body></html>
