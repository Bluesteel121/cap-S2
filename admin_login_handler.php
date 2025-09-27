<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
session_start();

include "connect.php";

// Include activity logger if available
if (file_exists('user_activity_logger.php')) {
    require_once 'user_activity_logger.php';
}

// Define logging function
function logAdminLoginAttempt($message) {
    $logFile = 'admin_login_attempts.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[{$timestamp}] {$message}" . PHP_EOL, FILE_APPEND);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = filter_input(INPUT_POST, 'username');
    $password = filter_input(INPUT_POST, 'password');

    // Basic validation
    if (empty($username) || empty($password)) {
        logAdminLoginAttempt("Failed login attempt: Username or password empty for username '{$username}'");
        $_SESSION['login_error'] = "Please enter both username and password.";
        header("Location: adminlogin.php");
        exit();
    }

    // Query the database for admin or reviewer user
    $stmt = $conn->prepare("SELECT id, username, password, role, email FROM accounts WHERE username = ? AND role IN ('admin', 'reviewer')");
    if ($stmt === false) {
        logAdminLoginAttempt("Database prepare error: " . $conn->error);
        $_SESSION['login_error'] = "An error occurred. Please try again.";
        header("Location: adminlogin.php");
        exit();
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Direct password comparison for plain text passwords
        if ($password === $user['password']) {
            // Successful login
            logAdminLoginAttempt("Successful login for {$user['role']} user: '{$username}'");
            
            // Set session variables
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];

            // Log activity if logger is available
            if (function_exists('logActivity')) {
                logActivity('LOGIN_SUCCESS', "User logged in with role: {$user['role']}");
            }

            // Try to update last login time (only if column exists)
            $columns_query = "SHOW COLUMNS FROM accounts LIKE 'last_login'";
            $columns_result = $conn->query($columns_query);
            if ($columns_result && $columns_result->num_rows > 0) {
                $login_update = $conn->prepare("UPDATE accounts SET last_login = NOW() WHERE id = ?");
                $login_update->bind_param("i", $user['id']);
                $login_update->execute();
                $login_update->close();
            }

            // Redirect based on role
            ob_clean();
            switch ($user['role']) {
                case 'admin':
                    header("Location: admin_loggedin_index.php");
                    break;
                case 'reviewer':
                    header("Location: reviewer_dashboard.php");
                    break;
                default:
                    // Fallback to admin dashboard
                    header("Location: admin_loggedin_index.php");
                    break;
            }
            exit();
        } else {
            // Password does not match
            logAdminLoginAttempt("Failed login attempt: Incorrect password for user '{$username}' with role '{$user['role']}'");
            $_SESSION['login_error'] = "Invalid username or password."; // Generic error for security
            header("Location: adminlogin.php");
            exit();
        }
    } else {
        // No user found with the provided username and admin/reviewer role
        logAdminLoginAttempt("Failed login attempt: No admin/reviewer user found with username '{$username}'");
        $_SESSION['login_error'] = "Invalid username or password."; // Generic error for security
        header("Location: adminlogin.php");
        exit();
    }

} else {
    // If not a POST request, redirect back to login page
    header("Location: adminlogin.php");
    exit();
}

?>