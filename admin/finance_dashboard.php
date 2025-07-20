?php
require_once '../includes/header.php';
// db_connect is already included in header.php

// --- Dashboard Logic for Finance Officer ---

// We'll calculate stats for the CURRENT ACTIVE session.
$active_session_query = "SELECT id, name FROM academic_sessions WHERE is_current = 1 LIMIT 1";
$active_session_result = $conn->query($active_session_query);
$active_session = $active_session_result->fetch_assoc();
$active_session_id = $active_session['id'] ?? 0;
$active_session_name = $active_session['name'] ?? 'None';

$stats = [
    'total_collected' => 0,
    'total_expected' => 0,
    'total_outstanding' => 0,
];

if ($active_session_id > 0) {
    // 1. Get total amount collected for the active session
    $stmt_collected = $conn->prepare("SELECT SUM(amount_paid) as total FROM student_payments WHERE academic_session_id = ?");
    $stmt_collected->bind_param("i", $active_session_id);
    $stmt_collected->execute();
    $stats['total_collected'] = $stmt_collected->get_result()->fetch_assoc()['total'] ?? 0;

    // 2. Get total expected revenue (This is a more complex query for later, we'll placeholder it)
    // For now, let's just make it a simple placeholder.
    $stats['total_expected'] = 'N/A';
    $stats['total_outstanding'] = 'N/A';
}

?>

<div class="page-header">
    <h1>Finance Dashboard</h1>
    <p>Financial overview for the current active session: <strong><?php echo htmlspecialchars($active_session_name); ?></strong></p>
</div>

<div class="card-container">
    <div class="stat-card green">
        <div class="card-body">
            <h3>TOTAL COLLECTED (CURRENT SESSION)</h3>
            <p>$<?php echo number_format($stats['total_collected'], 2); ?></p>
        </div>
    </div>
    <div class="stat-card orange">
        <div class="card-body">
            <h3>TOTAL EXPECTED (CURRENT SESSION)</h3>
            <p><?php echo is_numeric($stats['total_expected']) ? '$'.number_format($stats['total_expected'], 2) : $stats['total_expected']; ?></p>
        </div>
    </div>
    <div class="stat-card red">
        <div class="card-body">
            <h3>TOTAL OUTSTANDING (CURRENT SESSION)</h3>
            <p><?php echo is_numeric($stats['total_outstanding']) ? '$'.number_format($stats['total_outstanding'], 2) : $stats['total_outstanding']; ?></p>
        </div>
    </div>
</div>

<div class="content-panel">
    <h2>Quick Actions</h2>
    <div class="quick-actions-container">
        <a href="financial_settings.php" class="quick-action-btn">System Financial Settings</a>
        <a href="manage_fee_components.php" class="quick-action-btn">Manage Fee Components</a>
        <a href="manage_fee_structure.php" class="quick-action-btn">Manage Fee Structures</a>
        <a href="#" class="quick-action-btn">Upload Student Payments</a>
    </div>
</div>


<?php require_once '../includes/footer.php'; ?>
