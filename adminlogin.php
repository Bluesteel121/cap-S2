<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin & Reviewer Login - CNLRRS</title>
    <link rel="icon" href="Images/Favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Add Font Awesome for eye icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .role-indicator {
            background: linear-gradient(135deg, #115D5B, #059669);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
        }
        .form-input:focus {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(17, 93, 91, 0.15);
        }
    </style>
</head>
<body class="h-screen flex relative" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);">
    <!-- Login Container -->
    <div class="m-auto login-container p-8 w-96">
        <!-- Logo -->
        <div class="flex justify-center mb-6">
            <img src="Images/logo.png" alt="CNLRRS Logo" class="h-16">
        </div>

        <!-- Role Indicator -->
        <div class="text-center mb-6">
            <div class="role-indicator inline-block">
                <i class="fas fa-shield-alt mr-2"></i>
                Administrative Access
            </div>
        </div>

        <!-- Error Messages -->
        <?php
        session_start();
        if (isset($_SESSION['login_error'])) {
            echo "<div class='bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg relative mb-4' role='alert'>" .
                 "<div class='flex items-center'>" .
                 "<i class='fas fa-exclamation-circle mr-2'></i>" .
                 "<span>" . htmlspecialchars($_SESSION['login_error']) . "</span>" .
                 "</div>" .
                 "</div>";
            unset($_SESSION['login_error']);
        }
        ?>

        <!-- Login Form -->
        <div id="login-section">
            <h2 class="text-2xl font-bold text-center mb-2 text-gray-800">Welcome Back</h2>
            <p class="text-center text-gray-600 mb-6">Sign in to your admin or reviewer account</p>
            
            <form id="login-form" method="POST" action="admin_login_handler.php" autocomplete="off" novalidate>
                <div class="mb-4">
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user mr-2 text-gray-500"></i>Username
                    </label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           placeholder="Enter your username" 
                           class="form-input border border-gray-300 w-full px-4 py-3 rounded-lg focus:ring-2 focus:ring-[#115D5B] focus:border-[#115D5B] transition-all duration-200" 
                           required>
                </div>

                <div class="mb-6">
                    <label for="loginPassword" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2 text-gray-500"></i>Password
                    </label>
                    <div class="relative">
                        <input type="password" 
                               name="password" 
                               id="loginPassword" 
                               placeholder="Enter your password" 
                               class="form-input border border-gray-300 w-full px-4 py-3 rounded-lg pr-12 focus:ring-2 focus:ring-[#115D5B] focus:border-[#115D5B] transition-all duration-200">
                        <button type="button" 
                                onclick="togglePassword('loginPassword', 'loginToggleIcon')" 
                                class="absolute right-3 top-3 text-gray-500 hover:text-gray-700 transition-colors">
                            <i class="far fa-eye" id="loginToggleIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="bg-[#115D5B] hover:bg-[#0d4a47] text-white w-full py-3 rounded-lg font-semibold transition-all duration-200 transform hover:scale-105 hover:shadow-lg">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Sign In
                </button>
            </form>

            <!-- Back Button -->
            <div class="mt-6 text-center">
                <a href="account.php" class="inline-flex items-center bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-arrow-left mr-2 transform group-hover:-translate-x-1 transition-transform duration-200"></i>
                    Back to Account Selection
                </a>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Add subtle animation to form elements
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('transform', 'scale-102');
                });
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('transform', 'scale-102');
                });
            });
        });

        // Form submission loading state
        document.getElementById('login-form').addEventListener('submit', function(e) {
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Signing In...';
            submitButton.disabled = true;
            
            // Re-enable button after 5 seconds in case of network issues
            setTimeout(() => {
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            }, 5000);
        });
    </script>
</body>
</html>