<?php
require_once '../includes/header.php';
require_once '../includes/db_connect.php';

// --- Page-Specific Logic ---

// Initialize messages for user feedback
$success_message = '';
$error_message = '';

// --- Handle Form Submission to Create a New Academic Year ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_year'])) {
    $year_name = trim($_POST['year_name']);

    // --- Validation ---
    if (empty($year_name)) {
        $error_message = "Academic year name cannot be empty.";
    } elseif (!preg_match('/^\d{4}\/\d{4}$/', $year_name)) {
        $error_message = "Format must be 'YYYY/YYYY' (e.g., 2024/2025).";
    } else {
        // Check if the year already exists to prevent duplicates
        $stmt_check = $conn->prepare("SELECT id FROM academic_years WHERE year_name = ?");
        $stmt_check->bind_param("s", $year_name);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $error_message = "The academic year '".htmlspecialchars($year_name)."' already exists.";
        } else {
            // No duplicate found, proceed to insert
            $stmt_insert = $conn->prepare("INSERT INTO academic_years (year_name) VALUES (?)");
            $stmt_insert->bind_param("s", $year_name);

            if ($stmt_insert->execute()) {
                $success_message = "Academic year '".htmlspecialchars($year_name)."' created successfully!";
            } else {
                $error_message = "Error creating academic year: " . $conn->error;
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}

// --- Data Fetching: Get All Academic Years to Display in the Table ---
$years_result = $conn->query("SELECT * FROM academic_years ORDER BY year_name DESC");

?>

<!-- Page Title -->
<div class="page-header">
    <h1>System Control</h1>
    <p>Manage Academic Years</p>
</div>

<!-- Display Success/Error Messages -->
<?php if ($success_message): ?>
    <div class="alert alert-success"><?php echo $success_message; ?></div>
<?php endif; ?>
<?php if ($error_message): ?>
    <div class="alert alert-danger"><?php echo $error_message; ?></div>
<?php endif; ?>

<!-- Form to Create a New Academic Year -->
<div class="content-panel">
    <h2>Create New Academic Year</h2>
    <form action="manage_years.php" method="POST" class="form-inline">
        <div class="form-group">
            <label for="year_name">Academic Year</label>
            <input type="text" id="year_name" name="year_name" placeholder="e.g., 2024/2025" required pattern="\d{4}/\d{4}">
        </div>
        <button type="submit" name="create_year" class="btn">Create Year</button>
    </form>
</div>

<!-- Table to List Existing Academic Years -->
<div class="content-panel">
    <h2>Existing Academic Years</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Academic Year</th>
                <th>Date Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($years_result && $years_result->num_rows > 0): ?>
                <?php $i = 1; while($row = $years_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($row['year_name']); ?></td>
                        <td><?php echo date('M j, Y, g:i a', strtotime($row['created_at'])); ?></td>
                        <td>
                            <!-- Actions like Edit/Delete can be added here later -->
                            <a href="#" class="btn btn-sm btn-outline disabled">Edit</a>
                            <a href="#" class="btn btn-sm btn-danger disabled">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">No academic years found. Please create one above.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>