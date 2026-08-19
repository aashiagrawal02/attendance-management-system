<?php
// ============================================================
//  view_attendance.php — View & Filter Attendance Records
//  Also shows attendance percentage per student
// ============================================================

require_once 'auth.php';
require_once 'db.php';

$pageTitle = 'View Attendance';

// Get filter values from GET request
$filter_class   = (int)    ($_GET['class_id']   ?? 0);
$filter_subject = (int)    ($_GET['subject_id'] ?? 0);
$filter_from    = isset($_GET['date_from']) && $_GET['date_from'] ? $_GET['date_from'] : date('Y-m-01'); // first of month
$filter_to      = isset($_GET['date_to'])   && $_GET['date_to']   ? $_GET['date_to']   : date('Y-m-d');

// Fetch all classes for dropdown
$classes_res = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");

// Fetch subjects for selected class
$subjects = [];
if ($filter_class) {
    $sub_res = mysqli_query($conn, "SELECT * FROM subjects WHERE class_id = $filter_class ORDER BY subject_name");
    while ($s = mysqli_fetch_assoc($sub_res)) $subjects[] = $s;
}

// ---- Build the attendance summary query ----
// For each student, count total days, present days, and calculate percentage
$records = [];
if ($filter_class && $filter_subject) {
    $sql = "
        SELECT
            s.id           AS student_id,
            s.roll_number,
            s.full_name,
            -- Count only days where attendance was recorded
            COUNT(a.id)    AS total_days,
            -- Count days marked Present
            SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) AS present_days,
            -- Count days marked Absent
            SUM(CASE WHEN a.status = 'Absent'  THEN 1 ELSE 0 END) AS absent_days,
            -- Count days marked Leave
            SUM(CASE WHEN a.status = 'Leave'   THEN 1 ELSE 0 END) AS leave_days,
            -- Calculate percentage: (present / total) * 100
            ROUND(
                (SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) / COUNT(a.id)) * 100
            , 2) AS percentage
        FROM students s
        LEFT JOIN attendance a
            ON  a.student_id  = s.id
            AND a.subject_id  = $filter_subject
            AND a.att_date    BETWEEN '$filter_from' AND '$filter_to'
        WHERE s.class_id = $filter_class
        GROUP BY s.id, s.roll_number, s.full_name
        ORDER BY s.roll_number
    ";
    $res = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $records[] = $row;
    }
}

