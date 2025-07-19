<?php
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
                // Login successful
                $_SESSION['id'] = $id;
                $_SESSION['name'] = $name;
                $_SESSION['role'] = $role;
                $_SESSION['login_success'] = "Login successful!"; // Set success message
                header("Location: loggedin_index.php"); // Redirect to logged-in page
                exit();
            } else {
                // Incorrect password
                logError("Failed user login attempt: Incorrect password for user '{$username}'"); // Example logging
                $_SESSION['login_error'] = "Invalid username or password."; // Set error message
                header("Location: userlogin.php"); // Redirect back to login page
                exit();
            }
        } else {
            // User not found
            logError("Failed user login attempt: No user found with username '{$username}'"); // Example logging
            $_SESSION['login_error'] = "Invalid username or password."; // Set error message
            header("Location: userlogin.php"); // Redirect back to login page
            exit();
        }

        $stmt->close();
    } else {
        // Error preparing the statement
        logError("Database error during user login: " . $conn->error); // Example logging
        $_SESSION['login_error'] = "An internal error occurred. Please try again later."; // Set error message
        header("Location: userlogin.php"); // Redirect back to login page
        exit();
    }
} else {
    // Not a POST request
    $_SESSION['login_error'] = "Invalid request method."; // Set error message
    header("Location: userlogin.php"); // Redirect back to login page
    exit();
}

// Close database connection
closeConnection();

?>