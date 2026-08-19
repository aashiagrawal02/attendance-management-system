<?php
// ============================================================
//  db.php — Database Connection
//  This file connects PHP to your MySQL database.
//  Include this file at the top of every PHP page that
//  needs database access using: require_once 'db.php';
// ============================================================

// --- Database settings ---
// These must match your XAMPP MySQL settings.
// By default XAMPP uses: host=localhost, user=root, password=''
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');            // Leave empty for default XAMPP
define('DB_NAME', 'attendance_system');

// --- Create connection ---
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// --- Check if connection failed ---
if (!$conn) {
    // Stop the script and show a helpful error message
    die("
        <div style='font-family:sans-serif;padding:40px;text-align:center;color:#c0392b;'>
            <h2>⚠️ Database Connection Failed</h2>
            <p>" . mysqli_connect_error() . "</p>
            <p>Make sure XAMPP is running and the database <b>attendance_system</b> exists.</p>
        </div>
    ");
}

// --- Set character encoding to UTF-8 ---
// This prevents issues with special characters like names in Hindi, etc.
mysqli_set_charset($conn, 'utf8');
?>
