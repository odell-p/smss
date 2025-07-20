<?php
require_once '../includes/header.php';
require_once '../includes/db_connect.php';

// --- Page-Specific Logic ---
$success_message = '';
$error_message = '';
$edit_mode = false;
$programme_to_edit = ['id' => '', 'name' => '', 'code' => ''];

// --- Handle DELETE request ---
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    // You can add a check here later if programmes are linked to other tables
    $stmt_delete = $conn->prepare("DELETE FROM programmes WHERE id = ?");
    $stmt_delete->bind_param("i", $delete_id);
    if ($stmt_delete->execute()) {
        $success_message = "Programme deleted successfully!";
    } else {
        $error_message = "Error deleting programme.";
    }
    $stmt_delete->close();
}

// --- Handle POST request (for both CREATE and UPDATE) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $code = trim($_POST['code']);
    $id = intval($_POST['id']);

    if (empty($name) || empty($code)) {
        $error_message = "Programme name and code are required.";
    } else {
        // --- UPDATE ---
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE programmes SET name = ?, code = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $code, $id);
            if ($stmt->execute()) {
                $success_message = "Programme updated successfully!";
            } else {
                $error_message = "Error updating programme. The name or code might already exist.";
            }
        // --- CREATE ---
        } else {
            $stmt = $conn->prepare("INSERT INTO programmes (name, code) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $code);
            if ($stmt->execute()) {
                $success_message = "Programme created successfully!";
            } else {
                $error_message = "Error creating programme. It might already exist.";
            }
        }
        $stmt->close();
    }
}

// --- Handle GET request for EDIT mode ---
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT * FROM programmes WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_mode = true;
        $programme_to_edit = $result->fetch_assoc();
    }
    $stmt->close();
}

// --- Data Fetching: Get all programmes for the table ---
$programmes_result = $conn->query("SELECT * FROM programmes ORDER BY name ASC");

?>

<!-- Page Title -->
<div class="page-header">
    <h1>System Control</h1>
    <p>Manage Programmes</p>
</div>

<!-- Display Messages -->
<?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
<?php if ($error_message): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>

<!-- Form for Adding/Editing a Programme -->
<div class="content-panel">
    <h2><?php echo $edit_mode ? 'Edit Programme' : 'Create New Programme'; ?></h2>
    <form action="manage_programmes.php" method="POST">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($programme_to_edit['id']); ?>">
        
        <div class="form-row">
            <div class="form-group" style="flex: 2;">
                <label for="name">Programme Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($programme_to_edit['name']); ?>" placeholder="e.g., B.Sc. Computer Science" required>
            </div>
            <div class="form-group">
                <label for="code">Programme Code</label>
                <input type="text" id="code" name="code" value="<?php echo htmlspecialchars($programme_to_edit['code']); ?>" placeholder="e.g., CSC" required>
            </div>
        </div>
        <button type="submit" class="btn"><?php echo $edit_mode ? 'Update Programme' : 'Create Programme'; ?></button>
        <?php if ($edit_mode): ?>
            <a href="manage_programmes.php" class="btn btn-outline">Cancel Edit</a>
        <?php endif; ?>
    </form>
</div>

<!-- Table of Existing Programmes -->
<div class="content-panel">
    <h2>Existing Programmes</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Programme Name</th>
                <th>Code</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($programmes_result && $programmes_result->num_rows > 0): ?>
                <?php $i = 1; while($row = $programmes_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['code']); ?></td>
                        <td>
                            <a href="manage_programmes.php?edit_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                            <a href="manage_programmes.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this programme?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4">No programmes found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>