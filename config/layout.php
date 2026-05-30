<?php

// Usage: include this file, then call sidebar($active) and topbar($title)
function sidebar($active = '') {
    $role = $_SESSION['role_id'] ?? 0;
    $name = htmlspecialchars($_SESSION['staff_name'] ?? '');
    $role_name = strtoUpper(htmlspecialchars($_SESSION['role_name'] ?? ''));

    echo '<div class="sidebar">';
    echo '<div class="sidebar-brand">
            <img src="../assets/logo.svg" style="width: 100px; border-radius: 50%">
            <h2>Neychurlava</h2><p>Food-House System</p></div>';
    /* echo '<div class="sidebar-user">
            <img src="../assets/sampleProfile.png" style="width: 50px; border-radius: 50%;">
            <div class="name">'.$name.'</div>
            <div class="role">'.$role_name.'</div>
        </div>'; */
    echo '<nav>';
    if (in_array($role,[1,2])) {
        $links = [
            ['admin/index.php',     '📊', 'Dashboard',  'dashboard'],
            ['admin/orders.php',    '📋', 'Orders',     'orders'],
            ['admin/menu.php',      '🍜', 'Menu Items', 'menu'],
            ['admin/inventory.php', '📦', 'Inventory',  'inventory'],
            ['admin/staff.php',     '👤', 'Staff',      'staff'],
            ['admin/reports.php',   '📈', 'Reports',    'reports'],
        ];
    } elseif (in_array($role,[3,4])) {
        $links = [
            ['cashier/index.php',     '🏠', 'Dashboard',  'dashboard'],
            ['cashier/new_order.php', '➕', 'New Order',  'new_order'],
            ['cashier/orders.php',    '📋', 'Orders',     'orders'],
            ['cashier/payments.php',  '💰', 'Payments',   'payments'],
        ];
    } else {
        $links = [
            ['kitchen/index.php', '👨‍🍳', 'Order Queue', 'dashboard'],
        ];
    }
    foreach ($links as $l) {
        $cls = ($active === $l[3]) ? ' class="active"' : '';
        echo '<a href="/neychurlava/'.$l[0].'"'.$cls.'><span class="icon">'.$l[1].'</span>'.$l[2].'</a>';
    }
    echo '</nav>';
    echo '<div class="sidebar-footer">
            <a href="/neychurlava/auth/logout.php">
                <span class="icon">🚪</span> 
                Logout
            </a>
        </div>';
    echo '</div>';
}
function topbar($title, $breadcrumb = '') {
    $role = $_SESSION['role_id'] ?? 0;
    $name = htmlspecialchars($_SESSION['staff_name'] ?? '');
    $role_name = htmlspecialchars($_SESSION['role_name'] ?? '');

    echo '<div class="topbar">
            <div>
                <h1>'.$title.'</h1>';
                if ($breadcrumb) echo '<div class="breadcrumb">'.$breadcrumb.'
                </div>';
    echo    '</div>
            <div class="top-right">
                <div>
                    <div class="name">'.$name.'</div>
                    <div class="role">'.$role_name.'</div>
                </div>
                <a href="/neychurlava/auth/logout.php">
                    <img src="../assets/sampleProfile.png" style="width: 45px; border-radius: 50%;">
                </a>
            </div>
    </div>';
}
?>
