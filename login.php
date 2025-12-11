<?php
    require_once 'config.php';
    
    if (!defined('CSV_DUMP_PASSWORD')) {
        die('CSV dump password not configured. Please define CSV_DUMP_PASSWORD in config.php');
    }
    
    /* Your password */
    $password = CSV_DUMP_PASSWORD;

    /* Redirects here after login */
    $redirect_after_login = 'csv_dump.php';

    /* Will not ask password again for */
    $remember_password = strtotime('+30 days'); // 30 days

    if (isset($_POST['password']) && $_POST['password'] == $password) {
        // Set secure cookie with HttpOnly and SameSite flags
        setcookie("password", $password, [
            'expires' => $remember_password,
            'path' => '/',
            'secure' => true,  // Only send over HTTPS
            'httponly' => true,  // Not accessible via JavaScript
            'samesite' => 'Strict'  // CSRF protection
        ]);
        header('Location: ' . $redirect_after_login);
        exit;
    }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Password protected</title>
</head>
<body>
    <div style="text-align:center;margin-top:50px;">
        You must enter the password to view this content.
        <form method="POST">
            <input type="password" name="password" required>
            <input type="submit" value="Login">
        </form>
    </div>
</body>
</html>