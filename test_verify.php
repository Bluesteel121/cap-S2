<!-- <?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// echo "<h1>Testing Verify Code Page</h1>";
// echo "<hr>";

// session_start();

// // Test 1: Session
// echo "<h2>1. Session Test</h2>";
// if (isset($_SESSION['reset_email'])) {
//     echo "✅ Reset email in session: " . htmlspecialchars($_SESSION['reset_email']) . "<br>";
// } else {
//     echo "❌ No reset email in session<br>";
// }

// if (isset($_SESSION['reset_code_for_testing'])) {
//     echo "✅ Test code: " . htmlspecialchars($_SESSION['reset_code_for_testing']) . "<br>";
// } else {
//     echo "⚠️ No test code in session<br>";
// }

// echo "<hr>";

// // Test 2: Files
// echo "<h2>2. Required Files Test</h2>";
// $files = [
//     'user_activity_logger.php',
//     'connect.php',
//     'verify_code_handler.php',
//     'Images/logo.png'
// ];

// foreach ($files as $file) {
//     if (file_exists($file)) {
//         echo "✅ $file exists<br>";
//     } else {
//         echo "❌ $file NOT FOUND<br>";
//     }
// }

// echo "<hr>";

// // Test 3: Try to load user_activity_logger
// echo "<h2>3. Loading user_activity_logger.php</h2>";
// if (file_exists('user_activity_logger.php')) {
//     try {
//         require_once 'user_activity_logger.php';
//         echo "✅ user_activity_logger.php loaded successfully<br>";
        
//         if (function_exists('logPageView')) {
//             echo "✅ logPageView() function exists<br>";
//         } else {
//             echo "⚠️ logPageView() function NOT found<br>";
//         }
//     } catch (Exception $e) {
//         echo "❌ Error loading user_activity_logger.php: " . $e->getMessage() . "<br>";
//     }
// } else {
//     echo "❌ user_activity_logger.php does not exist<br>";
// }

// echo "<hr>";
// echo "<h2>4. Session Data</h2>";
// echo "<pre>";
// print_r($_SESSION);
// echo "</pre>";

// echo "<hr>";
// echo "<a href='forgot_password.php'>← Back to Forgot Password</a><br>";
// echo "<a href='verify_code.php'>Try verify_code.php →</a>";
?> -->