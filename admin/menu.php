<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/layout.php';
require_role([1,2]);


$msg = ''; $msg_type = 'success';

// CREATE
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'') === 'create') {
    $stmt = $conn->prepare("INSERT INTO Menu_Item (category_id,inventory_id,item_name,description,price,availability,item_type) VALUES (?,?,?,?,?,?,?)");

    $inv_id = ($_POST['inventory_id']!=='') ? (int)$_POST['inventory_id'] : null;

    $stmt->bind_param("iissdss",$_POST['category_id'],$inv_id,$_POST['item_name'],$_POST['description'],$_POST['price'],$_POST['availability'],$_POST['item_type']);

    if ($stmt->execute()) { 
        $msg='Menu item added successfully.';
    } else { 
        $msg=$conn->error; $msg_type='danger'; 
    }
    $stmt->close();
}


// UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    
    // Prepare the SQL statement
    $stmt = $conn->prepare("
        UPDATE Menu_Item 
        SET category_id = ?, 
            inventory_id = ?, 
            item_name = ?, 
            description = ?, 
            price = ?, 
            availability = ?, 
            item_type = ? 
        WHERE item_id = ?
    ");

    // Handle optional inventory_id (convert empty string to NULL)
    $inv_id = ($_POST['inventory_id'] !== '') ? (int)$_POST['inventory_id'] : null;

    // Bind parameters
    $stmt->bind_param(
        "iissdssi",
        $_POST['category_id'],   // integer
        $inv_id,                 // integer or NULL
        $_POST['item_name'],     // string
        $_POST['description'],   // string
        $_POST['price'],         // double
        $_POST['availability'],  // string
        $_POST['item_type'],     // string
        $_POST['item_id']        // integer
    );

    if ($stmt->execute()) {
        $msg      = 'Menu item updated.';
        $msg_type = 'success';
    } else {
        $msg      = $conn->error;
        $msg_type = 'danger';
    }

    $stmt->close();
}


/* if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'') === 'delete') {
    $stmt = $conn->prepare("DELETE FROM Menu_Item WHERE item_id=?");
    $stmt->bind_param("i",$_POST['item_id']);
    if ($stmt->execute()) { $msg='Menu item deleted.'; } else { $msg=$conn->error; $msg_type='danger'; }
    $stmt->close();
} */


// DELETE
// Turn on exception mode for mysqli
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    try {
        $stmt = $conn->prepare("DELETE FROM Menu_Item WHERE item_id=?");
        $stmt->bind_param("i", $_POST['item_id']);
        $stmt->execute();

        $_SESSION['notification'] = [
            'message' => 'Menu item deleted.',
            'type' => 'success'
        ];

        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $_SESSION['notification'] = [
            'message' => 'Unable to delete item because it is linked to an order.',
            'type' => 'danger'
        ];
    }

    // Redirect so the notification shows after reload
    header("Location: menu.php");
    exit;
}

if (isset($_SESSION['notification'])) {
    $msg = $_SESSION['notification']['message'];
    $msg_type = $_SESSION['notification']['type'];

    echo "<script>showNotification(" . json_encode($msg) . ", " . json_encode($msg_type) . ");</script>";

    unset($_SESSION['notification']); // clear after showing
}

// Search
$search = trim($_GET['search'] ?? '');
$where  = $search ? "WHERE mi.item_name LIKE '%".mysqli_real_escape_string($conn,$search)."%'" : '';

