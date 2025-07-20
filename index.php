<?php
// ========================================================================
//  LOGIN LOGIC
// ========================================================================

ini_set('display_errors', 1);
error_reporting(E_ALL);
// Must be the very first thing on the page.
session_start();

// If the user is already logged in, redirect them to the admin dashboard.
if (isset($_SESSION['user_id'])) {
    header("Location: admin/dashboard.php");
    exit();
}

// Include the database connection file.
require_once 'includes/db_connect.php';

// Define a variable to hold error messages.
$error_message = '';

// Check if the form has been submitted using POST.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Check if email and password are set
    if (!empty($_POST['email']) && !empty($_POST['password'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Prepare a SQL statement to prevent SQL injection.
        // We join with the 'roles' table to get the role name.
        $sql = "SELECT u.id, u.full_name, u.password, r.role_name
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.email = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if a user with that email exists.
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // Verify the password against the stored hash.
            if (password_verify($password, $user['password'])) {
                // Password is correct. Login successful.
                
                // Regenerate session ID for security.
                session_regenerate_id(true);

                // Store user data in the session.
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role_name'];

                    // --- NEW REDIRECTION LOGIC ---
            if ($user['role_name'] == 'Finance') {
                header("Location: admin/finance_dashboard.php");
            } else {
                // Default redirect for Admin, HOD, etc.
                header("Location: admin/dashboard.php");
            }
            exit();

            } else {
                // Password is not correct.
                $error_message = "Invalid email or password.";
            }
        } else {
            // No user found with that email.
            $error_message = "Invalid email or password.";
        }

        $stmt->close();
    } else {
        $error_message = "Please enter both email and password.";
    }
}

$conn->close();

// ========================================================================
//  LOGIN PAGE HTML
// ========================================================================
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - School Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-bg: #f4f7fa;
            --form-bg: #ffffff;
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --brand-color: #1f2937; /* Dark Gray from your theme */
            --card-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--primary-bg);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: var(--form-bg);
            padding: 3rem;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .login-container h1 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--brand-color);
        }
        .login-container p {
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }
        .login-form .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }
        .login-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .login-form input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            box-sizing: border-box; /* Important */
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        .login-form input:focus {
            outline: none;
            border-color: var(--brand-color);
            box-shadow: 0 0 0 3px rgba(31, 41, 55, 0.1);
        }
        .btn-login {
            width: 100%;
            padding: 0.9rem;
            border: none;
            border-radius: 8px;
            background-color: var(--brand-color);
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-login:hover {
            background-color: #374151;
        }
        .error-message {
            background-color: #fee2e2;
            color: #b91c1c;
            padding: 0.8rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
            border: 1px solid #fca5a5;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Welcome Back</h1>
        <p>Login to access your dashboard</p>

        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form action="index.php" method="post" class="login-form">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-login">Login</button>
        </form>
    </div>
</body>
</html>