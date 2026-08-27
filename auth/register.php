<?php
// (PHP logic unchanged – same as before)
// Initialize the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/config.php"; 

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
        if (!preg_match('/^(?!.*\.\.)(?!^\.)[a-zA-Z0-9._]{1,30}(?<!\.)$/', $username)) {
            $errors['username'] = "Username must be 1–30 characters, using only letters, numbers, underscores, and periods (cannot start/end with a period or have consecutive periods).";
        } else {
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
        $pwd_errors = [];

        if (strlen($password) < 8) {
            $pwd_errors[] = "at least 8 characters long";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $pwd_errors[] = "at least one uppercase letter";
        }
        if (!preg_match('/[a-z]/', $password)) {
            $pwd_errors[] = "at least one lowercase letter";
        }
        if (!preg_match('/[0-9]/', $password)) {
            $pwd_errors[] = "at least one number";
        }
        if (!preg_match('/[\W_]/', $password)) {
            $pwd_errors[] = "at least one special character";
        }

        if (!empty($pwd_errors)) {
            $errors['password'] = "Password must include:<br>• " . implode("<br>• ", $pwd_errors);
            $password = "";
            $confirm_password = "";
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

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $hashed_password
            ]);
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
</head>
<body>

    <div class="auth-header">
        <img src="../assets/images/pharmacore_icon.svg" alt="icon">
        <h2>Sign up to PharmaCore</h2>
    </div>

    <form action="" method="post" id="registerForm" novalidate>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" required autofocus autocomplete="email">
            <span class="field-error" id="email-error"></span>
            <?php if (isset($errors['email'])): ?>
                <div class="error-message"><?php echo $errors['email']; ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username); ?>" required autocomplete="username">
            <span class="field-error" id="username-error"></span>
            <?php if (isset($errors['username'])): ?>
                <div class="error-message"><?php echo $errors['username']; ?></div>
            <?php endif; ?>
        </div>

        <!-- Dummy password field to confuse browser password manager -->
        <input type="password" style="display:none" tabindex="-1" autocomplete="new-password">

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" value="" autocomplete="new-password" required>
            <span class="field-error" id="password-error"></span>
            <?php if (isset($errors['password'])): ?>
                <div class="error-message"><?php echo $errors['password']; ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" value="" autocomplete="new-password" required>
            <span class="field-error" id="confirm_password-error"></span>
            <?php if (isset($errors['confirm_password'])): ?>
                <div class="error-message"><?php echo $errors['confirm_password']; ?></div>
            <?php endif; ?>
        </div>

        <button type="submit">Sign Up</button>

        <div class="auth-footer">
            <p>Already have an account? <a href="login.php">&nbsp;Login here</a></p>
        </div>
        
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const fields = ['email', 'username', 'password', 'confirm_password'];
            
            fields.forEach(id => {
                const input = document.getElementById(id);
                if (input) {
                    ['input', 'change', 'blur', 'focus'].forEach(eventType => {
                        input.addEventListener(eventType, function() {
                            validateRegisterField(this);
                        });
                    });
                }
            });

            form.addEventListener('submit', function(e) {
                let allValid = true;
                fields.forEach(id => {
                    const input = document.getElementById(id);
                    if (input && !validateRegisterField(input)) {
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

        function validateRegisterField(field) {
            const id = field.id;
            const value = field.value.trim();
            const errorId = id + '-error';
            const errorEl = document.getElementById(errorId);
            if (!errorEl) return true;

            let errorMsg = '';

            switch (id) {
                case 'email':
                    if (value === '') {
                        errorMsg = 'Email is required.';
                    } else {
                        // Strict email regex: requires dot in domain and valid TLD
                        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                        if (!emailRegex.test(value)) {
                            errorMsg = 'Please enter a valid email address (e.g., name@domain.com).';
                        } else {
                            // Additional checks: domain cannot start/end with dot, no consecutive dots
                            const parts = value.split('@');
                            if (parts.length === 2) {
                                const domain = parts[1];
                                if (domain.startsWith('.') || domain.endsWith('.') || domain.includes('..')) {
                                    errorMsg = 'Domain cannot start/end with dot or have consecutive dots.';
                                }
                            }
                        }
                    }
                    break;

                case 'username':
                    if (value === '') {
                        errorMsg = 'Username is required.';
                    } else if (!/^(?!.*\.\.)(?!^\.)[a-zA-Z0-9._]{1,30}(?<!\.)$/.test(value)) {
                        errorMsg = 'Must be 1–30 chars, letters/numbers/underscores/dots only (no consecutive dots, no leading/trailing dot).';
                    } else if (!/[a-zA-Z0-9]/.test(value)) {
                        errorMsg = 'Must contain at least one letter or number.';
                    }
                    break;

                case 'password':
                    if (value === '') {
                        errorMsg = 'Password is required.';
                    } else {
                        const pwdErrors = [];
                        if (value.length < 8) pwdErrors.push('at least 8 characters');
                        if (!/[A-Z]/.test(value)) pwdErrors.push('one uppercase letter');
                        if (!/[a-z]/.test(value)) pwdErrors.push('one lowercase letter');
                        if (!/[0-9]/.test(value)) pwdErrors.push('one number');
                        if (!/[\W_]/.test(value)) pwdErrors.push('one special character');
                        if (pwdErrors.length > 0) {
                            errorMsg = 'Password must include: ' + pwdErrors.join(', ');
                        }
                    }
                    const confirmField = document.getElementById('confirm_password');
                    if (confirmField && confirmField.value) {
                        validateRegisterField(confirmField);
                    }
                    break;

                case 'confirm_password':
                    const passwordField = document.getElementById('password');
                    const pwd = passwordField ? passwordField.value : '';
                    if (value === '') {
                        errorMsg = 'Please confirm your password.';
                    } else if (value !== pwd) {
                        errorMsg = 'Passwords do not match.';
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