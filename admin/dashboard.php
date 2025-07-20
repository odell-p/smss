<?php
// Include the header and database connection
require_once '../includes/header.php';
require_once '../includes/db_connect.php';

// --- ROLE-BASED DASHBOARD LOGIC ---

// Get the role of the logged-in user from the session
$user_role = $_SESSION['user_role'] ?? 'Guest';
$user_id   = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['user_name'] ?? 'User';

// Initialize variables to hold dashboard data
$stats = [];
$page_title = 'Dashboard';
$page_subtitle = "Overview of the system's key metrics.";

// Use a switch statement to load data based on the user's role
switch ($user_role) {
    case 'Admin':
        // --- ADMIN DASHBOARD DATA ---
        $page_subtitle = "System-wide overview of all key metrics.";
        
        // 1. Get total number of users
        $result_total_users = $conn->query("SELECT COUNT(*) as total FROM users");
        $stats['total_users'] = $result_total_users->fetch_assoc()['total'];

        // 2. Get total number of students (role_id = 2)
        $result_total_students = $conn->query("SELECT COUNT(*) as total FROM users WHERE role_id = 2");
        $stats['total_students'] = $result_total_students->fetch_assoc()['total'];

        // 3. Get number of users who need to change their password
        $result_pending_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 'pending_password_change'");
        $stats['pending_users'] = $result_pending_users->fetch_assoc()['total'];
        break;

    case 'HOD':
        // --- HOD DASHBOARD DATA ---
        // First, get the HOD's department ID
        $stmt_dept = $conn->prepare("SELECT d.name, d.id FROM users u JOIN departments d ON u.department_id = d.id WHERE u.id = ?");
        $stmt_dept->bind_param("i", $user_id);
        $stmt_dept->execute();
        $dept_result = $stmt_dept->get_result();
        
        if ($dept_result->num_rows > 0) {
            $department = $dept_result->fetch_assoc();
            $department_id = $department['id'];
            $department_name = $department['name'];

            $page_title = "HOD Dashboard";
            $page_subtitle = "Overview of the " . htmlspecialchars($department_name) . " Department.";

            // 1. Get total number of students in THIS department
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role_id = 2 AND department_id = ?");
            $stmt->bind_param("i", $department_id);
            $stmt->execute();
            $stats['dept_students'] = $stmt->get_result()->fetch_assoc()['total'];

            // 2. Get total number of lecturers in THIS department (role_id = 4 for Lecturer)
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role_id = 4 AND department_id = ?");
            $stmt->bind_param("i", $department_id);
            $stmt->execute();
            $stats['dept_lecturers'] = $stmt->get_result()->fetch_assoc()['total'];
            
            // 3. Get total number of courses for THIS department (Placeholder for future)
            // For now, let's set a placeholder
            $stats['dept_courses'] = 'N/A';

        } else {
             $page_subtitle = "You are not currently assigned to a department.";
        }
        $stmt_dept->close();
        break;

    default:
        // --- DEFAULT DASHBOARD FOR OTHER ROLES (Student, Lecturer, etc.) ---
        $page_subtitle = "Welcome to your personal dashboard.";
        break;
}

?>

<div class="page-header">
    <h1><?php echo $page_title; ?></h1>
    <p><?php echo $page_subtitle; ?></p>
</div>

<div class="card-container">
    <?php // --- RENDER CARDS BASED ON ROLE --- ?>

    <?php if ($user_role == 'Admin'): ?>
        <div class="stat-card orange">
            <div class="card-body">
                <h3>TOTAL USERS</h3>
                <p><?php echo $stats['total_users'] ?? '0'; ?></p>
            </div>
        </div>
        <div class="stat-card purple">
            <div class="card-body">
                <h3>TOTAL STUDENTS</h3>
                <p><?php echo $stats['total_students'] ?? '0'; ?></p>
            </div>
        </div>
        <div class="stat-card red">
            <div class="card-body">
                <h3>PENDING ACTIVATION</h3>
                <p><?php echo $stats['pending_users'] ?? '0'; ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($user_role == 'HOD'): ?>
        <div class="stat-card blue">
            <div class="card-body">
                <h3>DEPARTMENT STUDENTS</h3>
                <p><?php echo $stats['dept_students'] ?? '0'; ?></p>
            </div>
        </div>
        <div class="stat-card green">
            <div class="card-body">
                <h3>DEPARTMENT LECTURERS</h3>
                <p><?php echo $stats['dept_lecturers'] ?? '0'; ?></p>
            </div>
        </div>
        <div class="stat-card teal">
            <div class="card-body">
                <h3>DEPARTMENT COURSES</h3>
                <p><?php echo $stats['dept_courses'] ?? '0'; ?></p>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="content-panel">
    <h2>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h2>
    <p>Select an option from the sidebar to manage the student information system.</p>
</div>

<?php
// Include the footer
require_once '../includes/footer.php';
?>