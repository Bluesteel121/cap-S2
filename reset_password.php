<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Load activity logger if available
if (file_exists('user_activity_logger.php')) {
    require_once 'user_activity_logger.php';
    if (function_exists('logPageView')) {
        logPageView('Reset Password Page');
    }
}

require_once 'connect.php';

// Check if user has verified the code
if (!isset($_SESSION['reset_user_verified']) || !isset($_SESSION['reset_user_id'])) {
    $_SESSION['forgot_error'] = 'Please verify your code first.';
    header('Location: forgot_password.php');
    exit();
}

$userId = $_SESSION['reset_user_id'];
$tableToUse = $_SESSION['reset_table'] ?? 'accounts';
$email = $_SESSION['reset_email'] ?? '';

// Get username
$stmt = $conn->prepare("SELECT username, name FROM $tableToUse WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$username = $user['username'] ?? $user['name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="icon" href="Images/Favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="h-screen flex relative bg-gray-100">
    <div class="m-auto bg-white p-8 rounded-lg shadow-lg w-96">
        <img src="Images/logo.png" alt="Logo" class="mx-auto h-16 mb-4">
        
        <?php
        if (isset($_SESSION['reset_error'])) {
            echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>" . htmlspecialchars($_SESSION['reset_error']) . "</div>";
            unset($_SESSION['reset_error']);
        }
        if (isset($_SESSION['reset_success'])) {
            echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>" . htmlspecialchars($_SESSION['reset_success']) . "</div>";
            unset($_SESSION['reset_success']);
        }
        ?>

        <form action="reset_password_handler.php" method="POST" id="resetForm">
            <h2 class="text-2xl font-bold text-center mb-2">Reset Password</h2>
            <p class="text-gray-600 text-center mb-6 text-sm">Welcome back, <?php echo htmlspecialchars($username); ?>!</p>
            
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                <div class="relative">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Enter new password" 
                        class="border w-full px-4 py-2 rounded-lg pr-10 focus:ring-green-500 focus:border-green-500" 
                        required>
                    <button type="button" onclick="togglePassword('password', 'password-icon')" class="absolute right-3 top-3 text-gray-500">
                        <i class="fa fa-eye" id="password-icon"></i>
                    </button>
                </div>
            </div>

            <div class="mb-4">
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                <div class="relative">
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        placeholder="Confirm new password" 
                        class="border w-full px-4 py-2 rounded-lg pr-10 focus:ring-green-500 focus:border-green-500" 
                        required>
                    <button type="button" onclick="togglePassword('confirm_password', 'confirm_password-icon')" class="absolute right-3 top-3 text-gray-500">
                        <i class="fa fa-eye" id="confirm_password-icon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="bg-green-500 text-white w-full py-2 mt-6 rounded-lg hover:bg-green-700 transition-colors duration-200">
                Reset Password
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="userlogin.php" class="text-sm text-green-600 hover:underline">Back to Login</a>
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

        function logActivityClient(action, details) {
            fetch('log_activity.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=' + encodeURIComponent(action) + '&details=' + encodeURIComponent(details)
            }).catch(error => console.error('Logging error:', error));
        }

        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match.');
                logActivityClient('RESET_PASSWORD_MISMATCH', 'Passwords do not match');
                return false;
            }

            logActivityClient('RESET_PASSWORD_ATTEMPT', 'Password reset initiated');
        });
    </script>
</body>
</html>