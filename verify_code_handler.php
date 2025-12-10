<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'user_activity_logger.php';
require_once 'connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    
    logActivity('VERIFY_CODE_HANDLER_CALLED', 'Code attempt');
    
    // Check if email in session
    if (!isset($_SESSION['reset_email'])) {
        $_SESSION['verify_error'] = 'Session expired. Please start over.';
        header('Location: forgot_password.php');
        exit();
    }
    
    $email = $_SESSION['reset_email'];
    
    // Basic validation
    if (empty($code)) {
        $_SESSION['verify_error'] = 'Please enter the verification code.';
        header('Location: verify_code.php');
        exit();
    }
    
    if (!preg_match('/^\d{6}$/', $code)) {
        $_SESSION['verify_error'] = 'Verification code must be 6 digits.';
        header('Location: verify_code.php');
        exit();
    }
    
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
        
        // Verify code (plain text comparison, no hash)
        $stmt = $conn->prepare("
            SELECT id, username, name 
            FROM $tableToUse 
            WHERE email = ? 
            AND reset_code = ? 
            AND reset_code_expiry > NOW()
        ");
        
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $stmt->bind_param("ss", $email, $code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Check if code exists but is expired
            $checkStmt = $conn->prepare("SELECT reset_code_expiry FROM $tableToUse WHERE email = ? AND reset_code = ?");
            $checkStmt->bind_param("ss", $email, $code);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows === 0) {
                $_SESSION['verify_error'] = 'Invalid verification code. Please check and try again.';
                logActivity('VERIFY_CODE_INVALID', 'Email: ' . $email);
            } else {
                $_SESSION['verify_error'] = 'Verification code has expired. Please request a new one.';
                logActivity('VERIFY_CODE_EXPIRED', 'Email: ' . $email);
            }
            
            header('Location: verify_code.php');
            exit();
        }
        
        $user = $result->fetch_assoc();
        
        // Store user info in session for password reset
        $_SESSION['reset_user_id'] = $user['id'];
        $_SESSION['reset_user_verified'] = true;
        $_SESSION['reset_table'] = $tableToUse;
        
        logActivity('VERIFY_CODE_SUCCESS', 'Email: ' . $email . ' | User ID: ' . $user['id']);
        
        // Redirect to reset password page
        header('Location: reset_password.php');
        exit();
        
    } catch (Exception $e) {
        $_SESSION['verify_error'] = 'Error: ' . $e->getMessage();
        logActivity('VERIFY_CODE_EXCEPTION', $e->getMessage());
        error_log('Verify Code Error: ' . $e->getMessage());
        header('Location: verify_code.php');
        exit();
    }
}

// If not POST, redirect
header('Location: verify_code.php');
exit();
?>l