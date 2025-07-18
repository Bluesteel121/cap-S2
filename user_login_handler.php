<?php
session_start();

require_once 'connect.php';

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
            $stmt->bind_result($id, $name, $hashed_password);
            $stmt->fetch();

            // Verify the password
            // Note: Your current code stores plain passwords. It is highly recommended to hash passwords.
            // If you implement hashing, use password_verify($password, $hashed_password) instead of a direct comparison.
            if ($password === $hashed_password) { // Replace with password_verify if using hashed passwords
                // Login successful
                $_SESSION['id'] = $id;
                $_SESSION['name'] = $name;
                $_SESSION['role'] = $role;
                $_SESSION['login_success'] = "Login successful!"; // Set success message
                header("Location: loggedin_index.php"); // Redirect to logged-in page
                exit();
            } else {
                // Incorrect password
                $_SESSION['login_error'] = "Invalid username or password."; // Set error message
            }
        } else {
            // User not found
            $_SESSION['login_error'] = "Invalid username or password."; // Set error message
        }

        $stmt->close();
    } else {
        // Error preparing the statement
        $_SESSION['login_error'] = "An internal error occurred. Please try again later."; // Set error message
    }
} else {
    // Not a POST request
    $_SESSION['login_error'] = "Invalid request method."; // Set error message
}

// Close database connection
closeConnection();

?>