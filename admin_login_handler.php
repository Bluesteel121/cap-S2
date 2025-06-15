<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
session_start();

function debugLog($message) {
    error_log($message);
    file_put_contents('admin_login_handler.log', date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

include "connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";
    $requested_role = "admin"; // Admins can only log in with the admin role

    debugLog("Database Connection Status: " . ($conn ? "Connected" : "Failed"));
    debugLog("Attempting admin login - Username: $username, Requested Role: $requested_role");

    try {
        // Ensure database connection is established
        if (!isset($conn) || $conn->connect_error) {
            throw new Exception("Database connection not established");
        }

        // Prepared statement to select user with matching username and admin role
        $stmt = $conn->prepare("SELECT * FROM accounts WHERE username = ? AND role = ?");
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ss", $username, $requested_role);
        $stmt->execute();
        $result = $stmt->get_result();

        debugLog("Query result rows: " . $result->num_rows);

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            // Direct password comparison (no hashing based on original code)
            if ($password === $row["password"]) {
                $_SESSION["username"] = $username;
                $_SESSION["role"] = $row["role"]; // Should be 'admin'
                $_SESSION["user_id"] = $row["id"]; // Store user ID

                debugLog("Admin login successful for user: $username");

                ob_clean();
                header("Location: index.php");
                exit();
            } else {
                debugLog("Admin login failed for user: $username - Invalid password");
                $_SESSION['login_error'] = "Invalid username or password";
                header("Location: adminlogin.php");
                exit();
            }
        } else {
            debugLog("Admin login failed - No matching user found for Username: $username with role admin");
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