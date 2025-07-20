<?php
require_once '../includes/header.php';
require_once '../includes/db_connect.php';

// --- Page-Specific Logic ---
$success_message = '';
$error_message = '';
$edit_mode = false;
$course_to_edit = ['id' => '', 'course_title' => '', 'course_code' => '', 'course_unit' => '', 'department_id' => '', 'programme_id' => '', 'level_id' => ''];

// --- Handle DELETE request ---
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    // You can add more checks here if this course is in use elsewhere
    $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $success_message = "Course deleted successfully from the catalog!";
    } else {
        $error_message = "Error deleting course.";
    }
    $stmt->close();
}

// --- Handle POST request (for both CREATE and UPDATE) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and retrieve form data
    $id = intval($_POST['id']);
    $title = trim($_POST['course_title']);
    $code = trim($_POST['course_code']);
    $unit = intval($_POST['course_unit']);
    $department_id = intval($_POST['department_id']);
    $programme_id = intval($_POST['programme_id']); // New field
    $level_id = intval($_POST['level_id']);

    // Validation
    if (empty($title) || empty($code) || empty($unit) || empty($department_id) || empty($programme_id) || empty($level_id)) {
        $error_message = "All fields are required.";
    } else {
        // --- UPDATE ---
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE courses SET course_title=?, course_code=?, course_unit=?, department_id=?, programme_id=?, level_id=? WHERE id=?");
            $stmt->bind_param("ssiiiii", $title, $code, $unit, $department_id, $programme_id, $level_id, $id);
            if ($stmt->execute()) {
                $success_message = "Course updated successfully!";
            } else {
                $error_message = "Error updating course. The code might already exist.";
            }
        // --- CREATE ---
        } else {
            $stmt = $conn->prepare("INSERT INTO courses (course_title, course_code, course_unit, department_id, programme_id, level_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiiii", $title, $code, $unit, $department_id, $programme_id, $level_id);
            if ($stmt->execute()) {
                $success_message = "Course created successfully!";
            } else {
                $error_message = "Error creating course. The code might already exist.";
            }
        }
        $stmt->close();
    }
}

// --- Handle GET request for EDIT mode ---
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_mode = true;
        $course_to_edit = $result->fetch_assoc();
    }
    $stmt->close();
}

// --- Data Fetching for Form Dropdowns and Table ---
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name");
$programmes = $conn->query("SELECT id, name FROM programmes ORDER BY name"); // New query
$levels = $conn->query("SELECT id, name FROM levels ORDER BY name");

// Main query to fetch courses with their related names using JOINs
$courses_result = $conn->query("
    SELECT c.*, d.name AS department_name, p.name AS programme_name, l.name AS level_name 
    FROM courses c
    LEFT JOIN departments d ON c.department_id = d.id
    LEFT JOIN programmes p ON c.programme_id = p.id
    LEFT JOIN levels l ON c.level_id = l.id
    ORDER BY c.course_code ASC
");

?>

<!-- Page Title -->
<div class="page-header">
    <h1>System Control</h1>
    <p>Manage Course Catalog</p>
</div>

<!-- Display Messages -->
<?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
<?php if ($error_message): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>

<!-- Form for Adding/Editing a Course -->
<div class="content-panel">
    <h2><?php echo $edit_mode ? 'Edit Course in Catalog' : 'Create New Course in Catalog'; ?></h2>
    <form action="manage_courses.php" method="POST">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($course_to_edit['id']); ?>">
        
        <div class="form-row">
            <div class="form-group" style="flex: 3;">
                <label for="course_title">Course Title</label>
                <input type="text" id="course_title" name="course_title" value="<?php echo htmlspecialchars($course_to_edit['course_title']); ?>" required>
            </div>
            <div class="form-group" style="flex: 1;">
                <label for="course_code">Course Code</label>
                <input type="text" id="course_code" name="course_code" value="<?php echo htmlspecialchars($course_to_edit['course_code']); ?>" required>
            </div>
            <div class="form-group" style="flex: 1;">
                <label for="course_unit">Course Unit</label>
                <input type="number" id="course_unit" name="course_unit" value="<?php echo htmlspecialchars($course_to_edit['course_unit']); ?>" min="1" max="10" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="department_id">Department</label>
                <select id="department_id" name="department_id" required>
                    <option value="">-- Select Department --</option>
                    <?php while($dept = $departments->fetch_assoc()): ?>
                        <option value="<?php echo $dept['id']; ?>" <?php if($dept['id'] == $course_to_edit['department_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($dept['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <!-- New Programme Dropdown -->
            <div class="form-group">
                <label for="programme_id">Programme</label>
                <select id="programme_id" name="programme_id" required>
                    <option value="">-- Select Programme --</option>
                    <?php while($prog = $programmes->fetch_assoc()): ?>
                        <option value="<?php echo $prog['id']; ?>" <?php if($prog['id'] == $course_to_edit['programme_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($prog['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="level_id">Level</label>
                <select id="level_id" name="level_id" required>
                    <option value="">-- Select Level --</option>
                    <?php while($level = $levels->fetch_assoc()): ?>
                        <option value="<?php echo $level['id']; ?>" <?php if($level['id'] == $course_to_edit['level_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($level['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <button type="submit" class="btn"><?php echo $edit_mode ? 'Update Course' : 'Create Course'; ?></button>
        <?php if ($edit_mode): ?>
            <a href="manage_courses.php" class="btn btn-outline">Cancel Edit</a>
        <?php endif; ?>
    </form>
</div>

<!-- Table of Existing Courses -->
<div class="content-panel">
    <h2>Existing Courses in Catalog</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Title</th>
                <th>Programme</th> <!-- New Column -->
                <th>Level</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($courses_result && $courses_result->num_rows > 0): ?>
                <?php while($row = $courses_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['course_code']); ?></td>
                        <td><?php echo htmlspecialchars($row['course_title']); ?></td>
                        <td><?php echo htmlspecialchars($row['programme_name'] ?? 'N/A'); ?></td> <!-- New Data -->
                        <td><?php echo htmlspecialchars($row['level_name'] ?? 'N/A'); ?></td>
                        <td>
                            <a href="manage_courses.php?edit_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                            <a href="manage_courses.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this course?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">No courses found. Create one above.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>