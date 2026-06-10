<?php

//  Initialize the session
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}


?>




<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login</title>

    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/auth.css">

</head>
<body>
    <div class="auth-header">
        <h2>Pharmacore</h2>
        <h3>Medicine Inventory Management System</h3>
    </div>

    <form action="login.php" method="post">

        <div class="form-group">
            <label for="username">Username</label>
            <br>
            <input type="text" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <br>
            <input type="password" required>
        </div>

        <button type="submit">Sign In</button>
    </form>

    <div class="auth-footer">
        <p>Don't have an account yet? <a href="register.php">Register here</a></p>
    </div>
</body>
</html>