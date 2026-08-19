<?php
// ============================================================
//  dashboard.php — Main Dashboard
//  Shows summary stats: total students, classes, subjects,
//  today's attendance count, and recent attendance.
// ============================================================

require_once 'auth.php';   // Check login
require_once 'db.php';     // Database connection

$pageTitle = 'Dashboard';

// --- Fetch summary statistics ---

// Total number of students
$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM students"))['cnt'];

// Total number of classes
$total_classes  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM classes"))['cnt'];

// Total number of subjects
$total_subjects = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM subjects"))['cnt'];

// Today's attendance count (how many records saved today)
$today = date('Y-m-d');
$today_att = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM attendance WHERE att_date = '$today'")
)['cnt'];

// Count of Present today
$today_present = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM attendance WHERE att_date = '$today' AND status='Present'")
)['cnt'];

// --- Fetch recent 10 attendance records for the table ---
$recent_sql = "
    SELECT
        s.full_name,
        s.roll_number,
        c.class_name,
        sub.subject_name,
        a.att_date,
        a.status
    FROM attendance a
    JOIN students s  ON a.student_id = s.id
    JOIN classes  c  ON a.class_id   = c.id
    JOIN subjects sub ON a.subject_id = sub.id
    ORDER BY a.marked_at DESC
    LIMIT 10
";
$recent_result = mysqli_query($conn, $recent_sql);

require_once 'partials/header.php';
?>

<!-- ======== STAT CARDS ======== -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:#e8f4fd;">👥</div>
        <div>
            <div class="stat-number"><?= $total_students ?></div>
            <div class="stat-label">Total Students</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#e8f7ee;">🏫</div>
        <div>
            <div class="stat-number"><?= $total_classes ?></div>
            <div class="stat-label">Total Classes</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef9e7;">📚</div>
        <div>
            <div class="stat-number"><?= $total_subjects ?></div>
            <div class="stat-label">Total Subjects</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#f0e8ff;">✅</div>
        <div>
            <div class="stat-number"><?= $today_present ?></div>
            <div class="stat-label">Present Today</div>
        </div>
    </div>
</div>

<!-- ======== QUICK ACTIONS ======== -->
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header">Quick Actions</div>
    <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <a href="mark_attendance.php" class="btn btn-primary">✅ Mark Attendance</a>
        <a href="students.php"        class="btn btn-secondary">👥 Manage Students</a>
        <a href="view_attendance.php" class="btn btn-secondary">📊 View Reports</a>
        <a href="classes.php"         class="btn btn-secondary">🏫 Manage Classes</a>
    </div>
</div>

<!-- ======== RECENT ATTENDANCE TABLE ======== -->
<div class="card">
    <div class="card-header">Recent Attendance Records</div>

    <?php if (mysqli_num_rows($recent_result) === 0): ?>
        <p class="empty-msg">No attendance records yet. <a href="mark_attendance.php">Mark attendance now →</a></p>
    <?php else: ?>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Roll No</th>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Subject</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = mysqli_fetch_assoc($recent_result)): ?>
                <tr>
                    <td><code><?= htmlspecialchars($row['roll_number']) ?></code></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['class_name']) ?></td>
                    <td><?= htmlspecialchars($row['subject_name']) ?></td>
                    <td><?= date('d M Y', strtotime($row['att_date'])) ?></td>
                    <td>
                        <?php
                        // Show badge based on status
                        $status = $row['status'];
                        $class  = ($status === 'Present') ? 'badge-present'
                                : (($status === 'Absent') ? 'badge-absent' : 'badge-leave');
                        ?>
                        <span class="badge <?= $class ?>"><?= $status ?></span>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'partials/footer.php'; ?>
