<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/layout.php';

require_role([1,2]);

// FETCH DASHBOARD STATISTICS
// Array to store key performance metrics
$stats = [];
// array of SQL queries to gather business metrics from different tables
$q = [
    'total_orders'      => "SELECT COUNT(*) FROM `Order`",
    'completed_orders'  => "SELECT COUNT(*) FROM `Order` WHERE order_status='Completed'",

    // SUM total_amount from completed orders. COALESCE ensures it returns 0 instead of NULL if empty.
    'revenue_total'     => "SELECT COALESCE(SUM(total_amount),0) FROM `Order` WHERE order_status='Completed'",
    'active_orders'     => "SELECT COUNT(*) FROM `Order` WHERE order_status IN ('Pending','In-Progress')",

    // Count ingredients where stock level has dropped to or below the safety reorder limit
    'low_stock'         => "SELECT COUNT(*) FROM Inventory WHERE quantity <= reorder_level",
    'total_staff'       => "SELECT COUNT(*) FROM Staff WHERE status='Active'",
    'total_customers'   => "SELECT COUNT(*) FROM Customer",
    'deliveries_out'    => "SELECT COUNT(*) FROM Delivery WHERE delivery_status='Out for Delivery'",
];

// Loop through every query, run it against the database, and save the result into $stats
foreach ($q as $key => $sql) {
    $r = $conn->query($sql);
    $stats[$key] = $r->fetch_row()[0]; // Extract the single numeric result from the query
}

