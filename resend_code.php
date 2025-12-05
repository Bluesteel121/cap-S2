<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'user_activity_logger.php';
require_once 'connect.php';
require_once 'email_config.php';
require_once 'send_email.php';

// Check if email in session
if (!isset($_SESSION['reset_email'])) {
    $_SESSION['forgot_error'] = 'Session expired. Please start over.';
    header('Location: forgot_password.php');
    exit();
}

$email = $_SESSION['reset_email'];

logActivity('RESEND_CODE_REQUESTED', 'Email: ' . $email);

try {
    // Find the correct table
    $tableToUse = 'accounts';
    $checkAccounts = $conn->query("SHOW TABLES LIKE 'accounts'");
    if ($checkAccounts->num_rows === 0) {
        $checkUsers = $conn->query("SHOW TABLES LIKE 'users'");
        if ($checkUsers->num_rows > 0) {
            $tableToUse = 'users';
        }
    }
    
    // Get user info
    $stmt = $conn->prepare("SELECT id, username, name FROM $tableToUse WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['verify_error'] = 'User not found.';
        header('Location: verify_code.php');
        exit();
    }
    
    $user = $result->fetch_assoc();
    $userId = $user['id'];
    $username = $user['username'] ?? $user['name'] ?? 'User';
    
    // Generate new 6-digit code
    $resetCode = sprintf("%06d", mt_rand(0, 999999));
    $expiryTime = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Update database
    $updateStmt = $conn->prepare("UPDATE $tableToUse SET reset_code = ?, reset_code_expiry = ? WHERE id = ?");
    $updateStmt->bind_param("ssi", $resetCode, $expiryTime, $userId);
    
    if (!$updateStmt->execute()) {
        throw new Exception('Failed to update reset code');
    }
    
    // ALWAYS set for localhost testing
    $_SESSION['reset_code_for_testing'] = $resetCode;
    
    // Send email
    $emailSent = sendPasswordResetCodeEmail($email, $username, $resetCode);
    
    if ($emailSent) {
        $_SESSION['forgot_success'] = 'A new verification code has been sent to your email (also shown below for localhost testing).';
        logActivity('RESEND_CODE_SUCCESS', 'Email: ' . $email);
    } else {
        // For localhost testing
        $_SESSION['forgot_success'] = 'New verification code generated (see below).';
    }
    
    // Always log for testing
    error_log("==============================================");
    error_log("RESEND CODE FOR: $email | CODE: $resetCode");
    error_log("==============================================");
    
} catch (Exception $e) {
    $_SESSION['verify_error'] = 'Error resending code: ' . $e->getMessage();
    logActivity('RESEND_CODE_ERROR', $e->getMessage());
    error_log('Resend Code Error: ' . $e->getMessage());
}

header('Location: verify_code.php');
exit();
?>