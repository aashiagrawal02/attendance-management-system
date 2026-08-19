<?php
// ============================================================
//  mark_attendance.php — Mark Attendance for a Class+Subject
// ============================================================

require_once 'auth.php';
require_once 'db.php';

$pageTitle = 'Mark Attendance';
$message   = '';

// ---- Handle SAVE attendance submission ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {

    $class_id   = (int) $_POST['class_id'];
    $subject_id = (int) $_POST['subject_id'];
    $att_date   = mysqli_real_escape_string($conn, $_POST['att_date']);
    $statuses   = $_POST['status'] ?? []; // array of student_id => status

    if ($class_id && $subject_id && $att_date && !empty($statuses)) {
        foreach ($statuses as $student_id => $status) {
            $student_id = (int) $student_id;
            $status     = mysqli_real_escape_string($conn, $status);

            // INSERT or UPDATE if attendance already saved for that date
            // ON DUPLICATE KEY UPDATE handles re-saving the same day
            $sql = "INSERT INTO attendance (student_id, subject_id, class_id, att_date, status)
                    VALUES ($student_id, $subject_id, $class_id, '$att_date', '$status')
                    ON DUPLICATE KEY UPDATE status = '$status'";
            mysqli_query($conn, $sql);
        }
        $message = ['type' => 'success', 'text' => 'Attendance saved successfully for ' . date('d M Y', strtotime($att_date)) . '!'];
    } else {
        $message = ['type' => 'danger', 'text' => 'Please fill all fields and select students.'];
    }
}

// Fetch all classes for dropdown
$classes = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");

// When a class is selected, load subjects via AJAX (or page reload)
$selected_class   = (int) ($_GET['class_id']   ?? $_POST['class_id']   ?? 0);
$selected_subject = (int) ($_GET['subject_id'] ?? $_POST['subject_id'] ?? 0);
$selected_date    = $_GET['att_date'] ?? $_POST['att_date'] ?? date('Y-m-d');

// Fetch subjects for selected class
$subjects = [];
if ($selected_class) {
    $sub_res = mysqli_query($conn, "SELECT * FROM subjects WHERE class_id = $selected_class ORDER BY subject_name");
    while ($s = mysqli_fetch_assoc($sub_res)) {
        $subjects[] = $s;
    }
}

