<?php
require_once '../includes/header.php';
// Ensure only Admin or Finance can access this page
if (!in_array($_SESSION['user_role'], ['Admin', 'Finance'])) {
    // Redirect to their dashboard or an access denied page
    header("Location: dashboard.php");
    exit();
}

$success_message = '';
$error_message = '';

// --- Handle form submission for updating settings ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    $reg_percentage = intval($_POST['registration_fee_percentage']);

    if ($reg_percentage >= 0 && $reg_percentage <= 100) {
        $stmt = $conn->prepare("UPDATE financial_settings SET setting_value = ? WHERE setting_key = 'registration_fee_percentage'");
        $stmt->bind_param("s", $reg_percentage);
        if ($stmt->execute()) {
            $success_message = "Settings updated successfully!";
        } else {
            $error_message = "Error updating settings.";
        }
        $stmt->close();
    } else {
        $error_message = "Percentage must be between 0 and 100.";
    }
}

// --- Fetch current settings from the database ---
$settings_result = $conn->query("SELECT * FROM financial_settings");
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row;
}
$current_reg_percentage = $settings['registration_fee_percentage']['setting_value'] ?? '60';

?>

<div class="page-header">
    <h1>Financial Settings</h1>
    <p>Manage global financial rules and policies for the system.</p>
</div>

<?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
<?php if ($error_message): ?><div class="alert alert-danger"><?php echo $error_message; ?></div><?php endif; ?>

<div class="content-panel">
    <h2>Course Registration Policy</h2>
    <form action="financial_settings.php" method="POST">
        <div class="form-group">
            <label for="registration_fee_percentage">Minimum Fee Payment Percentage for Registration</label>
            <input type="number" name="registration_fee_percentage" id="registration_fee_percentage" 
                   value="<?php echo htmlspecialchars($current_reg_percentage); ?>" min="0" max="100" required>
            <p class="form-help-text">Enter a number (e.g., 60 for 60%). This is the minimum percentage of session fees a student must pay to be eligible for course registration.</p>
        </div>
        <button type="submit" name="update_settings" class="btn">Update Settings</button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>

