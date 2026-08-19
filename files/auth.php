<?php
// ============================================================
//  auth.php — Session / Auth Guard
//  Include this at the top of every protected page.
//  If the user is NOT logged in, it redirects to login.
// ============================================================

session_start();

if (!isset($_SESSION['admin_id'])) {
    // Not logged in — redirect to login page
    header("Location: index.php");
    exit();
}

// Logged in — we can use $_SESSION['admin_name'] anywhere
?>
