<?php
// ============================================================
//  students.php — Add, Edit, Delete Students
// ============================================================

require_once 'auth.php';
require_once 'db.php';

$pageTitle = 'Students';
$message   = '';  // Success/error messages
$edit_student = null; // Will hold student data when editing

// ---- Handle DELETE ----
if (isset($_GET['delete'])) {
    $del_id = (int) $_GET['delete']; // Cast to integer to prevent injection
    mysqli_query($conn, "DELETE FROM students WHERE id = $del_id");
    $message = ['type' => 'success', 'text' => 'Student deleted successfully.'];
}

// ---- Handle EDIT (load data into form) ----
if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $res = mysqli_query($conn, "SELECT * FROM students WHERE id = $edit_id");
    $edit_student = mysqli_fetch_assoc($res);
}

// ---- Handle ADD / UPDATE FORM SUBMISSION ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitize inputs
    $roll   = mysqli_real_escape_string($conn, trim($_POST['roll_number']));
    $name   = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $email  = mysqli_real_escape_string($conn, trim($_POST['email']));
    $cls_id = (int) $_POST['class_id'];
    $stu_id = (int) ($_POST['student_id'] ?? 0);

    // Validation
    if (empty($roll) || empty($name) || $cls_id === 0) {
        $message = ['type' => 'danger', 'text' => 'Roll number, name, and class are required.'];
    } else {
        if ($stu_id > 0) {
            // --- UPDATE existing student ---
            $sql = "UPDATE students SET
                        roll_number = '$roll',
                        full_name   = '$name',
                        email       = '$email',
                        class_id    = $cls_id
                    WHERE id = $stu_id";
            mysqli_query($conn, $sql);
            $message = ['type' => 'success', 'text' => 'Student updated successfully!'];
        } else {
            // --- INSERT new student ---
            // Check if roll number already exists
            $check = mysqli_fetch_assoc(
                mysqli_query($conn, "SELECT id FROM students WHERE roll_number = '$roll'")
            );
            if ($check) {
                $message = ['type' => 'danger', 'text' => "Roll number '$roll' already exists!"];
            } else {
                $sql = "INSERT INTO students (roll_number, full_name, email, class_id)
                        VALUES ('$roll', '$name', '$email', $cls_id)";
                mysqli_query($conn, $sql);
                $message = ['type' => 'success', 'text' => 'Student added successfully!'];
            }
        }
        $edit_student = null; // Reset form
    }
}

// ---- Fetch all classes for dropdown ----
$classes_result = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");

// ---- Fetch all students with their class name ----
$students_result = mysqli_query($conn, "
    SELECT s.*, c.class_name
    FROM students s
    JOIN classes c ON s.class_id = c.id
    ORDER BY c.class_name, s.roll_number
");

require_once 'partials/header.php';
?>

<!-- Flash Message -->
<?php if ($message): ?>
    <div class="alert alert-<?= $message['type'] ?>"><?= htmlspecialchars($message['text']) ?></div>
<?php endif; ?>

<!-- ======== ADD / EDIT FORM ======== -->
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header"><?= $edit_student ? '✏️ Edit Student' : '➕ Add New Student' ?></div>

    <form method="POST" action="">
        <!-- Hidden field to know if we're editing -->
        <?php if ($edit_student): ?>
            <input type="hidden" name="student_id" value="<?= $edit_student['id'] ?>"/>
        <?php endif; ?>

        <div class="form-row">
            <div class="form-group">
                <label>Roll Number *</label>
                <input type="text" name="roll_number"
                    value="<?= htmlspecialchars($edit_student['roll_number'] ?? '') ?>"
                    placeholder="e.g. BCA101" required/>
            </div>
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name"
                    value="<?= htmlspecialchars($edit_student['full_name'] ?? '') ?>"
                    placeholder="e.g. Aryan Sharma" required/>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email"
                    value="<?= htmlspecialchars($edit_student['email'] ?? '') ?>"
                    placeholder="e.g. aryan@college.edu"/>
            </div>
            <div class="form-group">
                <label>Class *</label>
                <select name="class_id" required>
                    <option value="">-- Select Class --</option>
                    <?php while ($c = mysqli_fetch_assoc($classes_result)): ?>
                        <option value="<?= $c['id'] ?>"
                            <?= (($edit_student['class_id'] ?? '') == $c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['class_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <div style="display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary">
                <?= $edit_student ? '💾 Update Student' : '➕ Add Student' ?>
            </button>
            <?php if ($edit_student): ?>
                <a href="students.php" class="btn btn-secondary">✕ Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- ======== STUDENTS TABLE ======== -->
<div class="card">
    <div class="card-header">All Students</div>

    <?php if (mysqli_num_rows($students_result) === 0): ?>
        <p class="empty-msg">No students added yet. Use the form above to add one.</p>
    <?php else: ?>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Roll No</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Class</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; while ($row = mysqli_fetch_assoc($students_result)): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><code><?= htmlspecialchars($row['roll_number']) ?></code></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['email'] ?: '—') ?></td>
                    <td><span class="badge badge-class"><?= htmlspecialchars($row['class_name']) ?></span></td>
                    <td>
                        <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-warning">✏️ Edit</a>
                        <!-- Confirm before deleting -->
                        <a href="?delete=<?= $row['id'] ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Are you sure you want to delete <?= addslashes($row['full_name']) ?>? This will also delete their attendance records.')">
                           🗑 Delete
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'partials/footer.php'; ?>
