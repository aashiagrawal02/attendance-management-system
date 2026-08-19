<?php
// ============================================================
//  logout.php — Destroy Session and Redirect to Login
// ============================================================

session_start();

// Remove all session data (effectively logs out the admin)
session_destroy();

// Redirect back to login page
header("Location: index.php");
exit();
?>
