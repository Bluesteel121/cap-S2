<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
session_start();

include "connect.php";

// Define logging function
function logAdminLoginAttempt($message) {
    $logFile = 'admin_login_attempts.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[{$timestamp}] {$message}" . PHP_EOL, FILE_APPEND);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = filter_input(INPUT_POST, 'password'); // Removed FILTER_SANITIZE_STRING

    // Basic validation
    if (empty($username) || empty($password)) {
        logAdminLoginAttempt("Failed login attempt: Username or password empty for username '{$username}'");
        $_SESSION['login_error'] = "Please enter both username and password.";
        header("Location: adminlogin.php");
        exit();
    }

    // Query the database for the admin user
    $stmt = $conn->prepare("SELECT id, username, password, role FROM accounts WHERE username = ? AND role = 'admin'");
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

        // Verify password
        if ($password === $user['password']) {
            // Successful login
            logAdminLoginAttempt("Successful login for admin user: '{$username}'");
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['user_id'] = $user['id'];

            ob_clean();
            header("Location: admin_loggedin_index.php");
            exit();
        } else {
            // Password does not match
            logAdminLoginAttempt("Failed login attempt: Incorrect password for admin user '{$username}'");
            $_SESSION['login_error'] = "Invalid username or password."; // Generic error for security
            header("Location: adminlogin.php");
            exit();
        }
    } else {
        // No user found with the provided username and admin role
        logAdminLoginAttempt("Failed login attempt: No admin user found with username '{$username}'");
        $_SESSION['login_error'] = "Invalid username or password."; // Generic error for security
        header("Location: adminlogin.php");
        exit();
    }

    $stmt->close();
    $conn->close();

} else {
    // If not a POST request, redirect back to login page
    header("Location: adminlogin.php");
    exit();
}

?>