// FETCH RECENT ORDERS
// Query the 8 most recent orders, merging Customer and Staff details via table joins
$recent = $conn->query("
    SELECT o.order_id, o.order_date, o.order_type, o.order_status, o.total_amount,
           -- If customer_id is null, default the name string to 'Walk-in'
           COALESCE(CONCAT(c.first_name,' ',c.last_name),'Walk-in') AS customer,
           CONCAT(s.first_name,' ',s.last_name) AS staff_name
    FROM `Order` o
    LEFT JOIN Customer c ON o.customer_id = c.customer_id
    INNER JOIN Staff s ON o.staff_id = s.staff_id
    ORDER BY o.order_date DESC LIMIT 8
");

// FETCH LOW STOCK ITEMS
// Query the top 5 critically low inventory ingredients, sorted by lowest quantity first
$lowstock = $conn->query("
    SELECT ingredient_name, quantity, unit, reorder_level
    FROM Inventory WHERE quantity <= reorder_level ORDER BY quantity ASC LIMIT 5
");

// HELPER FUNCTION: STATUS BADGES
// Generates a colorful CSS badge depending on the order status string provided
function badge($status) {
    $map = ['Completed'=>'success','Cancelled'=>'danger','Pending'=>'warning','In-Progress'=>'info'];
    $cls = $map[$status] ?? 'secondary'; // Default to a grey 'secondary' badge if status doesn't match
    return '<span class="badge badge-'.$cls.'">'.$status.'</span>';
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Dashboard – Admin</title>
        <link rel="stylesheet" href="/neychurlava/assets/css/style.css">
        <link rel="shortcut icon" href="/neychurlava/assets/favicon.png" type="image/x-icon">
    </head>
    <body>
        <div class="wrapper">
            <!-- Render the dynamic navigation sidebar with the 'dashboard' tab highlighted -->
            <?php sidebar('dashboard'); ?>
            <div class="main">
                <!-- Render the dynamic top bar displaying page titles -->
                <?php topbar('Dashboard', 'Admin > Dashboard'); ?>
                <div class="content">

                    <!-- 6. FLASH MESSAGES (TEMPORARY NOTIFICATIONS) -->
                    <!-- If an action on a previous page set a temporary notification message, display it here -->
                    <?php if (isset($_SESSION['flash'])): ?>
                    <div class="alert alert-<?= $_SESSION['flash_type'] ?>"><?= $_SESSION['flash'] ?></div>
                        <!-- Delete the flash message instantly so it doesn't display again on a page refresh -->
                        <?php unset($_SESSION['flash'],$_SESSION['flash_type']); endif; ?>

        <!-- STATISTICAL CARDS DISPLAY GRID -->
        <div class="stats-grid">
            <!-- Total Orders Card -->
            <div class="stat-card">
                <div class="label">Total Orders</div>
                <div class="value"><?= $stats['total_orders'] ?></div>
                <div class="sub"><?= $stats['completed_orders'] ?> completed</div>
            </div>
            <!-- Revenue Card (Formats number to Philippine Peso currency style) -->
            <div class="stat-card green">
                <div class="label">Total Revenue</div>
                <div class="value">₱<?= number_format($stats['revenue_total'],2) ?></div>
                <div class="sub">From completed orders</div>
            </div>
            <!-- Active Orders Card -->
            <div class="stat-card yellow">
                <div class="label">Active Orders</div>
                <div class="value"><?= $stats['active_orders'] ?></div>
                <div class="sub">Pending / In-Progress</div>
            </div>
            <!-- Low Stock Card -->
            <div class="stat-card red">
                <div class="label">Low Stock Alerts</div>
                <div class="value"><?= $stats['low_stock'] ?></div>
                <div class="sub">Items below reorder level</div>
            </div>
            <!-- Active Staff Card -->
            <div class="stat-card blue">
                <div class="label">Active Staff</div>
                <div class="value"><?= $stats['total_staff'] ?></div>
                <div class="sub">Registered employees</div>
            </div>
            <!-- Registered Customers Card -->
            <div class="stat-card">
                <div class="label">Customers</div>
                <div class="value"><?= $stats['total_customers'] ?></div>
                <div class="sub">Registered accounts</div>
            </div>
        </div>

        <!-- 8. RECENT ORDERS TABLE SECTION -->
        <div class="card">
            <div class="card-header">
                <h2>Recent Orders</h2>
                <a href="/neychurlava/admin/orders.php" class="btn btn-sm btn-secondary">View All</a>
            </div>
            <div class="card-body">
            <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>Date</th><th>Type</th><th>Customer</th><th>Handled By</th><th>Total</th><th>Status</th></tr></thead>
                <tbody>
                <!-- Loop through the 8 recent order rows fetched from the database query -->
                <?php while ($row = $recent->fetch_assoc()): ?>
                <tr>
                    <td>#<?= $row['order_id'] ?></td>
                    <!-- Convert the SQL timestamp string into a readable date format (e.g., Oct 25, 2025 14:30) -->
                    <td><?= date('M d, Y H:i', strtotime($row['order_date'])) ?></td>
                    <td><span class="badge badge-primary"><?= $row['order_type'] ?></span></td>
                    <!-- htmlspecialchars avoids HTML layout breaking or XSS injection if name contains odd characters -->
                    <td><?= htmlspecialchars($row['customer']) ?></td>
                    <td><?= htmlspecialchars($row['staff_name']) ?></td>
                    <td>₱<?= number_format($row['total_amount'],2) ?></td>
                    <!-- Calls the badge helper function declared at the top of the file -->
                    <td><?= badge($row['order_status']) ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
            </div>
        </div>

        <!-- 9. LOW STOCK INVENTORY WARNING TABLES -->
        <!-- Only render this entire HTML section if low stock count is strictly greater than 0 -->
        <?php if ($stats['low_stock'] > 0): ?>
        <div class="card">
            <div class="card-header"><h2>⚠️ Low Stock Ingredients</h2>
                <a href="/neychurlava/admin/inventory.php" class="btn btn-sm btn-warning">Manage Inventory</a>
            </div>
            <div class="card-body">
            <div class="table-wrap"><table>
                <thead><tr><th>Ingredient</th><th>Current Stock</th><th>Unit</th><th>Reorder Level</th></tr></thead>
                <tbody>
                <!-- Loop through the low stock item rows fetched from the inventory query -->
                <?php while ($r = $lowstock->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($r['ingredient_name']) ?></td>
                    <!-- Explicitly colors the text red to emphasize the shortage warning -->
                    <td style="color:#dc3545;font-weight:700"><?= $r['quantity'] ?></td>
                    <td><?= $r['unit'] ?></td>
                    <td><?= $r['reorder_level'] ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table></div>
            </div>
        </div>
        <?php endif; ?>
        </div>
        </div>
        </div>
    </body>
</html>
