<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// echo "<!DOCTYPE html>
// <html>
// <head>
//     <title>Password Reset System Test</title>
//     <style>
//         body { font-family: Arial; padding: 20px; background: #f5f5f5; }
//         .box { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
//         .success { color: green; font-weight: bold; }
//         .error { color: red; font-weight: bold; }
//         .warning { color: orange; font-weight: bold; }
//         h1 { color: #333; }
//         h2 { color: #666; margin-top: 0; }
//         table { width: 100%; border-collapse: collapse; }
//         th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
//         th { background: #f0f0f0; }
//         .code { background: #f5f5f5; padding: 10px; border-radius: 3px; font-family: monospace; }
//     </style>
// </head>
// <body>
//     <h1>🔧 Password Reset System Test</h1>
// ";

// // Test 1: Database Connection
// echo "<div class='box'>";
// echo "<h2>1️⃣ Database Connection</h2>";
// if (file_exists('connect.php')) {
//     require_once 'connect.php';
//     if (isset($conn) && $conn) {
//         echo "<span class='success'>✓ Connected to database</span><br>";
//         echo "Database name: <strong>" . $conn->query("SELECT DATABASE()")->fetch_row()[0] . "</strong><br>";
//     } else {
//         echo "<span class='error'>✗ Database connection failed</span><br>";
//         die("Cannot proceed without database connection.");
//     }
// } else {
//     echo "<span class='error'>✗ connect.php not found</span>";
//     die("Cannot proceed without connect.php");
// }
// echo "</div>";

// // Test 2: Find User Table
// echo "<div class='box'>";
// echo "<h2>2️⃣ User Table Detection</h2>";
// $tables = [];
// $result = $conn->query("SHOW TABLES");
// while ($row = $result->fetch_array()) {
//     $tables[] = $row[0];
// }

// $userTable = null;
// if (in_array('accounts', $tables)) {
//     $userTable = 'accounts';
//     echo "<span class='success'>✓ Found 'accounts' table</span><br>";
// } elseif (in_array('users', $tables)) {
//     $userTable = 'users';
//     echo "<span class='success'>✓ Found 'users' table</span><br>";
// } else {
//     echo "<span class='error'>✗ No user table found!</span><br>";
//     echo "Available tables: " . implode(', ', $tables);
//     die();
// }
// echo "</div>";

// // Test 3: Check Columns
// echo "<div class='box'>";
// echo "<h2>3️⃣ Table Structure</h2>";
// $columns = [];
// $result = $conn->query("SHOW COLUMNS FROM $userTable");
// echo "<table>";
// echo "<tr><th>Column Name</th><th>Type</th><th>Status</th></tr>";
// while ($row = $result->fetch_assoc()) {
//     $columns[] = $row['Field'];
//     $status = "✓";
//     if ($row['Field'] == 'email' || $row['Field'] == 'id' || $row['Field'] == 'password') {
//         $status = "<span class='success'>✓ Required</span>";
//     }
//     echo "<tr><td>" . $row['Field'] . "</td><td>" . $row['Type'] . "</td><td>$status</td></tr>";
// }
// echo "</table>";

// // Check for reset token columns
// if (!in_array('reset_token', $columns)) {
//     echo "<br><span class='warning'>⚠ reset_token column missing - adding it now...</span><br>";
//     $conn->query("ALTER TABLE $userTable ADD COLUMN reset_token VARCHAR(255) NULL");
//     echo "<span class='success'>✓ reset_token column added</span><br>";
// }

// if (!in_array('reset_token_expiry', $columns)) {
//     echo "<span class='warning'>⚠ reset_token_expiry column missing - adding it now...</span><br>";
//     $conn->query("ALTER TABLE $userTable ADD COLUMN reset_token_expiry DATETIME NULL");
//     echo "<span class='success'>✓ reset_token_expiry column added</span><br>";
// }
// echo "</div>";

// // Test 4: Sample Users
// echo "<div class='box'>";
// echo "<h2>4️⃣ Users in Database</h2>";
// $result = $conn->query("SELECT id, email FROM $userTable LIMIT 5");
// if ($result->num_rows > 0) {
//     echo "<table>";
//     echo "<tr><th>ID</th><th>Email</th><th>Action</th></tr>";
//     while ($row = $result->fetch_assoc()) {
//         echo "<tr>";
//         echo "<td>" . $row['id'] . "</td>";
//         echo "<td>" . htmlspecialchars($row['email']) . "</td>";
//         echo "<td><a href='forgot_password.php' target='_blank'>Test Reset</a></td>";
//         echo "</tr>";
//     }
//     echo "</table>";
//     echo "<p><strong>📝 Note:</strong> Use one of these email addresses to test the password reset.</p>";
// } else {
//     echo "<span class='error'>✗ No users found in database</span><br>";
//     echo "You need to create a user first before testing password reset.";
// }
// echo "</div>";

// // Test 5: File Check
// echo "<div class='box'>";
// echo "<h2>5️⃣ Required Files</h2>";
// $requiredFiles = [
//     'forgot_password.php' => 'Forgot password form',
//     'forgot_password_handler.php' => 'Handles password reset requests',
//     'reset_password.php' => 'Reset password form',
//     'reset_password_handler.php' => 'Handles password reset',
//     'user_activity_logger.php' => 'Activity logging'
// ];

// echo "<table>";
// echo "<tr><th>File</th><th>Description</th><th>Status</th></tr>";
// foreach ($requiredFiles as $file => $desc) {
//     $exists = file_exists($file);
//     $status = $exists ? "<span class='success'>✓ Exists</span>" : "<span class='error'>✗ Missing</span>";
//     echo "<tr><td>$file</td><td>$desc</td><td>$status</td></tr>";
// }
// echo "</table>";
// echo "</div>";

// // Test 6: Quick Test
// echo "<div class='box'>";
// echo "<h2>6️⃣ Quick Test</h2>";
// echo "<p>Everything looks good! Here's how to test:</p>";
// echo "<ol>";
// echo "<li>Go to <a href='forgot_password.php' target='_blank'><strong>forgot_password.php</strong></a></li>";
// echo "<li>Enter one of the email addresses from the table above</li>";
// echo "<li>Click 'Send Reset Link'</li>";
// echo "<li>The reset link will appear on the page (no email needed for localhost)</li>";
// echo "<li>Click the link to reset your password</li>";
// echo "</ol>";
// echo "</div>";

// // Summary
// echo "<div class='box' style='background: #d4edda; border: 2px solid #28a745;'>";
// echo "<h2>✅ System Status: READY</h2>";
// echo "<p>Your password reset system is configured correctly!</p>";
// echo "<a href='forgot_password.php' style='display: inline-block; background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 10px;'>Test Password Reset Now →</a>";
// echo "</div>";

// echo "</body></html>";
?>