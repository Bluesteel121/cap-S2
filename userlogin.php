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
                 "</div>";
            unset($_SESSION['login_error']);
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

        <!-- Sign-up Form (initially hidden) -->
        <div id="signup-section" class="hidden">
            <h2 class="text-2xl font-bold text-center mb-4">User Sign Up</h2>
            <form id="signup-form" method="POST" action="user_signup_handler.php" autocomplete="off" novalidate>
                <div class="mb-4">
                    <label for="signupUsername" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" id="signupUsername" name="username" placeholder="Choose a username" class="border w-full px-4 py-2 rounded-lg focus:ring-green-500 focus:border-green-500" required>
                </div>

                <div class="mb-4">
                    <label for="fullname" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" id="fullname" name="fullname" placeholder="Enter your full name" class="border w-full px-4 py-2 rounded-lg focus:ring-green-500 focus:border-green-500" required>
                </div>

                <div class="mb-4">
                    <label for="signupPassword" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="signupPassword" placeholder="Create a password" class="border w-full px-4 py-2 rounded-lg pr-10 focus:ring-green-500 focus:border-green-500" required>
                        <button type="button" onclick="togglePassword('signupPassword', 'signupToggleIcon')" class="absolute right-3 top-3 text-gray-500">
                            <i class="far fa-eye" id="signupToggleIcon"></i>
                        </button>
                    </div>
                </div>

                 <div class="mb-4">
                    <label for="contactNumber" class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                    <input type="text" id="contactNumber" name="contact_number" placeholder="Enter your contact number" class="border w-full px-4 py-2 rounded-lg focus:ring-green-500 focus:border-green-500" required>
                </div>

                <button type="submit" class="bg-green-500 text-white w-full py-2 mt-4 rounded-lg hover:bg-green-700 transition-colors duration-200">
                    Sign Up
                </button>
            </form>
        </div>

        <!-- Toggle Button -->
        <div class="mt-4 text-center">
            <button id="toggle-button" class="text-sm text-green-600 hover:underline" onclick="toggleForm()">
                Don't have an account? Sign Up
            </button>
        </div>

        <div class="mt-4 text-center">
             <button id="toggle-button-back" class="text-sm text-green-600 hover:underline hidden" onclick="toggleFormBack()">
                Already have an account? Login
            </button>

            <!-- Centered Back Button -->
            <div class="mt-6 text-center">
                <a href="account.php" class="inline-block bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors duration-200">
                    ← Back to Account Selection
                </a>
            </div>
        </div>
    </div>

    <script>
    function toggleForm() {
        const loginSection = document.getElementById('login-section');
        const signupSection = document.getElementById('signup-section');
        const toggleButton = document.getElementById('toggle-button');
        const toggleButtonBack = document.getElementById('toggle-button-back');

        loginSection.classList.add('hidden');
        signupSection.classList.remove('hidden');
        toggleButton.classList.add('hidden');
        toggleButtonBack.classList.remove('hidden');
    }
      function toggleFormBack() {
        const loginSection = document.getElementById('login-section');
        const signupSection = document.getElementById('signup-section');
         const toggleButton = document.getElementById('toggle-button');
        const toggleButtonBack = document.getElementById('toggle-button-back');
        loginSection.classList.remove('hidden');
        signupSection.classList.add('hidden');
        toggleButton.classList.remove('hidden');
        toggleButtonBack.classList.add('hidden');
    }
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