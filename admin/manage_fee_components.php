<?php
require_once '../includes/header.php';
// Add access control
if (!in_array($_SESSION['user_role'], ['Admin', 'Finance'])) {
    header("Location: dashboard.php"); exit();
}

$success_message = ''; $error_message = '';
$edit_mode = false; $component_to_edit = ['id' => '', 'name' => ''];

// Handle Delete
if(isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    // Add check to prevent deleting if component is in use
    $stmt_check = $conn->prepare("SELECT COUNT(*) as count FROM session_fees WHERE fee_component_id = ?");
    $stmt_check->bind_param("i", $delete_id);
    $stmt_check->execute();
    if($stmt_check->get_result()->fetch_assoc()['count'] > 0){
        $error_message = "Cannot delete: This component is being used in a fee structure.";
    } else {
        $stmt_delete = $conn->prepare("DELETE FROM fee_components WHERE id = ?");
        $stmt_delete->bind_param("i", $delete_id);
        if($stmt_delete->execute()){ $success_message = "Component deleted."; } else { $error_message = "Error deleting."; }
    }
}

// Handle Create/Update
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $name = trim($_POST['name']);
    $id = intval($_POST['id']);
    if(empty($name)){ $error_message = "Component name cannot be empty."; }
    else {
        if($id > 0){ // Update
            $stmt = $conn->prepare("UPDATE fee_components SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $id);
            if($stmt->execute()){ $success_message = "Component updated."; } else { $error_message = "Error. Name may already exist.";}
        } else { // Create
            $stmt = $conn->prepare("INSERT INTO fee_components (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            if($stmt->execute()){ $success_message = "Component created."; } else { $error_message = "Error. Name may already exist.";}
        }
    }
}

// Handle Edit mode
if(isset($_GET['edit_id'])){
    $edit_id = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT * FROM fee_components WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){ $edit_mode = true; $component_to_edit = $result->fetch_assoc(); }
}

$components_result = $conn->query("SELECT * FROM fee_components ORDER BY name ASC");
?>

<div class="page-header">
    <h1>Manage Fee Components</h1>
    <p>Create and manage individual fee items (e.g., Tuition, Library Fee).</p>
</div>

<?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
<?php if ($error_message): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>

<div class="content-panel">
    <h2><?php echo $edit_mode ? 'Edit Fee Component' : 'Create New Fee Component'; ?></h2>
    <form action="manage_fee_components.php" method="POST">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($component_to_edit['id']); ?>">
        <div class="form-row">
            <div class="form-group">
                <label for="name">Component Name</label>
                <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($component_to_edit['name']); ?>" placeholder="e.g., Tuition Fee" required>
            </div>
            <div class="form-group-action">
                <button type="submit" class="btn"><?php echo $edit_mode ? 'Update' : 'Create'; ?></button>
                <?php if ($edit_mode): ?><a href="manage_fee_components.php" class="btn btn-outline">Cancel</a><?php endif; ?>
            </div>
        </div>
    </form>
</div>

<div class="content-panel">
    <h2>Existing Fee Components</h2>
    <table class="data-table">
        <thead><tr><th>#</th><th>Component Name</th><th>Actions</th></tr></thead>
        <tbody>
            <?php if ($components_result && $components_result->num_rows > 0): ?>
                <?php $i = 1; while($row = $components_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td>
                            <a href="manage_fee_components.php?edit_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                            <a href="manage_fee_components.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="3">No fee components found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>


