<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
function debugLog($message) {
    error_log($message);
    file_put_contents('farmer_login.log', date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}


try {
    include "connect.php";
} catch (Exception $e) {
    
    $_SESSION['login_error'] = "Database connection error. Please try again later.";
    debugLog("Database connection error: " . $e->getMessage());
}


function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Login Logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'login') {
    $login_identifier = sanitizeInput($_POST["login_identifier"] ?? "");
    // Accept password as is, no validation or sanitization whatsoever
    $password = isset($_POST["password"]) ? $_POST["password"] : "";

    debugLog("Attempting farmer login - Identifier: $login_identifier");

    try {
        // Check if the database connection is established
        if (!isset($conn) || $conn->connect_error) {
            throw new Exception("Database connection not established");
        }
        
        // Use prepared statement for login with username or contact number
        $stmt = $conn->prepare("SELECT * FROM farmer_acc WHERE username = ? OR contact_num = ?");
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ss", $login_identifier, $login_identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            
           
            if ($password === $row['password']) { 
                session_regenerate_id(true);
                
               
                $_SESSION["username"] = $row['username'];
                if (isset($row['id'])) {
                    $_SESSION["user_id"] = $row['id'];
                }
                
                debugLog("Login successful for identifier: $login_identifier");
                
               
                header("Location: farmerpage.php");
                exit();
            } else {
                debugLog("Login failed for identifier: $login_identifier - Invalid password");
                $_SESSION['login_error'] = "Invalid username/contact or password";
                header("Location: farmerlogin.php");
                exit();
            }
        } else {
            debugLog("Login failed for identifier: $login_identifier - User not found");
            $_SESSION['login_error'] = "Invalid username/contact or password";
            header("Location: farmerlogin.php");
            exit();
        }
    } catch (Exception $e) {
        debugLog("Exception: " . $e->getMessage());
        $_SESSION['login_error'] = "An error occurred during login";
        header("Location: farmerlogin.php");
        exit();
    }
}

