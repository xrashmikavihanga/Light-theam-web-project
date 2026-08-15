<?php
session_start();
include('connection.php');

$errorMsg = '';
$successMsg = '';
$redirectTarget = '';

// Track whether the user is already logged in
$alreadyLoggedIn = false;
if (isset($_SESSION['userId'])) {
    $alreadyLoggedIn = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userName = trim($_POST['userName'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($userName) || empty($password)) {
        $errorMsg = "Please enter both username and password.";
    } else {
        // Fetch user record
        $stmt = $conn->prepare("SELECT userId, userName, password, role FROM users WHERE userName = ?");
        $stmt->bind_param("s", $userName);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $row = $result->fetch_assoc()) {
            // Verify hashed password
            if (password_verify($password, $row['password'])) {
                session_regenerate_id(true);

                // Store user session data
                $_SESSION['userId'] = $row['userId'];
                $_SESSION['userName'] = $row['userName'];
                $_SESSION['role'] = $row['role'] ?? 'user';

                $role = $_SESSION['role'] ?? 'user';
                $redirectTarget = $role === 'admin' ? 'admin.php' : 'homepage.php';
                $successMsg = $role === 'admin'
                    ? "Login successful. Redirecting to admin dashboard..."
                    : "Login successful. Redirecting to homepage...";
            } else {
                $errorMsg = "Invalid username or password.";
            }
        } else {
            $errorMsg = "Invalid username or password.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="./styles/style.css?v=<?php echo time(); ?>">
</head>
<body class="auth-body">

    <div class="auth-container">
        <section class="auth-visual">
            <div class="brand-label">Disaster Management System</div>
            <h1>Secure access for emergency coordination</h1>
            <p>Monitor incidents, manage response teams, and keep communities informed through a unified, secure portal.</p>

            <div class="feature-list">
                <div class="feature-item"><span class="feature-badge">✓</span> Rapid incident reporting</div>
                <div class="feature-item"><span class="feature-badge">✓</span> Role-based dashboard access</div>
                <div class="feature-item"><span class="feature-badge">✓</span> Admin approval & review</div>
            </div>
        </section>

        <section class="auth-form-panel">
            <div class="login-card auth-form">
                <div class="header">
                    <h2>Sign in to start response operations</h2>
                    <p>Enter your credentials to access the Disaster Management dashboard.</p>
                </div>
                <hr>

                <?php if (!empty($successMsg)): ?>
                    <div class="success-message"><?php echo htmlspecialchars($successMsg); ?></div>
                <?php endif; ?>

                <?php if (!empty($errorMsg)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($errorMsg); ?></div>
                <?php endif; ?>

                <form action="index.php" method="POST">
                    <div class="form-group">
                        <label for="userName">Username</label>
                        <input type="text" name="userName" id="userName" placeholder="Username" value="<?php echo htmlspecialchars($_POST['userName'] ?? ''); ?>" required>

                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" placeholder="Password" required>

                        <button type="submit" class="login-btn">Log In</button>
                    </div>
                </form>

                <a href="createAccount.php" class="hyper">Need a new account? Register</a>
            </div>
        </section>
    </div>

    <?php if (!empty($successMsg) && !empty($redirectTarget)): ?>
        <script>
            setTimeout(() => {
                window.location.href = '<?php echo htmlspecialchars($redirectTarget); ?>';
            }, 1500);
        </script>
    <?php endif; ?>
</body>
</html>
