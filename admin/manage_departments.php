<?php
require_once '../includes/header.php';
require_once '../includes/db_connect.php';

// --- Page-Specific Logic ---
$success_message = '';
$error_message = '';
$edit_mode = false;
$department_to_edit = ['id' => '', 'name' => ''];

// --- Handle DELETE request ---
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Security Check: Make sure department is not in use by any user
    $stmt_check = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE department_id = ?");
    $stmt_check->bind_param("i", $delete_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result()->fetch_assoc();
    
    if ($result_check['count'] > 0) {
        $error_message = "Cannot delete this department. It is currently assigned to one or more users.";
    } else {
        $stmt_delete = $conn->prepare("DELETE FROM departments WHERE id = ?");
        $stmt_delete->bind_param("i", $delete_id);
        if ($stmt_delete->execute()) {
            $success_message = "Department deleted successfully!";
        } else {
            $error_message = "Error deleting department.";
        }
        $stmt_delete->close();
    }
    $stmt_check->close();
}

// --- Handle POST request (for both CREATE and UPDATE) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $id = intval($_POST['id']);

    if (empty($name)) {
        $error_message = "Department name cannot be empty.";
    } else {
        // --- UPDATE ---
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE departments SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $id);
            if ($stmt->execute()) {
                $success_message = "Department updated successfully!";
            } else {
                $error_message = "Error updating department. The name might already exist.";
            }
        // --- CREATE ---
        } else {
            $stmt = $conn->prepare("INSERT INTO departments (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            if ($stmt->execute()) {
                $success_message = "Department created successfully!";
            } else {
                $error_message = "Error creating department. It might already exist.";
            }
        }
        $stmt->close();
    }
}

// --- Handle GET request for EDIT mode ---
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT * FROM departments WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_mode = true;
        $department_to_edit = $result->fetch_assoc();
    }
    $stmt->close();
}

// --- Data Fetching: Get all departments for the table ---
$departments_result = $conn->query("SELECT * FROM departments ORDER BY name ASC");

?>

<!-- Page Title -->
<div class="page-header">
    <h1>System Control</h1>
    <p>Manage Departments</p>
</div>

<!-- Display Messages -->
<?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
<?php if ($error_message): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>

<!-- Form for Adding/Editing a Department -->
<div class="content-panel">
    <h2><?php echo $edit_mode ? 'Edit Department' : 'Create New Department'; ?></h2>
    <form action="manage_departments.php" method="POST">
        <!-- Hidden input to store the ID for editing -->
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($department_to_edit['id']); ?>">
        
        <div class="form-row">
            <div class="form-group">
                <label for="name">Department Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($department_to_edit['name']); ?>" placeholder="e.g., Computer Science" required>
            </div>
            <div class="form-group-action">
                <button type="submit" class="btn"><?php echo $edit_mode ? 'Update Department' : 'Create Department'; ?></button>
                <?php if ($edit_mode): ?>
                    <a href="manage_departments.php" class="btn btn-outline">Cancel Edit</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- Table of Existing Departments -->
<div class="content-panel">
    <h2>Existing Departments</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Department Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($departments_result && $departments_result->num_rows > 0): ?>
                <?php $i = 1; while($row = $departments_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td>
                            <a href="manage_departments.php?edit_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                            <a href="manage_departments.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this department? This cannot be undone.');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="3">No departments found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>