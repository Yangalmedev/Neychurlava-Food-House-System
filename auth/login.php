<?php
// SYSTEM INITIALIZATION & CONFIGURATION
// Load the database connection settings ($conn variable lives here)
require_once '../config/db.php';

// Start or resume the user session so we can track logged-in users
require_once '../config/session.php';

// AUTOMATIC REDIRECT FOR ALREADY LOGGED-IN USERS
if (isset($_SESSION['staff_id'])) {
    if (is_admin()) { header("Location: /neychurlava/admin/index.php");   exit(); }
    if (is_cashier())  { header("Location: /neychurlava/cashier/index.php"); exit(); }
    if (is_kitchen())  { header("Location: /neychurlava/kitchen/index.php"); exit(); }
}

// Initialize an empty error message variable
$error = '';
$redirect = $_GET['redirect'] ?? '';

// HANDLE LOGIN FORM SUBMISSION (When user clicks "Log In")
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Grab the inputs and trim trailing/leading spaces. Default to empty string if missing.
    $email = trim($_POST['email'] ?? '');
    $pass  = trim($_POST['password'] ?? '');
    
    // Check if either field was left entirely blank
    if ($email === '' || $pass === '') {
        $error = 'Please enter both email and password.';
    } else {
        // Encrypt the plain text password using SHA-256 to match the database hashes
        $hash = hash('sha256', $pass);
        
        // Prepare a secure SQL query to prevent SQL Injection attacks
        // It fetches staff info and joins the Role table to get the role name
        /* $stmt = $conn->prepare("
            SELECT s.staff_id, s.first_name, s.last_name, s.role_id, r.role_name
            FROM Staff s INNER JOIN Role r ON s.role_id = r.role_id
            WHERE s.email = ? AND s.password_hash = ? AND s.status = 'Active' LIMIT 1
        "); */

        // Try STAFF login first 
        $stmt = $conn->prepare("
            SELECT s.staff_id, s.first_name, s.last_name, s.role_id, r.role_name
            FROM Staff s
            INNER JOIN Role r ON s.role_id = r.role_id
            WHERE s.email = ? AND s.password_hash = ? AND s.status = 'Active'
            LIMIT 1
        ");


        // Bind the user's input email and hashed password to the "?" placeholders safely
        $stmt->bind_param("ss", $email, $hash);
        // Run the query against the database
        $stmt->execute();
        // Grab the results of the query
        $res = $stmt->get_result();
        
        // If exactly 1 matching record is found in the database
        if ($res->num_rows === 1) {
            // Fetch the user data as an associative array
            $u = $res->fetch_assoc();
            
            // Save user details into the Session array so they stay logged in across pages
            $_SESSION['staff_id']   = $u['staff_id'];
            $_SESSION['staff_name'] = $u['first_name'].' '.$u['last_name']; // Combines first and last name
            $_SESSION['role_id']    = $u['role_id'];
            $_SESSION['role_name']  = $u['role_name'];
            $stmt->close();
            
            // Redirect the user based on their specific Role ID
           /*  if (in_array($u['role_id'],[1,2]))       header("Location: /neychurlava/admin/index.php"); // Roles 1,2 -> Admin
            elseif (in_array($u['role_id'],[3,4]))   header("Location: /neychurlava/cashier/index.php"); // Roles 3,4 -> Cashier
            elseif (in_array($u['role_id'],[5,6,7])) header("Location: /neychurlava/kitchen/index.php"); // Roles 5,6,7 -> Kitchen
            else header("Location: /neychurlava/auth/login.php"); */ // Fallback safety redirect
            
            if (in_array($u['role_id'],[1,2]))       header("Location: /neychurlava/admin/index.php");
            elseif (in_array($u['role_id'],[3,4]))   header("Location: /neychurlava/cashier/index.php");
            elseif (in_array($u['role_id'],[5,6,7])) header("Location: /neychurlava/kitchen/index.php");
            elseif (in_array($u['role_id'],[8,9]))   header("Location: /neychurlava/rider/index.php");
            else                                      header("Location: /neychurlava/auth/login.php");

            // Stop executing this login script immediately after redirecting
            exit();
        } 
        $stmt->close();

        // Try CUSTOMER login 
        $stmt2 = $conn->prepare("
            SELECT customer_id, first_name, last_name, email, customer_type
            FROM Customer
            WHERE email = ? AND password_hash = ?
            LIMIT 1
        ");
        $stmt2->bind_param("ss", $email, $hash);
        $stmt2->execute();
        $res2 = $stmt2->get_result();

        if ($res2->num_rows === 1) {
            $c = $res2->fetch_assoc();
            $_SESSION['customer_id']    = $c['customer_id'];
            $_SESSION['customer_name']  = $c['first_name'].' '.$c['last_name'];
            $_SESSION['customer_email'] = $c['email'];
            $stmt2->close();
            header("Location: /neychurlava/customer/dashboard.php");
            exit();
        }
        $stmt2->close();

        $error = 'Invalid email or password. Please try again.';
    }
}

// EXTERNAL ACCESS CONTROL ALERTS
// If another page redirected here with '?error=access' in the URL, display a permission message
if (isset($_GET['error']) && $_GET['error'] === 'access') {
    $error = 'You do not have permission to access that page.';
}
$show_customer_hint = ($redirect === 'customer');

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Login — Neychurlava Food-House</title>
        <link rel="stylesheet" href="/neychurlava/assets/css/customer.css">
        <link rel="shortcut icon" href="../assets/favicon.png" type="image/x-icon">
        <style>
            :root {
                --pri-color: #016924;
                --sec-color: #1c9d05;
            }
            .login-body{ margin: 50px; display:flex;align-items:center;justify-content:center;
                min-height:100vh;background:linear-gradient( 135deg, var(--sec-color), var(--sec-color))}
            .login-card{background:#fff;border-radius:16px;padding:44px 40px;
                width:100%;max-width:440px;box-shadow:0 16px 56px rgba(0,0,0,.28)}
            .login-logo{font-size:52px;text-align:center;margin-bottom:10px}
            .login-title{text-align:center;font-size:1.4rem;font-weight:900;color:var(--sec-color);margin-bottom:4px}
            .login-sub{text-align:center;color:#6B7280;font-size:.88rem;margin-bottom:28px}
            .login-tabs{display:flex;gap:0;border:2px solid #E5E7EB;border-radius:10px;
                margin-bottom:24px;overflow:hidden}
            .login-tab{flex:1;padding:10px;font-weight:700;font-size:.88rem;cursor:pointer;
                border:none;background:#fff;color:#6B7280;transition:all .2s}
            .login-tab.active{background:var(--pri-color);color:#fff}
            .login-hint{margin-top:20px;background:#FFF5F0;border-radius:10px;
                padding:14px 18px;font-size:.8rem;color:#92400E;line-height:1.9}
            .login-hint code{background:#FECBA1;padding:2px 7px;border-radius:4px}
        </style>
    </head>
    <body class="login-body">
        <div class="login-card">
        <div class="login-logo">
            <img src="../assets/logo.svg" alt="" style="width: 100px; border: 2px solid green; border-radius: 50%;">
        </div>
        <h1 class="login-title">Neychurlava Food-House</h1>
        <p class="login-sub">Sign in to your account</p>

        <?php if ($error): ?>
        <div class="c-alert c-alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['registered'])): ?>
        <div class="c-alert c-alert-success">Account created! You can now log in.</div>
        <?php endif; ?>

        <div class="login-tabs">
            <button class="login-tab active" id="tabStaff"    onclick="switchTab('staff')">👤 Staff</button>
            <button class="login-tab"        id="tabCustomer" onclick="switchTab('customer')">🛒 Customer</button>
        </div>

        <form method="POST" action="">
            <div class="form-group">
            <label style="font-weight:600;font-size:.88rem;color:#2E4057;display:block;margin-bottom:6px">
                Email Address
            </label>
            <input type="email" name="email" class="form-control"
                    placeholder="Enter your email"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required autofocus>
            </div>
            <div class="form-group">
            <label style="font-weight:600;font-size:.88rem;color:#2E4057;display:block;margin-bottom:6px">
                Password
            </label>
            <input type="password" name="password" class="form-control"
                    placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-dark btn-full" style="margin-top:8px;font-size:1rem;padding:14px">
            Log In →
            </button>
        </form>

        <div style="text-align:center;margin-top:20px;font-size:.88rem;color:#6B7280">
            New customer?
            <a href="/neychurlava/auth/register.php" style="color:#F4845F;font-weight:700">
            Create an account
            </a>
        </div>

        <!-- <div class="login-hint" id="staffHint">
            <strong>Staff Demo Logins</strong><br>
            Admin:   <code>maria.reyes@neychurlava.ph</code><br>
            Cashier: <code>ana.garcia@neychurlava.ph</code><br>
            Kitchen: <code>luisa.abellana@neychurlava.ph</code><br>
            Password: <code>neychurlava2026</code>
        </div> -->
        
        <div class="login-hint" id="customerHint" style="display:none">
            <strong>Customer Login</strong><br>
            Register a new account using the link above,<br>
            or use: <code>jessa.adriano@gmail.com</code> (if you set a password)
        </div>
        </div>
        <script>
            function switchTab(t) {
            document.getElementById('tabStaff').classList.toggle('active', t==='staff');
            document.getElementById('tabCustomer').classList.toggle('active', t==='customer');
            document.getElementById('staffHint').style.display    = t==='staff'    ? '' : 'none';
            document.getElementById('customerHint').style.display = t==='customer' ? '' : 'none';
            }
            <?php if ($show_customer_hint): ?>switchTab('customer');<?php endif; ?>
        </script>
    </body>
</html>
