<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'neychurlava_db');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("<div style='font-family:Arial;padding:30px;color:red'>
        <h3>Database Connection Failed</h3>
        <p>".$conn->connect_error."</p>
        <p>Make sure XAMPP MySQL is running and neychurlava_db exists.</p>
    </div>");
}
$conn->set_charset("utf8mb4");
?>
