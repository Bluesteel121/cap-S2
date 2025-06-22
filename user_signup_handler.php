php
<?php
session_start();

// Include the database connection file
require_once 'connect.php';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $username = $_POST['username'] ?? '';
    $fullname = $_POST['fullname'] ?? '';
    $password = $_POST['password'] ?? '';
    $contact_number = $_POST['contact_number'] ?? '';

    // Validate input
    if (empty($username) || empty($fullname) || empty($password) || empty($contact_number)) {
        $_SESSION['registration_error'] = "All fields are required.";
        header("Location: userlogin.php");
        exit();
    }

    // Check if username already exists
    $check_username_sql = "SELECT id FROM accounts WHERE username = ?";
    $stmt_check = $conn->prepare($check_username_sql);
    $stmt_check->bind_param("s", $username); // Assuming username is a string
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $_SESSION['registration_error'] = "Username already exists.";
        $stmt_check->close();
        closeConnection();
        header("Location: userlogin.php");
        exit();
    }
    $stmt_check->close();

    $role = 'user'; // Set the role to 'user'
    // Insert user into the database
    $insert_user_sql = "INSERT INTO accounts (username, name, password, contact, role) VALUES (?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($insert_user_sql);
    $stmt_insert->bind_param("sssss", $username, $fullname, $password, $contact_number, $role); // Assuming all are strings

    if ($stmt_insert->execute()) {
        $_SESSION['registration_success'] = "Registration successful. You can now log in.";
    } else {
        $_SESSION['registration_error'] = "Error during registration: " . $stmt_insert->error;
    }

    $stmt_insert->close();
} else {
    $_SESSION['registration_error'] = "Invalid request method.";
}

// Close the database connection
closeConnection();

// Redirect back to the login page
header("Location: userlogin.php");
exit();
?>