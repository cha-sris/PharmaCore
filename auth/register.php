<?php
// Initialize the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pull in the database setup from your file
require_once "../config/config.php"; 

// Initialize variables and error array
$email = $username = $password = $confirm_password = "";
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Validate Email
    if (empty($_POST['email'])) {
        $errors['email'] = "Email is required.";
    } else {
        $email = trim($_POST['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Invalid email format.";
        } else {
            // Check if email already exists
            $sql = "SELECT id FROM users WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $email]);
            if ($stmt->rowCount() > 0) {
                $errors['email'] = "This email is already registered.";
            }
        }
    }

    // 2. Validate Username
    if (empty($_POST['username'])) {
        $errors['username'] = "Username is required.";
    } else {
        $username = trim($_POST['username']);
        if (strlen($username) < 3) {
            $errors['username'] = "Username must be at least 3 characters long.";
        } else {
            // Check if username already exists
            $sql = "SELECT id FROM users WHERE username = :username";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':username' => $username]);
            if ($stmt->rowCount() > 0) {
                $errors['username'] = "This username is already taken.";
            }
        }
    }

    // 3. Validate Password
    if (empty($_POST['password'])) {
        $errors['password'] = "Password is required.";
    } else {
        $password = $_POST['password'];
        if (strlen($password) < 8) {
            $errors['password'] = "Password must be at least 8 characters long.";
        }
    }

    // 4. Validate Confirm Password
    if (empty($_POST['confirm_password'])) {
        $errors['confirm_password'] = "Please confirm your password.";
    } else {
        $confirm_password = $_POST['confirm_password'];
        if ($password !== $confirm_password) {
            $errors['confirm_password'] = "Passwords do not match.";
        }
    }

    // If there are no errors, proceed to save to database
    if (empty($errors)) {
        // Hash the password securely
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
         
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $hashed_password
            ]);

            // Registration successful! Alert and redirect to login page
            echo "<script>alert('Registration successful!'); window.location.href='login.php';</script>";
            exit;
            
        } catch(PDOException $e) {
            echo "<script>alert('Oops! Something went wrong. Please try again later.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="shortcut icon" href="../assets/images/pharmacore_icon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <style>
        .error-message {
            color: #ff4d4d;
            font-size: 0.85rem;
            margin-top: 4px;
        }
    </style>
</head>
<body>

    <div class="auth-header">
        <img src="../assets/images/pharmacore_icon.svg" alt="icon">
        <h2>Sign up to PharmaCore</h2>
    </div>

    <form action="" method="post">

        <div class="form-group">
            <label for="email">Email</label>
            <br>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required autofocus>
            <?php if (isset($errors['email'])): ?>
                <div class="error-message"><?php echo $errors['email']; ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="username">Username</label>
            <br>
            <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username); ?>" required>
            <?php if (isset($errors['username'])): ?>
                <div class="error-message"><?php echo $errors['username']; ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <br>
            <input type="password" name="password" id="password" required>
            <?php if (isset($errors['password'])): ?>
                <div class="error-message"><?php echo $errors['password']; ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <br>
            <input type="password" name="confirm_password" id="confirm_password" required>
            <?php if (isset($errors['confirm_password'])): ?>
                <div class="error-message"><?php echo $errors['confirm_password']; ?></div>
            <?php endif; ?>
        </div>

        <button type="submit">Sign Up</button>

        <div class="auth-footer">
            <p>Already have an account? <a href="login.php">&nbsp;Login here</a></p>
        </div>
        
    </form>

</body>
</html>