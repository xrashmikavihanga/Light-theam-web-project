<?php
include('connection.php');

$errorMsg = '';
$sucessMsg = '';
$redirect = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $userName = trim($_POST['userName'] ?? '');
    $password = $_POST['password'] ?? '';
    $reEnteredPassword = $_POST['rePassword'] ?? '';
    $university = $_POST['university'] ?? 'No_University';
    $studentId = trim($_POST['studentId'] ?? 'No_StudentID');
    
    // Sanitize contact number so it stores as an integer
    $contactRaw = trim($_POST['contact'] ?? '');
    $contactNumber = !empty($contactRaw) ? (int)$contactRaw : null;
    
    if (empty($fullName) || empty($email) || empty($password) || empty($reEnteredPassword) || empty($userName)) {
        $errorMsg = "Please fill in all required fields to continue.";
    } elseif ($password !== $reEnteredPassword) {
        $errorMsg = "Passwords do not match.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = "Please enter a valid email address.";
    } else {

        // CRUD: READ - check whether the username or email already exists before creating the account
        $checkStmt = $conn->prepare("
            SELECT COUNT(*) as userCount 
            FROM users u 
            LEFT JOIN userData ud ON u.userId = ud.userId 
            WHERE u.userName = ? OR ud.email = ?
        ");
        $checkStmt->bind_param("ss", $userName, $email);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result && $row = $result->fetch_assoc()) {
            if ($row['userCount'] > 0) {
                $errorMsg = "Username or Email is already registered!";
            } else {

                // CRUD: CREATE - insert the user login record into the users table
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $insertUser = $conn->prepare("INSERT INTO users (userName, password) VALUES (?, ?)");
                $insertUser->bind_param("ss", $userName, $hashedPassword);

                if ($insertUser->execute()) {
                    $newUserId = $conn->insert_id; // Get generated userId

                    // CRUD: CREATE - insert the user profile information into the userData table
                    $insertData = $conn->prepare("
                        INSERT INTO userData (userId, fullName, email, university, studentId, contactNumber) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    
                    // Bind types: i = int (userId), s = string (fullName), s = string (email), 
                    //             s = string (university), s = string (studentId), i = int (contactNumber)
                    $insertData->bind_param("issssi", $newUserId, $fullName, $email, $university, $studentId, $contactNumber);

                    if ($insertData->execute()) {
                        $sucessMsg = "Account Creation Successful!";
                        $redirect = true;
                    } else {
                        // CRUD: DELETE - clean up the orphan user record if the profile insert fails
                        $conn->query("DELETE FROM users WHERE userId = '$newUserId'");
                        $errorMsg = "Failed to save profile details: " . $insertData->error;
                    }
                    $insertData->close();
                } else {
                    $errorMsg = "Failed to create user account: " . $insertUser->error;
                }
                $insertUser->close();
            }
        } else {
            $errorMsg = "Database error checking availability.";
        }
        $checkStmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <link rel="stylesheet" href="./styles/style.css?v=<?php echo time(); ?>">
</head>
<body class="auth-body">

    <div class="auth-container">
        <div class="auth-visual">
            <span class="brand-label">Group 21 • Disaster Management</span>
            <h1>Register to report incidents and stay informed.</h1>
            <p>Join your campus safety network and help keep the community aware with verified alerts and response updates.</p>

            <div class="feature-list">
                <div class="feature-item">
                    <span class="feature-badge">✓</span>
                    Secure credentials and student verification
                </div>
                <div class="feature-item">
                    <span class="feature-badge">✓</span>
                    Fast incident reporting for admin review
                </div>
                <div class="feature-item">
                    <span class="feature-badge">✓</span>
                    Access curated disaster news and alerts
                </div>
            </div>
        </div>

        <div class="auth-form-panel">
            <div class="accountCreateCard auth-form">
                <div class="header">
                    <h2>Create Account</h2>
                    <p>Fill in the details to join Group 21's disaster response project.</p>
                </div>

                <?php if (!empty($errorMsg)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($errorMsg); ?></div>
                <?php endif; ?>

                <?php if (!empty($sucessMsg)): ?>
                    <div class="info-message">
                        <?php echo htmlspecialchars($sucessMsg); ?>
                    </div>
                <?php endif; ?>

                <form action="" method="post" class="auth-form">
                    <div class="form-group">
                        <label for="fullName">Full Name</label>
                        <input type="text" name="fullName" id="fullName" placeholder="Full Name" value="<?php echo htmlspecialchars($fullName ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" placeholder="Email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="userName">Username</label>
                        <input type="text" name="userName" id="userName" placeholder="Username" value="<?php echo htmlspecialchars($userName ?? ''); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group half-width">
                            <label for="password">Password</label>
                            <input type="password" name="password" id="password" placeholder="Password" required>
                        </div>
                        <div class="form-group half-width">
                            <label for="rePassword">Confirm Password</label>
                            <input type="password" name="rePassword" id="rePassword" placeholder="Re-enter Password" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group half-width">
                            <label for="university">University</label>
                            <select name="university" id="university">
                                <option value="UOC" <?php if(($university ?? '') === 'UOC') echo 'selected'; ?>>University of Colombo</option>
                                <option value="UOM" <?php if(($university ?? '') === 'UOM') echo 'selected'; ?>>University of Moratuwa</option>
                                <option value="UOP" <?php if(($university ?? '') === 'UOP') echo 'selected'; ?>>University of Peradeniya</option>
                                <option value="USJ" <?php if(($university ?? '') === 'USJ') echo 'selected'; ?>>University of Sri Jayewardenepura</option>
                                <option value="UOR" <?php if(($university ?? '') === 'UOR') echo 'selected'; ?>>University of Ruhuna</option>
                                <option value="UOJ" <?php if(($university ?? '') === 'UOJ') echo 'selected'; ?>>University of Jaffna</option>
                                <option value="UORJ" <?php if(($university ?? '') === 'UORJ') echo 'selected'; ?>>Rajarata University of Sri Lanka</option>
                                <option value="other" <?php if(($university ?? '') === 'other') echo 'selected'; ?>>Other</option>
                            </select>
                        </div>
                        <div class="form-group half-width">
                            <label for="studentId">Student ID</label>
                            <input type="text" name="studentId" id="studentId" placeholder="Student ID" value="<?php echo htmlspecialchars($studentId ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="contact">Contact Number</label>
                        <input type="tel" name="contact" id="contact" placeholder="Contact Number" value="<?php echo htmlspecialchars($_POST['contact'] ?? ''); ?>" pattern="[0-9+\- ]*" inputmode="tel" onwheel="this.blur();">
                    </div>

                    <p class="note-text">🔒 All credentials are encrypted by the system.</p>
                    <button type="submit" class="login-btn">Create Your Account</button>
                </form>

                <a href="index.php" class="hyper">Already have an account? Log in</a>
            </div>
        </div>
    </div>

    <?php if ($redirect): ?>
        <script>
            alert("<?php echo addslashes($sucessMsg); ?>");
            window.location.href = "index.php";
        </script>
    <?php endif; ?>

</body>
</html>