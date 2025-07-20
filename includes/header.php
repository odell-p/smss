<?php
// Must be the very first thing on the page to use sessions.
session_start();

// --- SECURITY CHECK ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// --- USER & PAGE DATA ---
$user_role = $_SESSION['user_role'] ?? 'Guest';
$user_name = $_SESSION['user_name'] ?? 'User';
$current_page = basename($_SERVER['SCRIPT_NAME']);

// --- FETCH CURRENT ACADEMIC SESSION ---
require_once __DIR__ . '/db_connect.php'; 

$current_session_name = "No Active Session"; 
$sql_current_session = "SELECT name FROM academic_sessions WHERE is_current = 1 LIMIT 1";
$result_current_session = $conn->query($sql_current_session);
if ($result_current_session && $result_current_session->num_rows > 0) {
    $current_session_row = $result_current_session->fetch_assoc();
    $current_session_name = $current_session_row['name'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user_role); ?> Dashboard - SMSS</title>
    
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
    
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Top navigation bar updated -->
    <div class="top-bar">
        <!-- CRITICAL CHANGE 1: MOBILE BUTTON MOVED HERE -->
        <button id="mobile-menu-toggle">☰</button>

        <div class="user-greeting">
            Hello, <?php echo htmlspecialchars($user_name); ?>
        </div>
        <div class="current-session-display">
            <span>Active Session:</span>
            <strong><?php echo htmlspecialchars($current_session_name); ?></strong>
        </div>
    </div>

    <!-- Main wrapper for the sidebar and content -->
    <div class="wrapper">
        
        <nav id="sidebar">
            <ul class="sidebar-nav">
                
                <!-- Dashboard Link -->
                <li class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                    <a href="dashboard.php"><i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span></a>
                </li>

                <!-- System Control Links -->
                <!-- System Control Links (Visible to Admin & Academic Affairs) -->
                <?php if (in_array($user_role, ['Admin', 'Academic Affairs'])): ?>
                    <?php
                        // 1. Define the group of pages that belong to this submenu.
                        $system_control_pages = [
                            'manage_sessions.php', 
                            'manage_years.php', 
                            'manage_departments.php', 
                            'manage_programmes.php', 
                            'manage_courses.php'
                        ];
                        
                        // 2. Check if the current page is one of them.
                        $is_system_control_open = in_array($current_page, $system_control_pages);
                    ?>
                    
                    <!-- 3. Add the 'open' class to the parent <li> if the condition is true. -->
                    <li class="has-submenu <?php echo $is_system_control_open ? 'open' : ''; ?>">
                        <a href="#" class="nav-link">
                            <i class="fa-solid fa-cogs"></i> 
                            <span>System Control</span> 
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                        
                        <!-- 4. Add an inline style to the <ul> to make it visible on page load if the condition is true. -->
                        <ul class="submenu" style="<?php echo $is_system_control_open ? 'display: block;' : ''; ?>">
                            <li class="<?php echo ($current_page == 'manage_sessions.php') ? 'active' : ''; ?>"><a href="manage_sessions.php">Semester</a></li>
                            <li class="<?php echo ($current_page == 'manage_years.php') ? 'active' : ''; ?>"><a href="manage_years.php">Academic Year</a></li>
                            <li class="<?php echo ($current_page == 'manage_departments.php') ? 'active' : ''; ?>"><a href="manage_departments.php">Department</a></li>
                            <li class="<?php echo ($current_page == 'manage_programmes.php') ? 'active' : ''; ?>"><a href="manage_programmes.php">Programme</a></li>
                            <li class="<?php echo ($current_page == 'manage_courses.php') ? 'active' : ''; ?>"><a href="manage_courses.php">Courses</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                                <!-- ========================================================== -->
                <!--   NEW: USER MANAGEMENT MENU BLOCK (ADMIN ONLY)             -->
                <!-- ========================================================== -->
                <?php if ($user_role == 'Admin'): ?>
                    <?php
                        $user_management_pages = ['manage_users.php'];
                        $is_user_mgmt_open = in_array($current_page, $user_management_pages);
                    ?>
                    <li class="has-submenu <?php echo $is_user_mgmt_open ? 'open' : ''; ?>">
                        <a href="#" class="nav-link">
                            <i class="fa-solid fa-users-gear"></i> 
                            <span>User Management</span> 
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                        <ul class="submenu" style="<?php echo $is_user_mgmt_open ? 'display: block;' : ''; ?>">
                            <li class="<?php echo ($current_page == 'manage_users.php') ? 'active' : ''; ?>"><a href="manage_users.php">Manage Staff</a></li>
                            <li><a href="#">Manage Roles</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
                <!-- ========================================================== -->

                <!-- HOD-specific Links -->
                <?php if ($user_role == 'HOD'): ?>
                    <li class="has-submenu">
                        <a href="#" class="nav-link">
                            <i class="fa-solid fa-chalkboard-user"></i> 
                            <span>My Department</span> 
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="#">Department Staff</a></li>
                            <li><a href="#">Department Students</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <!-- Common Logout Link -->
                <li>
                    <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
                </li>
            </ul>

            <!-- CRITICAL CHANGE 3: DESKTOP COLLAPSE BUTTON ADDED HERE -->
            <div class="sidebar-footer">
                <button id="desktop-sidebar-toggle">
                    <i class="fa-solid fa-angles-left"></i>
                </button>
            </div>
        </nav>

        <!-- Main Content Area -->
        <div id="main-content">
            <!-- The old mobile button is correctly removed from here. -->
            
            <!-- The rest of the page's unique content will be inserted here -->