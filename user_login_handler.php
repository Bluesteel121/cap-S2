<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once 'connect.php';
require_once 'includes/debug.php'; // Include your logging function if you want to keep logging

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data, using null coalescing operator for safety
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? ''; // Should be 'user' from the form

    // Basic validation
    if (empty($username) || empty($password) || $role !== 'user') {
        $_SESSION['login_error'] = "Invalid request. Please try again.";
        header("Location: userlogin.php");
        exit();
    }

    // Prepare and execute the SQL query to find the user
    $sql = "SELECT id, name, password FROM accounts WHERE username = ? AND role = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ss", $username, $role);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id, $name, $stored_password);
            $stmt->fetch();

            // Verify the password (direct comparison as requested)
            if ($password === $stored_password) {
                // Login successful - SET ALL REQUIRED SESSION VARIABLES
                $_SESSION['id'] = $id;
                $_SESSION['username'] = $username; // THIS WAS MISSING!
                $_SESSION['name'] = $name;
                $_SESSION['role'] = $role;
                $_SESSION['login_success'] = "Login successful!";
                
                header("Location: loggedin_index.php");
                exit();
            } else {
                // Incorrect password
                $_SESSION['login_error'] = "Invalid username or password.";
                header("Location: userlogin.php");
                exit();
            }
        } else {
            // User not found
            $_SESSION['login_error'] = "Invalid username or password.";
            header("Location: userlogin.php");
            exit();
        }

        $stmt->close();
    } else {
        // Error preparing the statement
        $_SESSION['login_error'] = "An internal error occurred. Please try again later.";
        header("Location: userlogin.php");
        exit();
    }
} else {
    // Not a POST request
    $_SESSION['login_error'] = "Invalid request method.";
    header("Location: userlogin.php");
    exit();
}

// Close database connection
closeConnection();
?>