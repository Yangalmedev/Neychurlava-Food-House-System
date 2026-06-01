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
        $stmt = $conn->prepare("
            SELECT s.staff_id, s.first_name, s.last_name, s.role_id, r.role_name
            FROM Staff s INNER JOIN Role r ON s.role_id = r.role_id
            WHERE s.email = ? AND s.password_hash = ? AND s.status = 'Active' LIMIT 1
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
            
            // Redirect the user based on their specific Role ID
            if (in_array($u['role_id'],[1,2]))       header("Location: /neychurlava/admin/index.php"); // Roles 1,2 -> Admin
            elseif (in_array($u['role_id'],[3,4]))   header("Location: /neychurlava/cashier/index.php"); // Roles 3,4 -> Cashier
            elseif (in_array($u['role_id'],[5,6,7])) header("Location: /neychurlava/kitchen/index.php"); // Roles 5,6,7 -> Kitchen
            else                                      header("Location: /neychurlava/auth/login.php"); // Fallback safety redirect
            
            // Stop executing this login script immediately after redirecting
            exit();
        } else {
            // If no match is found, or if the account status is not 'Active'
            $error = 'Invalid email/password or account is inactive.';
        }
        // Close the database statement to free up resources
        $stmt->close();
    }
}

// EXTERNAL ACCESS CONTROL ALERTS
// If another page redirected here with '?error=access' in the URL, display a permission message
if (isset($_GET['error']) && $_GET['error'] === 'access') $error = 'You do not have permission to view that page.';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login – Neychurlava</title>
    <link rel="stylesheet" href="/neychurlava/assets/css/style.css">
    <link rel="shortcut icon" href="/neychurlava/assets/favicon.png" type="image/x-icon">
</head>
<body class="login-body">
<div class="login-card">
    
    <div class="login-logo"><img src="../assets/logo.svg" style="width: 60px;"></div>
    <h1 class="login-title">Neychurlava Food-House</h1>
    <p class="login-sub">Food Ordering & Management System</p>
    
    <!-- 5. ERROR ALERT DISPLAY -->
    <!-- If the $error variable contains a message, display it safely inside an alert box -->
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    
    <div style="width: 100%;">

        <!-- THE LOGIN FORM -->
        <form method="POST">
            
            <div class="form-group">
                <label>Email Address</label>
                <!-- Keeps the submitted email visible using htmlspecialchars if the login failed -->
                <input type="email" name="email" class="form-control" placeholder="staff@neychurlava.ph"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter password" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Log In</button>
        </form>
    </div>

    <div class="login-hint">
        <code>by - amia and hjbs</code>
    </div>
    
    <!-- <div class="login-hint">
        <strong>Demo Login</strong><br>
        Email: <code>maria.reyes@neychurlava.ph</code> (Admin)<br>
        Email: <code>ana.garcia@neychurlava.ph</code> (Cashier)<br>
        Email: <code>luisa.abellana@neychurlava.ph</code> (Kitchen)<br>
        Password for all: <code>neychurlava2025</code>
    </div> -->
</div>
</body></html>
