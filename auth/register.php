<?php
require_once '../config/db.php';
require_once '../config/session.php';

// Already logged in
if (isset($_SESSION['customer_id'])) { header("Location: /neychurlava/customer/dashboard.php"); exit(); }
if (isset($_SESSION['staff_id']))    { header("Location: /neychurlava/admin/index.php");        exit(); }

$error   = '';
$success = '';
$tab     = $_GET['tab'] ?? 'customer'; // 'customer' or 'staff'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['reg_type'] ?? 'customer';

    // ── CUSTOMER REGISTRATION ──────────────────────────────────────────────────
    if ($type === 'customer') {
        $fn      = trim($_POST['first_name'] ?? '');
        $ln      = trim($_POST['last_name']  ?? '');
        $email   = trim($_POST['email']      ?? '');
        $phone   = trim($_POST['phone']      ?? '');
        $addr    = trim($_POST['address']    ?? '');
        $pass    = $_POST['password']         ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!$fn || !$ln || !$email || !$phone || !$pass) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($pass) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($pass !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            // Check if email already exists
            $chk = $conn->prepare("SELECT customer_id FROM Customer WHERE email = ?");
            $chk->bind_param("s", $email); $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                $error = 'An account with that email already exists. Please log in.';
            } else {
                $hash = hash('sha256', $pass);
                $stmt = $conn->prepare("
                    INSERT INTO Customer
                    (first_name, last_name, email, phone, address, customer_type, password_hash, created_at)
                    VALUES (?, ?, ?, ?, ?, 'Online', ?, NOW())
                ");
                $stmt->bind_param("ssssss", $fn, $ln, $email, $phone, $addr, $hash);
                if ($stmt->execute()) {
                    $stmt->close();
                    header("Location: /neychurlava/auth/login.php?registered=1");
                    exit();
                } else {
                    $error = 'Registration failed. Please try again.';
                }
                $stmt->close();
            }
            $chk->close();
        }
        $tab = 'customer';
    }

    // ── STAFF REGISTRATION REQUEST ────────────────────────────────────────────
    if ($type === 'staff') {
        $fn      = trim($_POST['staff_first_name'] ?? '');
        $ln      = trim($_POST['staff_last_name']  ?? '');
        $email   = trim($_POST['staff_email']       ?? '');
        $phone   = trim($_POST['staff_phone']       ?? '');
        $role_id = (int)($_POST['role_id']          ?? 4);
        $pass    = $_POST['staff_password']          ?? '';
        $confirm = $_POST['staff_confirm']           ?? '';

        if (!$fn || !$ln || !$email || !$phone || !$pass) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($pass) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($pass !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $chk = $conn->prepare("SELECT staff_id FROM Staff WHERE email = ?");
            $chk->bind_param("s", $email); $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                $error = 'An account with that email already exists.';
            } else {
                $hash = hash('sha256', $pass);
                // status = 'Inactive' — admin must activate in admin/staff.php
                $stmt = $conn->prepare("
                    INSERT INTO Staff
                    (role_id, first_name, last_name, email, phone, status, hired_at, password_hash)
                    VALUES (?, ?, ?, ?, ?, 'Inactive', NOW(), ?)
                ");
                $stmt->bind_param("isssss", $role_id, $fn, $ln, $email, $phone, $hash);
                if ($stmt->execute()) {
                    $stmt->close();
                    $success = 'Registration submitted! Your account is pending admin approval. You will be notified when it is activated.';
                    $tab = 'staff';
                } else {
                    $error = 'Registration failed. Please try again.';
                }
                if (isset($stmt)) $stmt->close();
            }
            $chk->close();
        }
        $tab = 'staff';
    }
}

