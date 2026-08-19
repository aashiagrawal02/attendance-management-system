<?php
// ============================================================
//  index.php — Admin Login Page
// ============================================================

// Start a session so we can remember who is logged in
session_start();

// If admin is already logged in, send them to dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Connect to the database
require_once 'db.php';

$error = ''; // Will hold error messages

// --- Handle Login Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get username and password from the form
    // mysqli_real_escape_string() prevents SQL Injection attacks
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = trim($_POST['password']);

    // Basic validation: fields must not be empty
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        // Search for the admin in the database
        $sql    = "SELECT * FROM admin WHERE username = '$username' LIMIT 1";
        $result = mysqli_query($conn, $sql);
        $admin  = mysqli_fetch_assoc($result);

        // password_verify() checks the entered password against the stored hash
        if ($admin && password_verify($password, $admin['password'])) {
            // Login SUCCESS — save admin ID in session
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['username'];

            // Redirect to dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid username or password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>EduTrack — Login</title>
    <link rel="stylesheet" href="style.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet"/>
</head>
<body class="login-body">

    <div class="login-card">
        <!-- Logo / Branding -->
        <div class="login-logo">
            <span class="login-icon">📘</span>
            <h1 class="login-title">EduTrack</h1>
            <p class="login-sub">Attendance Management System</p>
        </div>

        <!-- Show error message if login failed -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Enter your username"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    autocomplete="username"
                    required
                />
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                />
            </div>

            <button type="submit" class="btn btn-primary btn-block">Login →</button>
        </form>

        <p class="login-hint">Default: <strong>admin</strong> / <strong>admin123</strong></p>
    </div>

</body>
</html>
