<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/layout.php';
require_role([1,2]);

// Update status
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')===  'update_status') {
    $stmt=$conn->prepare("UPDATE `Order` SET order_status=? WHERE order_id=?");
    $stmt->bind_param("si",$_POST['status'],$_POST['order_id']);
    $stmt->execute(); $stmt->close();
}

$filter = $_GET['status'] ?? '';
$where  = $filter ? "WHERE o.order_status='".mysqli_real_escape_string($conn,$filter)."'" : '';

$orders = $conn->query("
    SELECT o.order_id,o.order_date,o.order_type,o.order_status,o.total_amount,o.special_instructions,
           COALESCE(CONCAT(c.first_name,' ',c.last_name),'Walk-in') AS customer,
           CONCAT(s.first_name,' ',s.last_name) AS staff_name
    FROM `Order` o
    LEFT JOIN Customer c ON o.customer_id=c.customer_id
    INNER JOIN Staff s ON o.staff_id=s.staff_id
    $where ORDER BY o.order_date DESC
");

function badge($st){
    $m=['Completed'=>'success','Cancelled'=>'danger','Pending'=>'warning','In-Progress'=>'info'];
    return '<span class="badge badge-'.($m[$st]??'secondary').'">'.$st.'</span>';
}
?><!DOCTYPE html>
<html lang="en">
    <head><meta charset="UTF-8"><title>Orders</title>
    <link rel="stylesheet" href="/neychurlava/assets/css/style.css">
    <link rel="shortcut icon" href="/neychurlava/assets/favicon.png" type="image/x-icon">
</head>
<body><div class="wrapper">
<?php sidebar('orders'); ?>
<div class="main">
<?php topbar('All Orders','Admin > Orders'); ?>
<div class="content">
<div class="card">
    <div class="card-header">
        <h2>Orders</h2>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <?php foreach(['','Pending','In-Progress','Completed','Cancelled'] as $s):
                $lbl=$s===''?'All':$s; $active=$filter===$s?'btn-primary':'btn-secondary'; ?>
            <a href="?status=<?= $s ?>" class="btn btn-sm btn-<?= $active ?>"><?= $lbl ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card-body">
        <div class="table-wrap">
            <table>
        <thead><tr><th>#</th><th>Date</th><th>Type</th><th>Customer</th><th>Handled By</th><th>Total</th><th>Note</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php while ($r=$orders->fetch_assoc()): ?>
        <tr>
            <td>#<?= $r['order_id'] ?></td>
            <td><?= date('M d H:i', strtotime($r['order_date'])) ?></td>
            <td><span class="badge badge-primary"><?= $r['order_type'] ?></span></td>
            <td><?= htmlspecialchars($r['customer']) ?></td>
            <td><?= htmlspecialchars($r['staff_name']) ?></td>
            <td>₱<?= number_format($r['total_amount'],2) ?></td>
            <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($r['special_instructions']??'') ?></td>
            <td><?= badge($r['order_status']) ?></td>
            <td>
                <form method="POST" style="display:flex;gap:4px">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" value="<?= $r['order_id'] ?>">
                    <select name="status" class="form-control" style="padding:4px 8px;font-size:12px">
                        <?php foreach(['Pending','In-Progress','Completed','Cancelled'] as $s): ?>
                        <option <?= $r['order_status']===$s?'selected':'' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-primary">✓</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table></div></div>
</div>
</div></div></div></body></html>
