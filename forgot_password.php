<?php
// Enable error display for localhost
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'user_activity_logger.php';

logPageView('Forgot Password Page');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="icon" href="Images/Favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-screen flex relative bg-gray-100">
    <div class="m-auto bg-white p-8 rounded-lg shadow-lg w-96">
        <img src="Images/logo.png" alt="Logo" class="mx-auto h-16 mb-4">
        
        <?php
        // Display error messages
        if (isset($_SESSION['forgot_error'])) {
            logActivity('FORGOT_PASSWORD_ERROR_DISPLAYED', $_SESSION['forgot_error']);
            echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>" . 
                 htmlspecialchars($_SESSION['forgot_error']) . 
                 "</div>";
            unset($_SESSION['forgot_error']);
        }
        
        // Display success messages
        if (isset($_SESSION['forgot_success'])) {
            logActivity('FORGOT_PASSWORD_SUCCESS_DISPLAYED', $_SESSION['forgot_success']);
            echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>" . 
                 htmlspecialchars($_SESSION['forgot_success']) . 
                 "</div>";
            unset($_SESSION['forgot_success']);
        }
        
        // LOCALHOST ONLY: Display the reset link for easy testing
        if (isset($_SESSION['reset_link_for_testing'])) {
            echo "<div class='bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4' role='alert'>";
            echo "<strong>📧 Localhost Testing - Reset Link:</strong><br>";
            echo "<div class='mt-2 p-2 bg-white rounded border border-blue-300' style='word-break: break-all; font-size: 12px;'>";
            echo htmlspecialchars($_SESSION['reset_link_for_testing']);
            echo "</div>";
            echo "<a href='" . htmlspecialchars($_SESSION['reset_link_for_testing']) . "' class='inline-block mt-2 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600'>Click to Reset Password</a>";
            echo "</div>";
            unset($_SESSION['reset_link_for_testing']);
        }
        ?>

        <form action="forgot_password_handler.php" method="POST" id="forgotForm">
            <h2 class="text-2xl font-bold text-center mb-6">Forgot Password</h2>
            <p class="text-gray-600 text-center mb-6 text-sm">Enter your email address and we'll send you a link to reset your password.</p>
            
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="Enter your registered email" 
                    class="border w-full px-4 py-2 rounded-lg focus:ring-green-500 focus:border-green-500" 
                    required>
            </div>

            <button type="submit" class="bg-green-500 text-white w-full py-2 mt-4 rounded-lg hover:bg-green-700 transition-colors duration-200">
                Send Reset Link
            </button>
        </form>

        <div class="mt-6 text-center space-y-4">
            <div>
                <a href="userlogin.php" class="text-sm text-green-600 hover:underline">Back to Login</a>
            </div>
            <div>
                <a href="account.php" class="inline-block bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors duration-200">
                    ← Back to Account Selection
                </a>
            </div>
        </div>
    </div>

    <script>
        function logActivityClient(action, details) {
            fetch('log_activity.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=' + encodeURIComponent(action) + '&details=' + encodeURIComponent(details)
            }).catch(error => console.error('Logging error:', error));
        }

        document.getElementById('forgotForm').addEventListener('submit', function() {
            const email = document.getElementById('email').value;
            logActivityClient('FORGOT_PASSWORD_ATTEMPT', 'Email: ' + email);
        });
    </script>
</body>
</html>