<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
session_start();

include "includes/debug.php"; // Include the debugLog function from the separate file
include "connect.php";

    $password = $_POST["password"] ?? "";
    $requested_role = $_POST["role"] ?? ""; // Get the requested role from the form

    debugLog("Database Connection Status: " . ($conn ? "Connected" : "Failed"));
    debugLog("Attempting admin login - Username: $username, Requested Role: $requested_role");

    try {
        // Ensure database connection is established
        if (!isset($conn) || $conn->connect_error) {
            throw new Exception("Database connection not established");
        }

        // Prepared statement to select user with matching username and requested role
        $stmt = $conn->prepare("SELECT * FROM accounts WHERE username = ? AND role = ? AND password = ?"); // Check for username, role, and password
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ss", $username, $requested_role);
        $stmt->execute();
        $result = $stmt->get_result();

        debugLog("Query result rows: " . $result->num_rows);

        if ($result->num_rows === 1) { // Check if exactly one user with matching credentials and role is found
            $row = $result->fetch_assoc();

            $_SESSION["username"] = $username;
            $_SESSION["role"] = $row["role"];
            $_SESSION["user_id"] = $row["id"]; // Store user ID

            debugLog("Admin login successful for user: $username with role: " . $row["role"]);

            ob_clean();
            header("Location: index.php"); // Redirect to index.php on successful login
            exit();
        } else {
            debugLog("Admin login failed - No matching user found for Username: $username with role: $requested_role");
            $_SESSION['login_error'] = "Invalid username or password"; // Generic error for security
            header("Location: adminlogin.php");
            exit();
        }
    } catch (Exception $e) {
        debugLog("Exception during admin login: " . $e->getMessage());
        $_SESSION['login_error'] = "An error occurred during login. Please try again.";
        header("Location: adminlogin.php");
        exit();
    } finally {
        // Close statement and connection
        if (isset($stmt) && $stmt !== false) {
            $stmt->close();
        }
        // The include 'connect.php' doesn't have a close connection call after every usage,
        // so rely on script termination or manual close if needed elsewhere.
        // closeConnection(); // Uncomment if you want to close the connection here
    }
} else {
    // If not a POST request, redirect back to login page
    header("Location: adminlogin.php");
    exit();
}

?>