<?php
// Initialize the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect the user to the dashboard if they are already logged in
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: ../modules/dashboard.php"); 
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
        $sql = "SELECT id, username, password FROM users WHERE username = :username OR email = :username";
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':username' => $username]);
            
            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $hashed_password = $user['password'];
                
                if (password_verify($password, $hashed_password)) {
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $user['id'];
                    $_SESSION["username"] = $user['username'];
                    
                    header("location: ../modules/dashboard.php");
                    exit;
                } else {
                    $errors['login'] = "Invalid username or password.";
                }
            } else {
                $errors['login'] = "Invalid username or password.";
            }
        } 
        catch (PDOException $e) {
            $errors['login'] = "Oops! Something went wrong. Please try again later.";
        }
    }
    // Clear sensitive data
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
</head>
<body>

    <div class="auth-header">
        <img src="../assets/images/pharmacore_icon.svg" alt="icon">
        <h2>Login to PharmaCore</h2>
    </div>

    <form action="" method="post" id="loginForm" autocomplete="off" novalidate>

        <?php if (isset($errors['login'])): ?>
            <div class="error-message"><?php echo $errors['login']; ?></div>
        <?php endif; ?>

        <div class="form-group">
            <label for="username">Username or Email</label>
            <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username); ?>" required autofocus autocomplete="off">
            <span class="field-error" id="username-error"></span>
            <?php if (isset($errors['username'])): ?>
                <div class="error-message"><?php echo $errors['username']; ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" value="" required autocomplete="new-password">
            <span class="field-error" id="password-error"></span>
            <?php if (isset($errors['password'])): ?>
                <div class="error-message"><?php echo $errors['password']; ?></div>
            <?php endif; ?>
        </div>

        <button type="submit">Login</button>

        <div class="auth-footer">
            <p>Don't have an account? <a href="register.php">&nbsp;Sign up here</a></p>
        </div>
        
    </form>

    <script>
        // Live validation for Login page
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const fields = ['username', 'password'];
            
            fields.forEach(id => {
                const input = document.getElementById(id);
                if (input) {
                    ['input', 'change', 'blur'].forEach(eventType => {
                        input.addEventListener(eventType, function() {
                            validateLoginField(this);
                        });
                    });
                }
            });

            form.addEventListener('submit', function(e) {
                let allValid = true;
                fields.forEach(id => {
                    const input = document.getElementById(id);
                    if (input && !validateLoginField(input)) {
                        allValid = false;
                    }
                });
                if (!allValid) {
                    e.preventDefault();
                    const firstError = form.querySelector('.field-error:not(:empty)');
                    if (firstError) {
                        const fieldId = firstError.id.replace('-error', '');
                        const field = document.getElementById(fieldId);
                        if (field) field.focus();
                    }
                }
            });
        });

        function validateLoginField(field) {
            const id = field.id;
            const value = field.value.trim();
            const errorId = id + '-error';
            const errorEl = document.getElementById(errorId);
            if (!errorEl) return true;

            let errorMsg = '';

            switch (id) {
                case 'username':
                    if (value === '') {
                        errorMsg = 'Please enter your username or email.';
                    }
                    break;
                case 'password':
                    if (value === '') {
                        errorMsg = 'Please enter your password.';
                    }
                    break;
                default:
                    break;
            }

            errorEl.textContent = errorMsg;
            if (errorMsg) {
                field.classList.add('error');
            } else {
                field.classList.remove('error');
            }
            return errorMsg === '';
        }
    </script>

</body>
</html>