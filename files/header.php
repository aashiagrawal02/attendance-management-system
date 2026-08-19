<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>EduTrack — <?= $pageTitle ?? 'Dashboard' ?></title>
    <link rel="stylesheet" href="style.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet"/>
</head>
<body>

<!-- ======== SIDEBAR ======== -->
<div class="layout">
<aside class="sidebar">
    <div class="sidebar-brand">
        <span>📘</span>
        <span>EduTrack</span>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php"        class="nav-link <?= ($pageTitle==='Dashboard')?'active':'' ?>">🏠 Dashboard</a>
        <a href="students.php"         class="nav-link <?= (strpos($pageTitle,'Student')!==false)?'active':'' ?>">👥 Students</a>
        <a href="classes.php"          class="nav-link <?= (strpos($pageTitle,'Class')!==false)?'active':'' ?>">🏫 Classes &amp; Subjects</a>
        <a href="mark_attendance.php"  class="nav-link <?= ($pageTitle==='Mark Attendance')?'active':'' ?>">✅ Mark Attendance</a>
        <a href="view_attendance.php"  class="nav-link <?= ($pageTitle==='View Attendance')?'active':'' ?>">📊 View Attendance</a>
        <a href="logout.php"           class="nav-link nav-logout">🚪 Logout</a>
    </nav>
</aside>

<!-- ======== MAIN AREA ======== -->
<div class="main">
    <header class="topbar">
        <div class="topbar-title"><?= $pageTitle ?? 'Dashboard' ?></div>
        <div class="topbar-user">
            👤 <?= htmlspecialchars($_SESSION['admin_name']) ?>
            &nbsp;|&nbsp;
            <?= date('D, d M Y') ?>
        </div>
    </header>
    <div class="content">
