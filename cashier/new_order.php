<?php
require_once '../config/db.php';       
require_once '../config/session.php';  
require_once '../config/layout.php';   
require_role([3,4]);                   

$msg = ''; 
$msg_type = 'success'; 
$new_order_id = null;

// Handle form submission for creating a new order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_order') {
    
    // Collect form inputs
    $cid   = ($_POST['customer_id'] !== '') ? (int)$_POST['customer_id'] : null;
    $type  = $_POST['order_type'];                
    $note  = $_POST['special_instructions'];      
    $items = json_decode($_POST['items_json'], true); // items from cart (JSON)

    // Validate: must have at least one item
    if (empty($items)) {
        $msg = 'Please add at least one item.';
        $msg_type = 'danger';
    } else {
        // Calculate total amount
        $total = 0;
        foreach ($items as $it) {
            $total += $it['qty'] * $it['price'];
        }

        // Start transaction (so all inserts succeed or fail together)
        $conn->begin_transaction();
        try {
            // Insert into Order table
            $stmt = $conn->prepare("
                INSERT INTO `Order` 
                (customer_id, staff_id, order_type, order_status, order_date, total_amount, special_instructions) 
                VALUES (?, ?, ?, 'Pending', NOW(), ?, ?)
            ");
            
            // Bind parameters: customer_id, staff_id, order_type, total_amount, note
            $stmt->bind_param("iisis", $cid, $_SESSION['staff_id'], $type, $total, $note);
            $stmt->execute();
            $new_order_id = $conn->insert_id; // get new order ID
            $stmt->close();

            // Insert each item into Order_Item table
            $stmt2 = $conn->prepare("
                INSERT INTO Order_Item (order_id, item_id, quantity, unit_price, subtotal) 
                VALUES (?, ?, ?, ?, ?)
            ");
            foreach ($items as $it) {
                $sub = $it['qty'] * $it['price'];
                $stmt2->bind_param("iiidd", $new_order_id, $it['item_id'], $it['qty'], $it['price'], $sub);
                $stmt2->execute();
            }
            $stmt2->close();

            // If delivery, insert into Delivery table
            if ($type === 'Delivery' && !empty($_POST['del_address'])) {
                $stmt3 = $conn->prepare("
                    INSERT INTO Delivery (order_id, delivery_address, contact_number, delivery_status) 
                    VALUES (?, ?, '', 'Pending')
                ");
                $addr = $_POST['del_address'];
                $stmt3->bind_param("is", $new_order_id, $addr);
                $stmt3->execute();
                $stmt3->close();
            }

            // Commit transaction
            $conn->commit();
            $msg = "Order #$new_order_id created successfully! Proceed to payment.";
        } catch (Exception $e) {
            // Rollback if any error occurs
            $conn->rollback();
            $msg = 'Error: ' . $e->getMessage();
            $msg_type = 'danger';
        }
    }
}




$menu_items = $conn->query("
    SELECT mi.*,cat.category_name FROM Menu_Item mi
    INNER JOIN Category cat ON mi.category_id=cat.category_id
    WHERE mi.availability='Available' ORDER BY cat.category_name,mi.item_name
");
$customers = $conn->query("SELECT * FROM Customer ORDER BY first_name");
$categories = $conn->query("SELECT * FROM Category ORDER BY category_name");
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>New Order</title>
        <link rel="stylesheet" href="/neychurlava/assets/css/style.css">
        <link rel="shortcut icon" href="/neychurlava/assets/favicon.png" type="image/x-icon">

        <style>
            .pos-grid{display:grid;grid-template-columns:1fr 360px;gap:20px;}
            .menu-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:12px;}
            .menu-card{background: #fff; border-radius:10px;padding:14px;box-shadow:0 2px 6px rgba(0,0,0,.08);cursor:pointer;border:1.5px solid #2f9114;transition:.2s;}
            .menu-card:hover{border-color:#2e4057;transform:translateY(-2px);}
            .menu-card h4{font-size:13px;font-weight:700;margin-bottom:4px;}
            .menu-card .price{color:#28a745;font-weight:700;font-size:15px;}
            .menu-card .cat{font-size:11px;color:#888;}
            .order-panel{background:#fff;border-radius:10px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.08);position:sticky;top:20px;}
            .order-panel h3{font-size:15px;font-weight:700;margin-bottom:16px;color:#2e4057;}
            .cart-item{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px dashed #f0f0f0;font-size:13px;}
            .cart-total{margin-top:12px;font-size:16px;font-weight:700;text-align:right;color:#2e4057;}
            .cat-filter{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;}
            .cat-btn{padding:6px 12px;border-radius:20px;border:1.5px solid #ddd;background:#fff;cursor:pointer;font-size:12px;}
            .cat-btn.active{background:#2e4057;color:#fff;border-color:#2e4057;}
        </style>
    </head>
    <body>
        <div class="wrapper">
        <?php sidebar('new_order'); ?>
        <div class="main">
        <?php topbar('New Order','Cashier > New Order'); ?>
        <div class="content">
        <?php if ($msg): ?><div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?>
            <?php if ($new_order_id && $msg_type==='success'): ?>
            &nbsp;<a href="/neychurlava/cashier/payments.php?order_id=<?= $new_order_id ?>" class="btn btn-success btn-sm">Proceed to Payment →</a>
            <?php endif; ?>
        </div><?php endif; ?>

        <form method="POST" id="orderForm">
        <input type="hidden" name="action" value="create_order">
        <input type="hidden" name="items_json" id="items_json">

        <div class="pos-grid">
        <!-- LEFT: Menu -->
        <div>
            <!-- Category Filter -->
            <div class="cat-filter">
                <button type="button" class="cat-btn active" onclick="filterCat('all',this)">All</button>
                <?php while ($cat=$categories->fetch_assoc()): ?>
                <button type="button" class="cat-btn" onclick="filterCat('<?= $cat['category_id'] ?>',this)"><?= htmlspecialchars($cat['category_name']) ?></button>
                <?php endwhile; ?>
            </div>
            <!-- Menu Grid -->
            <div class="menu-grid" id="menuGrid">
                <?php while ($mi=$menu_items->fetch_assoc()): ?>
                <div class="menu-card" data-cat="<?= $mi['category_id'] ?>"
                    onclick="addToCart(<?= $mi['item_id'] ?>, '<?= htmlspecialchars(addslashes($mi['item_name'])) ?>', <?= $mi['price'] ?>)">
                    <div class="cat"><?= htmlspecialchars($mi['category_name']) ?></div>
                    <h4><?= htmlspecialchars($mi['item_name']) ?></h4>
                    <div class="price">₱<?= number_format($mi['price'],2) ?></div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- RIGHT: Order Panel -->
        <div>
        <div class="order-panel">
            <h3>🧾 Current Order</h3>

            <div class="form-group">
                <label>Order Type</label>
                <select name="order_type" id="order_type" class="form-control" onchange="toggleDelivery()">
                    <option>Dine-in</option><option>Take-out</option><option>Delivery</option>
                </select>
            </div>
            <div class="form-group">
                <label>Customer (optional)</label>
                <select name="customer_id" class="form-control">
                    <option value="">-- Walk-in --</option>
                    <?php while ($cu=$customers->fetch_assoc()): ?>
                    <option value="<?= $cu['customer_id'] ?>"><?= htmlspecialchars($cu['first_name'].' '.$cu['last_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group" id="del_group" style="display:none">
                <label>Delivery Address</label>
                <textarea name="del_address" class="form-control" placeholder="Full delivery address"></textarea>
            </div>
            <div class="form-group">
                <label>Special Instructions</label>
                <textarea name="special_instructions" class="form-control" rows="2" placeholder="e.g. Less spicy, no onions"></textarea>
            </div>

            <hr style="margin:12px 0">
            <div id="cartList"><p style="color:#aaa;font-size:13px;text-align:center">No items yet.<br>Click a menu item to add.</p></div>
            <div class="cart-total" id="cartTotal" style="display:none">Total: ₱<span id="totalAmt">0.00</span></div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top:16px" id="submitBtn" disabled>Place Order</button>
        </div>
        </div>
        </div><!-- end pos-grid -->
        </form>
        </div></div></div>

        <script>
        let cart = {};
        function addToCart(id, name, price) {
            if (cart[id]) cart[id].qty++;
            else cart[id] = {id, name, price, qty: 1};
            renderCart();
        }
        function removeFromCart(id) { delete cart[id]; renderCart(); }
        function changeQty(id, delta) {
            cart[id].qty += delta;
            if (cart[id].qty <= 0) delete cart[id];
            renderCart();
        }
        function renderCart() {
            const list = document.getElementById('cartList');
            const totalEl = document.getElementById('cartTotal');
            const totalAmtEl = document.getElementById('totalAmt');
            const submitBtn = document.getElementById('submitBtn');
            const keys = Object.keys(cart);
            if (keys.length === 0) {
                list.innerHTML = '<p style="color:#aaa;font-size:13px;text-align:center">No items yet.<br>Click a menu item to add.</p>';
                totalEl.style.display='none'; submitBtn.disabled=true;
                document.getElementById('items_json').value='[]'; return;
            }
            let html='', total=0;
            const itemsArr=[];
            keys.forEach(id => {
                const it=cart[id]; const sub=it.qty*it.price; total+=sub;
                itemsArr.push({item_id:it.id,qty:it.qty,price:it.price});
                html+=`<div class="cart-item">
                    <div><b>${it.name}</b><br><small>₱${it.price.toFixed(2)} x ${it.qty}</small></div>
                    <div style="display:flex;align-items:center;gap:6px">
                        <button type="button" onclick="changeQty(${id},-1)" style="width:24px;height:24px;border-radius:50%;border:1px solid #ddd;cursor:pointer">-</button>
                        <span>${it.qty}</span>
                        <button type="button" onclick="changeQty(${id},1)"  style="width:24px;height:24px;border-radius:50%;border:1px solid #ddd;cursor:pointer">+</button>
                        <button type="button" onclick="removeFromCart(${id})" style="color:#dc3545;background:none;border:none;cursor:pointer;font-size:16px">×</button>
                    </div>
                </div>`;
            });
            list.innerHTML=html;
            totalAmtEl.textContent=total.toFixed(2);
            totalEl.style.display='block'; submitBtn.disabled=false;
            document.getElementById('items_json').value=JSON.stringify(itemsArr);
        }
        function filterCat(catId, btn) {
            document.querySelectorAll('.cat-btn').forEach(b=>b.classList.remove('active'));
            btn.classList.add('active');
            document.querySelectorAll('.menu-card').forEach(c=>{
                c.style.display=(catId==='all'||c.dataset.cat===catId)?'block':'none';
            });
        }
        function toggleDelivery() {
            const del=document.getElementById('del_group');
            del.style.display=document.getElementById('order_type').value==='Delivery'?'block':'none';
        }
        </script>
    </body>
</html>