// Fetch students + their existing attendance for the selected date
$students = [];
if ($selected_class && $selected_subject && $selected_date) {
    $stu_res = mysqli_query($conn, "
        SELECT s.id, s.roll_number, s.full_name,
               a.status AS existing_status
        FROM students s
        LEFT JOIN attendance a
            ON  a.student_id  = s.id
            AND a.subject_id  = $selected_subject
            AND a.att_date    = '$selected_date'
        WHERE s.class_id = $selected_class
        ORDER BY s.roll_number
    ");
    while ($s = mysqli_fetch_assoc($stu_res)) {
        $students[] = $s;
    }
}

require_once 'partials/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $message['type'] ?>"><?= htmlspecialchars($message['text']) ?></div>
<?php endif; ?>

<!-- ======== STEP 1: Select Class / Subject / Date ======== -->
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header">Step 1 — Select Class, Subject & Date</div>

    <!-- When class changes, reload page to load subjects -->
    <form method="GET" action="" id="filterForm">
        <div class="form-row">
            <div class="form-group">
                <label>Class *</label>
                <select name="class_id" onchange="this.form.submit()" required>
                    <option value="">-- Select Class --</option>
                    <?php while ($c = mysqli_fetch_assoc($classes)): ?>
                        <option value="<?= $c['id'] ?>" <?= ($selected_class == $c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['class_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <?php if ($selected_class && $subjects): ?>
            <div class="form-group">
                <label>Subject *</label>
                <select name="subject_id" required>
                    <option value="">-- Select Subject --</option>
                    <?php foreach ($subjects as $sub): ?>
                        <option value="<?= $sub['id'] ?>" <?= ($selected_subject == $sub['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sub['subject_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label>Date *</label>
                <input type="date" name="att_date"
                       value="<?= htmlspecialchars($selected_date) ?>"
                       max="<?= date('Y-m-d') ?>" required/>
            </div>
        </div>
        <?php if ($selected_class): ?>
        <button type="submit" class="btn btn-secondary">🔍 Load Students</button>
        <?php endif; ?>
    </form>
</div>

<!-- ======== STEP 2: Mark Attendance ======== -->
<?php if ($selected_class && $selected_subject && $students): ?>
<div class="card">
    <div class="card-header">
        Step 2 — Mark Attendance
        <span style="font-size:13px;font-weight:400;color:#888;">
            (<?= count($students) ?> students)
        </span>
    </div>

    <!-- Quick select buttons -->
    <div style="display:flex;gap:10px;margin-bottom:1rem;">
        <button type="button" class="btn btn-sm btn-success" onclick="markAll('Present')">✓ All Present</button>
        <button type="button" class="btn btn-sm btn-danger"  onclick="markAll('Absent')">✗ All Absent</button>
    </div>

    <form method="POST" action="">
        <!-- Pass selected values as hidden fields -->
        <input type="hidden" name="class_id"   value="<?= $selected_class ?>"/>
        <input type="hidden" name="subject_id" value="<?= $selected_subject ?>"/>
        <input type="hidden" name="att_date"   value="<?= htmlspecialchars($selected_date) ?>"/>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Roll No</th>
                        <th>Student Name</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($students as $i => $stu):
                    // Use existing status if found, default to 'Present'
                    $cur_status = $stu['existing_status'] ?? 'Present';
                ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><code><?= htmlspecialchars($stu['roll_number']) ?></code></td>
                        <td><?= htmlspecialchars($stu['full_name']) ?></td>
                        <td>
                            <div class="status-selector">
                                <!-- Each radio group is named status[student_id] -->
                                <label class="status-radio <?= $cur_status === 'Present' ? 'active-present' : '' ?>">
                                    <input type="radio"
                                           name="status[<?= $stu['id'] ?>]"
                                           value="Present"
                                           <?= $cur_status === 'Present' ? 'checked' : '' ?>
                                           onchange="updateLabel(this)"/>
                                    Present
                                </label>
                                <label class="status-radio <?= $cur_status === 'Absent' ? 'active-absent' : '' ?>">
                                    <input type="radio"
                                           name="status[<?= $stu['id'] ?>]"
                                           value="Absent"
                                           <?= $cur_status === 'Absent' ? 'checked' : '' ?>
                                           onchange="updateLabel(this)"/>
                                    Absent
                                </label>
                                <label class="status-radio <?= $cur_status === 'Leave' ? 'active-leave' : '' ?>">
                                    <input type="radio"
                                           name="status[<?= $stu['id'] ?>]"
                                           value="Leave"
                                           <?= $cur_status === 'Leave' ? 'checked' : '' ?>
                                           onchange="updateLabel(this)"/>
                                    Leave
                                </label>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top:1.25rem;">
            <button type="submit" name="save_attendance" class="btn btn-primary">
                💾 Save Attendance for <?= date('d M Y', strtotime($selected_date)) ?>
            </button>
        </div>
    </form>
</div>

<?php elseif ($selected_class && empty($students)): ?>
    <div class="alert alert-danger">No students found in this class. <a href="students.php">Add students →</a></div>
<?php endif; ?>

<script>
// Mark all students with one status
function markAll(status) {
    var classMap = { 'Present': 'active-present', 'Absent': 'active-absent', 'Leave': 'active-leave' };
    document.querySelectorAll('input[type="radio"][value="' + status + '"]').forEach(function(radio) {
        radio.checked = true;
        // Update visual styling
        var group = radio.closest('td').querySelectorAll('.status-radio');
        group.forEach(function(label) {
            label.classList.remove('active-present', 'active-absent', 'active-leave');
        });
        radio.parentElement.classList.add(classMap[status]);
    });
}

// Update label styling when individual radio changes
function updateLabel(radio) {
    var group = radio.closest('td').querySelectorAll('.status-radio');
    var map   = { 'Present': 'active-present', 'Absent': 'active-absent', 'Leave': 'active-leave' };
    group.forEach(function(label) {
        label.classList.remove('active-present', 'active-absent', 'active-leave');
    });
    radio.parentElement.classList.add(map[radio.value]);
}
</script>

<?php require_once 'partials/footer.php'; ?>
