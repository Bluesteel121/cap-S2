<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check if user_activity_logger exists
if (file_exists('user_activity_logger.php')) {
    require_once 'user_activity_logger.php';
    if (function_exists('logPageView')) {
        logPageView('Verify Code Page');
    }
}

// Redirect if no email in session
if (!isset($_SESSION['reset_email'])) {
    $_SESSION['forgot_error'] = 'Please start the password reset process again.';
    header('Location: forgot_password.php');
    exit();
}

$email = $_SESSION['reset_email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code</title>
    <link rel="icon" href="Images/Favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-screen flex relative bg-gray-100">
    <div class="m-auto bg-white p-8 rounded-lg shadow-lg w-96">
        <img src="Images/logo.png" alt="Logo" class="mx-auto h-16 mb-4">
        
        <?php
        // Display error messages
        if (isset($_SESSION['verify_error'])) {
            echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>" . 
                 htmlspecialchars($_SESSION['verify_error']) . 
                 "</div>";
            unset($_SESSION['verify_error']);
        }
        
        // Display success messages
        if (isset($_SESSION['forgot_success'])) {
            echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>" . 
                 htmlspecialchars($_SESSION['forgot_success']) . 
                 "</div>";
            unset($_SESSION['forgot_success']);
        }
        
        // LOCALHOST ONLY: Display the verification code for easy testing
        if (isset($_SESSION['reset_code_for_testing'])) {
            echo "<div class='bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4' role='alert'>";
            echo "<strong>🔧 Localhost Testing - Verification Code:</strong><br>";
            echo "<div class='mt-2 p-4 bg-white rounded border border-blue-300 text-center' style='font-size: 24px; font-weight: bold; letter-spacing: 3px;'>";
            echo htmlspecialchars($_SESSION['reset_code_for_testing']);
            echo "</div>";
            echo "</div>";
            unset($_SESSION['reset_code_for_testing']);
        }
        ?>

        <form action="verify_code_handler.php" method="POST" id="verifyForm">
            <h2 class="text-2xl font-bold text-center mb-6">Verify Code</h2>
            <p class="text-gray-600 text-center mb-6 text-sm">
                Enter the 6-digit verification code sent to:<br>
                <strong><?php echo htmlspecialchars($email); ?></strong>
            </p>
            
            <div class="mb-4">
                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Verification Code</label>
                <input 
                    type="text" 
                    id="code" 
                    name="code" 
                    placeholder="Enter 6-digit code" 
                    maxlength="6"
                    pattern="[0-9]{6}"
                    class="border w-full px-4 py-2 rounded-lg text-center text-2xl tracking-widest focus:ring-green-500 focus:border-green-500" 
                    required>
                <small class="text-gray-500 mt-1 block text-center">Code expires in 15 minutes</small>
            </div>

            <button type="submit" class="bg-green-500 text-white w-full py-2 mt-4 rounded-lg hover:bg-green-700 transition-colors duration-200">
                Verify Code
            </button>
        </form>

        <div class="mt-6 text-center space-y-4">
            <div>
                <button onclick="resendCode()" class="text-sm text-blue-600 hover:underline">
                    Resend Code
                </button>
            </div>
            <div>
                <a href="forgot_password.php" class="text-sm text-gray-600 hover:underline">
                    Use Different Email
                </a>
            </div>
            <div>
                <a href="userlogin.php" class="text-sm text-green-600 hover:underline">Back to Login</a>
            </div>
        </div>
    </div>

    <script>
        // Auto-format code input (numbers only)
        document.getElementById('code').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        function resendCode() {
            if (confirm('Resend verification code to your email?')) {
                window.location.href = 'resend_code.php';
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

        document.getElementById('verifyForm').addEventListener('submit', function() {
            const code = document.getElementById('code').value;
            if (typeof logActivityClient === 'function') {
                logActivityClient('VERIFY_CODE_ATTEMPT', 'Code length: ' + code.length);
            }
        });
    </script>
</body>
</html>