<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/layout.php';
require_role([1,2]);

$search = trim($_GET['search'] ?? '');
$where  = '';
if ($search) {
    $s = mysqli_real_escape_string($conn, $search);
    $where = "WHERE c.first_name LIKE '%$s%' OR c.last_name LIKE '%$s%' OR c.email LIKE '%$s%'";
}

$customers = $conn->query("
    SELECT c.*,
           COUNT(o.order_id)                                            AS total_orders,
           COALESCE(SUM(CASE WHEN o.order_status='Completed' THEN o.total_amount ELSE 0 END),0) AS total_spent,
           MAX(o.order_date)                                            AS last_order_date
    FROM Customer c
    LEFT JOIN `Order` o ON c.customer_id = o.customer_id
    $where
    GROUP BY c.customer_id
    ORDER BY c.created_at DESC
");
$total_customers = $conn->query("SELECT COUNT(*) FROM Customer")->fetch_row()[0];
$online_customers= $conn->query("SELECT COUNT(*) FROM Customer WHERE customer_type='Online'")->fetch_row()[0];
$with_orders     = $conn->query("SELECT COUNT(DISTINCT customer_id) FROM `Order` WHERE customer_id IS NOT NULL")->fetch_row()[0];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Customers</title>
<link rel="stylesheet" href="/neychurlava/assets/css/style.css">
</head>
<body><div class="wrapper">
<?php sidebar('customers'); ?>
<div class="main">
<?php topbar('Customers','Admin > Customers'); ?>
<div class="content">

<div class="stats-grid">
  <div class="stat-card"><div class="label">Total Customers</div>
    <div class="value"><?= $total_customers ?></div></div>
  <div class="stat-card blue"><div class="label">Online Accounts</div>
    <div class="value"><?= $online_customers ?></div></div>
  <div class="stat-card green"><div class="label">Placed Orders</div>
    <div class="value"><?= $with_orders ?></div></div>
</div>

<div class="card">
  <div class="card-header">
    <h2>All Customers</h2>
  </div>
  <div class="card-body">
    <form method="GET" class="search-bar">
      <input type="text" name="search" class="form-control"
             placeholder="Search by name or email..."
             value="<?= htmlspecialchars($search) ?>">
      <button class="btn btn-primary">Search</button>
      <?php if($search):?><a href="customers.php" class="btn btn-secondary">Clear</a><?php endif;?>
    </form>
    <div class="table-wrap"><table>
      <thead><tr>
        <th>#</th><th>Name</th><th>Email</th><th>Phone</th>
        <th>Type</th><th>Orders</th><th>Total Spent</th>
        <th>Last Order</th><th>Registered</th>
      </tr></thead>
      <tbody>
      <?php while ($r = $customers->fetch_assoc()): ?>
      <tr>
        <td><?= $r['customer_id'] ?></td>
        <td><strong><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></strong></td>
        <td><?= htmlspecialchars($r['email'] ?? '—') ?></td>
        <td><?= htmlspecialchars($r['phone'] ?? '—') ?></td>
        <td><span class="badge <?= $r['customer_type']==='Online'?'badge-info':'badge-secondary' ?>">
          <?= $r['customer_type'] ?></span></td>
        <td><?= $r['total_orders'] ?></td>
        <td>₱<?= number_format($r['total_spent'],2) ?></td>
        <td><?= $r['last_order_date'] ? date('M d, Y',strtotime($r['last_order_date'])) : '—' ?></td>
        <td><?= date('M d, Y',strtotime($r['created_at'])) ?></td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table></div>
  </div>
</div>
</div></div></div>
</body></html>