// Load roles for staff form
$roles = $conn->query("SELECT role_id, role_name FROM Role WHERE role_id NOT IN (1,2) ORDER BY role_name");
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Create Account — Neychurlava Food-House</title>
<link rel="stylesheet" href="/neychurlava/assets/css/customer.css">
<link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
<style>
  :root {
    --pri-color: #016924;
    --sec-color: #1c9d05;
  }
  .reg-body{display:flex;align-items:center;justify-content:center;
    min-height:100vh;background:linear-gradient( 135deg, var(--sec-color), var(--sec-color));
    padding:40px 20px}
  .reg-card{background:#fff;border-radius:18px;padding:44px 40px;
    width:100%;max-width:520px;box-shadow:0 16px 56px rgba(0,0,0,.28)}
  .reg-logo{font-size:48px;text-align:center;margin-bottom:10px}
  .reg-title{text-align:center;font-size:1.4rem;font-weight:900;color:var(--pri-color);margin-bottom:4px}
  .reg-sub{text-align:center;color:#6B7280;font-size:.88rem;margin-bottom:28px}
  .reg-tabs{display:flex;gap:0;border:2px solid #E5E7EB;border-radius:10px;
    margin-bottom:28px;overflow:hidden}
  .reg-tab{flex:1;padding:11px;font-weight:700;font-size:.9rem;cursor:pointer;
    border:none;background:#fff;color:#6B7280;transition:all .2s;text-align:center}
  .reg-tab.active{background:var(--pri-color);color:#fff}
  .reg-form{display:none}
  .reg-form.active{display:block}
  .form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
  .notice-box{background:#FFF5F0;border:1.5px solid #FED7C3;border-radius:10px;
    padding:14px 16px;font-size:.83rem;color:#92400E;margin-top:16px;line-height:1.7}
  .pending-box{background:#EFF6FF;border:1.5px solid #BFDBFE;border-radius:10px;
    padding:14px 16px;font-size:.85rem;color:#1E40AF;margin-bottom:20px}
</style>
</head>
<body class="reg-body">
<div class="reg-card">
  <div class="reg-logo">
    <img src="../assets/logo.svg" alt="" style="width: 100px; border: 2px solid green; border-radius: 50%;">
  </div>
  <h1 class="reg-title">Create an Account</h1>
  <p class="reg-sub">Neychurlava Food-House</p>

  <?php if ($error): ?>
  <div class="c-alert c-alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
  <div class="c-alert c-alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="reg-tabs">
    <button class="reg-tab <?= $tab==='customer'?'active':'' ?>"
            onclick="switchTab('customer')">🛒 Customer</button>
    <button class="reg-tab <?= $tab==='staff'?'active':'' ?>"
            onclick="switchTab('staff')">👤 Staff</button>
  </div>

  <!-- CUSTOMER FORM -->
  <form method="POST" class="reg-form <?= $tab==='customer'?'active':'' ?>" id="customerForm">
    <input type="hidden" name="reg_type" value="customer">
    <div class="form-row">
      <div class="form-group">
        <label>First Name <span style="color:red">*</span></label>
        <input type="text" name="first_name" class="form-control"
               value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
               placeholder="Juan" required>
      </div>
      <div class="form-group">
        <label>Last Name <span style="color:red">*</span></label>
        <input type="text" name="last_name" class="form-control"
               value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"
               placeholder="dela Cruz" required>
      </div>
    </div>
    <div class="form-group">
      <label>Email Address <span style="color:red">*</span></label>
      <input type="email" name="email" class="form-control"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
             placeholder="juan@gmail.com" required>
    </div>
    <div class="form-group">
      <label>Phone Number <span style="color:red">*</span></label>
      <input type="tel" name="phone" class="form-control"
             value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
             placeholder="09171234567" required>
    </div>
    <div class="form-group">
      <label>Delivery Address</label>
      <textarea name="address" class="form-control" rows="2"
        placeholder="Brgy., Municipality, Province (for faster delivery checkout)"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Password <span style="color:red">*</span></label>
        <input type="password" name="password" class="form-control"
               placeholder="Min. 6 characters" required>
      </div>
      <div class="form-group">
        <label>Confirm Password <span style="color:red">*</span></label>
        <input type="password" name="confirm_password" class="form-control"
               placeholder="Repeat password" required>
      </div>
    </div>
    <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px;padding:14px;font-size:1rem">
      Create Customer Account →
    </button>
    <div style="text-align:center;margin-top:16px;font-size:.85rem;color:#6B7280">
      Already have an account?
      <a href="/neychurlava/auth/login.php" style="color:#F4845F;font-weight:700">Log in</a>
    </div>
  </form>

  <!-- STAFF FORM -->
  <form method="POST" class="reg-form <?= $tab==='staff'?'active':'' ?>" id="staffForm">
    <input type="hidden" name="reg_type" value="staff">
    <div class="pending-box">
      ℹ️ <strong>Staff accounts require admin approval.</strong><br>
      After submitting, your account will be inactive until an admin activates it.
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>First Name <span style="color:red">*</span></label>
        <input type="text" name="staff_first_name" class="form-control"
               placeholder="First name" required>
      </div>
      <div class="form-group">
        <label>Last Name <span style="color:red">*</span></label>
        <input type="text" name="staff_last_name" class="form-control"
               placeholder="Last name" required>
      </div>
    </div>
    <div class="form-group">
      <label>Email Address <span style="color:red">*</span></label>
      <input type="email" name="staff_email" class="form-control"
             placeholder="yourname@neychurlava.ph" required>
    </div>
    <div class="form-group">
      <label>Phone Number <span style="color:red">*</span></label>
      <input type="tel" name="staff_phone" class="form-control"
             placeholder="09171234567" required>
    </div>
    <div class="form-group">
      <label>Applying For <span style="color:red">*</span></label>
      <select name="role_id" class="form-control">
        <?php while ($r = $roles->fetch_assoc()): ?>
        <option value="<?= $r['role_id'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Password <span style="color:red">*</span></label>
        <input type="password" name="staff_password" class="form-control"
               placeholder="Min. 6 characters" required>
      </div>
      <div class="form-group">
        <label>Confirm Password <span style="color:red">*</span></label>
        <input type="password" name="staff_confirm" class="form-control"
               placeholder="Repeat password" required>
      </div>
    </div>
    <button type="submit" class="btn btn-dark btn-full" style="margin-top:8px;padding:14px;font-size:1rem">
      Submit Staff Registration →
    </button>
    <div style="text-align:center;margin-top:16px;font-size:.85rem;color:#6B7280">
      Already have an account?
      <a href="/neychurlava/auth/login.php" style="color:#F4845F;font-weight:700">Log in</a>
    </div>
  </form>
</div>
<script>
function switchTab(t) {
  document.querySelectorAll('.reg-tab').forEach((b,i)=>
    b.classList.toggle('active', (t==='customer'&&i===0)||(t==='staff'&&i===1)));
  document.getElementById('customerForm').classList.toggle('active', t==='customer');
  document.getElementById('staffForm').classList.toggle('active', t==='staff');
}
</script>
</body></html>
