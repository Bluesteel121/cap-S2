<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();

session_start();

function debugLog($message) {
    error_log($message);
    file_put_contents('user_login.log', date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

include "connect.php"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";
    // We are explicitly setting the role to 'user' for this handler
    $role = "user"; 

    debugLog("Database Connection Status: " . ($conn ? "Connected" : "Failed"));
    debugLog("Attempting user login - Username: $username, Expected Role: $role");

    try {
        $stmt = $conn->prepare("SELECT * FROM accounts WHERE username = ? AND role = ?");
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ss", $username, $role);
        $stmt->execute();
        $result = $stmt->get_result();

        debugLog("Query result rows: " . $result->num_rows);

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            
            debugLog("Stored Password: " . $row["password"]);
            debugLog("Submitted Password: " . $password);

            // Direct password comparison (as per original code, hashing is recommended)
            if ($password === $row["password"]) {
                $_SESSION["username"] = $username;
                $_SESSION["role"] = $role;
                $_SESSION['name'] = $row['name']; // Store the user's name in the session
                
                debugLog("Password match. Redirecting to userpage.php");
                
                ob_clean();
                header("Location: loggedin_index.php");
                exit();
            } else {
                debugLog("Password mismatch for user: $username");
                $_SESSION['login_error'] = "Invalid password";
                header("Location: userlogin.php");
                exit();
            }
        } else {
            debugLog("No matching user found for Username: $username, Role: $role");
            $_SESSION['login_error'] = "Invalid username or role";
            header("Location: userlogin.php");
            exit();
        }
    } catch (Exception $e) {
        debugLog("Exception: " . $e->getMessage());
        $_SESSION['login_error'] = "An error occurred during login";
        header("Location: userlogin.php");
        exit();
    }
}
?>