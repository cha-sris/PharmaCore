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
    <link rel="shortcut icon" href="../assets/images/box_pill.svg" type="image/svg+xml">

    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/auth.css">

</head>
<body>

    <div class="auth-header">
            <img src="../assets/images/box_pill.svg" alt="icon">
            <h2>Sign in to PharmaCore</h2>
    </div>

    <form action="login.php" method="post">

        <div class="form-group">
            <label for="username">Username</label>
            <br>
            <input type="text" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <br>
            <input type="password" required>
        </div>

        <button type="submit">Sign In</button>

        <div class="auth-footer">
            <p>Don't have an account yet? <a href="register.php">&nbsp;Register here</a></p>
        </div>
        
    </form>

</body>
</html>