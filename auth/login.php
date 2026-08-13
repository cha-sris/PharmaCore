<?php
// Initialize the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect the user to the dashboard if they are already logged in
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: dashboard.php"); 
    exit;
}

// Pull in the database setup
require_once "../config/config.php";

// Initialize variables and error array
$username = $password = "";
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Check if username/email is empty
    if (empty(trim($_POST['username']))) {
        $errors['username'] = "Please enter your username.";
    } else {
        $username = trim($_POST['username']);
    }

    // Check if password is empty
    if (empty(trim($_POST['password']))) {
        $errors['password'] = "Please enter your password.";
    } else {
        $password = trim($_POST['password']);
    }

    // Validate credentials if no structural field errors exist
    if (empty($errors)) {
        // Prepare a select statement
        $sql = "SELECT id, username, password FROM users WHERE username = :username OR email = :username";
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':username' => $username]);
            
            // Check if username exists
            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $hashed_password = $user['password'];
                
                // Verify the hashed password
                if (password_verify($password, $hashed_password)) {
                    // Password is correct, start a new session
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $user['id'];
                    $_SESSION["username"] = $user['username'];
                    
                    // Redirect user to dashboard
                    header("location: ../modules/dashboard.php");
                    exit;
                } else {
                    // Display a generic error message for security reasons
                    $errors['login'] = "Invalid username or password.";
                    // $username = "";
                }
            } else {
                $errors['login'] = "Invalid username or password.";
                // $username = "";
            }
        } 
        catch (PDOException $e) {
            $errors['login'] = "Oops! Something went wrong. Please try again later.";
            // Clear BOTH username and password on wrong input/error
        }
    }
            $username = "";
            $password = "";
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="shortcut icon" href="../assets/images/pharmacore_icon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <style>
        .error-message {
            color: #ff4d4d;
            font-size: 0.85rem;
            margin-top: 4px;
            margin-bottom: 12px;
        }
        /* .global-error {
            background-color: rgba(255, 77, 77, 0.15);
            border: 1px solid rgba(255, 77, 77, 0.3);
            padding: 10px 14px;
            border-radius: 6px;
            color: #ff4d4d;
            text-align: center;
            margin-bottom: 16px;
            font-size: 0.9rem;
        } */
    </style>
</head>
<body>

    <div class="auth-header">
        <img src="../assets/images/pharmacore_icon.svg" alt="icon">
        <h2>Login to PharmaCore</h2>
    </div>

    <form action="" method="post" autocomplete="off" novalidate>

        <!-- Display generic invalid credentials error here -->
        <?php if (isset($errors['login'])): ?>
            <div class="error-message"><?php echo $errors['login']; ?></div>
        <?php endif; ?>

        <div class="form-group">
            <label for="username">Username or Email</label>
            <br>
            <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username); ?>"  required autofocus autocomplete="off">
            <?php if (isset($errors['username'])): ?>
                <div class="error-message"><?php echo $errors['username']; ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <br>
            <input type="password" name="password" id="password" value=""  required autocomplete="new-password">
            <?php if (isset($errors['password'])): ?>
                <div class="error-message"><?php echo $errors['password']; ?></div>
            <?php endif; ?>
        </div>

        <button type="submit">Login</button>

        <div class="auth-footer">
            <p>Don't have an account? <a href="register.php">&nbsp;Sign up here</a></p>
        </div>
        
    </form>

</body>
</html>