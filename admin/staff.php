<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/layout.php';
require_role([1,2]);

$msg=''; $msg_type='success';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action=$_POST['action']??'';
    if ($action==='create') {
        $hash=hash('sha256',$_POST['password']);
        $stmt=$conn->prepare("INSERT INTO Staff (role_id,first_name,last_name,email,phone,status,hired_at,password_hash) VALUES (?,?,?,?,?,?,NOW(),?)");
        $stmt->bind_param("issssss",$_POST['role_id'],$_POST['first_name'],$_POST['last_name'],$_POST['email'],$_POST['phone'],$_POST['status'],$hash);
        if ($stmt->execute()) { $msg='Staff added.'; } else { $msg=$conn->error; $msg_type='danger'; }
        $stmt->close();
    } elseif ($action==='update') {
        $stmt=$conn->prepare("UPDATE Staff SET role_id=?,first_name=?,last_name=?,email=?,phone=?,status=? WHERE staff_id=?");
        $stmt->bind_param("isssssi",$_POST['role_id'],$_POST['first_name'],$_POST['last_name'],$_POST['email'],$_POST['phone'],$_POST['status'],$_POST['staff_id']);
        if ($stmt->execute()) { $msg='Staff updated.'; } else { $msg=$conn->error; $msg_type='danger'; }
        $stmt->close();
    } elseif ($action==='delete') {
        $stmt=$conn->prepare("UPDATE Staff SET status='Inactive' WHERE staff_id=?");
        $stmt->bind_param("i",$_POST['staff_id']);
        if ($stmt->execute()) { $msg='Staff deactivated.'; } else { $msg=$conn->error; $msg_type='danger'; }
        $stmt->close();
    }
}

$staff = $conn->query("SELECT s.*,r.role_name FROM Staff s INNER JOIN Role r ON s.role_id=r.role_id ORDER BY s.status,r.role_id,s.last_name");
$roles  = $conn->query("SELECT * FROM Role ORDER BY role_name");
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Staff</title>
    <link rel="stylesheet" href="/neychurlava/assets/css/style.css">
    <link rel="shortcut icon" href="/neychurlava/assets/favicon.png" type="image/x-icon">
</head>
<body><div class="wrapper">
<?php sidebar('staff'); ?>
<div class="main">
<?php topbar('Staff Management','Admin > Staff'); ?>
<div class="content">
<?php if ($msg): ?><div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="card">
    <div class="card-header"><h2>Staff Records</h2>
        <button class="btn btn-success btn-sm" onclick="openModal('addModal')">+ Add Staff</button>
    </div>
    <div class="card-body"><div class="table-wrap"><table>
        <thead><tr><th>#</th><th>Name</th><th>Role</th><th>Email</th><th>Phone</th><th>Status</th><th>Hired</th><th>Actions</th></tr></thead>
        <tbody>
        <?php while ($r=$staff->fetch_assoc()): ?>
        <tr>
            <td><?= $r['staff_id'] ?></td>
            <td><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></td>
            <td><span class="badge badge-primary"><?= htmlspecialchars($r['role_name']) ?></span></td>
            <td><?= htmlspecialchars($r['email']) ?></td>
            <td><?= $r['phone'] ?></td>
            <td><?= $r['status']==='Active' ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>' ?></td>
            <td><?= date('M d, Y', strtotime($r['hired_at'])) ?></td>
            <td>
                <button class="btn btn-warning btn-sm" onclick='editStaff(<?= json_encode($r) ?>)'>Edit</button>
                <form method="POST" style="display:inline" onsubmit="return confirm('Deactivate this staff?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="staff_id" value="<?= $r['staff_id'] ?>">
                    <button class="btn btn-danger btn-sm">Deactivate</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table></div></div>
</div>

<!-- ADD MODAL -->
<div class="modal-overlay" id="addModal">
<div class="modal">
    <div class="modal-header"><h3>Add Staff</h3><button class="modal-close" onclick="closeModal('addModal')">×</button></div>
    <form method="POST"><div class="modal-body">
        <input type="hidden" name="action" value="create">
        <div class="form-group"><label>First Name</label><input name="first_name" class="form-control" required></div>
        <div class="form-group"><label>Last Name</label><input name="last_name" class="form-control" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" required></div>
        <div class="form-group"><label>Phone</label><input name="phone" class="form-control" required></div>
        <div class="form-group"><label>Role</label>
            <select name="role_id" class="form-control">
                <?php $roles->data_seek(0); while($ro=$roles->fetch_assoc()): ?>
                <option value="<?= $ro['role_id'] ?>"><?= htmlspecialchars($ro['role_name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group"><label>Status</label>
            <select name="status" class="form-control"><option>Active</option><option>Inactive</option></select>
        </div>
        <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control" value="neychurlava2025" required></div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
        <button class="btn btn-success">Add Staff</button>
    </div></form>
</div></div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModal">
<div class="modal">
    <div class="modal-header"><h3>Edit Staff</h3><button class="modal-close" onclick="closeModal('editModal')">×</button></div>
    <form method="POST"><div class="modal-body">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="staff_id" id="e_id">
        <div class="form-group"><label>First Name</label><input id="e_fn" name="first_name" class="form-control" required></div>
        <div class="form-group"><label>Last Name</label><input id="e_ln" name="last_name" class="form-control" required></div>
        <div class="form-group"><label>Email</label><input type="email" id="e_email" name="email" class="form-control" required></div>
        <div class="form-group"><label>Phone</label><input id="e_phone" name="phone" class="form-control" required></div>
        <div class="form-group"><label>Role</label>
            <select id="e_role" name="role_id" class="form-control">
                <?php $roles->data_seek(0); while($ro=$roles->fetch_assoc()): ?>
                <option value="<?= $ro['role_id'] ?>"><?= htmlspecialchars($ro['role_name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group"><label>Status</label>
            <select id="e_status" name="status" class="form-control"><option>Active</option><option>Inactive</option></select>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
        <button class="btn btn-warning">Update</button>
    </div></form>
</div></div>
<script>
function openModal(id){document.getElementById(id).classList.add('active');}
function closeModal(id){document.getElementById(id).classList.remove('active');}
function editStaff(r){
    document.getElementById('e_id').value=r.staff_id;
    document.getElementById('e_fn').value=r.first_name;
    document.getElementById('e_ln').value=r.last_name;
    document.getElementById('e_email').value=r.email;
    document.getElementById('e_phone').value=r.phone;
    document.getElementById('e_role').value=r.role_id;
    document.getElementById('e_status').value=r.status;
    openModal('editModal');
}
</script>
</div></div></div></body></html>
