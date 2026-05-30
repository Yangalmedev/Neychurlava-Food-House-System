<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function require_login() {
    if (!isset($_SESSION['staff_id'])) {
        header("Location: /neychurlava/auth/login.php");
        exit();
    }
}
function require_role(array $ids) {
    require_login();
    if (!in_array($_SESSION['role_id'], $ids)) {
        header("Location: /neychurlava/auth/login.php?error=access");
        exit();
    }
}
function is_admin()    { return isset($_SESSION['role_id']) && in_array($_SESSION['role_id'],[1,2]); }
function is_cashier()  { return isset($_SESSION['role_id']) && in_array($_SESSION['role_id'],[3,4]); }
function is_kitchen()  { return isset($_SESSION['role_id']) && in_array($_SESSION['role_id'],[5,6,7]); }
?>
