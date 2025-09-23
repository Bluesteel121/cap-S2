<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'user_activity_logger.php';

// Log page access
logPageView('Forgot Password Page');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - CNLRRS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="h-screen flex relative bg-gray-100">
    <!-- Password Recovery Container -->
    <div class="m-auto bg-white p-8 rounded-lg shadow-lg w-96">
        <img src="Images/logo.png" alt="Logo" class="mx-auto h-16 mb-4">

        <!-- Error/Success Messages -->
        <?php
        if (isset($_SESSION['password_reset_error'])) {
            echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>" .
                 htmlspecialchars($_SESSION['password_reset_error']) .
                 "</div>";
            unset($_SESSION['password_reset_error']);
        }
        if (isset($_SESSION['password_reset_success'])) {
            echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>" .
                 htmlspecialchars($_SESSION['password_reset_success']) .
                 "</div>";
            unset($_SESSION['password_reset_success']);
        }
        ?>

        <!-- Forgot Password Form -->
        <div id="forgot-password-section">
            <h2 class="text-2xl font-bold text-center mb-4">Reset Password</h2>
            <p class="text-gray-600 text-sm text-center mb-6">
                Enter your email address or username and we'll send you instructions to reset your password.
            </p>
            
            <form id="forgot-password-form" method="POST" action="forgot_password_handler.php" autocomplete="off">
                <div class="mb-4">
                    <label for="email_username" class="block text-sm font-medium text-gray-700 mb-1">
                        Email Address or Username
                    </label>
                    <input type="text" 
                           id="email_username" 
                           name="email_username" 
                           placeholder="Enter your email or username" 
                           class="border w-full px-4 py-2 rounded-lg focus:ring-green-500 focus:border-green-500" 
                           required>
                </div>

                <button type="submit" 
                        class="bg-green-500 text-white w-full py-2 mt-4 rounded-lg hover:bg-green-700 transition-colors duration-200">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Send Recovery Email
                </button>
            </form>
        </div>

        <!-- Navigation Links -->
        <div class="mt-6 text-center space-y-2">
            <div>
                <a href="userlogin.php" class="text-sm text-green-600 hover:underline">
                    <i class="fas fa-arrow-left mr-1"></i>
                    Back to Login
                </a>
            </div>
            <div>
                <a href="usersignup.php" class="text-sm text-blue-600 hover:underline">
                    Don't have an account? Sign Up
                </a>
            </div>
        </div>
        
        <!-- Back to Account Selection Button -->
        <div class="mt-6 text-center">
            <a href="account.php" 
               class="inline-block bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors duration-200">
                ← Back to Account Selection
            </a>
        </div>
    </div>

    <script>
    // Log form interactions
    document.getElementById('forgot-password-form').addEventListener('submit', function() {
        fetch('log_activity.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=password_reset_request&identifier=' + encodeURIComponent(document.getElementById('email_username').value)
        });
    });
    </script>
</body>
</html>