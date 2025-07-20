<?php
require_once '../includes/header.php';
require_once '../includes/db_connect.php';

// --- VALIDATE SESSION ID ---
if (!isset($_GET['session_id']) || !is_numeric($_GET['session_id'])) {
    die("Error: Invalid or missing Academic Session ID.");
}
$session_id = intval($_GET['session_id']);

// --- Get Session Details ---
$stmt_session = $conn->prepare("SELECT name FROM academic_sessions WHERE id = ?");
$stmt_session->bind_param("i", $session_id);
$stmt_session->execute();
$session_result = $stmt_session->get_result();
if ($session_result->num_rows == 0) {
    die("Error: Academic Session not found.");
}
$session = $session_result->fetch_assoc();
$session_name = $session['name'];


// --- Page Logic ---
$success_message = '';
$error_message = '';

// Handle form submission to ADD a course to this session
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_course'])) {
    $course_id = intval($_POST['course_id']);
    if (empty($course_id)) {
        $error_message = "Please select a course to add.";
    } else {
        $stmt_add = $conn->prepare("INSERT INTO session_courses (academic_session_id, course_id) VALUES (?, ?)");
        $stmt_add->bind_param("ii", $session_id, $course_id);
        if ($stmt_add->execute()) {
            $success_message = "Course added to session successfully!";
        } else {
            // Error 1062 is for duplicate entry
            if ($conn->errno == 1062) {
                $error_message = "This course is already in this session.";
            } else {
                $error_message = "Error adding course: " . $conn->error;
            }
        }
        $stmt_add->close();
    }
}

// Handle request to REMOVE a course from this session
if (isset($_GET['remove_id'])) {
    $remove_id = intval($_GET['remove_id']);
    $stmt_remove = $conn->prepare("DELETE FROM session_courses WHERE id = ? AND academic_session_id = ?");
    $stmt_remove->bind_param("ii", $remove_id, $session_id);
    if ($stmt_remove->execute()) {
        $success_message = "Course removed from session successfully!";
    } else {
        $error_message = "Error removing course.";
    }
    $stmt_remove->close();
}


// --- Data Fetching ---
// 1. Get courses already IN this session
$offered_courses_query = "
    SELECT sc.id, c.course_code, c.course_title, d.name as department_name, l.name as level_name
    FROM session_courses sc
    JOIN courses c ON sc.course_id = c.id
    LEFT JOIN departments d ON c.department_id = d.id
    LEFT JOIN levels l ON c.level_id = l.id
    WHERE sc.academic_session_id = ?
    ORDER BY c.course_code ASC
";
$stmt_offered = $conn->prepare($offered_courses_query);
$stmt_offered->bind_param("i", $session_id);
$stmt_offered->execute();
$offered_courses_result = $stmt_offered->get_result();

// 2. Get courses NOT YET in this session (for the dropdown)
$available_courses_query = "
    SELECT id, course_code, course_title FROM courses 
    WHERE id NOT IN (SELECT course_id FROM session_courses WHERE academic_session_id = ?)
    ORDER BY course_code ASC
";
$stmt_available = $conn->prepare($available_courses_query);
$stmt_available->bind_param("i", $session_id);
$stmt_available->execute();
$available_courses_result = $stmt_available->get_result();
?>

<!-- Page Title -->
<div class="page-header">
    <h1>Manage Courses for Session</h1>
    <p class="text-prominent"><?php echo htmlspecialchars($session_name); ?></p>
</div>

<!-- Display Messages -->
<?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
<?php if ($error_message): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>

<!-- Form to Add a Course to this Session -->
<div class="content-panel">
    <h2>Add Course to this Session</h2>
    <form action="manage_session_courses.php?session_id=<?php echo $session_id; ?>" method="POST" class="form-inline">
        <div class="form-group" style="flex: 3;">
            <label for="course_id">Select Course from Catalog</label>
            <select name="course_id" id="course_id" required>
                <option value="">-- Select a Course --</option>
                <?php while($course = $available_courses_result->fetch_assoc()): ?>
                    <option value="<?php echo $course['id']; ?>">
                        <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_title']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <button type="submit" name="add_course" class="btn">Add Course</button>
    </form>
</div>


<!-- Table of Courses Offered in this Session -->
<div class="content-panel">
    <h2>Courses Offered in this Session</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Title</th>
                <th>Department</th>
                <th>Level</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($offered_courses_result->num_rows > 0): ?>
                <?php while($row = $offered_courses_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['course_code']); ?></td>
                        <td><?php echo htmlspecialchars($row['course_title']); ?></td>
                        <td><?php echo htmlspecialchars($row['department_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['level_name']); ?></td>
                        <td>
                            <a href="manage_session_courses.php?session_id=<?php echo $session_id; ?>&remove_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to remove this course from the session?');">Remove</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">No courses have been added to this session yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>