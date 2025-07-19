<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login - CNLRRS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Add Font Awesome for eye icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="h-screen flex relative bg-gray-100">
    <!-- Login Container -->
    <div class="m-auto bg-white p-8 rounded-lg shadow-lg w-96">
        <img src="Images/logo.png" alt="Logo" class="mx-auto h-16 mb-4">

        <!-- Error/Success Messages -->
        <?php
        if (isset($_SESSION['login_error'])) {
            echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>" .
                 htmlspecialchars($_SESSION['login_error']) .
                 "</div>\n";
            unset($_SESSION['login_error']);
        }
        if (isset($_SESSION['registration_success'])) {
            echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>" .
                 htmlspecialchars($_SESSION['registration_success']) .
                 "</div>\n";
            unset($_SESSION['registration_success']);
        }
 if (isset($_SESSION['login_success'])) {
            echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>" .
                 htmlspecialchars($_SESSION['login_success']) .
                 "</div>";
            unset($_SESSION['login_success']);
        }
        ?>

        <!-- Login Form -->
        <div id="login-section">
            <h2 class="text-2xl font-bold text-center mb-4">User Login</h2>
            <form id="login-form" method="POST" action="user_login_handler.php" autocomplete="off" novalidate>
                <div class="mb-4">
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter username" class="border w-full px-4 py-2 rounded-lg focus:ring-green-500 focus:border-green-500" required>
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

                <input type="hidden" name="role" value="user">

                <button type="submit" class="bg-green-500 text-white w-full py-2 mt-4 rounded-lg hover:bg-green-700 transition-colors duration-200">
                    Login
                </button>
            </form>
        </div>

 <div class="mt-4 text-center">
 <a href="usersignup.php" class="text-sm text-green-600 hover:underline">Don't have an account? Sign Up</a>
 </div>
        <!-- Back to Account Selection Button -->
        <div class="mt-6 text-center">
            <a href="account.php" class="inline-block bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors duration-200">
                ← Back to Account Selection
            </a>
        </div>
    </div>

    <script>
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


user_login_handler.php (Updated):

php
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


