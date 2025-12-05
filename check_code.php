<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// echo date("Y-m-d H:i:s");
// session_start();
// require_once 'connect.php';

// $email = 'raynesmark721@gmail.com'; // Change this if needed

// echo "<!DOCTYPE html>
// <html>
// <head>
//     <title>Code Status Checker</title>
//     <script src='https://cdn.tailwindcss.com'></script>
// </head>
// <body class='bg-gray-100 p-8'>
//     <div class='max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-8'>
//         <h1 class='text-3xl font-bold text-green-700 mb-6'>🔍 Password Reset Code Status</h1>";

// // Check if email is in session
// echo "<div class='mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200'>";
// echo "<h2 class='font-bold text-lg mb-2'>Session Information:</h2>";
// if (isset($_SESSION['reset_email'])) {
//     echo "<p>✅ Email in session: <strong>" . htmlspecialchars($_SESSION['reset_email']) . "</strong></p>";
// } else {
//     echo "<p>❌ No email in session</p>";
// }
// if (isset($_SESSION['reset_code_for_testing'])) {
//     echo "<p>✅ Test code in session: <strong style='font-size: 20px; color: blue;'>" . htmlspecialchars($_SESSION['reset_code_for_testing']) . "</strong></p>";
// } else {
//     echo "<p>⚠️ No test code in session</p>";
// }
// echo "</div>";

// // Find table
// $tableToUse = 'accounts';
// $checkAccounts = $conn->query("SHOW TABLES LIKE 'accounts'");
// if ($checkAccounts->num_rows === 0) {
//     $checkUsers = $conn->query("SHOW TABLES LIKE 'users'");
//     if ($checkUsers->num_rows > 0) {
//         $tableToUse = 'users';
//     }
// }

// echo "<div class='mb-6 p-4 bg-gray-50 rounded-lg'>";
// echo "<p><strong>Using table:</strong> $tableToUse</p>";
// echo "</div>";

// // Query database
// $sql = "SELECT reset_code, reset_code_expiry FROM $tableToUse WHERE email = ?";
// $stmt = $conn->prepare($sql);
// $stmt->bind_param("s", $email);
// $stmt->execute();
// $result = $stmt->get_result();

// if ($row = $result->fetch_assoc()) {
//     $currentTime = date('Y-m-d H:i:s');
//     $expiryTime = $row['reset_code_expiry'];
//     $code = $row['reset_code'];
    
//     $isExpired = strtotime($expiryTime) <= strtotime($currentTime);
    
//     echo "<div class='mb-6 p-6 bg-green-50 rounded-lg border-2 border-green-300'>";
//     echo "<h2 class='font-bold text-xl mb-4 text-green-800'>📊 Database Information:</h2>";
    
//     echo "<div class='grid grid-cols-2 gap-4 mb-4'>";
//     echo "<div class='p-4 bg-white rounded'>";
//     echo "<p class='text-sm text-gray-600 mb-1'>Reset Code:</p>";
//     echo "<p class='text-3xl font-bold text-blue-600' style='letter-spacing: 3px;'>$code</p>";
//     echo "</div>";
    
//     echo "<div class='p-4 bg-white rounded'>";
//     echo "<p class='text-sm text-gray-600 mb-1'>Status:</p>";
//     if ($isExpired) {
//         echo "<p class='text-2xl font-bold text-red-600'>❌ EXPIRED</p>";
//     } else {
//         echo "<p class='text-2xl font-bold text-green-600'>✅ VALID</p>";
//     }
//     echo "</div>";
//     echo "</div>";
    
//     echo "<div class='bg-white p-4 rounded'>";
//     echo "<table class='w-full'>";
//     echo "<tr><td class='font-semibold py-2'>Current Time:</td><td>$currentTime</td></tr>";
//     echo "<tr><td class='font-semibold py-2'>Expiry Time:</td><td>$expiryTime</td></tr>";
    
//     if (!$isExpired) {
//         $timeLeft = strtotime($expiryTime) - strtotime($currentTime);
//         $minutesLeft = floor($timeLeft / 60);
//         $secondsLeft = $timeLeft % 60;
//         echo "<tr><td class='font-semibold py-2'>Time Remaining:</td><td class='text-green-600 font-bold'>{$minutesLeft}m {$secondsLeft}s</td></tr>";
//     } else {
//         $timeExpired = strtotime($currentTime) - strtotime($expiryTime);
//         $minutesExpired = floor($timeExpired / 60);
//         echo "<tr><td class='font-semibold py-2'>Expired Since:</td><td class='text-red-600 font-bold'>{$minutesExpired} minutes ago</td></tr>";
//     }
//     echo "</table>";
//     echo "</div>";
//     echo "</div>";
    
//     // Action buttons
//     echo "<div class='mt-6 space-y-3'>";
//     if ($isExpired) {
//         echo "<a href='resend_code.php' class='block text-center bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 font-bold'>
//                 🔄 Generate New Code
//               </a>";
//     } else {
//         echo "<a href='verify_code.php' class='block text-center bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-600 font-bold'>
//                 ✅ Go to Verify Page
//               </a>";
//     }
//     echo "<a href='forgot_password.php' class='block text-center bg-gray-300 text-gray-800 px-6 py-3 rounded-lg hover:bg-gray-400'>
//             ← Start Over
//           </a>";
//     echo "</div>";
    
// } else {
//     echo "<div class='p-6 bg-red-50 rounded-lg border-2 border-red-300'>";
//     echo "<h2 class='font-bold text-xl mb-2 text-red-800'>❌ No Reset Code Found</h2>";
//     echo "<p>No reset code exists for email: <strong>$email</strong></p>";
//     echo "<a href='forgot_password.php' class='inline-block mt-4 bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600'>
//             Request New Code
//           </a>";
//     echo "</div>";
// }

// // MySQL timezone check
// echo "<div class='mt-6 p-4 bg-yellow-50 rounded-lg border border-yellow-200'>";
// echo "<h3 class='font-bold mb-2'>⚙️ System Configuration:</h3>";
// $mysqlTime = $conn->query("SELECT NOW() as mysql_time")->fetch_assoc()['mysql_time'];
// echo "<p><strong>PHP Time:</strong> " . date('Y-m-d H:i:s') . " (" . date_default_timezone_get() . ")</p>";
// echo "<p><strong>MySQL Time:</strong> $mysqlTime</p>";

// $timeDiff = strtotime(date('Y-m-d H:i:s')) - strtotime($mysqlTime);
// if (abs($timeDiff) > 60) {
//     echo "<p class='text-red-600 font-bold mt-2'>⚠️ WARNING: Time difference detected! PHP and MySQL times are not synchronized.</p>";
// } else {
//     echo "<p class='text-green-600 mt-2'>✅ PHP and MySQL times are synchronized.</p>";
// }
// echo "</div>";

// echo "</div>
// </body>
// </html>";
?>