// Registration Logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'register') {
    $contact = sanitizeInput($_POST["contact"] ?? "");
    $username = sanitizeInput($_POST["username"] ?? "");
    // Accept password as is with no restrictions
    $password = isset($_POST["password"]) ? $_POST["password"] : "";

    debugLog("Attempting farmer registration - Username: $username");

    try {
        // Check if the database connection is established
        if (!isset($conn) || $conn->connect_error) {
            throw new Exception("Database connection not established");
        }
        
        // Validate contact number must be exactly 11 digits
        if (strlen($contact) != 11) {
            debugLog("Registration failed - Contact number must be exactly 11 digits: $contact");
            $_SESSION['registration_error'] = "Contact number must be exactly 11 digits";
            header("Location: farmerlogin.php");
            exit();
        }
        
        // Check if username already exists
        $check_username = $conn->prepare("SELECT * FROM farmer_acc WHERE username = ?");
        $check_username->bind_param("s", $username);
        $check_username->execute();
        $username_result = $check_username->get_result();

        if ($username_result->num_rows > 0) {
            debugLog("Registration failed - Username already exists: $username");
            $_SESSION['registration_error'] = "Username already exists";
            header("Location: farmerlogin.php");
            exit();
        }

        // Check if contact number already exists
        $check_contact = $conn->prepare("SELECT * FROM farmer_acc WHERE contact_num = ?");
        $check_contact->bind_param("s", $contact);
        $check_contact->execute();
        $contact_result = $check_contact->get_result();

        if ($contact_result->num_rows > 0) {
            debugLog("Registration failed - Contact number already exists: $contact");
            $_SESSION['registration_error'] = "Contact number already exists";
            header("Location: farmerlogin.php");
            exit();
        }

        // Store password as-is with no hashing or validation
        $hashed_password = $password;

        // Prepare insert statement for farmer_acc
        $insert_stmt = $conn->prepare("INSERT INTO farmer_acc (username, contact_num, password) VALUES (?, ?, ?)");
        if ($insert_stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $insert_stmt->bind_param("sss", $username, $contact, $hashed_password);
        
        if ($insert_stmt->execute()) {
            debugLog("Registration successful for: $username");
            $_SESSION['registration_success'] = "Account created successfully. Please log in with your username/contact and password.";
            header("Location: farmerlogin.php");
            exit();
        } else {
            throw new Exception("Execute failed: " . $insert_stmt->error);
        }
    } catch (Exception $e) {
        debugLog("Registration Exception: " . $e->getMessage());
        $_SESSION['registration_error'] = "An error occurred during registration";
        header("Location: farmerlogin.php");
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Login/Signup - CNLRRS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Add Font Awesome for eye icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="h-screen flex relative bg-gray-100">
    <!-- Login/Signup Container -->
    <div class="m-auto bg-white p-8 rounded-lg shadow-lg w-96">
        <img src="Images/logo.png" alt="Logo" class="mx-auto h-16 mb-4">
        
        <!-- Error/Success Messages -->
        <?php
        if (isset($_SESSION['login_error'])) {
            echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>" . 
                 htmlspecialchars($_SESSION['login_error']) . 
                 "</div>";
            unset($_SESSION['login_error']);
        }
        if (isset($_SESSION['registration_error'])) {
            echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>" . 
                 htmlspecialchars($_SESSION['registration_error']) . 
                 "</div>";
            unset($_SESSION['registration_error']);
        }
        if (isset($_SESSION['registration_success'])) {
            echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>" . 
                 htmlspecialchars($_SESSION['registration_success']) . 
                 "</div>";
            unset($_SESSION['registration_success']);
        }
        ?>
        
        <!-- Login Form -->
        <div id="login-section">
            <h2 class="text-2xl font-bold text-center mb-4">Farmer Login</h2>
            <form id="login-form" method="POST" action="farmerlogin.php" autocomplete="off" novalidate>
                <input type="hidden" name="action" value="login">
                <div class="mb-4">
                    <label for="login_identifier" class="block text-sm font-medium text-gray-700 mb-1">Username or Contact Number</label>
                    <input type="text" id="login_identifier" name="login_identifier" placeholder="Enter username or contact number" class="border w-full px-4 py-2 rounded-lg focus:ring-green-500 focus:border-green-500" required>
                </div>
                
                <div class="mb-4">
                    <label for="loginPassword" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="loginPassword" placeholder="Enter your password" class="border w-full px-4 py-2 rounded-lg pr-10 focus:ring-green-500 focus:border-green-500">
                        <button type="button" onclick="togglePassword('loginPassword', 'loginToggleIcon')" class="absolute right-3 top-3 text-gray-500">
                            <i class="far fa-eye" id="loginToggleIcon"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="bg-green-500 text-white w-full py-2 mt-4 rounded-lg hover:bg-green-700 transition-colors duration-200">
                    Login
                </button>
            </form>
            
            <!-- Centered Back Button -->
            <div class="mt-6 text-center">
                <a href="account.php" class="inline-block bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors duration-200">
                    ← Back to Account Selection
                </a>
            </div>
        </div>
    </div>

    <script>
    // Disable any browser validation
    document.addEventListener('DOMContentLoaded', function() {
        // Disable HTML5 validation
        document.getElementById('login-form').setAttribute('novalidate', '');
        
        // Override submit behavior to prevent any validation
        document.getElementById('login-form').addEventListener('submit', function(e) {
            // Allow form submission without any client-side validation
        });
    });

    function togglePassword(passwordFieldId, iconId) {
        const passwordField = document.getElementById(passwordFieldId);
        const icon = document.getElementById(iconId);
        
        if (passwordField.type === "password") {
            passwordField.type = "text";
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            passwordField.type = "password";
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }
    </script>
</body>
</html>