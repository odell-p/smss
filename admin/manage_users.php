<?php
require_once '../includes/header.php';
// Add access control: Only Admins should see this page.
if ($_SESSION['user_role'] !== 'Admin') {
    // Redirect to a safe page like their dashboard
    echo "<script>window.location.href='dashboard.php';</script>";
    exit();
}

$success_message = '';
$error_message = '';
$edit_mode = false;
$user_to_edit = ['id'=>'', 'full_name'=>'', 'email'=>'', 'role_id'=>'', 'department_id'=>'', 'status'=>''];

// --- ACTION HANDLING ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // --- CREATE OR UPDATE USER ---
    if (isset($_POST['save_user'])) {
        $id = intval($_POST['id']);
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $role_id = intval($_POST['role_id']);
        $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : NULL;
        $status = $_POST['status'];

        if (empty($full_name) || empty($email) || empty($role_id)) {
            $error_message = "Full Name, Email, and Role are required.";
        } else {
            // --- UPDATE ---
            if ($id > 0) {
                $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, role_id=?, department_id=?, status=? WHERE id=?");
                $stmt->bind_param("ssiisi", $full_name, $email, $role_id, $department_id, $status, $id);
                if($stmt->execute()){ $success_message = "User updated successfully!"; } else { $error_message = "Error updating user. Email may already exist."; }
            // --- CREATE ---
            } else {
                // Generate a temporary password
                $temp_password = 'Password123!'; // Or a more random one
                $hashed_password = password_hash($temp_password, PASSWORD_BCRYPT);
                
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role_id, department_id, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssiss", $full_name, $email, $hashed_password, $role_id, $department_id, $status);
                if($stmt->execute()){ $success_message = "User created successfully! Temporary password is: <strong>$temp_password</strong>"; } else { $error_message = "Error creating user. Email may already exist."; }
            }
            $stmt->close();
        }
    }
    // --- RESET PASSWORD ---
    if (isset($_POST['reset_password'])) {
        $id = intval($_POST['id']);
        $temp_password = 'Password123!';
        $hashed_password = password_hash($temp_password, PASSWORD_BCRYPT);
        
        $stmt = $conn->prepare("UPDATE users SET password = ?, status = 'pending_password_change' WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $id);
        if($stmt->execute()){ $success_message = "Password reset successfully! New temporary password is: <strong>$temp_password</strong>"; } else { $error_message = "Error resetting password."; }
        $stmt->close();
    }
}

// --- GET request for EDIT mode ---
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role_id != 2"); // Exclude students
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_mode = true;
        $user_to_edit = $result->fetch_assoc();
    } else {
        $error_message = "User not found or is a student.";
    }
    $stmt->close();
}

// --- DATA FETCHING ---
// Fetch all roles except 'Student' for the dropdown
$roles_result = $conn->query("SELECT * FROM roles WHERE id != 2 ORDER BY role_name");
$departments_result = $conn->query("SELECT * FROM departments ORDER BY name");

// Fetch all non-student users for the table
$users_result = $conn->query("
    SELECT u.id, u.full_name, u.email, u.status, r.role_name, d.name as department_name
    FROM users u
    JOIN roles r ON u.role_id = r.id
    LEFT JOIN departments d ON u.department_id = d.id
    WHERE u.role_id != 2
    ORDER BY u.full_name ASC
");
?>

<div class="page-header">
    <h1>User Management</h1>
    <p>Create, edit, and manage all staff and administrative accounts.</p>
</div>

<?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
<?php if ($error_message): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>

<!-- Form Panel -->
<div class="content-panel">
    <h2><?php echo $edit_mode ? 'Edit User' : 'Create New User'; ?></h2>
    <form action="manage_users.php" method="POST">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($user_to_edit['id']); ?>">
        <div class="form-row">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user_to_edit['full_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user_to_edit['email']); ?>" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="role_id">Role</label>
                <select name="role_id" required>
                    <option value="">-- Select Role --</option>
                    <?php while($role = $roles_result->fetch_assoc()): ?>
                        <option value="<?php echo $role['id']; ?>" <?php if($role['id'] == $user_to_edit['role_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($role['role_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="department_id">Department (for HOD/Lecturer)</label>
                <select name="department_id">
                    <option value="">-- No Department --</option>
                    <?php while($dept = $departments_result->fetch_assoc()): ?>
                        <option value="<?php echo $dept['id']; ?>" <?php if($dept['id'] == $user_to_edit['department_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($dept['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <?php if ($edit_mode): ?>
            <div class="form-group">
                <label for="status">Account Status</label>
                <select name="status">
                    <option value="active" <?php if('active' == $user_to_edit['status']) echo 'selected'; ?>>Active</option>
                    <option value="inactive" <?php if('inactive' == $user_to_edit['status']) echo 'selected'; ?>>Inactive</option>
                    <option value="pending_password_change" <?php if('pending_password_change' == $user_to_edit['status']) echo 'selected'; ?>>Pending Password Change</option>
                </select>
            </div>
            <?php else: ?>
                <input type="hidden" name="status" value="pending_password_change">
            <?php endif; ?>
        </div>
        <button type="submit" name="save_user" class="btn"><?php echo $edit_mode ? 'Update User' : 'Create User'; ?></button>
        <?php if ($edit_mode): ?>
            <button type="submit" name="reset_password" class="btn btn-outline" onclick="return confirm('Are you sure you want to reset this user\'s password?');">Reset Password</button>
            <a href="manage_users.php" class="btn btn-outline">Cancel Edit</a>
        <?php endif; ?>
    </form>
</div>

<!-- Users Table -->
<div class="content-panel">
    <h2>Existing Staff Users</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Department</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($users_result->num_rows > 0): ?>
                <?php while($user = $users_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['department_name'] ?? 'N/A'); ?></td>
                        <td>
                            <span class="status-<?php echo str_replace('_', '-', $user['status']); ?>">
                                <?php echo ucwords(str_replace('_', ' ', $user['status'])); ?>
                            </span>
                        </td>
                        <td>
                            <a href="manage_users.php?edit_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>
