<?php
require_once '../config/db.php';        
require_once '../config/session.php';   
require_once '../config/layout.php';   
require_role([1,2]);                   

$msg=''; 
$msg_type='success';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';

    // Create new ingredient
    if ($action==='create') {
        $stmt=$conn->prepare("INSERT INTO Inventory (ingredient_name,quantity,unit,reorder_level,last_updated) VALUES (?,?,?,?,NOW())");
        $stmt->bind_param("sdsd",$_POST['ingredient_name'],$_POST['quantity'],$_POST['unit'],$_POST['reorder_level']);
        if ($stmt->execute()) { 
            $msg='Ingredient added.'; 
        } else { 
            $msg=$conn->error; 
            $msg_type='danger'; 
        }
        $stmt->close();

    // Update existing ingredient
    } elseif ($action==='update') {
        $stmt=$conn->prepare("UPDATE Inventory SET ingredient_name=?,quantity=?,unit=?,reorder_level=?,last_updated=NOW() WHERE inventory_id=?");
        $stmt->bind_param("sdsdi",$_POST['ingredient_name'],$_POST['quantity'],$_POST['unit'],$_POST['reorder_level'],$_POST['inventory_id']);
        if ($stmt->execute()) { 
            $msg='Inventory updated.'; 
        } else { 
            $msg=$conn->error; 
            $msg_type='danger'; 
        }
        $stmt->close();

    // Delete ingredient
    } elseif ($action==='delete') {
        $stmt=$conn->prepare("DELETE FROM Inventory WHERE inventory_id=?");
        $stmt->bind_param("i",$_POST['inventory_id']);
        if ($stmt->execute()) { 
            $msg='Ingredient deleted.'; 
        } else { 
            $msg=$conn->error; 
            $msg_type='danger'; 
        }
        $stmt->close();
    }
}

// Fetch all inventory items
$items = $conn->query("SELECT * FROM Inventory ORDER BY ingredient_name");
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory</title>
    <link rel="stylesheet" href="/neychurlava/assets/css/style.css">
    <link rel="shortcut icon" href="/neychurlava/assets/favicon.png" type="image/x-icon">
</head>
<body>
<div class="wrapper">

    <?php sidebar('inventory'); ?>
    <div class="main">
        <?php topbar('Inventory Management','Admin > Inventory'); ?>

        <div class="content">
            <!-- Display success/error messages -->
            <?php if ($msg): ?>
                <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <!-- Inventory Table -->
            <div class="card">
                <div class="card-header">
                    <h2>Ingredients & Stock</h2>
                    <button class="btn btn-success btn-sm" onclick="openModal('addModal')">+ Add Ingredient</button>
                </div>
                <div class="card-body">
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Ingredient</th>
                                    <th>Quantity</th>
                                    <th>Unit</th>
                                    <th>Reorder Level</th>
                                    <th>Status</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($r=$items->fetch_assoc()): $low=($r['quantity']<=$r['reorder_level']); ?>
                                <tr>
                                    <td><?= $r['inventory_id'] ?></td>
                                    <td><?= htmlspecialchars($r['ingredient_name']) ?></td>
                                    <td style="<?= $low?'color:#dc3545;font-weight:700':'' ?>"><?= $r['quantity'] ?></td>
                                    <td><?= $r['unit'] ?></td>
                                    <td><?= $r['reorder_level'] ?></td>
                                    <td><?= $low ? '<span class="badge badge-danger">Low Stock</span>' : '<span class="badge badge-success">OK</span>' ?></td>
                                    <td><?= date('M d, Y', strtotime($r['last_updated'])) ?></td>
                                    <td>
                                        <!-- Edit button -->
                                        <button class="btn btn-warning btn-sm" onclick='editInv(<?= json_encode($r) ?>)'>Edit</button>
                                        <!-- Delete form -->
                                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="inventory_id" value="<?= $r['inventory_id'] ?>">
                                            <button class="btn btn-danger btn-sm">Del</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Add Ingredient Modal -->
            <div class="modal-overlay" id="addModal">
                <div class="modal">
                    <div class="modal-header">
                        <h3>Add Ingredient</h3>
                        <button class="modal-close" onclick="closeModal('addModal')">×</button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="create">
                            <div class="form-group">
                                <label>Ingredient Name</label>
                                <input name="ingredient_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Quantity</label>
                                <input type="number" step="0.01" name="quantity" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Unit</label>
                                <input name="unit" class="form-control" placeholder="kg / liters / pieces" required>
                            </div>
                            <div class="form-group">
                                <label>Reorder Level</label>
                                <input type="number" step="0.01" name="reorder_level" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                            <button class="btn btn-success">Add</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit Ingredient Modal -->
            <div class="modal-overlay" id="editModal">
                <div class="modal">
                    <div class="modal-header">
                        <h3>Edit Ingredient</h3>
                        <button class="modal-close" onclick="closeModal('editModal')">×</button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="inventory_id" id="edit_id">
                            <div class="form-group">
                                <label>Ingredient Name</label>
                                <input id="edit_name" name="ingredient_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Quantity</label>
                                <input type="number" step="0.01" id="edit_qty" name="quantity" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Unit</label>
                                <input id="edit_unit" name="unit" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Reorder Level</label>
                                <input type="number" step="0.01" id="edit_reorder" name="reorder_level" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                            <button class="btn btn-warning">Update</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal and Edit Functions -->
<script>
    function openModal(id){document.getElementById(id).classList.add('active');}
    function closeModal(id){document.getElementById(id).classList.remove('active');}
    function editInv(r){
        document.getElementById('edit_id').value=r.inventory_id;
        document.getElementById('edit_name').value=r.ingredient_name;
        document.getElementById('edit_qty').value=r.quantity;
        document.getElementById('edit_unit').value=r.unit;
        document.getElementById('edit_reorder').value=r.reorder_level;
        openModal('editModal');
    }
</script>
</body>
</html>
