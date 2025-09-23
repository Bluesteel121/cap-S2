<?php
session_start();

require_once 'connect.php';
require_once 'email_config.php';
require_once 'includes/debug.php';

// Function to validate password strength
function validatePasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter.";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter.";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number.";
    }
    
    return $errors;
}

// Function to send password change confirmation email
function sendPasswordChangeConfirmation($userEmail, $userName) {
    $subject = "Password Changed Successfully - CNLRRS";
    
    $body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='background-color: #f8f9fa; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
            <h2 style='color: #28a745; margin: 0;'>Password Changed Successfully</h2>
        </div>
        
        <div style='padding: 30px; background-color: white; border: 1px solid #dee2e6;'>
            <p>Hello <strong>" . htmlspecialchars($userName) . "</strong>,</p>
            
            <p>Your password for your CNLRRS E-Library account has been successfully changed.</p>
            
            <div style='background-color: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745; margin: 20px 0;'>
                <p style='margin: 0; color: #155724;'><strong>Security Details:</strong></p>
                <ul style='margin: 5px 0 0 20px; color: #155724;'>
                    <li>Date: " . date('F j, Y \a\t g:i A') . "</li>
                    <li>IP Address: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "</li>
                    <li>Browser: " . (isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 100) : 'Unknown') . "</li>
                </ul>
            </div>
            
            <div style='background-color: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107; margin: 20px 0;'>
                <p style='margin: 0; color: #856404;'><strong>Important:</strong></p>
                <p style='margin: 5px 0 0 0; color: #856404;'>If you did not make this change, please contact us immediately at <strong>dacnlrrs@gmail.com</strong> or log into your account and change your password again.</p>
            </div>
            
            <p>You can now log in to your account using your new password.</p>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/userlogin.php' 
                   style='background-color: #28a745; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>
                    Login to Your Account
                </a>
            </div>
        </div>
        
        <div style='background-color: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; color: #6c757d; font-size: 14px;'>
            <p style='margin: 0;'>This email was sent from CNLRRS E-Library System</p>
            <p style='margin: 5px 0 0 0;'>If you need help, contact us at: <strong>dacnlrrs@gmail.com</strong></p>
        </div>
    </div>";
    
    return EmailService::sendEmail($userEmail, $subject, $body);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($token) || empty($newPassword) || empty($confirmPassword)) {
        $_SESSION['password_reset_error'] = "All fields are required.";
        header("Location: reset_password.php?token=" . urlencode($token));
        exit();
    }
    
    if ($newPassword !== $confirmPassword) {
        $_SESSION['password_reset_error'] = "Passwords do not match.";
        header("Location: reset_password.php?token=" . urlencode($token));
        exit();
    }
    
    // Validate password strength
    $passwordErrors = validatePasswordStrength($newPassword);
    if (!empty($passwordErrors)) {
        $_SESSION['password_reset_error'] = implode(" ", $passwordErrors);
        header("Location: reset_password.php?token=" . urlencode($token));
        exit();
    }
    
    // Verify token is valid and not expired
    $sql = "SELECT prt.id, prt.user_id, prt.email, prt.used, a.name, a.username 
            FROM password_reset_tokens prt 
            JOIN accounts a ON prt.user_id = a.id 
            WHERE prt.token = ? AND prt.expires_at > NOW() AND prt.used = FALSE";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($tokenData = $result->fetch_assoc()) {
            $userId = $tokenData['user_id'];
            $userEmail = $tokenData['email'];
            $userName = $tokenData['name'];
            $tokenId = $tokenData['id'];
            
            // Update user password (storing as plain text as per your current system)
            // Note: In production, you should hash passwords
            $updateSql = "UPDATE accounts SET password = ?, updated_at = NOW() WHERE id = ?";
            
            if ($updateStmt = $conn->prepare($updateSql)) {
                $updateStmt->bind_param("si", $newPassword, $userId);
                
                if ($updateStmt->execute()) {
                    // Mark token as used
                    $markUsedSql = "UPDATE password_reset_tokens SET used = TRUE WHERE id = ?";
                    $markUsedStmt = $conn->prepare($markUsedSql);
                    $markUsedStmt->bind_param("i", $tokenId);
                    $markUsedStmt->execute();
                    $markUsedStmt->close();
                    
                    // Send confirmation email
                    $emailSent = sendPasswordChangeConfirmation($userEmail, $userName);
                    
                    // Log successful password reset
                    logActivity('PASSWORD_RESET_SUCCESS', "User ID: $userId, Email: $userEmail");
                    
                    // Set success message and redirect to login
                    $_SESSION['login_success'] = "Your password has been reset successfully! " . 
                                                ($emailSent ? "A confirmation email has been sent to your email address." : "");
                    
                    header("Location: userlogin.php");
                    exit();
                    
                } else {
                    $_SESSION['password_reset_error'] = "Failed to update password. Please try again.";
                    logError("Failed to update password for user ID: $userId");
                }
                
                $updateStmt->close();
            } else {
                $_SESSION['password_reset_error'] = "Database error occurred. Please try again.";
                logError("Failed to prepare password update query");
            }
            
        } else {
            $_SESSION['password_reset_error'] = "This password reset link is invalid or has expired. Please request a new password reset.";
            logActivity('PASSWORD_RESET_INVALID_TOKEN_USED', "Invalid token: $token");
        }
        
        $stmt->close();
    } else {
        $_SESSION['password_reset_error'] = "Database error occurred. Please try again.";
        logError("Failed to prepare token verification query");
    }
    
    closeConnection();
    header("Location: reset_password.php?token=" . urlencode($token));
    exit();
    
} else {
    // Not a POST request
    header("Location: forgot_password.php");
    exit();
}
?>