// ---- Detailed day-by-day records for a specific student ----
$detail_records = [];
$detail_student = null;
if (isset($_GET['student_id']) && $filter_class && $filter_subject) {
    $stu_id = (int) $_GET['student_id'];
    $detail_res = mysqli_query($conn, "
        SELECT a.att_date, a.status, sub.subject_name
        FROM attendance a
        JOIN subjects sub ON a.subject_id = sub.id
        WHERE a.student_id = $stu_id
          AND a.subject_id = $filter_subject
          AND a.att_date BETWEEN '$filter_from' AND '$filter_to'
        ORDER BY a.att_date DESC
    ");
    while ($r = mysqli_fetch_assoc($detail_res)) {
        $detail_records[] = $r;
    }
    $stu_res = mysqli_query($conn, "SELECT full_name, roll_number FROM students WHERE id = $stu_id");
    $detail_student = mysqli_fetch_assoc($stu_res);
}

require_once 'partials/header.php';
?>

<!-- ======== FILTER FORM ======== -->
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header">🔍 Filter Attendance</div>
    <form method="GET" action="">
        <div class="form-row">
            <div class="form-group">
                <label>Class *</label>
                <select name="class_id" onchange="this.form.submit()">
                    <option value="">-- Select Class --</option>
                    <?php while ($c = mysqli_fetch_assoc($classes_res)): ?>
                        <option value="<?= $c['id'] ?>" <?= ($filter_class == $c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['class_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <?php if ($filter_class && $subjects): ?>
            <div class="form-group">
                <label>Subject *</label>
                <select name="subject_id">
                    <option value="">-- Select Subject --</option>
                    <?php foreach ($subjects as $sub): ?>
                        <option value="<?= $sub['id'] ?>" <?= ($filter_subject == $sub['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sub['subject_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label>From Date</label>
                <input type="date" name="date_from" value="<?= htmlspecialchars($filter_from) ?>"/>
            </div>
            <div class="form-group">
                <label>To Date</label>
                <input type="date" name="date_to" value="<?= htmlspecialchars($filter_to) ?>" max="<?= date('Y-m-d') ?>"/>
            </div>
        </div>
        <?php if ($filter_class): ?>
        <button type="submit" class="btn btn-primary">📊 Generate Report</button>
        <?php endif; ?>
    </form>
</div>

<!-- ======== ATTENDANCE SUMMARY TABLE ======== -->
<?php if ($records): ?>
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header">
        Attendance Summary
        <span style="font-size:13px;font-weight:400;color:#888;">
            (<?= date('d M Y', strtotime($filter_from)) ?> — <?= date('d M Y', strtotime($filter_to)) ?>)
        </span>
    </div>

    <!-- Legend for color coding -->
    <div style="display:flex;gap:16px;margin-bottom:1rem;font-size:13px;">
        <span>Color code: </span>
        <span style="color:#2f9e44;font-weight:600;">🟢 ≥75% Good</span>
        <span style="color:#e67700;font-weight:600;">🟡 50–74% Warning</span>
        <span style="color:#e03131;font-weight:600;">🔴 &lt;50% Danger</span>
    </div>

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Roll No</th>
                    <th>Student Name</th>
                    <th>Total Days</th>
                    <th>Present</th>
                    <th>Absent</th>
                    <th>Leave</th>
                    <th>Attendance %</th>
                    <th>Detail</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($records as $i => $r):
                $pct = $r['total_days'] > 0 ? (float) $r['percentage'] : 0;
                // Color code based on percentage
                if ($pct >= 75)      $pct_class = 'pct-high';
                elseif ($pct >= 50)  $pct_class = 'pct-medium';
                else                 $pct_class = 'pct-low';
            ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><code><?= htmlspecialchars($r['roll_number']) ?></code></td>
                    <td><?= htmlspecialchars($r['full_name']) ?></td>
                    <td><?= $r['total_days'] ?></td>
                    <td style="color:#2f9e44;font-weight:600;"><?= $r['present_days'] ?></td>
                    <td style="color:#e03131;font-weight:600;"><?= $r['absent_days'] ?></td>
                    <td style="color:#e67700;font-weight:600;"><?= $r['leave_days'] ?></td>
                    <td>
                        <?php if ($r['total_days'] > 0): ?>
                        <!-- Progress bar + percentage -->
                        <div class="pct-bar-wrap">
                            <div class="pct-bar" style="width:<?= min($pct, 100) ?>%;background:<?= $pct>=75 ? '#2f9e44' : ($pct>=50 ? '#e67700' : '#e03131') ?>;"></div>
                        </div>
                        <span class="<?= $pct_class ?>"><?= $pct ?>%</span>
                        <?php else: ?>
                            <span style="color:#aaa;">No records</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <!-- Link to see day-by-day detail for this student -->
                        <a href="?class_id=<?= $filter_class ?>&subject_id=<?= $filter_subject ?>&date_from=<?= $filter_from ?>&date_to=<?= $filter_to ?>&student_id=<?= $r['student_id'] ?>"
                           class="btn btn-sm btn-secondary">📋 Detail</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php elseif ($filter_class && $filter_subject): ?>
    <div class="alert alert-danger">No records found for this filter. Try different dates.</div>
<?php endif; ?>

<!-- ======== STUDENT DETAIL RECORDS ======== -->
<?php if ($detail_student && $detail_records): ?>
<div class="card">
    <div class="card-header">
        📋 Day-by-Day Detail —
        <?= htmlspecialchars($detail_student['full_name']) ?>
        (<?= htmlspecialchars($detail_student['roll_number']) ?>)
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr><th>Date</th><th>Subject</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php foreach ($detail_records as $r): ?>
                <tr>
                    <td><?= date('d M Y (D)', strtotime($r['att_date'])) ?></td>
                    <td><?= htmlspecialchars($r['subject_name']) ?></td>
                    <td>
                        <?php
                        $cls = ($r['status'] === 'Present') ? 'badge-present'
                             : (($r['status'] === 'Absent') ? 'badge-absent' : 'badge-leave');
                        ?>
                        <span class="badge <?= $cls ?>"><?= $r['status'] ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php elseif (isset($_GET['student_id'])): ?>
    <div class="alert alert-danger">No day-by-day records found for this student in the selected range.</div>
<?php endif; ?>

<?php require_once 'partials/footer.php'; ?>
