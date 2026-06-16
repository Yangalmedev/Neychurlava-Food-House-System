
<?php

/* <?php
require_once '../config/session.php';
session_destroy();
header("Location: /neychurlava/auth/login.php");
exit();
?> */

require_once '../config/session.php';

// Only destroy the session if the user confirmed the action
if (isset($_POST['action']) && $_POST['action'] === 'confirm') {
    session_destroy();
    header("Location: /neychurlava/auth/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Logout</title>
    <link rel="shortcut icon" href="/neychurlava/assets/favicon.png" type="image/x-icon">
    <style>
        /* Blurred, dark background overlay */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: #2f9114;
            backdrop-filter: blur(4px);
            display: flex; justify-content: center; align-items: center;
            z-index: 9999;
        }

        /* Small pop-up window box */
        .modal-box {
            background: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 400px;
            width: 90%;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        .modal-box h2 {
            margin-top: 0;
            color: #333333;
            font-size: 20px;
        }
        .modal-box p {
            color: #666666;
            margin-bottom: 25px;
            font-size: 14px;
        }
        
        /* Buttons layout */
        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-confirm {
            background-color: #545454;
            color: white;
        }
        .btn-confirm:hover {
            background-color: #929292;
        }
        .btn-cancel {
            background-color: #28a745;
            color: white;
        }
        .btn-cancel:hover {
            background-color: #28c432;
        }
    </style>
</head>
<body>

    <!-- The custom pop-up window -->
    <div class="modal-overlay">
        <div class="modal-box">
            <h2>Confirm Logout</h2>
            <p>Are you sure you want to log out of your account?</p>
            
            <div class="modal-buttons">
                <!-- Form POSTs to this same page to trigger session_destroy() safely -->
                <form method="POST" action="">
                    <input type="hidden" name="action" value="confirm">
                    <button type="submit" class="btn btn-confirm">Log Out</button>
                </form>
                
                <!-- Cancel button returns user to previous page using JavaScript -->
                <button type="button" class="btn btn-cancel" onclick="window.history.back();">Cancel</button>
            </div>
        </div>
    </div>

</body>
</html>
