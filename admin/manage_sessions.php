<?php
require_once '../includes/header.php';
require_once '../includes/db_connect.php';

// ========================================================================
//  THIS IS THE CRUCIAL PHP LOGIC BLOCK THAT WAS MISSING
// ========================================================================
$success_message = '';
$error_message = '';

// --- Handle Form Submission from the Modal to Create a New Session ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_session'])) {
    $academic_year = trim($_POST['academic_year']);
    $semester = intval($_POST['semester']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    if (empty($academic_year) || empty($semester) || empty($start_date) || empty($end_date)) {
        $error_message = "All fields are required.";
    } elseif (!preg_match('/^\d{4}\/\d{4}$/', $academic_year)) {
        $error_message = "Academic Year must be in the format 'YYYY/YYYY'.";
    } else {
        $stmt_check = $conn->prepare("SELECT id FROM academic_sessions WHERE academic_year = ? AND semester = ?");
        $stmt_check->bind_param("si", $academic_year, $semester);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $error_message = "This academic session already exists.";
        } else {
            $name = $academic_year . " - Semester " . $semester;
            $sql = "INSERT INTO academic_sessions (academic_year, semester, name, start_date, end_date) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sisss", $academic_year, $semester, $name, $start_date, $end_date);
            if ($stmt->execute()) {
                $success_message = "Academic session '".htmlspecialchars($name)."' created successfully!";
            } else {
                $error_message = "Error creating session: " . $conn->error;
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}

// --- Handle Link Click to Set a Session as Current ---
if (isset($_GET['set_current'])) {
    $session_id = intval($_GET['set_current']);
    $conn->begin_transaction();
    try {
        $conn->query("UPDATE academic_sessions SET is_current = 0");
        $conn->query("UPDATE academic_sessions SET is_current = 1 WHERE id = $session_id");
        $conn->commit();
        $success_message = "Academic session has been set as current.";
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        $error_message = "Failed to set current session.";
    }
}

// --- Data Fetching: Get All Sessions for the DataTable ---
$sessions_result = $conn->query("SELECT * FROM academic_sessions ORDER BY academic_year DESC, semester DESC");
// ========================================================================
//  END OF PHP LOGIC BLOCK
// ========================================================================
?>

<!-- New Page Header -->
<div class="page-header-with-button">
    <div class="page-header-title">
        <h1>Manage Academic Sessions</h1>
        <p>The 'Active' session is the current academic year and semester for the entire system, used for new registrations and results processing.</p>
    </div>
    <button id="add-new-session-btn" class="btn btn-primary">+ Add New Session</button>
</div>

<!-- Display Success/Error Messages (These now work because the logic above sets them) -->
<?php if (!empty($success_message)): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
<?php if (!empty($error_message)): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>

<!-- Table of Existing Sessions -->
<div class="content-panel">
    <table id="sessionsTable" class="data-table display" style="width:100%">
        <thead>
            <tr>
                <th>Academic Year</th>
                <th>Semester</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($sessions_result && $sessions_result->num_rows > 0): ?>
                <?php while($row = $sessions_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['academic_year']); ?></td>
                        <td><?php echo htmlspecialchars($row['semester']); ?></td>
                        <td><?php echo date('d M, Y', strtotime($row['start_date'])); ?></td>
                        <td><?php echo ($row['end_date'] ? date('d M, Y', strtotime($row['end_date'])) : 'N/A'); ?></td>
                        <td>
                            <?php if ($row['is_current']): ?>
                                <span class="status-active">Active</span>
                            <?php else: ?>
                                <span class="status-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['is_current']): ?>
                                <button class="btn btn-sm disabled" disabled>Active</button>
                            <?php else: ?>
                                <a href="manage_sessions.php?set_current=<?php echo $row['id']; ?>" class="action-link" onclick="return confirm('Are you sure you want to set this as the current session?');">Set as Active</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>


<!-- Modal for Adding a New Session -->
<div id="addSessionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Create New Academic Session</h2>
            <span class="close-btn">×</span>
        </div>
        <div class="modal-body">
            <!-- This form correctly submits to manage_sessions.php -->
            <form action="manage_sessions.php" method="POST">
                <div class="form-group">
                    <label for="academic_year">Academic Year</label>
                    <input type="text" id="academic_year" name="academic_year" placeholder="e.g., 2024/2025" required pattern="\d{4}/\d{4}">
                </div>
                <div class="form-group">
                    <label for="semester">Semester</label>
                    <select id="semester" name="semester" required>
                        <option value="">-- Select Semester --</option>
                        <option value="1">First Semester</option>
                        <option value="2">Second Semester</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" required>
                </div>
                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" required>
                </div>
                <!-- This button is type="submit" and will trigger the form submission -->
                <button type="submit" name="create_session" class="btn btn-primary">Create Session</button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>