$items = $conn->query("
    SELECT mi.*, cat.category_name
    FROM Menu_Item mi
    INNER JOIN Category cat ON mi.category_id = cat.category_id
    $where ORDER BY cat.category_name, mi.item_name
");
$categories = $conn->query("SELECT * FROM Category ORDER BY category_name");
$inventories = $conn->query("SELECT * FROM Inventory ORDER BY ingredient_name");
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Menu Items</title>
        <link rel="stylesheet" href="/neychurlava/assets/css/style.css">
        <link rel="shortcut icon" href="/neychurlava/assets/favicon.png" type="image/x-icon">
    </head>
    <body>
        <div class="wrapper">
        <?php sidebar('menu'); ?>
        <div class="main">
        <?php topbar('Menu Items','Admin > Menu Items'); ?>
        <div class="content">
        <?php if ($msg): ?><div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>Menu Items</h2>
                <button class="btn btn-success btn-sm" onclick="openModal('addModal')">+ Add Item</button>
            </div>
            <div class="card-body">
                <form method="GET" class="search-bar">
                    <input type="text" name="search" class="form-control" placeholder="Search menu item..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-primary">Search</button>
                    <?php if ($search): ?><a href="menu.php" class="btn btn-secondary">Clear</a><?php endif; ?>
                </form>
                <div class="table-wrap"><table>
                    <thead><tr><th>#</th><th>Item Name</th><th>Category</th><th>Price</th><th>Type</th><th>Availability</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php while ($r = $items->fetch_assoc()): ?>
                    <tr>
                        <td><?= $r['item_id'] ?></td>
                        <td><?= htmlspecialchars($r['item_name']) ?></td>
                        <td><?= htmlspecialchars($r['category_name']) ?></td>
                        <td>₱<?= number_format($r['price'],2) ?></td>
                        <td><span class="badge badge-primary"><?= $r['item_type'] ?></span></td>
                        <td><?php if($r['availability']==='Available'): ?>
                            <span class="badge badge-success">Available</span>
                            <?php else: ?><span class="badge badge-danger">Unavailable</span><?php endif; ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm" onclick='editMenu(<?= json_encode($r) ?>)'>Edit</button>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this item?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="item_id" value="<?= $r['item_id'] ?>">
                                <button class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table></div>
            </div>
        </div>

        <!-- ADD MODAL -->
        <div class="modal-overlay" id="addModal">
        <div class="modal">
            <div class="modal-header"><h3>Add Menu Item</h3><button class="modal-close" onclick="closeModal('addModal')">×</button></div>
            <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="create">
                <div class="form-group"><label>Item Name</label><input name="item_name" class="form-control" required></div>
                <div class="form-group"><label>Category</label>
                    <select name="category_id" class="form-control" required>
                        <?php $categories->data_seek(0); while ($c=$categories->fetch_assoc()): ?>
                        <option value="<?= $c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group"><label>Linked Ingredient (optional)</label>
                    <select name="inventory_id" class="form-control">
                        <option value="">-- None --</option>
                        <?php $inventories->data_seek(0); while ($i=$inventories->fetch_assoc()): ?>
                        <option value="<?= $i['inventory_id'] ?>"><?= htmlspecialchars($i['ingredient_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group"><label>Description</label><textarea name="description" class="form-control"></textarea></div>
                <div class="form-group"><label>Price (₱)</label><input type="number" step="0.01" name="price" class="form-control" required></div>
                <div class="form-group"><label>Type</label>
                    <select name="item_type" class="form-control">
                        <option>Regular</option><option>Special</option><option>Seasonal</option>
                    </select>
                </div>
                <div class="form-group"><label>Availability</label>
                    <select name="availability" class="form-control">
                        <option>Available</option><option>Unavailable</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                <button class="btn btn-success">Add Item</button>
            </div>
            </form>
        </div></div>

        <!-- EDIT MODAL -->
        <div class="modal-overlay" id="editModal">
        <div class="modal">
            <div class="modal-header"><h3>Edit Menu Item</h3><button class="modal-close" onclick="closeModal('editModal')">×</button></div>
            <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="item_id" id="edit_item_id">
                <div class="form-group"><label>Item Name</label><input id="edit_item_name" name="item_name" class="form-control" required></div>
                <div class="form-group"><label>Category</label>
                    <select id="edit_category_id" name="category_id" class="form-control">
                        <?php $categories->data_seek(0); while ($c=$categories->fetch_assoc()): ?>
                        <option value="<?= $c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group"><label>Linked Ingredient (optional)</label>
                    <select id="edit_inventory_id" name="inventory_id" class="form-control">
                        <option value="">-- None --</option>
                        <?php $inventories->data_seek(0); while ($i=$inventories->fetch_assoc()): ?>
                        <option value="<?= $i['inventory_id'] ?>"><?= htmlspecialchars($i['ingredient_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group"><label>Description</label><textarea id="edit_description" name="description" class="form-control"></textarea></div>
                <div class="form-group"><label>Price (₱)</label><input type="number" step="0.01" id="edit_price" name="price" class="form-control" required></div>
                <div class="form-group"><label>Type</label>
                    <select id="edit_item_type" name="item_type" class="form-control">
                        <option>Regular</option><option>Special</option><option>Seasonal</option>
                    </select>
                </div>
                <div class="form-group"><label>Availability</label>
                    <select id="edit_availability" name="availability" class="form-control">
                        <option>Available</option><option>Unavailable</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                <button class="btn btn-warning">Update Item</button>
            </div>
            </form>
        </div></div>

        <script>
        function openModal(id)  { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }
        function editMenu(r) {
            document.getElementById('edit_item_id').value      = r.item_id;
            document.getElementById('edit_item_name').value    = r.item_name;
            document.getElementById('edit_description').value  = r.description || '';
            document.getElementById('edit_price').value        = r.price;
            document.getElementById('edit_category_id').value  = r.category_id;
            document.getElementById('edit_inventory_id').value = r.inventory_id || '';
            document.getElementById('edit_item_type').value    = r.item_type;
            document.getElementById('edit_availability').value = r.availability;
            openModal('editModal');
        }
        </script>
        </div></div></div>

    </body>
</html>
