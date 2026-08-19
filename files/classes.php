<?php
// ============================================================
//  classes.php — Manage Classes and Subjects
// ============================================================

require_once 'auth.php';
require_once 'db.php';

$pageTitle = 'Classes & Subjects';
$message   = '';

// ---- Handle DELETE CLASS ----
if (isset($_GET['del_class'])) {
    $id = (int) $_GET['del_class'];
    mysqli_query($conn, "DELETE FROM classes WHERE id = $id");
    $message = ['type' => 'success', 'text' => 'Class deleted (and all its subjects/students).'];
}

// ---- Handle DELETE SUBJECT ----
if (isset($_GET['del_sub'])) {
    $id = (int) $_GET['del_sub'];
    mysqli_query($conn, "DELETE FROM subjects WHERE id = $id");
    $message = ['type' => 'success', 'text' => 'Subject deleted.'];
}

// ---- Handle ADD CLASS ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_class'])) {
    $class_name = mysqli_real_escape_string($conn, trim($_POST['class_name']));
    if (empty($class_name)) {
        $message = ['type' => 'danger', 'text' => 'Class name cannot be empty.'];
    } else {
        mysqli_query($conn, "INSERT INTO classes (class_name) VALUES ('$class_name')");
        $message = ['type' => 'success', 'text' => "Class '$class_name' added!"];
    }
}

// ---- Handle ADD SUBJECT ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $sub_name = mysqli_real_escape_string($conn, trim($_POST['subject_name']));
    $class_id = (int) $_POST['class_id'];
    if (empty($sub_name) || $class_id === 0) {
        $message = ['type' => 'danger', 'text' => 'Subject name and class are required.'];
    } else {
        mysqli_query($conn, "INSERT INTO subjects (class_id, subject_name) VALUES ($class_id, '$sub_name')");
        $message = ['type' => 'success', 'text' => "Subject '$sub_name' added!"];
    }
}

// Fetch all classes
$classes = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");

// Fetch all subjects with their class name
$subjects = mysqli_query($conn, "
    SELECT sub.*, c.class_name
    FROM subjects sub
    JOIN classes c ON sub.class_id = c.id
    ORDER BY c.class_name, sub.subject_name
");

require_once 'partials/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $message['type'] ?>"><?= htmlspecialchars($message['text']) ?></div>
<?php endif; ?>

<!-- Two-column layout for Add Class + Add Subject -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">

    <!-- ADD CLASS FORM -->
    <div class="card">
        <div class="card-header">➕ Add New Class</div>
        <form method="POST">
            <div class="form-group">
                <label>Class Name *</label>
                <input type="text" name="class_name" placeholder="e.g. BCA 1st Year" required/>
            </div>
            <button type="submit" name="add_class" class="btn btn-primary">Add Class</button>
        </form>
    </div>

    <!-- ADD SUBJECT FORM -->
    <div class="card">
        <div class="card-header">➕ Add New Subject</div>
        <form method="POST">
            <div class="form-group">
                <label>Subject Name *</label>
                <input type="text" name="subject_name" placeholder="e.g. Mathematics" required/>
            </div>
            <div class="form-group">
                <label>Select Class *</label>
                <select name="class_id" required>
                    <option value="">-- Choose Class --</option>
                    <?php
                    // Reset pointer to top of classes result
                    mysqli_data_seek($classes, 0);
                    while ($c = mysqli_fetch_assoc($classes)):
                    ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['class_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" name="add_subject" class="btn btn-primary">Add Subject</button>
        </form>
    </div>
</div>

<!-- Two-column layout for Class List + Subject List -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">

    <!-- CLASS LIST -->
    <div class="card">
        <div class="card-header">All Classes</div>
        <?php mysqli_data_seek($classes, 0); ?>
        <?php if (mysqli_num_rows($classes) === 0): ?>
            <p class="empty-msg">No classes yet.</p>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>#</th><th>Class Name</th><th>Action</th></tr></thead>
            <tbody>
            <?php $i = 1; mysqli_data_seek($classes, 0);
            while ($c = mysqli_fetch_assoc($classes)): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($c['class_name']) ?></td>
                    <td>
                        <a href="?del_class=<?= $c['id'] ?>" class="btn btn-sm btn-danger"
                           onclick="return confirm('Delete this class and all its students/subjects?')">
                           🗑 Delete
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- SUBJECT LIST -->
    <div class="card">
        <div class="card-header">All Subjects</div>
        <?php if (mysqli_num_rows($subjects) === 0): ?>
            <p class="empty-msg">No subjects yet.</p>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>#</th><th>Subject</th><th>Class</th><th>Action</th></tr></thead>
            <tbody>
            <?php $i = 1; while ($s = mysqli_fetch_assoc($subjects)): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($s['subject_name']) ?></td>
                    <td><span class="badge badge-class"><?= htmlspecialchars($s['class_name']) ?></span></td>
                    <td>
                        <a href="?del_sub=<?= $s['id'] ?>" class="btn btn-sm btn-danger"
                           onclick="return confirm('Delete this subject?')">
                           🗑 Delete
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'partials/footer.php'; ?>
