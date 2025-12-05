<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Load activity logger if available
if (file_exists('user_activity_logger.php')) {
    require_once 'user_activity_logger.php';
}

require_once 'connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate session
    if (!isset($_SESSION['reset_user_verified']) || !isset($_SESSION['reset_user_id'])) {
        if (function_exists('logActivity')) {
            logActivity('RESET_PASSWORD_SESSION_INVALID', 'Missing session data');
        }
        $_SESSION['reset_error'] = 'Invalid session. Please start the password reset process again.';
        header('Location: forgot_password.php');
        exit();
    }
    
    $userId = $_SESSION['reset_user_id'];
    $tableToUse = $_SESSION['reset_table'] ?? 'accounts';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (function_exists('logActivity')) {
        logActivity('RESET_PASSWORD_HANDLER_CALLED', 'User ID: ' . $userId);
    }
    
    // Validation
    if (empty($password) || empty($confirmPassword)) {
        $_SESSION['reset_error'] = 'Please fill in all fields.';
        if (function_exists('logActivity')) {
            logActivity('RESET_PASSWORD_ERROR', 'Empty password fields');
        }
        header('Location: reset_password.php');
        exit();
    }
    
    if ($password !== $confirmPassword) {
        $_SESSION['reset_error'] = 'Passwords do not match.';
        if (function_exists('logActivity')) {
            logActivity('RESET_PASSWORD_ERROR', 'Passwords do not match');
        }
        header('Location: reset_password.php');
        exit();
    }
    
    try {
        // Update password WITHOUT hashing (plain text)
        $stmt = $conn->prepare("
            UPDATE $tableToUse 
            SET password = ?, reset_code = NULL, reset_code_expiry = NULL 
            WHERE id = ?
        ");
        
        if (!$stmt) {
            throw new Exception('Database prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param("si", $password, $userId);
        
        if (!$stmt->execute()) {
            throw new Exception('Database update failed: ' . $stmt->error);
        }
        
        if (function_exists('logActivity')) {
            logActivity('RESET_PASSWORD_SUCCESS', 'Password reset for user ID: ' . $userId);
        }
        
        // Clear all reset-related session variables
        unset($_SESSION['reset_user_id']);
        unset($_SESSION['reset_user_verified']);
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_table']);
        
        $_SESSION['login_success'] = 'Your password has been reset successfully! Please login with your new password.';
        header('Location: userlogin.php');
        exit();
        
    } catch (Exception $e) {
        $_SESSION['reset_error'] = 'An error occurred while resetting your password: ' . $e->getMessage();
        if (function_exists('logActivity')) {
            logActivity('RESET_PASSWORD_EXCEPTION', $e->getMessage());
        }
        error_log('Reset Password Error: ' . $e->getMessage());
        header('Location: reset_password.php');
        exit();
    }
} else {
    // Not a POST request
    header('Location: forgot_password.php');
    exit();
}
?>