<?php
// Enable error display for localhost debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'user_activity_logger.php';
require_once 'connect.php';
require_once 'email_config.php';
require_once 'send_email.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    logActivity('FORGOT_PASSWORD_HANDLER_CALLED', 'Email: ' . $email);
    
    // Basic validation
    if (empty($email)) {
        $_SESSION['forgot_error'] = 'Please provide an email address.';
        header('Location: forgot_password.php');
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['forgot_error'] = 'Please provide a valid email address.';
        header('Location: forgot_password.php');
        exit();
    }
    
    try {
        // Find the correct table name
        $tableToUse = 'accounts';
        $checkAccounts = $conn->query("SHOW TABLES LIKE 'accounts'");
        if ($checkAccounts->num_rows === 0) {
            $checkUsers = $conn->query("SHOW TABLES LIKE 'users'");
            if ($checkUsers->num_rows > 0) {
                $tableToUse = 'users';
            } else {
                throw new Exception("No 'accounts' or 'users' table found in database.");
            }
        }
        
        // Check and add missing columns if needed
        $columnsCheck = $conn->query("SHOW COLUMNS FROM $tableToUse LIKE 'reset_code'");
        if ($columnsCheck->num_rows === 0) {
            $conn->query("ALTER TABLE $tableToUse ADD COLUMN reset_code VARCHAR(10) NULL");
            $conn->query("ALTER TABLE $tableToUse ADD COLUMN reset_code_expiry DATETIME NULL");
        }
        
        // Get table structure to find username field
        $columns = [];
        $result = $conn->query("SHOW COLUMNS FROM $tableToUse");
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        $usernameField = 'email'; // default fallback
        if (in_array('username', $columns)) {
            $usernameField = 'username';
        } elseif (in_array('name', $columns)) {
            $usernameField = 'name';
        }
        
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, $usernameField as username, email FROM $tableToUse WHERE email = ?");
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // For security, show success message even if user doesn't exist
            $_SESSION['forgot_success'] = 'If an account exists with this email, a verification code has been sent.';
            logActivity('FORGOT_PASSWORD_NO_USER', 'Email not found: ' . $email);
            header('Location: forgot_password.php');
            exit();
        }
        
        $user = $result->fetch_assoc();
        $userId = $user['id'];
        $username = $user['username'] ?? 'User';
        
        // Generate 6-digit verification code (no hashing)
        $resetCode = sprintf("%06d", mt_rand(0, 999999));
        
        // Extended expiry for testing (15 minutes)
        $expiryTime = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        // Save code to database (plain text, no hash)
        $updateStmt = $conn->prepare("UPDATE $tableToUse SET reset_code = ?, reset_code_expiry = ? WHERE id = ?");
        if (!$updateStmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $updateStmt->bind_param("ssi", $resetCode, $expiryTime, $userId);
        
        if (!$updateStmt->execute()) {
            throw new Exception("Failed to save reset code: " . $updateStmt->error);
        }
        
        // For localhost testing, ALWAYS show the code
        $_SESSION['reset_code_for_testing'] = $resetCode;
        $_SESSION['reset_email'] = $email;
        
        // Send verification code via email
        $emailSent = sendPasswordResetCodeEmail($email, $username, $resetCode);
        
        if ($emailSent) {
            $_SESSION['forgot_success'] = 'A 6-digit verification code has been sent to your email. Please check your inbox (or see code below for localhost testing).';
            logActivity('FORGOT_PASSWORD_SUCCESS', 'Verification code sent to: ' . $email);
        } else {
            // For localhost testing, show the code
            $_SESSION['forgot_success'] = 'Verification code generated. (Email sending may have failed - check code below)';
        }
        
        // Always log for localhost testing
        error_log("==============================================");
        error_log("VERIFICATION CODE FOR: $email");
        error_log("CODE: $resetCode");
        error_log("=======================================================================================");
        
    } catch (Exception $e) {
        $_SESSION['forgot_error'] = 'Error: ' . $e->getMessage();
        logActivity('FORGOT_PASSWORD_EXCEPTION', $e->getMessage());
        error_log('Forgot Password Error: ' . $e->getMessage());
    }
    
    header('Location: verify_code.php');
    exit();
}

// If not POST, redirect
header('Location: forgot_password.php');
exit();
?>